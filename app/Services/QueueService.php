<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\QueueTicket;
use App\Repositories\QueueRepository;
use Carbon\Carbon;
use DB;
use Exception;

class QueueService
{
    /**
     * Get active ticket for a user (today)
     */
    public function getActiveTicketForUser($user)
    {
        return $this->queueRepository->findActiveTicketForUser($user->id);
    }

    /**
     * Get display queue data for TV display
     */
    public function getDisplayQueue($workshopId)
    {
        $tickets = $this->queueRepository->getDisplayTickets($workshopId);
        $currentServing = $tickets->where('status', QueueTicket::STATUS_SERVING)->first();
        $waitingList = $tickets->where('status', QueueTicket::STATUS_WAITING)->values();

        return [
            'now_serving' => $currentServing,
            'upcoming' => $waitingList,
        ];
    }

    const AVG_SERVICE_TIME = 20;

    protected $queueRepository;

    public function __construct(QueueRepository $queueRepository)
    {
        $this->queueRepository = $queueRepository;
    }

    /**
     * Get/Create Queue Hari Ini & Hitung Status Traffic
     */
    public function getTodayQueue($workshopId)
    {
        $queue = $this->queueRepository->getTodayQueue($workshopId);
        $activeCount = $this->queueRepository->countActiveTickets($queue);
        $status = Queue::TRAFFIC_STATUS_QUIET;
        if ($activeCount > 2 && $activeCount <= 5) {
            $status = Queue::TRAFFIC_STATUS_MODERATE;
        } elseif ($activeCount > 5) {
            $status = Queue::TRAFFIC_STATUS_BUSY;
        }
        if ($queue->traffic_status !== $status) {
            $queue->update(['traffic_status' => $status]);
        }

        return [
            'queue' => $queue,
            'active_count' => $activeCount,
            'estimated_wait_time' => $activeCount * self::AVG_SERVICE_TIME,
        ];
    }

    /**
     * User Ambil Antrian (Remote Queuing)
     */
    public function bookTicket($user, $workshopId)
    {
        return DB::transaction(function () use ($user, $workshopId) {
            $hasActive = $this->queueRepository->findActiveTicketForUser($user->id);
            if ($hasActive) {
                throw new Exception('Anda sudah memiliki antrian aktif hari ini.');
            }
            $queueData = $this->getTodayQueue($workshopId);
            $queue = $queueData['queue'];
            $dailyTotal = $this->queueRepository->countDailyTickets($queue);
            $number = $dailyTotal + 1;
            $ticketCode = 'A-'.str_pad($number, 3, '0', STR_PAD_LEFT);
            $minutesToWait = $queueData['active_count'] * self::AVG_SERVICE_TIME;
            $estimatedServeAt = Carbon::now()->addMinutes($minutesToWait);
            $ticket = $this->queueRepository->createTicket([
                'queue_id' => $queue->id,
                'workshop_id' => $workshopId,
                'customer_id' => $user->id,
                'ticket_code' => $ticketCode,
                'status' => QueueTicket::STATUS_WAITING,
                'estimated_serve_at' => $estimatedServeAt,
                'qr_code' => $ticketCode.'-'.$user->id,
            ]);
            $this->getTodayQueue($workshopId);

            return $ticket;
        });
    }

    /**
     * Mekanik Panggil/Proses Tiket (Scan QR)
     */
    public function processTicket($mechanicUser, $ticketCode)
    {
        $ticket = $this->queueRepository->findTicketByCode($ticketCode);
        if (! $ticket) {
            throw new Exception('Tiket tidak ditemukan atau status tidak valid (Sudah diproses/Cancel).');
        }
        $ticket->update([
            'status' => QueueTicket::STATUS_SERVING,
            'mechanic_id' => $mechanicUser->id,
        ]);
        $this->getTodayQueue($ticket->workshop_id);

        return $ticket;
    }

    /**
     * Update Tiket Selesai (DONE)
     */
    public function completeTicket($ticketId)
    {
        $ticket = $this->queueRepository->findTicketById($ticketId);
        $ticket->update(['status' => QueueTicket::STATUS_DONE]);
        $this->getTodayQueue($ticket->workshop_id);

        return $ticket;
    }
}

<?php

namespace App\Repositories;

use App\Models\Queue;
use App\Models\QueueTicket;
use Carbon\Carbon;

class QueueRepository
{
    public function getDisplayTickets($workshopId)
    {
        return QueueTicket::where('workshop_id', $workshopId)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', [
                QueueTicket::STATUS_WAITING,
                QueueTicket::STATUS_SERVING,
            ])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getTodayQueue($workshopId)
    {
        return Queue::firstOrCreate(
            [
                'workshop_id' => $workshopId,
                'date' => Carbon::today(),
            ],
            [
                'traffic_status' => Queue::TRAFFIC_STATUS_MODERATE,
            ]
        );
    }

    public function countActiveTickets($queue)
    {
        return $queue->tickets()
            ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING])
            ->count();
    }

    public function countDailyTickets($queue)
    {
        return $queue->tickets()->count();
    }

    public function findActiveTicketForUser($userId)
    {
        return QueueTicket::where('customer_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING])
            ->latest()
            ->first();
    }

    public function createTicket($data)
    {
        return QueueTicket::create($data);
    }

    public function findTicketByCode($ticketCode)
    {
        return QueueTicket::where('ticket_code', $ticketCode)
            ->whereDate('created_at', Carbon::today())
            ->where('status', QueueTicket::STATUS_WAITING)
            ->first();
    }

    public function findTicketById($ticketId)
    {
        return QueueTicket::findOrFail($ticketId);
    }
}

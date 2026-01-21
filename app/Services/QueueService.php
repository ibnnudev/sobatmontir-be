<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\QueueTicket;
use Carbon\Carbon;
use DB;
use Exception;

class QueueService
{
    // Rata-rata waktu service (20 menit)
    const AVG_SERVICE_TIME = 20;

    /**
     * Get/Create Queue Hari Ini & Hitung Status Traffic
     */
    public function getTodayQueue($workshopId)
    {
        $queue = Queue::firstOrCreate(
            [
                'workshop_id' => $workshopId,
                'date' => Carbon::today(),
            ],
            [
                'traffic_status' => Queue::TRAFFIC_STATUS_NORMAL,
            ]
        );

        // Hitung ulang antrian yang aktif (WAITING + SERVING)
        $activeCount = $queue->tickets()
            ->whereIn(
                'status',
                [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING]
            )
            ->count();

        // Tentukan Status Warna (Logic Traffic Light)
        $status = Queue::TRAFFIC_STATUS_QUIET;
        if ($activeCount > 2 && $activeCount <= 5) {
            $status = Queue::TRAFFIC_STATUS_NORMAL;
        } elseif ($activeCount > 5) {
            $status = Queue::TRAFFIC_STATUS_BUSY;
        }

        // Update jika status berubah
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
            // Validasi: User tidak boleh punya ticket aktif di hari yang sama
            $hasActive = QueueTicket::where('customer', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING])
                ->exists();

            if ($hasActive) {
                throw new Exception('Anda sudah memiliki antrian aktif hari ini.');
            }

            // Ambil Queue Hari Ini
            $queueData = $this->getTodayQueue($workshopId);
            $queue = $queueData['queue'];

            /**
             * Generate Nomor Tiket (A-00X)
             * Hitung total tiket hari ini (termasuk yg sudah selesai/cancel) untuk running number
             */
            $dailyTotal = $queue->tickets()->count();
            $number = $dailyTotal + 1;
            $ticketCode = 'A-'.str_pad($number, 3, '0', STR_PAD_LEFT);

            /**
             * Hitung Estimasi Jam Dilayani
             * Estimasi = Sekarang + (Jumlah Menunggu * 20 Menit)
             */
            $minutesToWait = $queueData['active_count'] * self::AVG_SERVICE_TIME;
            $estimatedServeAt = Carbon::now()->addMinutes($minutesToWait);

            // Simpan Tiket
            $ticket = QueueTicket::create([
                'queue_id' => $queue->id,
                'workshop_id' => $workshopId,
                'customer_id' => $user->id,
                'ticket_code' => $ticketCode,
                'status' => QueueTicket::STATUS_WAITING,
                'estimated_serve_at' => $estimatedServeAt,
                'qr_code' => $ticketCode.'-'.$user->id, // Simple string for QR
            ]);

            // Refresh status traffic queue induk
            $this->getTodayQueue($workshopId);

            return $ticket;
        });
    }

    /**
     * Mekanik Panggil/Proses Tiket (Scan QR)
     */
    public function processTicket($mechanicUser, $ticketCode)
    {
        // Cari ticket hari ini berdasarkan kode
        $ticket = QueueTicket::where('ticket_code', $ticketCode)
            ->whereDate('created_at', Carbon::today())
            ->where('status', QueueTicket::STATUS_WAITING)
            ->first();

        if (! $ticket) {
            throw new Exception('Tiket tidak ditemukan atau status tidak valid (Sudah diproses/Cancel).');
        }

        // Update status jadi SERVING
        $ticket->update([
            'status' => QueueTicket::STATUS_SERVING,
            'mechanic_id' => $mechanicUser->id,
        ]);

        // Update traffic bengkel
        $this->getTodayQueue($ticket->workshop_id);

        return $ticket;
    }

    /**
     * Update Tiket Selesai (DONE)
     */
    public function completeTicket($ticketId)
    {
        $ticket = QueueTicket::findOrFail($ticketId);
        $ticket->update(['status' => QueueTicket::STATUS_DONE]);

        // Update traffic bengkel (berkurang)
        $this->getTodayQueue($ticket->workshop_id);

        return $ticket;
    }
}

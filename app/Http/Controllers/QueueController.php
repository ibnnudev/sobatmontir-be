<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    // [CUSTOMER] Cek Status Bengkel (Traffic Light)
    public function checkStatus(Request $request, $workshopId)
    {
        $data = $this->queueService->getTodayQueue($workshopId);

        return response()->json([
            'status' => $data['queue']->traffic_status,
            'active_queue' => $data['active_count'],
            'wait_time_minutes' => $data['estimated_wait_time'],
            'message' => $this->getMessage($data['queue']->traffic_status),
        ]);
    }

    // [CUSTOMER] Ambil Antrian
    public function book(Request $request)
    {
        $request->validate(['workshop_id' => 'required|exists:workshops,id']);

        try {
            $ticket = $this->queueService->bookTicket($request->user(), $request->workshop_id);

            return response()->json(
                ['message' => 'Antrian berhasil diambil!', 'data' => $ticket],
                201
            );
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal mengambil antrian: '.$th->getMessage(),
            ], 400);
        }
    }

    // [CUSTOMER] Lihat Tiket Saya Hari Ini
    public function myTicket(Request $request)
    {
        $ticket = QueueTicket::where('customer_id', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', [
                QueueTicket::STATUS_WAITING,
                QueueTicket::STATUS_SERVING,
            ])
            ->with('queue')
            ->latest()
            ->first();

        if (! $ticket) {
            return response()->json([
                'message' => 'Anda tidak memiliki tiket aktif hari ini.',
            ], 404);
        }

        return response()->json(['data' => $ticket]);
    }

    // [MECHANIC] Scan QR / Panggil Nomor (Start Servis)
    public function serve(Request $request)
    {
        $request->validate(['ticket_code' => 'required']);

        //  Cek Permission
        if (! $request->user()->can('queue.call')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $ticket = $this->queueService->processTicket($request->user(), $request->ticket_code);

            return response()->json(['message' => 'Mulai mengerjakan servis', 'data' => $ticket]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Gagal memproses tiket: '.$th->getMessage(),
            ], 400);
        }
    }

    // [TV DISPLAY] List Antrian untuk TV Bengkel
    public function display(Request $request, $workshopId)
    {
        // Ambil tiket yang Waiting dan Serving
        $tickets = QueueTicket::where('workshop_id', $workshopId)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', [
                QueueTicket::STATUS_WAITING,
                QueueTicket::STATUS_SERVING,
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $currentServing = $tickets->where('status', QueueTicket::STATUS_SERVING)->first();
        $waitingList = $tickets->where('status', QueueTicket::STATUS_WAITING)->values();

        return response()->json([
            'now_serving' => $currentServing,
            'upcoming' => $waitingList,
        ]);
    }

    public function getMessage($status)
    {
        switch ($status) {
            case \App\Models\Queue::TRAFFIC_STATUS_QUIET:
                'Langsung dikerjakan! Gas ke bengkel.';
            case \App\Models\Queue::TRAFFIC_STATUS_NORMAL:
                'Sedikit antrian, harap bersabar.';
            case \App\Models\Queue::TRAFFIC_STATUS_BUSY:
                'Banyak antrian, siapkan waktu tunggu.';
            default:
                return '';
        }
    }
}

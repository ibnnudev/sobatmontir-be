<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Services\QueueService;
use App\Http\Requests\QueueBookRequest;
use App\Http\Requests\QueueServeRequest;
use App\Http\Responses\ApiResponse;
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
        return ApiResponse::success([
            'status' => $data['queue']->traffic_status,
            'active_queue' => $data['active_count'],
            'wait_time_minutes' => $data['estimated_wait_time'],
            'message' => $this->getMessage($data['queue']->traffic_status),
        ]);
    }

    // [CUSTOMER] Ambil Antrian
    public function book(QueueBookRequest $request)
    {
        try {
            $ticket = $this->queueService->bookTicket($request->user(), $request->workshop_id);
            return ApiResponse::success($ticket, 'Antrian berhasil diambil!', 201);
        } catch (\Throwable $th) {
            return ApiResponse::error('Gagal mengambil antrian: ' . $th->getMessage(), 400);
        }
    }

    // [CUSTOMER] Lihat Tiket Saya Hari Ini
    public function myTicket(Request $request)
    {
        $ticket = $this->queueService->getActiveTicketForUser($request->user());
        if (!$ticket) {
            return ApiResponse::error('Anda tidak memiliki tiket aktif hari ini.', 404);
        }
        return ApiResponse::success($ticket);
    }

    // [MECHANIC] Scan QR / Panggil Nomor (Start Servis)
    public function serve(QueueServeRequest $request)
    {
        try {
            $ticket = $this->queueService->processTicket($request->user(), $request->ticket_code);
            return ApiResponse::success($ticket, 'Mulai mengerjakan servis');
        } catch (\Throwable $th) {
            return ApiResponse::error('Gagal memproses tiket: ' . $th->getMessage(), 400);
        }
    }

    // [TV DISPLAY] List Antrian untuk TV Bengkel
    public function display(Request $request, $workshopId)
    {
        $displayData = $this->queueService->getDisplayQueue($workshopId);
        return ApiResponse::success($displayData);
    }

    public function getMessage($status)
    {
        switch ($status) {
            case \App\Models\Queue::TRAFFIC_STATUS_QUIET:
                return 'Langsung dikerjakan! Gas ke bengkel.';
            case \App\Models\Queue::TRAFFIC_STATUS_MODERATE:
                return 'Sedikit antrian, harap bersabar.';
            case \App\Models\Queue::TRAFFIC_STATUS_BUSY:
                return 'Banyak antrian, siapkan waktu tunggu.';
            default:
                return '';
        }
    }
}

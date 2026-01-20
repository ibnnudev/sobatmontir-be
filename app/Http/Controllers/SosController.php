<?php

namespace App\Http\Controllers;

use App\Http\Requests\SosRequest;
use App\Http\Requests\SosStatusUpdateRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\SosRepository;
use App\Services\SosService;
use Illuminate\Http\Request;

class SosController extends Controller
{
    protected $sosService;

    protected $sosRepository;

    public function __construct(SosService $sosService, SosRepository $sosRepository)
    {
        $this->sosService = $sosService;
        $this->sosRepository = $sosRepository;
    }

    // [CUSTOMER] Panggil Mekanik
    public function requestSos(SosRequest $request)
    {
        try {
            $data = $this->sosService->createRequest($request->user(), $request->validated());

            return ApiResponse::success($data, 'Sinyal SOS disebarkan! Mencari mekanik terdekat...');
        } catch (\Throwable $th) {
            return ApiResponse::error('Gagal membuat permintaan SOS: '.$th->getMessage(), 500);
        }
    }

    // [MECANIC] Cek Order di Sekitar (Polling)
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
        if (! $request->user()->can('sos.accept')) {
            return ApiResponse::error('Unauthorized', 403);
        }
        $orders = $this->sosService->getNearbyRequests($request->user(), $request->lat, $request->lng);

        return ApiResponse::success($orders);
    }

    // [MECHANIC] Terima Order
    public function accept(Request $request, $id)
    {
        if (! $request->user()->can('sos.accept')) {
            return ApiResponse::error('Unauthorized', 403);
        }
        try {
            $data = $this->sosService->acceptRequest($request->user(), $id);

            return ApiResponse::success($data, 'Order diterima! Segera meluncur.');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // [MECHANIC] Update Status (OTW, Sampai, dll)
    public function updateStatus(SosStatusUpdateRequest $request, $id)
    {
        try {
            $data = $this->sosService->updateStatus($request->user(), $id, $request->status);

            return ApiResponse::success($data, 'Status diperbarui.');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    // [CUSTOMER] Cek Status Order saya (Polling)
    public function myActiveOrder(Request $request)
    {
        $order = $this->sosRepository->findActiveByCustomer($request->user()->id);
        if (! $order) {
            return ApiResponse::success(null, 'Tidak ada order aktif');
        }

        return ApiResponse::success($order);
    }
}

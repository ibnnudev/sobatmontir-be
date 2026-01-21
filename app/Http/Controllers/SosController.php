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
            $result = $this->sosService->requestSos($request->user(), $request->validated());

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // [MECANIC] Cek Order di Sekitar (Polling)
    public function nearby(Request $request)
    {
        try {
            $result = $this->sosService->nearby($request->user(), $request->all());

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // [MECHANIC] Terima Order
    public function accept(Request $request, $id)
    {
        try {
            $result = $this->sosService->accept($request->user(), $id);

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // [MECHANIC] Update Status (OTW, Sampai, dll)
    public function updateStatus(SosStatusUpdateRequest $request, $id)
    {
        try {
            $result = $this->sosService->updateStatus($request->user(), $id, $request->status);

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // [CUSTOMER] Cek Status Order saya (Polling)
    public function myActiveOrder(Request $request)
    {
        try {
            $result = $this->sosService->myActiveOrder($request->user());

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * [MECHANIC] Finalisasi & Pembayaran
     */
    public function finalize(Request $request, $id)
    {
        try {
            $result = $this->sosService->finalize($request->user(), $id, $request->all());

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}

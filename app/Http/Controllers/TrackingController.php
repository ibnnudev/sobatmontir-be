<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingOrderRequest;
use App\Http\Requests\TrackingUpdateLocationRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\TrackingRepository;
use App\Services\TrackingService;

class TrackingController extends Controller
{
    protected $trackingService;

    protected $trackingRepository;

    public function __construct(TrackingService $trackingService, TrackingRepository $trackingRepository)
    {
        $this->trackingService = $trackingService;
        $this->trackingRepository = $trackingRepository;
    }

    /**
     * [MECHANIC] Kirim Lokasi (Heartbeat)
     */
    public function updateLocation(TrackingUpdateLocationRequest $request)
    {
        $this->trackingService->updateMechanicLocation($request->user(), $request->lat, $request->lng);

        return ApiResponse::success(null, 'Lokasi diperbarui.');
    }

    /**
     * [CUSTOMER] Lacak Mekanik
     */
    public function trackOrder(TrackingOrderRequest $request, $orderId)
    {
        try {
            $result = $this->trackingService->getTrackingInfo($request->user(), $orderId);

            return ApiResponse::success($result);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 400);
        }
    }
}

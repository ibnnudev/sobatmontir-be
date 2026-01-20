<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftCloseRequest;
use App\Http\Requests\ShiftOpenRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    use AuthorizesRequests;

    protected $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * POST: /api/shifts/open
     * Membuka laci kasir (Modal Awal)
     */
    public function open(ShiftOpenRequest $request)
    {
        $this->authorize('open', Shift::class);
        $user = $request->user();
        $workshopId = $user->ownedWorkshops->first()->id ?? $user->mechanicProfile->workshop_id ?? null;
        if (! $workshopId) {
            return ApiResponse::error('User tidak terdaftar di bengkel manapun.', 403);
        }
        try {
            $shift = $this->shiftService->openShift($user, $request->opening_cash, $workshopId);

            return ApiResponse::success($shift, 'Shift berhasil dibuka.', 201);
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 400);
        }
    }

    /**
     * GET: /api/shifts/current
     * Cek status kasir (Dashboard State)
     */
    public function current(Request $request)
    {
        $shift = $this->shiftService->getCurrentOpenShift($request->user());
        $this->authorize('view', $shift ?? Shift::class);
        if (! $shift) {
            return ApiResponse::success(['status' => 'CLOSED', 'message' => 'Tidak ada shift aktif.']);
        }

        return ApiResponse::success([
            'status' => Shift::STATUS_OPEN,
            'data' => $shift,
            'current_system_cash' => $shift->opening_cash + $shift->cash_in,
        ]);
    }

    /**
     * POST: /api/shifts/close
     * Tutup laci & Rekonsiliasi
     */
    public function close(ShiftCloseRequest $request)
    {
        $this->authorize('close', Shift::class);
        try {
            $shift = $this->shiftService->closeShift($request->user(), $request->closing_cash);
            $summary = $this->shiftService->getShiftSummary($shift);

            return ApiResponse::success(['summary' => $summary], 'Shift berhasil ditutup.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Tidak ada shift aktif untuk ditutup.', 404);
        } catch (\Exception $th) {
            return ApiResponse::error($th->getMessage(), 400);
        }
    }
}

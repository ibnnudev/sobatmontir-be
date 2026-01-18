<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
    public function open(Request $request)
    {
        $this->authorize('open', Shift::class);
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);
        $user = $request->user();
        $workshopId = $user->ownedWorkshops->first()->id ?? $user->mechanicProfile->workshop_id ?? null;
        if (!$workshopId) {
            return response()->json(['User tidak terdaftar di bengkel manapun.'], 403);
        }
        try {
            $shift = $this->shiftService->openShift($user, $request->opening_cash, $workshopId);
            return response()->json(['message' => 'Shift berhasil dibuka.', 'data' => $shift], 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    /**
     * GET: /api/shifts/current
     * Cek status kasir (Dashboard State)
     */
    public function current(Request $request)
    {
        $shift = Shift::where('cashier_id', $request->user()->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();
        $this->authorize('view', $shift ?? Shift::class);
        if (!$shift) {
            return response()->json(['status' => 'CLOSED', 'message' => 'Tidak ada shift aktif.'], 200);
        }
        return response()->json([
            'status' => Shift::STATUS_OPEN,
            'data' => $shift,
            'current_system_cash' => $shift->opening_cash + $shift->cash_in
        ]);
    }

    /**
     * POST: /api/shifts/close
     * Tutup laci & Rekonsiliasi
     */
    public function close(Request $request)
    {
        $this->authorize('close', Shift::class);
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);
        try {
            $shift = $this->shiftService->closeShift($request->user(), $request->closing_cash);
            $summary = $this->shiftService->getShiftSummary($shift);
            return response()->json([
                'message' => 'Shift berhasil ditutup.',
                'summary' => $summary,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Tidak ada shift aktif untuk ditutup.'], 404);
        } catch (\Exception $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

}

<?php

namespace App\Services;

use App\Models\Shift;
use App\Repositories\ShiftRepository;
use DB;
use Exception;

class ShiftService
{
    protected $shiftRepository;

    public function __construct(ShiftRepository $shiftRepository)
    {
        $this->shiftRepository = $shiftRepository;
    }
    /**
     * Buka Shift Baru
     * @param $user, $amount, $workshopId
     */
    public function openShift($user, $amount, $workshopId)
    {
        $existingShift = $this->shiftRepository->findOpenByCashier($user->id);
        if ($existingShift) {
            throw new Exception("Anda masih memiliki shift yang terbuka (ID: {$existingShift->id}). Harap tutup shift sebelumnya.");
        }
        return $this->shiftRepository->create([
            'workshop_id' => $workshopId,
            'cashier_id' => $user->id,
            'opening_cash' => $amount,
            'total_sales' => 0,
            'status' => Shift::STATUS_OPEN,
            'opened_at' => now(),
        ]);
    }

    /**
     * Tutup Shift (Rekonsiliasi)
     * @param $user, $realClosingCash
     */
    public function closeShift($user, $realClosingCash)
    {
        $shift = $this->shiftRepository->findOpenByCashier($user->id);
        return DB::transaction(function () use ($shift, $realClosingCash) {
            $expectedCash = $shift->opening_cash + $shift->cash_in;
            $difference = $realClosingCash - $expectedCash;
            $this->shiftRepository->update($shift, [
                'closing_cash' => $realClosingCash,
                'cash_difference' => $difference,
                'status' => Shift::STATUS_CLOSED,
                'closed_at' => now(),
            ]);
            return $shift;
        });
    }

    /**
     * Dapatkan Laporan Ringkas Shift (Breakdown Payment)
     */
    public function getShiftSummary($shift)
    {
        $paymentSummary = $shift->transactions()
            ->select('payment_method', DB::raw('SUM(total) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
        return [
            'shift_info' => $shift,
            'expected_cash_in_drawer' => $shift->opening_cash + $shift->cash_in,
            'actual_cash_in_drawer' => $shift->closing_cash,
            'differenece' => $shift->cash_difference,
            'sales_breakdown' => $paymentSummary,
        ];
    }

    public function getCurrentOpenShift($user)
    {
        return $this->shiftRepository->findOpenByCashier($user->id);
    }
}
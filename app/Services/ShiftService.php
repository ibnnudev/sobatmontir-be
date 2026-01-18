<?php

namespace App\Services;

use App\Models\Shift;
use DB;
use Exception;

class ShiftService
{
    /**
     * Buka Shift Baru
     * @param $user, $amount, $workshopId
     */
    public function openShift($user, $amount, $workshopId)
    {
        // Validasi: User tidak boleh punya shift yang masih OPEN
        $existingShift = Shift::where('cashier_id', $user->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();

        if ($existingShift) {
            throw new Exception("Anda masih memiliki shift yang terbuka (ID: {$existingShift->id}). Harap tutup shift sebelumnya.");
        }

        return Shift::create([
            'workshop_id' => $workshopId,
            'cashier_id' => $user->id,
            'opening_cash' => $amount,
            'total_sales' => 0, // Belum ada penjualan tunai
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
        // Cari shift aktif milik user ini
        $shift = Shift::where('cashier_id', $user->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();

        return DB::transaction(function () use ($shift, $realClosingCash) {
            /**
             * Hitung Uang Sistem
             * Rumus: Modal awal + Uang Tunai Masuk (Dari Penjualan Cash)
             * Catatan: total_sales mencakup QRIS, jadi kita pakai cash_in
             */
            $expectedCash = $shift->opening_cash + $shift->cash_in;

            /**
             * Hitung Selisih
             * Jika positif: Uang Lebih. Jika negatif: Uang Kurang/Hilang
             */
            $difference = $realClosingCash - $expectedCash;

            // Update Shift
            $shift->update([
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
}
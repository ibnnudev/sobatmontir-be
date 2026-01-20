<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * POST /api/pos/checkout
     * Melakukan Transaksi Quick POS
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string|in:CASH,QRIS',
            'paid_amount' => 'required_if:payment_method,CASH|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Cek Permission
        // User harus punya hak akses membuat transaksi
        if (!$request->user()->can('transaction.create')) {
            return response()->json(['message' => 'Unauthorized. Anda tidak memiliki akses kasir.'], 403);
        }

        try {
            $result = $this->transactionService->createTransaction($request->user(), $request->all());
            return response()->json([
                'message' => 'Transaksi berhasil dibuat.',
                'data' => $result,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Transaksi gagal. ' . $th->getMessage(),
            ], 400);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use DB;
use Exception;

class TransactionService
{
    public function createTransaction($user, array $data)
    {
        // Cek Shift Kasir (Wajib Open)
        $shift = Shift::where('cashier_id', $user->id)
            ->where('status', Shift::STATUS_OPEN)
            ->first();

        if (!$shift) {
            throw new Exception('Shift kasir belum dibuka.');
        }

        // Perisapkan data & validasi stok (pre-calculation)
        $cartItems = $data['items'];
        $grandTotal = 0;
        $preparedItems = [];

        // Ambil semua produk sekaligus biar hemat query
        $productIds = array_column($cartItems, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cartItems as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new Exception("Produk dengan ID {$item['product_id']} tidak ditemukan.");
            }

            // Validasi stok
            if (!$product->is_service && $product->stock < $item['qty']) {
                throw new Exception("Stok produk '{$product->name}' tidak mencukupi. Sisa: {$product->stock}");
            }

            $subtotal = $product->price * $item['qty'];
            $grandTotal += $subtotal;

            $preparedItems[] = [
                'product_id' => $product->id,
                'product' => $product,
                'qty' => $item['qty'],
                'price' => $product->price,
                'subtotal' => $subtotal,
            ];
        }

        // Hitung kembalian (Change)
        $paymentMethod = $data['payment_method'];
        $paidAmount = $data['paid_amount'] ?? $grandTotal; // Jika QRIS, anggap pas

        if ($paymentMethod == Transaction::PAYMENT_METHOD_CASH && $paidAmount < $grandTotal) {
            throw new Exception('Uang tunai kurang. Total: ' . $grandTotal . ', Dibayar: ' . $paidAmount);
        }

        $change = $paidAmount - $grandTotal;

        // Database Transaction
        return DB::transaction(function () use ($user, $shift, $data, $preparedItems, $paymentMethod, $paidAmount, $change, $grandTotal) {
            // Header Transaksi
            $transaction = Transaction::create([
                'shift_id' => $shift->id,
                'customer_id' => $data['customer_id'] ?? null, // Optional member
                'total' => $grandTotal,
                'payment_method' => $paymentMethod,
                'status' => Transaction::STATUS_PAID,
            ]);

            // Proses Item & Stok
            foreach ($preparedItems as $item) {
                // Simpan item transaksi
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product']->id,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Kurangi stok & catat log (jika bukan jasa)
                if (!$item['product']->is_service) {
                    $item['product']->decrement('stock', $item['qty']);

                    StockMovement::create([
                        'product_id' => $item['product']->id,
                        'transaction_id' => $transaction->id,
                        'movement_type' => StockMovement::MOVEMENT_TYPE_SALE,
                        'qty_change' => -($item['qty']), // Negatif karena keluar
                        'reason' => 'Penjualan POS'
                    ]);
                }
            }

            // Catat Pemabayaran
            Payment::create([
                'transaction_id' => $transaction->id,
                'method' => $paymentMethod,
                'amount' => $paidAmount, // Yang dicatat sistem sesuai total tagihan
            ]);

            // Update Shift (Uang Masuk)
            $shift->increment('total_sales', $grandTotal);
            if ($paymentMethod == Transaction::PAYMENT_METHOD_CASH) {
                $shift->increment('cash_in', $grandTotal);
            }

            // Return Data untuk Struk
            return [
                'transaction' => $transaction->load('items.product'),
                'receipt' => [
                    'store_name' => $shift->workshop->name,
                    'store_address' => $shift->workshop->address,
                    'cashier' => $user->name,
                    'date' => now()->format('d/m/Y H:i'),
                    'trx_id' => $transaction->id,
                    'items' => $preparedItems, // Format simpel untuk printer
                    'total' => $grandTotal,
                    'payment_method' => $paymentMethod,
                    'paid_amount' => $paidAmount,
                    'change' => $change,
                ]
            ];
        });
    }
}
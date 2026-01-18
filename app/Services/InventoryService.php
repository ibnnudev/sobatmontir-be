<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use DB;
use Exception;

class InventoryService
{
    /**
     * Hanle Create Product dengan Logic Service/Barang
     */
    public function createProduct(array $data, string $workshopId)
    {
        return DB::transaction(function () use ($data, $workshopId) {
            // 1. Sanitasi Logic: Jika Service, sto dan min_stock set 0
            if (isset($data['is_service']) && $data['is_service']) {
                $data['stock'] = 0;
                $data['min_stock'] = 0;
            }

            // 2. Buat Produk
            $product = Product::create([
                ...$data,
                'workshop_id' => $workshopId,
            ]);

            // 3. Jika Barang Fisik & Ada Stok Awal > 0, catat sebagai Initial Balance
            if (! $product->is_service && $product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => StockMovement::MOVEMENT_TYPE_ADJUSTMENT,
                    'qty_change' => $product->stock,
                    'reason' => 'Stok Awal (Initial Balance)',
                ]);
            }
        });
    }

    /**
     * Handle Stock Opname (Penyesuaian Stok Manual)
     */
    public function adjustStock(string $productId, int $realQty, string $userId, string $reason)
    {
        return DB::transaction(function () use ($productId, $realQty, $userId, $reason) {
            // Lock row for update untuk mencegah race condition
            $product = Product::lockForUpdate()->findOrFail($productId);

            if ($product->is_service) {
                throw new Exception('Jasa tidak memiliki stok untuk disesuaikan.');
            }

            $qtyBefore = $product->stock;
            $qtyDiff = $realQty - $qtyBefore;

            if ($qtyDiff === 0) {
                return $product; // Tidak ada perubahan
            }

            // 1. Update Master Product
            $product->update(['stock' => $realQty]);

            // 2. Catat Stock Adjustment (Audit Trail siapa yang ubah)
            StockAdjustment::create([
                'product_id' => $product->id,
                'adjusted_by' => $userId,
                'qty_before' => $qtyBefore,
                'qty_after' => $realQty,
                'reason' => $reason,
                'approved_at' => now(), // Anggap auto-approve untuk owner
            ]);

            // 3. Catat Stock Movement (Kartu Stok)
            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => StockMovement::MOVEMENT_TYPE_ADJUSTMENT,
                'qty_change' => $qtyDiff, // Bisa positif atau negatif
                'reason' => 'Stock Opname: '.$reason,
            ]);

            return $product;
        });
    }
}

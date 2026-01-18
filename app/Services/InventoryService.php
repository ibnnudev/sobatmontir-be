<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Repositories\ProductRepository;
use DB;
use Exception;

class InventoryService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Hanle Create Product dengan Logic Service/Barang
     */
    public function createProduct(array $data, string $workshopId)
    {
        return DB::transaction(function () use ($data, $workshopId) {
            if (isset($data['is_service']) && $data['is_service']) {
                $data['stock'] = 0;
                $data['min_stock'] = 0;
            }
            $product = $this->productRepository->create([
                ...$data,
                'workshop_id' => $workshopId,
            ]);
            if (! $product->is_service && $product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => StockMovement::MOVEMENT_TYPE_ADJUSTMENT,
                    'qty_change' => $product->stock,
                    'reason' => 'Stok Awal (Initial Balance)',
                ]);
            }

            return $product;
        });
    }

    /**
     * Handle Stock Opname (Penyesuaian Stok Manual)
     */
    public function adjustStock(string $productId, int $realQty, string $userId, string $reason)
    {
        return DB::transaction(function () use ($productId, $realQty, $userId, $reason) {
            $product = $this->productRepository->lockForUpdate($productId);
            if ($product->is_service) {
                throw new Exception('Jasa tidak memiliki stok untuk disesuaikan.');
            }
            $qtyBefore = $product->stock;
            $qtyDiff = $realQty - $qtyBefore;
            if ($qtyDiff === 0) {
                return $product;
            }
            $this->productRepository->updateStock($product, $realQty);
            StockAdjustment::create([
                'product_id' => $product->id,
                'adjusted_by' => $userId,
                'qty_before' => $qtyBefore,
                'qty_after' => $realQty,
                'reason' => $reason,
                'approved_at' => now(),
            ]);
            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => StockMovement::MOVEMENT_TYPE_ADJUSTMENT,
                'qty_change' => $qtyDiff,
                'reason' => 'Stock Opname: '.$reason,
            ]);

            return $product;
        });
    }
}

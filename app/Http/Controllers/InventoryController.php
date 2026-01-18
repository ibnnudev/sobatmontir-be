<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Requests\InventoryAdjustRequest;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * API: POST /api/inventory/adjust
     */
    public function adjust(InventoryAdjustRequest $request)
    {
        $product = Product::findOrFail($request->product_id);
        Gate::authorize('update', $product);
        try {
            $updatedProduct = $this->inventoryService->adjustStock(
                $request->product_id,
                $request->real_qty,
                $request->user()->id,
                $request->reason
            );
            return ApiResponse::success([
                'current_stock' => $updatedProduct->stock,
            ], 'Stok berhasil disesuaikan');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}

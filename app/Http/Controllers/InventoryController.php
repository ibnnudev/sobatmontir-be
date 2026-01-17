<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\Http\Request;
use App\Models\Product;
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
    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'real_qty' => 'required|integer|min:0',
            'reason' => 'required|string|min:5',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Gunakan Policy: Hanya Owner yang boleh adjust stok
        Gate::authorize('update', $product);

        try {
            $updatedProduct = $this->inventoryService->adjustStock(
                $request->product_id,
                $request->real_qty,
                $request->user()->id,
                $request->reason
            );

            return response()->json([
                'message' => 'Stok berhasil disesuaikan',
                'current_stock' => $updatedProduct->stock
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
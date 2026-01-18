<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductDestroyRequest;
use App\Models\Product;
use App\Services\InventoryService;
use App\Repositories\ProductRepository;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    protected $inventoryService;
    protected $productRepository;

    public function __construct(InventoryService $inventoryService, ProductRepository $productRepository)
    {
        $this->inventoryService = $inventoryService;
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $workshopId = $user->ownedWorkshops->first()->id ?? null;
        if (!$workshopId) {
            return ApiResponse::error('User tidak memiliki bengkel aktif.', 403);
        }
        $filters = [
            'search' => $request->input('search'),
            'low_stock' => $request->input('low_stock'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ];
        $limit = $request->input('limit', 10);
        $products = $this->productRepository->getByWorkshopId($workshopId, $filters, $limit);
        return ApiResponse::success($products);
    }

    public function store(ProductStoreRequest $request)
    {
        Gate::authorize('create', Product::class);
        $workshopId = $request->user()->ownedWorkshops->first()->id;
        $product = $this->inventoryService->createProduct($request->all(), $workshopId);
        return ApiResponse::success($product, 'Produk berhasil dibuat.', 201);
    }

    /**
     * Update data produk (KECUALI STOK).
     * Stok hanya boleh berubah lewat Transaksi atau Stock Opname.
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        Gate::authorize('update', $product);
        $data = $request->except(['stock', 'workshop_id']);
        $product->update($data);
        return ApiResponse::success($product, 'Data produk diperbarui (Stok tidak berubah)');
    }

    /**
     * Hapus produk dengan aman.
     */
    public function destroy(ProductDestroyRequest $request)
    {
        $id = $request->id;
        $product = Product::withCount(['stockMovements', 'transactionItems'])->findOrFail($id);
        Gate::authorize('delete', $product);
        if ($product->stock_movements_count > 0 || $product->transaction_items_count > 0) {
            return ApiResponse::error('Gagal hapus! Produk ini sudah memiliki riwayat transaksi atau stok. Silakan non-aktifkan saja (is_active=false) jika tidak ingin dijual.', 422);
        }
        $product->delete();
        return ApiResponse::success(null, 'Produk berhasil dihapus permanen');
    }

    /**
     * Menampilkan detail satu produk
     */
    public function show($id)
    {
        $product = Product::with([
            'stockMovements' => function ($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);
        // Optionally: Gate::authorize('view', $product);
        return ApiResponse::success($product);
    }
}

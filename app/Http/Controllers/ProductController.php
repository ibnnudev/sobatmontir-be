<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use Gate;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $workshopId = $request->user()->ownedWorkshops->first()->id;
        $query = Product::where('workshop_id', $workshopId);

        // Filter Low Stock
        if ($request->has('low_stock')) {
            $query->lowStock();
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required_if:is_service,false|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'is_service' => 'boolean',
        ]);

        Gate::authorize('create', Product::class);

        $workshopId = $request->user()->ownedWorkshops->first()->id;

        $product = $this->inventoryService->createProduct($request->all(), $workshopId);
        return response()->json([
            'message' => 'Produk berhasil dibuat.',
            'data' => $product
        ], 201);
    }

    /**
     * Update data produk (KECUALI STOK).
     * Stok hanya boleh berubah lewat Transaksi atau Stock Opname.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Cek Policy (Hanya owner yang boleh edit)
        Gate::authorize('update', $product);

        $request->validate([
            'name' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            // Kita TIDAK memvalidasi 'stock' disini agar user tidak bisa input
        ]);

        // Ambil data request, tapi buang field 'stock' jika user iseng mengirimnya
        $data = $request->except(['stock', 'workshop_id']);

        $product->update($data);

        return response()->json([
            'message' => 'Data produk diperbarui (Stok tidak berubah)',
            'data' => $product
        ]);
    }

    /**
     * Hapus produk dengan aman.
     */
    public function destroy($id)
    {
        $product = Product::withCount(['stockMovements', 'transactionItems'])->findOrFail($id);

        // Cek Policy
        Gate::authorize('delete', $product);

        // Validasi: Jangan hapus produk yang sudah ada riwayat transaksi/stok!
        if ($product->stock_movements_count > 0 || $product->transaction_items_count > 0) {
            return response()->json([
                'message' => 'Gagal hapus! Produk ini sudah memiliki riwayat transaksi atau stok. Silakan non-aktifkan saja (is_active=false) jika tidak ingin dijual.'
            ], 422); // Unprocessable Entity
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus permanen']);
    }

    /**
     * Menampilkan detail satu produk
     */
    public function show($id)
    {
        // Load relasi stock movements agar user bisa lihat kartu stok di detail barang
        $product = Product::with([
            'stockMovements' => function ($query) {
                $query->latest()->limit(10); // Ambil 10 riwayat terakhir
            }
        ])->findOrFail($id);

        // Opsional: Cek Policy 'view' jika ingin membatasi akses
        // Gate::authorize('view', $product);

        return response()->json($product);
    }
}

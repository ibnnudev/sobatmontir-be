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
        // 1. Ambil Workshop ID dari User yang login
        // (Asumsi: User adalah Owner yang punya workshop)
        $user = $request->user();
        $workshopId = $user->ownedWorkshops->first()->id ?? null;

        if (!$workshopId) {
            return response()->json(['message' => 'User tidak memiliki bengkel aktif.'], 403);
        }

        // 2. Mulai Query
        $query = Product::where('workshop_id', $workshopId);

        // --- FILTER 1: Pencarian Nama (Search) ---
        // Contoh: ?search=Oli
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // --- FILTER 2: Low Stock (Peringatan Stok) ---
        // Contoh: ?low_stock=1
        if ($request->filled('low_stock') && $request->low_stock == '1') {
            $query->lowStock(); // Memanggil Scope yang sudah kita buat di Model
        }

        // --- FILTER 3: Status Aktif ---
        // Contoh: ?is_active=1 (Hanya yang aktif untuk POS)
        // Contoh: ?is_active=0 (Hanya yang non-aktif/soft deleted untuk Gudang)
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // --- SORTING ---
        // Urutkan dari yang terbaru dibuat
        $query->latest(); 
        
        // --- PAGINATION ---
        // Gunakan pagination biar ringan kalau datanya ribuan
        // Default 10 item per halaman, atau bisa diatur via ?limit=50
        $limit = $request->input('limit', 10);
        
        return response()->json($query->paginate($limit));
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
            'is_active' => 'sometimes|boolean',
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

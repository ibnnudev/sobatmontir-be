<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    const MOVEMENT_TYPE_SALE = 'sale';
    const MOVEMENT_TYPE_ADJUSTMENT = 'adjustment';

    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'transaction_id', // Nullable (hanya terisi jika movement_type = SALE)
        'movement_type',  // 'SALE', 'ADJUSTMENT'
        'qty_change',     // Bisa negatif (keluar) atau positif (masuk)
        'reason',
    ];

    protected $casts = [
        'qty_change' => 'integer',
    ];

    /**
     * Produk yang mengalami pergerakan stok.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Transaksi terkait (jika pergerakan disebabkan oleh penjualan).
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // --- Scopes untuk mempermudah Query Laporan ---

    public function scopeSales($query)
    {
        return $query->where('movement_type', 'SALE');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('movement_type', 'ADJUSTMENT');
    }
}
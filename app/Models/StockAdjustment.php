<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory, HasUuids;

    // Tabel ini biasanya tidak butuh updated_at, karena sifatnya log (sekali buat)
    // Tapi jika schema pakai timestamps(), biarkan default.

    protected $fillable = [
        'product_id',
        'adjusted_by', // User ID (Owner/Admin)
        'qty_before',
        'qty_after',
        'reason',
        'approved_at',
    ];

    protected $casts = [
        'qty_before' => 'integer',
        'qty_after' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Produk yang disesuaikan.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User yang melakukan penyesuaian (Owner/Kepala Gudang).
     */
    public function adjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Helper untuk mendapatkan selisih (Misal: -5 atau +10)
     */
    public function getDifferenceAttribute(): int
    {
        return $this->qty_after - $this->qty_before;
    }
}
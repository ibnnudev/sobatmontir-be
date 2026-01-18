<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'is_service' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class)->latest();
    }

    // Scopes

    /**
     * Filter produk yang stoknya menipis (dibawah ataus sama dengan min_stock)
     */
    public function scopeLowStock(Builder $query)
    {
        return $query->where('is_service', false)
            ->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Helper attribut untuk frontend
     */
    public function getIsLowStockAttribute()
    {
        if ($this->is_service) {
            return $this->stock <= $this->min_stock;
        }
    }
}

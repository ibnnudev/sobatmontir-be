<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;
    protected $guarded = ['id'];
    protected $casts = ['is_service' => 'boolean', 'price' => 'decimal:2'];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    /**
     * Menonaktifkan timestamps default (created_at, updated_at)
     * karena di migration tabel payments hanya ada 'paid_at'.
     */
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'method', // 'CASH', 'QRIS'
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2', // Memastikan angka desimal tetap presisi
        'paid_at' => 'datetime',
    ];

    /**
     * Relasi ke Transaksi Induk
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
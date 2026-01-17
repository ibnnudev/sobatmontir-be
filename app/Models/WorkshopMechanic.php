<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopMechanic extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'workshop_mechanics';

    protected $fillable = [
        'workshop_id',
        'mechanic_id',
        'mechanic_type', // 'IN_SHOP', 'MOBILE'
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the workshop that owns the mechanic assignment.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Get the user (mechanic) associated with this assignment.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Scope untuk mencari mekanik mobile yang aktif
     */
    public function scopeMobile($query)
    {
        return $query->where('mechanic_type', 'MOBILE')->where('is_active', true);
    }

    /**
     * Scope untuk mencari mekanik in-shop yang aktif
     */
    public function scopeInShop($query)
    {
        return $query->where('mechanic_type', 'IN_SHOP')->where('is_active', true);
    }
}
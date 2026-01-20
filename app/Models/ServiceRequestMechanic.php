<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestMechanic extends Model
{
    use HasFactory, HasUuids;

    /**
     * Non-aktifkan timestamps default (created_at, updated_at).
     * Sesuai migration, tabel ini hanya punya kolom 'accepted_at'.
     */
    public $timestamps = false;

    protected $fillable = [
        'service_request_id',
        'mechanic_id',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * Relasi ke Order SOS (Service Request)
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Relasi ke User (Mekanik)
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
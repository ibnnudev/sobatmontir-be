<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'workshop_id', // Nullable saat awal broadcast
        'problem_type',
        'pickup_lat',
        'pickup_lng',
        'status', // BROADCAST, ACCEPTED, ON_THE_WAY, ARRIVED, PROCESSING, DONE, CANCELLED
        'cancellation_reason',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function mechanics()
    {
        return $this->belongsToMany(User::class, 'service_request_mechanics', 'service_request_id', 'mechanic_id')
            ->withPivot('accepted_at');
    }

    public function mechanic()
    {
        // Relasi lewat tabel pivot service_request_mechanics
        // Untuk mengetahui siapa mekanik yang menangani order ini
        return $this->hasOneThrough(
            User::class,
            ServiceRequestMechanic::class,
            'service_request_id', // FK di pivot
            'id', // PK di user
            'id', // PK di service_request
            'mechanic_id' // FK di pivot
        );
    }
}

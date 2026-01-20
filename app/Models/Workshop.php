<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'is_open' => 'boolean',
        'is_mobile_service_enabled' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function mechanics()
    {
        return $this->belongsToMany(User::class, 'workshop_mechanics', 'workshop_id', 'mechanic_id')
            ->withPivot('mechanic_type', 'is_active');
    }

    public function services()
    {
        return $this->hasMany(WorkshopService::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }

    // Scopes
    /**
     * Scope untuk mencari workshop mobile di sekitar lokasi user (Radius km)
     */
    public function scopeNearby($query, $lat, $lng, $radius = 3)
    {
        // Rumus Haversine (Hitung jarak bola bumi)
        $haversine = "(6371 * acos(cos(radians($lat)) 
                     * cos(radians(latitude)) 
                     * cos(radians(longitude) - radians($lng)) 
                     + sin(radians($lat)) 
                     * sin(radians(latitude))))";

        return $query->select('*') // Ambil semua kolom
            ->selectRaw("{$haversine} AS distance") // Tambah kolom virtual 'distance'
            ->where('is_open', true) // Bengkel harus buka
            ->where('is_mobile_service_enabled', true) // Harus terima panggilan
            ->having('distance', '<=', $radius) // Filter radius
            ->orderBy('distance', 'asc'); // Urutkan dari yang terdekat
    }
}

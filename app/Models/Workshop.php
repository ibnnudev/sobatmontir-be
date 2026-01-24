<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function galleries()
    {
        return $this->hasMany(WorkshopGallery::class);
    }

    // Scopes
    /**
     * Scope untuk mencari workshop mobile di sekitar lokasi user (Radius km)
     */
    public function scopeNearby($query, $lat, $lng, $radius = 10)
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

    public function scopeOpen24Hours($query)
    {
        return $query->whereHas('services', function ($q) {
            $q->where('is_24_hours', true);
        });
    }

    public function scopeWithService(Builder $query, $serviceName)
    {
        return $query->whereHas('services', function ($q) use ($serviceName) {
            $q->whereLike('service_name', '%' . $serviceName . '%');
        });
    }

    /**
     * Virtual Attributes: Rating rata-rata
     * Bisa dipanggil via $workshop->average_rating
     */
    public function getAverageRatingAttribute()
    {
        // Rounding ke 1 desimal, misal 4.5
        return round($this->reviews()->average('rating'), 1) ?? 0;
    }

    // Total Reviews
    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    protected $appends = [
        'average_rating',
        'review_count',
    ];
}

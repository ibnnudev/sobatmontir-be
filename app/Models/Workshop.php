<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    use HasUuids;

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
}

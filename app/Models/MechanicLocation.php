<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MechanicLocation extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'mechanic_id',
        'latitude',
        'longitude',
        'updated_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'updated_at' => 'datetime',
    ];

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}

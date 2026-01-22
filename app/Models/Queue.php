<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory, HasUuids;

    const TRAFFIC_STATUS_QUIET = 'SEPI';

    const TRAFFIC_STATUS_MODERATE = 'SEDANG';

    const TRAFFIC_STATUS_BUSY = 'RAME';

    protected $fillable = [
        'workshop_id',
        'date',
        'traffic_status', // SEPI, SEDANG, RAME
    ];

    public function tickets()
    {
        return $this->hasMany(QueueTicket::class);
    }
}

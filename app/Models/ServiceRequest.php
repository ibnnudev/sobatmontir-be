<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

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
}

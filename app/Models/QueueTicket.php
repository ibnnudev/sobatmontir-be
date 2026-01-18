<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = ['estimated_serve_at' => 'datetime'];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

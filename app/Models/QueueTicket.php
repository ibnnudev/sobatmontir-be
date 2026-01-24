<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    use HasFactory, HasUuids;

    const STATUS_WAITING = 'WAITING';

    const STATUS_SERVING = 'SERVING';

    const STATUS_DONE = 'DONE';

    const STATUS_CANCELED = 'CANCELED';

    protected $fillable = [
        'queue_id',
        'workshop_id',
        'customer_id',
        'mechanic_id',
        'ticket_code', // A-001
        'status',
        'estimated_serve_at',
        'qr_code', // Bisa string text untuk digenerate di FE
    ];

    protected $casts = [
        'estimated_serve_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }
}

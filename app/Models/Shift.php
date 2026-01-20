<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';

    use HasUuids, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }
}

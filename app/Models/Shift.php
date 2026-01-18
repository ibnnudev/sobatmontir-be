<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

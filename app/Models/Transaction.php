<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    const PAYMENT_METHOD_CASH = 'CASH';

    const PAYMENT_METHOD_CARD = 'CARD';

    const PAYMENT_METHOD_QRIS = 'QRIS';

    const STATUS_PENDING = 'PENDING';

    const STATUS_PAID = 'PAID';

    const STATUS_COMPLETED = 'COMPLETED';

    use HasUuids;

    protected $guarded = ['id'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}

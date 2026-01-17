<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
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
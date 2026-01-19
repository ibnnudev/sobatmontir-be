<?php

namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    public function create(array $data)
    {
        return Transaction::create($data);
    }

    public function findOpenShiftByCashier($cashierId)
    {
        return \App\Models\Shift::where('cashier_id', $cashierId)
            ->where('status', \App\Models\Shift::STATUS_OPEN)
            ->first();
    }
}

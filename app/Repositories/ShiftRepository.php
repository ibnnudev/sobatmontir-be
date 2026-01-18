<?php

namespace App\Repositories;

use App\Models\Shift;

class ShiftRepository
{
    public function findOpenByCashier($cashierId)
    {
        return Shift::where('cashier_id', $cashierId)
            ->where('status', Shift::STATUS_OPEN)
            ->first();
    }

    public function create(array $data)
    {
        return Shift::create($data);
    }

    public function update(Shift $shift, array $data)
    {
        $shift->update($data);
        return $shift;
    }
}

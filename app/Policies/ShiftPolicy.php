<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function view(User $user, ?Shift $shift = null)
    {
        return $user->can('shift.view');
    }

    public function open(User $user)
    {
        return $user->can('shift.open');
    }

    public function close(User $user, ?Shift $shift = null)
    {
        return $user->can('shift.close');
    }
}

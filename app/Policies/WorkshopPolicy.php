<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    /**
     * Determine whether the user can update the workshop details.
     */
    public function update(User $user, Workshop $workshop): bool
    {
        // Logic sederhana: User ID harus sama dengan Owner ID di bengkel
        return $user->id === $workshop->owner_id;
    }

    /**
     * Determine whether the user can view the daily report of the workshop.
     */
    public function viewReport(User $user, Workshop $workshop): bool
    {
        // Cek Permission Spatie
        if (! $user->can('finance.view_report')) {
            return false;
        }

        // Cek Kepemilikan
        return $user->id === $workshop->owner_id;
    }
}

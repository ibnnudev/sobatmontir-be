<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships
    public function ownedWorkshops()
    {
        return $this->hasMany(Workshop::class, 'owner_id');
    }

    public function mechanicProfile()
    {
        return $this->hasOne(WorkshopMechanic::class, 'mechanic_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

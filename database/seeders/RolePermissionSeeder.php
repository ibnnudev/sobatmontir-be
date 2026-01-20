<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Definisi Permission dari config
        $permissions = config('permissions.all');
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // SOS Feature: Ensure new permissions exist
        $sosPermissions = ['sos.create', 'sos.accept', 'sos.view', 'sos.update', 'sos.cancel'];
        foreach ($sosPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 3. Definisi Roles & Assignment dari config
        $rolesConfig = config('roles.roles');
        $rolePermissions = config('roles.permissions');

        foreach ($rolesConfig as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $permissionsForRole = $rolePermissions[$roleName] ?? [];
            $role->givePermissionTo($permissionsForRole);
        }

        // --- SUPER ADMIN (Opsional, akses semua) ---
        $admin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin->givePermissionTo(Permission::all());
    }
}

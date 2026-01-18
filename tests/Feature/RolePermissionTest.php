<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @test */
    public function all_roles_in_config_are_seeded()
    {
        $roles = config('roles.roles');
        foreach ($roles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    /** @test */
    public function all_permissions_in_config_are_seeded()
    {
        $permissions = config('permissions.all');
        foreach ($permissions as $permissionName) {
            $this->assertDatabaseHas('permissions', ['name' => $permissionName]);
        }
    }

    /** @test */
    public function roles_have_correct_permissions()
    {
        $rolePermissions = config('roles.permissions');
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Role $roleName does not exist");
            foreach ($permissions as $permissionName) {
                $this->assertTrue($role->hasPermissionTo($permissionName), "Role $roleName missing permission $permissionName");
            }
        }
    }
}

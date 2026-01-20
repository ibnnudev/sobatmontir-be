<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShiftPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        // Ensure permissions exist for the web guard
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'shift.open', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'shift.close', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'shift.view', 'guard_name' => 'web']);
    }

    /** @test */
    public function given_user_with_permission_when_open_shift_then_allowed()
    {
        // Given
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'mechanic_in_shop']);
        $role->givePermissionTo('shift.open');
        $user->assignRole('mechanic_in_shop');
        $workshop = \App\Models\Workshop::factory()->create(['owner_id' => $user->id]);

        // When & Then
        $this->actingAs($user);
        $this->assertTrue($user->can('shift.open'));
        $response = $this->postJson('/api/shifts/open', ['opening_cash' => 10000]);
        $response->assertStatus(201);
    }

    /** @test */
    public function given_user_without_permission_when_open_shift_then_forbidden()
    {
        // Given
        $user = User::factory()->create();

        // When & Then
        $this->actingAs($user);
        $this->assertFalse($user->can('shift.open'));
        $response = $this->postJson('/api/shifts/open', ['opening_cash' => 10000]);
        $response->assertStatus(403);
    }

    /** @test */
    public function given_user_with_permission_when_view_current_shift_then_allowed()
    {
        // Given
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'mechanic_in_shop']);
        $role->givePermissionTo('shift.view');
        $user->assignRole('mechanic_in_shop');
        $workshop = Workshop::factory()->create(['owner_id' => $user->id]);
        $shift = Shift::factory()->create([
            'cashier_id' => $user->id,
            'workshop_id' => $workshop->id,
            'status' => Shift::STATUS_OPEN,
        ]);

        // When & Then
        $this->actingAs($user);
        $this->assertTrue($user->can('shift.view'));
        $response = $this->getJson('/api/shifts/current');
        $response->assertStatus(200);
    }

    /** @test */
    public function given_user_without_permission_when_view_current_shift_then_forbidden()
    {
        // Given
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create(['owner_id' => $user->id]);
        $shift = Shift::factory()->create([
            'cashier_id' => $user->id,
            'workshop_id' => $workshop->id,
            'status' => Shift::STATUS_OPEN,
        ]);

        // When & Then
        $this->actingAs($user);
        $this->assertFalse($user->can('shift.view'));
        $response = $this->getJson('/api/shifts/current');
        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DefaultRolesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_roles_are_seeded_by_migration(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'Admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Marshall', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Executor', 'guard_name' => 'web']);

        $user = User::factory()->create();

        // Checking hasRole for Marshall should NOT throw RoleDoesNotExist exception
        $this->assertFalse($user->hasRole('Marshall'));
        $this->assertFalse($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('Executor'));

        // Assigning role should succeed smoothly
        $user->assignRole('Marshall');
        $this->assertTrue($user->hasRole('Marshall'));
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        // Create sample Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@plantrack.test'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole($adminRole);

        // Create sample Executor
        $executor = User::firstOrCreate(
            ['email' => 'executor@plantrack.test'],
            [
                'name' => 'John Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor->assignRole($executorRole);

        // Add two new Executors
        $executor2 = User::firstOrCreate(
            ['email' => 'executor2@plantrack.test'],
            [
                'name' => 'Sarah Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor2->assignRole($executorRole);

        $executor3 = User::firstOrCreate(
            ['email' => 'executor3@plantrack.test'],
            [
                'name' => 'Marcus Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor3->assignRole($executorRole);

        // Create sample Marshall
        $marshall = User::firstOrCreate(
            ['email' => 'marshall@plantrack.test'],
            [
                'name' => 'Mary Marshall',
                'password' => bcrypt('password'),
            ]
        );
        $marshall->assignRole($marshallRole);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Executor']);
    }

    public function test_creates_admin_user_with_provided_options(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Commander Shepard',
            '--email' => 'shepard@normandy.test',
            '--password' => 'NormandySR2!',
        ])
            ->expectsOutputToContain('Administrator [Commander Shepard] successfully provisioned')
            ->assertSuccessful();

        $user = User::where('email', 'shepard@normandy.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Commander Shepard', $user->name);
        $this->assertTrue(Hash::check('NormandySR2!', $user->password));
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->must_reset_password);
    }

    public function test_creates_admin_user_with_generated_password_when_omitted(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Auto Key Admin',
            '--email' => 'autokey@admin.test',
        ])
            ->expectsOutputToContain('Administrator [Auto Key Admin] successfully provisioned')
            ->expectsOutputToContain('Auto-generated (Temporary/Random)')
            ->assertSuccessful();

        $user = User::where('email', 'autokey@admin.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertNotEmpty($user->password);
    }

    public function test_creates_admin_user_with_reset_flag(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Reset Admin',
            '--email' => 'reset@admin.test',
            '--password' => 'TemporaryPass123!',
            '--reset' => true,
        ])
            ->expectsOutputToContain('Yes (Enforced on 1st login)')
            ->assertSuccessful();

        $user = User::where('email', 'reset@admin.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue($user->must_reset_password);
    }

    public function test_fails_when_user_exists_without_force_flag(): void
    {
        User::factory()->create([
            'email' => 'existing@plantrack.test',
            'name' => 'Existing User',
        ]);

        $this->artisan('admin:create', [
            '--name' => 'Existing User',
            '--email' => 'existing@plantrack.test',
            '--password' => 'NewPassword123!',
        ])
            ->expectsOutputToContain('A user with email [existing@plantrack.test] already exists')
            ->expectsOutputToContain('Use the --force option')
            ->assertFailed();
    }

    public function test_grants_admin_role_to_existing_user_with_force_flag(): void
    {
        $existing = User::factory()->create([
            'email' => 'operative@plantrack.test',
            'name' => 'Original Operative',
            'password' => Hash::make('OldPassword123!'),
            'must_reset_password' => false,
        ]);
        $existing->assignRole('Executor');

        $this->assertFalse($existing->hasRole('Admin'));

        $this->artisan('admin:create', [
            '--name' => 'Promoted Operative',
            '--email' => 'operative@plantrack.test',
            '--password' => 'UpgradedPassword123!',
            '--reset' => true,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully granted Admin role to existing operative')
            ->assertSuccessful();

        $existing->refresh();
        $this->assertEquals('Promoted Operative', $existing->name);
        $this->assertTrue($existing->hasRole('Admin'));
        $this->assertTrue(Hash::check('UpgradedPassword123!', $existing->password));
        $this->assertTrue($existing->must_reset_password);
    }

    public function test_validation_fails_on_invalid_inputs(): void
    {
        // Invalid email format
        $this->artisan('admin:create', [
            '--name' => 'Bad Email User',
            '--email' => 'not-an-email',
            '--password' => 'ValidPassword123!',
        ])
            ->expectsOutputToContain('Failed to create admin user due to validation errors')
            ->assertFailed();

        // Password too short
        $this->artisan('admin:create', [
            '--name' => 'Short Pass User',
            '--email' => 'short@admin.test',
            '--password' => 'short',
        ])
            ->expectsOutputToContain('The password field must be at least 8 characters')
            ->assertFailed();
    }

    public function test_interactive_prompts_work(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Enter the administrator full name', 'Interactive Admin')
            ->expectsQuestion('Enter the administrator email address', 'interactive@admin.test')
            ->expectsQuestion('Enter administrator password (leave empty to generate a random key)', 'InteractivePass123!')
            ->expectsOutputToContain('Administrator [Interactive Admin] successfully provisioned')
            ->assertSuccessful();

        $user = User::where('email', 'interactive@admin.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertTrue(Hash::check('InteractivePass123!', $user->password));
    }

    public function test_interactive_prompts_with_empty_password_generates_random_key(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Enter the administrator full name', 'Interactive Random Admin')
            ->expectsQuestion('Enter the administrator email address', 'interactive-random@admin.test')
            ->expectsQuestion('Enter administrator password (leave empty to generate a random key)', '')
            ->expectsOutputToContain('Administrator [Interactive Random Admin] successfully provisioned')
            ->expectsOutputToContain('Auto-generated (Temporary/Random)')
            ->assertSuccessful();

        $user = User::where('email', 'interactive-random@admin.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertNotEmpty($user->password);
    }

    public function test_make_admin_alias_works(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'Alias Admin',
            '--email' => 'alias@admin.test',
            '--password' => 'AliasPassword123!',
        ])
            ->expectsOutputToContain('Administrator [Alias Admin] successfully provisioned')
            ->assertSuccessful();

        $user = User::where('email', 'alias@admin.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Admin'));
    }
}

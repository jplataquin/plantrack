<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResetAdminPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $executorRole;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $this->executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->adminUser = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@plantrack.test',
            'password' => Hash::make('InitialPassword123!'),
            'must_reset_password' => true,
        ]);
        $this->adminUser->assignRole($this->adminRole);
    }

    public function test_resets_admin_password_with_email_argument_and_custom_password(): void
    {
        $this->artisan('admin:reset-password', [
            'email' => 'admin@plantrack.test',
            '--password' => 'NewSecurePassword123!',
        ])
            ->expectsOutputToContain('Successfully reset password for administrator [System Administrator]')
            ->expectsOutputToContain('Custom provided')
            ->expectsOutputToContain('No')
            ->assertSuccessful();

        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->adminUser->password));
        $this->assertFalse($this->adminUser->must_reset_password);
    }

    public function test_resets_admin_password_using_email_option(): void
    {
        $this->artisan('admin:reset-password', [
            '--email' => 'admin@plantrack.test',
            '--password' => 'AnotherNewPassword!',
        ])
            ->expectsOutputToContain('Successfully reset password for administrator')
            ->assertSuccessful();

        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('AnotherNewPassword!', $this->adminUser->password));
    }

    public function test_generates_random_password_when_password_option_omitted(): void
    {
        $this->artisan('admin:reset-password', [
            'email' => 'admin@plantrack.test',
        ])
            ->expectsOutputToContain('Successfully reset password for administrator')
            ->expectsOutputToContain('Auto-generated (Temporary/Random)')
            ->expectsOutputToContain('Make sure to securely save or transmit the temporary password above')
            ->assertSuccessful();

        $this->adminUser->refresh();
        $this->assertFalse(Hash::check('InitialPassword123!', $this->adminUser->password));
        $this->assertNotEmpty($this->adminUser->password);
    }

    public function test_sets_must_reset_password_flag_when_reset_option_is_passed(): void
    {
        $this->adminUser->update(['must_reset_password' => false]);

        $this->artisan('admin:reset-password', [
            'email' => 'admin@plantrack.test',
            '--password' => 'TemporaryPassword123!',
            '--reset' => true,
        ])
            ->expectsOutputToContain('Yes (Enforced on 1st login)')
            ->assertSuccessful();

        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('TemporaryPassword123!', $this->adminUser->password));
        $this->assertTrue($this->adminUser->must_reset_password);
    }

    public function test_fails_when_user_does_not_exist(): void
    {
        $this->artisan('admin:reset-password', [
            'email' => 'nonexistent@plantrack.test',
            '--password' => 'SomePassword123!',
        ])
            ->expectsOutputToContain('No user found with email [nonexistent@plantrack.test]')
            ->assertFailed();
    }

    public function test_fails_when_user_is_not_an_admin_without_force_flag(): void
    {
        $operative = User::factory()->create([
            'name' => 'Field Operative',
            'email' => 'operative@plantrack.test',
            'password' => Hash::make('OperativePassword!'),
        ]);
        $operative->assignRole($this->executorRole);

        $this->artisan('admin:reset-password', [
            'email' => 'operative@plantrack.test',
            '--password' => 'NewOperativePass123!',
        ])
            ->expectsOutputToContain('User [Field Operative] (operative@plantrack.test) is not an administrator')
            ->expectsOutputToContain('Use the --force option')
            ->assertFailed();

        $operative->refresh();
        $this->assertFalse($operative->hasRole('Admin'));
        $this->assertTrue(Hash::check('OperativePassword!', $operative->password));
    }

    public function test_grants_admin_role_and_resets_password_with_force_flag(): void
    {
        $operative = User::factory()->create([
            'name' => 'Promoted Operative',
            'email' => 'promoted@plantrack.test',
            'password' => Hash::make('OldOperativePass!'),
        ]);
        $operative->assignRole($this->executorRole);

        $this->artisan('admin:reset-password', [
            'email' => 'promoted@plantrack.test',
            '--password' => 'AdminAccessPass123!',
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully reset password for administrator [Promoted Operative]')
            ->assertSuccessful();

        $operative->refresh();
        $this->assertTrue($operative->hasRole('Admin'));
        $this->assertTrue(Hash::check('AdminAccessPass123!', $operative->password));
    }

    public function test_fails_when_email_is_invalid(): void
    {
        $this->artisan('admin:reset-password', [
            'email' => 'invalid-email-address',
            '--password' => 'ValidPassword123!',
        ])
            ->expectsOutputToContain('Failed to reset admin password due to validation errors')
            ->expectsOutputToContain('The email field must be a valid email address')
            ->assertFailed();
    }

    public function test_fails_when_password_is_too_short(): void
    {
        $this->artisan('admin:reset-password', [
            'email' => 'admin@plantrack.test',
            '--password' => 'short',
        ])
            ->expectsOutputToContain('The password field must be at least 8 characters')
            ->assertFailed();
    }

    public function test_can_be_invoked_via_aliases(): void
    {
        $this->artisan('admin:password', [
            'email' => 'admin@plantrack.test',
            '--password' => 'AliasPassword123!',
        ])
            ->expectsOutputToContain('Successfully reset password for administrator')
            ->assertSuccessful();

        $this->artisan('admin:reset', [
            'email' => 'admin@plantrack.test',
            '--password' => 'AliasResetPass123!',
        ])
            ->expectsOutputToContain('Successfully reset password for administrator')
            ->assertSuccessful();

        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('AliasResetPass123!', $this->adminUser->password));
    }
}

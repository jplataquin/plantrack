<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExecutorCreationAndResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $marshall;

    protected User $executor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Marshall']);
        Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create([
            'name' => 'Fleet Marshall',
            'email' => 'marshall@plantrack.test',
        ]);
        $this->marshall->assignRole('Marshall');

        $this->executor = User::factory()->create([
            'name' => 'Standard Executor',
            'email' => 'executor@plantrack.test',
            'must_reset_password' => false,
        ]);
        $this->executor->assignRole('Executor');
    }

    public function test_marshall_can_view_provision_executor_page(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('executors.create'));

        $response->assertStatus(200);
        $response->assertSee('PROVISION EXECUTOR ACCOUNT');
        $response->assertSee('TEMPORARY PASSWORD');
    }

    public function test_provision_executor_link_is_removed_from_navbar_but_present_on_executors_index(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('executors.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Provision Executor');
        $response->assertSee(route('executors.create'));
        $response->assertSee('NEW EXECUTOR');

        // Confirm Plan status filter and "Assign New Plan" button are removed from /executors
        $response->assertDontSee('PLANS STATUS: ALL');
        $response->assertDontSee('Assign New Plan');
        $response->assertSee('VIEW ALL PLAN RECORDS');
    }

    public function test_executor_cannot_view_provision_executor_page(): void
    {
        $response = $this->actingAs($this->executor)->get(route('executors.create'));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_viewing_provision_page(): void
    {
        $response = $this->get(route('executors.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_marshall_can_create_executor_with_custom_temporary_password(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('executors.store'), [
            'name' => 'Operative Jane Doe',
            'email' => 'jane.doe@example.test',
            'temporary_password' => 'TempSecretKey123!',
        ]);

        $createdUser = User::where('email', 'jane.doe@example.test')->first();

        $this->assertNotNull($createdUser);
        $this->assertEquals('Operative Jane Doe', $createdUser->name);
        $this->assertTrue($createdUser->must_reset_password);
        $this->assertTrue($createdUser->hasRole('Executor'));
        $this->assertTrue(Hash::check('TempSecretKey123!', $createdUser->password));

        $response->assertRedirect(route('executors.show', $createdUser));
        $response->assertSessionHas('created_executor_password', 'TempSecretKey123!');
        $response->assertSessionHas('created_executor_email', 'jane.doe@example.test');
    }

    public function test_marshall_can_create_executor_with_auto_generated_temporary_password(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('executors.store'), [
            'name' => 'Operative Auto Password',
            'email' => 'auto.pass@example.test',
            'temporary_password' => '',
        ]);

        $createdUser = User::where('email', 'auto.pass@example.test')->first();

        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->must_reset_password);
        $this->assertTrue($createdUser->hasRole('Executor'));

        $response->assertRedirect(route('executors.show', $createdUser));
        $generatedPassword = session('created_executor_password');
        $this->assertNotEmpty($generatedPassword);
        $this->assertGreaterThanOrEqual(8, strlen($generatedPassword));
        $this->assertTrue(Hash::check($generatedPassword, $createdUser->password));
    }

    public function test_non_marshall_cannot_create_executor_account(): void
    {
        $response = $this->actingAs($this->executor)->post(route('executors.store'), [
            'name' => 'Hacker Operative',
            'email' => 'hacker@example.test',
            'temporary_password' => 'somePassword123',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.test']);
    }

    public function test_cannot_create_executor_with_existing_email(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('executors.store'), [
            'name' => 'Duplicate Email',
            'email' => 'executor@plantrack.test', // already taken
            'temporary_password' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_cannot_create_executor_with_short_temporary_password(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('executors.store'), [
            'name' => 'Short Pass Operative',
            'email' => 'shortpass@example.test',
            'temporary_password' => 'short', // < 8 characters
        ]);

        $response->assertSessionHasErrors(['temporary_password']);
    }

    public function test_user_with_must_reset_password_is_redirected_to_force_reset_page(): void
    {
        $tempUser = User::factory()->create([
            'name' => 'Locked Operative',
            'email' => 'locked@example.test',
            'password' => Hash::make('TemporaryKey123'),
            'must_reset_password' => true,
        ]);
        $tempUser->assignRole('Executor');

        // Trying to access executors list
        $response = $this->actingAs($tempUser)->get(route('executors.index'));
        $response->assertRedirect(route('password.force_reset'));

        // Trying to access create plan
        $response = $this->actingAs($tempUser)->get(route('plans.create'));
        $response->assertRedirect(route('password.force_reset'));

        // Can access the force reset view
        $response = $this->actingAs($tempUser)->get(route('password.force_reset'));
        $response->assertStatus(200);
        $response->assertSee('FIRST-LOGIN PASSWORD CONFIGURATION REQUIRED');
    }

    public function test_user_in_must_reset_state_can_logout(): void
    {
        $tempUser = User::factory()->create([
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($tempUser)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_force_reset_fails_if_current_temporary_password_is_incorrect(): void
    {
        $tempUser = User::factory()->create([
            'password' => Hash::make('ActualTemp123!'),
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($tempUser)->post(route('password.force_reset.update'), [
            'current_password' => 'WrongPassword',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertTrue($tempUser->fresh()->must_reset_password);
    }

    public function test_force_reset_fails_if_new_password_is_same_as_temporary_password(): void
    {
        $tempUser = User::factory()->create([
            'password' => Hash::make('SameOldTemp123!'),
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($tempUser)->post(route('password.force_reset.update'), [
            'current_password' => 'SameOldTemp123!',
            'password' => 'SameOldTemp123!',
            'password_confirmation' => 'SameOldTemp123!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue($tempUser->fresh()->must_reset_password);
    }

    public function test_force_reset_succeeds_and_unlocks_account(): void
    {
        $tempUser = User::factory()->create([
            'password' => Hash::make('ValidTemp123!'),
            'must_reset_password' => true,
        ]);
        $tempUser->assignRole('Executor');

        $response = $this->actingAs($tempUser)->post(route('password.force_reset.update'), [
            'current_password' => 'ValidTemp123!',
            'password' => 'PermanentSecret999!',
            'password_confirmation' => 'PermanentSecret999!',
        ]);

        $response->assertRedirect(route('executors.index'));
        $response->assertSessionHas('success');

        $freshUser = $tempUser->fresh();
        $this->assertFalse($freshUser->must_reset_password);
        $this->assertTrue(Hash::check('PermanentSecret999!', $freshUser->password));

        // Now user can access protected pages normally
        $executorsResponse = $this->actingAs($freshUser)->get(route('executors.index'));
        $executorsResponse->assertStatus(200);
    }

    public function test_normal_user_is_redirected_away_from_force_reset_page(): void
    {
        $normalUser = User::factory()->create([
            'must_reset_password' => false,
        ]);

        $response = $this->actingAs($normalUser)->get(route('password.force_reset'));

        $response->assertRedirect(route('executors.index'));
    }
}

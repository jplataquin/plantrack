<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $marshall;
    protected User $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->admin = User::factory()->create([
            'name' => 'Root Administrator',
            'email' => 'admin@test.com',
            'must_reset_password' => false,
        ]);
        $this->admin->assignRole($adminRole);

        $this->marshall = User::factory()->create([
            'name' => 'Field Marshall',
            'email' => 'marshall@test.com',
            'must_reset_password' => false,
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create([
            'name' => 'Special Executor',
            'email' => 'executor@test.com',
            'must_reset_password' => false,
        ]);
        $this->executor->assignRole($executorRole);
    }

    public function test_guest_is_redirected_from_admin_module(): void
    {
        $response = $this->get(route('admin.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.users.create'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.marshalls.create'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.executors.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_executor_cannot_access_admin_module(): void
    {
        $response = $this->actingAs($this->executor)->get(route('admin.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->get(route('admin.users.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->get(route('admin.marshalls.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->get(route('admin.executors.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->post(route('admin.users.store'), [
            'name' => 'Hacker Operative',
            'email' => 'hacker@test.com',
            'role' => 'Marshall',
        ]);
        $response->assertStatus(403);
    }

    public function test_marshall_cannot_access_admin_module(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('admin.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->marshall)->get(route('admin.users.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->marshall)->get(route('admin.marshalls.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->marshall)->get(route('admin.executors.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->marshall)->post(route('admin.users.store'), [
            'name' => 'Promoted Marshall',
            'email' => 'promoted@test.com',
            'role' => 'Marshall',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_index_and_view_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
        $response->assertSee('USER CONTROL MATRIX');
        $response->assertSee('Root Administrator');
        $response->assertSee('Field Marshall');
        $response->assertSee('Special Executor');
        $response->assertSee('PROVISION MARSHALL');
        $response->assertSee('PROVISION EXECUTOR');
    }

    public function test_admin_can_filter_users_by_role_and_search_by_keyword(): void
    {
        // Filter by Marshall
        $response = $this->actingAs($this->admin)->get(route('admin.index', ['role' => 'Marshall']));
        $response->assertStatus(200);
        $response->assertSee('Field Marshall');
        $response->assertDontSee('Special Executor');

        // Filter by Executor
        $response = $this->actingAs($this->admin)->get(route('admin.index', ['role' => 'Executor']));
        $response->assertStatus(200);
        $response->assertSee('Special Executor');
        $response->assertDontSee('Field Marshall');

        // Search by keyword
        $response = $this->actingAs($this->admin)->get(route('admin.index', ['search' => 'Special']));
        $response->assertStatus(200);
        $response->assertSee('Special Executor');
        $response->assertDontSee('Field Marshall');
    }

    public function test_admin_can_view_create_user_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.create-user');
        $response->assertSee('PROVISION SYSTEM OPERATIVE');
        $response->assertSee('CLEARANCE ROLE LEVEL');

        $response = $this->actingAs($this->admin)->get(route('admin.marshalls.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.create-user');

        $response = $this->actingAs($this->admin)->get(route('admin.executors.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.create-user');
    }

    public function test_admin_can_provision_marshall_user_with_custom_password(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'New Captain Marshall',
            'email' => 'captain@test.com',
            'role' => 'Marshall',
            'temporary_password' => 'TemporaryPass123!',
            'force_password_reset' => 0,
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('created_marshall_name', 'New Captain Marshall');
        $response->assertSessionHas('created_marshall_email', 'captain@test.com');
        $response->assertSessionHas('created_marshall_password', 'TemporaryPass123!');
        $response->assertSessionHas('created_marshall_must_reset', false);

        $newMarshall = User::where('email', 'captain@test.com')->first();
        $this->assertNotNull($newMarshall);
        $this->assertEquals('New Captain Marshall', $newMarshall->name);
        $this->assertTrue($newMarshall->hasRole('Marshall'));
        $this->assertFalse($newMarshall->must_reset_password);
        $this->assertTrue(Hash::check('TemporaryPass123!', $newMarshall->password));

        // Marshall authenticates directly via login screen
        auth()->logout();
        $loginResponse = $this->post('/login', [
            'email' => 'captain@test.com',
            'password' => 'TemporaryPass123!',
        ]);

        $loginResponse->assertRedirect('/home');
        $this->assertAuthenticatedAs($newMarshall);

        // Accessing main route does not redirect to password reset
        $indexResponse = $this->actingAs($newMarshall)->get(route('executors.index'));
        $indexResponse->assertStatus(200);
    }

    public function test_admin_can_provision_marshall_with_custom_password_and_enforce_reset(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Reset Enforced Marshall',
            'email' => 'reset.enforced@test.com',
            'role' => 'Marshall',
            'temporary_password' => 'EnforcedPass99!',
            'force_password_reset' => 1,
        ]);

        $response->assertRedirect(route('admin.index'));
        $newMarshall = User::where('email', 'reset.enforced@test.com')->first();
        $this->assertNotNull($newMarshall);
        $this->assertTrue($newMarshall->must_reset_password);

        // Marshall authenticates
        auth()->logout();
        $loginResponse = $this->post('/login', [
            'email' => 'reset.enforced@test.com',
            'password' => 'EnforcedPass99!',
        ]);
        $loginResponse->assertRedirect('/home');

        // Middleware redirects to password reset
        $indexResponse = $this->actingAs($newMarshall)->get(route('executors.index'));
        $indexResponse->assertRedirect(route('password.force_reset'));
    }

    public function test_admin_can_provision_marshall_user_with_auto_generated_password(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.marshalls.store'), [
            'name' => 'Auto Key Marshall',
            'email' => 'autokey@test.com',
            'temporary_password' => '',
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('created_marshall_password');

        $generatedPass = session('created_marshall_password');
        $this->assertNotEmpty($generatedPass);

        $newMarshall = User::where('email', 'autokey@test.com')->first();
        $this->assertNotNull($newMarshall);
        $this->assertTrue($newMarshall->hasRole('Marshall'));
        $this->assertTrue($newMarshall->must_reset_password);
        $this->assertTrue(Hash::check($generatedPass, $newMarshall->password));
    }

    public function test_admin_can_provision_executor_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.executors.store'), [
            'name' => 'Field Operative Alpha',
            'email' => 'alpha@test.com',
            'temporary_password' => 'AlphaKeySecret99!',
            'force_password_reset' => 0,
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('created_executor_name', 'Field Operative Alpha');
        $response->assertSessionHas('created_executor_email', 'alpha@test.com');
        $response->assertSessionHas('created_executor_password', 'AlphaKeySecret99!');

        $newExecutor = User::where('email', 'alpha@test.com')->first();
        $this->assertNotNull($newExecutor);
        $this->assertTrue($newExecutor->hasRole('Executor'));
        $this->assertFalse($newExecutor->must_reset_password);
        $this->assertTrue(Hash::check('AlphaKeySecret99!', $newExecutor->password));
    }

    public function test_provisioned_marshall_must_reset_password_on_first_login(): void
    {
        // Admin provisions Marshall with explicit forced reset
        $this->actingAs($this->admin)->post(route('admin.marshalls.store'), [
            'name' => 'Enforced Reset Marshall',
            'email' => 'enforced@test.com',
            'temporary_password' => 'TempSecret888',
            'force_password_reset' => 1,
        ]);

        $marshall = User::where('email', 'enforced@test.com')->first();
        $this->assertTrue($marshall->must_reset_password);

        // Marshall logs in
        $response = $this->actingAs($marshall)->get(route('executors.index'));

        // Middleware redirects to password reset
        $response->assertRedirect(route('password.force_reset'));
    }

    public function test_user_provisioning_validates_inputs(): void
    {
        // Missing name and email
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'role' => 'Marshall',
        ]);
        $response->assertSessionHasErrors(['name', 'email']);

        // Duplicate email
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Duplicate Email',
            'email' => 'marshall@test.com',
            'role' => 'Marshall',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Invalid role
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Invalid Role User',
            'email' => 'invalidrole@test.com',
            'role' => 'SuperAdmin',
        ]);
        $response->assertSessionHasErrors(['role']);

        // Password too short
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Short Pass User',
            'email' => 'shortpass@test.com',
            'role' => 'Marshall',
            'temporary_password' => 'short',
        ]);
        $response->assertSessionHasErrors(['temporary_password']);
    }

    public function test_admin_badge_rendered_in_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.index'));
        $response->assertSee('ADMIN');
        $response->assertSee('Admin Console');
    }

    public function test_admin_console_excludes_current_logged_in_admin_from_user_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.index'));
        $response->assertStatus(200);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
        $users = $response->viewData('users');
        $userIds = $users->pluck('id')->all();

        $this->assertNotContains($this->admin->id, $userIds);
        $this->assertContains($this->marshall->id, $userIds);
        $this->assertContains($this->executor->id, $userIds);
    }

    public function test_admin_can_view_edit_user_page_for_non_admin_operatives(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $this->marshall));
        $response->assertStatus(200);
        $response->assertViewIs('admin.edit-user');
        $response->assertSee($this->marshall->name);
        $response->assertSee($this->marshall->email);
    }

    public function test_admin_cannot_view_edit_page_for_any_admin(): void
    {
        // Cannot view edit page for another admin
        $otherAdmin = User::factory()->create([
            'email' => 'otheradmin@test.com',
            'must_reset_password' => false,
        ]);
        $otherAdmin->assignRole('Admin');

        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $otherAdmin));
        $response->assertStatus(403);

        // Cannot view edit page for themselves via admin user edit route
        $selfResponse = $this->actingAs($this->admin)->get(route('admin.users.edit', $this->admin));
        $selfResponse->assertStatus(403);
    }

    public function test_admin_cannot_update_any_admin(): void
    {
        // Cannot update another admin
        $otherAdmin = User::factory()->create([
            'email' => 'secureadmin@test.com',
            'must_reset_password' => false,
        ]);
        $otherAdmin->assignRole('Admin');

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $otherAdmin), [
            'name' => 'Attempted Overwrite',
            'email' => 'overwritten@test.com',
            'role' => 'Executor',
            'new_password' => 'HackedPassword123!',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('secureadmin@test.com', $otherAdmin->fresh()->email);

        // Cannot update themselves via admin users update route
        $selfUpdateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => 'Self Change',
            'email' => 'selfchange@test.com',
            'role' => 'Executor',
        ]);
        $selfUpdateResponse->assertStatus(403);
    }

    public function test_admin_can_update_operative_profile_and_role(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->marshall), [
            'name' => 'Promoted Commander Marshall',
            'email' => 'promoted.marshall@test.com',
            'role' => 'Executor', // Transition role to Executor
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');

        $updatedUser = $this->marshall->fresh();
        $this->assertEquals('Promoted Commander Marshall', $updatedUser->name);
        $this->assertEquals('promoted.marshall@test.com', $updatedUser->email);
        $this->assertTrue($updatedUser->hasRole('Executor'));
        $this->assertFalse($updatedUser->hasRole('Marshall'));
    }

    public function test_admin_can_reset_operative_password_with_direct_login(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->marshall), [
            'name' => $this->marshall->name,
            'email' => $this->marshall->email,
            'role' => 'Marshall',
            'new_password' => 'NewResetPass123!',
            'force_password_reset' => 0,
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('created_marshall_password', 'NewResetPass123!');
        $response->assertSessionHas('created_marshall_must_reset', false);

        $updatedMarshall = $this->marshall->fresh();
        $this->assertTrue(Hash::check('NewResetPass123!', $updatedMarshall->password));
        $this->assertFalse($updatedMarshall->must_reset_password);

        // Marshall logs in with new password directly
        auth()->logout();
        $loginResponse = $this->post('/login', [
            'email' => $this->marshall->email,
            'password' => 'NewResetPass123!',
        ]);
        $loginResponse->assertRedirect('/home');
        $this->assertAuthenticatedAs($updatedMarshall);
    }

    public function test_admin_can_reset_operative_password_with_enforced_reset(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->marshall), [
            'name' => $this->marshall->name,
            'email' => $this->marshall->email,
            'role' => 'Marshall',
            'new_password' => 'TempOverridePass99!',
            'force_password_reset' => 1,
        ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('created_marshall_must_reset', true);

        $updatedMarshall = $this->marshall->fresh();
        $this->assertTrue(Hash::check('TempOverridePass99!', $updatedMarshall->password));
        $this->assertTrue($updatedMarshall->must_reset_password);

        // Marshall authenticates then gets redirected to force-reset
        auth()->logout();
        $loginResponse = $this->post('/login', [
            'email' => $this->marshall->email,
            'password' => 'TempOverridePass99!',
        ]);
        $loginResponse->assertRedirect('/home');

        $navResponse = $this->actingAs($updatedMarshall)->get(route('executors.index'));
        $navResponse->assertRedirect(route('password.force_reset'));
    }

    public function test_non_admin_cannot_access_edit_or_update_user(): void
    {
        // Marshall cannot edit user
        $response = $this->actingAs($this->marshall)->get(route('admin.users.edit', $this->executor));
        $response->assertStatus(403);

        $response = $this->actingAs($this->marshall)->put(route('admin.users.update', $this->executor), [
            'name' => 'Hacker Name',
            'email' => 'hacked@test.com',
            'role' => 'Marshall',
        ]);
        $response->assertStatus(403);

        // Executor cannot edit user
        $response = $this->actingAs($this->executor)->get(route('admin.users.edit', $this->marshall));
        $response->assertStatus(403);
    }
}

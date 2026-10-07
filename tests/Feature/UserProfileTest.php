<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $marshall;

    protected function setUp(): void
    {
        parent::setUp();

        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);

        $this->user = User::factory()->create([
            'name' => 'Agent Smith',
            'email' => 'smith@plantrack.test',
            'password' => Hash::make('SecretPass123'),
        ]);
        $this->user->assignRole($executorRole);

        $this->marshall = User::factory()->create([
            'name' => 'Commander Marshall',
            'email' => 'commander@plantrack.test',
        ]);
        $this->marshall->assignRole($marshallRole);
    }

    public function test_guest_is_redirected_to_login_when_viewing_profile(): void
    {
        $response = $this->get(route('profile.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('OPERATIVE PROFILE');
        $response->assertSee('Agent Smith');
        $response->assertSee('smith@plantrack.test');
        $response->assertSee('EXECUTOR');
        $response->assertSee('OPERATIVE INFORMATION');
        $response->assertSee('SECURITY &amp; ACCESS KEY', false);
    }

    public function test_profile_page_displays_executor_mission_metrics(): void
    {
        PlanRecord::create([
            'title' => 'Alpha Assignment',
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('MISSION METRICS');
        $response->assertSee('MY PLANS DOSSIER');
    }

    public function test_profile_page_displays_marshall_command_clearance(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('COMMAND CLEARANCE');
        $response->assertSee('EXECUTORS DIRECTORY');
    }

    public function test_user_can_update_profile_name_and_email(): void
    {
        $response = $this->actingAs($this->user)->patch(route('profile.update'), [
            'name' => 'Agent Neo',
            'email' => 'neo.matrix@plantrack.test',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('Agent Neo', $this->user->name);
        $this->assertEquals('neo.matrix@plantrack.test', $this->user->email);
    }

    public function test_user_cannot_update_profile_to_duplicate_email(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'existing.operative@plantrack.test',
        ]);

        $response = $this->actingAs($this->user)->patch(route('profile.update'), [
            'name' => 'Agent Smith',
            'email' => $otherUser->email,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('smith@plantrack.test', $this->user->fresh()->email);
    }

    public function test_user_can_update_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
            'current_password' => 'SecretPass123',
            'password' => 'NewCyberKey999',
            'password_confirmation' => 'NewCyberKey999',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewCyberKey999', $this->user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
            'current_password' => 'WrongCurrentPassword',
            'password' => 'NewCyberKey999',
            'password_confirmation' => 'NewCyberKey999',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertTrue(Hash::check('SecretPass123', $this->user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_mismatched_confirmation(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
            'current_password' => 'SecretPass123',
            'password' => 'NewCyberKey999',
            'password_confirmation' => 'DifferentKey123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_cannot_update_password_shorter_than_eight_characters(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password.update'), [
            'current_password' => 'SecretPass123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_navigation_dropdown_contains_operative_profile_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('executors.index'));

        $response->assertStatus(200);
        $response->assertSee(route('profile.show'));
        $response->assertSee('Operative Profile');
    }

    public function test_profile_page_calculates_average_score_strictly_for_closed_plans(): void
    {
        // Plan 1: Closed with 100% score
        $plan1 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Close',
            'start_date' => now()->subDays(10),
            'end_date' => now(),
        ]);
        $plan1->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);
        $plan1->resources()->create(['description' => 'R1', 'quantity' => '1', 'target_date' => now(), 'status' => 'Hit']);
        $plan1->riskManagements()->create(['risk' => 'K1', 'impact' => '1', 'mitigation' => 'M', 'status' => 'Hit']);
        $plan1->budgets()->create(['description' => 'B1', 'quantity' => '100', 'status' => 'Hit']);

        // Plan 2: Closed with 60% score
        $plan2 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Close',
            'start_date' => now()->subDays(10),
            'end_date' => now(),
        ]);
        $plan2->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Hit']);
        $plan2->resources()->create(['description' => 'R2', 'quantity' => '1', 'target_date' => now(), 'status' => 'Missed']);
        $plan2->riskManagements()->create(['risk' => 'K2', 'impact' => '1', 'mitigation' => 'M', 'status' => 'Missed']);
        $plan2->budgets()->create(['description' => 'B2', 'quantity' => '100', 'status' => 'Missed']);

        // Plan 3: Open plan (ignored in average calculation!)
        $plan3 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);
        $plan3->targetObjectives()->create(['description' => 'T3', 'quantity' => '1', 'status' => 'Missed']);

        // Plan 4: Review plan (ignored in average calculation!)
        $plan4 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Review',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);
        $plan4->targetObjectives()->create(['description' => 'T4', 'quantity' => '1', 'status' => 'Missed']);

        // Expected average of closed plans (with default 60% Target, 20% Resource, 10% Risk, 10% Budget):
        // Plan 1: (100 * 0.60) + (100 * 0.20) + (100 * 0.10) + (100 * 0.10) = 100.0%
        // Plan 2: (100 * 0.60) + (0 * 0.20) + (0 * 0.10) + (0 * 0.10) = 60.0%
        // Average: (100.0 + 60.0) / 2 = 80.0%
        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('80.0%');
        $response->assertSee('AVG SCORE (CLOSED PLANS)');
        $response->assertSee('2 CLOSED PLANS');
        $response->assertSee('ASSIGNED PLAN RECORDS (4)');
        $response->assertSee($plan1->title);
        $response->assertSee($plan2->title);
        $response->assertSee($plan3->title);
        $response->assertSee($plan4->title);
    }

    public function test_profile_page_displays_zero_average_when_no_closed_plans(): void
    {
        PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('0.0%');
        $response->assertSee('0 CLOSED PLANS');
    }

    public function test_profile_page_displays_closed_plan_component_distribution(): void
    {
        // Closed Plan 1
        $plan1 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Close',
            'start_date' => now()->subDays(10),
            'end_date' => now(),
        ]);
        $plan1->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);
        $plan1->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Missed']);
        $plan1->resources()->create(['description' => 'R1', 'quantity' => '1', 'target_date' => now(), 'status' => 'Hit']);
        $plan1->riskManagements()->create(['risk' => 'K1', 'impact' => '1', 'mitigation' => 'M', 'status' => 'Void']);
        $plan1->budgets()->create(['description' => 'B1', 'quantity' => '100', 'status' => 'Hit']);

        // Open Plan 2 (must be ignored in closed plan component distribution!)
        $plan2 = PlanRecord::create([
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);
        $plan2->targetObjectives()->create(['description' => 'T3', 'quantity' => '1', 'status' => 'Missed']);

        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee('COMPONENT PERFORMANCE DISTRIBUTION');
        $response->assertSee('TARGET OBJECTIVES');
        $response->assertSee('RESOURCES');
        $response->assertSee('RISK MANAGEMENT');
        $response->assertSee('BUDGET ITEMS');

        // Targets: 1 Hit (50.0%), 1 Missed (50.0%), 0 Void (0.0%), 2 Total
        $response->assertSee('1 Hit (50.0%)');
        $response->assertSee('1 Missed (50.0%)');
        $response->assertSee('0 Void (0.0%)');
        $response->assertSee('2 Total');

        // Resources: 1 Hit (100.0%)
        $response->assertSee('1 Hit (100.0%)');

        // Risks: 1 Void (100.0%)
        $response->assertSee('1 Void (100.0%)');
    }

    public function test_user_can_view_another_users_profile_page(): void
    {
        $otherExecutor = User::factory()->create([
            'name' => 'Agent Trinity',
            'email' => 'trinity@plantrack.test',
        ]);
        $otherExecutor->assignRole('Executor');

        PlanRecord::create([
            'title' => 'Nebuchadnezzar Operations',
            'executor_id' => $otherExecutor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->user)->get(route('profile.show', $otherExecutor));

        $response->assertStatus(200);
        $response->assertSee('OPERATIVE PROFILE');
        $response->assertSee('Agent Trinity');
        $response->assertSee('trinity@plantrack.test');
        $response->assertSee('OPERATIVE DOSSIER SUMMARY');
        $response->assertSee('Nebuchadnezzar Operations');
        // Must not see password change form
        $response->assertDontSee('SECURITY &amp; ACCESS KEY', false);
        $response->assertDontSee('CURRENT PASSWORD');
    }

    public function test_comment_username_links_to_commenter_profile(): void
    {
        $commenter = User::factory()->create([
            'name' => 'Morpheus Operator',
            'email' => 'morpheus@plantrack.test',
        ]);
        $commenter->assignRole('Marshall');

        $plan = PlanRecord::create([
            'title' => 'Zion Defense Grid',
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target 1',
            'quantity' => '10',
            'priority' => 'high',
        ]);

        $target->comments()->create([
            'user_id' => $commenter->id,
            'body' => 'Stand firm at the gate.',
        ]);

        $response = $this->actingAs($this->user)->get(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee(route('profile.show', $commenter));
        $response->assertSee('Morpheus Operator');
    }

    public function test_plan_assigned_executor_username_links_to_executor_profile(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Zion Defense Grid',
            'executor_id' => $this->user->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee(route('profile.show', $this->user));
        $response->assertSee('Agent Smith');
    }

    public function test_executors_directory_links_to_executor_profile(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('executors.index'));

        $response->assertStatus(200);
        $response->assertSee(route('profile.show', $this->user));
        $response->assertSee('Agent Smith');
    }
}

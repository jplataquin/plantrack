<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlansPageTest extends TestCase
{
    use RefreshDatabase;

    private User $marshall;

    private User $executor1;

    private User $executor2;

    private Project $projectA;

    private Project $projectB;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create(['name' => 'Mary Marshall']);
        $this->marshall->assignRole($marshallRole);

        $this->executor1 = User::factory()->create(['name' => 'John Executor']);
        $this->executor1->assignRole($executorRole);

        $this->executor2 = User::factory()->create(['name' => 'Sarah Executor']);
        $this->executor2->assignRole($executorRole);

        $this->projectA = Project::create(['name' => 'Project Alpha', 'status' => 'Active']);
        $this->projectB = Project::create(['name' => 'Project Beta', 'status' => 'Active']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('plans.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dedicated_plans_page(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Core Infrastructure Setup',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectA->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->executor1)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('PLANS');
        $response->assertSee('REPOSITORY');
        $response->assertSee('PLAN RECORDS DIRECTORY');
        $response->assertSee('Core Infrastructure Setup');
        $response->assertSee('Project Alpha');
        $response->assertSee('John Executor');
    }

    public function test_plans_can_be_filtered_by_project(): void
    {
        $planA = PlanRecord::create([
            'title' => 'Alpha Plan Record',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectA->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $planB = PlanRecord::create([
            'title' => 'Beta Plan Record',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectB->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.index', [
            'project_id' => $this->projectA->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Alpha Plan Record');
        $response->assertDontSee('Beta Plan Record');
    }

    public function test_plans_can_be_filtered_by_executor(): void
    {
        $plan1 = PlanRecord::create([
            'title' => 'Plan for Executor One',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectA->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $plan2 = PlanRecord::create([
            'title' => 'Plan for Executor Two',
            'executor_id' => $this->executor2->id,
            'project_id' => $this->projectA->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.index', [
            'executor_id' => $this->executor2->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Plan for Executor Two');
        $response->assertDontSee('Plan for Executor One');
    }

    public function test_plans_can_be_filtered_by_status(): void
    {
        $planOpen = PlanRecord::create([
            'title' => 'Open Status Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $planClose = PlanRecord::create([
            'title' => 'Closed Status Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.index', [
            'status' => 'Close',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Closed Status Plan');
        $response->assertDontSee('Open Status Plan');
    }

    public function test_plans_can_be_filtered_by_combined_criteria(): void
    {
        $match = PlanRecord::create([
            'title' => 'Exact Matching Plan',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectA->id,
            'status' => 'Review',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $diffProject = PlanRecord::create([
            'title' => 'Wrong Project Plan',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectB->id,
            'status' => 'Review',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $diffExecutor = PlanRecord::create([
            'title' => 'Wrong Executor Plan',
            'executor_id' => $this->executor2->id,
            'project_id' => $this->projectA->id,
            'status' => 'Review',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $diffStatus = PlanRecord::create([
            'title' => 'Wrong Status Plan',
            'executor_id' => $this->executor1->id,
            'project_id' => $this->projectA->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.index', [
            'project_id' => $this->projectA->id,
            'executor_id' => $this->executor1->id,
            'status' => 'Review',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Exact Matching Plan');
        $response->assertDontSee('Wrong Project Plan');
        $response->assertDontSee('Wrong Executor Plan');
        $response->assertDontSee('Wrong Status Plan');
    }

    public function test_plans_can_be_searched_by_text(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Quantum Encryption Protocol',
            'description' => 'Upgrading legacy cipher suites',
            'executor_id' => $this->executor1->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $other = PlanRecord::create([
            'title' => 'Database Backup Sync',
            'executor_id' => $this->executor1->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.index', [
            'search' => 'Quantum',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Quantum Encryption Protocol');
        $response->assertDontSee('Database Backup Sync');
    }
}

<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $marshall;

    protected User $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create([
            'name' => 'Marshall Agent',
            'email' => 'marshall@test.com',
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create([
            'name' => 'Executor Operative',
            'email' => 'executor@test.com',
        ]);
        $this->executor->assignRole($executorRole);
    }

    public function test_marshall_can_view_projects_index(): void
    {
        Project::create(['name' => 'Project Alpha', 'status' => 'Active']);
        Project::create(['name' => 'Project Beta', 'status' => 'Deactive']);

        $response = $this->actingAs($this->marshall)->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertSee('Project Alpha');
        $response->assertSee('Project Beta');
        $response->assertSee('NEW PROJECT');
    }

    public function test_marshall_can_view_create_project_page(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('projects.create'));

        $response->assertStatus(200);
        $response->assertSee('PROVISION NEW PROJECT');
        $response->assertSee('INITIAL STATUS');
    }

    public function test_marshall_can_create_active_project(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('projects.store'), [
            'name' => 'Cyber Infrastructure Overhaul',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', [
            'name' => 'Cyber Infrastructure Overhaul',
            'status' => 'Active',
            'deleted_at' => null,
        ]);
    }

    public function test_marshall_can_create_deactive_project(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('projects.store'), [
            'name' => 'Decommissioned Fleet',
            'status' => 'Deactive',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', [
            'name' => 'Decommissioned Fleet',
            'status' => 'Deactive',
        ]);
    }

    public function test_project_name_must_be_unique_on_creation(): void
    {
        Project::create([
            'name' => 'Unique Initiative',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->marshall)->post(route('projects.store'), [
            'name' => 'Unique Initiative',
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(1, Project::where('name', 'Unique Initiative')->count());
    }

    public function test_project_status_must_be_valid(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('projects.store'), [
            'name' => 'Invalid Status Project',
            'status' => 'InvalidStatus',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_marshall_can_view_edit_project_page(): void
    {
        $project = Project::create([
            'name' => 'Editable Project',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->marshall)->get(route('projects.edit', $project));

        $response->assertStatus(200);
        $response->assertSee('EDIT PROJECT RECORD');
        $response->assertSee('Editable Project');
    }

    public function test_marshall_can_update_project(): void
    {
        $project = Project::create([
            'name' => 'Project Old Name',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->marshall)->put(route('projects.update', $project), [
            'name' => 'Project New Name',
            'status' => 'Deactive',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Project New Name',
            'status' => 'Deactive',
        ]);
    }

    public function test_project_name_must_be_unique_on_update_excluding_self(): void
    {
        $project1 = Project::create(['name' => 'Project One', 'status' => 'Active']);
        $project2 = Project::create(['name' => 'Project Two', 'status' => 'Active']);

        // Updating self with same name should pass
        $responseSame = $this->actingAs($this->marshall)->put(route('projects.update', $project1), [
            'name' => 'Project One',
            'status' => 'Active',
        ]);
        $responseSame->assertSessionHasNoErrors();

        // Updating with existing name of another project should fail
        $responseConflict = $this->actingAs($this->marshall)->put(route('projects.update', $project1), [
            'name' => 'Project Two',
            'status' => 'Active',
        ]);
        $responseConflict->assertSessionHasErrors(['name']);
    }

    public function test_marshall_can_soft_delete_project(): void
    {
        $project = Project::create([
            'name' => 'Project To Terminate',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->marshall)->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
            'name' => 'Project To Terminate',
        ]);
    }

    public function test_executor_cannot_access_create_project_page(): void
    {
        $response = $this->actingAs($this->executor)->get(route('projects.create'));

        $response->assertStatus(403);
    }

    public function test_executor_cannot_store_project(): void
    {
        $response = $this->actingAs($this->executor)->post(route('projects.store'), [
            'name' => 'Unauthorized Project',
            'status' => 'Active',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('projects', ['name' => 'Unauthorized Project']);
    }

    public function test_executor_cannot_access_edit_project_page(): void
    {
        $project = Project::create(['name' => 'Project Secret', 'status' => 'Active']);

        $response = $this->actingAs($this->executor)->get(route('projects.edit', $project));

        $response->assertStatus(403);
    }

    public function test_executor_cannot_update_project(): void
    {
        $project = Project::create(['name' => 'Immutable Project', 'status' => 'Active']);

        $response = $this->actingAs($this->executor)->put(route('projects.update', $project), [
            'name' => 'Hacked Project',
            'status' => 'Deactive',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', ['name' => 'Immutable Project']);
    }

    public function test_executor_cannot_soft_delete_project(): void
    {
        $project = Project::create(['name' => 'Protected Project', 'status' => 'Active']);

        $response = $this->actingAs($this->executor)->delete(route('projects.destroy', $project));

        $response->assertStatus(403);
        $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_guest_cannot_access_projects(): void
    {
        $response = $this->get(route('projects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_create_plan_form_contains_active_projects_dropdown_for_marshall(): void
    {
        $activeProject = Project::create(['name' => 'Operation Active', 'status' => 'Active']);
        $deactiveProject = Project::create(['name' => 'Operation Inactive', 'status' => 'Deactive']);

        $response = $this->actingAs($this->marshall)->get(route('plans.create'));

        $response->assertStatus(200);
        $response->assertSee('PROJECT (OPTIONAL)');
        $response->assertSee('Operation Active');
        $response->assertDontSee('Operation Inactive');
    }

    public function test_create_plan_form_does_not_list_soft_deleted_projects(): void
    {
        $deletedProject = Project::create(['name' => 'Deleted Initiative', 'status' => 'Active']);
        $deletedProject->delete();

        $response = $this->actingAs($this->marshall)->get(route('plans.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Deleted Initiative');
    }

    public function test_marshall_can_create_plan_with_assigned_active_project(): void
    {
        $project = Project::create(['name' => 'Titan Protocol', 'status' => 'Active']);

        $response = $this->actingAs($this->marshall)->post(route('plans.store'), [
            'title' => 'Titan Deployment Plan',
            'description' => 'Deploying Titan infrastructure.',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plan_records', [
            'title' => 'Titan Deployment Plan',
            'project_id' => $project->id,
            'executor_id' => $this->executor->id,
        ]);
    }

    public function test_marshall_can_create_plan_without_project(): void
    {
        $response = $this->actingAs($this->marshall)->post(route('plans.store'), [
            'title' => 'Standalone Plan',
            'description' => 'No project linked.',
            'executor_id' => $this->executor->id,
            'project_id' => '',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plan_records', [
            'title' => 'Standalone Plan',
            'project_id' => null,
        ]);
    }

    public function test_plan_cannot_be_assigned_to_deactive_project(): void
    {
        $deactiveProject = Project::create(['name' => 'Suspended Project', 'status' => 'Deactive']);

        $response = $this->actingAs($this->marshall)->post(route('plans.store'), [
            'title' => 'Illegal Assignment Plan',
            'executor_id' => $this->executor->id,
            'project_id' => $deactiveProject->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertSessionHasErrors(['project_id']);
    }

    public function test_plan_cannot_be_assigned_to_soft_deleted_project(): void
    {
        $project = Project::create(['name' => 'Soft Deleted Project', 'status' => 'Active']);
        $project->delete();

        $response = $this->actingAs($this->marshall)->post(route('plans.store'), [
            'title' => 'Plan For Deleted Project',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertSessionHasErrors(['project_id']);
    }

    public function test_executor_cannot_assign_project_on_plan_creation(): void
    {
        $project = Project::create(['name' => 'Marshall Only Project', 'status' => 'Active']);

        $response = $this->actingAs($this->executor)->post(route('plans.store'), [
            'title' => 'Executor Plan',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        // Project ID should be null because executors are not authorized to assign projects
        $this->assertDatabaseHas('plan_records', [
            'title' => 'Executor Plan',
            'project_id' => null,
        ]);
    }

    public function test_marshall_can_update_plan_project_assignment(): void
    {
        $projectA = Project::create(['name' => 'Project Alpha Prime', 'status' => 'Active']);
        $projectB = Project::create(['name' => 'Project Omega Prime', 'status' => 'Active']);

        $plan = PlanRecord::create([
            'title' => 'Switchable Plan',
            'executor_id' => $this->executor->id,
            'project_id' => $projectA->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->put(route('plans.update', $plan), [
            'title' => 'Switchable Plan',
            'executor_id' => $this->executor->id,
            'project_id' => $projectB->id,
            'status' => 'Open',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertEquals($projectB->id, $plan->fresh()->project_id);
    }

    public function test_executor_cannot_change_plan_project_assignment(): void
    {
        $project = Project::create(['name' => 'Original Project', 'status' => 'Active']);
        $otherProject = Project::create(['name' => 'Attempted Project', 'status' => 'Active']);

        $plan = PlanRecord::create([
            'title' => 'Plan With Immutable Project',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->executor)->put(route('plans.update', $plan), [
            'title' => 'Plan With Updated Title',
            'executor_id' => $this->executor->id,
            'project_id' => $otherProject->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        // Project ID remains the original project
        $this->assertEquals($project->id, $plan->fresh()->project_id);
    }

    public function test_plan_show_page_displays_assigned_project(): void
    {
        $project = Project::create(['name' => 'Showcase Project', 'status' => 'Active']);

        $plan = PlanRecord::create([
            'title' => 'Showcase Plan',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee('Showcase Project');
        $response->assertDontSee('ASSIGNED PROJECT');
        $response->assertDontSee('title="Change Project"', false);
    }

    public function test_project_show_page_lists_assigned_plans(): void
    {
        $project = Project::create(['name' => 'Project With Plans', 'status' => 'Active']);

        $plan = PlanRecord::create([
            'title' => 'Assigned Plan Item',
            'executor_id' => $this->executor->id,
            'project_id' => $project->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertSee('Assigned Plan Item');
    }
}

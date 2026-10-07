<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\Resource;
use App\Models\RiskManagement;
use App\Models\TargetObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMarshallPrivilegesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $marshall;
    protected User $executor;
    protected Project $project;
    protected PlanRecord $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@test.com',
            'must_reset_password' => false,
        ]);
        $this->admin->assignRole($adminRole);

        $this->marshall = User::factory()->create([
            'name' => 'System Marshall',
            'email' => 'marshall@test.com',
            'must_reset_password' => false,
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create([
            'name' => 'Field Operative',
            'email' => 'executor@test.com',
            'must_reset_password' => false,
        ]);
        $this->executor->assignRole($executorRole);

        $this->project = Project::create([
            'name' => 'Nexus Initiative',
            'status' => 'Active',
            'description' => 'Top level project',
        ]);

        $this->plan = PlanRecord::create([
            'title' => 'Alpha Directive',
            'description' => 'Directive plan description',
            'executor_id' => $this->executor->id,
            'project_id' => $this->project->id,
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(30)->endOfDay(),
            'status' => 'Open',
            'target_weight' => 50.00,
            'resource_weight' => 20.00,
            'risk_weight' => 15.00,
            'budget_weight' => 15.00,
        ]);
    }

    public function test_admin_can_create_plan_record_with_project_and_custom_weights(): void
    {
        $response = $this->actingAs($this->admin)->post(route('plans.store'), [
            'title' => 'Admin Initiated Mission',
            'description' => 'Mission details',
            'executor_id' => $this->executor->id,
            'project_id' => $this->project->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'target_weight' => 40.00,
            'resource_weight' => 30.00,
            'risk_weight' => 15.00,
            'budget_weight' => 15.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plan_records', [
            'title' => 'Admin Initiated Mission',
            'executor_id' => $this->executor->id,
            'project_id' => $this->project->id,
            'target_weight' => 40.00,
        ]);
    }

    public function test_admin_can_update_plan_status_and_weights(): void
    {
        // Update weights
        $response = $this->actingAs($this->admin)->patchJson(route('plans.weights.update', $this->plan), [
            'target_weight' => 30.00,
            'resource_weight' => 30.00,
            'risk_weight' => 20.00,
            'budget_weight' => 20.00,
        ]);
        $response->assertStatus(200);
        $this->assertEquals(30.00, $this->plan->fresh()->target_weight);

        // Update status to Review
        $response = $this->actingAs($this->admin)->patchJson(route('plans.status.update', $this->plan), [
            'status' => 'Review',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Review', $this->plan->fresh()->status);
    }

    public function test_admin_has_full_crud_on_target_objectives(): void
    {
        // Create Target Objective
        $response = $this->actingAs($this->admin)->post(route('targets.store', $this->plan), [
            'description' => 'Secure Neural Gateway',
            'quantity' => '100',
            'unit' => 'nodes',
            'priority' => 'critical',
        ]);
        $response->assertRedirect();

        $target = TargetObjective::where('description', 'Secure Neural Gateway')->first();
        $this->assertNotNull($target);
        $this->assertEquals('critical', $target->priority);

        // Update Target Objective
        $response = $this->actingAs($this->admin)->put(route('targets.update', $target), [
            'description' => 'Secure Neural Gateway V2',
            'quantity' => '120',
            'unit' => 'nodes',
            'priority' => 'high',
        ]);
        $response->assertRedirect();
        $this->assertEquals('Secure Neural Gateway V2', $target->fresh()->description);

        // Update Priority via AJAX
        $response = $this->actingAs($this->admin)->patchJson(route('targets.priority.update', $target), [
            'priority' => 'normal',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('normal', $target->fresh()->priority);

        // Update Status
        $response = $this->actingAs($this->admin)->patchJson(route('targets.status.update', $target), [
            'status' => 'Hit',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Hit', $target->fresh()->status);

        // Update Actual
        $response = $this->actingAs($this->admin)->patchJson(route('targets.actual.update', $target), [
            'actual' => '120',
            'unit' => 'nodes',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('120', $target->fresh()->actual);

        // Delete Target
        $response = $this->actingAs($this->admin)->delete(route('targets.destroy', $target));
        $response->assertRedirect();
        $this->assertDatabaseMissing('target_objectives', ['id' => $target->id]);
    }

    public function test_admin_has_full_crud_on_resources_and_executor_cannot_modify_admin_made(): void
    {
        // Admin creates resource
        $response = $this->actingAs($this->admin)->post(route('resources.store', $this->plan), [
            'description' => 'Quantum Core Processing Server',
            'quantity' => '2',
            'unit' => 'units',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);
        $response->assertRedirect();

        $resource = Resource::where('description', 'Quantum Core Processing Server')->first();
        $this->assertNotNull($resource);
        $this->assertTrue($resource->isMarshallMade());

        // Admin updates status
        $response = $this->actingAs($this->admin)->patchJson(route('resources.status.update', $resource), [
            'status' => 'Hit',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Hit', $resource->fresh()->status);

        // Executor CANNOT edit or delete Admin-made resource
        $response = $this->actingAs($this->executor)->put(route('resources.update', $resource), [
            'description' => 'Hacked Core',
            'quantity' => '1',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->delete(route('resources.destroy', $resource));
        $response->assertStatus(403);

        // Admin deletes resource
        $response = $this->actingAs($this->admin)->delete(route('resources.destroy', $resource));
        $response->assertRedirect();
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }

    public function test_admin_has_full_crud_on_risk_management_and_executor_cannot_modify_admin_made(): void
    {
        // Admin creates risk
        $response = $this->actingAs($this->admin)->post(route('risks.store', $this->plan), [
            'risk' => 'High latency link',
            'impact' => 'Lv 3',
            'mitigation' => 'Deploy secondary fiber link',
        ]);
        $response->assertRedirect();

        $risk = RiskManagement::where('risk', 'High latency link')->first();
        $this->assertNotNull($risk);
        $this->assertTrue($risk->isMarshallMade());

        // Admin updates status
        $response = $this->actingAs($this->admin)->patchJson(route('risks.status.update', $risk), [
            'status' => 'Hit',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Hit', $risk->fresh()->status);

        // Executor cannot edit or delete Admin-made risk
        $response = $this->actingAs($this->executor)->put(route('risks.update', $risk), [
            'risk' => 'Executor modification attempt',
            'impact' => 'Lv 1',
            'mitigation' => 'None',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->executor)->delete(route('risks.destroy', $risk));
        $response->assertStatus(403);

        // Admin can delete risk
        $response = $this->actingAs($this->admin)->delete(route('risks.destroy', $risk));
        $response->assertRedirect();
        $this->assertDatabaseMissing('risk_management', ['id' => $risk->id]);
    }

    public function test_admin_has_full_crud_on_budgets_and_executor_cannot_access(): void
    {
        // Admin creates budget
        $response = $this->actingAs($this->admin)->post(route('budgets.store', $this->plan), [
            'description' => 'Security Audit License',
            'quantity' => 15000,
            'unit' => 'USD',
        ]);
        $response->assertRedirect();

        $budget = Budget::where('description', 'Security Audit License')->first();
        $this->assertNotNull($budget);
        $this->assertTrue($budget->isMarshallMade());

        // Admin updates budget
        $response = $this->actingAs($this->admin)->put(route('budgets.update', $budget), [
            'description' => 'Security Audit License Extended',
            'quantity' => 18000,
            'unit' => 'USD',
        ]);
        $response->assertRedirect();
        $this->assertEquals('Security Audit License Extended', $budget->fresh()->description);

        // Admin updates actual
        $response = $this->actingAs($this->admin)->patchJson(route('budgets.actual.update', $budget), [
            'actual' => '17500',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('17500', $budget->fresh()->actual);

        // Admin updates status
        $response = $this->actingAs($this->admin)->patchJson(route('budgets.status.update', $budget), [
            'status' => 'Hit',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Hit', $budget->fresh()->status);

        // Executor cannot perform budget operations
        $response = $this->actingAs($this->executor)->delete(route('budgets.destroy', $budget));
        $response->assertStatus(403);

        // Admin deletes budget
        $response = $this->actingAs($this->admin)->delete(route('budgets.destroy', $budget));
        $response->assertRedirect();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_admin_can_comment_on_closed_plan_and_delete_any_comment(): void
    {
        $target = TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'description' => 'Test Target',
            'quantity' => '10',
            'user_id' => $this->admin->id,
        ]);

        // Close the plan directly
        $this->plan->update(['status' => 'Close']);

        // Executor cannot comment on closed plan
        $response = $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $target->id,
            'body' => 'Executor remark',
        ]);
        $response->assertStatus(403);

        // Admin can comment on closed plan
        $response = $this->actingAs($this->admin)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $target->id,
            'body' => 'Admin administrative review note',
        ]);
        $response->assertRedirect();

        $comment = $target->comments()->first();
        $this->assertNotNull($comment);

        // Admin can delete comment
        $response = $this->actingAs($this->admin)->delete(route('comments.destroy', $comment));
        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_manage_projects(): void
    {
        // Admin creates project
        $response = $this->actingAs($this->admin)->post(route('projects.store'), [
            'name' => 'Project Hyperion',
            'status' => 'Active',
            'description' => 'Hyperion defense matrix',
        ]);
        $response->assertRedirect(route('projects.index'));

        $project = Project::where('name', 'Project Hyperion')->first();
        $this->assertNotNull($project);

        // Admin updates project
        $response = $this->actingAs($this->admin)->put(route('projects.update', $project), [
            'name' => 'Project Hyperion Deactivated',
            'status' => 'Deactive',
            'description' => 'Deactivated',
        ]);
        $response->assertRedirect(route('projects.index'));
        $this->assertEquals('Deactive', $project->fresh()->status);

        // Admin deletes project
        $response = $this->actingAs($this->admin)->delete(route('projects.destroy', $project));
        $response->assertRedirect(route('projects.index'));
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_admin_can_use_executor_controller_to_create_executor(): void
    {
        $response = $this->actingAs($this->admin)->get(route('executors.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('executors.store'), [
            'name' => 'Legacy Controller Executor',
            'email' => 'legacy@test.com',
            'temporary_password' => 'Temporary99!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'legacy@test.com',
            'name' => 'Legacy Controller Executor',
            'must_reset_password' => true,
        ]);
    }

    public function test_admin_can_delete_other_user_avatar(): void
    {
        $this->executor->update(['profile_picture' => 'avatars/test.jpg']);

        $response = $this->actingAs($this->admin)->deleteJson(route('profile.avatar.destroy'), [
            'user_id' => $this->executor->id,
        ]);

        $response->assertStatus(200);
        $this->assertNull($this->executor->fresh()->profile_picture);
    }
}

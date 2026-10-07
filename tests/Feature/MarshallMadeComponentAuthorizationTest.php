<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\Resource;
use App\Models\RiskManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarshallMadeComponentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $executor;

    protected User $otherExecutor;

    protected User $marshall;

    protected PlanRecord $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);

        $this->executor = User::factory()->create([
            'name' => 'Assigned Executor',
            'email' => 'assigned@test.com',
        ]);
        $this->executor->assignRole($executorRole);

        $this->otherExecutor = User::factory()->create([
            'name' => 'Other Executor',
            'email' => 'other@test.com',
        ]);
        $this->otherExecutor->assignRole($executorRole);

        $this->marshall = User::factory()->create([
            'name' => 'Commander Marshall',
            'email' => 'marshall@test.com',
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->plan = PlanRecord::create([
            'title' => 'Alpha Defense Protocol',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(30)->startOfDay(),
        ]);
    }

    public function test_executor_cannot_edit_marshall_made_resource(): void
    {
        // Marshall creates a resource
        $resource = $this->plan->resources()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Classified Marshall Workstations',
            'quantity' => '10',
            'unit' => 'nodes',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertTrue($resource->isMarshallMade());

        // Executor attempts to edit Marshall-made resource
        $response = $this->actingAs($this->executor)->put(route('resources.update', $resource), [
            'description' => 'Tampered Resource',
            'quantity' => '99',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Classified Marshall Workstations', $resource->fresh()->description);
    }

    public function test_executor_cannot_delete_marshall_made_resource(): void
    {
        $resource = $this->plan->resources()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Marshall Server Rack',
            'quantity' => '4',
            'unit' => 'racks',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        // Executor attempts to delete Marshall-made resource
        $response = $this->actingAs($this->executor)->delete(route('resources.destroy', $resource));

        $response->assertStatus(403);
        $this->assertDatabaseHas('resources', ['id' => $resource->id]);
    }

    public function test_executor_cannot_update_status_of_marshall_made_resource(): void
    {
        $resource = $this->plan->resources()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Marshall Hardware',
            'quantity' => '2',
            'target_date' => now()->addDays(5)->toDateString(),
            'status' => null,
        ]);

        $response = $this->actingAs($this->executor)->patch(route('resources.status.update', $resource), [
            'status' => 'Hit',
        ]);

        $response->assertStatus(403);
        $this->assertNull($resource->fresh()->status);
    }

    public function test_executor_cannot_edit_or_delete_marshall_made_risk(): void
    {
        $risk = $this->plan->riskManagements()->create([
            'user_id' => $this->marshall->id,
            'risk' => 'Core Firewall Vulnerability',
            'impact' => 'Lv 3 - All target objectives are affected',
            'mitigation' => 'Deploy kernel-level patches immediately',
        ]);

        $this->assertTrue($risk->isMarshallMade());

        // Executor attempts to edit
        $editResponse = $this->actingAs($this->executor)->put(route('risks.update', $risk), [
            'risk' => 'Tampered Risk',
            'impact' => 'Lv 1 - Only one target object is affected',
            'mitigation' => 'Ignore risk',
        ]);
        $editResponse->assertStatus(403);
        $this->assertEquals('Core Firewall Vulnerability', $risk->fresh()->risk);

        // Executor attempts to evaluate status
        $statusResponse = $this->actingAs($this->executor)->patch(route('risks.status.update', $risk), [
            'status' => 'Hit',
        ]);
        $statusResponse->assertStatus(403);
        $this->assertNull($risk->fresh()->status);

        // Executor attempts to delete
        $deleteResponse = $this->actingAs($this->executor)->delete(route('risks.destroy', $risk));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('risk_management', ['id' => $risk->id]);
    }

    public function test_executor_cannot_edit_or_delete_target_objective(): void
    {
        $target = $this->plan->targetObjectives()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Critical Objective',
            'quantity' => '100',
            'priority' => 'critical',
        ]);

        $this->assertTrue($target->isMarshallMade());

        $editResponse = $this->actingAs($this->executor)->put(route('targets.update', $target), [
            'description' => 'Hacked Objective',
            'quantity' => '1',
        ]);
        $editResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->executor)->delete(route('targets.destroy', $target));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('target_objectives', ['id' => $target->id]);
    }

    public function test_executor_can_edit_and_delete_own_created_resource(): void
    {
        // Executor creates a resource via endpoint
        $storeResponse = $this->actingAs($this->executor)->post(route('resources.store', $this->plan), [
            'description' => 'Operative Field Radios',
            'quantity' => '8',
            'unit' => 'sets',
            'target_date' => now()->addDays(10)->toDateString(),
        ]);
        $storeResponse->assertRedirect(route('plans.show', $this->plan));

        $resource = Resource::where('description', 'Operative Field Radios')->first();
        $this->assertNotNull($resource);
        $this->assertEquals($this->executor->id, $resource->user_id);
        $this->assertFalse($resource->isMarshallMade());

        // Executor can edit their own resource
        $editResponse = $this->actingAs($this->executor)->put(route('resources.update', $resource), [
            'description' => 'Updated Field Radios and Antennas',
            'quantity' => '12',
            'unit' => 'sets',
            'target_date' => now()->addDays(12)->toDateString(),
        ]);
        $editResponse->assertRedirect(route('plans.show', $this->plan));
        $this->assertEquals('Updated Field Radios and Antennas', $resource->fresh()->description);
        $this->assertEquals('12', $resource->fresh()->quantity);

        // Executor can update status of their own resource
        $statusResponse = $this->actingAs($this->executor)->patch(route('resources.status.update', $resource), [
            'status' => 'Hit',
        ]);
        $statusResponse->assertRedirect(route('plans.show', $this->plan));
        $this->assertEquals('Hit', $resource->fresh()->status);

        // Executor can delete their own resource
        $deleteResponse = $this->actingAs($this->executor)->delete(route('resources.destroy', $resource));
        $deleteResponse->assertRedirect(route('plans.show', $this->plan));
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }

    public function test_executor_can_edit_and_delete_own_created_risk(): void
    {
        // Executor creates a risk via endpoint
        $storeResponse = $this->actingAs($this->executor)->post(route('risks.store', $this->plan), [
            'risk' => 'Local Network Jitter',
            'impact' => 'Lv 1 - Only one target object is affected',
            'mitigation' => 'Switch to redundant mesh node',
        ]);
        $storeResponse->assertRedirect(route('plans.show', $this->plan));

        $risk = RiskManagement::where('risk', 'Local Network Jitter')->first();
        $this->assertNotNull($risk);
        $this->assertEquals($this->executor->id, $risk->user_id);
        $this->assertFalse($risk->isMarshallMade());

        // Executor can edit their own risk
        $editResponse = $this->actingAs($this->executor)->put(route('risks.update', $risk), [
            'risk' => 'Updated Jitter and Packet Loss',
            'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
            'mitigation' => 'Switch to fiber optic backup link',
        ]);
        $editResponse->assertRedirect(route('plans.show', $this->plan));
        $this->assertEquals('Updated Jitter and Packet Loss', $risk->fresh()->risk);

        // Executor can delete their own risk
        $deleteResponse = $this->actingAs($this->executor)->delete(route('risks.destroy', $risk));
        $deleteResponse->assertRedirect(route('plans.show', $this->plan));
        $this->assertDatabaseMissing('risk_management', ['id' => $risk->id]);
    }

    public function test_other_executor_cannot_edit_or_delete_executor_component(): void
    {
        $resource = $this->plan->resources()->create([
            'user_id' => $this->executor->id,
            'description' => 'Assigned Executor Resource',
            'quantity' => '5',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $risk = $this->plan->riskManagements()->create([
            'user_id' => $this->executor->id,
            'risk' => 'Assigned Executor Risk',
            'impact' => 'Lv 1 - Only one target object is affected',
            'mitigation' => 'Mitigate locally',
        ]);

        // Other executor attempts edit/delete
        $this->actingAs($this->otherExecutor)
            ->put(route('resources.update', $resource), [
                'description' => 'Unauthorized edit',
                'quantity' => '1',
                'target_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->otherExecutor)
            ->delete(route('resources.destroy', $resource))
            ->assertStatus(403);

        $this->actingAs($this->otherExecutor)
            ->put(route('risks.update', $risk), [
                'risk' => 'Unauthorized risk edit',
                'impact' => 'Lv 1',
                'mitigation' => 'None',
            ])
            ->assertStatus(403);

        $this->actingAs($this->otherExecutor)
            ->delete(route('risks.destroy', $risk))
            ->assertStatus(403);
    }

    public function test_marshall_can_edit_and_delete_any_component(): void
    {
        // Executor created resource and risk
        $res = $this->plan->resources()->create([
            'user_id' => $this->executor->id,
            'description' => 'Executor Resource',
            'quantity' => '5',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $risk = $this->plan->riskManagements()->create([
            'user_id' => $this->executor->id,
            'risk' => 'Executor Risk',
            'impact' => 'Lv 1 - Only one target object is affected',
            'mitigation' => 'Local mitigation',
        ]);

        // Marshall can edit executor-made resource
        $this->actingAs($this->marshall)
            ->put(route('resources.update', $res), [
                'description' => 'Marshall Overridden Resource',
                'quantity' => '25',
                'target_date' => now()->addDays(15)->toDateString(),
            ])
            ->assertRedirect(route('plans.show', $this->plan));
        $this->assertEquals('Marshall Overridden Resource', $res->fresh()->description);

        // Marshall can edit executor-made risk
        $this->actingAs($this->marshall)
            ->put(route('risks.update', $risk), [
                'risk' => 'Marshall Overridden Risk',
                'impact' => 'Lv 3 - All target objectives are affected',
                'mitigation' => 'Enterprise failover protocol',
            ])
            ->assertRedirect(route('plans.show', $this->plan));
        $this->assertEquals('Marshall Overridden Risk', $risk->fresh()->risk);

        // Marshall can delete both
        $this->actingAs($this->marshall)
            ->delete(route('resources.destroy', $res))
            ->assertRedirect(route('plans.show', $this->plan));
        $this->assertDatabaseMissing('resources', ['id' => $res->id]);

        $this->actingAs($this->marshall)
            ->delete(route('risks.destroy', $risk))
            ->assertRedirect(route('plans.show', $this->plan));
        $this->assertDatabaseMissing('risk_management', ['id' => $risk->id]);
    }

    public function test_ui_does_not_render_edit_or_delete_actions_to_executor_for_marshall_made_components(): void
    {
        $target = $this->plan->targetObjectives()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Marshall Secure Target',
            'quantity' => '100',
        ]);

        $resource = $this->plan->resources()->create([
            'user_id' => $this->marshall->id,
            'description' => 'Marshall Secure Resource',
            'quantity' => '10',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $risk = $this->plan->riskManagements()->create([
            'user_id' => $this->marshall->id,
            'risk' => 'Marshall Secure Risk',
            'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
            'mitigation' => 'Marshall mitigation plan',
        ]);

        // View as Executor
        $executorView = $this->actingAs($this->executor)->get(route('plans.show', $this->plan));
        $executorView->assertStatus(200);

        // Target: No edit modal trigger, no delete form
        $executorView->assertDontSee('data-bs-target="#editTargetModal-'.$target->id.'"', false);
        $executorView->assertDontSee('action="'.route('targets.destroy', $target).'"', false);

        // Resource: No edit modal trigger, no delete form, no status select
        $executorView->assertDontSee('data-bs-target="#editResourceModal-'.$resource->id.'"', false);
        $executorView->assertDontSee('action="'.route('resources.destroy', $resource).'"', false);
        $executorView->assertDontSee('data-url="'.route('resources.status.update', $resource).'"', false);

        // Risk: No edit modal trigger, no delete form, no status select
        $executorView->assertDontSee('data-bs-target="#editRiskModal-'.$risk->id.'"', false);
        $executorView->assertDontSee('action="'.route('risks.destroy', $risk).'"', false);
        $executorView->assertDontSee('data-url="'.route('risks.status.update', $risk).'"', false);

        // View as Marshall: All actions ARE visible
        $marshallView = $this->actingAs($this->marshall)->get(route('plans.show', $this->plan));
        $marshallView->assertStatus(200);

        $marshallView->assertSee('data-bs-target="#editTargetModal-'.$target->id.'"', false);
        $marshallView->assertSee('action="'.route('targets.destroy', $target).'"', false);

        $marshallView->assertSee('data-bs-target="#editResourceModal-'.$resource->id.'"', false);
        $marshallView->assertSee('action="'.route('resources.destroy', $resource).'"', false);
        $marshallView->assertSee('data-url="'.route('resources.status.update', $resource).'"', false);

        $marshallView->assertSee('data-bs-target="#editRiskModal-'.$risk->id.'"', false);
        $marshallView->assertSee('action="'.route('risks.destroy', $risk).'"', false);
        $marshallView->assertSee('data-url="'.route('risks.status.update', $risk).'"', false);
    }
}

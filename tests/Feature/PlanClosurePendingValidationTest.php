<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanClosurePendingValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $marshall;

    private User $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create();
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create();
        $this->executor->assignRole($executorRole);
    }

    public function test_marshall_cannot_close_plan_via_ajax_when_target_objective_is_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Target pending evaluation',
            'quantity' => '5',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'current_status' => 'Open',
        ]);
        $this->assertStringContainsString('evaluated (Pending)', $response->json('message'));
        $this->assertStringContainsString('Target Objective', $response->json('message'));

        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_marshall_cannot_close_plan_via_ajax_when_resource_is_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Evaluated Target',
            'quantity' => '1',
            'status' => 'Hit',
        ]);

        $plan->resources()->create([
            'description' => 'Resource pending evaluation',
            'quantity' => '1',
            'target_date' => now(),
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Resource', $response->json('message'));

        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_marshall_cannot_close_plan_via_ajax_when_risk_is_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->riskManagements()->create([
            'risk' => 'Security Threat',
            'impact' => 'High',
            'mitigation' => 'Firewall',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Risk Item', $response->json('message'));

        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_marshall_cannot_close_plan_via_ajax_when_budget_is_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->budgets()->create([
            'description' => 'Cloud Hosting Costs',
            'quantity' => '5000',
            'unit' => 'USD',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Budget Item', $response->json('message'));

        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_marshall_cannot_close_plan_via_edit_form_when_components_are_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Pending Target',
            'quantity' => '1',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)->put(route('plans.update', $plan), [
            'executor_id' => $this->executor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'Close',
        ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_marshall_cannot_close_plan_via_standard_redirect_when_components_are_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Review',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->resources()->create([
            'description' => 'Pending Specialist',
            'quantity' => '1',
            'target_date' => now(),
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)
            ->patch(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['status']);
        $response->assertSessionHas('error');

        $this->assertEquals('Review', $plan->fresh()->status);
    }

    public function test_marshall_can_close_plan_when_all_components_are_evaluated(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Review',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Hit Target',
            'quantity' => '1',
            'status' => 'Hit',
        ]);

        $plan->resources()->create([
            'description' => 'Missed Resource',
            'quantity' => '1',
            'target_date' => now(),
            'status' => 'Missed',
        ]);

        $plan->riskManagements()->create([
            'risk' => 'Void Risk',
            'impact' => 'Low',
            'mitigation' => 'Mitigated',
            'status' => 'Void',
        ]);

        $plan->budgets()->create([
            'description' => 'Hit Budget',
            'quantity' => '100',
            'unit' => 'USD',
            'status' => 'Hit',
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Close',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'Close',
        ]);

        $this->assertEquals('Close', $plan->fresh()->status);
    }

    public function test_non_close_status_transitions_are_not_blocked_by_pending_components(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Pending Target',
            'quantity' => '1',
            'status' => null,
        ]);

        // Transitioning to Review is allowed
        $reviewResponse = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Review',
            ]);
        $reviewResponse->assertStatus(200);
        $this->assertEquals('Review', $plan->fresh()->status);

        // Transitioning to Void is allowed
        $voidResponse = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Void',
            ]);
        $voidResponse->assertStatus(200);
        $this->assertEquals('Void', $plan->fresh()->status);

        // Transitioning back to Open is allowed
        $openResponse = $this->actingAs($this->marshall)
            ->patchJson(route('plans.status.update', $plan), [
                'status' => 'Open',
            ]);
        $openResponse->assertStatus(200);
        $this->assertEquals('Open', $plan->fresh()->status);
    }
}

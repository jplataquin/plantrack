<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanWeightsTest extends TestCase
{
    use RefreshDatabase;

    protected User $marshall;

    protected User $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create(['name' => 'Chief Marshall']);
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create(['name' => 'Lead Executor']);
        $this->executor->assignRole($executorRole);
    }

    public function test_new_plan_has_default_weights_of_60_20_10_10(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Default Weights Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        $this->assertEquals(60.0, $plan->target_weight);
        $this->assertEquals(20.0, $plan->resource_weight);
        $this->assertEquals(10.0, $plan->risk_weight);
        $this->assertEquals(10.0, $plan->budget_weight);
    }

    public function test_marshall_can_modify_weights_via_patch_weights_endpoint(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Custom Weights Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.weights.update', $plan), [
                'target_weight' => 50,
                'resource_weight' => 20,
                'risk_weight' => 15,
                'budget_weight' => 15,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'target_weight' => 50.0,
            'resource_weight' => 20.0,
            'risk_weight' => 15.0,
            'budget_weight' => 15.0,
        ]);

        $plan->refresh();
        $this->assertEquals(50.0, $plan->target_weight);
        $this->assertEquals(20.0, $plan->resource_weight);
        $this->assertEquals(15.0, $plan->risk_weight);
        $this->assertEquals(15.0, $plan->budget_weight);
    }

    public function test_weights_must_total_100_percent(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Validation Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        // Total 90% (under 100)
        $responseUnder = $this->actingAs($this->marshall)
            ->patchJson(route('plans.weights.update', $plan), [
                'target_weight' => 50,
                'resource_weight' => 20,
                'risk_weight' => 10,
                'budget_weight' => 10,
            ]);

        $responseUnder->assertStatus(422);
        $responseUnder->assertJsonValidationErrors(['weights']);

        // Total 110% (over 100)
        $responseOver = $this->actingAs($this->marshall)
            ->patchJson(route('plans.weights.update', $plan), [
                'target_weight' => 60,
                'resource_weight' => 30,
                'risk_weight' => 10,
                'budget_weight' => 10,
            ]);

        $responseOver->assertStatus(422);
        $responseOver->assertJsonValidationErrors(['weights']);

        // Original weights intact
        $plan->refresh();
        $this->assertEquals(60.0, $plan->target_weight);
        $this->assertEquals(20.0, $plan->resource_weight);
        $this->assertEquals(10.0, $plan->risk_weight);
        $this->assertEquals(10.0, $plan->budget_weight);
    }

    public function test_weights_cannot_be_negative(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Negative Weights Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->marshall)
            ->patchJson(route('plans.weights.update', $plan), [
                'target_weight' => -10,
                'resource_weight' => 50,
                'risk_weight' => 30,
                'budget_weight' => 30,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_weight']);
    }

    public function test_executor_cannot_modify_weights(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Executor Restricted Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        // Attempt direct patch endpoint
        $response = $this->actingAs($this->executor)
            ->patchJson(route('plans.weights.update', $plan), [
                'target_weight' => 40,
                'resource_weight' => 20,
                'risk_weight' => 20,
                'budget_weight' => 20,
            ]);

        $response->assertStatus(403);

        // Attempt plan update route
        $updateResponse = $this->actingAs($this->executor)
            ->put(route('plans.update', $plan), [
                'title' => 'Attempted Hack',
                'executor_id' => $this->executor->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(14)->format('Y-m-d'),
                'target_weight' => 40,
                'resource_weight' => 20,
                'risk_weight' => 20,
                'budget_weight' => 20,
            ]);

        $plan->refresh();
        $this->assertEquals(60.0, $plan->target_weight);
        $this->assertEquals(20.0, $plan->resource_weight);
        $this->assertEquals(10.0, $plan->risk_weight);
        $this->assertEquals(10.0, $plan->budget_weight);
    }

    public function test_marshall_can_modify_weights_via_plan_edit_form(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Edit Form Weights Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->marshall)
            ->put(route('plans.update', $plan), [
                'title' => 'Updated Weights Plan',
                'executor_id' => $this->executor->id,
                'status' => 'Open',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(14)->format('Y-m-d'),
                'target_weight' => 50,
                'resource_weight' => 20,
                'risk_weight' => 15,
                'budget_weight' => 15,
            ]);

        $response->assertRedirect(route('plans.show', $plan));

        $plan->refresh();
        $this->assertEquals(50.0, $plan->target_weight);
        $this->assertEquals(20.0, $plan->resource_weight);
        $this->assertEquals(15.0, $plan->risk_weight);
        $this->assertEquals(15.0, $plan->budget_weight);
    }

    public function test_marshall_can_set_custom_weights_on_plan_creation(): void
    {
        $response = $this->actingAs($this->marshall)
            ->post(route('plans.store'), [
                'description' => 'Brand new plan with custom weights',
                'executor_id' => $this->executor->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(14)->format('Y-m-d'),
                'target_weight' => 40,
                'resource_weight' => 30,
                'risk_weight' => 15,
                'budget_weight' => 15,
            ]);

        $plan = PlanRecord::latest('id')->first();
        $this->assertNotNull($plan);
        $response->assertRedirect(route('plans.show', $plan));

        $this->assertEquals(40.0, $plan->target_weight);
        $this->assertEquals(30.0, $plan->resource_weight);
        $this->assertEquals(15.0, $plan->risk_weight);
        $this->assertEquals(15.0, $plan->budget_weight);
    }

    public function test_evaluation_score_calculated_accurately_with_custom_weights(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Scoring Precision Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
            'target_weight' => 70.0,
            'resource_weight' => 10.0,
            'risk_weight' => 10.0,
            'budget_weight' => 10.0,
        ]);

        // Target: 100%
        $plan->targetObjectives()->create([
            'description' => 'Target 1',
            'quantity' => '1',
            'status' => 'Hit',
        ]);

        // Resource: 50%
        $plan->resources()->createMany([
            ['description' => 'Res 1', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Hit'],
            ['description' => 'Res 2', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Missed'],
        ]);

        // Risk: 0%
        $plan->riskManagements()->create([
            'risk' => 'Risk 1',
            'impact' => 'Lv 1',
            'mitigation' => 'Mit 1',
            'status' => 'Missed',
        ]);

        // Budget: 100%
        $plan->budgets()->create([
            'description' => 'Budget 1',
            'quantity' => '1000',
            'status' => 'Hit',
        ]);

        // Score: (100 * 0.70) + (50 * 0.10) + (0 * 0.10) + (100 * 0.10) = 70 + 5 + 0 + 10 = 85.0%
        $this->assertEquals(85.0, $plan->evaluation_score);
    }

    public function test_weights_ui_controls_visibility_based_on_role(): void
    {
        $plan = PlanRecord::create([
            'title' => 'UI Visibility Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
        ]);

        // Marshall should see weights modal and button
        $marshallShow = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $marshallShow->assertStatus(200);
        $marshallShow->assertSee('id="editWeightsModal"', false);
        $marshallShow->assertSee('data-bs-target="#editWeightsModal"', false);

        // Executor should not see modal or edit weights button
        $executorShow = $this->actingAs($this->executor)->get(route('plans.show', $plan));
        $executorShow->assertStatus(200);
        $executorShow->assertDontSee('id="editWeightsModal"', false);
        $executorShow->assertDontSee('data-bs-target="#editWeightsModal"', false);
    }
}

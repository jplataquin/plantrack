<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\Resource;
use App\Models\RiskManagement;
use App\Models\TargetObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanTrackTest extends TestCase
{
    use RefreshDatabase;

    protected User $executor;

    protected User $marshall;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);

        // Create users
        $this->executor = User::factory()->create([
            'name' => 'Test Executor',
            'email' => 'executor@test.com',
        ]);
        $this->executor->assignRole($executorRole);

        $this->marshall = User::factory()->create([
            'name' => 'Test Marshall',
            'email' => 'marshall@test.com',
        ]);
        $this->marshall->assignRole($marshallRole);
    }

    public function test_executor_can_create_a_plan_record(): void
    {
        $response = $this->actingAs($this->executor)->post(route('plans.store'), [
            'title' => 'Alpha Launch Plan',
            'description' => 'Detailed plan for Alpha release.',
            'executor_id' => $this->executor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plan_records', [
            'title' => 'Alpha Launch Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
        ]);
    }

    public function test_create_plan_form_defaults_end_date_to_five_days_from_current_date(): void
    {
        $response = $this->actingAs($this->executor)->get(route('plans.create'));

        $response->assertStatus(200);
        $expectedEndDate = date('Y-m-d', strtotime('+5 days'));
        $response->assertSee('name="end_date" value="'.$expectedEndDate.'"', false);
    }

    public function test_create_and_edit_plan_forms_do_not_contain_title_input(): void
    {
        $responseCreate = $this->actingAs($this->executor)->get(route('plans.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertDontSee('name="title"', false);
        $responseCreate->assertDontSee('PLAN TITLE');

        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $responseEdit = $this->actingAs($this->executor)->get(route('plans.edit', $plan));
        $responseEdit->assertStatus(200);
        $responseEdit->assertDontSee('name="title"', false);
    }

    public function test_plan_created_without_title_defaults_to_padded_id(): void
    {
        $response = $this->actingAs($this->executor)->post(route('plans.store'), [
            'description' => 'Autonomous plan title test.',
            'executor_id' => $this->executor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $plan = PlanRecord::latest('id')->first();
        $this->assertNotNull($plan);
        $expectedTitle = 'Plan - '.str_pad((string) $plan->id, 4, '0', STR_PAD_LEFT);
        $this->assertEquals($expectedTitle, $plan->title);

        $response->assertRedirect(route('plans.show', $plan));

        $viewResponse = $this->actingAs($this->executor)->get(route('plans.show', $plan));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee($expectedTitle);
    }

    public function test_can_add_target_objective_component(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Target',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Reach 1,000 active users',
            'quantity' => '1000 users',
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('target_objectives', [
            'plan_record_id' => $plan->id,
            'description' => 'Reach 1,000 active users',
            'quantity' => '1000 users',
            'priority' => 'low',
            'actual' => null,
            'status' => null,
        ]);
    }

    public function test_can_add_target_objective_with_custom_priority(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with High Priority Target',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Deploy security patch',
            'quantity' => '100%',
            'priority' => 'critical',
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('target_objectives', [
            'plan_record_id' => $plan->id,
            'description' => 'Deploy security patch',
            'priority' => 'critical',
            'actual' => null,
            'status' => null,
        ]);
    }

    public function test_can_create_target_with_unit_and_update_actual(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Actual and Unit Target',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        // Marshall creates target (actual and status default to null/pending)
        $response = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Deliver microservices',
            'quantity' => '100',
            'unit' => 'services',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('plans.show', $plan));

        $target = $plan->targetObjectives()->latest()->first();
        $this->assertNotNull($target);
        $this->assertEquals('100', $target->quantity);
        $this->assertNull($target->actual);
        $this->assertEquals('services', $target->unit);
        $this->assertEquals('100 services', $target->quantity_with_unit);
        $this->assertEquals('—', $target->actual_with_unit);
        $this->assertNull($target->status);

        // Executor fills the Actual Accomplished field
        $updateResponse = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('targets.actual.update', $target), [
                'actual' => '85',
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('85', $target->fresh()->actual);
        $this->assertEquals('85 services', $target->fresh()->actual_with_unit);
    }

    public function test_can_update_target_actual_and_unit_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan for Actual Update',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Onboard active operatives',
            'quantity' => '500',
            'actual' => null,
            'unit' => 'operatives',
        ]);

        $this->assertEquals('—', $target->actual_with_unit);

        $response = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('targets.actual.update', $target), [
                'actual' => '475',
                'unit' => 'operatives',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'actual' => '475',
                'unit' => 'operatives',
                'actual_with_unit' => '475 operatives',
            ]);

        $this->assertEquals('475', $target->fresh()->actual);
        $this->assertEquals('475 operatives', $target->fresh()->actual_with_unit);
    }

    public function test_target_actual_and_unit_rendered_in_plan_view(): void
    {
        $plan = PlanRecord::create([
            'title' => 'View Plan with Actual Target',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Produce telemetry metrics',
            'quantity' => '1000',
            'actual' => '980',
            'unit' => 'packets',
            'status' => 'Hit',
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);
        $response->assertSee('1000 packets');
        $response->assertSee('980 packets');
        $response->assertSee('Actual:');
    }

    public function test_can_update_target_objective_priority_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan for Priority Update',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Initial low priority goal',
            'quantity' => '5 tasks',
            'priority' => 'low',
        ]);

        // Update to high
        $response = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('targets.priority.update', $target), [
                'priority' => 'high',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'priority' => 'high',
                'badge_class' => 'badge badge-priority-high',
            ]);

        $this->assertEquals('high', $target->fresh()->priority);

        // Update to normal
        $responseNormal = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('targets.priority.update', $target), [
                'priority' => 'normal',
            ]);

        $responseNormal->assertStatus(200)
            ->assertJson([
                'success' => true,
                'priority' => 'normal',
                'badge_class' => 'badge badge-priority-normal',
            ]);

        $this->assertEquals('normal', $target->fresh()->priority);
    }

    public function test_can_add_target_objective_with_normal_priority(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Normal Priority Target',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Standard milestone task',
            'quantity' => '50',
            'unit' => 'units',
            'priority' => 'normal',
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('target_objectives', [
            'plan_record_id' => $plan->id,
            'description' => 'Standard milestone task',
            'priority' => 'normal',
        ]);
    }

    public function test_target_objective_priority_validation(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Invalid Priority',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Invalid Priority Target',
            'quantity' => '1',
            'priority' => 'InvalidPriorityLevel',
        ]);

        $response->assertSessionHasErrors(['priority']);
    }

    public function test_target_objective_priority_badge_classes(): void
    {
        $target = new TargetObjective(['priority' => 'critical']);
        $this->assertEquals('badge badge-priority-critical', $target->priority_badge_class);

        $target->priority = 'high';
        $this->assertEquals('badge badge-priority-high', $target->priority_badge_class);

        $target->priority = 'normal';
        $this->assertEquals('badge badge-priority-normal', $target->priority_badge_class);

        $target->priority = 'low';
        $this->assertEquals('badge badge-priority-low', $target->priority_badge_class);
    }

    public function test_can_add_resource_component(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Resource',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->executor)->post(route('resources.store', $plan), [
            'description' => 'Frontend Developers',
            'quantity' => '3 Engineers',
            'target_date' => now()->addDays(5)->toDateString(),
            'status' => 'Hit',
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('resources', [
            'plan_record_id' => $plan->id,
            'description' => 'Frontend Developers',
            'quantity' => '3 Engineers',
            'status' => 'Hit',
        ]);
    }

    public function test_can_add_risk_management_component(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan with Risk',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->executor)->post(route('risks.store', $plan), [
            'risk' => 'Server Outage during release',
            'impact' => 'Lv 3 - All target objectives are affected',
            'mitigation' => 'Deploy multi-region failover cluster',
        ]);

        $response->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('risk_management', [
            'plan_record_id' => $plan->id,
            'risk' => 'Server Outage during release',
            'impact' => 'Lv 3 - All target objectives are affected',
            'mitigation' => 'Deploy multi-region failover cluster',
            'status' => null,
        ]);
    }

    public function test_marshall_and_executor_can_add_polymorphic_comments(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan for Feedback',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Deliver MVP UI',
            'quantity' => '10 screens',
            'target_date' => now()->addDays(10),
            'status' => 'Hit',
        ]);

        // Executor remarks
        $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $target->id,
            'body' => 'All screens completed ahead of schedule.',
        ])->assertRedirect(route('plans.show', $plan));

        // Marshall remarks
        $this->actingAs($this->marshall)->post(route('comments.store'), [
            'commentable_type' => 'target_objective',
            'commentable_id' => $target->id,
            'body' => 'Evaluation verified. The screens pass quality inspection.',
        ])->assertRedirect(route('plans.show', $plan));

        $this->assertCount(2, $target->comments);
        $this->assertEquals('All screens completed ahead of schedule.', $target->comments[0]->body);
        $this->assertEquals($this->executor->id, $target->comments[0]->user_id);
        $this->assertEquals('Evaluation verified. The screens pass quality inspection.', $target->comments[1]->body);
        $this->assertEquals($this->marshall->id, $target->comments[1]->user_id);
    }

    public function test_can_post_comment_via_ajax_with_dynamic_json_response(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan for AJAX Comments',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Test AJAX Comment Posting',
            'quantity' => '1 item',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('comments.store'), [
                'commentable_type' => 'target_objective',
                'commentable_id' => $target->id,
                'body' => "Multi-line remark\nTesting textarea and AJAX thread update",
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'comments_count' => 1,
                'commentable_type' => 'target_objective',
                'commentable_id' => $target->id,
                'comment' => [
                    'body' => "Multi-line remark\nTesting textarea and AJAX thread update",
                    'user' => [
                        'id' => $this->executor->id,
                        'name' => $this->executor->name,
                        'is_executor' => true,
                    ],
                    'can_delete' => true,
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => TargetObjective::class,
            'commentable_id' => $target->id,
            'body' => "Multi-line remark\nTesting textarea and AJAX thread update",
        ]);
    }

    public function test_can_delete_comment_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Plan for Comment Deletion',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target with comment to delete',
            'quantity' => '1 item',
            'priority' => 'normal',
        ]);

        $comment = $target->comments()->create([
            'user_id' => $this->executor->id,
            'body' => 'Remark to be deleted asynchronously',
        ]);

        $response = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('comments.destroy', $comment));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment deleted successfully.',
            ]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_can_update_overall_plan_status(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Review Lifecycle Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $this->actingAs($this->marshall)->patch(route('plans.status.update', $plan), [
            'status' => 'Review',
        ])->assertRedirect(route('plans.show', $plan));

        $this->assertEquals('Review', $plan->fresh()->status);

        $this->actingAs($this->marshall)->patch(route('plans.status.update', $plan), [
            'status' => 'Close',
        ])->assertRedirect(route('plans.show', $plan));

        $this->assertEquals('Close', $plan->fresh()->status);

        $this->actingAs($this->marshall)->patch(route('plans.status.update', $plan), [
            'status' => 'Void',
        ])->assertRedirect(route('plans.show', $plan));

        $this->assertEquals('Void', $plan->fresh()->status);
        $this->assertEquals('badge badge-plan-void', $plan->fresh()->status_badge_class);
    }

    public function test_plan_page_renders_back_button_in_title_card_and_not_in_sidebar(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // Sidebar should no longer contain "Edit Plan Details" or "Back to Executors"
        $response->assertDontSee('Edit Plan Details');
        $response->assertDontSee('Back to Executors');

        // Plan title card must contain Back button linking to executors.index and Edit button
        $response->assertSee('<a href="'.route('executors.index').'" class="btn btn-neon-outline btn-sm d-inline-flex align-items-center">', false);
        $response->assertSee('<a href="'.route('plans.edit', $plan).'" class="btn btn-neon-outline btn-sm d-inline-flex align-items-center">', false);
    }

    public function test_identified_risk_in_add_risk_modal_is_a_textarea(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // Assert textarea for identified risk in add risk modal
        $response->assertSee('<textarea name="risk" class="form-control" rows="3" placeholder="Risk description..." required></textarea>', false);
        $response->assertDontSee('<input type="text" name="risk"', false);
    }

    public function test_executor_cannot_update_overall_plan_status(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Executor Status Lock Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        // Attempting to patch plan status via quick status endpoint
        $response = $this->actingAs($this->executor)->patch(route('plans.status.update', $plan), [
            'status' => 'Close',
        ]);
        $response->assertStatus(403);
        $this->assertEquals('Open', $plan->fresh()->status);

        // Attempting to alter status via general plan update form
        $updateResponse = $this->actingAs($this->executor)->put(route('plans.update', $plan), [
            'title' => 'Updated Plan Title',
            'executor_id' => $this->executor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => 'Close',
        ]);
        $updateResponse->assertRedirect(route('plans.show', $plan));
        // Status must remain Open
        $this->assertEquals('Open', $plan->fresh()->status);
    }

    public function test_plan_view_hides_status_dropdown_for_executor(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Executor View Status Lock Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        // Executor view should not have plan-status-select
        $executorResponse = $this->actingAs($this->executor)->get(route('plans.show', $plan));
        $executorResponse->assertStatus(200);
        $executorResponse->assertDontSee('id="plan-status-select"', false);

        // Marshall view should have plan-status-select
        $marshallResponse = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $marshallResponse->assertStatus(200);
        $marshallResponse->assertSee('id="plan-status-select"', false);
    }

    public function test_can_view_executors_index(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('executors.index'));

        $response->assertStatus(200);
        $response->assertSee('EXECUTOR');
        $response->assertSee('DIRECTORY');
        $response->assertSee($this->executor->name);
    }

    public function test_can_view_executor_plans_page(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Specific Executor Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('executors.show', $this->executor));

        $response->assertStatus(200);
        $response->assertSee($this->executor->name);
        $response->assertSee('Specific Executor Plan');
        $response->assertSee('PLANS BREAKDOWN');
    }

    public function test_plan_record_scoring_with_no_items_defaults_to_zero_percent(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Empty Plan for Scoring',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $this->assertEquals(0.0, $plan->target_score);
        $this->assertEquals(0.0, $plan->resource_score);
        $this->assertEquals(0.0, $plan->risk_score);
        $this->assertEquals(0.0, $plan->evaluation_score);

        // Verify view renders 0.0%
        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);
        $response->assertSee('0.0%');
    }

    public function test_plan_record_scoring_with_all_void_items_defaults_to_100_percent(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Voided Plan for Scoring',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $plan->targetObjectives()->create([
            'description' => 'Cancelled target',
            'quantity' => '10',
            'target_date' => now()->addDays(5),
            'status' => 'Void',
        ]);

        $plan->resources()->create([
            'description' => 'Cancelled server',
            'quantity' => '1 server',
            'target_date' => now()->addDays(5),
            'status' => 'Void',
        ]);

        $plan->riskManagements()->create([
            'risk' => 'Cancelled risk',
            'impact' => 'Low',
            'mitigation' => 'None',
            'status' => 'Void',
        ]);

        $this->assertEquals(100.0, $plan->target_score);
        $this->assertEquals(100.0, $plan->resource_score);
        $this->assertEquals(100.0, $plan->risk_score);
        $this->assertEquals(100.0, $plan->evaluation_score);
    }

    public function test_plan_record_weighted_score_calculation(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Weighted Scoring Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        // Target Objectives (50%): 2 Hit, 1 Missed => 2 / (3 - 0) * 100 = 66.67%
        $plan->targetObjectives()->createMany([
            ['description' => 'Target 1', 'quantity' => '1', 'status' => 'Hit'],
            ['description' => 'Target 2', 'quantity' => '1', 'status' => 'Hit'],
            ['description' => 'Target 3', 'quantity' => '1', 'status' => 'Missed'],
        ]);

        // Resources (25%): 1 Hit, 1 Missed, 2 Void => 1 / (4 - 2) * 100 = 50.00%
        $plan->resources()->createMany([
            ['description' => 'Resource 1', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Hit'],
            ['description' => 'Resource 2', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Missed'],
            ['description' => 'Resource 3', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Void'],
            ['description' => 'Resource 4', 'quantity' => '1', 'target_date' => now()->addDays(5), 'status' => 'Void'],
        ]);

        // Risk Management (10%): 2 Hit, 0 Missed => 2 / (2 - 0) * 100 = 100.00%
        $plan->riskManagements()->createMany([
            ['risk' => 'Risk 1', 'impact' => 'Lv 1', 'mitigation' => 'Mit 1', 'status' => 'Hit'],
            ['risk' => 'Risk 2', 'impact' => 'Lv 2', 'mitigation' => 'Mit 2', 'status' => 'Hit'],
        ]);

        // Budgets (10%): 1 Hit, 0 Missed => 100.00%
        $plan->budgets()->create([
            'description' => 'Budget 1',
            'quantity' => '1000',
            'status' => 'Hit',
        ]);

        $this->assertEquals(66.67, $plan->target_score);
        $this->assertEquals(50.0, $plan->resource_score);
        $this->assertEquals(100.0, $plan->risk_score);
        $this->assertEquals(100.0, $plan->budget_score);

        // Default weights (60% Target, 20% Resource, 10% Risk, 10% Budget)
        // (66.67 * 0.60) + (50.00 * 0.20) + (100.00 * 0.10) + (100.00 * 0.10) = 40.002 + 10.0 + 10.0 + 10.0 = 70.002 => 70.00
        $this->assertEquals(70.00, $plan->evaluation_score);

        // Verify score rendered on show page with default weights
        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);
        $response->assertSee('OVERALL EVALUATION SCORE');
        $response->assertSee('70.0%');
        $response->assertSee('id="label-target-weight"', false);
        $response->assertSee('id="label-resource-weight"', false);
        $response->assertSee('id="label-risk-weight"', false);
        $response->assertSee('id="label-budget-weight"', false);

        // Custom weights (50% Target, 20% Resource, 15% Risk, 15% Budget)
        $plan->update([
            'target_weight' => 50.0,
            'resource_weight' => 20.0,
            'risk_weight' => 15.0,
            'budget_weight' => 15.0,
        ]);
        // (66.67 * 0.50) + (50.00 * 0.20) + (100.00 * 0.15) + (100.00 * 0.15) = 33.335 + 10.0 + 15.0 + 15.0 = 73.335 => 73.34
        $this->assertEquals(73.34, $plan->fresh()->evaluation_score);

        $responseCustom = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $responseCustom->assertStatus(200);
        $responseCustom->assertSee('73.3%');
        $responseCustom->assertSee('id="label-target-weight">50<', false);
        $responseCustom->assertSee('id="label-resource-weight">20<', false);
        $responseCustom->assertSee('id="label-risk-weight">15<', false);
        $responseCustom->assertSee('id="label-budget-weight">15<', false);
    }

    public function test_can_update_target_objective_status_to_pending_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Pending Status Test Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target to mark Pending',
            'quantity' => '1',
            'target_date' => now()->addDays(5),
            'status' => 'Hit',
        ]);

        // When Hit, target score is 100%
        $this->assertEquals(100.0, $plan->fresh()->target_score);

        // Update to Pending (empty string)
        $response = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('targets.status.update', $target), [
                'status' => '',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'Unevaluated',
                'badge_class' => 'badge badge-unevaluated',
            ]);

        $this->assertNull($target->fresh()->status);

        // With target pending, valid=1, hit=0 => target score is now 0%
        $this->assertEquals(0.0, $plan->fresh()->target_score);
    }

    public function test_can_update_resource_and_risk_status_to_pending_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Pending Resource & Risk Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $resource = $plan->resources()->create([
            'description' => 'Server node',
            'quantity' => '1',
            'target_date' => now()->addDays(5),
            'status' => 'Hit',
        ]);

        $risk = $plan->riskManagements()->create([
            'risk' => 'Downtime risk',
            'impact' => 'High',
            'mitigation' => 'Backup cluster',
            'status' => 'Hit',
        ]);

        // Update resource to Pending
        $resResponse = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('resources.status.update', $resource), [
                'status' => 'Pending',
            ]);

        $resResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'Unevaluated',
                'badge_class' => 'badge badge-unevaluated',
            ]);
        $this->assertNull($resource->fresh()->status);
        $this->assertEquals(0.0, $plan->fresh()->resource_score);

        // Update risk to Pending
        $riskResponse = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('risks.status.update', $risk), [
                'status' => '',
            ]);

        $riskResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'Unevaluated',
                'badge_class' => 'badge badge-unevaluated',
            ]);
        $this->assertNull($risk->fresh()->status);
        $this->assertEquals(0.0, $plan->fresh()->risk_score);
    }

    public function test_executor_cannot_create_target_objective(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Target Security Test Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->executor)->post(route('targets.store', $plan), [
            'description' => 'Unauthorized target creation',
            'quantity' => '10',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_executor_cannot_edit_or_delete_target_objective(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Target Edit Security Test Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target to protect',
            'quantity' => '10',
            'status' => null,
        ]);

        // Attempting to edit target details
        $editResponse = $this->actingAs($this->executor)->put(route('targets.update', $target), [
            'description' => 'Hacked description',
            'quantity' => '999',
        ]);
        $editResponse->assertStatus(403);

        // Attempting to change priority
        $priorityResponse = $this->actingAs($this->executor)->patch(route('targets.priority.update', $target), [
            'priority' => 'critical',
        ]);
        $priorityResponse->assertStatus(403);

        // Attempting to evaluate status
        $statusResponse = $this->actingAs($this->executor)->patch(route('targets.status.update', $target), [
            'status' => 'Hit',
        ]);
        $statusResponse->assertStatus(403);

        // Attempting to delete target
        $deleteResponse = $this->actingAs($this->executor)->delete(route('targets.destroy', $target));
        $deleteResponse->assertStatus(403);
    }

    public function test_marshall_can_edit_target_objective_details(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Marshall Edit Test Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Original description',
            'quantity' => '10',
            'unit' => 'servers',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->marshall)->put(route('targets.update', $target), [
            'description' => 'Updated by Marshall',
            'quantity' => '20',
            'unit' => 'nodes',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('plans.show', $plan));

        $target->refresh();
        $this->assertEquals('Updated by Marshall', $target->description);
        $this->assertEquals('20', $target->quantity);
        $this->assertEquals('nodes', $target->unit);
        $this->assertEquals('high', $target->priority);
    }

    public function test_target_objectives_operate_without_target_date(): void
    {
        $plan = PlanRecord::create([
            'title' => 'No Target Date Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        // Marshall creates target without any target_date
        $createResponse = $this->actingAs($this->marshall)->post(route('targets.store', $plan), [
            'description' => 'Target without date',
            'quantity' => '15',
            'unit' => 'deployments',
            'priority' => 'high',
        ]);
        $createResponse->assertRedirect(route('plans.show', $plan));

        $this->assertDatabaseHas('target_objectives', [
            'plan_record_id' => $plan->id,
            'description' => 'Target without date',
            'quantity' => '15',
            'unit' => 'deployments',
            'priority' => 'high',
        ]);

        $target = $plan->targetObjectives()->latest()->first();
        $this->assertNotNull($target);

        // Marshall updates target without any target_date
        $updateResponse = $this->actingAs($this->marshall)->put(route('targets.update', $target), [
            'description' => 'Target without date updated',
            'quantity' => '30',
            'unit' => 'deployments',
            'priority' => 'critical',
        ]);
        $updateResponse->assertRedirect(route('plans.show', $plan));

        $target->refresh();
        $this->assertEquals('Target without date updated', $target->description);
        $this->assertEquals('30', $target->quantity);
        $this->assertEquals('critical', $target->priority);

        // Verify view renders target details and does not have target date inputs in target modal
        $viewResponse = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Target without date updated');
        $viewResponse->assertDontSee('name="target_date" min="'.$plan->start_date->format('Y-m-d').'"', false);
    }

    public function test_can_add_target_objective_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('targets.store', $plan), [
                'description' => 'AJAX Target Deployment',
                'quantity' => '500',
                'unit' => 'nodes',
                'priority' => 'critical',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Target / Objective added successfully.',
                'count' => 1,
            ]);

        $this->assertStringContainsString('AJAX Target Deployment', $response->json('html'));
        $this->assertStringContainsString('500 nodes', $response->json('html'));

        $this->assertDatabaseHas('target_objectives', [
            'plan_record_id' => $plan->id,
            'description' => 'AJAX Target Deployment',
            'quantity' => '500',
            'unit' => 'nodes',
            'priority' => 'critical',
        ]);
    }

    public function test_can_add_resource_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('resources.store', $plan), [
                'description' => 'AJAX Cloud GPU Cluster',
                'quantity' => '8 GPUs, $12,000',
                'target_date' => now()->addDays(3)->toDateString(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource added successfully.',
                'count' => 1,
            ]);

        $this->assertStringContainsString('AJAX Cloud GPU Cluster', $response->json('html'));
        $this->assertStringContainsString('8 GPUs, $12,000', $response->json('html'));

        $this->assertDatabaseHas('resources', [
            'plan_record_id' => $plan->id,
            'description' => 'AJAX Cloud GPU Cluster',
            'quantity' => '8 GPUs, $12,000',
        ]);
    }

    public function test_can_add_risk_management_via_ajax(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->marshall)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('risks.store', $plan), [
                'risk' => 'AJAX Supply chain delay in fiber optic cable delivery',
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Route through secondary satellite link redundancy',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Risk Management item added successfully.',
                'count' => 1,
            ]);

        $this->assertStringContainsString('AJAX Supply chain delay in fiber optic cable delivery', $response->json('html'));
        $this->assertStringContainsString('Route through secondary satellite link redundancy', $response->json('html'));

        $this->assertDatabaseHas('risk_management', [
            'plan_record_id' => $plan->id,
            'risk' => 'AJAX Supply chain delay in fiber optic cable delivery',
            'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
            'mitigation' => 'Route through secondary satellite link redundancy',
        ]);
    }

    public function test_can_add_resource_with_unit_actual_and_for_target(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target Alpha Goal',
            'quantity' => '100',
        ]);

        // 1. Marshall creates resource without passing status (should default to null/Pending)
        $response = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Server rack hardware',
            'quantity' => '5',
            'unit' => 'racks',
            'actual' => '4',
            'for' => $target->id,
            'target_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertRedirect(route('plans.show', $plan));

        $resource = $plan->resources()->latest()->first();
        $this->assertNotNull($resource);
        $this->assertEquals('5', $resource->quantity);
        $this->assertEquals('racks', $resource->unit);
        $this->assertEquals('4', $resource->actual);
        $this->assertEquals('5 racks', $resource->quantity_with_unit);
        $this->assertEquals('4 racks', $resource->actual_with_unit);
        $this->assertEquals($target->id, $resource->target_objective_id);
        $this->assertEquals('Target Alpha Goal', $resource->for->description);
        $this->assertNull($resource->status); // Status is always pending by default upon creation

        // 2. View displays unit, actual, and for target badge, and modal does not have status select
        $viewResponse = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('5 racks');
        $viewResponse->assertSee('4 racks');
        $viewResponse->assertSee('Target Alpha Goal');
        $viewResponse->assertDontSee('#addResourceModal select[name="status"]', false);
    }

    public function test_resource_target_date_must_be_within_plan_record_date_scope(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(15)->startOfDay(),
        ]);

        // Target date before plan start date should fail
        $beforeResponse = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Early Resource',
            'quantity' => '1',
            'target_date' => now()->subDay()->toDateString(),
        ]);
        $beforeResponse->assertSessionHasErrors(['target_date']);

        // Target date after plan end date should fail
        $afterResponse = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Late Resource',
            'quantity' => '1',
            'target_date' => now()->addDays(16)->toDateString(),
        ]);
        $afterResponse->assertSessionHasErrors(['target_date']);

        // Target date within scope should succeed
        $validResponse = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Valid Date Resource',
            'quantity' => '1',
            'target_date' => now()->addDays(7)->toDateString(),
        ]);
        $validResponse->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('resources', [
            'plan_record_id' => $plan->id,
            'description' => 'Valid Date Resource',
        ]);
    }

    public function test_can_update_resource_actual_accomplished(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(20),
        ]);

        $resource = $plan->resources()->create([
            'description' => 'Developer workstations',
            'quantity' => '10',
            'unit' => 'units',
            'actual' => null,
            'target_date' => now()->addDays(5),
        ]);

        // Executor updates actual
        $response = $this->actingAs($this->executor)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('resources.actual.update', $resource), [
                'actual' => '8',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'actual' => '8',
                'actual_with_unit' => '8 units',
            ]);

        $this->assertEquals('8', $resource->fresh()->actual);
        $this->assertEquals('8 units', $resource->fresh()->actual_with_unit);
    }

    public function test_executor_cannot_add_or_input_data_when_plan_is_not_open(): void
    {
        $nonOpenStatuses = ['Review', 'Close', 'Void'];

        foreach ($nonOpenStatuses as $status) {
            $plan = PlanRecord::create([
                'executor_id' => $this->executor->id,
                'status' => $status,
                'start_date' => now()->startOfDay(),
                'end_date' => now()->addDays(20)->startOfDay(),
            ]);

            $target = $plan->targetObjectives()->create([
                'description' => 'Target Goal',
                'quantity' => '100',
                'actual' => '50',
            ]);

            $resource = $plan->resources()->create([
                'description' => 'Server HW',
                'quantity' => '5',
                'unit' => 'nodes',
                'actual' => null,
                'target_date' => now()->addDays(5),
            ]);

            $risk = $plan->riskManagements()->create([
                'risk' => 'Downtime risk',
                'impact' => 'Lv 1',
                'mitigation' => 'Backup systems',
            ]);

            $comment = $target->comments()->create([
                'user_id' => $this->executor->id,
                'body' => 'Existing remark',
            ]);

            // 1. Executor cannot update target actual
            $this->actingAs($this->executor)
                ->patch(route('targets.actual.update', $target), ['actual' => '99'])
                ->assertStatus(403);

            // 2. Executor cannot add resource
            $this->actingAs($this->executor)
                ->post(route('resources.store', $plan), [
                    'description' => 'Disallowed resource',
                    'quantity' => '1',
                    'target_date' => now()->addDays(5)->toDateString(),
                ])
                ->assertStatus(403);

            // 3. Executor cannot update resource actual
            $this->actingAs($this->executor)
                ->patch(route('resources.actual.update', $resource), ['actual' => '10'])
                ->assertStatus(403);

            // 4. Executor cannot delete resource
            $this->actingAs($this->executor)
                ->delete(route('resources.destroy', $resource))
                ->assertStatus(403);

            // 5. Executor cannot add risk
            $this->actingAs($this->executor)
                ->post(route('risks.store', $plan), [
                    'risk' => 'Disallowed risk',
                    'impact' => 'Lv 2',
                    'mitigation' => 'None',
                ])
                ->assertStatus(403);

            // 6. Executor cannot delete risk
            $this->actingAs($this->executor)
                ->delete(route('risks.destroy', $risk))
                ->assertStatus(403);

            // 7. Executor cannot post remark
            $this->actingAs($this->executor)
                ->post(route('comments.store'), [
                    'commentable_type' => 'target_objective',
                    'commentable_id' => $target->id,
                    'body' => 'Disallowed comment',
                ])
                ->assertStatus(403);

            // 8. Executor cannot delete remark
            $this->actingAs($this->executor)
                ->delete(route('comments.destroy', $comment))
                ->assertStatus(403);

            // 9. UI locks add buttons and actual edit buttons for Executor
            $view = $this->actingAs($this->executor)->get(route('plans.show', $plan));
            $view->assertStatus(200);
            $view->assertDontSee('data-bs-target="#addResourceModal"', false);
            $view->assertDontSee('data-bs-target="#addRiskModal"', false);
            $view->assertDontSee('data-bs-target="#editActualModal-'.$target->id.'"', false);
            $view->assertDontSee('data-bs-target="#editResourceActualModal-'.$resource->id.'"', false);
            $view->assertSee('Component remarks and input are locked because this Plan Record status is');

            // 10. Marshall CAN still add or update actual on non-open plans
            $marshallActual = $this->actingAs($this->marshall)
                ->patch(route('targets.actual.update', $target), ['actual' => '100']);
            $marshallActual->assertRedirect();
            $this->assertEquals('100', $target->fresh()->actual);
        }
    }

    public function test_component_modals_have_standardized_width_and_remarks_textarea_is_centered_full_width(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target Alpha',
            'quantity' => '100',
        ]);

        $resource = $plan->resources()->create([
            'description' => 'Resource Alpha',
            'quantity' => '5',
            'target_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // Standardized modal width class on all component form modals
        $response->assertSee('<div class="modal-dialog modal-dialog-centered component-modal-dialog">', false);
        $response->assertDontSee('modal-dialog modal-dialog-centered modal-sm', false);

        // Remarks form textarea is full width and centered
        $response->assertSee('class="form-control custom-scrollbar remark-body-textarea w-100"', false);
        $response->assertSee('class="mb-2 w-100 d-flex justify-content-center"', false);
    }

    public function test_resource_create_form_shows_targets_in_for_field_and_hides_actual_field(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $target1 = $plan->targetObjectives()->create([
            'description' => 'Deploy Kubernetes Cluster',
            'quantity' => '3',
            'unit' => 'nodes',
        ]);

        $target2 = $plan->targetObjectives()->create([
            'description' => 'Setup CI Pipeline',
            'quantity' => '1',
            'unit' => 'pipeline',
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // 1. Available target/objectives are in the "For" field
        $response->assertSee('<select name="for" id="for" class="form-select">', false);
        $response->assertSee($target1->description);
        $response->assertSee($target2->description);

        // 2. In resource creation modal, Actual field is NOT shown
        $response->assertDontSee('ACTUAL ACCOMPLISHED (OPTIONAL)', false);

        // 3. Resource creation with 'for' field links resource to target
        $createResponse = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Cloud Compute Nodes',
            'quantity' => '3',
            'unit' => 'nodes',
            'for' => $target1->id,
            'target_date' => now()->addDays(10)->toDateString(),
        ]);

        $createResponse->assertRedirect(route('plans.show', $plan));
        $this->assertDatabaseHas('resources', [
            'plan_record_id' => $plan->id,
            'description' => 'Cloud Compute Nodes',
            'target_objective_id' => $target1->id,
            'actual' => null,
            'status' => null,
        ]);
    }

    public function test_resource_description_is_truncated_to_maximum_200_chars_on_resource_card(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $longDescription = str_repeat('A very long resource requirement specification details and hardware notes. ', 6);
        $this->assertGreaterThan(200, strlen($longDescription));

        $plan->resources()->create([
            'description' => $longDescription,
            'quantity' => '10',
            'target_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        $expectedTruncated = Str::limit($longDescription, 200);
        $response->assertSee('<h6 class="text-white fw-bold mb-0 font-rajdhani fs-6 fs-md-5" title="'.$longDescription.'">'.$expectedTruncated.'</h6>', false);
    }

    public function test_marshall_can_edit_a_resource(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target Omega',
            'quantity' => '10',
        ]);

        $resource = $plan->resources()->create([
            'description' => 'Original Resource',
            'quantity' => '5',
            'unit' => 'servers',
            'target_date' => now()->addDays(5)->startOfDay(),
        ]);

        // Marshall edits resource
        $response = $this->actingAs($this->marshall)->put(route('resources.update', $resource), [
            'description' => 'Updated Cyber Workstations',
            'quantity' => '15',
            'unit' => 'nodes',
            'for' => $target->id,
            'target_date' => now()->addDays(12)->toDateString(),
        ]);

        $response->assertRedirect(route('plans.show', $plan->id));
        $resource->refresh();

        $this->assertEquals('Updated Cyber Workstations', $resource->description);
        $this->assertEquals('15', $resource->quantity);
        $this->assertEquals('nodes', $resource->unit);
        $this->assertEquals($target->id, $resource->target_objective_id);
        $this->assertEquals(now()->addDays(12)->toDateString(), $resource->target_date->toDateString());
    }

    public function test_executor_cannot_edit_a_resource(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $resource = $plan->resources()->create([
            'description' => 'Original Resource',
            'quantity' => '5',
            'unit' => 'servers',
            'target_date' => now()->addDays(5)->startOfDay(),
        ]);

        $response = $this->actingAs($this->executor)->put(route('resources.update', $resource), [
            'description' => 'Executor Attempted Edit',
            'quantity' => '20',
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Original Resource', $resource->fresh()->description);
    }

    public function test_target_card_does_not_render_priority_dropdown_field_and_label(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $target = $plan->targetObjectives()->create([
            'description' => 'Target Without Priority Field',
            'quantity' => '10',
            'priority' => 'critical',
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // Does not render the priority dropdown or the Priority: label row on the card
        $response->assertDontSee('<select class="form-select form-select-sm ajax-priority-select', false);
        $response->assertDontSee('>Priority:</span>', false);
    }

    public function test_resource_date_available_not_in_create_form_and_only_editable_after_created(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // 1. Not in creation form
        $response->assertDontSee('id="addResourceForm">\s*.*name="date_available"', true);

        // 2. Creating resource forces date_available to null
        $storeResponse = $this->actingAs($this->marshall)->post(route('resources.store', $plan), [
            'description' => 'Test Hardware Cluster',
            'quantity' => '4',
            'unit' => 'nodes',
            'target_date' => now()->addDays(5)->toDateString(),
            'date_available' => now()->addDays(2)->toDateString(),
        ]);

        $storeResponse->assertRedirect(route('plans.show', $plan));
        $resource = Resource::where('description', 'Test Hardware Cluster')->first();
        $this->assertNotNull($resource);
        $this->assertNull($resource->date_available);

        // 3. Editable via resources.update after creation
        $updateResponse = $this->actingAs($this->marshall)->put(route('resources.update', $resource), [
            'description' => 'Test Hardware Cluster',
            'quantity' => '4',
            'unit' => 'nodes',
            'target_date' => now()->addDays(5)->toDateString(),
            'date_available' => now()->addDays(3)->toDateString(),
        ]);

        $updateResponse->assertRedirect(route('plans.show', $plan->id));
        $this->assertEquals(now()->addDays(3)->toDateString(), $resource->fresh()->date_available->toDateString());

        // 4. Also editable via actual update
        $this->actingAs($this->marshall)->patch(route('resources.actual.update', $resource), [
            'actual' => '4',
            'date_available' => now()->addDays(4)->toDateString(),
        ]);
        $this->assertEquals(now()->addDays(4)->toDateString(), $resource->fresh()->date_available->toDateString());
    }

    public function test_top_marshall_view_section_does_not_contain_status_badge(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // The top Marshall View box does not render plan-status-badge
        $response->assertDontSee('id="plan-status-badge"', false);
    }

    public function test_risk_creation_does_not_include_status_and_defaults_to_pending(): void
    {
        $plan = PlanRecord::create([
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now()->startOfDay(),
            'end_date' => now()->addDays(20)->startOfDay(),
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $plan));
        $response->assertStatus(200);

        // 1. Status select is not in addRiskModal
        $response->assertDontSee('#addRiskModal select[name="status"]');

        // 2. Risk creation defaults status to null (pending)
        $storeResponse = $this->actingAs($this->marshall)->post(route('risks.store', $plan), [
            'risk' => 'Cloud Outage Risk',
            'impact' => 'Lv 1',
            'mitigation' => 'Deploy multi-region failover',
            'status' => 'Hit',
        ]);

        $storeResponse->assertRedirect(route('plans.show', $plan));
        $risk = RiskManagement::where('risk', 'Cloud Outage Risk')->first();
        $this->assertNotNull($risk);
        $this->assertNull($risk->status);
    }
}

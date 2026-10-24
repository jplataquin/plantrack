<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\CommentAttachment;
use App\Models\PlanRecord;
use App\Models\TargetObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $marshall;

    protected User $executor;

    protected PlanRecord $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create(['name' => 'Chief Marshall']);
        $this->marshall->assignRole($marshallRole);

        $this->executor = User::factory()->create(['name' => 'Field Operative']);
        $this->executor->assignRole($executorRole);

        $this->plan = PlanRecord::create([
            'title' => 'Operation Alpha Plan',
            'executor_id' => $this->executor->id,
            'status' => 'Open',
            'start_date' => now(),
            'end_date' => now()->addDays(14),
            'target_weight' => 60.0,
            'resource_weight' => 20.0,
            'risk_weight' => 10.0,
            'budget_weight' => 10.0,
        ]);
    }

    public function test_marshall_can_create_budget_component(): void
    {
        $target = TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'description' => 'Target 101',
            'quantity' => '10',
            'status' => 'Hit',
        ]);

        $response = $this->actingAs($this->marshall)->post(route('budgets.store', $this->plan), [
            'description' => 'Server Hardware Budget',
            'quantity' => '15000',
            'unit' => 'USD',
            'actual' => '14200',
            'for' => $target->id,
            'status' => 'Hit',
        ]);

        $response->assertRedirect(route('plans.show', $this->plan));

        $this->assertDatabaseHas('budgets', [
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Server Hardware Budget',
            'quantity' => '15000',
            'unit' => 'USD',
            'actual' => '14200',
            'target_objective_id' => $target->id,
            'status' => 'Hit',
        ]);
    }

    public function test_executor_cannot_create_budget_component(): void
    {
        $response = $this->actingAs($this->executor)->post(route('budgets.store', $this->plan), [
            'description' => 'Unauthorized Budget',
            'quantity' => '5000',
            'unit' => 'USD',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, Budget::count());
    }

    public function test_marshall_can_update_budget_component(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Original Budget Description',
            'quantity' => '1000',
            'unit' => 'EUR',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)->put(route('budgets.update', $budget), [
            'description' => 'Updated Budget Description',
            'quantity' => '1200',
            'unit' => 'EUR',
            'actual' => '1150',
            'status' => 'Hit',
        ]);

        $response->assertRedirect(route('plans.show', $this->plan));

        $budget->refresh();
        $this->assertEquals('Updated Budget Description', $budget->description);
        $this->assertEquals('1200', $budget->quantity);
        $this->assertEquals('1150', $budget->actual);
        $this->assertEquals('Hit', $budget->status);
    }

    public function test_executor_cannot_update_budget_component(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Secure Budget',
            'quantity' => '1000',
        ]);

        $response = $this->actingAs($this->executor)->put(route('budgets.update', $budget), [
            'description' => 'Tampered Budget',
            'quantity' => '9999',
        ]);

        $response->assertStatus(403);
        $budget->refresh();
        $this->assertEquals('Secure Budget', $budget->description);
    }

    public function test_marshall_can_update_budget_actual_quantity(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Consulting Hours',
            'quantity' => '40',
            'unit' => 'hrs',
        ]);

        $response = $this->actingAs($this->marshall)->patchJson(route('budgets.actual.update', $budget), [
            'actual' => '38.5',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'actual' => '38.5 hrs',
        ]);

        $budget->refresh();
        $this->assertEquals('38.5', $budget->actual);
    }

    public function test_executor_cannot_update_budget_actual_quantity(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Infrastructure',
            'quantity' => '10',
        ]);

        $response = $this->actingAs($this->executor)->patchJson(route('budgets.actual.update', $budget), [
            'actual' => '20',
        ]);

        $response->assertStatus(403);
        $budget->refresh();
        $this->assertNull($budget->actual);
    }

    public function test_marshall_can_update_budget_status(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Travel expenses',
            'quantity' => '3000',
            'status' => null,
        ]);

        $response = $this->actingAs($this->marshall)->patchJson(route('budgets.status.update', $budget), [
            'status' => 'Hit',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'Hit',
            'badge_class' => 'badge badge-hit',
        ]);

        $budget->refresh();
        $this->assertEquals('Hit', $budget->status);
    }

    public function test_executor_cannot_update_budget_status(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Security Audit',
            'quantity' => '1',
            'status' => null,
        ]);

        $response = $this->actingAs($this->executor)->patchJson(route('budgets.status.update', $budget), [
            'status' => 'Hit',
        ]);

        $response->assertStatus(403);
        $budget->refresh();
        $this->assertNull($budget->status);
    }

    public function test_marshall_can_delete_budget_component(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Obsolete Budget Item',
            'quantity' => '100',
        ]);

        $response = $this->actingAs($this->marshall)->delete(route('budgets.destroy', $budget));

        $response->assertRedirect(route('plans.show', $this->plan));
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_executor_cannot_delete_budget_component(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Protected Budget Item',
            'quantity' => '100',
        ]);

        $response = $this->actingAs($this->executor)->delete(route('budgets.destroy', $budget));

        $response->assertStatus(403);
        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }

    public function test_ui_controls_rendered_for_marshall_and_restricted_for_executor(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Licensing Fees',
            'quantity' => '2500',
            'unit' => 'USD',
            'status' => null,
        ]);

        // Marshall view
        $marshallResponse = $this->actingAs($this->marshall)->get(route('plans.show', $this->plan));
        $marshallResponse->assertStatus(200);
        $marshallResponse->assertSee('id="addBudgetModal"', false);
        $marshallResponse->assertSee("id=\"editBudgetModal-{$budget->id}\"", false);
        $marshallResponse->assertSee("id=\"editBudgetActualModal-{$budget->id}\"", false);
        $marshallResponse->assertSee('ADD BUDGET');

        // Executor view
        $executorResponse = $this->actingAs($this->executor)->get(route('plans.show', $this->plan));
        $executorResponse->assertStatus(200);
        $executorResponse->assertSee('Licensing Fees'); // Can see the budget item
        $executorResponse->assertDontSee('id="addBudgetModal"', false); // Cannot see add modal
        $executorResponse->assertDontSee("id=\"editBudgetModal-{$budget->id}\"", false); // Cannot see edit modal
        $executorResponse->assertDontSee("id=\"editBudgetActualModal-{$budget->id}\"", false); // Cannot see actual edit modal
    }

    public function test_budget_scoring_calculation_in_plan_record(): void
    {
        // 1 Hit Target (100%) -> 60%
        $this->plan->targetObjectives()->create([
            'description' => 'Core objective',
            'quantity' => '1',
            'status' => 'Hit',
        ]);

        // 1 Hit Resource (100%) -> 20%
        $this->plan->resources()->create([
            'description' => 'Workstation',
            'quantity' => '1',
            'target_date' => now(),
            'status' => 'Hit',
        ]);

        // 1 Hit Risk (100%) -> 10%
        $this->plan->riskManagements()->create([
            'risk' => 'Downtime',
            'impact' => 'Lv 1',
            'mitigation' => 'Backup',
            'status' => 'Hit',
        ]);

        // 1 Missed Budget (0%) -> 0% of 10%
        $this->plan->budgets()->create([
            'description' => 'Overspent Budget',
            'quantity' => '5000',
            'status' => 'Missed',
        ]);

        // Evaluation score: (100 * 0.60) + (100 * 0.20) + (100 * 0.10) + (0 * 0.10) = 60 + 20 + 10 + 0 = 90.0%
        $this->assertEquals(0.0, $this->plan->budget_score);
        $this->assertEquals(90.0, $this->plan->evaluation_score);

        // Update budget to Hit (100%)
        $this->plan->budgets()->first()->update(['status' => 'Hit']);
        $this->assertEquals(100.0, $this->plan->fresh()->budget_score);
        $this->assertEquals(100.0, $this->plan->fresh()->evaluation_score);
    }

    public function test_budget_quantity_must_be_numeric(): void
    {
        // Non-numeric string fails
        $responseNonNumeric = $this->actingAs($this->marshall)
            ->postJson(route('budgets.store', $this->plan), [
                'description' => 'Text quantity attempt',
                'quantity' => 'five-thousand',
            ]);

        $responseNonNumeric->assertStatus(422);
        $responseNonNumeric->assertJsonValidationErrors(['quantity']);

        // Negative number fails
        $responseNegative = $this->actingAs($this->marshall)
            ->postJson(route('budgets.store', $this->plan), [
                'description' => 'Negative quantity attempt',
                'quantity' => -100,
            ]);

        $responseNegative->assertStatus(422);
        $responseNegative->assertJsonValidationErrors(['quantity']);

        // Valid decimal number succeeds
        $responseValid = $this->actingAs($this->marshall)
            ->postJson(route('budgets.store', $this->plan), [
                'description' => 'Valid numeric budget',
                'quantity' => '12500.50',
                'unit' => 'USD',
            ]);

        $responseValid->assertStatus(200);
        $responseValid->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseHas('budgets', [
            'description' => 'Valid numeric budget',
            'quantity' => '12500.50',
        ]);
    }

    public function test_first_budget_creation_via_ajax_returns_rendered_card(): void
    {
        $response = $this->actingAs($this->marshall)
            ->postJson(route('budgets.store', $this->plan), [
                'description' => 'First Ever Budget Item',
                'quantity' => 7500,
                'unit' => 'USD',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 1,
        ]);
        $response->assertJsonStructure(['html']);
        $this->assertStringContainsString('budget-item', $response->json('html'));
        $this->assertStringContainsString('First Ever Budget Item', $response->json('html'));
    }

    public function test_user_can_upload_file_attachment_and_link_to_budget_remark(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Operations Software License',
            'quantity' => 12000,
            'unit' => 'USD',
        ]);

        $uploadId = 'budget_file_'.uniqid();
        $chunk = UploadedFile::fake()->createWithContent('invoice.pdf', 'PDF invoice content for software license');

        $this->actingAs($this->executor)->post(route('comments.upload.chunk'), [
            'upload_id' => $uploadId,
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file' => $chunk,
        ])->assertStatus(200);

        $assembleRes = $this->actingAs($this->executor)->postJson(route('comments.upload.assemble'), [
            'upload_id' => $uploadId,
            'filename' => 'invoice.pdf',
            'total_chunks' => 1,
        ]);

        $assembleRes->assertStatus(200);
        $attachmentId = $assembleRes->json('attachment_id');

        $commentRes = $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'budget',
            'commentable_id' => $budget->id,
            'body' => 'Attached the vendor invoice receipt.',
            'attachment_ids' => [$attachmentId],
        ]);

        $commentRes->assertRedirect(route('plans.show', $this->plan));

        $budget->refresh();
        $this->assertCount(1, $budget->comments);
        $comment = $budget->comments->first();
        $this->assertEquals('Attached the vendor invoice receipt.', $comment->body);
        $this->assertCount(1, $comment->attachments);
        $this->assertEquals($attachmentId, $comment->attachments->first()->id);
        $this->assertEquals('invoice.pdf', $comment->attachments->first()->original_name);

        // Verify attachment download
        $attachment = $comment->attachments->first();
        $downloadRes = $this->actingAs($this->executor)->get(route('comments.attachments.download', $attachment));
        $downloadRes->assertStatus(200);
        $downloadRes->assertDownload('invoice.pdf');

        // Verify attachment file cleanup on comment deletion
        $filePath = storage_path('app/public/'.$attachment->file_path);
        $this->assertFileExists($filePath);

        $this->actingAs($this->executor)->delete(route('comments.destroy', $comment));
        $this->assertFileDoesNotExist($filePath);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_can_post_budget_remark_via_ajax_with_attachments(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Hardware Server Rack',
            'quantity' => 8000,
            'unit' => 'USD',
        ]);

        $attachment = CommentAttachment::create([
            'comment_id' => null,
            'upload_id' => 'budget_att_'.uniqid(),
            'original_name' => 'receipt.png',
            'file_path' => 'comments/attachments/test_receipt.png',
            'mime_type' => 'image/png',
            'file_size' => 1024 * 50,
        ]);

        $response = $this->actingAs($this->marshall)->postJson(route('comments.store'), [
            'commentable_type' => 'budget',
            'commentable_id' => $budget->id,
            'body' => 'Invoice for hardware acquisition confirmed.',
            'attachment_ids' => [$attachment->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'commentable_type' => 'budget',
            'commentable_id' => $budget->id,
            'comments_count' => 1,
        ]);
        $response->assertJsonPath('comment.attachments.0.id', $attachment->id);
        $response->assertJsonPath('comment.attachments.0.original_name', 'receipt.png');

        $attachment->refresh();
        $this->assertNotNull($attachment->comment_id);
    }

    public function test_budget_card_renders_chunk_upload_elements(): void
    {
        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Consulting retainer',
            'quantity' => 3000,
            'unit' => 'USD',
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $this->plan));
        $response->assertStatus(200);

        // Verify HTML markup for the remark form and file upload elements
        $response->assertSee('remark-form', false);
        $response->assertSee('name="commentable_type" value="budget"', false);
        $response->assertSee('staged-attachment-ids-container', false);
        $response->assertSee('remark-staged-container', false);
        $response->assertSee('remark-upload-progress', false);
        $response->assertSee('remark-attach-btn', false);
        $response->assertSee('remark-file-input', false);
        $response->assertSee('remarks-empty-state', false);
    }

    public function test_executor_cannot_post_budget_remark_when_plan_is_not_open(): void
    {
        $this->plan->update(['status' => 'Closed']);

        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Closed Plan Budget Item',
            'quantity' => 500,
        ]);

        $response = $this->actingAs($this->executor)->post(route('comments.store'), [
            'commentable_type' => 'budget',
            'commentable_id' => $budget->id,
            'body' => 'Attempting to remark on closed plan.',
        ]);

        $response->assertStatus(403);
    }
}

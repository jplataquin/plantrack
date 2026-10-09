<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\PlanRecord;
use App\Models\Resource;
use App\Models\TargetObjective;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NumericActualFieldTest extends TestCase
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

        $this->executor = User::factory()->create(['name' => 'Lead Executor']);
        $this->executor->assignRole($executorRole);

        $this->plan = PlanRecord::create([
            'title' => 'Numeric Actual Test Plan',
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

    public function test_target_objective_actual_accepts_valid_numeric_values(): void
    {
        $target = TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Target Alpha',
            'quantity' => '100',
            'unit' => 'units',
        ]);

        // 1. Integer update
        $res = $this->actingAs($this->executor)->patchJson(route('targets.actual.update', $target), [
            'actual' => '85',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(85, $target->fresh()->actual);
        $this->assertEquals('85 units', $target->fresh()->actual_with_unit);

        // 2. Decimal / Float update
        $res = $this->actingAs($this->executor)->patchJson(route('targets.actual.update', $target), [
            'actual' => '42.75',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(42.75, $target->fresh()->actual);
        $this->assertEquals('42.75 units', $target->fresh()->actual_with_unit);

        // 3. Zero update
        $res = $this->actingAs($this->executor)->patchJson(route('targets.actual.update', $target), [
            'actual' => '0',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(0, $target->fresh()->actual);
        $this->assertEquals('0 units', $target->fresh()->actual_with_unit);

        // 4. Empty string clears to null
        $res = $this->actingAs($this->executor)->patchJson(route('targets.actual.update', $target), [
            'actual' => '',
        ]);
        $res->assertStatus(200);
        $this->assertNull($target->fresh()->actual);
        $this->assertEquals('—', $target->fresh()->actual_with_unit);
    }

    public function test_target_objective_actual_rejects_non_numeric_via_controller(): void
    {
        $target = TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Target Beta',
            'quantity' => '100',
            'unit' => 'units',
        ]);

        $invalidValues = ['abc', 'Completed', '15 TB', 'ten', '10.5.2'];

        foreach ($invalidValues as $invalid) {
            $res = $this->actingAs($this->executor)->patchJson(route('targets.actual.update', $target), [
                'actual' => $invalid,
            ]);
            $res->assertStatus(422);
            $res->assertJsonValidationErrors('actual');
        }
    }

    public function test_target_objective_model_mutator_rejects_non_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The actual field must be numeric.');

        TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Target Gamma',
            'quantity' => '100',
            'actual' => 'not-a-number',
        ]);
    }

    public function test_resource_actual_accepts_valid_numeric_values(): void
    {
        // 1. Marshall creates resource with numeric actual
        $res = $this->actingAs($this->marshall)->post(route('resources.store', $this->plan), [
            'description' => 'Hardware Servers',
            'quantity' => '10',
            'unit' => 'racks',
            'target_date' => now()->addDays(5)->format('Y-m-d'),
            'actual' => '4',
        ]);
        $res->assertRedirect();
        $resource = Resource::where('description', 'Hardware Servers')->first();
        $this->assertNotNull($resource);
        $this->assertEquals(4, $resource->actual);
        $this->assertEquals('4 racks', $resource->actual_with_unit);

        // 2. Executor updates with float
        $res = $this->actingAs($this->executor)->patchJson(route('resources.actual.update', $resource), [
            'actual' => '6.5',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(6.5, $resource->fresh()->actual);
        $this->assertEquals('6.5 racks', $resource->fresh()->actual_with_unit);

        // 3. Executor updates with zero
        $res = $this->actingAs($this->executor)->patchJson(route('resources.actual.update', $resource), [
            'actual' => '0',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(0, $resource->fresh()->actual);
        $this->assertEquals('0 racks', $resource->fresh()->actual_with_unit);

        // 4. Executor clears actual with empty string
        $res = $this->actingAs($this->executor)->patchJson(route('resources.actual.update', $resource), [
            'actual' => '',
        ]);
        $res->assertStatus(200);
        $this->assertNull($resource->fresh()->actual);
        $this->assertEquals('—', $resource->fresh()->actual_with_unit);
    }

    public function test_resource_actual_rejects_non_numeric_via_controller(): void
    {
        // 1. Rejects non-numeric on store
        $res = $this->actingAs($this->marshall)->postJson(route('resources.store', $this->plan), [
            'description' => 'Invalid Resource',
            'quantity' => '10',
            'target_date' => now()->addDays(5)->format('Y-m-d'),
            'actual' => 'non-numeric-text',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors('actual');

        $resource = Resource::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Valid Resource',
            'quantity' => '10',
            'target_date' => now()->addDays(5),
        ]);

        // 2. Rejects non-numeric on updateActual
        $invalidValues = ['abc', 'Done', '20 Units', '12,000'];
        foreach ($invalidValues as $invalid) {
            $res = $this->actingAs($this->executor)->patchJson(route('resources.actual.update', $resource), [
                'actual' => $invalid,
            ]);
            $res->assertStatus(422);
            $res->assertJsonValidationErrors('actual');
        }
    }

    public function test_resource_model_mutator_rejects_non_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The actual field must be numeric.');

        Resource::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Test Mutator Resource',
            'quantity' => '10',
            'target_date' => now()->addDays(5),
            'actual' => 'invalid-actual',
        ]);
    }

    public function test_budget_actual_accepts_valid_numeric_values(): void
    {
        // 1. Marshall creates budget with numeric actual
        $res = $this->actingAs($this->marshall)->post(route('budgets.store', $this->plan), [
            'description' => 'Cloud Hosting',
            'quantity' => 10000,
            'unit' => 'USD',
            'actual' => '8500.50',
        ]);
        $res->assertRedirect();
        $budget = Budget::where('description', 'Cloud Hosting')->first();
        $this->assertNotNull($budget);
        $this->assertEquals(8500.5, $budget->actual);
        $this->assertEquals('8500.5 USD', $budget->actual_with_unit);

        // 2. Marshall updates actual via budget edit
        $res = $this->actingAs($this->marshall)->put(route('budgets.update', $budget), [
            'description' => 'Cloud Hosting Updated',
            'quantity' => 12000,
            'unit' => 'USD',
            'actual' => '9000',
        ]);
        $res->assertRedirect();
        $this->assertEquals(9000, $budget->fresh()->actual);

        // 3. Marshall updates actual via updateActual endpoint
        $res = $this->actingAs($this->marshall)->patchJson(route('budgets.actual.update', $budget), [
            'actual' => '9250.75',
        ]);
        $res->assertStatus(200);
        $this->assertEquals(9250.75, $budget->fresh()->actual);
        $this->assertEquals('9250.75 USD', $budget->fresh()->actual_with_unit);

        // 4. Marshall clears actual with empty string
        $res = $this->actingAs($this->marshall)->patchJson(route('budgets.actual.update', $budget), [
            'actual' => '',
        ]);
        $res->assertStatus(200);
        $this->assertNull($budget->fresh()->actual);
        $this->assertEquals('—', $budget->fresh()->actual_with_unit);
    }

    public function test_budget_actual_rejects_non_numeric_via_controller(): void
    {
        // 1. Rejects on store
        $res = $this->actingAs($this->marshall)->postJson(route('budgets.store', $this->plan), [
            'description' => 'Test Budget',
            'quantity' => 5000,
            'actual' => 'not-numeric',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors('actual');

        $budget = Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Valid Budget',
            'quantity' => '5000',
        ]);

        // 2. Rejects on update
        $res = $this->actingAs($this->marshall)->putJson(route('budgets.update', $budget), [
            'description' => 'Valid Budget',
            'quantity' => 5000,
            'actual' => 'abc',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors('actual');

        // 3. Rejects on updateActual
        $invalidValues = ['Completed', '3,500', 'five hundred', 'XYZ'];
        foreach ($invalidValues as $invalid) {
            $res = $this->actingAs($this->marshall)->patchJson(route('budgets.actual.update', $budget), [
                'actual' => $invalid,
            ]);
            $res->assertStatus(422);
            $res->assertJsonValidationErrors('actual');
        }
    }

    public function test_budget_model_mutator_rejects_non_numeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The actual field must be numeric.');

        Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'Test Mutator Budget',
            'quantity' => '5000',
            'actual' => 'letters-only',
        ]);
    }

    public function test_database_level_rejects_non_numeric_actual(): void
    {
        // Test direct database insert/update bypassing Eloquent mutators
        $tables = ['target_objectives', 'resources', 'budgets'];

        foreach ($tables as $table) {
            $failed = false;
            try {
                if ($table === 'target_objectives') {
                    DB::table($table)->insert([
                        'plan_record_id' => $this->plan->id,
                        'description' => 'DB Test',
                        'quantity' => '10',
                        'actual' => 'raw_non_numeric_string',
                    ]);
                } elseif ($table === 'resources') {
                    DB::table($table)->insert([
                        'plan_record_id' => $this->plan->id,
                        'description' => 'DB Test',
                        'quantity' => '10',
                        'target_date' => now()->toDateString(),
                        'actual' => 'raw_non_numeric_string',
                    ]);
                } elseif ($table === 'budgets') {
                    DB::table($table)->insert([
                        'plan_record_id' => $this->plan->id,
                        'description' => 'DB Test',
                        'quantity' => '1000',
                        'actual' => 'raw_non_numeric_string',
                    ]);
                }
            } catch (QueryException $e) {
                $failed = true;
            }

            $this->assertTrue($failed, "Direct SQL insert of non-numeric actual on table {$table} must fail at the database level.");
        }
    }

    public function test_blade_views_render_number_inputs_for_actual_fields(): void
    {
        TargetObjective::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'View Target',
            'quantity' => '50',
            'actual' => 25,
        ]);

        Resource::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'View Resource',
            'quantity' => '5',
            'target_date' => now()->addDays(3),
            'actual' => 3,
        ]);

        Budget::create([
            'plan_record_id' => $this->plan->id,
            'user_id' => $this->marshall->id,
            'description' => 'View Budget',
            'quantity' => '1000',
            'actual' => 800,
        ]);

        $response = $this->actingAs($this->marshall)->get(route('plans.show', $this->plan));
        $response->assertOk();

        // Check that actual inputs in modals are type="number" with step="any"
        $html = $response->getContent();
        $this->assertStringContainsString('type="number" step="any" name="actual"', $html);
        $this->assertStringNotContainsString('type="text" name="actual"', $html);
    }
}

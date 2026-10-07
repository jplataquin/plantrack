<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_three_executors_with_three_plans_each_and_all_components(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Check Admin
        $admin = User::where('email', 'admin@plantrack.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Admin'));

        // Check Marshall
        $marshall = User::where('email', 'marshall@plantrack.test')->first();
        $this->assertNotNull($marshall);
        $this->assertTrue($marshall->hasRole('Marshall'));

        // Check original Executor and the two new Executors
        $executor1 = User::where('email', 'executor@plantrack.test')->first();
        $executor2 = User::where('email', 'executor2@plantrack.test')->first();
        $executor3 = User::where('email', 'executor3@plantrack.test')->first();

        $this->assertNotNull($executor1);
        $this->assertNotNull($executor2);
        $this->assertNotNull($executor3);

        $this->assertTrue($executor1->hasRole('Executor'));
        $this->assertTrue($executor2->hasRole('Executor'));
        $this->assertTrue($executor3->hasRole('Executor'));

        $this->assertEquals('Sarah Executor', $executor2->name);
        $this->assertEquals('Marcus Executor', $executor3->name);

        // Verify the two new executors each have exactly 3 plan records
        $this->assertCount(3, $executor2->planRecords);
        $this->assertCount(3, $executor3->planRecords);

        // Verify each plan record across executors has all components
        $executors = [$executor1, $executor2, $executor3];
        foreach ($executors as $executor) {
            $plans = $executor->planRecords;
            $this->assertCount(3, $plans, "Executor {$executor->email} must have exactly 3 plan records.");

            foreach ($plans as $plan) {
                // Must have Target Objectives
                $this->assertNotEmpty($plan->targetObjectives, "Plan {$plan->id} has no target objectives.");
                $this->assertGreaterThanOrEqual(2, $plan->targetObjectives->count());

                // Must have Resources
                $this->assertNotEmpty($plan->resources, "Plan {$plan->id} has no resources.");
                $this->assertGreaterThanOrEqual(2, $plan->resources->count());

                // Must have Risk Managements
                $this->assertNotEmpty($plan->riskManagements, "Plan {$plan->id} has no risk managements.");
                $this->assertGreaterThanOrEqual(2, $plan->riskManagements->count());

                // Must have Budgets
                $this->assertNotEmpty($plan->budgets, "Plan {$plan->id} has no budgets.");
                $this->assertGreaterThanOrEqual(2, $plan->budgets->count());

                // Component weights must be present
                $this->assertEquals(60.00, $plan->target_weight);
                $this->assertEquals(20.00, $plan->resource_weight);
                $this->assertEquals(10.00, $plan->risk_weight);
                $this->assertEquals(10.00, $plan->budget_weight);

                // Project must be linked
                $this->assertNotNull($plan->project);
            }
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        // Seed twice to ensure firstOrCreate avoids integrity constraint errors
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(3, User::whereHas('roles', fn ($q) => $q->where('name', 'Executor'))->count());
        $this->assertEquals(9, PlanRecord::count());
    }

    public function test_role_and_user_seeder_contains_all_executors(): void
    {
        $this->seed(RoleAndUserSeeder::class);

        $this->assertNotNull(User::where('email', 'admin@plantrack.test')->first());
        $this->assertNotNull(User::where('email', 'executor@plantrack.test')->first());
        $this->assertNotNull(User::where('email', 'executor2@plantrack.test')->first());
        $this->assertNotNull(User::where('email', 'executor3@plantrack.test')->first());
    }
}

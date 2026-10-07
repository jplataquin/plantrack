<?php

namespace Tests\Feature;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private User $marshall;

    private User $executor1;

    private User $executor2;

    private User $executor3;

    protected function setUp(): void
    {
        parent::setUp();

        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        $this->marshall = User::factory()->create([
            'name' => 'Marshall Mary',
            'email' => 'mary@plantrack.test',
        ]);
        $this->marshall->assignRole($marshallRole);

        $this->executor1 = User::factory()->create([
            'name' => 'Executor Alpha',
            'email' => 'alpha@plantrack.test',
        ]);
        $this->executor1->assignRole($executorRole);

        $this->executor2 = User::factory()->create([
            'name' => 'Executor Beta',
            'email' => 'beta@plantrack.test',
        ]);
        $this->executor2->assignRole($executorRole);

        $this->executor3 = User::factory()->create([
            'name' => 'Executor Gamma',
            'email' => 'gamma@plantrack.test',
        ]);
        $this->executor3->assignRole($executorRole);
    }

    public function test_guest_is_redirected_to_login_when_visiting_leaderboard(): void
    {
        $response = $this->get(route('leaderboard.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_leaderboard(): void
    {
        $response = $this->actingAs($this->executor1)->get(route('leaderboard.index'));
        $response->assertStatus(200);
        $response->assertSee('OPERATIVE LEADERBOARD');
        $response->assertSee('ALL-TIME CLOSED RECORDS (DEFAULT)');
        $response->assertDontSee('CURRENT LEADER');
        $response->assertDontSee('AVERAGE SCORE');
        $response->assertDontSee('EVALUATED PLANS');
        $response->assertDontSee('FIELD EXECUTORS');
    }

    public function test_executors_leaderboard_alias_redirects_to_leaderboard(): void
    {
        $response = $this->actingAs($this->executor1)->get(route('executors.leaderboard'));
        $response->assertRedirect(route('leaderboard.index'));
    }

    public function test_leaderboard_calculates_performance_scores_from_closed_plans_by_default(): void
    {
        // Executor 1: 1 closed plan with 100% score
        $plan1 = PlanRecord::create([
            'title' => 'Alpha Closed Plan 1',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);
        $plan1->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);
        $plan1->resources()->create(['description' => 'R1', 'quantity' => '1', 'target_date' => '2026-05-15', 'status' => 'Hit']);
        $plan1->riskManagements()->create(['risk' => 'K1', 'impact' => '1', 'mitigation' => 'M', 'status' => 'Hit']);
        $plan1->budgets()->create(['description' => 'B1', 'quantity' => '100', 'status' => 'Hit']);

        // Executor 2: 1 closed plan with 0% score (all missed)
        $plan2 = PlanRecord::create([
            'title' => 'Beta Closed Plan 1',
            'executor_id' => $this->executor2->id,
            'status' => 'Close',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);
        $plan2->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Missed']);
        $plan2->resources()->create(['description' => 'R2', 'quantity' => '1', 'target_date' => '2026-06-15', 'status' => 'Missed']);
        $plan2->riskManagements()->create(['risk' => 'K2', 'impact' => '1', 'mitigation' => 'M', 'status' => 'Missed']);
        $plan2->budgets()->create(['description' => 'B2', 'quantity' => '100', 'status' => 'Missed']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Executor Alpha');
        $response->assertSee('Executor Beta');
        $response->assertSee('100.0%');
        $response->assertSee('0.0%');

        // Confirm list of plan records is removed from under the executor row
        $response->assertDontSee('Alpha Closed Plan 1');
        $response->assertDontSee('Beta Closed Plan 1');
        $response->assertDontSee('Qualifying Closed Plans');

        // Verify calculation used the correct plans
        $leaderboard = $response->viewData('leaderboard');
        $this->assertEquals(100.0, $leaderboard->firstWhere('executor.id', $this->executor1->id)['average_score']);
        $this->assertEquals(0.0, $leaderboard->firstWhere('executor.id', $this->executor2->id)['average_score']);
    }

    public function test_leaderboard_excludes_open_review_and_void_plans(): void
    {
        // Executor 1 has an Open plan with 100% score (should be ignored)
        $openPlan = PlanRecord::create([
            'title' => 'Open Plan Not Included',
            'executor_id' => $this->executor1->id,
            'status' => 'Open',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);
        $openPlan->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);

        // Executor 1 has a Review plan (should be ignored)
        $reviewPlan = PlanRecord::create([
            'title' => 'Review Plan Not Included',
            'executor_id' => $this->executor1->id,
            'status' => 'Review',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);
        $reviewPlan->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Hit']);

        // Executor 1 has a Void plan (should be ignored)
        $voidPlan = PlanRecord::create([
            'title' => 'Void Plan Not Included',
            'executor_id' => $this->executor1->id,
            'status' => 'Void',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);
        $voidPlan->targetObjectives()->create(['description' => 'T3', 'quantity' => '1', 'status' => 'Hit']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $leaderboard = $response->viewData('leaderboard');
        $this->assertEquals(0, $leaderboard->firstWhere('executor.id', $this->executor1->id)['closed_plans_count']);
    }

    public function test_leaderboard_filters_by_from_date_inclusive(): void
    {
        // Plan before From date (start_date: 2026-04-15) -> excluded
        $oldPlan = PlanRecord::create([
            'title' => 'Old Closed Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-04-15',
            'end_date' => '2026-05-15',
        ]);
        $oldPlan->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);

        // Plan on/after From date (start_date: 2026-05-01) -> included
        $newPlan = PlanRecord::create([
            'title' => 'Valid Closed Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-20',
        ]);
        $newPlan->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Hit']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index', [
            'from' => '2026-05-01',
        ]));

        $response->assertStatus(200);
        $leaderboard = $response->viewData('leaderboard');
        $plans = $leaderboard->firstWhere('executor.id', $this->executor1->id)['plans'];
        $this->assertTrue($plans->contains('id', $newPlan->id));
        $this->assertFalse($plans->contains('id', $oldPlan->id));
        $this->assertEquals(1, $leaderboard->firstWhere('executor.id', $this->executor1->id)['closed_plans_count']);
    }

    public function test_leaderboard_filters_by_to_date_inclusive(): void
    {
        // Plan ending within To date (end_date: 2026-05-20) -> included
        $planInside = PlanRecord::create([
            'title' => 'Plan Inside To Date',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-20',
        ]);
        $planInside->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);

        // Plan ending after To date (end_date: 2026-06-15) -> excluded
        $planOutside = PlanRecord::create([
            'title' => 'Plan Outside To Date',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-01',
            'end_date' => '2026-06-15',
        ]);
        $planOutside->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Hit']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index', [
            'to' => '2026-05-31',
        ]));

        $response->assertStatus(200);
        $leaderboard = $response->viewData('leaderboard');
        $plans = $leaderboard->firstWhere('executor.id', $this->executor1->id)['plans'];
        $this->assertTrue($plans->contains('id', $planInside->id));
        $this->assertFalse($plans->contains('id', $planOutside->id));
        $this->assertEquals(1, $leaderboard->firstWhere('executor.id', $this->executor1->id)['closed_plans_count']);
    }

    public function test_leaderboard_filters_by_both_from_and_to_dates(): void
    {
        // Qualifying Plan (start: 2026-06-01, end: 2026-06-25)
        $validPlan = PlanRecord::create([
            'title' => 'Range Match Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-25',
        ]);
        $validPlan->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);

        // Start date too early (start: 2026-05-28, end: 2026-06-20)
        $earlyPlan = PlanRecord::create([
            'title' => 'Start Too Early Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-28',
            'end_date' => '2026-06-20',
        ]);
        $earlyPlan->targetObjectives()->create(['description' => 'T2', 'quantity' => '1', 'status' => 'Hit']);

        // End date too late (start: 2026-06-05, end: 2026-07-02)
        $latePlan = PlanRecord::create([
            'title' => 'End Too Late Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-06-05',
            'end_date' => '2026-07-02',
        ]);
        $latePlan->targetObjectives()->create(['description' => 'T3', 'quantity' => '1', 'status' => 'Hit']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index', [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ]));

        $response->assertStatus(200);
        $leaderboard = $response->viewData('leaderboard');
        $plans = $leaderboard->firstWhere('executor.id', $this->executor1->id)['plans'];
        $this->assertTrue($plans->contains('id', $validPlan->id));
        $this->assertFalse($plans->contains('id', $earlyPlan->id));
        $this->assertFalse($plans->contains('id', $latePlan->id));
        $this->assertEquals(1, $leaderboard->firstWhere('executor.id', $this->executor1->id)['closed_plans_count']);
    }

    public function test_leaderboard_ranks_executors_by_average_score_descending(): void
    {
        // Executor 1 has 90% score
        $planA = PlanRecord::create([
            'title' => 'Score 90 Plan',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-30',
            'target_weight' => 100,
            'resource_weight' => 0,
            'risk_weight' => 0,
            'budget_weight' => 0,
        ]);
        // 9 hit, 1 missed = 90%
        for ($i = 0; $i < 9; $i++) {
            $planA->targetObjectives()->create(['description' => "Hit $i", 'quantity' => '1', 'status' => 'Hit']);
        }
        $planA->targetObjectives()->create(['description' => 'Missed 1', 'quantity' => '1', 'status' => 'Missed']);

        // Executor 2 has 60% score
        $planB = PlanRecord::create([
            'title' => 'Score 60 Plan',
            'executor_id' => $this->executor2->id,
            'status' => 'Close',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-30',
            'target_weight' => 100,
            'resource_weight' => 0,
            'risk_weight' => 0,
            'budget_weight' => 0,
        ]);
        // 6 hit, 4 missed = 60%
        for ($i = 0; $i < 6; $i++) {
            $planB->targetObjectives()->create(['description' => "Hit $i", 'quantity' => '1', 'status' => 'Hit']);
        }
        for ($i = 0; $i < 4; $i++) {
            $planB->targetObjectives()->create(['description' => "Missed $i", 'quantity' => '1', 'status' => 'Missed']);
        }

        // Executor 3 has 0 closed plans (0.0%)

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index'));

        $response->assertStatus(200);

        // Verify ranked leaderboard collection
        $leaderboard = $response->viewData('leaderboard');
        $this->assertEquals('Executor Alpha', $leaderboard[0]['executor']->name);
        $this->assertEquals(90.0, $leaderboard[0]['average_score']);
        $this->assertEquals(1, $leaderboard[0]['rank']);

        $this->assertEquals('Executor Beta', $leaderboard[1]['executor']->name);
        $this->assertEquals(60.0, $leaderboard[1]['average_score']);
        $this->assertEquals(2, $leaderboard[1]['rank']);

        $this->assertEquals('Executor Gamma', $leaderboard[2]['executor']->name);
        $this->assertEquals(0.0, $leaderboard[2]['average_score']);
        $this->assertEquals(3, $leaderboard[2]['rank']);

        // Check within table rows in rendered HTML
        $content = $response->getContent();
        $tableContent = substr($content, strpos($content, '<tbody'));
        $posAlpha = strpos($tableContent, 'Executor Alpha');
        $posBeta = strpos($tableContent, 'Executor Beta');
        $posGamma = strpos($tableContent, 'Executor Gamma');

        $this->assertNotFalse($posAlpha);
        $this->assertNotFalse($posBeta);
        $this->assertNotFalse($posGamma);

        // Alpha appears before Beta in the rendered table
        $this->assertLessThan($posBeta, $posAlpha);
        // Beta appears before Gamma in the rendered table
        $this->assertLessThan($posGamma, $posBeta);
    }

    public function test_leaderboard_links_to_executor_profile(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index'));
        $response->assertStatus(200);
        $response->assertSee(route('profile.show', $this->executor1));
        $response->assertSee(route('profile.show', $this->executor2));
    }

    public function test_leaderboard_rejects_invalid_date_format(): void
    {
        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index', [
            'from' => 'not-a-date',
        ]));

        $response->assertSessionHasErrors(['from']);
    }

    public function test_leaderboard_action_buttons_have_same_width_and_no_plan_list_under_executor_row(): void
    {
        $plan = PlanRecord::create([
            'title' => 'Test Closed Plan Alpha',
            'executor_id' => $this->executor1->id,
            'status' => 'Close',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-30',
        ]);
        $plan->targetObjectives()->create(['description' => 'T1', 'quantity' => '1', 'status' => 'Hit']);

        $response = $this->actingAs($this->marshall)->get(route('leaderboard.index'));

        $response->assertStatus(200);

        // Action buttons have matching explicit width style
        $response->assertSee('style="width: 85px;"', false);

        // Confirm plan title and sub-row indicator are absent under executor row
        $response->assertDontSee('Test Closed Plan Alpha');
        $response->assertDontSee('Qualifying Closed Plans');
    }
}

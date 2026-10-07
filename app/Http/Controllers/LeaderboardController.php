<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Display the executors performance leaderboard.
     */
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        // Base query: all Plan records with status "Close"
        $closedPlansQuery = PlanRecord::where('status', 'Close')
            ->with(['executor', 'project', 'targetObjectives', 'resources', 'riskManagements', 'budgets']);

        // Date filter: Start Date >= from
        if (! empty($from)) {
            $closedPlansQuery->whereDate('start_date', '>=', $from);
        }

        // Date filter: End Date <= to
        if (! empty($to)) {
            $closedPlansQuery->whereDate('end_date', '<=', $to);
        }

        $closedPlans = $closedPlansQuery->get();

        // Retrieve all executors
        $executors = User::whereHas('roles', function ($q) {
            $q->where('name', 'Executor');
        })->get();

        // If there are closed plans belonging to users without executor role, also include those users
        $planExecutorIds = $closedPlans->pluck('executor_id')->filter()->unique();
        if ($planExecutorIds->isNotEmpty()) {
            $missingExecutors = User::whereIn('id', $planExecutorIds)
                ->whereNotIn('id', $executors->pluck('id'))
                ->get();
            $executors = $executors->concat($missingExecutors);
        }

        // Calculate performance for each executor
        $leaderboardData = $executors->map(function (User $executor) use ($closedPlans) {
            $matchingPlans = $closedPlans->where('executor_id', $executor->id);
            $closedCount = $matchingPlans->count();

            $scores = $matchingPlans->map(fn (PlanRecord $plan) => (float) $plan->evaluation_score);
            $averageScore = $closedCount > 0 ? round($scores->avg(), 2) : 0.0;
            $highestScore = $closedCount > 0 ? $scores->max() : 0.0;
            $lowestScore = $closedCount > 0 ? $scores->min() : 0.0;

            return [
                'executor' => $executor,
                'closed_plans_count' => $closedCount,
                'average_score' => $averageScore,
                'highest_score' => $highestScore,
                'lowest_score' => $lowestScore,
                'plans' => $matchingPlans->values(),
            ];
        });

        // Sort leaderboard: average score desc, closed plans count desc, name asc
        $sortedLeaderboard = $leaderboardData->sort(function ($a, $b) {
            if ($b['average_score'] !== $a['average_score']) {
                return $b['average_score'] <=> $a['average_score'];
            }
            if ($b['closed_plans_count'] !== $a['closed_plans_count']) {
                return $b['closed_plans_count'] <=> $a['closed_plans_count'];
            }

            return strcmp($a['executor']->name, $b['executor']->name);
        })->values();

        // Assign ranking numbers
        $rankedLeaderboard = $sortedLeaderboard->map(function ($item, $index) {
            $item['rank'] = $index + 1;

            return $item;
        });

        return view('leaderboard.index', [
            'leaderboard' => $rankedLeaderboard,
            'from' => $from,
            'to' => $to,
        ]);
    }
}

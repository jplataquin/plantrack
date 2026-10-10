<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isMarshall = $user->hasAnyRole(['Marshall', 'Admin']);
        $isExecutor = $user->hasRole('Executor');

        $query = PlanRecord::with([
            'executor',
            'project',
            'targetObjectives',
            'resources',
            'riskManagements',
            'budgets',
        ])->orderBy('start_date', 'desc')->latest('id');

        // Filter by Project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by Executor
        if ($request->filled('executor_id')) {
            $query->where('executor_id', $request->executor_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by Title or Description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $plans = $query->paginate(15)->withQueryString();

        // Populate filter dropdown choices
        $projects = Project::orderBy('name')->get();
        $executors = User::whereHas('roles', function ($q) {
            $q->where('name', 'Executor');
        })->orWhereHas('planRecords')->orderBy('name')->get();

        $stats = [
            'total_plans' => PlanRecord::count(),
            'open_plans' => PlanRecord::where('status', 'Open')->count(),
            'review_plans' => PlanRecord::where('status', 'Review')->count(),
            'close_plans' => PlanRecord::where('status', 'Close')->count(),
            'void_plans' => PlanRecord::where('status', 'Void')->count(),
        ];

        return view('plans.index', compact('plans', 'projects', 'executors', 'stats', 'isMarshall', 'isExecutor'));
    }

    /**
     * Display all plan records of the selected executor.
     */
    public function showExecutor(Request $request, User $executor)
    {
        $user = Auth::user();
        $isMarshall = $user->hasAnyRole(['Marshall', 'Admin']);
        $isExecutor = $user->hasRole('Executor');

        $query = $executor->planRecords()
            ->with(['targetObjectives', 'resources', 'riskManagements', 'budgets'])
            ->orderBy('start_date', 'desc')->latest('id');

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->paginate(9)->withQueryString();

        // Calculate aggregate component metrics across all executor's plans
        $allExecutorPlans = $executor->planRecords()
            ->with(['targetObjectives', 'resources', 'riskManagements', 'budgets'])
            ->get();

        $allComponents = collect();
        foreach ($allExecutorPlans as $p) {
            $allComponents = $allComponents
                ->merge($p->targetObjectives)
                ->merge($p->resources)
                ->merge($p->riskManagements)
                ->merge($p->budgets);
        }

        $metrics = [
            'total_plans' => $allExecutorPlans->count(),
            'open_plans' => $allExecutorPlans->where('status', 'Open')->count(),
            'review_plans' => $allExecutorPlans->where('status', 'Review')->count(),
            'close_plans' => $allExecutorPlans->where('status', 'Close')->count(),
            'void_plans' => $allExecutorPlans->where('status', 'Void')->count(),
            'hit_components' => $allComponents->where('status', 'Hit')->count(),
            'missed_components' => $allComponents->where('status', 'Missed')->count(),
            'void_components' => $allComponents->where('status', 'Void')->count(),
            'pending_components' => $allComponents->whereNull('status')->count(),
        ];

        return view('executors.show', compact('executor', 'plans', 'metrics', 'isMarshall', 'isExecutor'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $executors = User::role('Executor')->get();
        // If current user is not in executors list but has role, ensure they are selectable
        if ($executors->isEmpty()) {
            $executors = User::all();
        }

        $activeProjects = Project::active()->orderBy('name')->get();

        return view('plans.create', compact('executors', 'activeProjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'executor_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];

        if (Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            $rules['project_id'] = [
                'nullable',
                Rule::exists('projects', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')->where('status', 'Active');
                }),
            ];

            if ($request->filled('target_weight') || $request->filled('resource_weight') || $request->filled('risk_weight') || $request->filled('budget_weight')) {
                $rules['target_weight'] = 'required|numeric|min:0|max:100';
                $rules['resource_weight'] = 'required|numeric|min:0|max:100';
                $rules['risk_weight'] = 'required|numeric|min:0|max:100';
                $rules['budget_weight'] = 'required|numeric|min:0|max:100';
            }
        }

        $validated = $request->validate($rules);

        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            $validated['project_id'] = null;
            $validated['target_weight'] = 60.00;
            $validated['resource_weight'] = 20.00;
            $validated['risk_weight'] = 10.00;
            $validated['budget_weight'] = 10.00;
        } else {
            if (isset($validated['target_weight'])) {
                $sum = round((float) $validated['target_weight'] + (float) $validated['resource_weight'] + (float) $validated['risk_weight'] + (float) $validated['budget_weight'], 2);
                if (abs($sum - 100.0) > 0.01) {
                    throw ValidationException::withMessages([
                        'weights' => 'The total sum of evaluation weights must equal 100%. (Current total: '.$sum.'%)',
                    ]);
                }
            } else {
                $validated['target_weight'] = 60.00;
                $validated['resource_weight'] = 20.00;
                $validated['risk_weight'] = 10.00;
                $validated['budget_weight'] = 10.00;
            }
        }

        $validated['status'] = 'Open';

        $plan = PlanRecord::create($validated);

        return redirect()->route('plans.show', $plan)->with('success', 'Plan Record created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanRecord $plan)
    {
        $plan->load([
            'project',
            'executor',
            'targetObjectives.user',
            'targetObjectives.comments.user',
            'targetObjectives.comments.attachments',
            'resources.user',
            'resources.targetObjective',
            'resources.comments.user',
            'resources.comments.attachments',
            'riskManagements.user',
            'riskManagements.comments.user',
            'riskManagements.comments.attachments',
            'budgets.user',
            'budgets.targetObjective',
            'budgets.comments.user',
            'budgets.comments.attachments',
        ]);

        $executors = User::role('Executor')->get();
        $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);
        $isExecutor = Auth::user()->hasRole('Executor');
        $isAssignedExecutor = Auth::id() === $plan->executor_id;

        return view('plans.show', compact('plan', 'executors', 'isMarshall', 'isExecutor', 'isAssignedExecutor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PlanRecord $plan)
    {
        $executors = User::role('Executor')->get();
        if ($executors->isEmpty()) {
            $executors = User::all();
        }

        $activeProjects = Project::active()->orderBy('name')->get();

        if ($plan->project && ! $activeProjects->contains('id', $plan->project_id)) {
            $activeProjects->prepend($plan->project);
        }

        return view('plans.edit', compact('plan', 'executors', 'activeProjects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanRecord $plan)
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'executor_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];

        // Only Marshalls and Admins can edit/change status, project, and weights in the plan form
        if (Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            $rules['status'] = 'required|in:Open,Review,Close,Void';
            $rules['project_id'] = [
                'nullable',
                Rule::exists('projects', 'id')->where(function ($query) use ($plan) {
                    $query->whereNull('deleted_at')
                        ->where(function ($q) use ($plan) {
                            $q->where('status', 'Active')
                                ->orWhere('id', $plan->project_id);
                        });
                }),
            ];

            if ($request->filled('target_weight') || $request->filled('resource_weight') || $request->filled('risk_weight') || $request->filled('budget_weight')) {
                $rules['target_weight'] = 'required|numeric|min:0|max:100';
                $rules['resource_weight'] = 'required|numeric|min:0|max:100';
                $rules['risk_weight'] = 'required|numeric|min:0|max:100';
                $rules['budget_weight'] = 'required|numeric|min:0|max:100';
            }
        }

        $validated = $request->validate($rules);

        if (! $request->filled('title')) {
            $validated['title'] = $plan->getRawOriginal('title') ?: $plan->title;
        }

        // If an executor submits the update, preserve the existing status, project, and weights
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            $validated['status'] = $plan->status;
            $validated['project_id'] = $plan->project_id;
            unset($validated['target_weight'], $validated['resource_weight'], $validated['risk_weight'], $validated['budget_weight']);
        } else {
            if (isset($validated['status']) && $validated['status'] === 'Close' && $plan->hasPendingComponents()) {
                $count = $plan->getPendingComponentsCount();
                $details = $plan->getPendingComponentsDescription();
                throw ValidationException::withMessages([
                    'status' => "Cannot close plan record: {$count} component(s) are not evaluated (Pending): {$details}. All components must be evaluated before closing.",
                ]);
            }

            if (isset($validated['target_weight'])) {
                $sum = round((float) $validated['target_weight'] + (float) $validated['resource_weight'] + (float) $validated['risk_weight'] + (float) $validated['budget_weight'], 2);
                if (abs($sum - 100.0) > 0.01) {
                    throw ValidationException::withMessages([
                        'weights' => 'The total sum of evaluation weights must equal 100%. (Current total: '.$sum.'%)',
                    ]);
                }
            }
        }

        $plan->update($validated);

        return redirect()->route('plans.show', $plan)->with('success', 'Plan Record updated successfully.');
    }

    /**
     * Update the evaluation weights of the plan record.
     */
    public function updateWeights(Request $request, PlanRecord $plan)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can modify evaluation weights.');
        }

        $validated = $request->validate([
            'target_weight' => 'required|numeric|min:0|max:100',
            'resource_weight' => 'required|numeric|min:0|max:100',
            'risk_weight' => 'required|numeric|min:0|max:100',
            'budget_weight' => 'required|numeric|min:0|max:100',
        ]);

        $sum = round((float) $validated['target_weight'] + (float) $validated['resource_weight'] + (float) $validated['risk_weight'] + (float) $validated['budget_weight'], 2);
        if (abs($sum - 100.0) > 0.01) {
            throw ValidationException::withMessages([
                'weights' => 'The total sum of evaluation weights must equal 100%. (Current total: '.$sum.'%)',
            ]);
        }

        $plan->update([
            'target_weight' => $validated['target_weight'],
            'resource_weight' => $validated['resource_weight'],
            'risk_weight' => $validated['risk_weight'],
            'budget_weight' => $validated['budget_weight'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Evaluation weights updated successfully.',
                'target_weight' => (float) $plan->target_weight,
                'resource_weight' => (float) $plan->resource_weight,
                'risk_weight' => (float) $plan->risk_weight,
                'budget_weight' => (float) $plan->budget_weight,
                'evaluation_score' => $plan->evaluation_score,
                'target_score' => $plan->target_score,
                'resource_score' => $plan->resource_score,
                'risk_score' => $plan->risk_score,
                'budget_score' => $plan->budget_score,
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Evaluation weights updated successfully.');
    }

    /**
     * Update the overall status of the plan record.
     */
    public function updateStatus(Request $request, PlanRecord $plan)
    {
        // Only Marshalls and Admins can update the overall plan status
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can update the plan record status.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Open,Review,Close,Void',
        ]);

        if ($validated['status'] === 'Close' && $plan->hasPendingComponents()) {
            $count = $plan->getPendingComponentsCount();
            $details = $plan->getPendingComponentsDescription();
            $message = "Cannot close plan record: {$count} component(s) are not evaluated (Pending): {$details}. All components must be evaluated before closing.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'pending_count' => $count,
                    'current_status' => $plan->status,
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['status' => $message])
                ->with('error', $message);
        }

        $plan->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plan status updated to '.$validated['status'],
                'status' => $validated['status'],
                'badge_class' => $plan->status_badge_class,
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Plan status updated to '.$validated['status'].'.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanRecord $plan)
    {
        $plan->delete();

        return redirect()->route('executors.index')->with('success', 'Plan Record deleted successfully.');
    }
}

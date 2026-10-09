<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\PlanRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created budget item in storage.
     */
    public function store(Request $request, PlanRecord $plan)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can create Budget components.');
        }

        $targetObjectiveId = $request->input('for', $request->input('target_objective_id'));
        if ($targetObjectiveId !== null) {
            $request->merge([
                'target_objective_id' => $targetObjectiveId,
                'for' => $targetObjectiveId,
            ]);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|numeric|min:0',
            'actual' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'for' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_objective_id' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'status' => 'nullable|in:Hit,Missed,Void',
        ], [
            'for.exists' => 'The selected target objective must belong to this plan.',
            'target_objective_id.exists' => 'The selected target objective must belong to this plan.',
        ]);

        $validated['target_objective_id'] = $targetObjectiveId ?: null;
        unset($validated['for']);

        if (! $request->filled('actual')) {
            $validated['actual'] = null;
        }

        if (! $request->filled('status')) {
            $validated['status'] = null;
        }

        $validated['user_id'] = Auth::id();

        $budget = $plan->budgets()->create($validated);

        if ($request->wantsJson()) {
            $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);
            $isExecutor = Auth::user()->hasRole('Executor');
            $isAssignedExecutor = Auth::id() === $plan->executor_id;

            $html = view('plans.partials.budget-card', [
                'budget' => $budget,
                'plan' => $plan,
                'isMarshall' => $isMarshall,
                'isExecutor' => $isExecutor,
                'isAssignedExecutor' => $isAssignedExecutor,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Budget item added successfully.',
                'budget' => $budget,
                'html' => $html,
                'count' => $plan->budgets()->count(),
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Budget item added successfully.');
    }

    /**
     * Update the specified budget item in storage.
     */
    public function update(Request $request, Budget $budget)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can edit Budget components.');
        }

        $plan = $budget->planRecord;

        $targetObjectiveId = $request->input('for', $request->input('target_objective_id'));
        if ($targetObjectiveId !== null) {
            $request->merge([
                'target_objective_id' => $targetObjectiveId,
                'for' => $targetObjectiveId,
            ]);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|numeric|min:0',
            'actual' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'for' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_objective_id' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'status' => 'nullable|in:Hit,Missed,Void',
        ], [
            'for.exists' => 'The selected target objective must belong to this plan.',
            'target_objective_id.exists' => 'The selected target objective must belong to this plan.',
        ]);

        $validated['target_objective_id'] = $targetObjectiveId ?: null;
        unset($validated['for']);

        if (! $request->filled('actual')) {
            $validated['actual'] = null;
        }

        $budget->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Budget item updated successfully.',
                'budget' => $budget,
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Budget item updated successfully.');
    }

    /**
     * Update the actual accomplished value of the budget item.
     */
    public function updateActual(Request $request, Budget $budget)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can update the actual value of a Budget component.');
        }

        $validated = $request->validate([
            'actual' => 'nullable|numeric',
        ]);

        $budget->update([
            'actual' => ($validated['actual'] !== '' && $validated['actual'] !== null) ? $validated['actual'] : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Actual value updated successfully.',
                'actual' => $budget->actual_with_unit,
                'raw_actual' => $budget->actual,
            ]);
        }

        return redirect()->route('plans.show', $budget->planRecord)->with('success', 'Actual value updated successfully.');
    }

    /**
     * Update the evaluation status of the budget item via AJAX or form.
     */
    public function updateStatus(Request $request, Budget $budget)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can update the status of a Budget component.');
        }

        $validated = $request->validate([
            'status' => 'nullable|in:Hit,Missed,Void,Pending',
        ]);

        $status = $validated['status'] === 'Pending' ? null : $validated['status'];
        $budget->update(['status' => $status]);

        $plan = $budget->planRecord;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'status' => $budget->status ?? 'Unevaluated',
                'badge_class' => $budget->status_badge_class,
                'budget_score' => $plan->budget_score,
                'evaluation_score' => $plan->evaluation_score,
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Status updated successfully.');
    }

    /**
     * Remove the specified budget item from storage.
     */
    public function destroy(Request $request, Budget $budget)
    {
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can delete Budget components.');
        }

        $plan = $budget->planRecord;
        $budget->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Budget item removed successfully.',
                'count' => $plan->budgets()->count(),
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Budget item removed successfully.');
    }
}

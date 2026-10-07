<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\TargetObjective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TargetObjectiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, PlanRecord $plan)
    {
        // Only the Marshall or Admin can create target/objectives
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can create target objectives.');
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|string|max:100',
            'unit' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:low,normal,high,critical,Low,Normal,High,Critical',
        ]);

        $validated['priority'] = strtolower($validated['priority'] ?? 'low');
        $validated['actual'] = null; // Actual is not fillable during creation
        $validated['status'] = null; // Status is always pending by default upon creation
        $validated['user_id'] = Auth::id();

        $target = $plan->targetObjectives()->create($validated);

        if ($request->wantsJson()) {
            $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);
            $isExecutor = Auth::user()->hasRole('Executor');
            $isAssignedExecutor = Auth::id() === $plan->executor_id;

            $html = view('plans.partials.target-card', compact('target', 'plan', 'isMarshall', 'isExecutor', 'isAssignedExecutor'))->render();

            return response()->json([
                'success' => true,
                'message' => 'Target / Objective added successfully.',
                'target' => $target,
                'html' => $html,
                'count' => $plan->targetObjectives()->count(),
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Target / Objective added successfully.');
    }

    public function update(Request $request, TargetObjective $targetObjective)
    {
        // Only the Marshall or Admin can edit target/objectives
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can edit target objectives.');
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|string|max:100',
            'unit' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:low,normal,high,critical,Low,Normal,High,Critical',
        ]);

        if (isset($validated['priority'])) {
            $validated['priority'] = strtolower($validated['priority']);
        }

        $targetObjective->update($validated);

        return redirect()->route('plans.show', $targetObjective->plan_record_id)->with('success', 'Target / Objective updated successfully.');
    }

    public function updateActual(Request $request, TargetObjective $targetObjective)
    {
        $user = Auth::user();
        $plan = $targetObjective->planRecord;

        // The Executor (or Marshall / Admin) can fill the Actual Accomplished field
        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($plan->executor_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot input data when the plan record is not Open.');
            }
        }

        $rules = [
            'actual' => 'nullable|string|max:100',
        ];

        if ($user->hasAnyRole(['Marshall', 'Admin'])) {
            $rules['unit'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        $targetObjective->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Actual accomplished quantity updated.',
                'actual' => $targetObjective->actual,
                'unit' => $targetObjective->unit,
                'actual_with_unit' => $targetObjective->actual_with_unit,
                'quantity_with_unit' => $targetObjective->quantity_with_unit,
            ]);
        }

        return redirect()->route('plans.show', $targetObjective->plan_record_id)->with('success', 'Actual accomplished quantity updated.');
    }

    public function updatePriority(Request $request, TargetObjective $targetObjective)
    {
        // Only the Marshall or Admin can edit target/objectives
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can edit target objectives.');
        }

        $validated = $request->validate([
            'priority' => 'required|in:low,normal,high,critical,Low,Normal,High,Critical',
        ]);

        $priority = strtolower($validated['priority']);
        $targetObjective->update(['priority' => $priority]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Target / Objective priority updated to '.$priority,
                'priority' => $priority,
                'badge_class' => $targetObjective->priority_badge_class,
            ]);
        }

        return redirect()->route('plans.show', $targetObjective->plan_record_id)->with('success', 'Target / Objective priority updated to '.$priority.'.');
    }

    public function updateStatus(Request $request, TargetObjective $targetObjective)
    {
        // Only the Marshall or Admin can evaluate target objective statuses
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can evaluate target objective statuses.');
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:Hit,Missed,Void,Pending,',
        ]);

        $status = in_array($validated['status'] ?? null, ['Hit', 'Missed', 'Void'])
            ? $validated['status']
            : null;

        $targetObjective->update(['status' => $status]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Target / Objective status updated to '.($status ?: 'Pending'),
                'status' => $status ?: 'Unevaluated',
                'badge_class' => $targetObjective->status_badge_class,
            ]);
        }

        return redirect()->route('plans.show', $targetObjective->plan_record_id)->with('success', 'Target / Objective status updated to '.($status ?: 'Pending').'.');
    }

    public function destroy(TargetObjective $targetObjective)
    {
        // Only the Marshall or Admin can delete target/objectives
        if (! Auth::user()->hasAnyRole(['Marshall', 'Admin'])) {
            abort(403, 'Only Marshalls and Admins can delete target objectives.');
        }

        $planId = $targetObjective->plan_record_id;
        $targetObjective->delete();

        return redirect()->route('plans.show', $planId)->with('success', 'Target / Objective removed successfully.');
    }
}

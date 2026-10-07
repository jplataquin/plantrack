<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\RiskManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, PlanRecord $plan)
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($plan->executor_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot add risk items when the plan record is not Open.');
            }
        }

        $validated = $request->validate([
            'risk' => 'required|string',
            'impact' => 'required|string',
            'mitigation' => 'required|string',
        ]);

        // When creating a risk item, default is always pending (null)
        $validated['status'] = null;
        $validated['user_id'] = $user->id;

        $risk = $plan->riskManagements()->create($validated);

        if ($request->wantsJson()) {
            $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);
            $isExecutor = Auth::user()->hasRole('Executor');
            $isAssignedExecutor = Auth::id() === $plan->executor_id;

            $html = view('plans.partials.risk-card', [
                'risk' => $risk,
                'plan' => $plan,
                'isMarshall' => $isMarshall,
                'isExecutor' => $isExecutor,
                'isAssignedExecutor' => $isAssignedExecutor,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Risk Management item added successfully.',
                'risk' => $risk,
                'html' => $html,
                'count' => $plan->riskManagements()->count(),
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Risk Management item added successfully.');
    }

    public function update(Request $request, RiskManagement $riskManagement)
    {
        $user = Auth::user();
        $plan = $riskManagement->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($riskManagement->isMarshallMade()) {
                abort(403, 'Executors cannot edit Marshall-made risk items.');
            }
            if ($plan->executor_id !== $user->id || $riskManagement->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot edit risk items when the plan record is not Open.');
            }
        }

        $validated = $request->validate([
            'risk' => 'required|string',
            'impact' => 'required|string',
            'mitigation' => 'required|string',
        ]);

        $riskManagement->update($validated);

        return redirect()->route('plans.show', $plan->id)->with('success', 'Risk Management item updated successfully.');
    }

    public function updateStatus(Request $request, RiskManagement $riskManagement)
    {
        $user = Auth::user();
        $plan = $riskManagement->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($riskManagement->isMarshallMade()) {
                abort(403, 'Executors cannot update the evaluation status of Marshall-made risk items.');
            }
            if ($plan->executor_id !== $user->id || $riskManagement->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot update status when the plan record is not Open.');
            }
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:Hit,Missed,Void,Pending,',
        ]);

        $status = in_array($validated['status'] ?? null, ['Hit', 'Missed', 'Void'])
            ? $validated['status']
            : null;

        $riskManagement->update(['status' => $status]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Risk item status updated to '.($status ?: 'Pending'),
                'status' => $status ?: 'Unevaluated',
                'badge_class' => $riskManagement->status_badge_class,
            ]);
        }

        return redirect()->route('plans.show', $riskManagement->plan_record_id)->with('success', 'Risk item status updated to '.($status ?: 'Pending').'.');
    }

    public function destroy(RiskManagement $riskManagement)
    {
        $user = Auth::user();
        $plan = $riskManagement->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($riskManagement->isMarshallMade()) {
                abort(403, 'Executors cannot delete Marshall-made risk items.');
            }
            if ($plan->executor_id !== $user->id || $riskManagement->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot delete risk items when the plan record is not Open.');
            }
        }

        $planId = $riskManagement->plan_record_id;
        $riskManagement->delete();

        return redirect()->route('plans.show', $planId)->with('success', 'Risk item removed successfully.');
    }
}

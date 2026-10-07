<?php

namespace App\Http\Controllers;

use App\Models\PlanRecord;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
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
                abort(403, 'Executors cannot add resources when the plan record is not Open.');
            }
        }

        $startDate = $plan->start_date->toDateString();
        $endDate = $plan->end_date->toDateString();

        $targetObjectiveId = $request->input('for', $request->input('target_objective_id'));
        if ($targetObjectiveId !== null) {
            $request->merge([
                'target_objective_id' => $targetObjectiveId,
                'for' => $targetObjectiveId,
            ]);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|string|max:100',
            'actual' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'for' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_objective_id' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_date' => 'required|date|after_or_equal:'.$startDate.'|before_or_equal:'.$endDate,
            'status' => 'nullable|in:Hit,Missed,Void',
        ], [
            'target_date.after_or_equal' => 'The target date must be within the plan schedule (on or after '.$plan->start_date->format('M d, Y').').',
            'target_date.before_or_equal' => 'The target date must be within the plan schedule (on or before '.$plan->end_date->format('M d, Y').').',
            'for.exists' => 'The selected target objective must belong to this plan.',
            'target_objective_id.exists' => 'The selected target objective must belong to this plan.',
        ]);

        $validated['target_objective_id'] = $targetObjectiveId ?: null;
        unset($validated['for']);

        if (! $request->filled('actual')) {
            $validated['actual'] = null;
        }

        // Date available cannot be set on creation; only editable after creation
        $validated['date_available'] = null;

        // Status is always pending (null) by default when creating a new resource
        if (! $request->filled('status')) {
            $validated['status'] = null;
        }

        $validated['user_id'] = $user->id;

        $resource = $plan->resources()->create($validated);

        if ($request->wantsJson()) {
            $isMarshall = Auth::user()->hasAnyRole(['Marshall', 'Admin']);
            $isExecutor = Auth::user()->hasRole('Executor');
            $isAssignedExecutor = Auth::id() === $plan->executor_id;

            $html = view('plans.partials.resource-card', [
                'res' => $resource,
                'plan' => $plan,
                'isMarshall' => $isMarshall,
                'isExecutor' => $isExecutor,
                'isAssignedExecutor' => $isAssignedExecutor,
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Resource added successfully.',
                'resource' => $resource,
                'html' => $html,
                'count' => $plan->resources()->count(),
            ]);
        }

        return redirect()->route('plans.show', $plan)->with('success', 'Resource added successfully.');
    }

    public function update(Request $request, Resource $resource)
    {
        $user = Auth::user();
        $plan = $resource->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($resource->isMarshallMade()) {
                abort(403, 'Executors cannot edit Marshall-made resources.');
            }
            if ($plan->executor_id !== $user->id || $resource->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot edit resources when the plan record is not Open.');
            }
        }

        $startDate = $plan->start_date->toDateString();
        $endDate = $plan->end_date->toDateString();

        $targetObjectiveId = $request->input('for', $request->input('target_objective_id'));
        if ($targetObjectiveId !== null) {
            $request->merge([
                'target_objective_id' => $targetObjectiveId,
                'for' => $targetObjectiveId,
            ]);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|string|max:100',
            'unit' => 'nullable|string|max:50',
            'for' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_objective_id' => [
                'nullable',
                Rule::exists('target_objectives', 'id')->where('plan_record_id', $plan->id),
            ],
            'target_date' => 'required|date|after_or_equal:'.$startDate.'|before_or_equal:'.$endDate,
            'date_available' => 'nullable|date',
        ], [
            'target_date.after_or_equal' => 'The target date must be within the plan schedule (on or after '.$plan->start_date->format('M d, Y').').',
            'target_date.before_or_equal' => 'The target date must be within the plan schedule (on or before '.$plan->end_date->format('M d, Y').').',
            'for.exists' => 'The selected target objective must belong to this plan.',
            'target_objective_id.exists' => 'The selected target objective must belong to this plan.',
        ]);

        $validated['target_objective_id'] = $targetObjectiveId ?: null;
        unset($validated['for']);

        $resource->update($validated);

        return redirect()->route('plans.show', $plan->id)->with('success', 'Resource updated successfully.');
    }

    public function updateActual(Request $request, Resource $resource)
    {
        $user = Auth::user();
        $plan = $resource->planRecord;

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
            'date_available' => 'nullable|date',
        ];

        if ($user->hasAnyRole(['Marshall', 'Admin'])) {
            $rules['unit'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        $resource->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Actual accomplished updated successfully.',
                'actual' => $resource->actual,
                'unit' => $resource->unit,
                'actual_with_unit' => $resource->actual_with_unit,
                'date_available' => $resource->date_available ? $resource->date_available->format('M d, Y') : null,
            ]);
        }

        return redirect()->route('plans.show', $resource->plan_record_id)->with('success', 'Actual accomplished updated successfully.');
    }

    public function updateStatus(Request $request, Resource $resource)
    {
        $user = Auth::user();
        $plan = $resource->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($resource->isMarshallMade()) {
                abort(403, 'Executors cannot update the evaluation status of Marshall-made resources.');
            }
            if ($plan->executor_id !== $user->id || $resource->user_id !== $user->id) {
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

        $resource->update(['status' => $status]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Resource status updated to '.($status ?: 'Pending'),
                'status' => $status ?: 'Unevaluated',
                'badge_class' => $resource->status_badge_class,
            ]);
        }

        return redirect()->route('plans.show', $resource->plan_record_id)->with('success', 'Resource status updated to '.($status ?: 'Pending').'.');
    }

    public function destroy(Resource $resource)
    {
        $user = Auth::user();
        $plan = $resource->planRecord;

        if (! $user->hasAnyRole(['Marshall', 'Admin'])) {
            if ($resource->isMarshallMade()) {
                abort(403, 'Executors cannot delete Marshall-made resources.');
            }
            if ($plan->executor_id !== $user->id || $resource->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
            if ($plan->status !== 'Open') {
                abort(403, 'Executors cannot delete resources when the plan record is not Open.');
            }
        }

        $planId = $resource->plan_record_id;
        $resource->delete();

        return redirect()->route('plans.show', $planId)->with('success', 'Resource removed successfully.');
    }
}

@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('plans.show', $plan) }}" class="btn btn-neon-outline btn-sm me-3">
                <i class="bi bi-arrow-left"></i> BACK
            </a>
            <h3 class="font-orbitron fw-bold text-white mb-0">EDIT {{ $plan->title }}</h3>
        </div>

        <div class="card p-3 p-md-4">
            <form action="{{ route('plans.update', $plan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="description" class="form-label font-rajdhani text-uppercase text-muted small">PLAN DESCRIPTION</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="executor_id" class="form-label font-rajdhani text-uppercase text-muted small">ASSIGNED EXECUTOR <span class="text-neon-pink">*</span></label>
                        <select class="form-select @error('executor_id') is-invalid @enderror" id="executor_id" name="executor_id" required>
                            @foreach ($executors as $exec)
                                <option value="{{ $exec->id }}" {{ old('executor_id', $plan->executor_id) == $exec->id ? 'selected' : '' }}>
                                    {{ $exec->name }} ({{ $exec->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('executor_id')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="status" class="form-label font-rajdhani text-uppercase text-muted small mb-0">OVERALL STATUS</label>
                            @if ($plan->hasPendingComponents())
                                <small class="text-warning font-rajdhani" style="font-size: 0.72rem;" title="Close requires all components to be evaluated">
                                    <i class="bi bi-clock-history me-1"></i>{{ $plan->getPendingComponentsCount() }} component(s) pending
                                </small>
                            @endif
                        </div>
                        @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="Open" {{ old('status', $plan->status) === 'Open' ? 'selected' : '' }}>OPEN</option>
                                <option value="Review" {{ old('status', $plan->status) === 'Review' ? 'selected' : '' }}>REVIEW</option>
                                <option value="Close" {{ old('status', $plan->status) === 'Close' ? 'selected' : '' }}>CLOSE</option>
                                <option value="Void" {{ old('status', $plan->status) === 'Void' ? 'selected' : '' }}>VOID</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                            @enderror
                        @else
                            <div class="form-control bg-transparent text-white border-secondary d-flex align-items-center justify-content-between" style="opacity: 0.85;">
                                <span class="{{ $plan->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ strtoupper($plan->status) }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">Only Marshalls can modify status</small>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="project_id" class="form-label font-rajdhani text-uppercase text-muted small">ASSIGNED PROJECT (OPTIONAL)</label>
                    @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                        <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
                            <option value="">NO PROJECT ASSIGNED</option>
                            @foreach ($activeProjects as $proj)
                                <option value="{{ $proj->id }}" {{ old('project_id', $plan->project_id) == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }} {{ $proj->status !== 'Active' ? '('.$proj->status.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Marshalls and Admins can optionally link this plan record to an active project.</small>
                        @error('project_id')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    @else
                        <div class="form-control bg-transparent text-white border-secondary d-flex align-items-center justify-content-between" style="opacity: 0.85;">
                            <span>{{ $plan->project ? $plan->project->name : 'No Project Assigned' }}</span>
                            <small class="text-muted" style="font-size: 0.7rem;">Only Marshalls can modify assigned project</small>
                        </div>
                    @endif
                </div>

                <!-- Evaluation Weights Configuration -->
                <div class="p-3 rounded mb-3" style="background: rgba(0, 240, 255, 0.04); border: 1px solid rgba(0, 240, 255, 0.2);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label font-rajdhani text-uppercase text-neon-cyan fw-bold mb-0">
                            <i class="bi bi-sliders me-1"></i>EVALUATION WEIGHT CONFIGURATION
                        </label>
                        @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                            <span class="small font-orbitron" id="editWeightsTotalBadge" style="color: var(--neon-green);">TOTAL: 100%</span>
                        @endif
                    </div>
                    <small class="text-muted d-block mb-3">Target, Resource, Risk, and Budget weights must total exactly 100%.</small>

                    @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="target_weight" class="form-label font-rajdhani text-uppercase text-muted small">TARGET / OBJECTIVES (%)</label>
                                <input type="number" step="any" min="0" max="100" class="form-control edit-weight-input @error('target_weight') is-invalid @enderror" id="target_weight" name="target_weight" value="{{ old('target_weight', $plan->target_weight) }}" required>
                                @error('target_weight')
                                    <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="resource_weight" class="form-label font-rajdhani text-uppercase text-muted small">RESOURCES (%)</label>
                                <input type="number" step="any" min="0" max="100" class="form-control edit-weight-input @error('resource_weight') is-invalid @enderror" id="resource_weight" name="resource_weight" value="{{ old('resource_weight', $plan->resource_weight) }}" required>
                                @error('resource_weight')
                                    <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="risk_weight" class="form-label font-rajdhani text-uppercase text-muted small">RISK MANAGEMENT (%)</label>
                                <input type="number" step="any" min="0" max="100" class="form-control edit-weight-input @error('risk_weight') is-invalid @enderror" id="risk_weight" name="risk_weight" value="{{ old('risk_weight', $plan->risk_weight) }}" required>
                                @error('risk_weight')
                                    <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="budget_weight" class="form-label font-rajdhani text-uppercase text-muted small">BUDGET (%)</label>
                                <input type="number" step="any" min="0" max="100" class="form-control edit-weight-input @error('budget_weight') is-invalid @enderror" id="budget_weight" name="budget_weight" value="{{ old('budget_weight', $plan->budget_weight) }}" required>
                                @error('budget_weight')
                                    <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @error('weights')
                            <div class="text-neon-pink small mt-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}</div>
                        @enderror
                        <div id="weightsWarning" class="text-neon-pink small mt-2 d-none">
                            <i class="bi bi-exclamation-circle me-1"></i>Weights must total exactly 100%.
                        </div>
                    @else
                        <div class="d-flex justify-content-between text-muted small p-2 rounded" style="background: rgba(255, 255, 255, 0.03);">
                            <span>Target: <strong class="text-white">{{ number_format($plan->target_weight, 0) }}%</strong></span>
                            <span>Resources: <strong class="text-white">{{ number_format($plan->resource_weight, 0) }}%</strong></span>
                            <span>Risk: <strong class="text-white">{{ number_format($plan->risk_weight, 0) }}%</strong></span>
                            <span>Budget: <strong class="text-white">{{ number_format($plan->budget_weight, 0) }}%</strong></span>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Only Marshalls can modify evaluation weights</small>
                    @endif
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="start_date" class="form-label font-rajdhani text-uppercase text-muted small">START DATE <span class="text-neon-pink">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $plan->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="end_date" class="form-label font-rajdhani text-uppercase text-muted small">END DATE <span class="text-neon-pink">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $plan->end_date->format('Y-m-d')) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline-danger mobile-w-100" onclick="if(confirm('Are you sure you want to terminate and delete this entire plan record?')) document.getElementById('delete-plan-form').submit();">
                        <i class="bi bi-trash-fill me-1"></i> DELETE PLAN
                    </button>

                    <div class="d-flex gap-2 mobile-w-100 justify-content-end">
                        <a href="{{ route('plans.show', $plan) }}" class="btn btn-neon-outline flex-fill flex-sm-grow-0 text-center">CANCEL</a>
                        <button type="submit" class="btn btn-neon-cyan flex-fill flex-sm-grow-0">
                            <i class="bi bi-check2-circle me-1"></i> UPDATE PLAN
                        </button>
                    </div>
                </div>
            </form>

            <form id="delete-plan-form" action="{{ route('plans.destroy', $plan) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

@if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const targetInput = document.getElementById('target_weight');
        const resourceInput = document.getElementById('resource_weight');
        const riskInput = document.getElementById('risk_weight');
        const budgetInput = document.getElementById('budget_weight');
        const badge = document.getElementById('editWeightsTotalBadge');
        const warning = document.getElementById('weightsWarning');

        function updateWeightTotal() {
            if (!targetInput || !resourceInput || !riskInput || !budgetInput) return;
            const t = parseFloat(targetInput.value) || 0;
            const r = parseFloat(resourceInput.value) || 0;
            const k = parseFloat(riskInput.value) || 0;
            const b = parseFloat(budgetInput.value) || 0;
            const total = Math.round((t + r + k + b) * 100) / 100;

            if (badge) {
                badge.textContent = `TOTAL: ${total}%`;
                if (Math.abs(total - 100.0) < 0.01) {
                    badge.style.color = 'var(--neon-green)';
                    if (warning) warning.classList.add('d-none');
                } else {
                    badge.style.color = 'var(--neon-pink)';
                    if (warning) {
                        warning.textContent = `Weights must total 100% (currently ${total}%).`;
                        warning.classList.remove('d-none');
                    }
                }
            }
        }

        [targetInput, resourceInput, riskInput, budgetInput].forEach(inp => {
            if (inp) inp.addEventListener('input', updateWeightTotal);
        });
        updateWeightTotal();
    });
</script>
@endif
@endsection

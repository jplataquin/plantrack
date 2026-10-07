@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm me-3">
                <i class="bi bi-arrow-left"></i> BACK
            </a>
            <h3 class="font-orbitron fw-bold text-white mb-0">CREATE PLAN RECORD</h3>
        </div>

        <div class="card p-3 p-md-4">
            <form action="{{ route('plans.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="description" class="form-label font-rajdhani text-uppercase text-muted small">PLAN DESCRIPTION</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Provide background or strategic goals for this plan...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="executor_id" class="form-label font-rajdhani text-uppercase text-muted small">ASSIGNED EXECUTOR <span class="text-neon-pink">*</span></label>
                    <select class="form-select @error('executor_id') is-invalid @enderror" id="executor_id" name="executor_id" required>
                        <option value="">SELECT EXECUTOR</option>
                        @foreach ($executors as $exec)
                            <option value="{{ $exec->id }}" {{ old('executor_id', auth()->id()) == $exec->id ? 'selected' : '' }}>
                                {{ $exec->name }} ({{ $exec->email }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block">The assigned executor is accountable for executing this plan record.</small>
                    @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                        <div class="mt-2 small">
                            <span class="text-muted">Operative not in directory?</span>
                            <a href="{{ route('executors.create') }}" class="text-neon-cyan fw-bold text-decoration-none ms-1">
                                <i class="bi bi-person-plus-fill me-1"></i> Provision new Executor account &rarr;
                            </a>
                        </div>
                    @endif
                    @error('executor_id')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="project_id" class="form-label font-rajdhani text-uppercase text-muted small">PROJECT (OPTIONAL)</label>
                    @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                        <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
                            <option value="">NO PROJECT ASSIGNED</option>
                            @foreach ($activeProjects as $proj)
                                <option value="{{ $proj->id }}" {{ old('project_id', request('project_id')) == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block">Marshalls and Admins can optionally link this plan record to an active project.</small>
                        @error('project_id')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    @else
                        <div class="form-control bg-transparent text-muted border-secondary d-flex align-items-center justify-content-between" style="opacity: 0.85;">
                            <span>No Project Assigned</span>
                            <small class="text-muted" style="font-size: 0.7rem;">Only Marshalls can assign projects</small>
                        </div>
                    @endif
                </div>

                @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                <!-- Evaluation Weights Configuration -->
                <div class="p-3 rounded mb-4" style="background: rgba(0, 240, 255, 0.04); border: 1px solid rgba(0, 240, 255, 0.2);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label font-rajdhani text-uppercase text-neon-cyan fw-bold mb-0">
                            <i class="bi bi-sliders me-1"></i>EVALUATION WEIGHT CONFIGURATION
                        </label>
                        <span class="small font-orbitron" id="createWeightsTotalBadge" style="color: var(--neon-green);">TOTAL: 100%</span>
                    </div>
                    <small class="text-muted d-block mb-3">Define evaluation weights (sum must equal 100%). Defaults to 60% Target, 20% Resources, 10% Risk, 10% Budget.</small>

                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label for="target_weight" class="form-label font-rajdhani text-uppercase text-muted small">TARGET / OBJECTIVES (%)</label>
                            <input type="number" step="any" min="0" max="100" class="form-control create-weight-input @error('target_weight') is-invalid @enderror" id="target_weight" name="target_weight" value="{{ old('target_weight', 60) }}" required>
                            @error('target_weight')
                                <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="resource_weight" class="form-label font-rajdhani text-uppercase text-muted small">RESOURCES (%)</label>
                            <input type="number" step="any" min="0" max="100" class="form-control create-weight-input @error('resource_weight') is-invalid @enderror" id="resource_weight" name="resource_weight" value="{{ old('resource_weight', 20) }}" required>
                            @error('resource_weight')
                                <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="risk_weight" class="form-label font-rajdhani text-uppercase text-muted small">RISK MANAGEMENT (%)</label>
                            <input type="number" step="any" min="0" max="100" class="form-control create-weight-input @error('risk_weight') is-invalid @enderror" id="risk_weight" name="risk_weight" value="{{ old('risk_weight', 10) }}" required>
                            @error('risk_weight')
                                <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="budget_weight" class="form-label font-rajdhani text-uppercase text-muted small">BUDGET (%)</label>
                            <input type="number" step="any" min="0" max="100" class="form-control create-weight-input @error('budget_weight') is-invalid @enderror" id="budget_weight" name="budget_weight" value="{{ old('budget_weight', 10) }}" required>
                            @error('budget_weight')
                                <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @error('weights')
                        <div class="text-neon-pink small mt-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}</div>
                    @enderror
                    <div id="createWeightsWarning" class="text-neon-pink small mt-2 d-none">
                        <i class="bi bi-exclamation-circle me-1"></i>Weights must total exactly 100%.
                    </div>
                </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="start_date" class="form-label font-rajdhani text-uppercase text-muted small">START DATE <span class="text-neon-pink">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="end_date" class="form-label font-rajdhani text-uppercase text-muted small">END DATE <span class="text-neon-pink">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', date('Y-m-d', strtotime('+5 days'))) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-3 border-top border-secondary">
                    <a href="{{ route('executors.index') }}" class="btn btn-neon-outline text-center">CANCEL</a>
                    <button type="submit" class="btn btn-neon-pink">
                        <i class="bi bi-lightning-charge-fill me-1"></i> INITIALIZE PLAN
                    </button>
                </div>
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
        const badge = document.getElementById('createWeightsTotalBadge');
        const warning = document.getElementById('createWeightsWarning');

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

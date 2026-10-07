@extends('layouts.app')

@section('content')
<!-- Mobile Burger Bar (< lg) -->
<div class="d-flex align-items-center justify-content-between d-lg-none mb-3 p-2 rounded" style="background: #120f24; border: 1px solid var(--neon-cyan);">
    <button class="btn btn-neon-outline btn-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#executorSidebarDrawer" aria-controls="executorSidebarDrawer">
        <i class="bi bi-list fs-5"></i>
        <span class="font-orbitron small">EXECUTOR PROFILE</span>
    </button>
    <div class="d-flex align-items-center gap-2">
        <span class="badge badge-hit px-2 py-1 small">{{ $metrics['hit_components'] }} Hit</span>
        <span class="badge badge-missed px-2 py-1 small">{{ $metrics['missed_components'] }} Missed</span>
    </div>
</div>

<!-- Main 2-Column Grid Layout (Left Sidebar + Right Plan Records) -->
<div class="row g-3">
    <!-- ==================== LEFT SIDEBAR: FULL HEIGHT CARD (DESKTOP) / OFFCANVAS DRAWER (MOBILE) ==================== -->
    <div class="col-12 col-lg-4 col-xl-3">
        <div class="offcanvas-lg offcanvas-start h-100" tabindex="-1" id="executorSidebarDrawer" aria-labelledby="executorSidebarLabel">
            <div class="card h-100 p-3" style="background: #120f24; border: 1px solid var(--neon-cyan); min-height: 100%;">
                <!-- Offcanvas Header for Mobile View -->
                <div class="offcanvas-header p-0 pb-3 border-bottom border-secondary d-lg-none">
                    <h5 class="offcanvas-title font-orbitron text-white" id="executorSidebarLabel">
                        <i class="bi bi-person-gear text-neon-cyan me-2"></i>EXECUTOR PROFILE
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#executorSidebarDrawer" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body p-0 pt-3 pt-lg-0 d-flex flex-column gap-3">
                    <!-- Console Header Badge -->
                    <div class="p-2 rounded" style="background: rgba(0, 240, 255, 0.1); border: 1px solid var(--neon-cyan);">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="font-orbitron small text-neon-cyan fw-bold">
                                <i class="bi bi-person-gear me-1"></i> OPERATIVE FILE
                            </span>
                            <span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">
                                EXECUTOR
                            </span>
                        </div>
                    </div>

                    <!-- Section 1: EXECUTOR DETAILS -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-person-lines-fill text-neon-cyan me-1"></i>EXECUTOR DETAILS
                        </span>
                        <div class="d-flex align-items-center p-3 rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05);">
                            <div class="me-3 flex-shrink-0">
                                <a href="{{ route('profile.show', $executor) }}" class="text-decoration-none" title="View {{ $executor->name }}'s Profile">
                                    @if ($executor->profile_picture)
                                        <img src="{{ asset('storage/' . $executor->profile_picture) }}" alt="{{ $executor->name }}" class="rounded-circle object-fit-cover shadow-sm executor-profile-avatar" style="width: 52px; height: 52px; border: 2px solid var(--neon-cyan);">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 executor-profile-avatar-fallback" style="width: 52px; height: 52px; background: #00f0ff; color: #0a0814;">
                                            {{ strtoupper(substr($executor->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <h5 class="fw-bold text-white mb-0 font-rajdhani text-truncate">
                                    <a href="{{ route('profile.show', $executor) }}" class="text-white text-decoration-none user-profile-link" title="View {{ $executor->name }}'s Profile">
                                        {{ $executor->name }}
                                    </a>
                                </h5>
                                <small class="text-muted text-truncate d-block">{{ $executor->email }}</small>
                                @if ($executor->must_reset_password)
                                    <span class="badge mt-1 text-neon-yellow" style="background: rgba(255, 230, 0, 0.15); border: 1px solid rgba(255, 230, 0, 0.4); font-size: 0.65rem;">
                                        <i class="bi bi-key me-1"></i> PENDING FIRST LOGIN
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-1">

                    <!-- Section 2: ASSIGNED PLANS STATUS BREAKDOWN -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-kanban text-neon-orange me-1"></i>PLANS BREAKDOWN
                        </span>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2" style="background: rgba(255, 255, 255, 0.03);">
                            <div class="text-center flex-fill border-end border-dark">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">TOTAL</span>
                                <span class="fw-bold font-orbitron text-white fs-6">{{ $metrics['total_plans'] }}</span>
                            </div>
                            <div class="text-center flex-fill border-end border-dark">
                                <span class="text-neon-cyan d-block" style="font-size: 0.68rem;">OPEN</span>
                                <span class="fw-bold font-orbitron text-neon-cyan fs-6">{{ $metrics['open_plans'] }}</span>
                            </div>
                            <div class="text-center flex-fill border-end border-dark">
                                <span class="text-neon-yellow d-block" style="font-size: 0.68rem;">REVIEW</span>
                                <span class="fw-bold font-orbitron text-neon-yellow fs-6">{{ $metrics['review_plans'] }}</span>
                            </div>
                            <div class="text-center flex-fill">
                                <span class="text-neon-green d-block" style="font-size: 0.68rem;">CLOSED</span>
                                <span class="fw-bold font-orbitron text-neon-green fs-6">{{ $metrics['close_plans'] }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-1">

                    <!-- Section 3: OVERALL EVALUATION PERFORMANCE -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-speedometer text-neon-green me-1"></i>OVERALL EVALUATION METRICS
                        </span>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(0, 255, 136, 0.1); border: 1px solid rgba(0, 255, 136, 0.3);">
                                <span class="small font-rajdhani fw-bold text-neon-green"><i class="bi bi-check-circle me-1"></i>TOTAL HIT</span>
                                <span class="badge badge-hit px-2 py-1 fs-6">{{ $metrics['hit_components'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3);">
                                <span class="small font-rajdhani fw-bold text-neon-violet"><i class="bi bi-x-circle me-1"></i>TOTAL MISSED</span>
                                <span class="badge badge-missed px-2 py-1 fs-6">{{ $metrics['missed_components'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(148, 163, 184, 0.1); border: 1px solid rgba(148, 163, 184, 0.3);">
                                <span class="small font-rajdhani fw-bold text-muted"><i class="bi bi-slash-circle me-1"></i>TOTAL VOID</span>
                                <span class="badge badge-void px-2 py-1 fs-6">{{ $metrics['void_components'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1);">
                                <span class="small font-rajdhani fw-bold text-muted"><i class="bi bi-clock me-1"></i>UNEVALUATED</span>
                                <span class="badge badge-unevaluated px-2 py-1 fs-6">{{ $metrics['pending_components'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions in Sidebar -->
                    <div class="mt-auto pt-3 border-top border-secondary">
                        <a href="{{ route('plans.create', ['executor_id' => $executor->id]) }}" class="btn btn-neon-orange btn-sm w-100 mb-2">
                            <i class="bi bi-plus-lg me-1"></i> Assign New Plan
                        </a>
                        <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm w-100 text-center">
                            <i class="bi bi-arrow-left me-1"></i> Back to All Executors
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== RIGHT MAIN COLUMN: EXECUTOR'S PLANS (3 PER ROW ON DESKTOP) ==================== -->
    <div class="col-12 col-lg-8 col-xl-9">
        <!-- Header Card with Search & Status Filters -->
        <div class="card mb-4 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm d-none d-lg-inline-flex">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h3 class="font-orbitron fw-bold text-white mb-0">
                            <a href="{{ route('profile.show', $executor) }}" class="text-white text-decoration-none user-profile-link" title="View {{ $executor->name }}'s Profile">
                                {{ $executor->name }}
                            </a>'s Plan Records
                        </h3>
                    </div>
                    <p class="text-muted mb-0 small">ALL PROJECT PLANS ASSIGNED FOR EXECUTION ({{ $plans->total() }})</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="GET" action="{{ route('executors.show', $executor) }}" class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 140px;">
                            <option value="">STATUS: ALL</option>
                            <option value="Open" {{ request('status') === 'Open' ? 'selected' : '' }}>OPEN ({{ $metrics['open_plans'] }})</option>
                            <option value="Review" {{ request('status') === 'Review' ? 'selected' : '' }}>IN REVIEW ({{ $metrics['review_plans'] }})</option>
                            <option value="Close" {{ request('status') === 'Close' ? 'selected' : '' }}>CLOSED ({{ $metrics['close_plans'] }})</option>
                            <option value="Void" {{ request('status') === 'Void' ? 'selected' : '' }}>VOID ({{ $metrics['void_plans'] ?? 0 }})</option>
                        </select>
                        @if (request('status'))
                            <a href="{{ route('executors.show', $executor) }}" class="btn btn-neon-outline btn-sm" title="Clear filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </form>

                    <a href="{{ route('plans.create', ['executor_id' => $executor->id]) }}" class="btn btn-neon-orange btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> New Plan
                    </a>
                </div>
            </div>
        </div>

        <!-- Plan Records List (Single Item Full-Width Cards) -->
        <div class="d-flex flex-column gap-3">
            @forelse ($plans as $plan)
                @php
                    $hit = $plan->targetObjectives->where('status', 'Hit')->count() 
                        + $plan->resources->where('status', 'Hit')->count() 
                        + $plan->riskManagements->where('status', 'Hit')->count()
                        + $plan->budgets->where('status', 'Hit')->count();
                    $missed = $plan->targetObjectives->where('status', 'Missed')->count() 
                        + $plan->resources->where('status', 'Missed')->count() 
                        + $plan->riskManagements->where('status', 'Missed')->count()
                        + $plan->budgets->where('status', 'Missed')->count();
                    $void = $plan->targetObjectives->where('status', 'Void')->count() 
                        + $plan->resources->where('status', 'Void')->count() 
                        + $plan->riskManagements->where('status', 'Void')->count()
                        + $plan->budgets->where('status', 'Void')->count();
                @endphp
                <div class="card card-hover p-3 p-md-4 w-100" style="border: 1px solid rgba(0, 240, 255, 0.25); background: #120f24;">
                    <div class="row align-items-center g-3">
                        <!-- Left: Title, Description, and Dates -->
                        <div class="col-12 col-xl-5">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="{{ $plan->status_badge_class }} px-2 py-0.5 rounded-pill small" style="font-size: 0.72rem;">
                                    {{ $plan->status }}
                                </span>
                                <span class="badge" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4); font-size: 0.72rem;">
                                    <i class="bi bi-speedometer2 me-1"></i>Score: {{ number_format($plan->evaluation_score, 1) }}%
                                </span>
                                <span class="text-muted small">
                                    <i class="bi bi-clock-history text-neon-cyan me-1"></i>{{ $plan->start_date->diffInDays($plan->end_date) }} Days
                                </span>
                                @if ($plan->project)
                                    <a href="{{ route('projects.show', $plan->project) }}" class="badge text-decoration-none" style="background: rgba(168, 85, 247, 0.15); color: var(--neon-violet-light); border: 1px solid var(--neon-violet); font-size: 0.72rem;">
                                        <i class="bi bi-folder-fill me-1"></i>{{ $plan->project->name }}
                                    </a>
                                @endif
                            </div>
                            <h4 class="font-rajdhani fw-bold text-white mb-1">
                                <a href="{{ route('plans.show', $plan) }}" class="text-white text-decoration-none">
                                    {{ $plan->title }}
                                </a>
                            </h4>
                            <p class="text-muted small mb-2" style="font-size: 0.825rem;">
                                {{ Str::limit($plan->description ?: 'No detailed mission description declared.', 140) }}
                            </p>
                            <div class="small text-light d-flex flex-wrap align-items-center gap-2">
                                <span><i class="bi bi-calendar-event text-neon-orange me-1"></i>Start: <strong>{{ $plan->start_date->format('M d, Y') }}</strong></span>
                                <span>&rarr;</span>
                                <span><i class="bi bi-calendar-check text-neon-cyan me-1"></i>End: <strong>{{ $plan->end_date->format('M d, Y') }}</strong></span>
                            </div>
                        </div>

                        <!-- Center: Component breakdown & evaluation stats -->
                        <div class="col-12 col-md-7 col-xl-4">
                            <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                                COMPONENTS & EVALUATION STATUS
                            </span>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <div class="p-2 rounded flex-fill" style="background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.2);">
                                    <span class="text-neon-cyan small fw-bold d-block font-rajdhani">
                                        <i class="bi bi-bullseye me-1"></i>{{ $plan->targetObjectives->count() }} TARGETS
                                    </span>
                                </div>
                                <div class="p-2 rounded flex-fill" style="background: rgba(255, 107, 0, 0.08); border: 1px solid rgba(255, 107, 0, 0.2);">
                                    <span class="text-neon-orange small fw-bold d-block font-rajdhani">
                                        <i class="bi bi-box-seam me-1"></i>{{ $plan->resources->count() }} RESOURCES
                                    </span>
                                </div>
                                <div class="p-2 rounded flex-fill" style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.2);">
                                    <span class="text-neon-violet small fw-bold d-block font-rajdhani">
                                        <i class="bi bi-shield-exclamation me-1"></i>{{ $plan->riskManagements->count() }} RISKS
                                    </span>
                                </div>
                                <div class="p-2 rounded flex-fill" style="background: rgba(255, 230, 0, 0.08); border: 1px solid rgba(255, 230, 0, 0.2);">
                                    <span class="text-neon-yellow small fw-bold d-block font-rajdhani">
                                        <i class="bi bi-cash-stack me-1"></i>{{ $plan->budgets->count() }} BUDGET
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge badge-hit px-2 py-0.5" style="font-size: 0.72rem;">{{ $hit }} Hit</span>
                                <span class="badge badge-missed px-2 py-0.5" style="font-size: 0.72rem;">{{ $missed }} Missed</span>
                                <span class="badge badge-void px-2 py-0.5" style="font-size: 0.72rem;">{{ $void }} Void</span>
                            </div>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="col-12 col-md-5 col-xl-3 text-xl-end">
                            <div class="d-flex flex-column flex-sm-row flex-xl-column gap-2 justify-content-xl-end">
                                <a href="{{ route('plans.show', $plan) }}" class="btn btn-neon-cyan fw-bold d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-eye-fill"></i>
                                    <span>OPEN PLAN</span>
                                </a>
                                <a href="{{ route('plans.edit', $plan) }}" class="btn btn-neon-outline btn-sm d-flex align-items-center justify-content-center gap-1">
                                    <i class="bi bi-pencil-fill"></i>
                                    <span>EDIT DETAILS</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-1 text-neon-cyan mb-2"></i>
                    <h5 class="text-white font-orbitron">NO PLAN RECORDS FOUND</h5>
                    <p class="small mb-3">No plans matching the selected filter were found for this executor.</p>
                    <div>
                        <a href="{{ route('plans.create', ['executor_id' => $executor->id]) }}" class="btn btn-neon-orange btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create Plan For {{ $executor->name }}
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($plans->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

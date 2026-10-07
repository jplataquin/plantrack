@extends('layouts.app')

@section('content')
@php
    $allComponents = collect()
        ->merge($plan->targetObjectives)
        ->merge($plan->resources)
        ->merge($plan->riskManagements)
        ->merge($plan->budgets);
    $hitCount = $allComponents->where('status', 'Hit')->count();
    $missedCount = $allComponents->where('status', 'Missed')->count();
    $voidCount = $allComponents->where('status', 'Void')->count();
    $pendingCount = $allComponents->whereNull('status')->count();
@endphp

<!-- Floating Toast Container for AJAX Feedback -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="statusToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="background: #17132e; border: 1px solid #00f0ff !important; min-width: 280px;">
        <div class="d-flex p-2 align-items-center">
            <i class="bi bi-check-circle-fill text-neon-green fs-4 me-2"></i>
            <div class="toast-body flex-grow-1 font-rajdhani fw-bold" id="statusToastBody">
                STATUS UPDATED
            </div>
            <button type="button" class="btn-close btn-close-white me-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Mobile Burger Bar (< lg) -->
<div class="d-flex align-items-center justify-content-between d-lg-none mb-3 p-2 rounded" style="background: #120f24; border: 1px solid {{ $isMarshall ? 'var(--neon-violet)' : 'var(--neon-cyan)' }};">
    <button class="btn btn-neon-outline btn-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#planSidebarDrawer" aria-controls="planSidebarDrawer">
        <i class="bi bi-list fs-5"></i>
        <span class="font-orbitron small">{{ $isMarshall ? 'MARSHALL CONSOLE' : 'PLAN DETAILS' }}</span>
    </button>
    <div class="d-flex align-items-center gap-2">
        <span class="badge" id="mobile-metric-score" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);">
            <i class="bi bi-speedometer2 me-1"></i>{{ number_format($plan->evaluation_score, 1) }}%
        </span>
        <span class="badge badge-hit px-2 py-1 small" id="mobile-hit-count">{{ $hitCount }} Hit</span>
        <span class="badge badge-missed px-2 py-1 small" id="mobile-missed-count">{{ $missedCount }} Missed</span>
    </div>
</div>

<!-- Main Grid Layout (Left Sidebar + Right Content) -->
<div class="row g-3">
    <!-- ==================== LEFT SIDEBAR: FULL HEIGHT CARD (DESKTOP) / OFFCANVAS DRAWER (MOBILE) ==================== -->
    <div class="col-12 col-lg-4 col-xl-3">
        <div class="offcanvas-lg offcanvas-start h-100" tabindex="-1" id="planSidebarDrawer" aria-labelledby="planSidebarLabel">
            <div class="card h-100 p-3" style="background: #120f24; border: 1px solid {{ $isMarshall ? 'var(--neon-violet)' : 'var(--neon-cyan)' }}; min-height: 100%;">
                <!-- Offcanvas Header for Mobile View -->
                <div class="offcanvas-header p-0 pb-3 border-bottom border-secondary d-lg-none">
                    <h5 class="offcanvas-title font-orbitron text-white" id="planSidebarLabel">
                        @if ($isMarshall)
                            <i class="bi bi-shield-check text-neon-violet me-2"></i>MARSHALL CONSOLE
                        @else
                            <i class="bi bi-kanban text-neon-cyan me-2"></i>PLAN DETAILS
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#planSidebarDrawer" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body p-0 pt-3 pt-lg-0 d-flex flex-column gap-3">
                    <!-- Console Header Badge -->
                    <div class="p-2 rounded text-center" style="background: {{ $isMarshall ? 'rgba(168, 85, 247, 0.15)' : 'rgba(0, 240, 255, 0.1)' }}; border: 1px solid {{ $isMarshall ? '#a855f7' : 'var(--neon-cyan)' }};">
                        <span class="font-orbitron small {{ $isMarshall ? 'text-neon-violet' : 'text-neon-cyan' }} fw-bold d-block">
                            @if ($isMarshall)
                                <i class="bi bi-shield-check me-1"></i> MARSHALL VIEW
                            @else
                                <i class="bi bi-lightning-charge me-1"></i> EXECUTOR VIEW
                            @endif
                        </span>
                    </div>

                    <!-- Quick Status Control -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="font-rajdhani text-uppercase text-muted small fw-bold mb-0">OVERALL STATUS</label>
                            @if ($isMarshall && $plan->hasPendingComponents())
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" style="font-size: 0.65rem;" title="{{ $plan->getPendingComponentsCount() }} component(s) pending evaluation">
                                    <i class="bi bi-clock-history me-1"></i>{{ $plan->getPendingComponentsCount() }} PENDING
                                </span>
                            @endif
                        </div>
                        @if ($isMarshall)
                            <div class="d-flex align-items-center gap-1">
                                <select id="plan-status-select" class="form-select form-select-sm" data-url="{{ route('plans.status.update', $plan) }}">
                                    <option value="Open" {{ $plan->status === 'Open' ? 'selected' : '' }}>SET: OPEN</option>
                                    <option value="Review" {{ $plan->status === 'Review' ? 'selected' : '' }}>SET: REVIEW</option>
                                    <option value="Close" {{ $plan->status === 'Close' ? 'selected' : '' }}>SET: CLOSE</option>
                                    <option value="Void" {{ $plan->status === 'Void' ? 'selected' : '' }}>SET: VOID</option>
                                </select>
                                <div id="plan-status-spinner" class="spinner-border spinner-border-sm text-neon-cyan d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        @else
                            <div class="d-flex align-items-center">
                                <span class="{{ $plan->status_badge_class }} px-2 py-1 rounded-pill font-rajdhani fw-bold small">
                                    {{ strtoupper($plan->status) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <hr class="border-secondary my-1">

                    <!-- Section 1: ASSIGNED EXECUTOR -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-person-gear text-neon-cyan me-1"></i>ASSIGNED EXECUTOR
                        </span>
                        <div class="d-flex align-items-center p-2 rounded" style="background: rgba(255, 255, 255, 0.03);">
                            @if ($plan->executor)
                                <a href="{{ route('profile.show', $plan->executor) }}" class="text-decoration-none me-2" title="View {{ $plan->executor->name }}'s Profile">
                                    @if ($plan->executor->profile_picture)
                                        <img src="{{ asset('storage/' . $plan->executor->profile_picture) }}" alt="{{ $plan->executor->name }}" class="rounded-circle object-fit-cover shadow-sm executor-profile-avatar" style="width: 38px; height: 38px; border: 1.5px solid var(--neon-cyan); flex-shrink: 0;">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold executor-profile-avatar-fallback" style="width: 38px; height: 38px; background: #00f0ff; color: #0a0814; flex-shrink: 0;">
                                            {{ strtoupper(substr($plan->executor->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="overflow-hidden">
                                    <a href="{{ route('profile.show', $plan->executor) }}" class="fw-bold text-white text-decoration-none text-truncate d-block user-profile-link" title="View {{ $plan->executor->name }}'s Profile">
                                        {{ $plan->executor->name }}
                                    </a>
                                    <small class="text-muted text-truncate d-block">{{ $plan->executor->email ?? '' }}</small>
                                </div>
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold executor-profile-avatar-fallback" style="width: 38px; height: 38px; background: #6c757d; color: #0a0814; flex-shrink: 0;">
                                    U
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-white text-truncate">Unassigned</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="border-secondary my-1">

                    <!-- Section 2: TIMELINE SCHEDULE -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-calendar-event text-neon-orange me-1"></i>TIMELINE SCHEDULE
                        </span>
                        <div class="p-2 rounded small" style="background: rgba(255, 255, 255, 0.03);">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Start Date:</span>
                                <strong class="text-white">{{ $plan->start_date->format('M d, Y') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Target End:</span>
                                <strong class="text-white">{{ $plan->end_date->format('M d, Y') }}</strong>
                            </div>
                            <div class="d-flex justify-content-between pt-1 border-top border-dark">
                                <span class="text-muted">Total Span:</span>
                                <span class="text-neon-cyan fw-bold">{{ $plan->start_date->diffInDays($plan->end_date) }} Days</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-1">

                    <!-- Section 3: EVALUATION METRICS -->
                    <div>
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">
                            <i class="bi bi-graph-up-arrow text-neon-green me-1"></i>EVALUATION METRICS
                        </span>

                        <!-- Weighted Overall Score Card -->
                        <div class="p-3 rounded mb-3 text-center position-relative" style="background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.35); box-shadow: 0 0 15px rgba(0, 255, 136, 0.15);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small font-rajdhani text-uppercase text-start flex-grow-1">OVERALL EVALUATION SCORE</span>
                                @if ($isMarshall)
                                    <button type="button" class="btn btn-link p-0 text-neon-cyan font-rajdhani fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#editWeightsModal" title="Modify Evaluation Weights" style="font-size: 0.72rem;">
                                        <i class="bi bi-sliders me-1"></i>WEIGHTS
                                    </button>
                                @endif
                            </div>
                            <div class="display-6 font-orbitron fw-bold text-neon-green mb-1" id="metric-overall-score">
                                {{ number_format($plan->evaluation_score, 1) }}%
                            </div>
                            <div class="progress mb-1" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="metric-overall-progress" role="progressbar" style="width: {{ min(100, max(0, $plan->evaluation_score)) }}%;"></div>
                            </div>
                        </div>

                        <!-- Component Score Summary Cards -->
                        <div class="d-flex flex-column gap-2" id="metrics-container">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.25);">
                                <span class="small font-rajdhani fw-bold text-neon-cyan component-score-title">
                                    <i class="bi bi-bullseye me-1"></i>Target/Objectives (<span id="label-target-weight">{{ number_format($plan->target_weight, 0) }}</span>%)
                                </span>
                                <span class="badge font-orbitron py-1 fs-6 component-score-pill" style="background: rgba(0, 240, 255, 0.15); color: var(--neon-cyan) !important; border: 1px solid var(--neon-cyan);" id="metric-target-score">{{ number_format($plan->target_score, 1) }}%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(255, 140, 0, 0.08); border: 1px solid rgba(255, 140, 0, 0.25);">
                                <span class="small font-rajdhani fw-bold text-neon-orange component-score-title">
                                    <i class="bi bi-box-seam me-1"></i>Resource (<span id="label-resource-weight">{{ number_format($plan->resource_weight, 0) }}</span>%)
                                </span>
                                <span class="badge font-orbitron py-1 fs-6 component-score-pill" style="background: rgba(255, 140, 0, 0.15); color: var(--neon-orange) !important; border: 1px solid var(--neon-orange);" id="metric-resource-score">{{ number_format($plan->resource_score, 1) }}%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.25);">
                                <span class="small font-rajdhani fw-bold text-neon-violet component-score-title">
                                    <i class="bi bi-shield-exclamation me-1"></i>Risk Management (<span id="label-risk-weight">{{ number_format($plan->risk_weight, 0) }}</span>%)
                                </span>
                                <span class="badge font-orbitron py-1 fs-6 component-score-pill" style="background: rgba(168, 85, 247, 0.15); color: var(--neon-violet) !important; border: 1px solid var(--neon-violet);" id="metric-risk-score">{{ number_format($plan->risk_score, 1) }}%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: rgba(255, 230, 0, 0.08); border: 1px solid rgba(255, 230, 0, 0.25);">
                                <span class="small font-rajdhani fw-bold text-neon-yellow component-score-title">
                                    <i class="bi bi-cash-stack me-1"></i>Budget (<span id="label-budget-weight">{{ number_format($plan->budget_weight, 0) }}</span>%)
                                </span>
                                <span class="badge font-orbitron py-1 fs-6 component-score-pill" style="background: rgba(255, 230, 0, 0.15); color: var(--neon-yellow) !important; border: 1px solid var(--neon-yellow);" id="metric-budget-score">{{ number_format($plan->budget_score, 1) }}%</span>
                            </div>

                            <!-- Preserved background references for legacy metric elements -->
                            <div class="d-none" aria-hidden="true">
                                <span id="metric-hit-count">{{ number_format($plan->target_score, 1) }}%</span>
                                <span id="metric-missed-count">{{ $missedCount }}</span>
                                <span id="metric-void-count">{{ $voidCount }}</span>
                                <span id="metric-pending-count">{{ $pendingCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== RIGHT MAIN COLUMN: TITLE & COMPONENTS ==================== -->
    <div class="col-12 col-lg-8 col-xl-9">
        <!-- Plan Title & Description Card -->
        <div class="card mb-4 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="font-orbitron fw-bold text-white mb-0">{{ $plan->title }}</h3>
                        @if ($plan->project)
                            <a href="{{ route('projects.show', $plan->project) }}" class="badge text-decoration-none" style="background: rgba(168, 85, 247, 0.15); border: 1px solid var(--neon-violet); color: var(--neon-violet-light);">
                                <i class="bi bi-folder-fill me-1"></i>{{ $plan->project->name }}
                            </a>
                        @endif
                    </div>
                    <p class="text-muted mb-0 small">{{ $plan->description ?: 'No detailed mission description declared.' }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('plans.edit', $plan) }}" class="btn btn-neon-outline btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-pencil-fill me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Component Navigation Tabs -->
        <ul class="nav nav-neon-pills mb-4" id="componentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="targets-tab" data-bs-toggle="tab" data-bs-target="#targets" type="button" role="tab">
                    <i class="bi bi-bullseye text-neon-cyan me-2"></i>TARGETS / OBJECTIVES (<span id="targets-tab-count">{{ $plan->targetObjectives->count() }}</span>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab">
                    <i class="bi bi-box-seam text-neon-orange me-2"></i>RESOURCES (<span id="resources-tab-count">{{ $plan->resources->count() }}</span>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="risks-tab" data-bs-toggle="tab" data-bs-target="#risks" type="button" role="tab">
                    <i class="bi bi-shield-exclamation text-neon-violet me-2"></i>RISK MANAGEMENT (<span id="risks-tab-count">{{ $plan->riskManagements->count() }}</span>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="budgets-tab" data-bs-toggle="tab" data-bs-target="#budgets" type="button" role="tab">
                    <i class="bi bi-cash-stack text-neon-yellow me-2"></i>BUDGET (<span id="budgets-tab-count">{{ $plan->budgets->count() }}</span>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="componentTabsContent">
            <!-- ==================== 1. TARGETS & OBJECTIVES (3 PER ROW ON DESKTOP) ==================== -->
            <div class="tab-pane fade show active" id="targets" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-orbitron text-white mb-0 d-flex align-items-center">
                        <i class="bi bi-bullseye text-neon-cyan me-2"></i>TARGETS & OBJECTIVES
                    </h5>
                    @if ($isMarshall)
                        <button type="button" class="btn btn-neon-cyan btn-sm" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                            <i class="bi bi-plus-lg me-1"></i> ADD TARGET
                        </button>
                    @endif
                </div>

                <div class="row g-3" id="targets-row">
                    @forelse ($plan->targetObjectives as $target)
                        @include('plans.partials.target-card', ['target' => $target])
                    @empty
                        <div class="col-12 empty-placeholder-targets">
                            <div class="card p-5 text-center text-muted">
                                <i class="bi bi-bullseye fs-1 text-neon-cyan mb-2"></i>
                                <h5 class="text-white font-orbitron">NO TARGETS CONFIGURED</h5>
                                <p class="small mb-3">Add targets and objective goals for the executor.</p>
                                <div>
                                    <button type="button" class="btn btn-neon-cyan btn-sm" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                                        Add First Target
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ==================== 2. RESOURCES (3 PER ROW ON DESKTOP) ==================== -->
            <div class="tab-pane fade" id="resources" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-orbitron text-white mb-0 d-flex align-items-center">
                        <i class="bi bi-box-seam text-neon-orange me-2"></i>RESOURCE COMPONENTS
                    </h5>
                    @if ($isMarshall || $plan->status === 'Open')
                        <button type="button" class="btn btn-neon-orange btn-sm" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                            <i class="bi bi-plus-lg me-1"></i> ADD RESOURCE
                        </button>
                    @endif
                </div>

                <div class="row g-3" id="resources-row">
                    @forelse ($plan->resources as $res)
                        @include('plans.partials.resource-card', ['res' => $res])
                    @empty
                        <div class="col-12 empty-placeholder-resources">
                            <div class="card p-5 text-center text-muted">
                                <i class="bi bi-box-seam fs-1 text-neon-orange mb-2"></i>
                                <h5 class="text-white font-orbitron">NO RESOURCES DECLARED</h5>
                                <p class="small mb-3">Add budget, hardware, or human resources needed for this plan.</p>
                                @if ($isMarshall || $plan->status === 'Open')
                                    <div>
                                        <button type="button" class="btn btn-neon-orange btn-sm" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                                            Add First Resource
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ==================== 3. RISK MANAGEMENT (3 PER ROW ON DESKTOP) ==================== -->
            <div class="tab-pane fade" id="risks" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-orbitron text-white mb-0 d-flex align-items-center">
                        <i class="bi bi-shield-exclamation text-neon-violet me-2"></i>RISK MANAGEMENT
                    </h5>
                    @if ($isMarshall || $plan->status === 'Open')
                        <button type="button" class="btn btn-neon-violet btn-sm" data-bs-toggle="modal" data-bs-target="#addRiskModal">
                            <i class="bi bi-plus-lg me-1"></i> ADD RISK ENTRY
                        </button>
                    @endif
                </div>

                <div class="row g-3" id="risks-row">
                    @forelse ($plan->riskManagements as $risk)
                        @include('plans.partials.risk-card', ['risk' => $risk])
                    @empty
                        <div class="col-12 empty-placeholder-risks">
                            <div class="card p-5 text-center text-muted">
                                <i class="bi bi-shield-exclamation fs-1 text-neon-violet mb-2"></i>
                                <h5 class="text-white font-orbitron">NO RISK ITEMS REGISTERED</h5>
                                <p class="small mb-3">Identify operational, security, or timeline risks and declare mitigations.</p>
                                @if ($isMarshall || $plan->status === 'Open')
                                    <div>
                                        <button type="button" class="btn btn-neon-violet btn-sm" data-bs-toggle="modal" data-bs-target="#addRiskModal">
                                            Add First Risk Item
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ==================== 4. BUDGETS (3 PER ROW ON DESKTOP) ==================== -->
            <div class="tab-pane fade" id="budgets" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-orbitron text-white mb-0 d-flex align-items-center">
                        <i class="bi bi-cash-stack text-neon-yellow me-2"></i>BUDGET ALLOCATIONS
                    </h5>
                    @if ($isMarshall)
                        <button type="button" class="btn btn-neon-yellow btn-sm" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                            <i class="bi bi-plus-lg me-1"></i> ADD BUDGET
                        </button>
                    @endif
                </div>

                <div class="row g-3" id="budgets-row">
                    @forelse ($plan->budgets as $budget)
                        @include('plans.partials.budget-card', ['budget' => $budget])
                    @empty
                        <div class="col-12 empty-placeholder-budgets" id="empty-budgets-msg">
                            <div class="card p-5 text-center text-muted">
                                <i class="bi bi-cash-stack fs-1 text-neon-yellow mb-2"></i>
                                <h5 class="text-white font-orbitron">NO BUDGET ALLOCATIONS REGISTERED</h5>
                                <p class="small mb-3">Declare expenditure limits, costs, and financial requirements for this mission.</p>
                                @if ($isMarshall)
                                    <div>
                                        <button type="button" class="btn btn-neon-yellow btn-sm" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                                            Add First Budget Item
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALS FOR CREATING COMPONENTS ==================== -->

<!-- Add Target Modal -->
<div class="modal fade" id="addTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('targets.store', $plan) }}" method="POST" id="addTargetForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-orbitron text-neon-cyan"><i class="bi bi-bullseye me-2"></i>ADD TARGET / OBJECTIVE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET DESCRIPTION *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Target description..." required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET QUANTITY *</label>
                            <input type="text" name="quantity" class="form-control" placeholder="e.g. 100, 5000" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT OF MEASURE</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g. users, hrs, %">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">PRIORITY *</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">LOW</option>
                                <option value="normal" selected>NORMAL</option>
                                <option value="high">HIGH</option>
                                <option value="critical">CRITICAL</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-cyan" id="addTargetSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save Target</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('resources.store', $plan) }}" method="POST" id="addResourceForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-orbitron text-neon-orange"><i class="bi bi-box-seam me-2"></i>ADD RESOURCE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">RESOURCE DESCRIPTION *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe resource needed..." required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">QUANTITY / ALLOCATION *</label>
                            <input type="text" name="quantity" class="form-control" placeholder="e.g. 4, 25000, 3" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT OF MEASURE</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g. Engineers, $, Servers">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="for" class="form-label text-muted small font-rajdhani text-uppercase">FOR (OPTIONAL)</label>
                        <select name="for" id="for" class="form-select">
                            <option value="">NONE (STANDALONE RESOURCE)</option>
                            @foreach ($plan->targetObjectives as $to)
                                <option value="{{ $to->id }}" {{ old('for') == $to->id ? 'selected' : '' }}>{{ $to->description }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="font-size: 0.7rem;">Optionally link this resource to a specific target or objective.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET DATE *</label>
                        <input type="date" name="target_date" class="form-control" value="{{ $plan->end_date->format('Y-m-d') }}" min="{{ $plan->start_date->format('Y-m-d') }}" max="{{ $plan->end_date->format('Y-m-d') }}" required>
                        <small class="text-muted" style="font-size: 0.7rem;">Plan schedule: {{ $plan->start_date->format('M d, Y') }} &ndash; {{ $plan->end_date->format('M d, Y') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-orange" id="addResourceSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save Resource</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Risk Modal -->
<div class="modal fade" id="addRiskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('risks.store', $plan) }}" method="POST" id="addRiskForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-orbitron text-neon-yellow"><i class="bi bi-shield-exclamation me-2"></i>ADD RISK MANAGEMENT ITEM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">IDENTIFIED RISK *</label>
                        <textarea name="risk" class="form-control" rows="3" placeholder="Risk description..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">POTENTIAL IMPACT *</label>
                        <select name="impact" class="form-select" required>
                            <option value="">SELECT IMPACT LEVEL</option>
                            <option value="Lv 1 - Only one target object is affected">Lv 1 - Only one target object is affected</option>
                            <option value="Lv 2 - At least 2 or more target objectives are affected">Lv 2 - At least 2 or more target objectives are affected</option>
                            <option value="Lv 3 - All target objectives are affected">Lv 3 - All target objectives are affected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">MITIGATION PLAN *</label>
                        <textarea name="mitigation" class="form-control" rows="2" placeholder="Mitigation plan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-orange" id="addRiskSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save Risk Item</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($isMarshall)
<!-- Add Budget Modal -->
<div class="modal fade" id="addBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('budgets.store', $plan) }}" method="POST" id="addBudgetForm">
            @csrf
            <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-yellow); box-shadow: 0 0 20px rgba(255, 230, 0, 0.2);">
                <div class="modal-header">
                    <h5 class="modal-title font-orbitron text-neon-yellow"><i class="bi bi-cash-stack me-2"></i>ADD BUDGET ALLOCATION</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 w-100">
                        <label class="form-label text-neon-yellow small font-rajdhani text-uppercase">DESCRIPTION *</label>
                        <textarea name="description" class="form-control w-100" rows="3" placeholder="Budget item or expenditure description..." required style="width: 100% !important; resize: vertical;"></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label text-neon-yellow small font-rajdhani text-uppercase">QUANTITY / ALLOCATION *</label>
                            <input type="number" step="any" min="0" name="quantity" class="form-control" placeholder="e.g., 5000" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT (OPTIONAL)</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g., USD, PHP">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-neon-cyan small font-rajdhani text-uppercase">FOR (OBJECTIVE / TARGET) (OPTIONAL)</label>
                        <select name="for" id="budget_for_select" class="form-select">
                            <option value="">None (Independent Budget Item)</option>
                            @foreach ($plan->targetObjectives as $to)
                                <option value="{{ $to->id }}">{{ Str::limit($to->description, 60) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-yellow font-rajdhani fw-bold" id="addBudgetSubmitBtn" style="color: #000000 !important;">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" style="color: #000000 !important;"></span>
                        <span class="btn-text" style="color: #000000 !important;">Save Budget Item</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Evaluation Weights Modal -->
<div class="modal fade" id="editWeightsModal" tabindex="-1" aria-labelledby="editWeightsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('plans.weights.update', $plan) }}" method="POST" id="editWeightsForm">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-orbitron text-neon-cyan" id="editWeightsModalLabel">
                        <i class="bi bi-sliders me-2"></i>EVALUATION WEIGHT CONFIGURATION
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Adjust component scoring weights. The combined total of Target, Resource, Risk, and Budget weights <strong>must equal exactly 100%</strong>. Default standard: 60% Target, 20% Resources, 10% Risk, 10% Budget.
                    </p>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-neon-cyan small font-rajdhani text-uppercase mb-0">
                                <i class="bi bi-bullseye me-1"></i>TARGET / OBJECTIVES WEIGHT (%)
                            </label>
                            <span class="badge" style="background: rgba(0, 240, 255, 0.15); color: var(--neon-cyan); border: 1px solid var(--neon-cyan);" id="badge-weight-target">{{ number_format($plan->target_weight, 0) }}%</span>
                        </div>
                        <input type="number" step="any" min="0" max="100" name="target_weight" id="input_target_weight" class="form-control weight-input-field" value="{{ $plan->target_weight }}" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-neon-orange small font-rajdhani text-uppercase mb-0">
                                <i class="bi bi-box-seam me-1"></i>RESOURCES WEIGHT (%)
                            </label>
                            <span class="badge" style="background: rgba(255, 140, 0, 0.15); color: var(--neon-orange); border: 1px solid var(--neon-orange);" id="badge-weight-resource">{{ number_format($plan->resource_weight, 0) }}%</span>
                        </div>
                        <input type="number" step="any" min="0" max="100" name="resource_weight" id="input_resource_weight" class="form-control weight-input-field" value="{{ $plan->resource_weight }}" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-neon-violet small font-rajdhani text-uppercase mb-0">
                                <i class="bi bi-shield-exclamation me-1"></i>RISK MANAGEMENT WEIGHT (%)
                            </label>
                            <span class="badge" style="background: rgba(168, 85, 247, 0.15); color: var(--neon-violet); border: 1px solid var(--neon-violet);" id="badge-weight-risk">{{ number_format($plan->risk_weight, 0) }}%</span>
                        </div>
                        <input type="number" step="any" min="0" max="100" name="risk_weight" id="input_risk_weight" class="form-control weight-input-field" value="{{ $plan->risk_weight }}" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-neon-yellow small font-rajdhani text-uppercase mb-0">
                                <i class="bi bi-cash-stack me-1"></i>BUDGET WEIGHT (%)
                            </label>
                            <span class="badge" style="background: rgba(255, 230, 0, 0.15); color: var(--neon-yellow); border: 1px solid var(--neon-yellow);" id="badge-weight-budget">{{ number_format($plan->budget_weight, 0) }}%</span>
                        </div>
                        <input type="number" step="any" min="0" max="100" name="budget_weight" id="input_budget_weight" class="form-control weight-input-field" value="{{ $plan->budget_weight }}" required>
                    </div>

                    <!-- Live Total Calculation Bar -->
                    <div class="p-2 rounded mt-3 d-flex justify-content-between align-items-center" id="weightsTotalBox" style="background: rgba(0, 255, 136, 0.1); border: 1px solid rgba(0, 255, 136, 0.3);">
                        <span class="font-rajdhani fw-bold small text-uppercase text-white">
                            <i class="bi bi-calculator me-1"></i>TOTAL WEIGHT:
                        </span>
                        <span class="font-orbitron fw-bold fs-6 text-neon-green" id="weightsTotalValue">100%</span>
                    </div>
                    <div id="weightsValidationAlert" class="text-neon-pink small mt-2 d-none">
                        <i class="bi bi-exclamation-circle me-1"></i><span id="weightsValidationAlertText">Total must equal exactly 100%.</span>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetWeightsDefaultBtn" style="font-size: 0.75rem;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default (60 / 20 / 10 / 10)
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-cyan" id="saveWeightsBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Update Weights</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- ==================== JAVASCRIPT: TAB SWITCHING & AJAX STATUS UPDATING ==================== -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // 1. Toast Notification Helper
        function showToast(message, isSuccess = true) {
            const toastEl = document.getElementById('statusToast');
            const toastBody = document.getElementById('statusToastBody');
            if (toastEl && toastBody) {
                toastBody.textContent = message;
                const icon = toastEl.querySelector('i');
                if (icon) {
                    icon.className = isSuccess 
                        ? 'bi bi-check-circle-fill text-neon-green fs-4 me-2' 
                        : 'bi bi-exclamation-triangle-fill text-neon-violet fs-4 me-2';
                }
                toastEl.classList.remove('d-none');
                if (window.bootstrap && window.bootstrap.Toast) {
                    const toast = new window.bootstrap.Toast(toastEl, { delay: 3000 });
                    toast.show();
                } else {
                    toastEl.classList.add('show');
                    setTimeout(() => toastEl.classList.remove('show'), 3000);
                }
            }
        }

        function calcComponentScore(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return 100.0;
            const selects = container.querySelectorAll('.ajax-status-select');
            const total = selects.length;
            let hit = 0, voidCount = 0;
            selects.forEach(s => {
                if (s.value === 'Hit') hit++;
                else if (s.value === 'Void') voidCount++;
            });
            const valid = total - voidCount;
            if (valid <= 0) return 100.0;
            return (hit / valid) * 100;
        }

        // 2. Metrics Recalculator
        function refreshMetrics() {
            let hit = 0, missed = 0, voidCount = 0, pending = 0;
            const allSelects = document.querySelectorAll('.ajax-status-select');
            allSelects.forEach(select => {
                const val = select.value;
                if (val === 'Hit') hit++;
                else if (val === 'Missed') missed++;
                else if (val === 'Void') voidCount++;
                else pending++;
            });
            const hitEl = document.getElementById('metric-hit-count');
            const missedEl = document.getElementById('metric-missed-count');
            const voidEl = document.getElementById('metric-void-count');
            const pendingEl = document.getElementById('metric-pending-count');

            if (missedEl) missedEl.textContent = missed;
            if (voidEl) voidEl.textContent = voidCount;
            if (pendingEl) pendingEl.textContent = pending;

            const scoreEl = document.getElementById('metric-overall-score');
            const progressEl = document.getElementById('metric-overall-progress');
            const targetScoreEl = document.getElementById('metric-target-score');
            const resourceScoreEl = document.getElementById('metric-resource-score');
            const riskScoreEl = document.getElementById('metric-risk-score');
            const budgetScoreEl = document.getElementById('metric-budget-score');
            const mobileScoreEl = document.getElementById('mobile-metric-score');
            const mobileHitEl = document.getElementById('mobile-hit-count');
            const mobileMissedEl = document.getElementById('mobile-missed-count');

            // If the plan record has no objective/target, resources, riskmanagement, or budgets, total score is zero
            if (allSelects.length === 0) {
                if (hitEl) hitEl.textContent = '0.0%';
                if (scoreEl) scoreEl.textContent = '0.0%';
                if (progressEl) progressEl.style.width = '0%';
                if (targetScoreEl) targetScoreEl.textContent = '0.0%';
                if (resourceScoreEl) resourceScoreEl.textContent = '0.0%';
                if (riskScoreEl) riskScoreEl.textContent = '0.0%';
                if (budgetScoreEl) budgetScoreEl.textContent = '0.0%';
                if (mobileScoreEl) mobileScoreEl.innerHTML = `<i class="bi bi-speedometer2 me-1"></i>0.0%`;
                return;
            }

            // Dynamic weighted score calculation based on configured weights
            const targetWeightPct = (parseFloat(document.getElementById('label-target-weight')?.textContent) || 60) / 100;
            const resourceWeightPct = (parseFloat(document.getElementById('label-resource-weight')?.textContent) || 20) / 100;
            const riskWeightPct = (parseFloat(document.getElementById('label-risk-weight')?.textContent) || 10) / 100;
            const budgetWeightPct = (parseFloat(document.getElementById('label-budget-weight')?.textContent) || 10) / 100;

            const targetScore = calcComponentScore('targets');
            const resourceScore = calcComponentScore('resources');
            const riskScore = calcComponentScore('risks');
            const budgetScore = calcComponentScore('budgets');
            const overallScore = (targetScore * targetWeightPct) + (resourceScore * resourceWeightPct) + (riskScore * riskWeightPct) + (budgetScore * budgetWeightPct);

            if (hitEl) hitEl.textContent = targetScore.toFixed(1) + '%';
            if (scoreEl) scoreEl.textContent = overallScore.toFixed(1) + '%';
            if (progressEl) progressEl.style.width = Math.min(100, Math.max(0, overallScore)) + '%';
            if (targetScoreEl) targetScoreEl.textContent = targetScore.toFixed(1) + '%';
            if (resourceScoreEl) resourceScoreEl.textContent = resourceScore.toFixed(1) + '%';
            if (riskScoreEl) riskScoreEl.textContent = riskScore.toFixed(1) + '%';
            if (budgetScoreEl) budgetScoreEl.textContent = budgetScore.toFixed(1) + '%';

            // Update mobile header bar metrics
            if (mobileScoreEl) mobileScoreEl.innerHTML = `<i class="bi bi-speedometer2 me-1"></i>${overallScore.toFixed(1)}%`;
            if (mobileHitEl) mobileHitEl.textContent = `${hit} Hit`;
            if (mobileMissedEl) mobileMissedEl.textContent = `${missed} Missed`;
        }

        // 3. Plan Overall Status AJAX Update
        const planSelect = document.getElementById('plan-status-select');
        const planSpinner = document.getElementById('plan-status-spinner');
        const planBadge = document.getElementById('plan-status-badge');

        if (planSelect) {
            let currentPlanStatus = planSelect.value;
            planSelect.addEventListener('change', async function () {
                const newStatus = planSelect.value;
                const url = planSelect.getAttribute('data-url');
                
                // Show loading indicator
                if (planSpinner) planSpinner.classList.remove('d-none');
                planSelect.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ status: newStatus })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        currentPlanStatus = newStatus;
                        // Update badge
                        if (planBadge) {
                            planBadge.textContent = data.status;
                            planBadge.className = data.badge_class + ' px-2 py-0.5 rounded-pill small';
                        }
                        showToast(data.message || 'Plan status updated to ' + newStatus);
                    } else {
                        planSelect.value = currentPlanStatus;
                        showToast(data.message || 'Status update failed.', false);
                    }
                } catch (error) {
                    planSelect.value = currentPlanStatus;
                    showToast(error.message || 'Failed to update status. Please try again.', false);
                } finally {
                    if (planSpinner) planSpinner.classList.add('d-none');
                    planSelect.disabled = false;
                }
            });
        }

        // 3b. Evaluation Weights Modal Handler (Marshalls)
        const editWeightsForm = document.getElementById('editWeightsForm');
        if (editWeightsForm) {
            const inputTarget = document.getElementById('input_target_weight');
            const inputResource = document.getElementById('input_resource_weight');
            const inputRisk = document.getElementById('input_risk_weight');
            const inputBudget = document.getElementById('input_budget_weight');
            const totalValueEl = document.getElementById('weightsTotalValue');
            const totalBoxEl = document.getElementById('weightsTotalBox');
            const alertEl = document.getElementById('weightsValidationAlert');
            const alertTextEl = document.getElementById('weightsValidationAlertText');
            const saveBtn = document.getElementById('saveWeightsBtn');
            const resetBtn = document.getElementById('resetWeightsDefaultBtn');

            const badgeTarget = document.getElementById('badge-weight-target');
            const badgeResource = document.getElementById('badge-weight-resource');
            const badgeRisk = document.getElementById('badge-weight-risk');
            const badgeBudget = document.getElementById('badge-weight-budget');

            function validateWeights() {
                const t = parseFloat(inputTarget?.value) || 0;
                const r = parseFloat(inputResource?.value) || 0;
                const k = parseFloat(inputRisk?.value) || 0;
                const b = parseFloat(inputBudget?.value) || 0;
                const total = Math.round((t + r + k + b) * 100) / 100;

                if (badgeTarget) badgeTarget.textContent = t + '%';
                if (badgeResource) badgeResource.textContent = r + '%';
                if (badgeRisk) badgeRisk.textContent = k + '%';
                if (badgeBudget) badgeBudget.textContent = b + '%';

                if (totalValueEl) totalValueEl.textContent = total + '%';

                const isValid = Math.abs(total - 100.0) < 0.01 && t >= 0 && r >= 0 && k >= 0 && b >= 0;

                if (isValid) {
                    if (totalBoxEl) {
                        totalBoxEl.style.background = 'rgba(0, 255, 136, 0.1)';
                        totalBoxEl.style.borderColor = 'rgba(0, 255, 136, 0.3)';
                    }
                    if (totalValueEl) {
                        totalValueEl.className = 'font-orbitron fw-bold fs-6 text-neon-green';
                    }
                    if (alertEl) alertEl.classList.add('d-none');
                    if (saveBtn) saveBtn.disabled = false;
                } else {
                    if (totalBoxEl) {
                        totalBoxEl.style.background = 'rgba(255, 0, 85, 0.1)';
                        totalBoxEl.style.borderColor = 'rgba(255, 0, 85, 0.3)';
                    }
                    if (totalValueEl) {
                        totalValueEl.className = 'font-orbitron fw-bold fs-6 text-neon-pink';
                    }
                    if (alertEl) {
                        alertEl.classList.remove('d-none');
                        if (alertTextEl) {
                            alertTextEl.textContent = `Weights must total 100% (currently ${total}%).`;
                        }
                    }
                    if (saveBtn) saveBtn.disabled = true;
                }
                return isValid;
            }

            [inputTarget, inputResource, inputRisk, inputBudget].forEach(input => {
                if (input) {
                    input.addEventListener('input', validateWeights);
                }
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    if (inputTarget) inputTarget.value = 60;
                    if (inputResource) inputResource.value = 20;
                    if (inputRisk) inputRisk.value = 10;
                    if (inputBudget) inputBudget.value = 10;
                    validateWeights();
                });
            }

            editWeightsForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!validateWeights()) return;

                const spinner = saveBtn.querySelector('.spinner-border');
                const btnText = saveBtn.querySelector('.btn-text');

                saveBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (btnText) btnText.textContent = 'Updating...';

                try {
                    const response = await fetch(editWeightsForm.action, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            target_weight: parseFloat(inputTarget.value),
                            resource_weight: parseFloat(inputResource.value),
                            risk_weight: parseFloat(inputRisk.value),
                            budget_weight: parseFloat(inputBudget.value)
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Update display labels in sidebar
                        const lblT = document.getElementById('label-target-weight');
                        const lblR = document.getElementById('label-resource-weight');
                        const lblK = document.getElementById('label-risk-weight');
                        const lblB = document.getElementById('label-budget-weight');

                        if (lblT) lblT.textContent = data.target_weight;
                        if (lblR) lblR.textContent = data.resource_weight;
                        if (lblK) lblK.textContent = data.risk_weight;
                        if (lblB) lblB.textContent = data.budget_weight;

                        refreshMetrics();

                        const modalEl = document.getElementById('editWeightsModal');
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        showToast(data.message || 'Weights updated successfully.');
                    } else {
                        const errMsg = (data.errors && Object.values(data.errors).flat().join(' ')) || data.message || 'Failed to update weights.';
                        showToast(errMsg, false);
                    }
                } catch (err) {
                    showToast('Failed to update weights: ' + err.message, false);
                } finally {
                    saveBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                    if (btnText) btnText.textContent = 'Update Weights';
                }
            });
        }

        // 4. Component Status AJAX Updates (Targets, Resources, Risks) - Event Delegation
        document.addEventListener('change', async function (e) {
            const select = e.target.closest('.ajax-status-select');
            if (!select) return;

            const url = select.getAttribute('data-url');
            const newStatus = select.value;
            const container = select.closest('.d-flex');
            const spinner = container ? container.querySelector('.status-spinner') : null;
            const card = select.closest('.card');
            const badge = card ? card.querySelector('.component-badge') : null;

            // Show spinner
            if (spinner) spinner.classList.remove('d-none');
            select.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Dynamically update the badge without page reload
                    if (badge) {
                        badge.textContent = data.status || 'Unevaluated';
                        badge.className = 'component-badge ' + data.badge_class + ' px-2 py-1 rounded';
                    }
                    refreshMetrics();
                    showToast(data.message || 'Evaluation status saved.');
                } else {
                    throw new Error(data.message || 'Update failed.');
                }
            } catch (error) {
                showToast('Failed to update status: ' + error.message, false);
            } finally {
                if (spinner) spinner.classList.add('d-none');
                select.disabled = false;
            }
        });

        // 5. Component Priority AJAX Updates (Targets) - Event Delegation
        document.addEventListener('change', async function (e) {
            const select = e.target.closest('.ajax-priority-select');
            if (!select) return;

            const url = select.getAttribute('data-url');
            const newPriority = select.value;
            const container = select.closest('.d-flex');
            const spinner = container ? container.querySelector('.priority-spinner') : null;
            const card = select.closest('.card');
            const badge = card ? card.querySelector('.target-priority-badge') : null;

            if (!newPriority) return;

            if (spinner) spinner.classList.remove('d-none');
            select.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ priority: newPriority })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (badge) {
                        badge.innerHTML = `<i class="bi bi-flag-fill me-1"></i>${data.priority.toUpperCase()}`;
                        badge.className = `target-priority-badge ${data.badge_class} px-2 py-0.5 rounded small me-1`;
                    }
                    showToast(data.message || 'Priority updated.');
                } else {
                    throw new Error(data.message || 'Priority update failed.');
                }
            } catch (error) {
                showToast('Failed to update priority: ' + error.message, false);
            } finally {
                if (spinner) spinner.classList.add('d-none');
                select.disabled = false;
            }
        });

        // 6. Component Tab Switching Handler
        const tabTriggers = document.querySelectorAll('#componentTabs button[data-bs-toggle="tab"]');
        tabTriggers.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                tabTriggers.forEach(t => t.classList.remove('active'));
                btn.classList.add('active');

                const targetId = btn.getAttribute('data-bs-target');
                document.querySelectorAll('#componentTabsContent .tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                const targetPane = document.querySelector(targetId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            });
        });

        // 7. JavaScript Chunked File Upload for Remarks with Staging (Up to 5 files, Max 5MB each)
        const CHUNK_SIZE = 1024 * 512; // 512 KB chunks
        const MAX_FILES = 5;
        const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

        function initRemarkForm(form) {
            const fileInput = form.querySelector('.remark-file-input');
            const attachBtn = form.querySelector('.remark-attach-btn');
            const attachBtnLabel = form.querySelector('.attach-btn-label');
            const stagedContainer = form.querySelector('.remark-staged-container');
            const stagedCountEl = form.querySelector('.staged-count');
            const stagedListEl = form.querySelector('.staged-items-list');
            const stagedInputsContainer = form.querySelector('.staged-attachment-ids-container');
            const progressContainer = form.querySelector('.remark-upload-progress');
            const progressBar = form.querySelector('.progress-bar');
            const progressStatus = form.querySelector('.progress-status-text');
            const progressPercent = form.querySelector('.progress-percent-text');
            const submitBtn = form.querySelector('button[type="submit"]');

            if (!fileInput) return;

            let stagedAttachments = [];

            function updateStagedUI() {
                if (stagedCountEl) stagedCountEl.textContent = stagedAttachments.length;
                if (stagedContainer) {
                    if (stagedAttachments.length > 0) {
                        stagedContainer.classList.remove('d-none');
                    } else {
                        stagedContainer.classList.add('d-none');
                    }
                }

                // Update attach button state
                if (attachBtn) {
                    if (stagedAttachments.length >= MAX_FILES) {
                        attachBtn.classList.add('disabled', 'opacity-50');
                        attachBtn.style.pointerEvents = 'none';
                        if (attachBtnLabel) attachBtnLabel.textContent = 'Max 5 files attached';
                    } else {
                        attachBtn.classList.remove('disabled', 'opacity-50');
                        attachBtn.style.pointerEvents = 'auto';
                        if (attachBtnLabel) attachBtnLabel.textContent = stagedAttachments.length > 0 ? 'Attach more files' : 'Attach files';
                    }
                }

                // Update hidden inputs
                if (stagedInputsContainer) {
                    stagedInputsContainer.innerHTML = '';
                    stagedAttachments.forEach(att => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'attachment_ids[]';
                        input.value = att.id;
                        stagedInputsContainer.appendChild(input);
                    });
                }
            }

            function renderStagedItems() {
                if (!stagedListEl) return;
                stagedListEl.innerHTML = '';
                const modal = form.closest('.modal');
                const threadList = modal ? modal.querySelector('.remarks-thread-list') : null;
                const neonColor = threadList ? (threadList.dataset.neonColor || '#00f0ff') : '#00f0ff';

                stagedAttachments.forEach(att => {
                    const item = document.createElement('div');
                    item.className = 'd-flex align-items-center justify-content-between p-2 rounded';
                    item.style.background = 'rgba(255, 255, 255, 0.04)';
                    item.style.border = '1px solid rgba(255, 255, 255, 0.12)';
                    item.innerHTML = `
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <i class="bi bi-paperclip fs-5" style="color: ${neonColor};"></i>
                            <span class="text-white small fw-bold text-truncate" title="${escapeHtml(att.original_name)}">${escapeHtml(att.original_name)}</span>
                            <span class="badge bg-secondary" style="font-size: 0.7rem;">${escapeHtml(att.file_size)}</span>
                            <span class="badge badge-hit small" style="font-size: 0.65rem;"><i class="bi bi-check-circle me-1"></i>Staged</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-staged-btn" data-att-id="${att.id}" title="Remove file">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </button>
                    `;

                    item.querySelector('.remove-staged-btn').addEventListener('click', function () {
                        stagedAttachments = stagedAttachments.filter(a => a.id !== att.id);
                        renderStagedItems();
                        updateStagedUI();
                    });

                    stagedListEl.appendChild(item);
                });

                updateStagedUI();
            }

            // File selection triggers chunk upload to staging
            fileInput.addEventListener('change', async function () {
                const selectedFiles = Array.from(fileInput.files);
                if (selectedFiles.length === 0) return;

                if (stagedAttachments.length + selectedFiles.length > MAX_FILES) {
                    showToast(`You can upload at most ${MAX_FILES} files in total (${stagedAttachments.length} already attached).`, false);
                    fileInput.value = '';
                    return;
                }

                // Check 5MB limit on each file
                for (const f of selectedFiles) {
                    if (f.size > MAX_FILE_SIZE) {
                        showToast(`File "${f.name}" exceeds the 5MB size limit (${(f.size / (1024 * 1024)).toFixed(1)}MB).`, false);
                        fileInput.value = '';
                        return;
                    }
                }

                progressContainer.classList.remove('d-none');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    for (let fileIdx = 0; fileIdx < selectedFiles.length; fileIdx++) {
                        const file = selectedFiles[fileIdx];
                        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                        const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

                        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                            const start = chunkIndex * CHUNK_SIZE;
                            const end = Math.min(start + CHUNK_SIZE, file.size);
                            const chunkBlob = file.slice(start, end);

                            const formData = new FormData();
                            formData.append('upload_id', uploadId);
                            formData.append('chunk_index', chunkIndex);
                            formData.append('total_chunks', totalChunks);
                            formData.append('file', chunkBlob, file.name);

                            const response = await fetch("{{ route('comments.upload.chunk') }}", {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: formData
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.message || `File ${file.name} chunk ${chunkIndex + 1} upload failed.`);
                            }

                            const fileProgress = Math.round(((chunkIndex + 1) / totalChunks) * 85);
                            const overallPercent = Math.round(((fileIdx * 100) + fileProgress) / selectedFiles.length);
                            progressBar.style.width = overallPercent + '%';
                            progressPercent.textContent = overallPercent + '%';
                            progressStatus.textContent = `[${fileIdx + 1}/${selectedFiles.length}] Staging "${file.name}" (${chunkIndex + 1}/${totalChunks})...`;
                        }

                        // Assemble file in staging and transfer to permanent storage
                        progressStatus.textContent = `[${fileIdx + 1}/${selectedFiles.length}] Assembling "${file.name}"...`;
                        const assembleResponse = await fetch("{{ route('comments.upload.assemble') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                upload_id: uploadId,
                                filename: file.name,
                                total_chunks: totalChunks,
                            })
                        });

                        const assembleData = await assembleResponse.json();

                        if (!assembleResponse.ok || !assembleData.success) {
                            throw new Error(assembleData.message || `Assembly for "${file.name}" failed.`);
                        }

                        stagedAttachments.push({
                            id: assembleData.attachment_id,
                            original_name: assembleData.original_name,
                            file_size: assembleData.file_size
                        });

                        renderStagedItems();
                    }

                    progressBar.style.width = '100%';
                    progressPercent.textContent = '100%';
                    progressStatus.textContent = `${selectedFiles.length} file(s) staged successfully!`;
                    showToast(`${selectedFiles.length} file(s) attached.`);
                } catch (error) {
                    showToast('Upload failed: ' + error.message, false);
                } finally {
                    fileInput.value = '';
                    setTimeout(() => {
                        progressContainer.classList.add('d-none');
                        progressBar.style.width = '0%';
                        progressPercent.textContent = '0%';
                    }, 1200);
                    if (submitBtn) submitBtn.disabled = false;
                }
            });

            // Ctrl+Enter / Cmd+Enter to submit textarea
            const textarea = form.querySelector('textarea[name="body"]');
            if (textarea) {
                textarea.addEventListener('keydown', function (e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        form.requestSubmit();
                    }
                });
            }

            // AJAX Form Submission (No page refresh, modal stays open)
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                if (!textarea) return;
                const bodyText = textarea.value.trim();
                if (!bodyText) {
                    showToast('Please type a remark before posting.', false);
                    textarea.focus();
                    return;
                }

                if (submitBtn && submitBtn.disabled) return;

                const origSubmitHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> POSTING...';
                }

                try {
                    const formData = new FormData(form);

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to post remark.');
                    }

                    // Clear textarea and staged attachments
                    textarea.value = '';
                    fileInput.value = '';
                    stagedAttachments = [];
                    renderStagedItems();

                    // Find thread list in modal
                    const modal = form.closest('.modal');
                    const threadList = modal ? modal.querySelector('.remarks-thread-list') : null;
                    if (threadList) {
                        const emptyState = threadList.querySelector('.remarks-empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }

                        const newCard = createCommentCard(data.comment, threadList.dataset);
                        threadList.appendChild(newCard);

                        // Smoothly scroll modal body to bottom
                        const modalBody = modal.querySelector('.modal-body');
                        if (modalBody) {
                            setTimeout(() => {
                                modalBody.scrollTo({
                                    top: modalBody.scrollHeight,
                                    behavior: 'smooth'
                                });
                            }, 50);
                        }
                    }

                    // Update count badges
                    const threadKey = `${data.commentable_type}-${data.commentable_id}`;
                    const count = data.comments_count;
                    document.querySelectorAll(`.modal-remarks-count[data-thread-key="${threadKey}"]`).forEach(el => {
                        el.textContent = count;
                    });
                    document.querySelectorAll(`.remarks-badge-count[data-thread-key="${threadKey}"]`).forEach(el => {
                        el.textContent = count;
                    });

                    showToast(data.message || 'Remark posted successfully.');
                } catch (error) {
                    showToast('Failed to post remark: ' + error.message, false);
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origSubmitHtml;
                    }
                }
            });
        }
        document.querySelectorAll('.remark-form').forEach(initRemarkForm);

        // 8. Helper Functions for Dynamic Comment Rendering
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createCommentCard(comment, dataset) {
            const card = document.createElement('div');
            card.className = 'card mb-3 p-3';

            const isAdmin = comment.user && comment.user.is_admin;
            const isMarshall = comment.user && comment.user.is_marshall;
            const borderStyle = isAdmin ? '#ff0055' : (isMarshall ? '#a855f7' : (dataset.borderClass || 'rgba(0, 240, 255, 0.3)'));
            card.style.background = '#120f24';
            card.style.border = `1px solid ${borderStyle}`;

            const avatarBg = isAdmin ? '#ff0055' : (isMarshall ? '#a855f7' : (dataset.neonColor || '#00f0ff'));
            const avatarColor = (isAdmin || isMarshall || dataset.neonColor === '#ff6b00') ? '#ffffff' : '#0a0814';
            const avatarInitial = escapeHtml(comment.user ? comment.user.initial : 'U');
            const userName = escapeHtml(comment.user ? comment.user.name : 'Unknown User');

            let roleBadge = '';
            if (isAdmin) {
                roleBadge = '<span class="badge badge-role-admin px-2 py-0.5 rounded-pill small">ADMIN</span>';
            } else if (isMarshall) {
                roleBadge = '<span class="badge badge-role-marshall px-2 py-0.5 rounded-pill small">MARSHALL</span>';
            } else if (comment.user && comment.user.is_executor) {
                roleBadge = '<span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">EXECUTOR</span>';
            }

            let deleteBtnHtml = '';
            if (comment.can_delete && comment.delete_url) {
                deleteBtnHtml = `
                    <form action="${comment.delete_url}" method="POST" class="d-inline comment-delete-form" onsubmit="return confirm('Delete this remark?');">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2" title="Delete Remark">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                `;
            }

            let attachmentsHtml = '';
            if (comment.attachments && comment.attachments.length > 0) {
                const neonColor = dataset.neonColor || '#00f0ff';
                const outlineClass = dataset.outlineClass || 'btn-neon-outline';
                const attItems = comment.attachments.map(att => {
                    const safeName = escapeHtml(att.original_name);
                    const safeSize = escapeHtml(att.file_size);
                    const safeDownload = att.download_url;

                    if (att.is_image) {
                        return `
                            <div class="comment-attachment-card d-inline-flex flex-column rounded overflow-hidden position-relative" style="background: rgba(18, 15, 36, 0.9); border: 1px solid ${dataset.borderClass || 'rgba(0, 240, 255, 0.35)'}; width: 124px;">
                                <div class="position-relative overflow-hidden cursor-pointer attachment-thumbnail-trigger"
                                     data-attachment-id="${att.id}"
                                     data-url="${att.url}"
                                     data-name="${safeName}"
                                     data-size="${safeSize}"
                                     data-download="${safeDownload}"
                                     style="height: 92px; background: #0a0814;"
                                     title="Click to view slideshow preview">
                                    <img src="${att.url}" alt="${safeName}" class="w-100 h-100 object-fit-cover" loading="lazy">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-40 opacity-0 hover-opacity-100 transition-opacity">
                                        <i class="bi bi-arrows-fullscreen text-white fs-5"></i>
                                    </div>
                                </div>
                                <div class="p-1 px-2 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03);">
                                    <div class="text-truncate me-1" style="max-width: 82px;" title="${safeName}">
                                        <span class="d-block small text-white text-truncate fw-bold" style="font-size: 0.68rem;">${safeName}</span>
                                        <small class="text-muted d-block" style="font-size: 0.62rem;">${safeSize}</small>
                                    </div>
                                    <a href="${safeDownload}" class="btn btn-sm btn-link p-0" style="color: ${neonColor};" title="Download">
                                        <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="d-inline-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); max-width: 240px;">
                                <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(255, 255, 255, 0.08);">
                                    <i class="bi ${att.icon_class || 'bi-file-earmark'} fs-5" style="color: ${neonColor};"></i>
                                </div>
                                <div class="text-truncate flex-grow-1" style="min-width: 0;">
                                    <span class="d-block small text-white text-truncate fw-bold" title="${safeName}">${safeName}</span>
                                    <small class="text-muted" style="font-size: 0.7rem;">${safeSize}</small>
                                </div>
                                <a href="${safeDownload}" class="btn btn-sm ${outlineClass} p-1 px-2 ms-1 flex-shrink-0" title="Download Attachment">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        `;
                    }
                }).join('');

                attachmentsHtml = `
                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex flex-wrap gap-2">
                        ${attItems}
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: ${avatarBg}; color: ${avatarColor};">
                            ${avatarInitial}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-white">${userName}</span>
                                ${roleBadge}
                            </div>
                            <small class="text-muted">${escapeHtml(comment.created_at_formatted)} (${escapeHtml(comment.created_at_human)})</small>
                        </div>
                    </div>
                    ${deleteBtnHtml}
                </div>
                <p class="mb-0 text-white mt-1 fs-6" style="white-space: pre-wrap;">${escapeHtml(comment.body)}</p>
                ${attachmentsHtml}
            `;

            return card;
        }

        // 9. Dynamic Comment Deletion (Without modal closure or page reload)
        document.addEventListener('submit', async function (e) {
            const form = e.target.closest('form');
            if (!form || !form.action.includes('/comments/')) return;
            const methodInput = form.querySelector('input[name="_method"]');
            if (!methodInput || methodInput.value !== 'DELETE') return;

            e.preventDefault();

            const card = form.closest('.card');
            const modal = form.closest('.modal');
            const threadList = modal ? modal.querySelector('.remarks-thread-list') : null;
            const threadKey = threadList ? threadList.dataset.threadKey : null;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    if (card) {
                        card.remove();
                    }

                    if (threadList) {
                        const remaining = threadList.querySelectorAll('.card').length;
                        if (threadKey) {
                            document.querySelectorAll(`.modal-remarks-count[data-thread-key="${threadKey}"]`).forEach(el => {
                                el.textContent = remaining;
                            });
                            document.querySelectorAll(`.remarks-badge-count[data-thread-key="${threadKey}"]`).forEach(el => {
                                el.textContent = remaining;
                            });
                        }

                        if (remaining === 0) {
                            const neonColor = threadList.dataset.neonColor || '#00f0ff';
                            threadList.innerHTML = `
                                <div class="text-center py-5 text-muted remarks-empty-state">
                                    <i class="bi bi-chat-square-text fs-1 d-block mb-2" style="color: ${neonColor};"></i>
                                    <h5 class="text-white font-orbitron">NO REMARKS RECORDED</h5>
                                    <p class="small">Enter evaluation feedback or progress notes below.</p>
                                </div>
                            `;
                        }
                    }

                    showToast('Comment deleted successfully.');
                } else {
                    throw new Error(data.message || 'Failed to delete comment.');
                }
            } catch (err) {
                showToast(err.message, false);
            }
        });

        // 10. AJAX Component Creation Handlers (Targets, Resources, Risks)
        function handleAjaxComponentForm(formId, modalId, rowId, emptyClass, countId, defaultSuccessMsg) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;
                const btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
                const originalText = btnText ? btnText.textContent : 'Save';

                // Set loading indicator
                if (submitBtn) submitBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (btnText) btnText.textContent = 'Saving...';

                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // 1. Remove empty placeholder if present
                        const row = document.getElementById(rowId);
                        if (row) {
                            const emptyEl = row.querySelector('.' + emptyClass) || row.querySelector('#' + emptyClass);
                            if (emptyEl) emptyEl.remove();

                            // 2. Append new card HTML
                            if (data.html) {
                                const tempWrapper = document.createElement('div');
                                tempWrapper.innerHTML = data.html.trim();
                                while (tempWrapper.firstChild) {
                                    const child = tempWrapper.firstChild;
                                    row.appendChild(child);
                                    if (child.nodeType === Node.ELEMENT_NODE) {
                                        const remarkForm = child.querySelector ? child.querySelector('.remark-form') : null;
                                        if (remarkForm) {
                                            initRemarkForm(remarkForm);
                                        }
                                    }
                                }
                            }
                        }

                        // 3. Update tab count
                        const countEl = document.getElementById(countId);
                        if (countEl && typeof data.count !== 'undefined') {
                            countEl.textContent = data.count;
                        }

                        // If a target was added, keep the resource "For" dropdown in sync
                        if (formId === 'addTargetForm') {
                            syncResourceForDropdown();
                        }

                        // 4. Update metrics & scores live
                        refreshMetrics();

                        // 5. Hide modal
                        const modalEl = document.getElementById(modalId);
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                            modal.hide();
                        }

                        // 6. Reset form
                        form.reset();

                        // 7. Toast notification
                        showToast(data.message || defaultSuccessMsg);
                    } else {
                        let errorMsg = data.message || 'Validation error.';
                        if (data.errors) {
                            const messages = Object.values(data.errors).flat();
                            errorMsg = messages.join(' ');
                        }
                        showToast(errorMsg, false);
                    }
                } catch (err) {
                    showToast('Failed to add component: ' + err.message, false);
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                    if (btnText) btnText.textContent = originalText;
                }
            });
        }

        function syncResourceForDropdown() {
            const forSelects = document.querySelectorAll('#for, #budget_for_select, select[name="for"], select[name="target_objective_id"]');
            if (forSelects.length === 0) return;

            const targetItems = document.querySelectorAll('#targets-row .target-item');
            const availableTargets = [];

            targetItems.forEach(item => {
                const id = item.dataset.targetId || (item.id ? item.id.replace('target-item-', '') : null);
                const descEl = item.querySelector('h6');
                const description = item.dataset.targetDescription || (descEl ? descEl.textContent.trim() : `Target #${id}`);
                if (id) {
                    availableTargets.push({ id: String(id), description: description });
                }
            });

            forSelects.forEach(forSelect => {
                const currentVal = forSelect.value;
                forSelect.innerHTML = '<option value="">NONE (STANDALONE)</option>';
                availableTargets.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.description;
                    if (String(t.id) === String(currentVal)) {
                        opt.selected = true;
                    }
                    forSelect.appendChild(opt);
                });
            });
        }

        const addResourceModalEl = document.getElementById('addResourceModal');
        if (addResourceModalEl) {
            addResourceModalEl.addEventListener('show.bs.modal', syncResourceForDropdown);
        }

        const addBudgetModalEl = document.getElementById('addBudgetModal');
        if (addBudgetModalEl) {
            addBudgetModalEl.addEventListener('show.bs.modal', syncResourceForDropdown);
        }

        handleAjaxComponentForm('addTargetForm', 'addTargetModal', 'targets-row', 'empty-placeholder-targets', 'targets-tab-count', 'Target / Objective added successfully.');
        handleAjaxComponentForm('addResourceForm', 'addResourceModal', 'resources-row', 'empty-placeholder-resources', 'resources-tab-count', 'Resource added successfully.');
        handleAjaxComponentForm('addRiskForm', 'addRiskModal', 'risks-row', 'empty-placeholder-risks', 'risks-tab-count', 'Risk Management item added successfully.');
        handleAjaxComponentForm('addBudgetForm', 'addBudgetModal', 'budgets-row', 'empty-placeholder-budgets', 'budgets-tab-count', 'Budget item added successfully.');
    });
</script>
@endsection

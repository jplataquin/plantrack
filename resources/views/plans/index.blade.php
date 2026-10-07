@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="font-orbitron fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-kanban text-neon-orange fs-4 fs-md-3"></i>
            PLANS <span class="text-neon-cyan">REPOSITORY</span>
        </h2>
        <p class="text-muted mb-0 font-rajdhani small">COMPREHENSIVE DIRECTORY OF ALL MISSION AND OPERATIONAL PLAN RECORDS</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('plans.create') }}" class="btn btn-neon-orange mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-plus-circle-fill"></i>
            <span>NEW PLAN RECORD</span>
        </a>
    </div>
</div>

<!-- Stats Overview Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md">
        <div class="card card-hover h-100 p-2 p-md-3">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-white" style="background: rgba(255, 255, 255, 0.08);">
                    <i class="bi bi-collection-fill fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">TOTAL PLANS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-white font-orbitron">{{ $stats['total_plans'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(0, 240, 255, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-cyan" style="background: rgba(0, 240, 255, 0.1);">
                    <i class="bi bi-broadcast fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-neon-cyan small font-rajdhani text-uppercase" style="font-size: 0.7rem;">OPEN PLANS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-neon-cyan font-orbitron">{{ $stats['open_plans'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(255, 230, 0, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-yellow" style="background: rgba(255, 230, 0, 0.1);">
                    <i class="bi bi-eye-fill fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-neon-yellow small font-rajdhani text-uppercase" style="font-size: 0.7rem;">IN REVIEW</div>
                    <div class="fs-5 fs-md-4 fw-bold text-neon-yellow font-orbitron">{{ $stats['review_plans'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(0, 255, 136, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-green" style="background: rgba(0, 255, 136, 0.1);">
                    <i class="bi bi-shield-fill-check fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-neon-green small font-rajdhani text-uppercase" style="font-size: 0.7rem;">CLOSED PLANS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-neon-green font-orbitron">{{ $stats['close_plans'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(108, 117, 125, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-muted" style="background: rgba(108, 117, 125, 0.1);">
                    <i class="bi bi-slash-circle-fill fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">VOID PLANS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-muted font-orbitron">{{ $stats['void_plans'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="card mb-4" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('plans.index') }}" class="row g-2 align-items-end">
            <!-- Search Text -->
            <div class="col-12 col-md-3">
                <label class="form-label font-rajdhani text-uppercase text-muted small mb-1">SEARCH TITLE / DETAILS</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search text-neon-cyan"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search title or description..." value="{{ request('search') }}">
                </div>
            </div>

            <!-- Project Filter -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label font-rajdhani text-uppercase text-muted small mb-1">PROJECT</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">ALL PROJECTS</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Executor Filter -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label font-rajdhani text-uppercase text-muted small mb-1">FIELD EXECUTOR</label>
                <select name="executor_id" class="form-select form-select-sm">
                    <option value="">ALL EXECUTORS</option>
                    @foreach ($executors as $executor)
                        <option value="{{ $executor->id }}" {{ request('executor_id') == $executor->id ? 'selected' : '' }}>
                            {{ $executor->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-8 col-sm-6 col-md-2">
                <label class="form-label font-rajdhani text-uppercase text-muted small mb-1">STATUS</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">ALL STATUSES</option>
                    <option value="Open" {{ request('status') === 'Open' ? 'selected' : '' }}>OPEN</option>
                    <option value="Review" {{ request('status') === 'Review' ? 'selected' : '' }}>IN REVIEW</option>
                    <option value="Close" {{ request('status') === 'Close' ? 'selected' : '' }}>CLOSED</option>
                    <option value="Void" {{ request('status') === 'Void' ? 'selected' : '' }}>VOID</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-4 col-sm-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-neon-cyan btn-sm w-100 font-rajdhani fw-bold" title="Apply Filters">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                @if (request()->hasAny(['search', 'project_id', 'executor_id', 'status']))
                    <a href="{{ route('plans.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- ==================== PLANS LIST TABLE ==================== -->
<div class="card p-0 overflow-hidden" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
    <div class="p-3 border-bottom border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" style="background: rgba(18, 15, 36, 0.7);">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-kanban-fill text-neon-orange fs-4"></i>
            <div>
                <h5 class="font-orbitron text-white mb-0">PLAN RECORDS DIRECTORY</h5>
                <small class="text-muted">Filtered results: {{ $plans->total() }} matching plan records</small>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="background: transparent;">
            <thead>
                <tr class="border-secondary text-muted small font-rajdhani text-uppercase" style="background: rgba(255, 255, 255, 0.02);">
                    <th class="ps-3" style="min-width: 250px;">PLAN TITLE</th>
                    <th style="min-width: 180px;">PROJECT</th>
                    <th style="min-width: 180px;">ASSIGNED EXECUTOR</th>
                    <th class="text-center" style="width: 120px;">STATUS</th>
                    <th class="text-center" style="width: 110px;">SCORE</th>
                    <th class="text-center" style="min-width: 150px;">TIMELINE</th>
                    <th class="pe-3 text-end" style="width: 100px;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr class="border-secondary">
                        <!-- Plan Title -->
                        <td class="ps-3">
                            <a href="{{ route('plans.show', $plan) }}" class="fw-bold text-white text-decoration-none user-profile-link font-rajdhani fs-6 d-block text-truncate" style="max-width: 320px;" title="{{ $plan->title }}">
                                {{ $plan->title }}
                            </a>
                            @if ($plan->description)
                                <small class="text-muted text-truncate d-block" style="max-width: 320px; font-size: 0.75rem;">
                                    {{ Str::limit($plan->description, 60) }}
                                </small>
                            @endif
                        </td>

                        <!-- Project -->
                        <td>
                            @if ($plan->project)
                                <a href="{{ route('projects.show', $plan->project) }}" class="badge text-decoration-none text-truncate d-inline-block font-rajdhani" style="max-width: 180px; background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3);">
                                    <i class="bi bi-folder-fill me-1"></i>{{ $plan->project->name }}
                                </a>
                            @else
                                <span class="text-muted small fst-italic">No Project</span>
                            @endif
                        </td>

                        <!-- Assigned Executor -->
                        <td>
                            @if ($plan->executor)
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('profile.show', $plan->executor) }}" class="text-decoration-none flex-shrink-0" title="View {{ $plan->executor->name }}'s Profile">
                                        @if ($plan->executor->profile_picture)
                                            <img src="{{ asset('storage/' . $plan->executor->profile_picture) }}" alt="{{ $plan->executor->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 28px; height: 28px; border: 1px solid var(--neon-cyan);">
                                        @else
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-dark" style="width: 28px; height: 28px; background: #00f0ff; font-size: 0.75rem;">
                                                {{ strtoupper(substr($plan->executor->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </a>
                                    <div class="overflow-hidden">
                                        <a href="{{ route('profile.show', $plan->executor) }}" class="text-white text-decoration-none user-profile-link small fw-bold text-truncate d-block">
                                            {{ $plan->executor->name }}
                                        </a>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">Unassigned</span>
                            @endif
                        </td>

                        <!-- Status Badge -->
                        <td class="text-center">
                            <span class="{{ $plan->status_badge_class }} px-2 py-0.5 rounded-pill small font-rajdhani fw-bold">
                                {{ strtoupper($plan->status) }}
                            </span>
                        </td>

                        <!-- Evaluation Score -->
                        <td class="text-center font-orbitron">
                            @if ($plan->status === 'Close')
                                <span class="text-neon-green fw-bold small">
                                    {{ number_format($plan->evaluation_score, 1) }}%
                                </span>
                            @elseif ($plan->status === 'Review')
                                <span class="text-neon-yellow small">
                                    {{ number_format($plan->evaluation_score, 1) }}%
                                </span>
                            @else
                                <span class="text-muted small">
                                    &mdash;
                                </span>
                            @endif
                        </td>

                        <!-- Timeline -->
                        <td class="text-center font-rajdhani small text-muted">
                            <div>{{ $plan->start_date->format('M d, Y') }} &rarr; {{ $plan->end_date->format('M d, Y') }}</div>
                        </td>

                        <!-- Action -->
                        <td class="pe-3 text-end">
                            <a href="{{ route('plans.show', $plan) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold p-1 px-2" title="Inspect Plan Details">
                                <i class="bi bi-eye"></i>
                                <span class="d-none d-lg-inline ms-1">Inspect</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-kanban display-4 d-block mb-2 opacity-50"></i>
                            <h6 class="font-orbitron text-white">NO PLAN RECORDS FOUND</h6>
                            <p class="small mb-3">No plans matching the current filter parameters were found.</p>
                            <div>
                                <a href="{{ route('plans.create') }}" class="btn btn-neon-orange btn-sm">
                                    Create New Plan
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($plans->hasPages())
        <div class="p-3 border-top border-secondary d-flex justify-content-center">
            {{ $plans->links() }}
        </div>
    @endif
</div>
@endsection

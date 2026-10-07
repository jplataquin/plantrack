@extends('layouts.app')

@section('content')
<!-- Header & Back Navigation -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center">
        <a href="{{ route('projects.index') }}" class="btn btn-neon-outline btn-sm me-3">
            <i class="bi bi-arrow-left"></i> BACK
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="font-orbitron fw-bold text-white mb-0">{{ $project->name }}</h2>
                <span class="{{ $project->status_badge_class }} px-2 py-1 rounded-pill font-rajdhani fw-bold small">
                    {{ strtoupper($project->status) }}
                </span>
            </div>
            <p class="text-muted mb-0 font-rajdhani small">PROJECT INITIATIVE PROFILE & LINKED PLAN RECORDS</p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($isMarshall)
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-neon-outline-violet mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-pencil-square text-neon-violet"></i>
                <span>EDIT PROJECT</span>
            </a>
            @if ($project->status === 'Active')
                <a href="{{ route('plans.create', ['project_id' => $project->id]) }}" class="btn btn-neon-orange mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>ASSIGN NEW PLAN</span>
                </a>
            @endif
        @endif
    </div>
</div>

<!-- Project Overview Card -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card p-3 h-100" style="border-color: rgba(168, 85, 247, 0.4);">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small font-rajdhani text-uppercase">PROJECT IDENTIFIER</span>
                <span class="font-orbitron text-neon-violet fw-bold">#{{ $project->id }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small font-rajdhani text-uppercase">CURRENT STATUS</span>
                <span class="{{ $project->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ $project->status }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small font-rajdhani text-uppercase">CREATED DATE</span>
                <span class="text-white small">{{ $project->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <span class="text-muted small font-rajdhani text-uppercase">LAST MODIFIED</span>
                <span class="text-white small">{{ $project->updated_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-8">
        <div class="card p-3 h-100">
            <h5 class="font-orbitron text-white mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-neon-cyan"></i>
                <span>PROJECT DISPATCH SUMMARY</span>
            </h5>
            <p class="text-muted small mb-3">
                This project aggregates strategic plan records executed by operatives across the organization.
                @if ($project->status === 'Active')
                    <span class="text-neon-green">Status is Active: Marshalls may link new and existing plan records to this project.</span>
                @else
                    <span class="text-neon-orange">Status is Deactive: Existing linked plans remain tracked, but new plans cannot select this project.</span>
                @endif
            </p>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="p-2 rounded" style="background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.25);">
                        <div class="fs-4 font-orbitron fw-bold text-neon-cyan">{{ $project->planRecords->count() }}</div>
                        <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">TOTAL PLANS</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded" style="background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.25);">
                        <div class="fs-4 font-orbitron fw-bold text-neon-cyan">{{ $project->planRecords->where('status', 'Open')->count() }}</div>
                        <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">OPEN PLANS</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded" style="background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.25);">
                        <div class="fs-4 font-orbitron fw-bold text-neon-green">{{ $project->planRecords->where('status', 'Close')->count() }}</div>
                        <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">CLOSED PLANS</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Linked Plan Records Section -->
<div class="card p-0 overflow-hidden mb-4">
    <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center" style="background: rgba(18, 15, 36, 0.7);">
        <h5 class="font-orbitron text-white mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-kanban text-neon-orange"></i>
            <span>LINKED PLAN RECORDS ({{ $project->planRecords->count() }})</span>
        </h5>
        @if ($isMarshall && $project->status === 'Active')
            <a href="{{ route('plans.create', ['project_id' => $project->id]) }}" class="btn btn-neon-orange btn-sm">
                <i class="bi bi-plus-lg me-1"></i> ADD PLAN
            </a>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="background: transparent;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <th class="font-rajdhani text-uppercase text-muted small py-3 ps-3">PLAN TITLE</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3">ASSIGNED EXECUTOR</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">STATUS</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3">TIMELINE</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">EVAL SCORE</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 pe-3 text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($project->planRecords as $plan)
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                        <td class="ps-3">
                            <a href="{{ route('plans.show', $plan) }}" class="fw-bold text-white text-decoration-none d-block">
                                {{ $plan->title }}
                            </a>
                            @if ($plan->description)
                                <small class="text-muted text-truncate d-block" style="max-width: 320px;">{{ $plan->description }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($plan->executor)
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('profile.show', $plan->executor) }}" class="text-white small fw-bold text-decoration-none user-profile-link" title="View {{ $plan->executor->name }}'s Profile">
                                        {{ $plan->executor->name }}
                                    </a>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">Unassigned</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="{{ $plan->status_badge_class }} px-2 py-0.5 rounded-pill small">
                                {{ strtoupper($plan->status) }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            <div>{{ $plan->start_date->format('M d, Y') }} &rarr; {{ $plan->end_date->format('M d, Y') }}</div>
                            <small class="text-neon-cyan">{{ $plan->start_date->diffInDays($plan->end_date) }} Days</small>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);">
                                {{ number_format($plan->evaluation_score, 1) }}%
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <a href="{{ route('plans.show', $plan) }}" class="btn btn-action btn-neon-outline" title="Inspect Plan">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-kanban fs-2 d-block mb-2 text-muted"></i>
                            <h6 class="font-orbitron text-white">NO PLAN RECORDS LINKED TO THIS PROJECT</h6>
                            <p class="small text-muted mb-0">Plan records can be assigned to this project when created or edited by a Marshall.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="font-orbitron fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-neon-cyan fs-4 fs-md-3"></i>
            EXECUTOR <span class="text-neon-orange">DIRECTORY</span>
        </h2>
        <p class="text-muted mb-0 font-rajdhani small">SELECT AN EXECUTOR CARD TO INSPECT AND EVALUATE ASSIGNED PLAN RECORDS</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($isMarshall)
            <a href="{{ route('executors.create') }}" class="btn btn-neon-cyan mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-person-plus-fill"></i>
                <span>NEW EXECUTOR</span>
            </a>
        @endif
        <a href="{{ route('plans.create') }}" class="btn btn-neon-orange mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-plus-circle-fill"></i>
            <span>NEW PLAN RECORD</span>
        </a>
    </div>
</div>

<!-- Stats Overview Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-hover h-100 p-2 p-md-3">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-cyan" style="background: rgba(0, 240, 255, 0.1);">
                    <i class="bi bi-person-badge fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">EXECUTORS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-white font-orbitron">{{ $stats['total_executors'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
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
    <div class="col-6 col-md-3">
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
</div>

<!-- Search & Filters -->
<div class="card mb-4">
    <div class="card-body p-2 p-md-3">
        <form method="GET" action="{{ route('executors.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-9 col-lg-10">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search text-neon-cyan"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search executor name or email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-12 col-md-3 col-lg-2 d-flex">
                <button type="submit" class="btn btn-neon-cyan w-100">
                    <i class="bi bi-search me-1"></i> SEARCH
                </button>
                @if (request()->filled('search'))
                    <a href="{{ route('executors.index') }}" class="btn btn-neon-outline ms-2" title="Reset search">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- ==================== EXECUTORS IN CARD FORMAT (3 PER ROW ON DESKTOP) ==================== -->
<div class="row g-3">
    @forelse ($executors as $executor)
        @php
            $plansCount = $executor->planRecords->count();
            $openCount = $executor->planRecords->where('status', 'Open')->count();
            $reviewCount = $executor->planRecords->where('status', 'Review')->count();
            $closeCount = $executor->planRecords->where('status', 'Close')->count();
        @endphp
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-hover h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(0, 240, 255, 0.3);">
                <div>
                    <!-- Executor Profile Header -->
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary">
                        <a href="{{ route('profile.show', $executor) }}" class="text-decoration-none flex-shrink-0" title="View {{ $executor->name }}'s Profile">
                            @if ($executor->profile_picture)
                                <img src="{{ asset('storage/' . $executor->profile_picture) }}" alt="{{ $executor->name }}" class="rounded-circle object-fit-cover shadow-sm executor-avatar-img-{{ $executor->id }}" style="width: 46px; height: 46px; border: 2px solid var(--neon-cyan);">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5 executor-avatar-initials-{{ $executor->id }}" style="width: 46px; height: 46px; background: #00f0ff; color: #0a0814;">
                                    {{ strtoupper(substr($executor->name, 0, 1)) }}
                                </div>
                            @endif
                        </a>
                        <div class="overflow-hidden flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold text-white mb-0 font-rajdhani text-truncate">
                                    <a href="{{ route('profile.show', $executor) }}" class="text-white text-decoration-none user-profile-link" title="View {{ $executor->name }}'s Profile">
                                        {{ $executor->name }}
                                    </a>
                                </h5>
                                <span class="badge badge-role-executor px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">
                                    EXECUTOR
                                </span>
                            </div>
                            <small class="text-muted text-truncate d-block">{{ $executor->email }}</small>
                            @if ($executor->must_reset_password)
                                <div class="mt-1">
                                    <span class="badge" style="background: rgba(255, 230, 0, 0.15); border: 1px solid rgba(255, 230, 0, 0.4); color: #ffe600; font-size: 0.65rem;">
                                        <i class="bi bi-key me-1"></i> PENDING FIRST LOGIN
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Plans Status Counters Bar -->
                    <div class="d-flex justify-content-between align-items-center p-2 rounded mb-3" style="background: rgba(255, 255, 255, 0.03);">
                        <div class="text-center flex-fill border-end border-dark">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">ASSIGNED</span>
                            <span class="fw-bold font-orbitron text-white fs-6">{{ $plansCount }}</span>
                        </div>
                        <div class="text-center flex-fill border-end border-dark">
                            <span class="text-neon-cyan d-block" style="font-size: 0.68rem;">OPEN</span>
                            <span class="fw-bold font-orbitron text-neon-cyan fs-6">{{ $openCount }}</span>
                        </div>
                        <div class="text-center flex-fill border-end border-dark">
                            <span class="text-neon-yellow d-block" style="font-size: 0.68rem;">REVIEW</span>
                            <span class="fw-bold font-orbitron text-neon-yellow fs-6">{{ $reviewCount }}</span>
                        </div>
                        <div class="text-center flex-fill">
                            <span class="text-neon-green d-block" style="font-size: 0.68rem;">CLOSED</span>
                            <span class="fw-bold font-orbitron text-neon-green fs-6">{{ $closeCount }}</span>
                        </div>
                    </div>

                    <!-- Performance Summary -->
                    <div class="p-2 rounded mb-3" style="background: rgba(255, 255, 255, 0.02); font-size: 0.8rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted"><i class="bi bi-activity text-neon-cyan me-1"></i>Activity Status:</span>
                            @if ($openCount > 0 || $reviewCount > 0)
                                <span class="text-neon-cyan fw-bold">Active in {{ $openCount + $reviewCount }} Plan(s)</span>
                            @else
                                <span class="text-muted">Standby / All Complete</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-check2-circle text-neon-green me-1"></i>Completed Plans:</span>
                            <span class="text-neon-green fw-bold">{{ $closeCount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Actions with VIEW ALL PLAN RECORDS Button -->
                <div class="pt-3 border-top border-secondary">
                    <a href="{{ route('executors.show', $executor) }}" class="btn btn-neon-cyan w-100 font-rajdhani fw-bold d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-kanban fs-6"></i>
                        <span>VIEW ALL PLAN RECORDS ({{ $plansCount }})</span>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                <i class="bi bi-person-x fs-1 text-neon-cyan mb-2"></i>
                <h5 class="text-white font-orbitron">NO EXECUTORS FOUND</h5>
                <p class="small mb-3">No operatives matching the query were found.</p>
                <div>
                    <a href="{{ route('plans.create') }}" class="btn btn-neon-orange btn-sm">
                        Create New Plan
                    </a>
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection

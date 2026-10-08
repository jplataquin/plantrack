@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 py-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom border-secondary">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge badge-role-admin px-2 py-0.5 rounded-pill small">
                    <i class="bi bi-shield-lock-fill me-1"></i> ADMIN CONSOLE
                </span>
                <span class="badge" style="background: rgba(255, 0, 85, 0.15); color: #ff0055; border: 1px solid rgba(255, 0, 85, 0.4);">
                    <i class="bi bi-cpu-fill me-1"></i> ROOT LEVEL CLEARANCE
                </span>
            </div>
            <h2 class="font-orbitron fw-bold text-white mb-0">USER CONTROL MATRIX</h2>
            <p class="text-muted small mb-0">System-wide operative management, clearance orchestration, and account provisioning.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.marshalls.create') }}" class="btn btn-neon-violet font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                <i class="bi bi-shield-shaded"></i> PROVISION MARSHALL
            </a>
            <a href="{{ route('admin.executors.create') }}" class="btn btn-neon-cyan font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                <i class="bi bi-lightning-charge-fill"></i> PROVISION EXECUTOR
            </a>
        </div>
    </div>

    <!-- Telemetry Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(255, 255, 255, 0.2);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">TOTAL OPERATIVES</span>
                    <i class="bi bi-people text-white"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-white">{{ $metrics['total_users'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(255, 0, 85, 0.4);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">ADMINISTRATORS</span>
                    <i class="bi bi-shield-lock-fill text-neon-pink"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-neon-pink">{{ $metrics['total_admins'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(168, 85, 247, 0.4);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">MARSHALLS</span>
                    <i class="bi bi-shield-shaded text-neon-violet"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-neon-violet">{{ $metrics['total_marshalls'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(0, 240, 255, 0.4);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">EXECUTORS</span>
                    <i class="bi bi-lightning-charge text-neon-cyan"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-neon-cyan">{{ $metrics['total_executors'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(255, 107, 0, 0.4);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">TOTAL PLANS</span>
                    <i class="bi bi-kanban text-neon-orange"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-neon-orange">{{ $metrics['total_plans'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card card-hover h-100 p-3" style="border-color: rgba(0, 255, 136, 0.4);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-rajdhani text-uppercase">ACTIVE PROJECTS</span>
                    <i class="bi bi-folder-check text-neon-green"></i>
                </div>
                <div class="font-orbitron fw-bold fs-4 text-neon-green">{{ $metrics['total_projects'] }}</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card p-3 mb-4" style="background: #120f24; border: 1px solid rgba(255, 255, 255, 0.1);">
        <form action="{{ route('admin.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control" placeholder="Search by operative name or email..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="d-flex flex-wrap gap-1">
                    <a href="{{ route('admin.index', array_merge(request()->except('role', 'page'), [])) }}" class="btn btn-sm {{ !request('role') ? 'btn-neon-outline active' : 'btn-outline-secondary' }}">
                        ALL ({{ $metrics['total_users'] }})
                    </a>
                    <a href="{{ route('admin.index', array_merge(request()->except('page'), ['role' => 'Marshall'])) }}" class="btn btn-sm {{ request('role') === 'Marshall' ? 'btn-neon-violet' : 'btn-outline-secondary' }}">
                        MARSHALLS ({{ $metrics['total_marshalls'] }})
                    </a>
                    <a href="{{ route('admin.index', array_merge(request()->except('page'), ['role' => 'Executor'])) }}" class="btn btn-sm {{ request('role') === 'Executor' ? 'btn-neon-cyan' : 'btn-outline-secondary' }}">
                        EXECUTORS ({{ $metrics['total_executors'] }})
                    </a>
                    <a href="{{ route('admin.index', array_merge(request()->except('page'), ['role' => 'Admin'])) }}" class="btn btn-sm {{ request('role') === 'Admin' ? 'btn-danger' : 'btn-outline-secondary' }}">
                        ADMINS ({{ $metrics['total_admins'] }})
                    </a>
                </div>
            </div>

            <div class="col-12 col-md-3 text-md-end">
                <button type="submit" class="btn btn-neon-outline btn-sm">
                    <i class="bi bi-funnel me-1"></i> APPLY FILTER
                </button>
                @if (request()->hasAny(['search', 'role']))
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm ms-1">
                        <i class="bi bi-x-circle me-1"></i> RESET
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Operatives Directory Table -->
    <div class="card" style="background: #120f24; border: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="card-header border-secondary d-flex justify-content-between align-items-center py-3 bg-transparent">
            <h5 class="font-orbitron fw-bold text-white mb-0">
                <i class="bi bi-shield-check text-neon-pink me-2"></i>OPERATIVE DIRECTORY
                <span class="badge bg-secondary font-monospace ms-2">{{ $users->total() }}</span>
            </h5>
            <span class="text-muted small font-rajdhani text-uppercase">ADMIN CONTROL ACCESS</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-white">
                <thead style="background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.15);">
                    <tr class="font-rajdhani text-uppercase text-muted small">
                        <th class="ps-3">OPERATIVE</th>
                        <th>ASSIGNED ROLE</th>
                        <th>SECURITY KEY STATUS</th>
                        <th>ASSIGNED PLANS</th>
                        <th>REGISTERED ON</th>
                        <th class="text-end pe-3">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                            <td class="ps-3 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    @if ($u->profile_picture)
                                        <img src="{{ asset('storage/' . $u->profile_picture) }}" alt="{{ $u->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 40px; height: 40px; border: 2px solid {{ $u->hasRole('Admin') ? '#ff0055' : ($u->hasRole('Marshall') ? 'var(--neon-violet)' : 'var(--neon-cyan)') }};">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; background: {{ $u->hasRole('Admin') ? '#ff0055' : ($u->hasRole('Marshall') ? '#a855f7' : '#00f0ff') }}; color: {{ $u->hasRole('Executor') ? '#0a0814' : '#ffffff' }}; font-size: 1.1rem;">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('profile.show', $u) }}" class="fw-bold text-white text-decoration-none d-block">
                                            {{ $u->name }}
                                        </a>
                                        <small class="text-muted font-monospace">{{ $u->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($u->hasRole('Admin'))
                                    <span class="badge badge-role-admin px-2 py-1 rounded-pill small">
                                        <i class="bi bi-shield-lock-fill me-1"></i> ADMIN
                                    </span>
                                @elseif ($u->hasRole('Marshall'))
                                    <span class="badge badge-role-marshall px-2 py-1 rounded-pill small">
                                        <i class="bi bi-shield-check me-1"></i> MARSHALL
                                    </span>
                                @elseif ($u->hasRole('Executor'))
                                    <span class="badge badge-role-executor px-2 py-1 rounded-pill small">
                                        <i class="bi bi-lightning-charge me-1"></i> EXECUTOR
                                    </span>
                                @else
                                    <span class="badge bg-secondary px-2 py-1 rounded-pill small">NONE</span>
                                @endif
                            </td>
                            <td>
                                @if ($u->must_reset_password)
                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2 py-1 rounded-pill small">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> TEMP KEY (PENDING RESET)
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill small">
                                        <i class="bi bi-check-circle-fill me-1"></i> ACTIVE
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($u->plan_records_count > 0)
                                    <a href="{{ route('executors.show', $u) }}" class="badge bg-primary bg-opacity-25 text-neon-cyan border border-info border-opacity-50 px-2 py-1 rounded-pill text-decoration-none">
                                        {{ $u->plan_records_count }} PLAN(S) &rarr;
                                    </a>
                                @else
                                    <span class="text-muted small">0 Plans</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small font-rajdhani">{{ $u->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('profile.show', $u) }}" class="btn btn-sm btn-outline-info" title="View Operative Dossier">
                                        <i class="bi bi-person-lines-fill"></i> Dossier
                                    </a>
                                    @if ($u->hasRole('Executor') || $u->plan_records_count > 0)
                                        <a href="{{ route('executors.show', $u) }}" class="btn btn-sm btn-outline-primary" title="View Assigned Plans">
                                            <i class="bi bi-kanban"></i> Plans
                                        </a>
                                    @endif
                                    @if (! $u->hasRole('Admin') || $u->id === Auth::id())
                                        <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-warning" title="Edit Operative Profile & Reset Key">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary opacity-50" disabled title="Administrator accounts cannot be modified">
                                            <i class="bi bi-lock-fill"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                                No operatives match the current search or filter parameters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-transparent border-secondary py-3 d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

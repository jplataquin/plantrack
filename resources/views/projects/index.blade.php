@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="font-orbitron fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-folder-fill text-neon-violet fs-4 fs-md-3"></i>
            PROJECT <span class="text-neon-violet">DIRECTORY</span>
        </h2>
        <p class="text-muted mb-0 font-rajdhani small">MANAGE ORGANIZATIONAL INITIATIVES AND PLAN RECORD ASSIGNMENTS</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($isMarshall)
            <a href="{{ route('projects.create') }}" class="btn btn-neon-outline-violet mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-plus-circle-fill text-neon-violet"></i>
                <span>NEW PROJECT</span>
            </a>
        @endif
        <a href="{{ route('plans.create') }}" class="btn btn-neon-orange mobile-w-100 d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>NEW PLAN RECORD</span>
        </a>
    </div>
</div>

<!-- Stats Overview Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(168, 85, 247, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-violet" style="background: rgba(168, 85, 247, 0.1);">
                    <i class="bi bi-folder-fill fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">TOTAL PROJECTS</div>
                    <div class="fs-5 fs-md-4 fw-bold text-white font-orbitron">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(0, 255, 136, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-neon-green" style="background: rgba(0, 255, 136, 0.1);">
                    <i class="bi bi-check2-circle fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-neon-green small font-rajdhani text-uppercase" style="font-size: 0.7rem;">ACTIVE</div>
                    <div class="fs-5 fs-md-4 fw-bold text-neon-green font-orbitron">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <div class="card card-hover h-100 p-2 p-md-3" style="border-color: rgba(148, 163, 184, 0.4);">
            <div class="d-flex align-items-center">
                <div class="rounded-3 p-2 me-2 text-muted" style="background: rgba(148, 163, 184, 0.1);">
                    <i class="bi bi-dash-circle fs-4 fs-md-3"></i>
                </div>
                <div>
                    <div class="text-muted small font-rajdhani text-uppercase" style="font-size: 0.7rem;">DEACTIVE</div>
                    <div class="fs-5 fs-md-4 fw-bold text-white font-orbitron">{{ $stats['deactive'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Status Filter -->
<div class="card p-3 mb-4">
    <form action="{{ route('projects.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-6 col-lg-7">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-secondary text-neon-cyan">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="search" class="form-control" placeholder="Search project name..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-8 col-md-4 col-lg-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">ALL STATUSES</option>
                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>ACTIVE</option>
                <option value="Deactive" {{ request('status') === 'Deactive' ? 'selected' : '' }}>DEACTIVE</option>
            </select>
        </div>
        <div class="col-4 col-md-2 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn btn-neon-outline flex-grow-1">FILTER</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Projects Table Card -->
<div class="card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="background: transparent;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(168, 85, 247, 0.3); background: rgba(18, 15, 36, 0.7);">
                    <th class="font-rajdhani text-uppercase text-muted small py-3 ps-3">ID</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3">PROJECT NAME</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">STATUS</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">ASSIGNED PLANS</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3">CREATED</th>
                    <th class="font-rajdhani text-uppercase text-muted small py-3 pe-3 text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                        <td class="ps-3 font-orbitron small text-muted">#{{ $project->id }}</td>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="fw-bold text-white text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-folder text-neon-violet"></i>
                                <span>{{ $project->name }}</span>
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="{{ $project->status_badge_class }} px-2 py-1 rounded-pill font-rajdhani fw-bold small">
                                {{ strtoupper($project->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="background: rgba(0, 240, 255, 0.1); color: var(--neon-cyan); border: 1px solid rgba(0, 240, 255, 0.3);">
                                <i class="bi bi-kanban me-1"></i>{{ $project->plan_records_count }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $project->created_at->format('M d, Y') }}</td>
                        <td class="pe-3 text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-action btn-neon-outline" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($isMarshall)
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-action btn-neon-outline-violet" title="Edit Project">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline-flex m-0 p-0" onsubmit="return confirm('Are you sure you want to soft delete project \'{{ addslashes($project->name) }}\'? Assigned plan records will be preserved.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-action btn-neon-outline-danger" title="Soft Delete Project">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                                <h5 class="font-orbitron text-white">NO PROJECTS FOUND</h5>
                                <p class="small text-muted mb-3">No organizational projects matching your filter criteria were located.</p>
                                @if ($isMarshall)
                                    <a href="{{ route('projects.create') }}" class="btn btn-neon-outline-violet btn-sm">
                                        <i class="bi bi-plus-lg me-1"></i> PROVISION FIRST PROJECT
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($projects->hasPages())
        <div class="p-3 border-top border-secondary d-flex justify-content-center">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container py-3 py-md-4">
    <!-- Header Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom border-secondary">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge" style="background: rgba(255, 230, 0, 0.15); color: #ffe600; border: 1px solid rgba(255, 230, 0, 0.4);">
                    <i class="bi bi-trophy-fill me-1"></i> OPERATIVE RANKINGS &bull; EVALUATED METRICS
                </span>
            </div>
            <h2 class="font-orbitron fw-bold text-white mb-0">OPERATIVE LEADERBOARD</h2>
            <p class="text-muted small mb-0">
                Score performance rankings calculated strictly across Plan Records with status <strong>CLOSE</strong>.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i>
                <span>EXECUTORS DIRECTORY</span>
            </a>
            <a href="{{ route('projects.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                <i class="bi bi-folder-fill"></i>
                <span>PROJECTS</span>
            </a>
        </div>
    </div>

    <!-- Date Filters Card -->
    <div class="card p-3 p-md-4 mb-4" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 pb-2 border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-range text-neon-cyan fs-5"></i>
                <h5 class="font-orbitron text-white mb-0">TIMELINE RANGE FILTER</h5>
            </div>
            <div class="small font-rajdhani">
                @if (empty($from) && empty($to))
                    <span class="badge" style="background: rgba(0, 240, 255, 0.1); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.3);">
                        <i class="bi bi-infinity me-1"></i>ALL-TIME CLOSED RECORDS (DEFAULT)
                    </span>
                @else
                    <span class="badge" style="background: rgba(255, 184, 0, 0.15); color: #ffb800; border: 1px solid rgba(255, 184, 0, 0.4);">
                        <i class="bi bi-funnel-fill me-1"></i>CUSTOM TIMELINE APPLIED
                    </span>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('leaderboard.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <label for="filter_from" class="form-label font-rajdhani text-uppercase text-muted small fw-bold">
                    <i class="bi bi-calendar-event me-1 text-neon-cyan"></i>PLAN START DATE &ge; (FROM)
                </label>
                <input type="date" class="form-control form-control-sm @error('from') is-invalid @enderror" id="filter_from" name="from" value="{{ old('from', $from) }}" placeholder="YYYY-MM-DD">
                @error('from')
                    <div class="invalid-feedback text-neon-pink">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <label for="filter_to" class="form-label font-rajdhani text-uppercase text-muted small fw-bold">
                    <i class="bi bi-calendar-check me-1 text-neon-orange"></i>PLAN END DATE &le; (TO)
                </label>
                <input type="date" class="form-control form-control-sm @error('to') is-invalid @enderror" id="filter_to" name="to" value="{{ old('to', $to) }}" placeholder="YYYY-MM-DD">
                @error('to')
                    <div class="invalid-feedback text-neon-pink">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-4 col-lg-6 d-flex gap-2">
                <button type="submit" class="btn btn-neon-cyan btn-sm font-rajdhani fw-bold px-3">
                    <i class="bi bi-funnel-fill me-1"></i> APPLY FILTER
                </button>
                <a href="{{ route('leaderboard.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> RESET DATES
                </a>
            </div>
        </form>

        <div class="mt-3 pt-2 border-top border-secondary small text-muted font-rajdhani d-flex flex-wrap align-items-center gap-3">
            <span><i class="bi bi-info-circle me-1 text-neon-cyan"></i><strong>Rule:</strong> Only Plan records with status <code>Close</code> are calculated.</span>
            <span><i class="bi bi-check2-circle me-1 text-neon-green"></i>Start Date must be &ge; From date (if provided).</span>
            <span><i class="bi bi-check2-circle me-1 text-neon-green"></i>End Date must be &le; To date (if provided).</span>
        </div>
    </div>

    <!-- Top 3 Podium (Shown if >= 2 executors) -->
    @if ($leaderboard->count() >= 2)
        <div class="card p-4 mb-4" style="background: #120f24; border: 1px solid rgba(255, 230, 0, 0.25);">
            <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom border-secondary">
                <i class="bi bi-award-fill text-neon-yellow fs-4"></i>
                <div>
                    <h5 class="font-orbitron text-white mb-0">HONOR ROLL PODIUM</h5>
                    <small class="text-muted">Top performing field operatives for the active evaluation timeline</small>
                </div>
            </div>

            <div class="row g-3 justify-content-center align-items-end pt-2 pb-2">
                <!-- Rank 2: Silver (Left) -->
                @if ($leaderboard->has(1))
                    @php $p2 = $leaderboard->get(1); @endphp
                    <div class="col-12 col-md-4 order-2 order-md-1">
                        <div class="p-3 rounded text-center h-100" style="background: rgba(0, 240, 255, 0.05); border: 2px solid rgba(0, 240, 255, 0.4); box-shadow: 0 4px 20px rgba(0, 240, 255, 0.15);">
                            <span class="badge px-3 py-1 mb-2 font-orbitron" style="background: #00f0ff; color: #0a0814; font-size: 0.85rem;">
                                <i class="bi bi-award me-1"></i>RANK #2
                            </span>
                            <div class="my-2">
                                <a href="{{ route('profile.show', $p2['executor']) }}" class="text-decoration-none">
                                    @if ($p2['executor']->profile_picture)
                                        <img src="{{ asset('storage/' . $p2['executor']->profile_picture) }}" alt="{{ $p2['executor']->name }}" class="rounded-circle object-fit-cover shadow-lg mx-auto" style="width: 64px; height: 64px; border: 2.5px solid #00f0ff;">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 mx-auto shadow-lg" style="width: 64px; height: 64px; background: #00f0ff; color: #0a0814;">
                                            {{ strtoupper(substr($p2['executor']->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <h5 class="font-rajdhani fw-bold text-white mb-1">
                                <a href="{{ route('profile.show', $p2['executor']) }}" class="text-white text-decoration-none user-profile-link">
                                    {{ $p2['executor']->name }}
                                </a>
                            </h5>
                            <div class="font-orbitron fw-bold fs-4 text-neon-cyan mb-1">
                                {{ number_format($p2['average_score'], 1) }}%
                            </div>
                            <small class="badge bg-secondary bg-opacity-25 text-light font-rajdhani">
                                {{ $p2['closed_plans_count'] }} Closed {{ Str::plural('Plan', $p2['closed_plans_count']) }}
                            </small>
                        </div>
                    </div>
                @endif

                <!-- Rank 1: Gold (Center, Elevated) -->
                @if ($leaderboard->has(0))
                    @php $p1 = $leaderboard->get(0); @endphp
                    <div class="col-12 col-md-4 order-1 order-md-2">
                        <div class="p-4 rounded text-center position-relative" style="background: rgba(255, 230, 0, 0.08); border: 2.5px solid #ffe600; box-shadow: 0 0 30px rgba(255, 230, 0, 0.25); transform: translateY(-8px);">
                            <span class="badge px-4 py-1 mb-2 font-orbitron" style="background: #ffe600; color: #0a0814; font-size: 0.95rem;">
                                <i class="bi bi-trophy-fill me-1"></i>CHAMPION #1
                            </span>
                            <div class="my-2">
                                <a href="{{ route('profile.show', $p1['executor']) }}" class="text-decoration-none">
                                    @if ($p1['executor']->profile_picture)
                                        <img src="{{ asset('storage/' . $p1['executor']->profile_picture) }}" alt="{{ $p1['executor']->name }}" class="rounded-circle object-fit-cover shadow-lg mx-auto" style="width: 80px; height: 80px; border: 3px solid #ffe600;">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto shadow-lg" style="width: 80px; height: 80px; background: #ffe600; color: #0a0814;">
                                            {{ strtoupper(substr($p1['executor']->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <h4 class="font-rajdhani fw-bold text-white mb-1">
                                <a href="{{ route('profile.show', $p1['executor']) }}" class="text-white text-decoration-none user-profile-link">
                                    {{ $p1['executor']->name }}
                                </a>
                            </h4>
                            <div class="font-orbitron fw-bold display-6 text-neon-yellow mb-1">
                                {{ number_format($p1['average_score'], 1) }}%
                            </div>
                            <span class="badge" style="background: rgba(255, 230, 0, 0.2); color: #ffe600; border: 1px solid #ffe600; font-size: 0.75rem;">
                                {{ $p1['closed_plans_count'] }} Closed {{ Str::plural('Plan', $p1['closed_plans_count']) }}
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Rank 3: Bronze (Right) -->
                @if ($leaderboard->has(2))
                    @php $p3 = $leaderboard->get(2); @endphp
                    <div class="col-12 col-md-4 order-3 order-md-3">
                        <div class="p-3 rounded text-center h-100" style="background: rgba(255, 107, 0, 0.05); border: 2px solid rgba(255, 107, 0, 0.4); box-shadow: 0 4px 20px rgba(255, 107, 0, 0.15);">
                            <span class="badge px-3 py-1 mb-2 font-orbitron" style="background: #ff6b00; color: #0a0814; font-size: 0.85rem;">
                                <i class="bi bi-award me-1"></i>RANK #3
                            </span>
                            <div class="my-2">
                                <a href="{{ route('profile.show', $p3['executor']) }}" class="text-decoration-none">
                                    @if ($p3['executor']->profile_picture)
                                        <img src="{{ asset('storage/' . $p3['executor']->profile_picture) }}" alt="{{ $p3['executor']->name }}" class="rounded-circle object-fit-cover shadow-lg mx-auto" style="width: 64px; height: 64px; border: 2.5px solid #ff6b00;">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 mx-auto shadow-lg" style="width: 64px; height: 64px; background: #ff6b00; color: #0a0814;">
                                            {{ strtoupper(substr($p3['executor']->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <h5 class="font-rajdhani fw-bold text-white mb-1">
                                <a href="{{ route('profile.show', $p3['executor']) }}" class="text-white text-decoration-none user-profile-link">
                                    {{ $p3['executor']->name }}
                                </a>
                            </h5>
                            <div class="font-orbitron fw-bold fs-4 text-neon-orange mb-1">
                                {{ number_format($p3['average_score'], 1) }}%
                            </div>
                            <small class="badge bg-secondary bg-opacity-25 text-light font-rajdhani">
                                {{ $p3['closed_plans_count'] }} Closed {{ Str::plural('Plan', $p3['closed_plans_count']) }}
                            </small>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Leaderboard Full Table Card -->
    <div class="card p-0 overflow-hidden" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
        <div class="p-3 border-bottom border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" style="background: rgba(18, 15, 36, 0.7);">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table text-neon-cyan fs-4"></i>
                <div>
                    <h5 class="font-orbitron text-white mb-0">OPERATIVES SCORE PERFORMANCE RANKINGS</h5>
                    <small class="text-muted">Ranked by average evaluation score descending across qualifying closed plans</small>
                </div>
            </div>
            <span class="badge" style="background: rgba(0, 240, 255, 0.15); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.3);">
                {{ $leaderboard->count() }} {{ Str::plural('EXECUTOR', $leaderboard->count()) }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="background: transparent;">
                <thead>
                    <tr class="border-secondary text-muted small font-rajdhani text-uppercase" style="background: rgba(255, 255, 255, 0.02);">
                        <th class="ps-3 text-center" style="width: 80px;">RANK</th>
                        <th>FIELD EXECUTOR</th>
                        <th class="text-center" style="width: 140px;">CLOSED PLANS</th>
                        <th style="min-width: 220px;">SCORE PERFORMANCE</th>
                        <th class="text-center d-none d-md-table-cell" style="width: 140px;">SCORE RANGE</th>
                        <th class="pe-3 text-end" style="width: 160px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaderboard as $row)
                        @php
                            $exec = $row['executor'];
                            $rank = $row['rank'];
                            $avgScore = $row['average_score'];
                            $closedCount = $row['closed_plans_count'];
                        @endphp
                        <tr class="border-secondary">
                            <!-- Rank Badge -->
                            <td class="ps-3 text-center">
                                @if ($rank === 1)
                                    <span class="badge font-orbitron px-2 py-1" style="background: #ffe600; color: #0a0814; font-size: 0.85rem;" title="1st Place Champion">
                                        <i class="bi bi-trophy-fill me-1"></i>#1
                                    </span>
                                @elseif ($rank === 2)
                                    <span class="badge font-orbitron px-2 py-1" style="background: #00f0ff; color: #0a0814; font-size: 0.85rem;" title="2nd Place">
                                        <i class="bi bi-award me-1"></i>#2
                                    </span>
                                @elseif ($rank === 3)
                                    <span class="badge font-orbitron px-2 py-1" style="background: #ff6b00; color: #0a0814; font-size: 0.85rem;" title="3rd Place">
                                        <i class="bi bi-award me-1"></i>#3
                                    </span>
                                @else
                                    <span class="badge font-orbitron px-2 py-1 bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-50" style="font-size: 0.85rem;">
                                        #{{ $rank }}
                                    </span>
                                @endif
                            </td>

                            <!-- Executor Info with Profile Link -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('profile.show', $exec) }}" class="text-decoration-none flex-shrink-0" title="View {{ $exec->name }}'s Profile">
                                        @if ($exec->profile_picture)
                                            <img src="{{ asset('storage/' . $exec->profile_picture) }}" alt="{{ $exec->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 38px; height: 38px; border: 1.5px solid var(--neon-cyan);">
                                        @else
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background: #00f0ff; color: #0a0814;">
                                                {{ strtoupper(substr($exec->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </a>
                                    <div class="overflow-hidden">
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ route('profile.show', $exec) }}" class="fw-bold text-white text-decoration-none user-profile-link text-truncate d-block" title="View {{ $exec->name }}'s Profile">
                                                {{ $exec->name }}
                                            </a>
                                            <span class="badge badge-role-executor px-1.5 py-0.5 rounded-pill" style="font-size: 0.6rem;">
                                                EXECUTOR
                                            </span>
                                        </div>
                                        <small class="text-muted text-truncate d-block" style="font-size: 0.75rem;">
                                            {{ $exec->email }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- Closed Plans Count -->
                            <td class="text-center font-orbitron">
                                @if ($closedCount > 0)
                                    <span class="badge" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4); font-size: 0.85rem;">
                                        {{ $closedCount }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-25" style="font-size: 0.85rem;">
                                        0
                                    </span>
                                @endif
                            </td>

                            <!-- Score Performance -->
                            <td>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="font-orbitron {{ $avgScore >= 80 ? 'text-neon-green' : ($avgScore >= 60 ? 'text-neon-yellow' : ($closedCount > 0 ? 'text-neon-pink' : 'text-muted')) }}" style="font-size: 1.05rem;">
                                        {{ number_format($avgScore, 1) }}%
                                    </strong>
                                    <small class="text-muted font-rajdhani" style="font-size: 0.72rem;">
                                        @if ($avgScore >= 90)
                                            <span class="text-neon-green fw-bold">EXEMPLARY</span>
                                        @elseif ($avgScore >= 75)
                                            <span class="text-neon-cyan fw-bold">SUPERIOR</span>
                                        @elseif ($avgScore >= 60)
                                            <span class="text-neon-yellow fw-bold">ACCEPTABLE</span>
                                        @elseif ($closedCount > 0)
                                            <span class="text-neon-pink fw-bold">SUBSTANDARD</span>
                                        @else
                                            <span>NO DATA</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="progress" style="height: 6px; background-color: rgba(255, 255, 255, 0.1); border-radius: 3px;">
                                    <div class="progress-bar {{ $avgScore >= 80 ? 'bg-success' : ($avgScore >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                         role="progressbar"
                                         style="width: {{ min(100, max(0, $avgScore)) }}%;"
                                         aria-valuenow="{{ $avgScore }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </td>

                            <!-- Score Range (Lowest & Highest Plan Score) -->
                            <td class="text-center d-none d-md-table-cell font-orbitron small">
                                @if ($closedCount > 0)
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        {{ number_format($row['lowest_score'], 1) }}% &mdash; {{ number_format($row['highest_score'], 1) }}%
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.75rem;">&mdash;</span>
                                @endif
                            </td>

                            <!-- Action Buttons -->
                            <td class="pe-3 text-end">
                                <div class="d-inline-flex gap-2 justify-content-end">
                                    <a href="{{ route('profile.show', $exec) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center justify-content-center p-1 px-2" style="width: 85px;" title="View Operative Profile">
                                        <i class="bi bi-person-badge me-1"></i>
                                        <span>Profile</span>
                                    </a>
                                    <a href="{{ route('executors.show', $exec) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center justify-content-center p-1 px-2" style="width: 85px;" title="View Full Plans Dossier">
                                        <i class="bi bi-folder2-open me-1"></i>
                                        <span>Dossier</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-trophy display-4 d-block mb-2 opacity-50"></i>
                                <h6 class="font-orbitron text-white">NO EXECUTORS FOUND</h6>
                                <p class="small mb-0">No executor operatives match the current query criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

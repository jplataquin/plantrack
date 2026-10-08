@extends('layouts.app')

@section('content')
<div class="container py-3 py-md-4">
    <!-- Header Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom border-secondary">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge" style="background: rgba(0, 240, 255, 0.15); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.4);">
                    <i class="bi bi-shield-lock me-1"></i> SECURITY LEVEL &bull; VERIFIED
                </span>
                @if ($user->hasRole('Admin'))
                    <span class="badge badge-role-admin px-2 py-0.5 rounded-pill small">ADMIN</span>
                @elseif ($isMarshall)
                    <span class="badge badge-role-marshall px-2 py-0.5 rounded-pill small">MARSHALL</span>
                @elseif ($isExecutor)
                    <span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">EXECUTOR</span>
                @endif
            </div>
            <h2 class="font-orbitron fw-bold text-white mb-0">OPERATIVE PROFILE</h2>
            <p class="text-muted small mb-0">
                @if ($isOwnProfile)
                    Manage operative identity, credentials, and security clearance keys.
                @else
                    Dossier telemetry and mission record for operative {{ $user->name }}.
                @endif
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($isOwnProfile)
                @if ($isExecutor)
                    <a href="{{ route('executors.show', $user) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-person"></i>
                        <span>MY PLANS DOSSIER</span>
                    </a>
                @else
                    <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                        <i class="bi bi-people-fill"></i>
                        <span>EXECUTORS DIRECTORY</span>
                    </a>
                @endif
            @else
                @if ($isExecutor)
                    <a href="{{ route('executors.show', $user) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-person"></i>
                        <span>FULL DOSSIER</span>
                    </a>
                @endif
                <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-2">
                    <i class="bi bi-people-fill"></i>
                    <span>EXECUTORS DIRECTORY</span>
                </a>
            @endif
            @if (Auth::user()->hasRole('Admin') && ! $user->hasRole('Admin'))
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm font-rajdhani fw-bold d-inline-flex align-items-center gap-1">
                    <i class="bi bi-pencil-square"></i>
                    <span>EDIT OPERATIVE</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div class="alert alert-dismissible fade show mb-4 d-flex align-items-center" role="alert" style="background: rgba(0, 255, 136, 0.12); border: 1px solid var(--neon-green); color: #00ff88;">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-dismissible fade show mb-4 d-flex align-items-center" role="alert" style="background: rgba(255, 0, 85, 0.12); border: 1px solid var(--neon-pink); color: #ff0055;">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div>
                <strong>Please correct the errors below:</strong>
                <ul class="mb-0 mt-1 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- ==================== LEFT COLUMN: OPERATIVE DOSSIER CARD ==================== -->
        <div class="col-12 col-lg-4">
            <div class="card p-4 h-100" style="background: #120f24; border: 1px solid {{ $user->hasRole('Admin') ? '#ff0055' : ($isMarshall ? 'var(--neon-violet)' : 'var(--neon-cyan)') }}; box-shadow: 0 0 20px {{ $user->hasRole('Admin') ? 'rgba(255, 0, 85, 0.25)' : ($isMarshall ? 'rgba(168, 85, 247, 0.15)' : 'rgba(0, 240, 255, 0.15)') }};">
                <div class="text-center pb-3 border-bottom border-secondary">
                    <!-- Avatar with Camera Overlay Button -->
                    <div class="position-relative d-inline-block mb-3">
                        @if ($user->profile_picture)
                            <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ $user->name }}" class="rounded-circle object-fit-cover shadow-lg current-user-avatar-img" style="width: 104px; height: 104px; border: 3px solid {{ $user->hasRole('Admin') ? '#ff0055' : ($isMarshall ? 'var(--neon-violet)' : 'var(--neon-cyan)') }};">
                        @else
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold display-5 current-user-avatar-fallback shadow-lg" style="width: 104px; height: 104px; background: {{ $user->hasRole('Admin') ? '#ff0055' : ($isMarshall ? '#a855f7' : '#00f0ff') }}; color: #ffffff;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        @if ($isOwnProfile || Auth::user()->hasAnyRole(['Marshall', 'Admin']))
                        <button type="button" class="btn btn-neon-cyan rounded-circle position-absolute bottom-0 end-0 p-0 d-flex align-items-center justify-content-center shadow open-avatar-modal-btn" style="width: 32px; height: 32px; border: 2px solid #120f24;" data-bs-toggle="modal" data-bs-target="#profilePictureModal" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" title="Update Profile Picture (Zoom & Crop)">
                            <i class="bi bi-camera-fill fs-6"></i>
                        </button>
                        @endif
                    </div>

                    <h4 class="font-orbitron fw-bold text-white mb-1">{{ $user->name }}</h4>
                    <p class="text-muted small mb-2 text-truncate">{{ $user->email }}</p>

                    <div class="d-flex align-items-center justify-content-center gap-2">
                        @if ($user->hasRole('Admin'))
                            <span class="badge badge-role-admin px-3 py-1 rounded-pill font-rajdhani fw-bold">SYSTEM ADMINISTRATOR</span>
                        @elseif ($isMarshall)
                            <span class="badge badge-role-marshall px-3 py-1 rounded-pill font-rajdhani fw-bold">MISSION MARSHALL</span>
                        @elseif ($isExecutor)
                            <span class="badge badge-role-executor px-3 py-1 rounded-pill font-rajdhani fw-bold">FIELD EXECUTOR</span>
                        @endif
                    </div>
                </div>

                <!-- Meta Details -->
                <div class="py-3 border-bottom border-secondary">
                    <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">OPERATIVE TELEMETRY</span>
                    <div class="d-flex flex-column gap-2 small">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-calendar-check me-1 text-neon-cyan"></i>Registered:</span>
                            <strong class="text-white">{{ $user->created_at->format('M d, Y') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-activity me-1 text-neon-green"></i>Account Status:</span>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">ACTIVE</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-key me-1 text-neon-orange"></i>Auth Standard:</span>
                            <strong class="text-white">Bcrypt Salted</strong>
                        </div>
                    </div>
                </div>

                <!-- Plans Summary & Average Score -->
                @if ($isExecutor || $assignedPlansCount > 0)
                    <div class="pt-3">
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">MISSION METRICS</span>

                        <!-- Average Score Card (Closed Plans Only) -->
                        <div class="p-3 rounded mb-3" style="background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.35); box-shadow: 0 0 15px rgba(0, 255, 136, 0.15);">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-neon-green small font-rajdhani text-uppercase fw-bold">
                                    <i class="bi bi-award-fill me-1"></i>AVG SCORE (CLOSED PLANS)
                                </span>
                                <span class="badge" style="background: rgba(0, 255, 136, 0.2); color: #00ff88; border: 1px solid #00ff88; font-size: 0.7rem;">
                                    {{ $closedPlansCount }} CLOSED {{ Str::plural('PLAN', $closedPlansCount) }}
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="display-6 font-orbitron fw-bold text-neon-green" id="profile-average-score">{{ number_format($averageScore, 1) }}%</span>
                                <small class="text-muted font-rajdhani">Performance Index</small>
                            </div>
                            <div class="progress mt-2" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, max(0, $averageScore)) }}%;" aria-valuenow="{{ $averageScore }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.7rem;">
                                Performance score averaged strictly from Plan records with status <strong>CLOSE</strong>.
                            </small>
                        </div>

                        <!-- Status Breakdown Counts -->
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 rounded h-100" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06);">
                                    <span class="text-muted d-block small" style="font-size: 0.68rem;">ASSIGNED</span>
                                    <strong class="font-orbitron text-white fs-6">{{ $assignedPlansCount }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded h-100" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.2);">
                                    <span class="text-neon-cyan d-block small" style="font-size: 0.68rem;">OPEN</span>
                                    <strong class="font-orbitron text-neon-cyan fs-6">{{ $openPlansCount }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded h-100" style="background: rgba(255, 184, 0, 0.05); border: 1px solid rgba(255, 184, 0, 0.2);">
                                    <span class="text-neon-yellow d-block small" style="font-size: 0.68rem; color: #ffb800;">REVIEW</span>
                                    <strong class="font-orbitron fs-6" style="color: #ffb800;">{{ $reviewPlansCount }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded h-100" style="background: rgba(0, 255, 136, 0.05); border: 1px solid rgba(0, 255, 136, 0.2);">
                                    <span class="text-neon-green d-block small" style="font-size: 0.68rem;">CLOSED</span>
                                    <strong class="font-orbitron text-neon-green fs-6">{{ $closedPlansCount }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded h-100" style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.2);">
                                    <span class="text-muted d-block small" style="font-size: 0.68rem;">VOID</span>
                                    <strong class="font-orbitron text-muted fs-6">{{ $voidPlansCount }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pt-3">
                        <span class="text-muted small font-rajdhani text-uppercase fw-bold d-block mb-2">COMMAND CLEARANCE</span>
                        <p class="small text-muted mb-0">
                            Authorized to oversee plan lifecycles, configure target objectives, declare resources, review risk mitigations, and provision field executors.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- ==================== RIGHT COLUMN: PROFILE DETAILS / EDIT ==================== -->
        <div class="col-12 col-lg-8 d-flex flex-column gap-4">
            @if ($isOwnProfile)
                <!-- 1. Edit Identity Card -->
                <div class="card p-4" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary">
                        <i class="bi bi-person-gear text-neon-cyan fs-4"></i>
                        <div>
                            <h5 class="font-orbitron text-white mb-0">OPERATIVE INFORMATION</h5>
                            <small class="text-muted">Update your display name and communication email</small>
                        </div>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label text-muted small font-rajdhani text-uppercase">OPERATIVE FULL NAME *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label text-muted small font-rajdhani text-uppercase">CONTACT EMAIL ADDRESS *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-neon-cyan px-4 font-rajdhani fw-bold">
                                <i class="bi bi-check2-circle me-1"></i> SAVE PROFILE CHANGES
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Security & Password Card -->
                <div class="card p-4" style="background: #120f24; border: 1px solid rgba(255, 107, 0, 0.35);">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary">
                        <i class="bi bi-shield-lock-fill text-neon-orange fs-4"></i>
                        <div>
                            <h5 class="font-orbitron text-white mb-0">SECURITY &amp; ACCESS KEY</h5>
                            <small class="text-muted">Update account password credentials</small>
                        </div>
                    </div>

                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label text-muted small font-rajdhani text-uppercase">CURRENT PASSWORD *</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter your existing password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label text-muted small font-rajdhani text-uppercase">NEW PASSWORD *</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password" placeholder="At least 8 characters">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="password_confirmation" class="form-label text-muted small font-rajdhani text-uppercase">CONFIRM NEW PASSWORD *</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat new password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-info-circle me-1"></i>Minimum 8 characters. Requires confirmation.
                            </small>
                            <button type="submit" class="btn btn-neon-orange px-4 font-rajdhani fw-bold">
                                <i class="bi bi-key-fill me-1"></i> UPDATE ACCESS KEY
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <!-- Operative Dossier Overview Card -->
                <div class="card p-4" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3 pb-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-badge-fill text-neon-cyan fs-4"></i>
                            <div>
                                <h5 class="font-orbitron text-white mb-0">OPERATIVE DOSSIER SUMMARY</h5>
                                <small class="text-muted">Public operational identity and mission parameters</small>
                            </div>
                        </div>
                        @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']) && $isExecutor)
                            <a href="{{ route('plans.create', ['executor_id' => $user->id]) }}" class="btn btn-neon-cyan btn-sm font-rajdhani fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> CREATE PLAN FOR OPERATIVE
                            </a>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <span class="text-muted small font-rajdhani text-uppercase d-block">OPERATIVE NAME</span>
                            <strong class="text-white fs-6 font-rajdhani">{{ $user->name }}</strong>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small font-rajdhani text-uppercase d-block">CONTACT ADDRESS</span>
                            <span class="text-neon-cyan fs-6 font-rajdhani">{{ $user->email }}</span>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small font-rajdhani text-uppercase d-block">SECURITY CLEARANCE</span>
                            <div class="mt-1">
                                @if ($user->hasRole('Admin'))
                                    <span class="badge badge-role-admin px-2 py-0.5 rounded-pill font-rajdhani">SYSTEM ADMINISTRATOR</span>
                                @elseif ($isMarshall)
                                    <span class="badge badge-role-marshall px-2 py-0.5 rounded-pill font-rajdhani">MISSION MARSHALL</span>
                                @elseif ($isExecutor)
                                    <span class="badge badge-role-executor px-2 py-0.5 rounded-pill font-rajdhani">FIELD EXECUTOR</span>
                                @else
                                    <span class="badge bg-secondary px-2 py-0.5 rounded-pill font-rajdhani">OPERATIVE</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="text-muted small font-rajdhani text-uppercase d-block">ACCOUNT STATUS</span>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 mt-1">ACTIVE</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Component Distribution Card (Closed Plans) -->
            <div class="card p-4" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3 pb-2 border-bottom border-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-steps text-neon-cyan fs-4"></i>
                        <div>
                            <h5 class="font-orbitron text-white mb-0">COMPONENT PERFORMANCE DISTRIBUTION</h5>
                            <small class="text-muted">Hit vs Missed vs Void percentage distribution for closed plans</small>
                        </div>
                    </div>
                    <span class="badge" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4); font-size: 0.72rem;">
                        <i class="bi bi-award-fill me-1"></i>{{ $closedPlansCount }} CLOSED {{ Str::plural('PLAN', $closedPlansCount) }}
                    </span>
                </div>

                <div class="d-flex flex-column gap-3">
                    @php
                        $categories = [
                            'targets' => ['label' => 'TARGET OBJECTIVES', 'icon' => 'bi-bullseye', 'color' => '#00f0ff'],
                            'resources' => ['label' => 'RESOURCES', 'icon' => 'bi-cpu', 'color' => '#ff6b00'],
                            'risks' => ['label' => 'RISK MANAGEMENT', 'icon' => 'bi-shield-exclamation', 'color' => '#a855f7'],
                            'budgets' => ['label' => 'BUDGET ITEMS', 'icon' => 'bi-cash-coin', 'color' => '#ffe600'],
                        ];
                    @endphp

                    @foreach ($categories as $key => $meta)
                        @php
                            $cat = $componentStats[$key];
                        @endphp
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-rajdhani fw-bold text-white small">
                                    <i class="bi {{ $meta['icon'] }} me-1" style="color: {{ $meta['color'] }};"></i>{{ $meta['label'] }}
                                </span>
                                <div class="font-rajdhani small d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                                    <span class="text-neon-green fw-bold">{{ $cat['hit'] }} Hit ({{ number_format($cat['hit_pct'], 1) }}%)</span>
                                    <span class="text-muted">&bull;</span>
                                    <span class="text-neon-pink fw-bold" style="color: #ff0055;">{{ $cat['missed'] }} Missed ({{ number_format($cat['missed_pct'], 1) }}%)</span>
                                    <span class="text-muted">&bull;</span>
                                    <span class="text-secondary fw-bold">{{ $cat['void'] }} Void ({{ number_format($cat['void_pct'], 1) }}%)</span>
                                    <span class="badge bg-secondary bg-opacity-25 text-light ms-1">{{ $cat['total'] }} Total</span>
                                </div>
                            </div>
                            <div class="progress" style="height: 10px; background-color: rgba(255, 255, 255, 0.08); border-radius: 5px; overflow: hidden;">
                                @if ($cat['total'] > 0)
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $cat['hit_pct'] }}%;" aria-valuenow="{{ $cat['hit_pct'] }}" aria-valuemin="0" aria-valuemax="100" title="Hit: {{ $cat['hit_pct'] }}%"></div>
                                    <div class="progress-bar" role="progressbar" style="width: {{ $cat['missed_pct'] }}%; background-color: #ff0055;" aria-valuenow="{{ $cat['missed_pct'] }}" aria-valuemin="0" aria-valuemax="100" title="Missed: {{ $cat['missed_pct'] }}%"></div>
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: {{ $cat['void_pct'] }}%;" aria-valuenow="{{ $cat['void_pct'] }}" aria-valuemin="0" aria-valuemax="100" title="Void: {{ $cat['void_pct'] }}%"></div>
                                @else
                                    <div class="progress-bar bg-secondary bg-opacity-25" role="progressbar" style="width: 100%;" title="No evaluated components"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex align-items-center justify-content-end gap-3 mt-3 pt-2 border-top border-secondary font-rajdhani" style="font-size: 0.72rem;">
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background-color: #00ff88;"></span>
                        <span class="text-muted">Hit</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background-color: #ff0055;"></span>
                        <span class="text-muted">Missed</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background-color: #6c757d;"></span>
                        <span class="text-muted">Void</span>
                    </div>
                </div>
            </div>

            <!-- Assigned Plan Records List -->
            <div class="card p-0 overflow-hidden" style="background: #120f24; border: 1px solid rgba(0, 240, 255, 0.3);">
                <div class="p-3 border-bottom border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" style="background: rgba(18, 15, 36, 0.7);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-kanban text-neon-cyan fs-4"></i>
                        <div>
                            <h5 class="font-orbitron text-white mb-0">ASSIGNED PLAN RECORDS ({{ $assignedPlansCount }})</h5>
                            <small class="text-muted">List of all strategic plan records assigned to this operative</small>
                        </div>
                    </div>
                    @if ($isExecutor)
                        <a href="{{ route('executors.show', $user) }}" class="btn btn-neon-outline btn-sm font-rajdhani fw-bold">
                            <i class="bi bi-box-arrow-up-right me-1"></i> FULL DOSSIER
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="background: transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                                <th class="font-rajdhani text-uppercase text-muted small py-3 ps-3">PLAN IDENTIFIER</th>
                                <th class="font-rajdhani text-uppercase text-muted small py-3">PROJECT</th>
                                <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">STATUS</th>
                                <th class="font-rajdhani text-uppercase text-muted small py-3 text-center">EVAL SCORE</th>
                                <th class="font-rajdhani text-uppercase text-muted small py-3">SCHEDULE</th>
                                <th class="font-rajdhani text-uppercase text-muted small py-3 pe-3 text-end">INSPECT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignedPlans as $plan)
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                    <td class="ps-3">
                                        <a href="{{ route('plans.show', $plan) }}" class="fw-bold text-white text-decoration-none d-block font-orbitron">
                                            {{ $plan->title }}
                                        </a>
                                        @if ($plan->description)
                                            <small class="text-muted text-truncate d-block" style="max-width: 240px;">{{ $plan->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($plan->project)
                                            <a href="{{ route('projects.show', $plan->project) }}" class="badge text-decoration-none" style="background: rgba(168, 85, 247, 0.15); color: var(--neon-violet-light); border: 1px solid var(--neon-violet); font-size: 0.72rem;">
                                                <i class="bi bi-folder-fill me-1"></i>{{ $plan->project->name }}
                                            </a>
                                        @else
                                            <span class="text-muted small fst-italic">Standalone</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ $plan->status_badge_class }} px-2 py-0.5 rounded-pill small">
                                            {{ strtoupper($plan->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($plan->status === 'Close')
                                            <span class="badge" style="background: rgba(0, 255, 136, 0.15); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.4);">
                                                {{ number_format($plan->evaluation_score, 1) }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50" title="Plan is currently {{ $plan->status }}">
                                                {{ number_format($plan->evaluation_score, 1) }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">
                                        <div>{{ $plan->start_date->format('M d, Y') }} &rarr; {{ $plan->end_date->format('M d, Y') }}</div>
                                        <small class="text-neon-cyan">{{ $plan->start_date->diffInDays($plan->end_date) }} Days</small>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <a href="{{ route('plans.show', $plan) }}" class="btn btn-action btn-neon-outline" title="Inspect Plan">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-kanban fs-2 d-block mb-1 text-muted"></i>
                                        <h6 class="font-orbitron text-white">NO PLAN RECORDS ASSIGNED</h6>
                                        <p class="small text-muted mb-0">No active or evaluated plan records are currently assigned to this account.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

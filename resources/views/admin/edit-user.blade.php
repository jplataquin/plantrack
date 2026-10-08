@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <!-- Back Navigation & Title -->
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm me-3 d-inline-flex align-items-center gap-1 font-rajdhani">
                <i class="bi bi-arrow-left"></i> RETURN TO ADMIN CONSOLE
            </a>
            <div>
                <span class="badge badge-role-admin px-2 py-0.5 rounded-pill small">ROOT AUTHORIZATION</span>
                <h3 class="font-orbitron fw-bold text-white mb-0">MODIFY OPERATIVE PROFILE</h3>
            </div>
        </div>

        <div class="card p-4 p-md-5" style="background: #120f24; border: 1px solid var(--neon-cyan); box-shadow: 0 0 25px rgba(0, 240, 255, 0.2);">
            <div class="mb-4 pb-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="font-orbitron text-white mb-1">{{ $user->name }}</h5>
                    <span class="text-muted small font-monospace">{{ $user->email }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge px-2 py-1 {{ $currentRole === 'Marshall' ? 'badge-role-marshall' : 'badge-role-executor' }}">
                        CURRENT ROLE: {{ strtoupper($currentRole) }}
                    </span>
                    @if ($user->must_reset_password)
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2 py-1 small">
                            RESET PENDING
                        </span>
                    @else
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1 small">
                            ACTIVE
                        </span>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.users.update', $user) }}" method="POST" id="editUserForm">
                @csrf
                @method('PUT')

                <!-- Role Selection -->
                <div class="mb-4">
                    <label class="form-label font-rajdhani text-uppercase text-muted small fw-bold">CLEARANCE ROLE LEVEL *</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="card h-100 p-3 role-selection-card cursor-pointer" for="role_marshall" id="card_marshall" style="background: #17132e; border: 2px solid {{ old('role', $currentRole) === 'Marshall' ? '#a855f7' : 'rgba(255, 255, 255, 0.15)' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" class="form-check-input mt-1" name="role" id="role_marshall" value="Marshall" {{ old('role', $currentRole) === 'Marshall' ? 'checked' : '' }} onchange="updateRoleStyling('Marshall')">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-shield-shaded text-neon-violet fs-5"></i>
                                            <span class="badge badge-role-marshall px-2 py-0.5 rounded-pill small">MARSHALL</span>
                                        </div>
                                        <strong class="text-white d-block font-rajdhani">Mission Oversight & Evaluation</strong>
                                        <small class="text-muted">Full administrative oversight over plans, evaluation weights, objectives, components, and remarks.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="card h-100 p-3 role-selection-card cursor-pointer" for="role_executor" id="card_executor" style="background: #17132e; border: 2px solid {{ old('role', $currentRole) === 'Executor' ? '#00f0ff' : 'rgba(255, 255, 255, 0.15)' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" class="form-check-input mt-1" name="role" id="role_executor" value="Executor" {{ old('role', $currentRole) === 'Executor' ? 'checked' : '' }} onchange="updateRoleStyling('Executor')">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-lightning-charge-fill text-neon-cyan fs-5"></i>
                                            <span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">EXECUTOR</span>
                                        </div>
                                        <strong class="text-white d-block font-rajdhani">Operational Field Operative</strong>
                                        <small class="text-muted">Executes mission plans, logs accomplishment telemetry, and submits resources and remarks.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    @error('role')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Full Name -->
                <div class="mb-4">
                    <label for="name" class="form-label font-rajdhani text-uppercase text-muted small fw-bold">OPERATIVE FULL NAME *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary text-neon-cyan">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    @error('name')
                        <div class="invalid-feedback d-block text-neon-pink">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="mb-4">
                    <label for="email" class="form-label font-rajdhani text-uppercase text-muted small fw-bold">SYSTEM IDENTIFIER (EMAIL ADDRESS) *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary text-neon-cyan">
                            <i class="bi bi-envelope-at"></i>
                        </span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block text-neon-pink">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password Reset Configuration -->
                <div class="p-3 rounded mb-4" style="background: rgba(255, 107, 0, 0.04); border: 1px solid rgba(255, 107, 0, 0.3);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="new_password" class="form-label font-rajdhani text-uppercase text-neon-orange fw-bold mb-0">
                            <i class="bi bi-key-fill me-1"></i>RESET ACCESS KEY / PASSWORD
                        </label>
                        <span class="badge bg-secondary font-monospace" style="font-size: 0.65rem;">OPTIONAL OVERRIDE</span>
                    </div>
                    <p class="text-muted small mb-3">
                        Leave blank to keep the operative's current password unchanged. Enter a new passphrase or click <strong>Generate</strong> to issue a new security key.
                    </p>

                    <div class="input-group mb-2">
                        <span class="input-group-text bg-transparent border-secondary text-neon-yellow">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="text" class="form-control font-monospace @error('new_password') is-invalid @enderror" id="new_password" name="new_password" value="{{ old('new_password') }}" placeholder="Leave blank to retain existing password" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" onclick="generateRandomKey()" title="Generate new random key">
                            <i class="bi bi-shuffle"></i> Generate
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyNewKey()" title="Copy key to clipboard">
                            <i class="bi bi-clipboard" id="copy-icon"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 8 characters if providing a new key.</small>
                    @error('new_password')
                        <div class="invalid-feedback d-block text-neon-pink">{{ $message }}</div>
                    @enderror

                    <!-- Force Reset Toggle -->
                    <div class="form-check mt-3 pt-2 border-top border-secondary border-opacity-25">
                        <input class="form-check-input" type="checkbox" name="force_password_reset" id="force_password_reset" value="1" {{ old('force_password_reset', $user->must_reset_password ? '1' : '') ? 'checked' : '' }}>
                        <label class="form-check-label text-white small font-rajdhani fw-bold" for="force_password_reset">
                            <i class="bi bi-shield-exclamation text-neon-yellow me-1"></i> REQUIRE PASSWORD RESET UPON NEXT SIGN-IN
                        </label>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            Enforces that the operative will be redirected to choose a new password upon their next system login.
                        </small>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary font-rajdhani fw-bold px-4">
                        CANCEL
                    </a>
                    <button type="submit" class="btn btn-neon-cyan font-orbitron fw-bold px-4" id="submitBtn">
                        <i class="bi bi-check2-circle me-1"></i> UPDATE OPERATIVE PROFILE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateRoleStyling(role) {
    const cardMarshall = document.getElementById('card_marshall');
    const cardExecutor = document.getElementById('card_executor');

    if (role === 'Marshall') {
        cardMarshall.style.borderColor = '#a855f7';
        cardExecutor.style.borderColor = 'rgba(255, 255, 255, 0.15)';
    } else {
        cardExecutor.style.borderColor = '#00f0ff';
        cardMarshall.style.borderColor = 'rgba(255, 255, 255, 0.15)';
    }
}

function generateRandomKey() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let key = '';
    for (let i = 0; i < 10; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('new_password').value = key;
    const forceResetCheckbox = document.getElementById('force_password_reset');
    if (forceResetCheckbox) {
        forceResetCheckbox.checked = true;
    }
}

function copyNewKey() {
    const input = document.getElementById('new_password');
    if (!input.value) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const icon = document.getElementById('copy-icon');
        icon.className = 'bi bi-check-lg text-neon-green';
        setTimeout(() => {
            icon.className = 'bi bi-clipboard';
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const selectedRole = document.querySelector('input[name="role"]:checked')?.value || '{{ $currentRole }}';
    updateRoleStyling(selectedRole);
});
</script>
@endsection

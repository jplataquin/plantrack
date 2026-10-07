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
                <h3 class="font-orbitron fw-bold text-white mb-0">PROVISION SYSTEM OPERATIVE</h3>
            </div>
        </div>

        <div class="card p-4 p-md-5" style="background: #120f24; border: 1px solid var(--neon-pink); box-shadow: 0 0 25px rgba(255, 0, 85, 0.2);">
            <div class="mb-4 pb-3 border-bottom border-secondary">
                <p class="text-muted small mb-0">
                    Create a new system user with Marshall or Executor clearance. An encrypted temporary access key will be issued, and the operative will be mandated to configure their own permanent password upon first system authentication.
                </p>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" id="provisionUserForm">
                @csrf

                <!-- Role Selection -->
                <div class="mb-4">
                    <label class="form-label font-rajdhani text-uppercase text-muted small fw-bold">CLEARANCE ROLE LEVEL *</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="card h-100 p-3 role-selection-card cursor-pointer" for="role_marshall" id="card_marshall" style="background: #17132e; border: 2px solid {{ old('role', $selectedRole) === 'Marshall' ? '#a855f7' : 'rgba(255, 255, 255, 0.15)' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" class="form-check-input mt-1" name="role" id="role_marshall" value="Marshall" {{ old('role', $selectedRole) === 'Marshall' ? 'checked' : '' }} onchange="updateRoleStyling('Marshall')">
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
                            <label class="card h-100 p-3 role-selection-card cursor-pointer" for="role_executor" id="card_executor" style="background: #17132e; border: 2px solid {{ old('role', $selectedRole) === 'Executor' ? '#00f0ff' : 'rgba(255, 255, 255, 0.15)' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" class="form-check-input mt-1" name="role" id="role_executor" value="Executor" {{ old('role', $selectedRole) === 'Executor' ? 'checked' : '' }} onchange="updateRoleStyling('Executor')">
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
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Commander Sarah Connor" required autofocus>
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
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="operative@plantrack.test" required>
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block text-neon-pink">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Temporary Password Configuration -->
                <div class="p-3 rounded mb-4" style="background: rgba(0, 240, 255, 0.04); border: 1px solid rgba(0, 240, 255, 0.2);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="temporary_password" class="form-label font-rajdhani text-uppercase text-neon-cyan fw-bold mb-0">
                            <i class="bi bi-key-fill me-1"></i>INITIAL TEMPORARY ACCESS KEY
                        </label>
                        <span class="badge bg-secondary font-monospace" style="font-size: 0.65rem;">ONE-TIME PROVISIONING</span>
                    </div>
                    <p class="text-muted small mb-3">
                        Provide a temporary passphrase or use the randomly generated suggestion below. The user will be required to change this upon their initial sign-in.
                    </p>

                    <div class="input-group mb-2">
                        <span class="input-group-text bg-transparent border-secondary text-neon-yellow">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="text" class="form-control font-monospace @error('temporary_password') is-invalid @enderror" id="temporary_password" name="temporary_password" value="{{ old('temporary_password', $suggestedPassword) }}" placeholder="Leave blank to generate random key">
                        <button type="button" class="btn btn-outline-secondary" onclick="generateRandomKey()" title="Generate new key">
                            <i class="bi bi-shuffle"></i> Generate
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="copyTemporaryKey()" title="Copy key to clipboard">
                            <i class="bi bi-clipboard" id="copy-icon"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 8 characters. Ensure you copy and transmit this key securely to the operative.</small>
                    @error('temporary_password')
                        <div class="invalid-feedback d-block text-neon-pink">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Mandatory Policy Notice -->
                <div class="alert alert-dark d-flex align-items-center gap-3 mb-4" style="background: rgba(255, 230, 0, 0.06); border: 1px solid rgba(255, 230, 0, 0.3); color: #ffffff;">
                    <i class="bi bi-exclamation-triangle-fill text-neon-yellow fs-3"></i>
                    <div class="small">
                        <strong class="text-neon-yellow d-block font-rajdhani text-uppercase">Automated Enforcement Policy:</strong>
                        The flag <code class="text-neon-cyan">must_reset_password</code> will be activated for this account. The user will be automatically redirected to configure a secure permanent passphrase before accessing any module of PlanTrack.
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary font-rajdhani fw-bold px-4">
                        CANCEL
                    </a>
                    <button type="submit" class="btn btn-neon-pink font-orbitron fw-bold px-4" id="submitBtn">
                        <i class="bi bi-person-check-fill me-1"></i> PROVISION OPERATIVE
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
    const submitBtn = document.getElementById('submitBtn');

    if (role === 'Marshall') {
        cardMarshall.style.borderColor = '#a855f7';
        cardExecutor.style.borderColor = 'rgba(255, 255, 255, 0.15)';
        submitBtn.className = 'btn btn-neon-violet font-orbitron fw-bold px-4';
        submitBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> PROVISION MARSHALL';
    } else {
        cardExecutor.style.borderColor = '#00f0ff';
        cardMarshall.style.borderColor = 'rgba(255, 255, 255, 0.15)';
        submitBtn.className = 'btn btn-neon-cyan font-orbitron fw-bold px-4';
        submitBtn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> PROVISION EXECUTOR';
    }
}

function generateRandomKey() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let key = '';
    for (let i = 0; i < 10; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('temporary_password').value = key;
}

function copyTemporaryKey() {
    const input = document.getElementById('temporary_password');
    navigator.clipboard.writeText(input.value).then(() => {
        const icon = document.getElementById('copy-icon');
        icon.className = 'bi bi-check-lg text-neon-green';
        setTimeout(() => {
            icon.className = 'bi bi-clipboard';
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const selectedRole = document.querySelector('input[name="role"]:checked')?.value || 'Marshall';
    updateRoleStyling(selectedRole);
});
</script>
@endsection

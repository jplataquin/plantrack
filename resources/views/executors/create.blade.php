@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <!-- Back Navigation & Title -->
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('executors.index') }}" class="btn btn-neon-outline btn-sm me-3">
                <i class="bi bi-arrow-left"></i> BACK
            </a>
            <div>
                <h3 class="font-orbitron fw-bold text-white mb-0">PROVISION EXECUTOR ACCOUNT</h3>
                <span class="small font-rajdhani text-muted">MARSHALL AUTHORIZATION CLEARANCE REQUIRED</span>
            </div>
        </div>

        <!-- Info / Security Notice Callout -->
        <div class="card mb-4" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.3) !important;">
            <div class="card-body p-3 d-flex align-items-start gap-3">
                <i class="bi bi-shield-lock-fill text-neon-cyan fs-3 mt-1"></i>
                <div>
                    <div class="fw-bold text-white font-rajdhani">TEMPORARY PASSWORD & FIRST-LOGIN ENFORCEMENT</div>
                    <div class="small text-muted">
                        You are creating an Executor operative profile on behalf of an unregistered user. The user will be assigned the <strong>Executor</strong> role. Upon their first login, they will be strictly required to configure their personal password before accessing any system records.
                    </div>
                </div>
            </div>
        </div>

        <!-- Executor Account Creation Form -->
        <div class="card p-3 p-md-4">
            <form action="{{ route('executors.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label font-rajdhani text-uppercase text-muted small">
                        OPERATIVE FULL NAME <span class="text-neon-pink">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill text-neon-cyan"></i></span>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Commander Sarah Vance" required autofocus>
                    </div>
                    @error('name')
                        <div class="invalid-feedback d-block text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label font-rajdhani text-uppercase text-muted small">
                        OPERATIVE EMAIL ADDRESS <span class="text-neon-pink">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill text-neon-cyan"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="e.g. s.vance@plantrack.test" required>
                    </div>
                    <small class="text-muted">Must be an unregistered email address.</small>
                    @error('email')
                        <div class="invalid-feedback d-block text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="temporary_password" class="form-label font-rajdhani text-uppercase text-muted small mb-0">
                            TEMPORARY PASSWORD <span class="text-neon-pink">*</span>
                        </label>
                        <button type="button" class="btn btn-link text-neon-cyan text-decoration-none p-0 small font-rajdhani" onclick="generateRandomPassword()">
                            <i class="bi bi-shuffle me-1"></i> GENERATE NEW KEY
                        </button>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key-fill text-neon-cyan"></i></span>
                        <input type="text" class="form-control font-monospace @error('temporary_password') is-invalid @enderror" id="temporary_password" name="temporary_password" value="{{ old('temporary_password', $suggestedPassword) }}" required minlength="8">
                        <button class="btn btn-neon-outline px-3" type="button" onclick="copyPasswordToClipboard()" title="Copy temporary password">
                            <i class="bi bi-clipboard" id="copy-icon"></i>
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Provide a temporary key (min 8 characters). This password will be displayed once more upon submission for you to share securely with the user.
                    </small>
                    @error('temporary_password')
                        <div class="invalid-feedback d-block text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-3 border-top border-secondary">
                    <a href="{{ route('executors.index') }}" class="btn btn-neon-outline text-center">CANCEL</a>
                    <button type="submit" class="btn btn-neon-cyan">
                        <i class="bi bi-person-plus-fill me-1"></i> PROVISION EXECUTOR ACCOUNT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateRandomPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let result = '';
    for (let i = 0; i < 12; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('temporary_password').value = result;
}

function copyPasswordToClipboard() {
    const pwdInput = document.getElementById('temporary_password');
    navigator.clipboard.writeText(pwdInput.value).then(() => {
        const icon = document.getElementById('copy-icon');
        icon.className = 'bi bi-check-lg text-neon-green';
        setTimeout(() => {
            icon.className = 'bi bi-clipboard';
        }, 2000);
    });
}
</script>
@endsection

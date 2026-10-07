@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: rgba(255, 107, 0, 0.15); border: 1px solid rgba(255, 107, 0, 0.5); box-shadow: 0 0 20px rgba(255, 107, 0, 0.3);">
                <i class="bi bi-shield-exclamation text-neon-orange fs-1"></i>
            </div>
            <h3 class="font-orbitron fw-bold text-white mb-1">INITIAL ACCESS SECURITY</h3>
            <p class="text-muted small font-rajdhani">FIRST-LOGIN PASSWORD CONFIGURATION REQUIRED</p>
        </div>

        <div class="card p-3 p-md-4 mb-3">
            <div class="alert mb-4" style="background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 10px;">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill text-neon-cyan fs-5 mt-1"></i>
                    <div class="small">
                        Welcome, <strong class="text-white">{{ $user->name }}</strong> (<span class="text-neon-cyan">{{ $user->email }}</span>). Your Executor account was provisioned by a Marshall with a temporary access key. To secure your account, you must define a new personal password before accessing the system.
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('password.force_reset.update') }}">
                @csrf

                <!-- Current Temporary Password -->
                <div class="mb-3">
                    <label for="current_password" class="form-label font-rajdhani text-uppercase text-muted small">
                        CURRENT TEMPORARY PASSWORD <span class="text-neon-pink">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key text-neon-orange"></i></span>
                        <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required autocomplete="current-password" autofocus placeholder="Enter the temporary password provided to you">
                    </div>
                    @error('current_password')
                        <div class="invalid-feedback d-block text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-3">
                    <label for="password" class="form-label font-rajdhani text-uppercase text-muted small">
                        NEW PERMANENT PASSWORD <span class="text-neon-pink">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill text-neon-cyan"></i></span>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="At least 8 characters">
                    </div>
                    <small class="text-muted">Must be at least 8 characters and differ from your temporary password.</small>
                    @error('password')
                        <div class="invalid-feedback d-block text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm New Password -->
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label font-rajdhani text-uppercase text-muted small">
                        CONFIRM NEW PASSWORD <span class="text-neon-pink">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-check text-neon-cyan"></i></span>
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="Re-type your new password">
                    </div>
                </div>

                <button type="submit" class="btn btn-neon-orange w-100 py-2 font-rajdhani fw-bold fs-6">
                    <i class="bi bi-check-circle-fill me-2"></i> UPDATE PASSWORD & ENTER SYSTEM
                </button>
            </form>

            <div class="mt-4 pt-3 border-top border-secondary text-center">
                <span class="text-muted small">Need to sign out?</span>
                <a href="{{ route('logout') }}" class="text-neon-pink text-decoration-none small ms-2" onclick="event.preventDefault(); document.getElementById('force-reset-logout-form').submit();">
                    <i class="bi bi-box-arrow-left me-1"></i> Log Out
                </a>
                <form id="force-reset-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

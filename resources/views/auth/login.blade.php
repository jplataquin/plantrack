@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock-fill text-neon-cyan fs-1 d-block mb-2"></i>
            <h3 class="font-orbitron fw-bold text-white mb-1">PLAN<span class="text-neon-orange">TRACK</span></h3>
            <p class="text-muted small font-rajdhani">SYSTEM ACCESS VERIFICATION</p>
        </div>

        <div class="card p-3 p-md-4 mb-3">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label font-rajdhani text-uppercase text-muted small">OPERATIVE EMAIL</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                    @error('email')
                        <span class="invalid-feedback text-neon-violet" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label font-rajdhani text-uppercase text-muted small">ACCESS KEY / PASSWORD</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                    @error('password')
                        <span class="invalid-feedback text-neon-violet" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-muted small" for="remember">
                            Remember session
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-neon-pink w-100 py-2 font-rajdhani fw-bold fs-6">
                    <i class="bi bi-box-arrow-in-right me-2"></i> ACCESS SYSTEM
                </button>
            </form>
        </div>

        <div class="text-center text-muted small">
            Need an account? <a href="{{ route('register') }}" class="text-neon-cyan text-decoration-none fw-bold">Register</a>
        </div>
    </div>
</div>
@endsection

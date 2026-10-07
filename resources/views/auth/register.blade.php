@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="text-center mb-4">
            <i class="bi bi-person-badge-fill text-neon-pink fs-1 d-block mb-2"></i>
            <h3 class="font-orbitron fw-bold text-white mb-1">OPERATIVE <span class="text-neon-cyan">REGISTRATION</span></h3>
            <p class="text-muted small font-rajdhani">NEW USER CREDENTIAL PROVISIONING</p>
        </div>

        <div class="card p-3 p-md-4 mb-3">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label font-rajdhani text-uppercase text-muted small">OPERATIVE NAME</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="e.g. Alex Vance">

                    @error('name')
                        <span class="invalid-feedback text-neon-violet" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label font-rajdhani text-uppercase text-muted small">EMAIL ADDRESS</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@company.com">

                    @error('email')
                        <span class="invalid-feedback text-neon-violet" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label font-rajdhani text-uppercase text-muted small">PASSWORD</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                    @error('password')
                        <span class="invalid-feedback text-neon-violet" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password-confirm" class="form-label font-rajdhani text-uppercase text-muted small">CONFIRM PASSWORD</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-neon-cyan w-100 py-2 font-rajdhani fw-bold fs-6">
                    <i class="bi bi-person-check-fill me-2"></i> INITIALIZE ACCOUNT
                </button>
            </form>
        </div>

        <div class="text-center text-muted small">
            Already have credentials? <a href="{{ route('login') }}" class="text-neon-pink text-decoration-none fw-bold">Sign In</a>
        </div>
    </div>
</div>
@endsection

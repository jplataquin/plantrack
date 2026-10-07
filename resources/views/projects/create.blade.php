@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-6">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('projects.index') }}" class="btn btn-neon-outline btn-sm me-3">
                <i class="bi bi-arrow-left"></i> BACK
            </a>
            <h3 class="font-orbitron fw-bold text-white mb-0">PROVISION NEW PROJECT</h3>
        </div>

        <div class="card p-3 p-md-4" style="border-color: rgba(168, 85, 247, 0.4);">
            <div class="mb-3 p-3 rounded" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3);">
                <div class="d-flex align-items-center gap-2 text-neon-violet fw-bold font-rajdhani">
                    <i class="bi bi-shield-check fs-5"></i>
                    <span>MARSHALL PROTOCOL: PROJECT CREATION</span>
                </div>
                <small class="text-muted d-block mt-1">
                    Projects allow grouping and tracking plan records under organizational initiatives. The project name must be unique.
                </small>
            </div>

            <form action="{{ route('projects.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label font-rajdhani text-uppercase text-muted small">
                        PROJECT NAME <span class="text-neon-pink">*</span>
                    </label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Project Nova: Cybernetic Grid Modernization" required autofocus>
                    <small class="text-muted d-block mt-1">Unique title identifying the initiative.</small>
                    @error('name')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label font-rajdhani text-uppercase text-muted small">
                        INITIAL STATUS <span class="text-neon-pink">*</span>
                    </label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>ACTIVE (Available for Plan Records)</option>
                        <option value="Deactive" {{ old('status') === 'Deactive' ? 'selected' : '' }}>DEACTIVE (Suspended / Archive Only)</option>
                    </select>
                    <small class="text-muted d-block mt-1">Only active projects are selectable in Plan Record assignment dropdowns.</small>
                    @error('status')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-3 border-top border-secondary">
                    <a href="{{ route('projects.index') }}" class="btn btn-neon-outline text-center">CANCEL</a>
                    <button type="submit" class="btn btn-neon-outline-violet">
                        <i class="bi bi-folder-plus me-1 text-neon-violet"></i> INITIALIZE PROJECT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

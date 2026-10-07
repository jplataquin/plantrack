@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7 col-xl-6">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('projects.show', $project) }}" class="btn btn-neon-outline btn-sm me-3">
                <i class="bi bi-arrow-left"></i> BACK
            </a>
            <h3 class="font-orbitron fw-bold text-white mb-0">EDIT PROJECT RECORD</h3>
        </div>

        <div class="card p-3 p-md-4" style="border-color: rgba(168, 85, 247, 0.4);">
            <div class="mb-3 p-3 rounded" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-neon-violet fw-bold font-rajdhani">
                        <i class="bi bi-pencil-square me-1"></i> MODIFYING PROJECT #{{ $project->id }}
                    </span>
                    <span class="{{ $project->status_badge_class }} px-2 py-0.5 rounded-pill small">
                        CURRENT: {{ strtoupper($project->status) }}
                    </span>
                </div>
            </div>

            <form action="{{ route('projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label font-rajdhani text-uppercase text-muted small">
                        PROJECT NAME <span class="text-neon-pink">*</span>
                    </label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $project->name) }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label font-rajdhani text-uppercase text-muted small">
                        PROJECT STATUS <span class="text-neon-pink">*</span>
                    </label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="Active" {{ old('status', $project->status) === 'Active' ? 'selected' : '' }}>ACTIVE (Available for Plan Records)</option>
                        <option value="Deactive" {{ old('status', $project->status) === 'Deactive' ? 'selected' : '' }}>DEACTIVE (Suspended / Archive Only)</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        Deactivating a project removes it from future plan record creation dropdowns, while preserving past links.
                    </small>
                    @error('status')
                        <div class="invalid-feedback text-neon-violet">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-3 border-top border-secondary">
                    <button type="button" class="btn btn-outline-danger mobile-w-100" onclick="if(confirm('Are you sure you want to soft delete project \'{{ addslashes($project->name) }}\'? Assigned plan records will be preserved.')) document.getElementById('delete-project-form').submit();">
                        <i class="bi bi-trash-fill me-1"></i> DELETE PROJECT
                    </button>

                    <div class="d-flex gap-2 mobile-w-100 justify-content-end">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-neon-outline flex-fill flex-sm-grow-0 text-center">CANCEL</a>
                        <button type="submit" class="btn btn-neon-outline-violet flex-fill flex-sm-grow-0">
                            <i class="bi bi-check2-circle me-1 text-neon-violet"></i> UPDATE PROJECT
                        </button>
                    </div>
                </div>
            </form>

            <form id="delete-project-form" action="{{ route('projects.destroy', $project) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection

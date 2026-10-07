@php
    $isMarshall = $isMarshall ?? Auth::user()?->hasAnyRole(['Marshall', 'Admin']);
@endphp
<div class="col-12 col-md-6 col-lg-4 target-item" id="target-item-{{ $target->id }}" data-target-id="{{ $target->id }}" data-target-description="{{ $target->description }}">
    <div class="card card-hover h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(0, 240, 255, 0.3);">
        <div>
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="target-priority-badge {{ $target->priority_badge_class }} px-2 py-0.5 rounded small me-1">
                        <i class="bi bi-flag-fill me-1"></i>{{ strtoupper($target->priority ?? 'low') }}
                    </span>
                    <h6 class="text-white fw-bold mb-0 font-rajdhani fs-6 fs-md-5 mt-1">{{ $target->description }}</h6>
                </div>
                @if ($isMarshall)
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link text-muted p-0 hover-cyan" data-bs-toggle="modal" data-bs-target="#editTargetModal-{{ $target->id }}" title="Edit Target">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('targets.destroy', $target) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove target?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-muted p-0 hover-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="p-2 rounded mb-3" style="background: rgba(255, 255, 255, 0.03);">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted"><i class="bi bi-box me-1 text-neon-cyan"></i>Quantity:</span>
                    <strong class="text-white">{{ $target->quantity_with_unit }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted"><i class="bi bi-check2-all me-1 text-neon-green"></i>Actual:</span>
                    <div class="d-flex align-items-center gap-1">
                        <strong class="{{ $target->actual !== null && $target->actual !== '' ? 'text-neon-green' : 'text-muted' }}">
                            {{ $target->actual_with_unit }}
                        </strong>
                        @if ($isMarshall || $plan->status === 'Open')
                            <button type="button" class="btn btn-link text-neon-cyan p-0 ms-1" data-bs-toggle="modal" data-bs-target="#editActualModal-{{ $target->id }}" title="Fill / Update Actual Accomplished">
                                <i class="bi bi-pencil-square" style="font-size: 0.75rem;"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Remarks Section -->
        <div class="mt-2 pt-2 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                @if ($isMarshall)
                    <div class="d-flex align-items-center gap-1">
                        <select class="form-select form-select-sm ajax-status-select" data-url="{{ route('targets.status.update', $target) }}" style="width: 125px;">
                            <option value="" {{ is_null($target->status) ? 'selected' : '' }}>PENDING</option>
                            <option value="Hit" {{ $target->status === 'Hit' ? 'selected' : '' }}>HIT</option>
                            <option value="Missed" {{ $target->status === 'Missed' ? 'selected' : '' }}>MISSED</option>
                            <option value="Void" {{ $target->status === 'Void' ? 'selected' : '' }}>VOID</option>
                        </select>
                        <div class="spinner-border spinner-border-sm text-neon-cyan status-spinner d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @else
                    <span class="text-muted small font-rajdhani text-uppercase">Evaluation:</span>
                @endif

                <span class="component-badge {{ $target->status_badge_class }} px-2 py-1 rounded">
                    {{ $target->status ?: 'Unevaluated' }}
                </span>
            </div>

            <!-- Remarks Trigger (Opens Full-Screen Modal) -->
            <button class="btn btn-neon-outline btn-sm w-100 fw-bold d-flex align-items-center justify-content-between" type="button" data-bs-toggle="modal" data-bs-target="#remarksModal-target-{{ $target->id }}">
                <span><i class="bi bi-chat-quote-fill me-1"></i>REMARKS</span>
                <span class="badge remarks-badge-count" data-thread-key="target_objective-{{ $target->id }}" style="background: rgba(0, 240, 255, 0.2); color: #00f0ff;">{{ $target->comments->count() }}</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal for Filling / Updating Actual Accomplished -->
<div class="modal fade" id="editActualModal-{{ $target->id }}" tabindex="-1" aria-labelledby="editActualModalLabel-{{ $target->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('targets.actual.update', $target) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-cyan); box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);">
                <div class="modal-header border-bottom border-secondary py-2 px-3">
                    <h6 class="modal-title font-orbitron text-white mb-0 fs-6" id="editActualModalLabel-{{ $target->id }}">
                        <i class="bi bi-check2-circle text-neon-green me-1"></i>ACTUAL ACCOMPLISHED
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3 p-2 rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06);">
                        <small class="text-muted d-block font-rajdhani">TARGET GOAL:</small>
                        <strong class="text-white">{{ $target->quantity_with_unit }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">ACTUAL QUANTITY ACCOMPLISHED</label>
                        <input type="text" name="actual" class="form-control" value="{{ $target->actual }}" placeholder="e.g. 95" autocomplete="off">
                        <small class="text-muted" style="font-size: 0.7rem;">Enter actual output or quantity delivered.</small>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-neon-cyan px-3 font-rajdhani fw-bold">SAVE ACTUAL</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($isMarshall)
    <!-- Modal for Editing Target Details (Marshall only) -->
    <div class="modal fade" id="editTargetModal-{{ $target->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered component-modal-dialog">
            <form action="{{ route('targets.update', $target) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-cyan);">
                    <div class="modal-header border-bottom border-secondary">
                        <h5 class="modal-title font-orbitron text-neon-cyan"><i class="bi bi-pencil-square me-2"></i>EDIT TARGET / OBJECTIVE</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET DESCRIPTION *</label>
                            <textarea name="description" class="form-control" rows="3" required>{{ $target->description }}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET QUANTITY *</label>
                                <input type="text" name="quantity" class="form-control" value="{{ $target->quantity }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT OF MEASURE</label>
                                <input type="text" name="unit" class="form-control" value="{{ $target->unit }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label class="form-label text-muted small font-rajdhani text-uppercase">PRIORITY *</label>
                                <select name="priority" class="form-select" required>
                                    <option value="low" {{ strtolower($target->priority ?? 'low') === 'low' ? 'selected' : '' }}>LOW</option>
                                    <option value="normal" {{ strtolower($target->priority ?? 'low') === 'normal' ? 'selected' : '' }}>NORMAL</option>
                                    <option value="high" {{ strtolower($target->priority ?? 'low') === 'high' ? 'selected' : '' }}>HIGH</option>
                                    <option value="critical" {{ strtolower($target->priority ?? 'low') === 'critical' ? 'selected' : '' }}>CRITICAL</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-neon-cyan">Update Target</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- ==================== FULL-SCREEN MODAL FOR TARGET REMARKS ==================== -->
<div class="modal fade" id="remarksModal-target-{{ $target->id }}" tabindex="-1" aria-labelledby="remarksModalLabel-target-{{ $target->id }}" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header px-4 py-3 border-bottom border-secondary d-flex align-items-center justify-content-between" style="background: #120f24;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-neon-outline btn-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-arrow-left me-1"></i> BACK TO PLAN
                    </button>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: rgba(0, 240, 255, 0.2); color: #00f0ff; border: 1px solid #00f0ff;">TARGET / OBJECTIVE</span>
                            <span class="target-priority-badge {{ $target->priority_badge_class }} px-2 py-0.5 rounded-pill small"><i class="bi bi-flag-fill me-1"></i>{{ strtoupper($target->priority ?? 'low') }}</span>
                            <span class="{{ $target->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ $target->status ?: 'Unevaluated' }}</span>
                        </div>
                        <h4 class="font-orbitron fw-bold text-white mb-0 mt-1">{{ $target->description }}</h4>
                        <small class="text-muted">Priority: <strong class="text-white">{{ $target->priority ?? 'low' }}</strong> &bull; Quantity: <strong class="text-white">{{ $target->quantity_with_unit }}</strong> &bull; Actual: <strong class="text-neon-green">{{ $target->actual_with_unit }}</strong></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="background: #0a0814;">
                <div class="container" style="max-width: 900px;">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-secondary">
                        <h5 class="font-rajdhani fw-bold text-white mb-0">
                            <i class="bi bi-chat-dots-fill text-neon-cyan me-2"></i>EVALUATION REMARKS & DISCUSSION (<span class="modal-remarks-count" data-thread-key="target_objective-{{ $target->id }}">{{ $target->comments->count() }}</span>)
                        </h5>
                        <small class="text-muted">Marshalls and Executors can evaluate and remark</small>
                    </div>

                    <div class="remarks-thread-list" data-thread-key="target_objective-{{ $target->id }}" data-neon-color="#00f0ff" data-outline-class="btn-neon-outline" data-border-class="rgba(0, 240, 255, 0.3)">
                        @forelse ($target->comments as $comment)
                            <div class="card mb-3 p-3" style="background: #120f24; border: 1px solid {{ $comment->user && $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user && $comment->user->hasRole('Marshall') ? '#a855f7' : 'rgba(0, 240, 255, 0.3)') }};">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($comment->user)
                                            <a href="{{ route('profile.show', $comment->user) }}" class="text-decoration-none" title="View {{ $comment->user->name }}'s Profile">
                                                @if ($comment->user->profile_picture)
                                                    <img src="{{ asset('storage/' . $comment->user->profile_picture) }}" alt="{{ $comment->user->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 32px; height: 32px; border: 1.5px solid {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? 'var(--neon-violet)' : 'var(--neon-cyan)') }};">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? '#a855f7' : '#00f0ff') }}; color: {{ $comment->user->hasRole('Admin') || $comment->user->hasRole('Marshall') ? '#fff' : '#0a0814' }};">
                                                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </a>
                                        @else
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: #6c757d; color: #fff;">
                                                U
                                            </div>
                                        @endif
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                @if ($comment->user)
                                                    <a href="{{ route('profile.show', $comment->user) }}" class="fw-bold text-white text-decoration-none user-profile-link" title="View {{ $comment->user->name }}'s Profile">
                                                        {{ $comment->user->name }}
                                                    </a>
                                                @else
                                                    <span class="fw-bold text-muted">Unknown Operative</span>
                                                @endif
                                                @if ($comment->user && $comment->user->hasRole('Admin'))
                                                    <span class="badge badge-role-admin px-2 py-0.5 rounded-pill small">ADMIN</span>
                                                @elseif ($comment->user && $comment->user->hasRole('Marshall'))
                                                    <span class="badge badge-role-marshall px-2 py-0.5 rounded-pill small">MARSHALL</span>
                                                @elseif ($comment->user && $comment->user->hasRole('Executor'))
                                                    <span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">EXECUTOR</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $comment->created_at->format('M d, Y h:i A') }} ({{ $comment->created_at->diffForHumans() }})</small>
                                        </div>
                                    </div>

                                    @if (Auth::user()->hasAnyRole(['Marshall', 'Admin']) || ($plan->status === 'Open' && Auth::id() === $comment->user_id))
                                        <form action="{{ route('comments.destroy', $comment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this remark?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2" title="Delete Remark">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <p class="mb-0 text-white mt-1 fs-6" style="white-space: pre-wrap;">{{ $comment->body }}</p>

                                @if ($comment->attachments->isNotEmpty())
                                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex flex-wrap gap-2">
                                        @foreach ($comment->attachments as $att)
                                            @if ($att->is_image)
                                                <div class="comment-attachment-card d-inline-flex flex-column rounded overflow-hidden position-relative" style="background: rgba(18, 15, 36, 0.9); border: 1px solid rgba(0, 240, 255, 0.35); width: 124px;">
                                                    <div class="position-relative overflow-hidden cursor-pointer attachment-thumbnail-trigger"
                                                         data-attachment-id="{{ $att->id }}"
                                                         data-url="{{ $att->url }}"
                                                         data-name="{{ $att->original_name }}"
                                                         data-size="{{ $att->formatted_size }}"
                                                         data-download="{{ route('comments.attachments.download', $att) }}"
                                                         style="height: 92px; background: #0a0814;"
                                                         title="Click to view slideshow preview">
                                                        <img src="{{ $att->url }}" alt="{{ $att->original_name }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                                                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-40 opacity-0 hover-opacity-100 transition-opacity">
                                                            <i class="bi bi-arrows-fullscreen text-neon-cyan fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 px-2 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03);">
                                                        <div class="text-truncate me-1" style="max-width: 82px;" title="{{ $att->original_name }}">
                                                            <span class="d-block small text-white text-truncate fw-bold" style="font-size: 0.68rem;">{{ $att->original_name }}</span>
                                                            <small class="text-muted d-block" style="font-size: 0.62rem;">{{ $att->formatted_size }}</small>
                                                        </div>
                                                        <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-link text-neon-cyan p-0" title="Download">
                                                            <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-inline-flex align-items-center gap-2 p-2 rounded" style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.25); max-width: 240px;">
                                                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(0, 240, 255, 0.1);">
                                                        <i class="bi {{ $att->icon_class }} fs-5"></i>
                                                    </div>
                                                    <div class="text-truncate flex-grow-1" style="min-width: 0;">
                                                        <span class="d-block small text-white text-truncate fw-bold" title="{{ $att->original_name }}">{{ $att->original_name }}</span>
                                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $att->formatted_size }}</small>
                                                    </div>
                                                    <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-neon-outline p-1 px-2 ms-1 flex-shrink-0" title="Download Attachment">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted remarks-empty-state">
                                <i class="bi bi-chat-square-text fs-1 text-neon-cyan d-block mb-2"></i>
                                <h5 class="text-white font-orbitron">NO REMARKS RECORDED</h5>
                                <p class="small">Enter evaluation feedback or progress notes below.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="modal-footer px-3 px-md-4 py-3 border-top border-secondary w-100" style="background: #120f24;">
                <div class="w-100 d-flex flex-column align-items-center">
                    @if ($isMarshall || $plan->status === 'Open')
                        <form action="{{ route('comments.store') }}" method="POST" class="w-100 remark-form d-flex flex-column align-items-center">
                            @csrf
                            <input type="hidden" name="commentable_type" value="target_objective">
                            <input type="hidden" name="commentable_id" value="{{ $target->id }}">
                            <div class="staged-attachment-ids-container w-100"></div>

                            <!-- Staged Attachments Container (Up to 5 files) -->
                            <div class="remark-staged-container d-none mb-2 p-2 rounded w-100" style="background: rgba(0, 240, 255, 0.05); border: 1px dashed rgba(0, 240, 255, 0.4);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-neon-cyan font-rajdhani fw-bold text-uppercase">
                                        <i class="bi bi-paperclip me-1"></i>STAGED ATTACHMENTS (<span class="staged-count">0</span>/5)
                                    </small>
                                    <small class="text-muted" style="font-size: 0.7rem;">Max 5 files &bull; Max 5MB each</small>
                                </div>
                                <div class="staged-items-list d-flex flex-column gap-1"></div>
                            </div>

                            <!-- Chunk Upload Progress Bar -->
                            <div class="remark-upload-progress d-none mb-2 w-100">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-neon-cyan progress-status-text"><i class="bi bi-arrow-repeat spin me-1"></i>Uploading chunk to staging...</span>
                                    <span class="text-white fw-bold progress-percent-text">0%</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div class="mb-2 w-100 d-flex justify-content-center">
                                <textarea name="body" class="form-control custom-scrollbar remark-body-textarea w-100" rows="3" placeholder="Type evaluation remark or response..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                                <label class="btn btn-neon-outline d-flex align-items-center gap-1 mb-0 px-3 cursor-pointer remark-attach-btn" title="Attach up to 5 files (Max 5MB each)">
                                    <i class="bi bi-paperclip"></i>
                                    <span class="attach-btn-label">Attach files</span>
                                    <input type="file" class="d-none remark-file-input" multiple>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted d-none d-sm-inline font-monospace" style="font-size: 0.75rem;">Ctrl+Enter to post</small>
                                    <button type="submit" class="btn btn-neon-cyan px-4 font-rajdhani fw-bold">
                                        <i class="bi bi-send-fill me-1"></i> POST REMARK
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-dark border-secondary text-muted small w-100 mb-0 d-flex align-items-center justify-content-center text-center gap-2" style="background: rgba(255, 255, 255, 0.03);">
                            <i class="bi bi-lock-fill text-neon-orange fs-5"></i>
                            <span>Component remarks and input are locked because this Plan Record status is <strong>{{ strtoupper($plan->status) }}</strong>. Only plans in <strong>OPEN</strong> status accept operative contributions.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

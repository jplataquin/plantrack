@php
    $isMarshall = $isMarshall ?? Auth::user()?->hasAnyRole(['Marshall', 'Admin']);
    $isAssignedExecutor = $isAssignedExecutor ?? (Auth::id() === $plan->executor_id);
    $canEditRisk = $isMarshall || (! $risk->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $risk->user_id === Auth::id());
    $canDeleteRisk = $isMarshall || (! $risk->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $risk->user_id === Auth::id());
    $canUpdateStatus = $isMarshall || (! $risk->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $risk->user_id === Auth::id());

    // Extract level number for concise "Lv {number}" badge (e.g., Lv 1, Lv 2, Lv 3)
    if (preg_match('/(\d+)/', (string) $risk->impact, $impactMatches)) {
        $impactLevel = 'Lv ' . $impactMatches[1];
    } else {
        $impactLevel = $risk->impact ?: 'Lv 1';
    }
@endphp
<div class="col-12 col-md-6 col-lg-4 risk-item" id="risk-item-{{ $risk->id }}">
    <div class="card card-hover h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(168, 85, 247, 0.35);">
        <div>
            <!-- Top Bar: Impact Level "Lv {number}" & Actions -->
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <span class="badge font-orbitron px-2 py-1 fs-6" style="background: rgba(168, 85, 247, 0.15); color: var(--neon-violet); border: 1px solid var(--neon-violet);" title="Impact Level: {{ $risk->impact }}">
                    {{ $impactLevel }}
                </span>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    @if ($canEditRisk)
                        <button type="button" class="btn btn-link text-neon-violet p-0" data-bs-toggle="modal" data-bs-target="#editRiskModal-{{ $risk->id }}" title="Edit Risk">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    @endif
                    @if ($canDeleteRisk)
                        <form action="{{ route('risks.destroy', $risk) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove risk?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-muted p-0 hover-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Violet Container: Risk Description -->
            <div class="p-2 rounded mb-2" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.25);">
                <span class="text-neon-violet small fw-bold font-rajdhani text-uppercase d-block mb-1">
                    <i class="bi bi-shield-exclamation me-1"></i>IDENTIFIED RISK
                </span>
                <p class="mb-0 text-white small fw-semibold" style="white-space: pre-wrap;">{{ $risk->risk }}</p>
            </div>

            <!-- Green Container: Mitigation Strategy -->
            <div class="p-2 rounded mb-3" style="background: rgba(0, 255, 136, 0.08); border: 1px solid rgba(0, 255, 136, 0.25);">
                <span class="text-neon-green small fw-bold font-rajdhani text-uppercase d-block mb-1">
                    <i class="bi bi-shield-check me-1"></i>MITIGATION STRATEGY
                </span>
                <p class="mb-0 text-white small" style="white-space: pre-wrap;">{{ $risk->mitigation }}</p>
            </div>
        </div>

        <!-- Status & Remarks Section -->
        <div class="mt-2 pt-2 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                @if ($canUpdateStatus)
                    <div class="d-flex align-items-center gap-1">
                        <select class="form-select form-select-sm ajax-status-select" data-url="{{ route('risks.status.update', $risk) }}" style="width: 125px;">
                            <option value="" {{ is_null($risk->status) ? 'selected' : '' }}>PENDING</option>
                            <option value="Hit" {{ $risk->status === 'Hit' ? 'selected' : '' }}>HIT</option>
                            <option value="Missed" {{ $risk->status === 'Missed' ? 'selected' : '' }}>MISSED</option>
                            <option value="Void" {{ $risk->status === 'Void' ? 'selected' : '' }}>VOID</option>
                        </select>
                        <div class="spinner-border spinner-border-sm text-neon-violet status-spinner d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @else
                    <span class="text-muted small font-rajdhani text-uppercase">Evaluation:</span>
                @endif

                <span class="component-badge {{ $risk->status_badge_class }} px-2 py-1 rounded">
                    {{ $risk->status ?: 'Unevaluated' }}
                </span>
            </div>

            <!-- Remarks Trigger (Opens Full-Screen Modal) -->
            <button class="btn btn-neon-outline-violet btn-sm w-100 fw-bold d-flex align-items-center justify-content-between" type="button" data-bs-toggle="modal" data-bs-target="#remarksModal-risk-{{ $risk->id }}">
                <span><i class="bi bi-chat-quote-fill me-1"></i>REMARKS</span>
                <span class="badge remarks-badge-count" data-thread-key="risk_management-{{ $risk->id }}" style="background: rgba(168, 85, 247, 0.2); color: #c084fc;">{{ $risk->comments->count() }}</span>
            </button>
        </div>
    </div>
</div>

<!-- ==================== FULL-SCREEN MODAL FOR RISK REMARKS ==================== -->
<div class="modal fade" id="remarksModal-risk-{{ $risk->id }}" tabindex="-1" aria-labelledby="remarksModalLabel-risk-{{ $risk->id }}" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header px-4 py-3 border-bottom border-secondary d-flex align-items-center justify-content-between" style="background: #120f24;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-neon-outline btn-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-arrow-left me-1"></i> BACK TO PLAN
                    </button>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid #a855f7;">RISK MANAGEMENT</span>
                            <span class="{{ $risk->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ $risk->status ?: 'Unevaluated' }}</span>
                        </div>
                        <h4 class="font-orbitron fw-bold text-white mb-0 mt-1">{{ $risk->risk }}</h4>
                        <small class="text-muted">Impact: <strong class="text-white">{{ $risk->impact }}</strong> &bull; Mitigation: <strong class="text-white">{{ $risk->mitigation }}</strong></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="background: #0a0814;">
                <div class="container" style="max-width: 900px;">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-secondary">
                        <h5 class="font-rajdhani fw-bold text-white mb-0">
                            <i class="bi bi-chat-dots-fill text-neon-violet me-2"></i>RISK MITIGATION REMARKS & DISCUSSION (<span class="modal-remarks-count" data-thread-key="risk_management-{{ $risk->id }}">{{ $risk->comments->count() }}</span>)
                        </h5>
                        <small class="text-muted">Assessment remarks and mitigation progress</small>
                    </div>

                    <div class="remarks-thread-list" data-thread-key="risk_management-{{ $risk->id }}" data-neon-color="#a855f7" data-outline-class="btn-neon-outline-violet" data-border-class="rgba(168, 85, 247, 0.35)">
                        @forelse ($risk->comments as $comment)
                            <div class="card mb-3 p-3" style="background: #120f24; border: 1px solid {{ $comment->user && $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user && $comment->user->hasRole('Marshall') ? '#a855f7' : 'rgba(168, 85, 247, 0.35)') }};">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($comment->user)
                                            <a href="{{ route('profile.show', $comment->user) }}" class="text-decoration-none" title="View {{ $comment->user->name }}'s Profile">
                                                @if ($comment->user->profile_picture)
                                                    <img src="{{ asset('storage/' . $comment->user->profile_picture) }}" alt="{{ $comment->user->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 32px; height: 32px; border: 1.5px solid {{ $comment->user->hasRole('Admin') ? '#ff0055' : 'var(--neon-violet)' }};">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: {{ $comment->user->hasRole('Admin') ? '#ff0055' : '#a855f7' }}; color: #ffffff;">
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
                                                <div class="comment-attachment-card d-inline-flex flex-column rounded overflow-hidden position-relative" style="background: rgba(18, 15, 36, 0.9); border: 1px solid rgba(168, 85, 247, 0.35); width: 124px;">
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
                                                            <i class="bi bi-arrows-fullscreen text-neon-violet fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 px-2 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03);">
                                                        <div class="text-truncate me-1" style="max-width: 82px;" title="{{ $att->original_name }}">
                                                            <span class="d-block small text-white text-truncate fw-bold" style="font-size: 0.68rem;">{{ $att->original_name }}</span>
                                                            <small class="text-muted d-block" style="font-size: 0.62rem;">{{ $att->formatted_size }}</small>
                                                        </div>
                                                        <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-link text-neon-violet p-0" title="Download">
                                                            <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-inline-flex align-items-center gap-2 p-2 rounded" style="background: rgba(168, 85, 247, 0.05); border: 1px solid rgba(168, 85, 247, 0.25); max-width: 240px;">
                                                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(168, 85, 247, 0.1);">
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
                                <i class="bi bi-chat-square-text fs-1 text-neon-violet d-block mb-2"></i>
                                <h5 class="text-white font-orbitron">NO REMARKS RECORDED</h5>
                                <p class="small">Enter risk mitigation notes or tracking updates below.</p>
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
                            <input type="hidden" name="commentable_type" value="risk_management">
                            <input type="hidden" name="commentable_id" value="{{ $risk->id }}">
                            <div class="staged-attachment-ids-container w-100"></div>

                            <!-- Staged Attachments Container (Up to 5 files) -->
                            <div class="remark-staged-container d-none mb-2 p-2 rounded w-100" style="background: rgba(168, 85, 247, 0.05); border: 1px dashed rgba(168, 85, 247, 0.4);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-neon-violet font-rajdhani fw-bold text-uppercase">
                                        <i class="bi bi-paperclip me-1"></i>STAGED ATTACHMENTS (<span class="staged-count">0</span>/5)
                                    </small>
                                    <small class="text-muted" style="font-size: 0.7rem;">Max 5 files &bull; Max 5MB each</small>
                                </div>
                                <div class="staged-items-list d-flex flex-column gap-1"></div>
                            </div>

                            <!-- Chunk Upload Progress Bar -->
                            <div class="remark-upload-progress d-none mb-2 w-100">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-neon-violet progress-status-text"><i class="bi bi-arrow-repeat spin me-1"></i>Uploading chunk to staging...</span>
                                    <span class="text-white fw-bold progress-percent-text">0%</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div class="mb-2 w-100 d-flex justify-content-center">
                                <textarea name="body" class="form-control custom-scrollbar remark-body-textarea w-100" rows="3" placeholder="Type risk mitigation remark or update..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                                <label class="btn btn-neon-outline d-flex align-items-center gap-1 mb-0 px-3 cursor-pointer remark-attach-btn" title="Attach up to 5 files (Max 5MB each)">
                                    <i class="bi bi-paperclip"></i>
                                    <span class="attach-btn-label">Attach files</span>
                                    <input type="file" class="d-none remark-file-input" multiple>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted d-none d-sm-inline font-monospace" style="font-size: 0.75rem;">Ctrl+Enter to post</small>
                                    <button type="submit" class="btn btn-neon-violet px-4 font-rajdhani fw-bold">
                                        <i class="bi bi-send-fill me-1"></i> POST REMARK
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-dark border-secondary text-muted small w-100 mb-0 d-flex align-items-center justify-content-center text-center gap-2" style="background: rgba(255, 255, 255, 0.03);">
                            <i class="bi bi-lock-fill text-neon-orange fs-5"></i>
                            <span>Risk remarks and mitigation tracking are locked because this Plan Record status is <strong>{{ strtoupper($plan->status) }}</strong>. Only plans in <strong>OPEN</strong> status accept operative contributions.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canEditRisk)
    <!-- Modal for Editing Risk Details -->
    <div class="modal fade" id="editRiskModal-{{ $risk->id }}" tabindex="-1" aria-labelledby="editRiskModalLabel-{{ $risk->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered component-modal-dialog">
            <form action="{{ route('risks.update', $risk) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-violet); box-shadow: 0 0 20px rgba(168, 85, 247, 0.2);">
                    <div class="modal-header border-bottom border-secondary">
                        <h5 class="modal-title font-orbitron text-neon-violet" id="editRiskModalLabel-{{ $risk->id }}">
                            <i class="bi bi-pencil-square me-2"></i>EDIT RISK ITEM
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">IDENTIFIED RISK *</label>
                            <textarea name="risk" class="form-control" rows="3" placeholder="Risk description..." required>{{ old('risk', $risk->risk) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">POTENTIAL IMPACT *</label>
                            <select name="impact" class="form-select" required>
                                <option value="">SELECT IMPACT LEVEL</option>
                                <option value="Lv 1 - Only one target object is affected" {{ old('impact', $risk->impact) === 'Lv 1 - Only one target object is affected' ? 'selected' : '' }}>Lv 1 - Only one target object is affected</option>
                                <option value="Lv 2 - At least 2 or more target objectives are affected" {{ old('impact', $risk->impact) === 'Lv 2 - At least 2 or more target objectives are affected' ? 'selected' : '' }}>Lv 2 - At least 2 or more target objectives are affected</option>
                                <option value="Lv 3 - All target objectives are affected" {{ old('impact', $risk->impact) === 'Lv 3 - All target objectives are affected' ? 'selected' : '' }}>Lv 3 - All target objectives are affected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">MITIGATION PLAN / CONTINGENCY *</label>
                            <textarea name="mitigation" class="form-control" rows="3" placeholder="Contingency steps..." required>{{ old('mitigation', $risk->mitigation) }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-neon-violet btn-sm px-3">UPDATE RISK ITEM</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

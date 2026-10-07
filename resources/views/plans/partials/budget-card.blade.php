@php
    $isMarshall = $isMarshall ?? Auth::user()?->hasAnyRole(['Marshall', 'Admin']);
    $canEdit = $isMarshall;
    $canDelete = $isMarshall;
    $canUpdateStatus = $isMarshall;
@endphp
<div class="col-12 col-md-6 col-lg-4 budget-item" id="budget-item-{{ $budget->id }}">
    <div class="card card-hover h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(255, 230, 0, 0.35);">
        <div>
            @if ($budget->targetObjective)
                <div class="mb-2">
                    <span class="badge text-truncate d-inline-block" style="background: rgba(255, 230, 0, 0.12); color: var(--neon-yellow); border: 1px solid rgba(255, 230, 0, 0.35); font-size: 0.72rem; max-width: 100%;" title="Linked Objective: {{ $budget->targetObjective->description }}">
                        <i class="bi bi-bullseye me-1"></i>FOR: {{ Str::limit($budget->targetObjective->description, 36) }}
                    </span>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 class="text-white fw-bold mb-0 font-rajdhani fs-6 fs-md-5" title="{{ $budget->description }}">{{ Str::limit($budget->description, 200) }}</h6>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    @if ($canEdit)
                        <button type="button" class="btn btn-link text-neon-yellow p-0" data-bs-toggle="modal" data-bs-target="#editBudgetModal-{{ $budget->id }}" title="Edit Budget">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    @endif
                    @if ($canDelete)
                        <form action="{{ route('budgets.destroy', $budget) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove budget item?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-muted p-0 hover-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="p-2 rounded mb-3" style="background: rgba(255, 255, 255, 0.03);">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted"><i class="bi bi-cash-stack me-1 text-neon-yellow"></i>Quantity / Allocation:</span>
                    <strong class="text-white">{{ $budget->quantity_with_unit }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted"><i class="bi bi-check2-all me-1 text-neon-green"></i>Actual Quantity:</span>
                    <div class="d-flex align-items-center gap-1">
                        <strong class="{{ $budget->actual !== null && $budget->actual !== '' ? 'text-neon-green' : 'text-muted' }}" id="budget-actual-display-{{ $budget->id }}">
                            {{ $budget->actual_with_unit }}
                        </strong>
                        @if ($canEdit)
                            <button type="button" class="btn btn-link text-neon-yellow p-0 ms-1" data-bs-toggle="modal" data-bs-target="#editBudgetActualModal-{{ $budget->id }}" title="Fill / Update Actual Quantity">
                                <i class="bi bi-pencil-square" style="font-size: 0.75rem;"></i>
                            </button>
                        @endif
                    </div>
                </div>
                @if ($budget->targetObjective)
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-muted"><i class="bi bi-bullseye me-1 text-neon-cyan"></i>For Objective:</span>
                        <strong class="text-neon-cyan small text-truncate ms-2" style="max-width: 170px;" title="{{ $budget->targetObjective->description }}">
                            {{ $budget->targetObjective->description }}
                        </strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Status & Remarks Section -->
        <div class="mt-2 pt-2 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                @if ($canUpdateStatus)
                    <div class="d-flex align-items-center gap-1">
                        <select class="form-select form-select-sm ajax-status-select" data-url="{{ route('budgets.status.update', $budget) }}" style="width: 125px;">
                            <option value="" {{ is_null($budget->status) ? 'selected' : '' }}>PENDING</option>
                            <option value="Hit" {{ $budget->status === 'Hit' ? 'selected' : '' }}>HIT</option>
                            <option value="Missed" {{ $budget->status === 'Missed' ? 'selected' : '' }}>MISSED</option>
                            <option value="Void" {{ $budget->status === 'Void' ? 'selected' : '' }}>VOID</option>
                        </select>
                        <div class="spinner-border spinner-border-sm text-neon-yellow status-spinner d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @else
                    <span class="text-muted small font-rajdhani text-uppercase">Evaluation:</span>
                @endif

                <span class="component-badge {{ $budget->status_badge_class }} px-2 py-1 rounded">
                    {{ $budget->status ?: 'Unevaluated' }}
                </span>
            </div>

            <!-- Remarks Trigger (Opens Full-Screen Modal) -->
            <button class="btn btn-neon-outline-yellow btn-sm w-100 fw-bold d-flex align-items-center justify-content-between" type="button" data-bs-toggle="modal" data-bs-target="#remarksModal-budget-{{ $budget->id }}">
                <span><i class="bi bi-chat-quote-fill me-1"></i>REMARKS</span>
                <span class="badge remarks-badge-count" data-thread-key="budget-{{ $budget->id }}" style="background: rgba(255, 230, 0, 0.2); color: #ffe600;">{{ $budget->comments->count() }}</span>
            </button>
        </div>
    </div>
</div>

@if ($canEdit)
<!-- Modal for Filling / Updating Budget Actual Accomplished -->
<div class="modal fade" id="editBudgetActualModal-{{ $budget->id }}" tabindex="-1" aria-labelledby="editBudgetActualModalLabel-{{ $budget->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('budgets.actual.update', $budget) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-yellow); box-shadow: 0 0 20px rgba(255, 230, 0, 0.2);">
                <div class="modal-header border-bottom border-secondary py-2 px-3">
                    <h6 class="modal-title font-orbitron text-white mb-0 fs-6" id="editBudgetActualModalLabel-{{ $budget->id }}">
                        <i class="bi bi-check2-circle text-neon-green me-1"></i>ACTUAL QUANTITY
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-white small mb-2 text-truncate" title="{{ $budget->description }}">
                        <strong class="text-neon-yellow">Item:</strong> {{ $budget->description }}
                    </p>
                    <div class="d-flex justify-content-between small text-muted mb-3 p-2 rounded" style="background: rgba(255, 255, 255, 0.04);">
                        <span>Allocation: <strong class="text-white">{{ $budget->quantity_with_unit }}</strong></span>
                        @if ($budget->unit)
                            <span>Unit: <strong class="text-white">{{ $budget->unit }}</strong></span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <label class="form-label font-rajdhani text-uppercase text-neon-green small fw-bold mb-1">
                            Actual Quantity Spent / Delivered
                        </label>
                        <div class="input-group">
                            <input type="text" name="actual" class="form-control" placeholder="e.g., 4500, 3, Completed" value="{{ old('actual', $budget->actual) }}" autofocus>
                            @if ($budget->unit)
                                <span class="input-group-text bg-dark text-white border-secondary">{{ $budget->unit }}</span>
                            @endif
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Enter the realized budget execution value.</small>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary py-2 px-3">
                    <button type="button" class="btn btn-sm btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-neon-yellow">Save Actual</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Editing Budget Item Details -->
<div class="modal fade" id="editBudgetModal-{{ $budget->id }}" tabindex="-1" aria-labelledby="editBudgetModalLabel-{{ $budget->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('budgets.update', $budget) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-yellow); box-shadow: 0 0 20px rgba(255, 230, 0, 0.2);">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title font-orbitron text-neon-yellow" id="editBudgetModalLabel-{{ $budget->id }}">
                        <i class="bi bi-cash-stack me-2"></i>EDIT BUDGET ITEM
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 w-100">
                        <label class="form-label text-neon-yellow small font-rajdhani text-uppercase">DESCRIPTION <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control w-100" rows="3" required style="width: 100% !important; resize: vertical;">{{ old('description', $budget->description) }}</textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label text-neon-yellow small font-rajdhani text-uppercase">QUANTITY / ALLOCATION *</label>
                            <input type="number" step="any" min="0" name="quantity" class="form-control" value="{{ old('quantity', $budget->quantity) }}" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT (OPTIONAL)</label>
                            <input type="text" name="unit" class="form-control" value="{{ old('unit', $budget->unit) }}" placeholder="e.g., USD, PHP">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-neon-green small font-rajdhani text-uppercase">ACTUAL QUANTITY (OPTIONAL)</label>
                        <input type="text" name="actual" class="form-control" value="{{ old('actual', $budget->actual) }}" placeholder="e.g., 5000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-neon-cyan small font-rajdhani text-uppercase">FOR (OBJECTIVE / TARGET) (OPTIONAL)</label>
                        <select name="for" class="form-select">
                            <option value="">None (Independent Budget Item)</option>
                            @foreach ($plan->targetObjectives as $to)
                                <option value="{{ $to->id }}" {{ old('for', $budget->target_objective_id) == $to->id ? 'selected' : '' }}>
                                    {{ Str::limit($to->description, 60) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">EVALUATION STATUS</label>
                        <select name="status" class="form-select">
                            <option value="" {{ is_null($budget->status) ? 'selected' : '' }}>Pending / Unevaluated</option>
                            <option value="Hit" {{ $budget->status === 'Hit' ? 'selected' : '' }}>Hit</option>
                            <option value="Missed" {{ $budget->status === 'Missed' ? 'selected' : '' }}>Missed</option>
                            <option value="Void" {{ $budget->status === 'Void' ? 'selected' : '' }}>Void</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-neon-yellow font-rajdhani fw-bold">UPDATE BUDGET</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- ==================== FULL-SCREEN MODAL FOR BUDGET REMARKS ==================== -->
<div class="modal fade" id="remarksModal-budget-{{ $budget->id }}" tabindex="-1" aria-labelledby="remarksModalLabel-budget-{{ $budget->id }}" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header px-4 py-3 border-bottom border-secondary d-flex align-items-center justify-content-between" style="background: #120f24;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-neon-outline btn-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-arrow-left me-1"></i> BACK TO PLAN
                    </button>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: rgba(255, 230, 0, 0.2); color: #ffe600; border: 1px solid #ffe600;">BUDGET</span>
                            <span class="{{ $budget->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ $budget->status ?: 'Unevaluated' }}</span>
                        </div>
                        <h4 class="font-orbitron fw-bold text-white mb-0 mt-1">{{ $budget->description }}</h4>
                        <small class="text-muted">Quantity / Cost: <strong class="text-white">{{ $budget->quantity_with_unit }}</strong> &bull; Actual: <strong class="text-neon-green">{{ $budget->actual_with_unit }}</strong>@if($budget->targetObjective) &bull; For: <strong class="text-neon-cyan">{{ $budget->targetObjective->description }}</strong>@endif</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="background: #0a0814;">
                <div class="container" style="max-width: 900px;">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-secondary">
                        <h5 class="font-rajdhani fw-bold text-white mb-0">
                            <i class="bi bi-chat-dots-fill text-neon-yellow me-2"></i>BUDGET REMARKS & DISCUSSION (<span class="modal-remarks-count" data-thread-key="budget-{{ $budget->id }}">{{ $budget->comments->count() }}</span>)
                        </h5>
                        <small class="text-muted">Financial logs and expenditures discussion</small>
                    </div>

                    <div class="remarks-thread-list" data-thread-key="budget-{{ $budget->id }}" data-neon-color="#ffe600" data-outline-class="btn-neon-outline-yellow" data-border-class="rgba(255, 230, 0, 0.35)">
                        @forelse ($budget->comments as $comment)
                            <div class="card mb-3 p-3" style="background: #120f24; border: 1px solid {{ $comment->user && $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user && $comment->user->hasRole('Marshall') ? '#a855f7' : 'rgba(255, 230, 0, 0.35)') }};">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($comment->user)
                                            <a href="{{ route('profile.show', $comment->user) }}" class="text-decoration-none" title="View {{ $comment->user->name }}'s Profile">
                                                @if ($comment->user->profile_picture)
                                                    <img src="{{ asset('storage/' . $comment->user->profile_picture) }}" alt="{{ $comment->user->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 32px; height: 32px; border: 1.5px solid {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? 'var(--neon-violet)' : 'var(--neon-yellow)') }};">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? '#a855f7' : '#ffe600') }}; color: {{ $comment->user->hasRole('Admin') || $comment->user->hasRole('Marshall') ? '#ffffff' : '#0a0814' }};">
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
                                                <div class="comment-attachment-card d-inline-flex flex-column rounded overflow-hidden position-relative" style="background: rgba(18, 15, 36, 0.9); border: 1px solid rgba(255, 230, 0, 0.35); width: 124px;">
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
                                                            <i class="bi bi-arrows-fullscreen text-neon-yellow fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 px-2 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03);">
                                                        <div class="text-truncate me-1" style="max-width: 82px;" title="{{ $att->original_name }}">
                                                            <span class="d-block small text-white text-truncate fw-bold" style="font-size: 0.68rem;">{{ $att->original_name }}</span>
                                                            <small class="text-muted d-block" style="font-size: 0.62rem;">{{ $att->formatted_size }}</small>
                                                        </div>
                                                        <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-link text-neon-yellow p-0" title="Download">
                                                            <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-inline-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255, 230, 0, 0.05); border: 1px solid rgba(255, 230, 0, 0.25); max-width: 240px;">
                                                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(255, 230, 0, 0.1);">
                                                        <i class="bi {{ $att->icon_class }} fs-5"></i>
                                                    </div>
                                                    <div class="text-truncate flex-grow-1" style="min-width: 0;">
                                                        <span class="d-block small text-white text-truncate fw-bold" title="{{ $att->original_name }}">{{ $att->original_name }}</span>
                                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $att->formatted_size }}</small>
                                                    </div>
                                                    <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-neon-outline-yellow p-1 px-2 ms-1 flex-shrink-0" title="Download Attachment">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5 border border-dashed border-secondary rounded empty-remarks-placeholder">
                                <i class="bi bi-chat-square-text text-muted fs-1 mb-2 d-block"></i>
                                <span class="text-muted font-rajdhani">NO REMARKS RECORDED YET</span>
                                <small class="text-muted d-block">Start the budget discussion using the console below.</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Remark Composer Form -->
            <div class="modal-footer px-3 px-md-4 py-3 border-top border-secondary w-100" style="background: #120f24;">
                <div class="w-100 d-flex flex-column align-items-center">
                    <form action="{{ route('comments.store') }}" method="POST" class="w-100 ajax-remark-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="commentable_type" value="App\Models\Budget">
                        <input type="hidden" name="commentable_id" value="{{ $budget->id }}">
                        <input type="hidden" name="thread_key" value="budget-{{ $budget->id }}">

                        <div class="mb-2 w-100 d-flex justify-content-center">
                            <textarea name="body" class="form-control custom-scrollbar remark-body-textarea remark-input w-100" rows="3" placeholder="Write a budget remark or update..." required style="background: #0a0814; color: #fff; border-color: rgba(255, 230, 0, 0.3); resize: none; width: 100% !important;"></textarea>
                        </div>

                        <!-- Staged Attachments Preview Container -->
                        <div class="remark-staged-container mb-2 d-none w-100">
                            <div class="small text-muted font-rajdhani mb-1 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-paperclip text-neon-yellow me-1"></i>STAGED ATTACHMENTS (<span class="staged-count">0</span>)</span>
                                <small style="font-size: 0.68rem;">Chunk upload on submit</small>
                            </div>
                            <div class="staged-items-list d-flex flex-wrap gap-2"></div>
                            <!-- Upload Progress Bar -->
                            <div class="staged-upload-progress mt-2 d-none">
                                <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 0.72rem;">
                                    <span class="upload-status-text">Uploading chunks...</span>
                                    <span class="upload-percent-text font-orbitron text-neon-yellow">0%</span>
                                </div>
                                <div class="progress" style="height: 5px; background: rgba(255, 255, 255, 0.1);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: #ffe600;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                            <div>
                                <input type="file" multiple class="d-none remark-file-input">
                                <button type="button" class="btn btn-sm btn-outline-secondary attach-btn">
                                    <i class="bi bi-paperclip me-1"></i><span class="attach-btn-label">Attach Files</span>
                                </button>
                                <small class="text-muted ms-2 d-none d-md-inline" style="font-size: 0.72rem;">Images, PDF, Docs up to 25MB</small>
                            </div>
                            <button type="submit" class="btn btn-neon-yellow btn-sm submit-remark-btn px-4 fw-bold font-rajdhani" style="color: #000000 !important;">
                                <i class="bi bi-send-fill me-1"></i>POST REMARK
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

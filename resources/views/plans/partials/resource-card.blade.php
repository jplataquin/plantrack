@php
    $isMarshall = $isMarshall ?? Auth::user()?->hasAnyRole(['Marshall', 'Admin']);
    $isAssignedExecutor = $isAssignedExecutor ?? (Auth::id() === $plan->executor_id);
    $canEdit = $isMarshall || (! $res->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $res->user_id === Auth::id());
    $canDelete = $isMarshall || (! $res->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $res->user_id === Auth::id());
    $canUpdateStatus = $isMarshall || (! $res->isMarshallMade() && $isAssignedExecutor && $plan->status === 'Open' && $res->user_id === Auth::id());
@endphp
<div class="col-12 col-md-6 col-lg-4 resource-item" id="resource-item-{{ $res->id }}">
    <div class="card card-hover h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(255, 107, 0, 0.35);">
        <div>
            @if ($res->targetObjective)
                <div class="mb-2">
                    <span class="badge text-truncate d-inline-block" style="background: rgba(0, 240, 255, 0.12); color: var(--neon-cyan); border: 1px solid rgba(0, 240, 255, 0.35); font-size: 0.72rem; max-width: 100%;" title="Linked Objective: {{ $res->targetObjective->description }}">
                        <i class="bi bi-bullseye me-1"></i>FOR: {{ Str::limit($res->targetObjective->description, 36) }}
                    </span>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 class="text-white fw-bold mb-0 font-rajdhani fs-6 fs-md-5" title="{{ $res->description }}">{{ Str::limit($res->description, 200) }}</h6>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    @if ($canEdit)
                        <button type="button" class="btn btn-link text-neon-orange p-0" data-bs-toggle="modal" data-bs-target="#editResourceModal-{{ $res->id }}" title="Edit Resource">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    @endif
                    @if ($canDelete)
                        <form action="{{ route('resources.destroy', $res) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove resource?');">
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
                    <span class="text-muted"><i class="bi bi-layers me-1 text-neon-orange"></i>Quantity / Allocation:</span>
                    <strong class="text-white">{{ $res->quantity_with_unit }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted"><i class="bi bi-check2-all me-1 text-neon-green"></i>Actual:</span>
                    <div class="d-flex align-items-center gap-1">
                        <strong class="{{ $res->actual !== null && $res->actual !== '' ? 'text-neon-green' : 'text-muted' }}" id="resource-actual-display-{{ $res->id }}">
                            {{ $res->actual_with_unit }}
                        </strong>
                        @if ($isMarshall || $plan->status === 'Open')
                            <button type="button" class="btn btn-link text-neon-orange p-0 ms-1" data-bs-toggle="modal" data-bs-target="#editResourceActualModal-{{ $res->id }}" title="Fill / Update Actual Accomplished">
                                <i class="bi bi-pencil-square" style="font-size: 0.75rem;"></i>
                            </button>
                        @endif
                    </div>
                </div>
                @if ($res->targetObjective)
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="bi bi-bullseye me-1 text-neon-cyan"></i>For Target:</span>
                        <strong class="text-neon-cyan small text-truncate ms-2" style="max-width: 170px;" title="{{ $res->targetObjective->description }}">
                            {{ $res->targetObjective->description }}
                        </strong>
                    </div>
                @endif
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="text-muted"><i class="bi bi-calendar-event me-1 text-neon-orange"></i>Target Date:</span>
                    <strong class="text-white">{{ $res->target_date->format('M d, Y') }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted"><i class="bi bi-calendar-check me-1 text-neon-green"></i>Date Available:</span>
                    <strong class="{{ $res->date_available ? 'text-neon-green' : 'text-muted' }}" id="resource-date-available-display-{{ $res->id }}">
                        {{ $res->date_available ? $res->date_available->format('M d, Y') : '—' }}
                    </strong>
                </div>
            </div>
        </div>

        <!-- Status & Remarks Section -->
        <div class="mt-2 pt-2 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                @if ($canUpdateStatus)
                    <div class="d-flex align-items-center gap-1">
                        <select class="form-select form-select-sm ajax-status-select" data-url="{{ route('resources.status.update', $res) }}" style="width: 125px;">
                            <option value="" {{ is_null($res->status) ? 'selected' : '' }}>PENDING</option>
                            <option value="Hit" {{ $res->status === 'Hit' ? 'selected' : '' }}>HIT</option>
                            <option value="Missed" {{ $res->status === 'Missed' ? 'selected' : '' }}>MISSED</option>
                            <option value="Void" {{ $res->status === 'Void' ? 'selected' : '' }}>VOID</option>
                        </select>
                        <div class="spinner-border spinner-border-sm text-neon-orange status-spinner d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @else
                    <span class="text-muted small font-rajdhani text-uppercase">Evaluation:</span>
                @endif

                <span class="component-badge {{ $res->status_badge_class }} px-2 py-1 rounded">
                    {{ $res->status ?: 'Unevaluated' }}
                </span>
            </div>

            <!-- Remarks Trigger (Opens Full-Screen Modal) -->
            <button class="btn btn-neon-outline-orange btn-sm w-100 fw-bold d-flex align-items-center justify-content-between" type="button" data-bs-toggle="modal" data-bs-target="#remarksModal-resource-{{ $res->id }}">
                <span><i class="bi bi-chat-quote-fill me-1"></i>REMARKS</span>
                <span class="badge remarks-badge-count" data-thread-key="resource-{{ $res->id }}" style="background: rgba(255, 107, 0, 0.2); color: #ff6b00;">{{ $res->comments->count() }}</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal for Filling / Updating Resource Actual Accomplished -->
<div class="modal fade" id="editResourceActualModal-{{ $res->id }}" tabindex="-1" aria-labelledby="editResourceActualModalLabel-{{ $res->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered component-modal-dialog">
        <form action="{{ route('resources.actual.update', $res) }}" method="POST" class="ajax-actual-form" data-component-type="resource" data-component-id="{{ $res->id }}">
            @csrf
            @method('PATCH')
            <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-orange); box-shadow: 0 0 20px rgba(255, 107, 0, 0.2);">
                <div class="modal-header border-bottom border-secondary py-2 px-3">
                    <h6 class="modal-title font-orbitron text-white mb-0 fs-6" id="editResourceActualModalLabel-{{ $res->id }}">
                        <i class="bi bi-check2-circle text-neon-green me-1"></i>ACTUAL ACCOMPLISHED
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3 p-2 rounded" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.06);">
                        <small class="text-muted d-block font-rajdhani">RESOURCE ALLOCATION:</small>
                        <strong class="text-white">{{ $res->quantity_with_unit }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">ACTUAL DELIVERED / USED</label>
                        <input type="text" name="actual" class="form-control" value="{{ $res->actual }}" placeholder="e.g. 3, 22000" autocomplete="off">
                        <small class="text-muted" style="font-size: 0.7rem;">Enter actual delivered output or expenditure.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small font-rajdhani text-uppercase">DATE AVAILABLE</label>
                        <input type="date" name="date_available" class="form-control" value="{{ old('date_available', $res->date_available ? $res->date_available->format('Y-m-d') : '') }}">
                        <small class="text-muted" style="font-size: 0.7rem;">Date the resource became available or delivered.</small>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary py-2 px-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-neon-orange px-3 font-rajdhani fw-bold">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        <span class="btn-text">SAVE ACTUAL</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($canEdit)
    <!-- Modal for Editing Resource Details -->
    <div class="modal fade" id="editResourceModal-{{ $res->id }}" tabindex="-1" aria-labelledby="editResourceModalLabel-{{ $res->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered component-modal-dialog">
            <form action="{{ route('resources.update', $res) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-orange); box-shadow: 0 0 20px rgba(255, 107, 0, 0.2);">
                    <div class="modal-header border-bottom border-secondary">
                        <h5 class="modal-title font-orbitron text-neon-orange" id="editResourceModalLabel-{{ $res->id }}">
                            <i class="bi bi-pencil-square me-2"></i>EDIT RESOURCE
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">RESOURCE DESCRIPTION *</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe resource needed..." required>{{ old('description', $res->description) }}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label text-muted small font-rajdhani text-uppercase">QUANTITY / ALLOCATION *</label>
                                <input type="text" name="quantity" class="form-control" value="{{ old('quantity', $res->quantity) }}" placeholder="e.g. 4, 25000, 3" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-muted small font-rajdhani text-uppercase">UNIT OF MEASURE</label>
                                <input type="text" name="unit" class="form-control" value="{{ old('unit', $res->unit) }}" placeholder="e.g. Engineers, $, Servers">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">FOR (OPTIONAL)</label>
                            <select name="for" class="form-select">
                                <option value="">NONE (STANDALONE RESOURCE)</option>
                                @foreach ($plan->targetObjectives as $to)
                                    <option value="{{ $to->id }}" {{ old('for', $res->target_objective_id) == $to->id ? 'selected' : '' }}>
                                        {{ $to->description }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.7rem;">Optionally link this resource to a specific target or objective.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">TARGET DATE *</label>
                            <input type="date" name="target_date" class="form-control" value="{{ old('target_date', $res->target_date ? $res->target_date->format('Y-m-d') : '') }}" min="{{ $plan->start_date->format('Y-m-d') }}" max="{{ $plan->end_date->format('Y-m-d') }}" required>
                            <small class="text-muted" style="font-size: 0.7rem;">Plan schedule: {{ $plan->start_date->format('M d, Y') }} &ndash; {{ $plan->end_date->format('M d, Y') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small font-rajdhani text-uppercase">DATE AVAILABLE (OPTIONAL)</label>
                            <input type="date" name="date_available" class="form-control" value="{{ old('date_available', $res->date_available ? $res->date_available->format('Y-m-d') : '') }}">
                            <small class="text-muted" style="font-size: 0.7rem;">Date the resource was actually made available or delivered.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-neon-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-neon-orange font-rajdhani fw-bold">UPDATE RESOURCE</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- ==================== FULL-SCREEN MODAL FOR RESOURCE REMARKS ==================== -->
<div class="modal fade" id="remarksModal-resource-{{ $res->id }}" tabindex="-1" aria-labelledby="remarksModalLabel-resource-{{ $res->id }}" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100">
            <div class="modal-header px-4 py-3 border-bottom border-secondary d-flex align-items-center justify-content-between" style="background: #120f24;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-neon-outline btn-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-arrow-left me-1"></i> BACK TO PLAN
                    </button>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: rgba(255, 107, 0, 0.2); color: #ff6b00; border: 1px solid #ff6b00;">RESOURCE</span>
                            <span class="{{ $res->status_badge_class }} px-2 py-0.5 rounded-pill small">{{ $res->status ?: 'Unevaluated' }}</span>
                        </div>
                        <h4 class="font-orbitron fw-bold text-white mb-0 mt-1">{{ $res->description }}</h4>
                        <small class="text-muted">Quantity / Cost: <strong class="text-white">{{ $res->quantity_with_unit }}</strong> &bull; Actual: <strong class="text-neon-green">{{ $res->actual_with_unit }}</strong>@if($res->targetObjective) &bull; For: <strong class="text-neon-cyan">{{ $res->targetObjective->description }}</strong>@endif &bull; Target Date: <strong class="text-white">{{ $res->target_date->format('M d, Y') }}</strong></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white fs-5" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="background: #0a0814;">
                <div class="container" style="max-width: 900px;">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom border-secondary">
                        <h5 class="font-rajdhani fw-bold text-white mb-0">
                            <i class="bi bi-chat-dots-fill text-neon-orange me-2"></i>RESOURCE ALLOCATION REMARKS & DISCUSSION (<span class="modal-remarks-count" data-thread-key="resource-{{ $res->id }}">{{ $res->comments->count() }}</span>)
                        </h5>
                        <small class="text-muted">Procurement remarks and deployment logs</small>
                    </div>

                    <div class="remarks-thread-list" data-thread-key="resource-{{ $res->id }}" data-neon-color="#ff6b00" data-outline-class="btn-neon-outline-orange" data-border-class="rgba(255, 107, 0, 0.35)">
                        @forelse ($res->comments as $comment)
                            <div class="card mb-3 p-3" style="background: #120f24; border: 1px solid {{ $comment->user && $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user && $comment->user->hasRole('Marshall') ? '#a855f7' : 'rgba(255, 107, 0, 0.35)') }};">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($comment->user)
                                            <a href="{{ route('profile.show', $comment->user) }}" class="text-decoration-none" title="View {{ $comment->user->name }}'s Profile">
                                                @if ($comment->user->profile_picture)
                                                    <img src="{{ asset('storage/' . $comment->user->profile_picture) }}" alt="{{ $comment->user->name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 32px; height: 32px; border: 1.5px solid {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? 'var(--neon-violet)' : 'var(--neon-orange)') }};">
                                                @else
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; background: {{ $comment->user->hasRole('Admin') ? '#ff0055' : ($comment->user->hasRole('Marshall') ? '#a855f7' : '#ff6b00') }}; color: {{ $comment->user->hasRole('Admin') || $comment->user->hasRole('Marshall') ? '#fff' : '#0a0814' }};">
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
                                                <div class="comment-attachment-card d-inline-flex flex-column rounded overflow-hidden position-relative" style="background: rgba(18, 15, 36, 0.9); border: 1px solid rgba(255, 107, 0, 0.35); width: 124px;">
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
                                                            <i class="bi bi-arrows-fullscreen text-neon-orange fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 px-2 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03);">
                                                        <div class="text-truncate me-1" style="max-width: 82px;" title="{{ $att->original_name }}">
                                                            <span class="d-block small text-white text-truncate fw-bold" style="font-size: 0.68rem;">{{ $att->original_name }}</span>
                                                            <small class="text-muted d-block" style="font-size: 0.62rem;">{{ $att->formatted_size }}</small>
                                                        </div>
                                                        <a href="{{ route('comments.attachments.download', $att) }}" class="btn btn-sm btn-link text-neon-orange p-0" title="Download">
                                                            <i class="bi bi-download" style="font-size: 0.75rem;"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="d-inline-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255, 107, 0, 0.05); border: 1px solid rgba(255, 107, 0, 0.25); max-width: 240px;">
                                                    <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background: rgba(255, 107, 0, 0.1);">
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
                                <i class="bi bi-chat-square-text fs-1 text-neon-orange d-block mb-2"></i>
                                <h5 class="text-white font-orbitron">NO REMARKS RECORDED</h5>
                                <p class="small">Enter allocation updates or procurement logs below.</p>
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
                            <input type="hidden" name="commentable_type" value="resource">
                            <input type="hidden" name="commentable_id" value="{{ $res->id }}">
                            <div class="staged-attachment-ids-container w-100"></div>

                            <!-- Staged Attachments Container (Up to 5 files) -->
                            <div class="remark-staged-container d-none mb-2 p-2 rounded w-100" style="background: rgba(255, 107, 0, 0.05); border: 1px dashed rgba(255, 107, 0, 0.4);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-neon-orange font-rajdhani fw-bold text-uppercase">
                                        <i class="bi bi-paperclip me-1"></i>STAGED ATTACHMENTS (<span class="staged-count">0</span>/5)
                                    </small>
                                    <small class="text-muted" style="font-size: 0.7rem;">Max 5 files &bull; Max 5MB each</small>
                                </div>
                                <div class="staged-items-list d-flex flex-column gap-1"></div>
                            </div>

                            <!-- Chunk Upload Progress Bar -->
                            <div class="remark-upload-progress d-none mb-2 w-100">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-neon-orange progress-status-text"><i class="bi bi-arrow-repeat spin me-1"></i>Uploading chunk to staging...</span>
                                    <span class="text-white fw-bold progress-percent-text">0%</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%;"></div>
                                </div>
                            </div>

                            <div class="mb-2 w-100 d-flex justify-content-center">
                                <textarea name="body" class="form-control custom-scrollbar remark-body-textarea w-100" rows="3" placeholder="Type resource allocation remark or update..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                                <label class="btn btn-neon-outline d-flex align-items-center gap-1 mb-0 px-3 cursor-pointer remark-attach-btn" title="Attach up to 5 files (Max 5MB each)">
                                    <i class="bi bi-paperclip"></i>
                                    <span class="attach-btn-label">Attach files</span>
                                    <input type="file" class="d-none remark-file-input" multiple>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted d-none d-sm-inline font-monospace" style="font-size: 0.75rem;">Ctrl+Enter to post</small>
                                    <button type="submit" class="btn btn-neon-orange px-4 font-rajdhani fw-bold">
                                        <i class="bi bi-send-fill me-1"></i> POST REMARK
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-dark border-secondary text-muted small w-100 mb-0 d-flex align-items-center justify-content-center text-center gap-2" style="background: rgba(255, 255, 255, 0.03);">
                            <i class="bi bi-lock-fill text-neon-orange fs-5"></i>
                            <span>Resource remarks and allocation input are locked because this Plan Record status is <strong>{{ strtoupper($plan->status) }}</strong>. Only plans in <strong>OPEN</strong> status accept operative contributions.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

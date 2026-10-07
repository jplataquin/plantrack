<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Plan Track') }} - Retro Neon</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=orbitron:600,800,900|rajdhani:600,700|nunito:400,600,700" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="retro-neon">
    <div id="app" class="d-flex flex-column min-vh-100">
        <!-- Solid Neon Header Bar -->
        <div style="height: 2px; background: #00f0ff; box-shadow: 0 0 10px #00f0ff;"></div>

        <nav class="navbar navbar-expand-lg sticky-top">
            <div class="container-fluid px-3 px-md-4">
                <a class="navbar-brand d-flex align-items-center gap-2 py-2" href="{{ url('/') }}">
                    <i class="bi bi-grid-3x3-gap-fill text-neon-orange fs-4"></i>
                    <span>PLAN<span class="text-neon-cyan">TRACK</span></span>
                </a>
                
                <div class="d-flex align-items-center gap-2 order-lg-3">
                    @auth
                        <div class="d-none d-sm-block">
                            @if (Auth::user()->hasRole('Admin'))
                                <span class="badge badge-role-admin px-2 py-1 rounded-pill">
                                    <i class="bi bi-shield-lock-fill me-1"></i> ADMIN
                                </span>
                            @elseif (Auth::user()->hasRole('Marshall'))
                                <span class="badge badge-role-marshall px-2 py-1 rounded-pill">
                                    <i class="bi bi-shield-check me-1"></i> MARSHALL
                                </span>
                            @elseif (Auth::user()->hasRole('Executor'))
                                <span class="badge badge-role-executor px-2 py-1 rounded-pill">
                                    <i class="bi bi-lightning-charge me-1"></i> EXECUTOR
                                </span>
                            @endif
                        </div>
                    @endauth

                    <button class="navbar-toggler border-0 p-2 text-neon-cyan" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-2"></i>
                    </button>
                </div>

                <div class="collapse navbar-collapse order-lg-2" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto my-2 my-lg-0 gap-1">
                        @auth
                            @if (! Auth::user()->must_reset_password)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('executors.*') ? 'active' : '' }}" href="{{ route('executors.index') }}">
                                        <i class="bi bi-people-fill me-1 text-neon-cyan"></i> Executors
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('plans.index') ? 'active' : '' }}" href="{{ route('plans.index') }}">
                                        <i class="bi bi-kanban-fill me-1 text-neon-orange"></i> Plans
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                                        <i class="bi bi-folder-fill me-1 text-neon-violet"></i> Projects
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('leaderboard.*') ? 'active' : '' }}" href="{{ route('leaderboard.index') }}">
                                        <i class="bi bi-trophy-fill me-1 text-neon-yellow"></i> Leaderboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('plans.create') ? 'active' : '' }}" href="{{ route('plans.create') }}">
                                        <i class="bi bi-plus-circle me-1 text-neon-orange"></i> Create Plan
                                    </a>
                                </li>
                                @if (Auth::user()->hasRole('Admin'))
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                                            <i class="bi bi-shield-lock-fill me-1 text-neon-pink"></i> Admin
                                        </a>
                                    </li>
                                @endif
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}"><i class="bi bi-person-plus me-1"></i> Register</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    @if (Auth::user()->profile_picture)
                                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="{{ Auth::user()->name }}" class="rounded-circle object-fit-cover current-user-avatar-img" style="width: 32px; height: 32px; border: 1.5px solid {{ Auth::user()->hasRole('Admin') ? '#ff0055' : (Auth::user()->hasRole('Marshall') ? 'var(--neon-violet)' : 'var(--neon-cyan)') }};">
                                    @else
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold current-user-avatar-fallback" style="width: 32px; height: 32px; background: {{ Auth::user()->hasRole('Admin') ? '#ff0055' : (Auth::user()->hasRole('Marshall') ? '#a855f7' : '#00f0ff') }}; color: {{ Auth::user()->hasRole('Executor') ? '#0a0814' : '#ffffff' }};">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span>{{ Auth::user()->name }}</span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 mt-2" aria-labelledby="navbarDropdown" style="background: #17132e; border: 1px solid rgba(0, 240, 255, 0.3) !important;">
                                    <div class="px-3 py-1">
                                        <div class="small text-muted">User Account</div>
                                        <div class="fw-bold text-light">{{ Auth::user()->email }}</div>
                                        <div class="mt-1">
                                            @if (Auth::user()->hasRole('Admin'))
                                                <span class="badge badge-role-admin rounded-pill">Admin</span>
                                            @elseif (Auth::user()->hasRole('Marshall'))
                                                <span class="badge badge-role-marshall rounded-pill">Marshall</span>
                                            @elseif (Auth::user()->hasRole('Executor'))
                                                <span class="badge badge-role-executor rounded-pill">Executor</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="dropdown-divider border-secondary"></div>
                                    @if (Auth::user()->hasRole('Admin'))
                                        <a class="dropdown-item py-2 text-light {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                                            <i class="bi bi-shield-lock-fill text-neon-pink me-2"></i> Admin Console
                                        </a>
                                    @endif
                                    <a class="dropdown-item py-2 text-light {{ request()->routeIs('leaderboard.*') ? 'active' : '' }}" href="{{ route('leaderboard.index') }}">
                                        <i class="bi bi-trophy-fill text-neon-yellow me-2"></i> Operative Leaderboard
                                    </a>
                                    <a class="dropdown-item py-2 text-light {{ request()->routeIs('profile.show') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                                        <i class="bi bi-person-badge text-neon-cyan me-2"></i> Operative Profile
                                    </a>
                                    <button type="button" class="dropdown-item py-2 text-light open-avatar-modal-btn" data-bs-toggle="modal" data-bs-target="#profilePictureModal" data-user-id="{{ Auth::id() }}" data-user-name="{{ Auth::user()->name }}">
                                        <i class="bi bi-camera text-neon-cyan me-2"></i> Update Profile Picture
                                    </button>
                                    <div class="dropdown-divider border-secondary"></div>
                                    <a class="dropdown-item text-danger d-flex align-items-center gap-2 py-2" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-power"></i> Log Out
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-3 py-md-4 flex-grow-1">
            <div class="container-fluid px-3 px-md-4">
                @if (session('created_marshall_password'))
                    <div class="card mb-4" style="background: rgba(168, 85, 247, 0.08); border: 1px solid var(--neon-violet) !important; box-shadow: 0 0 20px rgba(168, 85, 247, 0.25);">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-shield-shaded text-neon-violet fs-4"></i>
                                        <h5 class="font-orbitron fw-bold text-white mb-0">MARSHALL PROVISIONED</h5>
                                        <span class="badge badge-role-marshall px-2 py-0.5 rounded-pill small">Temporary Key Set</span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        Deliver these credentials to the user. They will be required to change their temporary password upon their first login.
                                    </p>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div>
                                            <span class="text-muted small font-rajdhani text-uppercase">Email:</span>
                                            <strong class="text-white font-monospace ms-1">{{ session('created_marshall_email') }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted small font-rajdhani text-uppercase">Temporary Key:</span>
                                            <code class="px-2 py-1 rounded ms-1 text-neon-yellow" style="background: #17132e; border: 1px solid rgba(255, 230, 0, 0.4); font-size: 1rem;">{{ session('created_marshall_password') }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-neon-violet btn-sm d-inline-flex align-items-center gap-2" type="button" onclick="navigator.clipboard.writeText('Email: {{ session('created_marshall_email') }}\nTemporary Password: {{ session('created_marshall_password') }}'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> COPIED!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> COPY CREDENTIALS', 2000);">
                                        <i class="bi bi-clipboard"></i> COPY CREDENTIALS
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('created_executor_password'))
                    <div class="card mb-4" style="background: rgba(0, 240, 255, 0.08); border: 1px solid var(--neon-cyan) !important; box-shadow: 0 0 20px rgba(0, 240, 255, 0.25);">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-shield-check text-neon-cyan fs-4"></i>
                                        <h5 class="font-orbitron fw-bold text-white mb-0">EXECUTOR PROVISIONED</h5>
                                        <span class="badge badge-role-executor px-2 py-0.5 rounded-pill small">Temporary Key Set</span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        Deliver these credentials to the user. They will be required to change their temporary password upon their first login.
                                    </p>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div>
                                            <span class="text-muted small font-rajdhani text-uppercase">Email:</span>
                                            <strong class="text-white font-monospace ms-1">{{ session('created_executor_email') }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-muted small font-rajdhani text-uppercase">Temporary Key:</span>
                                            <code class="px-2 py-1 rounded ms-1 text-neon-yellow" style="background: #17132e; border: 1px solid rgba(255, 230, 0, 0.4); font-size: 1rem;">{{ session('created_executor_password') }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-neon-cyan btn-sm d-inline-flex align-items-center gap-2" type="button" onclick="navigator.clipboard.writeText('Email: {{ session('created_executor_email') }}\nTemporary Password: {{ session('created_executor_password') }}'); this.innerHTML='<i class=\'bi bi-check-lg\'></i> COPIED!'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i> COPY CREDENTIALS', 2000);">
                                        <i class="bi bi-clipboard"></i> COPY CREDENTIALS
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="background: rgba(0, 255, 136, 0.12); border: 1px solid rgba(0, 255, 136, 0.5) !important; color: #ffffff; box-shadow: 0 0 15px rgba(0, 255, 136, 0.2); border-radius: 10px;">
                        <i class="bi bi-check2-circle me-2 text-neon-green fs-4"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="background: rgba(255, 230, 0, 0.12); border: 1px solid rgba(255, 230, 0, 0.5) !important; color: #ffffff; box-shadow: 0 0 15px rgba(255, 230, 0, 0.2); border-radius: 10px;">
                        <i class="bi bi-exclamation-circle me-2 text-neon-yellow fs-4"></i>
                        <div>{{ session('warning') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.5) !important; color: #ffffff; box-shadow: 0 0 15px rgba(168, 85, 247, 0.25); border-radius: 10px;">
                        <i class="bi bi-exclamation-triangle me-2 text-neon-violet fs-4"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-dismissible fade show mb-4" role="alert" style="background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.5) !important; color: #ffffff; border-radius: 10px;">
                        <div class="fw-bold mb-1 text-neon-violet"><i class="bi bi-exclamation-octagon-fill me-1"></i> Please check errors:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Bottom Mobile Quick Bar (for authenticated mobile users) -->
        @auth
            @if (! Auth::user()->must_reset_password)
                <div class="d-md-none sticky-bottom py-2 px-3 border-top" style="background: rgba(18, 15, 36, 0.95); backdrop-filter: blur(12px); border-color: rgba(0, 240, 255, 0.25) !important;">
                    <div class="d-flex justify-content-around align-items-center">
                        <a href="{{ route('executors.index') }}" class="text-center text-decoration-none {{ request()->routeIs('executors.index') || request()->routeIs('plans.index') ? 'text-neon-cyan' : 'text-muted' }}">
                            <i class="bi bi-people-fill fs-4 d-block"></i>
                            <span class="small font-rajdhani fw-bold">EXECUTORS</span>
                        </a>
                        <a href="{{ route('plans.create') }}" class="btn-neon-orange rounded-circle d-flex align-items-center justify-content-center text-decoration-none shadow" style="width: 42px; height: 42px; margin-top: -16px;" title="Create Plan">
                            <i class="bi bi-plus-lg fs-5"></i>
                        </a>
                        <a href="{{ route('plans.create') }}" class="text-center text-decoration-none text-muted">
                            <i class="bi bi-plus-circle fs-4 d-block"></i>
                            <span class="small font-rajdhani fw-bold">NEW PLAN</span>
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    </div>

    @auth
        @include('components.attachment-slideshow-modal')
        @include('components.profile-picture-modal')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // ==================== PROFILE PICTURE MODAL CONTROLLER ====================
                (function () {
                    const modalEl = document.getElementById('profilePictureModal');
                    if (!modalEl) return;

                const modalTargetLabel = document.getElementById('avatarModalTargetLabel');
                const targetUserIdInput = document.getElementById('avatarTargetUserId');
                const dropzone = document.getElementById('avatarDropzone');
                const fileInput = document.getElementById('avatarFileInput');
                const cropWorkspace = document.getElementById('avatarCropWorkspace');
                const canvas = document.getElementById('avatarCropCanvas');
                const ctx = canvas.getContext('2d');
                const zoomRange = document.getElementById('avatarZoomRange');
                const zoomInBtn = document.getElementById('avatarZoomInBtn');
                const zoomOutBtn = document.getElementById('avatarZoomOutBtn');
                const resetCropBtn = document.getElementById('avatarResetCropBtn');
                const saveBtn = document.getElementById('avatarSaveBtn');
                const deleteBtn = document.getElementById('avatarDeleteBtn');
                const alertBox = document.getElementById('avatarModalAlert');
                const progressBox = document.getElementById('avatarUploadProgress');
                const progressBar = document.getElementById('avatarProgressBar');
                const progressStatus = document.getElementById('avatarProgressStatus');
                const progressPercent = document.getElementById('avatarProgressPercent');

                let currentImg = null;
                let baseScale = 1;
                let zoomFactor = 1;
                let panX = 0;
                let panY = 0;
                let isDragging = false;
                let startX = 0;
                let startY = 0;
                const CHUNK_SIZE = 1024 * 512; // 512 KB
                const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

                function showAlert(msg) {
                    alertBox.textContent = msg;
                    alertBox.classList.remove('d-none');
                }

                function hideAlert() {
                    alertBox.textContent = '';
                    alertBox.classList.add('d-none');
                }

                // Open Modal Handler
                document.querySelectorAll('.open-avatar-modal-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const userId = this.dataset.userId || "{{ Auth::id() }}";
                        const userName = this.dataset.userName || "{{ Auth::user()->name }}";
                        targetUserIdInput.value = userId;
                        if (modalTargetLabel) {
                            modalTargetLabel.textContent = `Operative: ${userName}`;
                        }
                    });
                });

                // Reset Modal when closed
                modalEl.addEventListener('hidden.bs.modal', function () {
                    fileInput.value = '';
                    currentImg = null;
                    hideAlert();
                    progressBox.classList.add('d-none');
                    cropWorkspace.classList.add('d-none');
                    dropzone.classList.remove('d-none');
                    saveBtn.disabled = true;
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                });

                // File selection
                fileInput.addEventListener('change', function () {
                    hideAlert();
                    const file = this.files[0];
                    if (!file) return;

                    if (!file.type.startsWith('image/')) {
                        showAlert('Please select a valid image file (JPG, PNG, or WEBP).');
                        fileInput.value = '';
                        return;
                    }

                    if (file.size > MAX_FILE_SIZE) {
                        showAlert(`File exceeds 5MB size limit (${(file.size / (1024 * 1024)).toFixed(1)}MB).`);
                        fileInput.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = new Image();
                        img.onload = function () {
                            currentImg = img;
                            initCrop();
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                });

                function initCrop() {
                    if (!currentImg) return;
                    dropzone.classList.add('d-none');
                    cropWorkspace.classList.remove('d-none');
                    saveBtn.disabled = false;

                    // Base scale so image covers the 300x300 canvas
                    baseScale = Math.max(300 / currentImg.width, 300 / currentImg.height);
                    zoomFactor = 1;
                    zoomRange.value = 1;

                    // Center image
                    panX = (300 - (currentImg.width * baseScale)) / 2;
                    panY = (300 - (currentImg.height * baseScale)) / 2;

                    drawCrop();
                }

                function drawCrop() {
                    if (!currentImg) return;
                    const totalScale = baseScale * zoomFactor;
                    const drawW = currentImg.width * totalScale;
                    const drawH = currentImg.height * totalScale;

                    // Clamp pan so crop circle is fully covered
                    const minX = 300 - drawW;
                    const maxX = 0;
                    panX = Math.min(maxX, Math.max(minX, panX));

                    const minY = 300 - drawH;
                    const maxY = 0;
                    panY = Math.min(maxY, Math.max(minY, panY));

                    ctx.clearRect(0, 0, 300, 300);
                    ctx.drawImage(currentImg, panX, panY, drawW, drawH);
                }

                // Zoom range slider
                zoomRange.addEventListener('input', function () {
                    const oldFactor = zoomFactor;
                    zoomFactor = parseFloat(this.value);

                    // Zoom relative to center
                    const ratio = zoomFactor / oldFactor;
                    panX = 150 - (150 - panX) * ratio;
                    panY = 150 - (150 - panY) * ratio;

                    drawCrop();
                });

                zoomInBtn.addEventListener('click', function () {
                    zoomRange.value = Math.min(4, parseFloat(zoomRange.value) + 0.2);
                    zoomRange.dispatchEvent(new Event('input'));
                });

                zoomOutBtn.addEventListener('click', function () {
                    zoomRange.value = Math.max(1, parseFloat(zoomRange.value) - 0.2);
                    zoomRange.dispatchEvent(new Event('input'));
                });

                resetCropBtn.addEventListener('click', function () {
                    initCrop();
                });

                // Wheel zoom
                canvas.addEventListener('wheel', function (e) {
                    e.preventDefault();
                    const delta = e.deltaY < 0 ? 0.1 : -0.1;
                    zoomRange.value = Math.min(4, Math.max(1, parseFloat(zoomRange.value) + delta));
                    zoomRange.dispatchEvent(new Event('input'));
                });

                // Drag / Pan interaction
                const viewport = document.getElementById('avatarCropViewport');

                function onPointerDown(clientX, clientY) {
                    isDragging = true;
                    startX = clientX - panX;
                    startY = clientY - panY;
                    viewport.style.cursor = 'grabbing';
                }

                function onPointerMove(clientX, clientY) {
                    if (!isDragging || !currentImg) return;
                    panX = clientX - startX;
                    panY = clientY - startY;
                    drawCrop();
                }

                function onPointerUp() {
                    isDragging = false;
                    viewport.style.cursor = 'grab';
                }

                viewport.addEventListener('mousedown', e => onPointerDown(e.clientX, e.clientY));
                window.addEventListener('mousemove', e => onPointerMove(e.clientX, e.clientY));
                window.addEventListener('mouseup', onPointerUp);

                viewport.addEventListener('touchstart', e => {
                    if (e.touches.length === 1) {
                        onPointerDown(e.touches[0].clientX, e.touches[0].clientY);
                    }
                }, { passive: true });
                window.addEventListener('touchmove', e => {
                    if (isDragging && e.touches.length === 1) {
                        onPointerMove(e.touches[0].clientX, e.touches[0].clientY);
                    }
                }, { passive: true });
                window.addEventListener('touchend', onPointerUp);

                // Delete Avatar Action
                deleteBtn.addEventListener('click', async function () {
                    if (!confirm('Remove this profile picture?')) return;
                    const targetUserId = targetUserIdInput.value;

                    try {
                        deleteBtn.disabled = true;
                        const response = await fetch("{{ route('profile.avatar.destroy') }}", {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ user_id: targetUserId })
                        });
                        const data = await response.json();
                        if (data.success) {
                            location.reload();
                        } else {
                            showAlert(data.message || 'Failed to remove profile picture.');
                        }
                    } catch (err) {
                        showAlert(err.message);
                    } finally {
                        deleteBtn.disabled = false;
                    }
                });

                // Save / Crop & Upload Action
                saveBtn.addEventListener('click', function () {
                    if (!currentImg) return;
                    hideAlert();

                    // High-resolution export canvas (400x400)
                    const exportCanvas = document.createElement('canvas');
                    exportCanvas.width = 400;
                    exportCanvas.height = 400;
                    const expCtx = exportCanvas.getContext('2d');

                    const ratio = 400 / 300;
                    const totalScale = baseScale * zoomFactor * ratio;
                    const expDrawW = currentImg.width * totalScale;
                    const expDrawH = currentImg.height * totalScale;
                    const expPanX = panX * ratio;
                    const expPanY = panY * ratio;

                    expCtx.drawImage(currentImg, expPanX, expPanY, expDrawW, expDrawH);

                    exportCanvas.toBlob(async function (blob) {
                        if (!blob) {
                            showAlert('Failed to process image canvas.');
                            return;
                        }

                        if (blob.size > MAX_FILE_SIZE) {
                            showAlert('Cropped image exceeds 5MB size limit.');
                            return;
                        }

                        // Staging & Chunk Upload Strategy
                        const totalChunks = Math.ceil(blob.size / CHUNK_SIZE);
                        const uploadId = 'avatar_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
                        const targetUserId = targetUserIdInput.value;

                        progressBox.classList.remove('d-none');
                        saveBtn.disabled = true;
                        progressBar.style.width = '0%';
                        progressPercent.textContent = '0%';
                        progressStatus.textContent = `Uploading chunk 1 of ${totalChunks} to staging...`;

                        try {
                            for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
                                const start = chunkIdx * CHUNK_SIZE;
                                const end = Math.min(start + CHUNK_SIZE, blob.size);
                                const chunkBlob = blob.slice(start, end);

                                const formData = new FormData();
                                formData.append('upload_id', uploadId);
                                formData.append('chunk_index', chunkIdx);
                                formData.append('total_chunks', totalChunks);
                                formData.append('file', chunkBlob, 'avatar.jpg');

                                const chunkRes = await fetch("{{ route('comments.upload.chunk') }}", {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                        'Accept': 'application/json',
                                    },
                                    body: formData
                                });

                                if (!chunkRes.ok) {
                                    const err = await chunkRes.json().catch(() => ({}));
                                    throw new Error(err.message || `Chunk ${chunkIdx + 1} upload failed.`);
                                }

                                const pct = Math.round(((chunkIdx + 1) / totalChunks) * 85);
                                progressBar.style.width = pct + '%';
                                progressPercent.textContent = pct + '%';
                                progressStatus.textContent = `Staging chunk ${chunkIdx + 1} of ${totalChunks}...`;
                            }

                            // Assemble & Update Avatar
                            progressStatus.textContent = 'Assembling in staging and updating profile picture...';
                            progressBar.style.width = '95%';
                            progressPercent.textContent = '95%';

                            const assembleRes = await fetch("{{ route('profile.avatar.update') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    upload_id: uploadId,
                                    filename: 'avatar.jpg',
                                    total_chunks: totalChunks,
                                    user_id: targetUserId
                                })
                            });

                            const assembleData = await assembleRes.json();
                            if (!assembleRes.ok || !assembleData.success) {
                                throw new Error(assembleData.message || 'Failed to update profile picture.');
                            }

                            progressBar.style.width = '100%';
                            progressPercent.textContent = '100%';
                            progressStatus.textContent = 'Profile picture updated successfully!';

                            // Dynamically update UI images
                            const newUrl = assembleData.profile_picture_url;
                            const uid = assembleData.user_id;

                            // 1. Current user navbar avatar
                            if (uid == "{{ Auth::id() }}") {
                                const navImg = document.querySelector('.current-user-avatar-img');
                                const navFallback = document.querySelector('.current-user-avatar-fallback');
                                if (navImg) {
                                    navImg.src = newUrl + '?t=' + Date.now();
                                } else if (navFallback) {
                                    navFallback.outerHTML = `<img src="${newUrl}?t=${Date.now()}" alt="Profile" class="rounded-circle object-fit-cover current-user-avatar-img" style="width: 32px; height: 32px; border: 1.5px solid var(--neon-cyan);">`;
                                }
                            }

                            // 2. Executor profile sidebar avatar
                            const sidebarImg = document.querySelector('.executor-profile-avatar');
                            const sidebarFallback = document.querySelector('.executor-profile-avatar-fallback');
                            if (sidebarImg) {
                                sidebarImg.src = newUrl + '?t=' + Date.now();
                            } else if (sidebarFallback) {
                                sidebarFallback.outerHTML = `<img src="${newUrl}?t=${Date.now()}" alt="Profile" class="rounded-circle object-fit-cover shadow-sm executor-profile-avatar" style="width: 52px; height: 52px; border: 2px solid var(--neon-cyan);">`;
                            }

                            // 3. Executor directory card
                            const cardImg = document.querySelector(`.executor-avatar-img-${uid}`);
                            const cardFallback = document.querySelector(`.executor-avatar-initials-${uid}`);
                            if (cardImg) {
                                cardImg.src = newUrl + '?t=' + Date.now();
                            } else if (cardFallback) {
                                cardFallback.outerHTML = `<img src="${newUrl}?t=${Date.now()}" alt="Profile" class="rounded-circle object-fit-cover shadow-sm executor-avatar-img-${uid}" style="width: 46px; height: 46px; border: 2px solid var(--neon-cyan); flex-shrink: 0;">`;
                            }

                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                                if (typeof showToast === 'function') {
                                    showToast('Profile picture updated successfully.');
                                }
                            }, 600);
                        } catch (error) {
                            showAlert(error.message);
                        } finally {
                            saveBtn.disabled = false;
                            setTimeout(() => progressBox.classList.add('d-none'), 1200);
                        }
                    }, 'image/jpeg', 0.92);
                });
            })();

            // ==================== ATTACHMENT PREVIEW SLIDESHOW CONTROLLER ====================
            (function () {
                const modalEl = document.getElementById('attachmentSlideshowModal');
                if (!modalEl) return;

                let bsModal = null;
                let slides = [];
                let currentIndex = 0;
                let zoom = 1;
                let rotation = 0;
                let panX = 0;
                let panY = 0;
                let isDragging = false;
                let startX = 0;
                let startY = 0;

                const activeImg = document.getElementById('slideshowActiveImg');
                const fileNameEl = document.getElementById('slideshowFileName');
                const fileSizeEl = document.getElementById('slideshowFileSize');
                const counterEl = document.getElementById('slideshowCounter');
                const downloadBtn = document.getElementById('slideshowDownloadBtn');
                const prevBtn = document.getElementById('slideshowPrevBtn');
                const nextBtn = document.getElementById('slideshowNextBtn');
                const zoomInBtn = document.getElementById('slideshowZoomInBtn');
                const zoomOutBtn = document.getElementById('slideshowZoomOutBtn');
                const zoomResetBtn = document.getElementById('slideshowZoomResetBtn');
                const rotateCWBtn = document.getElementById('slideshowRotateCWBtn');
                const rotateCCWBtn = document.getElementById('slideshowRotateCCWBtn');
                const filmstripEl = document.getElementById('slideshowFilmstrip');
                const filmstripContainer = document.getElementById('slideshowFilmstripContainer');
                const viewport = document.getElementById('slideshowViewport');

                function applyTransform(animate = true) {
                    if (!activeImg) return;
                    activeImg.style.transition = animate ? 'transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none';
                    activeImg.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom}) rotate(${rotation}deg)`;
                    if (zoomResetBtn) {
                        zoomResetBtn.textContent = Math.round(zoom * 100) + '%';
                    }
                    if (viewport) {
                        viewport.style.cursor = zoom > 1 ? 'grab' : 'default';
                    }
                }

                function resetTransform() {
                    zoom = 1;
                    rotation = 0;
                    panX = 0;
                    panY = 0;
                    applyTransform(false);
                }

                function loadSlide(index) {
                    if (!slides || slides.length === 0) return;
                    if (index < 0) index = slides.length - 1;
                    if (index >= slides.length) index = 0;
                    currentIndex = index;
                    resetTransform();

                    const slide = slides[currentIndex];
                    if (activeImg) {
                        activeImg.src = slide.url;
                        activeImg.alt = slide.name;
                    }
                    if (fileNameEl) fileNameEl.textContent = slide.name;
                    if (fileSizeEl) fileSizeEl.textContent = slide.size;
                    if (counterEl) counterEl.textContent = `${currentIndex + 1} / ${slides.length}`;
                    if (downloadBtn) {
                        downloadBtn.href = slide.downloadUrl || slide.url;
                        downloadBtn.setAttribute('download', slide.name);
                    }

                    // Show/hide prev and next buttons
                    if (slides.length <= 1) {
                        if (prevBtn) prevBtn.classList.add('d-none');
                        if (nextBtn) nextBtn.classList.add('d-none');
                        if (filmstripContainer) filmstripContainer.classList.add('d-none');
                    } else {
                        if (prevBtn) prevBtn.classList.remove('d-none');
                        if (nextBtn) nextBtn.classList.remove('d-none');
                        if (filmstripContainer) filmstripContainer.classList.remove('d-none');
                    }

                    // Render filmstrip
                    if (filmstripEl && slides.length > 1) {
                        filmstripEl.innerHTML = '';
                        slides.forEach((s, idx) => {
                            const thumb = document.createElement('div');
                            thumb.className = 'rounded overflow-hidden cursor-pointer flex-shrink-0';
                            const isActive = idx === currentIndex;
                            thumb.style.width = '48px';
                            thumb.style.height = '48px';
                            thumb.style.border = isActive ? '2px solid var(--neon-cyan)' : '1px solid rgba(255, 255, 255, 0.2)';
                            thumb.style.opacity = isActive ? '1' : '0.6';
                            thumb.style.transition = 'all 0.2s';
                            thumb.innerHTML = `<img src="${s.url}" alt="${s.name}" class="w-100 h-100 object-fit-cover">`;
                            thumb.addEventListener('click', () => loadSlide(idx));
                            filmstripEl.appendChild(thumb);
                        });
                    }
                }

                window.openAttachmentSlideshow = function (slideList, startIndex = 0) {
                    if (!slideList || slideList.length === 0) return;
                    slides = slideList;
                    if (!bsModal) {
                        bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
                    }
                    loadSlide(startIndex);
                    bsModal.show();
                };

                // Controls
                if (prevBtn) prevBtn.addEventListener('click', () => loadSlide(currentIndex - 1));
                if (nextBtn) nextBtn.addEventListener('click', () => loadSlide(currentIndex + 1));

                if (zoomInBtn) zoomInBtn.addEventListener('click', () => {
                    zoom = Math.min(5, zoom + 0.25);
                    applyTransform();
                });

                if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => {
                    zoom = Math.max(0.5, zoom - 0.25);
                    if (zoom <= 1) { panX = 0; panY = 0; }
                    applyTransform();
                });

                if (zoomResetBtn) zoomResetBtn.addEventListener('click', () => {
                    resetTransform();
                });

                if (rotateCWBtn) rotateCWBtn.addEventListener('click', () => {
                    rotation = (rotation + 90) % 360;
                    applyTransform();
                });

                if (rotateCCWBtn) rotateCCWBtn.addEventListener('click', () => {
                    rotation = (rotation - 90 + 360) % 360;
                    applyTransform();
                });

                // Wheel zoom
                if (viewport) {
                    viewport.addEventListener('wheel', function (e) {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            zoom = Math.min(5, zoom + 0.15);
                        } else {
                            zoom = Math.max(0.5, zoom - 0.15);
                            if (zoom <= 1) { panX = 0; panY = 0; }
                        }
                        applyTransform(false);
                    });

                    // Pan / Drag when zoomed in
                    viewport.addEventListener('mousedown', function (e) {
                        if (zoom <= 1) return;
                        isDragging = true;
                        startX = e.clientX - panX;
                        startY = e.clientY - panY;
                        viewport.style.cursor = 'grabbing';
                    });

                    window.addEventListener('mousemove', function (e) {
                        if (!isDragging) return;
                        panX = e.clientX - startX;
                        panY = e.clientY - startY;
                        applyTransform(false);
                    });

                    window.addEventListener('mouseup', function () {
                        if (isDragging) {
                            isDragging = false;
                            if (viewport) viewport.style.cursor = zoom > 1 ? 'grab' : 'default';
                        }
                    });

                    // Touch support
                    viewport.addEventListener('touchstart', function (e) {
                        if (zoom <= 1 || e.touches.length !== 1) return;
                        isDragging = true;
                        startX = e.touches[0].clientX - panX;
                        startY = e.touches[0].clientY - panY;
                    }, { passive: true });

                    window.addEventListener('touchmove', function (e) {
                        if (!isDragging || e.touches.length !== 1) return;
                        panX = e.touches[0].clientX - startX;
                        panY = e.touches[0].clientY - startY;
                        applyTransform(false);
                    }, { passive: true });

                    window.addEventListener('touchend', function () {
                        isDragging = false;
                    });
                }

                // Keyboard navigation
                window.addEventListener('keydown', function (e) {
                    if (!modalEl.classList.contains('show')) return;
                    if (e.key === 'ArrowLeft') {
                        loadSlide(currentIndex - 1);
                    } else if (e.key === 'ArrowRight') {
                        loadSlide(currentIndex + 1);
                    } else if (e.key === '+' || e.key === '=') {
                        zoom = Math.min(5, zoom + 0.25);
                        applyTransform();
                    } else if (e.key === '-' || e.key === '_') {
                        zoom = Math.max(0.5, zoom - 0.25);
                        if (zoom <= 1) { panX = 0; panY = 0; }
                        applyTransform();
                    } else if (e.key === 'r' || e.key === 'R') {
                        rotation = (rotation + 90) % 360;
                        applyTransform();
                    } else if (e.key === '0') {
                        resetTransform();
                    }
                });

                // Global Click Delegator for Attachment Thumbnails
                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('.attachment-thumbnail-trigger');
                    if (!trigger) return;

                    e.preventDefault();
                    e.stopPropagation();

                    // Find all sibling image triggers within the same comment card or modal thread
                    const commentCard = trigger.closest('.card') || document;
                    const triggers = Array.from(commentCard.querySelectorAll('.attachment-thumbnail-trigger'));

                    const slideList = triggers.map(t => ({
                        id: t.dataset.attachmentId,
                        url: t.dataset.url,
                        name: t.dataset.name,
                        size: t.dataset.size,
                        downloadUrl: t.dataset.download
                    }));

                    const startIdx = triggers.indexOf(trigger);
                    window.openAttachmentSlideshow(slideList, startIdx >= 0 ? startIdx : 0);
                });
            })();
        });
        </script>
    @endauth
</body>
</html>

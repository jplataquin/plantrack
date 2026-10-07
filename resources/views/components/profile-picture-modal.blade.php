<!-- Profile Picture Zoom & Crop Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #120f24; border: 1px solid var(--neon-cyan); box-shadow: 0 0 25px rgba(0, 240, 255, 0.2);">
            <div class="modal-header border-bottom border-secondary px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-bounding-box text-neon-cyan fs-4"></i>
                    <div>
                        <h5 class="modal-title font-orbitron text-white mb-0" id="profilePictureModalLabel">PROFILE PICTURE</h5>
                        <small class="text-muted" id="avatarModalTargetLabel">Crop & Zoom operative avatar</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" id="avatarTargetUserId" value="">

                <!-- Dropzone / Picker State -->
                <div id="avatarDropzone" class="p-4 rounded text-center cursor-pointer mb-3" style="border: 2px dashed rgba(0, 240, 255, 0.4); background: rgba(0, 240, 255, 0.03);">
                    <i class="bi bi-cloud-arrow-up text-neon-cyan display-5 d-block mb-2"></i>
                    <h6 class="text-white font-rajdhani fw-bold mb-1">SELECT AN IMAGE TO CROP &amp; UPLOAD</h6>
                    <p class="text-muted small mb-2">Supports JPG, PNG, or WEBP (Max 5MB)</p>
                    <button type="button" class="btn btn-sm btn-neon-outline font-rajdhani fw-bold px-3" onclick="document.getElementById('avatarFileInput').click()">
                        <i class="bi bi-folder2-open me-1"></i> BROWSE COMPUTER
                    </button>
                    <input type="file" id="avatarFileInput" accept="image/png, image/jpeg, image/webp" class="d-none">
                </div>

                <!-- Crop Workspace (Initially Hidden) -->
                <div id="avatarCropWorkspace" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted font-rajdhani text-uppercase">Drag image to position &bull; Slider to zoom</small>
                        <button type="button" class="btn btn-sm btn-link text-neon-cyan p-0 text-decoration-none small" onclick="document.getElementById('avatarFileInput').click()">
                            <i class="bi bi-arrow-repeat me-1"></i>Change Image
                        </button>
                    </div>

                    <!-- Crop Viewport Container -->
                    <div class="d-flex justify-content-center mb-3">
                        <div class="position-relative overflow-hidden user-select-none shadow-lg" id="avatarCropViewport" style="width: 300px; height: 300px; background: #0a0814; border: 2px solid var(--neon-cyan); border-radius: 50%; cursor: grab;">
                            <canvas id="avatarCropCanvas" width="300" height="300" class="position-absolute top-0 start-0"></canvas>
                            <!-- Subtle Grid Mask -->
                            <div class="position-absolute top-0 start-0 w-100 h-100 pointer-events-none" style="box-shadow: inset 0 0 25px rgba(0, 240, 255, 0.3); border-radius: 50%;"></div>
                        </div>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3 px-3">
                        <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="avatarZoomOutBtn" title="Zoom Out">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <input type="range" class="form-range flex-grow-1" id="avatarZoomRange" min="1" max="4" step="0.05" value="1">
                        <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="avatarZoomInBtn" title="Zoom In">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2 ms-1" id="avatarResetCropBtn" title="Reset Positioning">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>

                <!-- Chunk Upload Progress Bar -->
                <div id="avatarUploadProgress" class="d-none mb-3">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-neon-cyan" id="avatarProgressStatus"><i class="bi bi-arrow-repeat spin me-1"></i>Uploading chunk to staging...</span>
                        <span class="text-white fw-bold" id="avatarProgressPercent">0%</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(255, 255, 255, 0.1);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" id="avatarProgressBar" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>

                <!-- Feedback Message -->
                <div id="avatarModalAlert" class="alert alert-danger d-none py-2 small mb-0"></div>
            </div>

            <div class="modal-footer border-top border-secondary px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-danger" id="avatarDeleteBtn">
                    <i class="bi bi-trash me-1"></i> Remove Picture
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-neon-cyan px-3 font-rajdhani fw-bold" id="avatarSaveBtn" disabled>
                        <i class="bi bi-crop me-1"></i> CROP &amp; UPLOAD
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

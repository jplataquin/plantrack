<!-- Attachment Preview Slideshow Modal -->
<div class="modal fade" id="attachmentSlideshowModal" tabindex="-1" aria-labelledby="attachmentSlideshowLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content d-flex flex-column h-100" style="background: rgba(10, 8, 20, 0.96); backdrop-filter: blur(20px);">
            <!-- Top Toolbar -->
            <div class="modal-header border-bottom border-secondary px-3 py-2 d-flex justify-content-between align-items-center" style="background: #120f24; min-height: 56px;">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                    <i class="bi bi-file-earmark-image text-neon-cyan fs-5 flex-shrink-0"></i>
                    <span id="slideshowFileName" class="text-white font-rajdhani fw-bold text-truncate" style="max-width: 350px;"></span>
                    <span id="slideshowFileSize" class="badge bg-secondary flex-shrink-0" style="font-size: 0.7rem;"></span>
                    <span id="slideshowCounter" class="badge rounded-pill flex-shrink-0 font-orbitron" style="background: rgba(0, 240, 255, 0.15); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.4); font-size: 0.72rem;"></span>
                </div>

                <!-- Controls: Zoom, Rotate, Download, Close -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="slideshowZoomOutBtn" title="Zoom Out (-)">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2 font-monospace" id="slideshowZoomResetBtn" title="Reset Zoom (100%)" style="font-size: 0.75rem; min-width: 52px;">
                        100%
                    </button>
                    <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="slideshowZoomInBtn" title="Zoom In (+)">
                        <i class="bi bi-zoom-in"></i>
                    </button>

                    <div class="vr bg-secondary mx-1" style="height: 24px;"></div>

                    <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="slideshowRotateCCWBtn" title="Rotate Counter-Clockwise (-90°)">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-neon-outline p-1 px-2" id="slideshowRotateCWBtn" title="Rotate Clockwise (+90°)">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <div class="vr bg-secondary mx-1" style="height: 24px;"></div>

                    <a href="#" id="slideshowDownloadBtn" class="btn btn-sm btn-neon-cyan p-1 px-2 text-decoration-none" title="Download File" download>
                        <i class="bi bi-download me-1"></i><span class="d-none d-md-inline small font-rajdhani fw-bold">DOWNLOAD</span>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2 ms-1" id="slideshowCloseBtn" data-bs-dismiss="modal" title="Close (Esc)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Central Slideshow Viewport -->
            <div class="modal-body p-0 flex-grow-1 position-relative d-flex align-items-center justify-content-center overflow-hidden" id="slideshowViewport" style="background: radial-gradient(circle at center, #17132e 0%, #0a0814 100%); cursor: grab;">
                <!-- Slideshow Image Stage -->
                <div id="slideshowStage" class="position-absolute d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; pointer-events: none;">
                    <img id="slideshowActiveImg" src="" alt="Preview" class="user-select-none shadow-lg" style="max-width: 90%; max-height: 85vh; object-fit: contain; transform-origin: center center; transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1); pointer-events: auto;">
                </div>

                <!-- Navigation Arrows -->
                <button type="button" class="btn btn-neon-outline position-absolute start-0 top-50 translate-middle-y ms-3 rounded-circle d-flex align-items-center justify-content-center shadow-lg" id="slideshowPrevBtn" style="width: 48px; height: 48px; z-index: 10; background: rgba(18, 15, 36, 0.85);" title="Previous Image (Left Arrow)">
                    <i class="bi bi-chevron-left fs-4"></i>
                </button>
                <button type="button" class="btn btn-neon-outline position-absolute end-0 top-50 translate-middle-y me-3 rounded-circle d-flex align-items-center justify-content-center shadow-lg" id="slideshowNextBtn" style="width: 48px; height: 48px; z-index: 10; background: rgba(18, 15, 36, 0.85);" title="Next Image (Right Arrow)">
                    <i class="bi bi-chevron-right fs-4"></i>
                </button>
            </div>

            <!-- Bottom Filmstrip Navigation -->
            <div id="slideshowFilmstripContainer" class="border-top border-secondary py-2 px-3 d-flex justify-content-center overflow-x-auto custom-scrollbar" style="background: #120f24; min-height: 68px;">
                <div id="slideshowFilmstrip" class="d-flex gap-2 align-items-center"></div>
            </div>
        </div>
    </div>
</div>

<!-- Self-contained CDN fallback dependencies for mobile design reliability -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        background-color: #f1f5f9 !important;
        margin: 0;
        padding: 0;
    }
    .mobile-app-card {
        min-height: 520px;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        border: 1px solid #e2e8f0;
    }
    .app-icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bg-light-primary {
        background-color: #eff6ff;
    }
    .scanner-container {
        width: 100%;
        background: #000;
        border-radius: 16px;
        overflow: hidden;
        aspect-ratio: 4/3;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #0f172a;
    }
    #reader {
        width: 100% !important;
        height: 100% !important;
        border: none !important;
        background: #000 !important;
    }
    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block !important;
    }
    #reader__scan_region {
        background: #000 !important;
        width: 100% !important;
        height: 100% !important;
    }
    #reader__dashboard {
        display: none !important;
    }
</style>

<!-- Hidden temporary canvas element for manual frame decoding -->
<div id="reader-temp-canvas" style="display: none;"></div>

<!-- Mobile Application Container Wrapper -->
<div class="d-flex justify-content-center align-items-center py-3 d-print-none" style="min-height: 90vh; background: #f1f5f9;">
    <div class="mobile-app-card shadow-lg d-flex flex-column" style="width: 100%; max-width: 480px; background: #ffffff; overflow: hidden;">
        
        <!-- Header Brand -->
        <div class="mobile-app-header bg-dark text-white p-3 text-center position-relative">
            <h5 class="m-0 fw-bold"><i class="fa-solid fa-qrcode text-primary me-2"></i> QR Code Scanner Hub</h5>
            <small class="text-secondary" style="font-size: 11px;">Garment Floor Scan Unit</small>
            
            <!-- Complete button (visible only in scanner view) -->
            <button type="button" id="complete-btn" class="btn btn-sm btn-outline-danger rounded-pill px-3 position-absolute end-0 top-50 translate-middle-y me-3" style="display: none; font-size: 11px;">
                <i class="fa-solid fa-circle-check me-1"></i> Complete
            </button>
        </div>

        <div class="mobile-app-body p-4 flex-grow-1">

            <!-- ==================== SCREEN 1: Stage Selection ==================== -->
            <div id="selection-view">
                <div class="text-center mb-3">
                    <div class="app-icon-circle bg-light-primary mb-2" style="width: 65px; height: 65px;">
                        <i class="fa-solid fa-industry fs-2 text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-dark m-0">Stage & Batch Setup</h5>
                    <p class="text-secondary small">Select your active production batch to auto-load style WIP stages.</p>
                </div>

                <!-- Step 1: Active Production Started Batch Dropdown -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-primary mb-1">
                        <i class="fa-solid fa-industry me-1"></i> 1. SELECT STARTED PRODUCTION BATCH <span class="text-danger">*</span>
                    </label>
                    <select id="batch-select" class="form-select form-select-lg text-dark fw-bold border-2 font-monospace" style="border-radius: 12px; font-size: 14px;">
                        <option value="">Select Batch</option>
                        <?php if (!empty($batches)): ?>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['production_no']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Step 2: Auto-Loaded WIP Stages Dropdown -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-primary mb-1">
                        <i class="fa-solid fa-route me-1"></i> 2. SELECT WIP STAGE <span class="text-danger">*</span>
                    </label>
                    <select id="stage-select" class="form-select form-select-lg text-dark fw-bold border-2" style="border-radius: 12px; font-size: 14px;" disabled>
                        <option value="">-- Please Select Batch First --</option>
                    </select>
                </div>

                <button type="button" id="start-work-btn" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow" disabled>
                    <i class="fa-solid fa-play me-2"></i> Start Work / Scan
                </button>
                
                <div class="mt-4 text-center">
                    <span class="badge bg-light text-secondary rounded-pill px-3 py-2">
                        Logged User: <strong><?= htmlspecialchars(\App\Core\Session::get('user_name')) ?></strong>
                    </span>
                </div>
            </div>

            <!-- ==================== SCREEN 2: Active Scanner ==================== -->
            <div id="scanner-view" style="display: none;">
                <div class="text-center mb-3">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1.5 rounded-pill fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">
                        <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span> SCANNING ACTIVE: <span id="active-stage-label" class="text-uppercase"></span>
                    </span>
                </div>

                <!-- Camera Select Dropdown (hidden if single/no camera) -->
                <div class="mb-3" id="camera-select-container" style="display: none;">
                    <label class="form-label small fw-bold text-secondary mb-1"><i class="fa-solid fa-camera me-1"></i> SELECT CAMERA</label>
                    <select id="camera-select" class="form-select text-dark fw-bold border-2" style="border-radius: 12px; background-color: #f8fafc; font-size: 13px;">
                    </select>
                </div>

                <!-- Video Scanner Viewport & Camera Overlays -->
                <div class="scanner-container mb-3 position-relative" id="scanner-container">
                    <div id="reader"></div>

                    <!-- Fast Sequence Validation & Duplicate Alert Banner Overlay -->
                    <div id="scanner-alert-banner" class="position-absolute top-50 start-50 translate-middle p-3 rounded-4 shadow-lg text-center" style="display: none; z-index: 1060; background: rgba(15, 23, 42, 0.96); backdrop-filter: blur(8px); border: 2px solid #ef4444; width: 92%;">
                        <div class="text-white">
                            <i id="scanner-alert-icon" class="fa-solid fa-triangle-exclamation fs-1 mb-2 text-warning"></i>
                            <h6 id="scanner-alert-title" class="fw-bold mb-1 text-uppercase text-white" style="letter-spacing: 0.5px;">Sequence Order Error</h6>
                            <p id="scanner-alert-msg" class="small mb-3 text-white-50 font-monospace" style="font-size: 12px; line-height: 1.4;"></p>
                            <button type="button" id="scanner-alert-dismiss" class="btn btn-warning btn-sm fw-bold rounded-pill px-4 shadow">
                                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Tap to Continue
                            </button>
                        </div>
                    </div>

                    <!-- Scanned Result Card Overlay (Directly Over Camera Viewport - Zero Scroll) -->
                    <div id="scan-result-card" class="position-absolute top-0 start-0 w-100 h-100 p-3 bg-white d-flex flex-column justify-content-between" style="display: none; z-index: 1050; border-radius: 16px; overflow-y: auto;">
                        <div class="text-center">
                            <span class="badge bg-success text-white text-uppercase mb-1 fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-shield-check me-1"></i> Verified Active Item
                            </span>
                            <h5 id="scanned-code-display" class="fw-bold font-monospace text-primary my-1"></h5>
                            
                            <div class="border rounded p-2 mb-2 bg-light text-start" style="font-size: 11px; line-height: 1.4; color: #334155;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-secondary small">Style No:</span>
                                    <strong id="scanned-style-no-display" class="text-dark"></strong>
                                </div>
                                <div class="mb-1">
                                    <span class="text-secondary small">Style Name:</span>
                                    <span id="scanned-style-name-display" class="text-dark fw-bold"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-secondary small">Category:</span>
                                    <span id="scanned-category-display" class="text-dark text-capitalize fw-semibold"></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary small">Fabric:</span>
                                    <span id="scanned-fabric-display" class="text-dark"></span>
                                </div>
                            </div>

                            <div class="row g-2 mb-2 bg-primary bg-opacity-10 p-2 rounded">
                                <div class="col-6 text-start">
                                    <span class="text-secondary small d-block" style="font-size: 10px;">GARMENT SIZE</span>
                                    <strong id="scanned-size-display" class="text-primary fs-4 font-monospace fw-bold"></strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="text-secondary small d-block" style="font-size: 10px;">SERIAL NO</span>
                                    <strong id="scanned-serial-display" class="text-primary fs-4 font-monospace fw-bold"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Touch Buttons Pass/Fail -->
                        <div class="row g-2 pt-1 border-top">
                            <div class="col-6">
                                <button type="button" id="pass-btn" class="btn btn-success btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                                    <i class="fa-solid fa-circle-check me-1"></i> PASS
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" id="fail-btn" class="btn btn-danger btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> FAIL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button: SCAN / CAPTURE QR CODE -->
                <div id="scan-trigger-container" class="mb-3 text-center">
                    <button type="button" id="trigger-scan-btn" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm" style="font-size: 15px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-expand me-2"></i> SCAN / CAPTURE QR CODE
                    </button>
                </div>

                <!-- Flashlight Button (hidden by default) -->
                <div class="mb-3" id="flashlight-container" style="display: none;">
                    <button type="button" id="flashlight-toggle-btn" class="btn btn-warning w-100 py-2.5 rounded-pill fw-bold text-dark shadow-sm" style="font-size: 13px;">
                        <i class="fa-solid fa-bolt me-1"></i> Toggle Flashlight / Torch
                    </button>
                </div>

                <!-- Manual Barcode Input Fallback -->
                <div id="manual-input-container" class="card border border-2 p-3 mb-3 bg-light" style="display: none; border-radius: 16px;">
                    <div class="text-center">
                        <label class="form-label small fw-bold text-secondary mb-2"><i class="fa-solid fa-keyboard me-1"></i> MANUAL QR CODE INPUT</label>
                        <input type="text" id="manual-code-input" class="form-control form-control-lg text-center font-monospace mb-2" placeholder="e.g. BATCH-001-S-0005" style="border-radius: 10px; border: 2px solid #cbd5e1;">
                        <button type="button" id="manual-submit-btn" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold">
                            <i class="fa-solid fa-circle-check me-1"></i> Submit Scanned Code
                        </button>
                    </div>
                </div>

                <!-- Switch Mode Button -->
                <div class="text-center mb-3">
                    <button type="button" id="toggle-mode-btn" class="btn btn-sm btn-link text-decoration-none text-secondary fw-bold">
                        <i class="fa-solid fa-keyboard me-1"></i> Switch to Manual Entry Mode
                    </button>
                </div>

                <!-- Live stats -->
                <div class="d-flex justify-content-between text-secondary small px-2 mt-2">
                    <span>Scanned pieces: <strong id="pieces-count" class="text-dark">0</strong></span>
                    <span>Elapsed: <strong id="elapsed-timer" class="text-dark">00:00</strong></span>
                </div>
            </div>
            <!-- ==================== END SCREENS ==================== -->

        </div>
    </div>
</div>

<!-- Load locally hosted camera scanner dependency -->
<script src="<?= base_url('assets/js/html5-qrcode.min.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===================== DOM REFERENCES =====================
    const selectionView = document.getElementById('selection-view');
    const scannerView = document.getElementById('scanner-view');
    const batchSelect = document.getElementById('batch-select');
    const stageSelect = document.getElementById('stage-select');
    const startWorkBtn = document.getElementById('start-work-btn');
    const completeBtn = document.getElementById('complete-btn');
    const activeStageLabel = document.getElementById('active-stage-label');

    // Default dropdown reset to "Select Batch" on every page load
    if (batchSelect) {
        batchSelect.value = '';
    }

    // Auto-load style WIP stages when active batch is selected
    if (batchSelect && stageSelect) {
        batchSelect.addEventListener('change', function() {
            const batchId = this.value;
            if (!batchId) {
                stageSelect.innerHTML = '<option value="">-- Please Select Batch First --</option>';
                stageSelect.disabled = true;
                if (startWorkBtn) startWorkBtn.disabled = true;
                return;
            }

            stageSelect.innerHTML = '<option value="">Loading Style WIP Stages...</option>';
            stageSelect.disabled = true;

            fetch('<?= base_url("company/production/batch-stages/") ?>' + batchId)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.stages && data.stages.length > 0) {
                        let html = '<option value="">-- Step 2: Select WIP Stage --</option>';
                        data.stages.forEach(stg => {
                            const stgKey = stg.key || stg;
                            const stgName = stg.name || (stgKey.replace(/_/g, ' ').toUpperCase());
                            const stgOrder = stg.order ? ('#' + stg.order + ' ') : '';
                            html += `<option value="${stgKey}">${stgOrder}${stgName}</option>`;
                        });
                        stageSelect.innerHTML = html;
                        stageSelect.disabled = false;
                    } else {
                        stageSelect.innerHTML = '<option value="">No stages configured for this style</option>';
                    }
                })
                .catch(err => {
                    stageSelect.innerHTML = '<option value="">Error loading stages</option>';
                });
        });

        stageSelect.addEventListener('change', function() {
            if (startWorkBtn) startWorkBtn.disabled = !this.value;
        });
    }

    const scanResultCard = document.getElementById('scan-result-card');
    const codeDisplay = document.getElementById('scanned-code-display');
    const sizeDisplay = document.getElementById('scanned-size-display');
    const serialDisplay = document.getElementById('scanned-serial-display');
    const passBtn = document.getElementById('pass-btn');
    const failBtn = document.getElementById('fail-btn');
    const piecesCountEl = document.getElementById('pieces-count');
    const timerEl = document.getElementById('elapsed-timer');

    const toggleModeBtn = document.getElementById('toggle-mode-btn');
    const manualContainer = document.getElementById('manual-input-container');
    const scannerContainer = document.getElementById('scanner-container');
    const manualCodeInput = document.getElementById('manual-code-input');
    const manualSubmitBtn = document.getElementById('manual-submit-btn');

    const triggerScanBtn = document.getElementById('trigger-scan-btn');
    const scanTriggerContainer = document.getElementById('scan-trigger-container');

    const cameraSelect = document.getElementById('camera-select');
    const cameraSelectContainer = document.getElementById('camera-select-container');
    const flashlightContainer = document.getElementById('flashlight-container');
    const flashlightToggleBtn = document.getElementById('flashlight-toggle-btn');

    const alertBanner = document.getElementById('scanner-alert-banner');
    const alertIcon = document.getElementById('scanner-alert-icon');
    const alertTitle = document.getElementById('scanner-alert-title');
    const alertMsg = document.getElementById('scanner-alert-msg');
    const alertDismissBtn = document.getElementById('scanner-alert-dismiss');

    // ===================== STATE =====================
    let html5QrCode = null;
    let tempFileDecoder = null;
    let scanCount = 0;
    let sessionStartTime = null;
    let pieceStartTime = null;
    let timerInterval = null;
    let alertTimeout = null;
    let currentScannedCode = null;
    let isCameraMode = true;
    let cameraRetryCount = 0;
    const maxRetryAttempts = 3;
    let torchState = false;

    // ===================== SCREEN 1: START WORK =====================
    startWorkBtn.addEventListener('click', function() {
        const stage = stageSelect.value;
        if (!stage) {
            alert('Please select a production stage first.');
            return;
        }

        // Transition to scanner screen
        activeStageLabel.innerText = stage.replace(/_/g, ' ');
        selectionView.style.display = 'none';
        scannerView.style.display = 'block';
        completeBtn.style.display = 'block';

        sessionStartTime = new Date();
        pieceStartTime = new Date();
        scanCount = 0;
        piecesCountEl.innerText = '0';
        scanResultCard.style.display = 'none';
        hideAlertBanner();

        // Start timer
        clearInterval(timerInterval);
        timerInterval = setInterval(updateTimer, 1000);

        // Launch camera scanner
        initScanner();
    });

    // ===================== COMPLETE SESSION =====================
    completeBtn.addEventListener('click', function() {
        if (confirm('Complete scanning session and return to stage selection?')) {
            stopScanner(true);
            clearInterval(timerInterval);
            scannerView.style.display = 'none';
            completeBtn.style.display = 'none';
            selectionView.style.display = 'block';
            
            // Reset dropdowns to default
            if (batchSelect) {
                batchSelect.value = '';
            }
            if (stageSelect) {
                stageSelect.innerHTML = '<option value="">-- Please Select Batch First --</option>';
                stageSelect.disabled = true;
            }
            if (startWorkBtn) {
                startWorkBtn.disabled = true;
            }
        }
    });

    // Alert Banner Dismiss Action
    if (alertDismissBtn) {
        alertDismissBtn.addEventListener('click', function() {
            hideAlertBanner();
            if (isCameraMode && html5QrCode && html5QrCode.isScanning) {
                html5QrCode.resume();
            }
        });
    }

    function showAlertBanner(title, message, isWarning = true) {
        clearTimeout(alertTimeout);
        if (alertBanner) {
            alertTitle.innerText = title;
            alertMsg.innerText = message;

            if (isWarning) {
                alertBanner.style.borderColor = '#3b82f6';
                alertIcon.className = 'fa-solid fa-circle-info fs-1 mb-2 text-info';
            } else {
                alertBanner.style.borderColor = '#ef4444';
                alertIcon.className = 'fa-solid fa-triangle-exclamation fs-1 mb-2 text-warning';
            }

            alertBanner.style.display = 'block';

            alertTimeout = setTimeout(() => {
                hideAlertBanner();
                if (isCameraMode && html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.resume();
                }
            }, 4500);
        }
    }

    function hideAlertBanner() {
        clearTimeout(alertTimeout);
        if (alertBanner) {
            alertBanner.style.display = 'none';
        }
    }

    // ===================== CAMERA INITIALIZATION =====================
    function initScanner() {
        isCameraMode = true;
        scannerContainer.style.display = 'flex';
        scanTriggerContainer.style.display = 'block';
        manualContainer.style.display = 'none';
        toggleModeBtn.innerHTML = '<i class="fa-solid fa-keyboard me-1"></i> Switch to Manual Entry Mode';

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                let backCameraId = devices[0].id;
                
                if (devices.length > 1) {
                    cameraSelectContainer.style.display = 'block';
                    cameraSelect.innerHTML = '';
                    
                    devices.forEach((device, idx) => {
                        const opt = document.createElement('option');
                        opt.value = device.id;
                        const label = device.label || `Camera ${idx + 1}`;
                        opt.innerText = label;
                        if (label.toLowerCase().includes('back') || label.toLowerCase().includes('rear') || label.toLowerCase().includes('environment')) {
                            backCameraId = device.id;
                            opt.selected = true;
                        }
                        cameraSelect.appendChild(opt);
                    });
                } else {
                    cameraSelectContainer.style.display = 'none';
                }

                startCameraScanner(backCameraId);
            } else {
                switchToManualMode("No camera detected. Switched to manual mode.");
            }
        }).catch(err => {
            console.warn("Camera enum error:", err);
            switchToManualMode("Unable to access camera permissions.");
        });
    }

    function startCameraScanner(cameraId) {
        if (html5QrCode) {
            stopScanner(false);
        }

        html5QrCode = new Html5Qrcode("reader");
        const config = {
            fps: 15,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.333333
        };

        html5QrCode.start(
            cameraId,
            config,
            (decodedText, decodedResult) => {
                onScanSuccess(decodedText);
            },
            (errorMessage) => {
                // Background scan frame decode failure - silent ignore
            }
        ).then(() => {
            cameraRetryCount = 0;
            // Check torch capability
            try {
                const capabilities = html5QrCode.getRunningTrackCapabilities();
                if (capabilities && capabilities.torch) {
                    flashlightContainer.style.display = 'block';
                } else {
                    flashlightContainer.style.display = 'none';
                }
            } catch(e) {
                flashlightContainer.style.display = 'none';
            }
        }).catch(err => {
            console.error("Camera start failure:", err);
            handleCameraError(err, cameraId);
        });
    }

    function handleCameraError(err, cameraId) {
        let errorStr = err ? err.toString() : '';
        let friendlyMessage = "Camera initialization failed.";

        if (errorStr.indexOf("NotAllowedError") !== -1 || errorStr.indexOf("Permission") !== -1) {
            friendlyMessage = "Camera permission was denied. Please enable camera access in browser settings.";
            switchToManualMode(friendlyMessage);
            return;
        } else if (errorStr.indexOf("NotReadableError") !== -1 || errorStr.indexOf("already in use") !== -1 || errorStr.indexOf("Could not start video source") !== -1) {
            friendlyMessage = "Camera is already in use by another app or tab.";
        } else if (errorStr.indexOf("OverconstrainedError") !== -1) {
            friendlyMessage = "Camera constraints not supported by hardware.";
        } else if (errorStr.indexOf("NotFoundError") !== -1 || errorStr.indexOf("DevicesNotFound") !== -1) {
            friendlyMessage = "No camera hardware detected.";
            switchToManualMode(friendlyMessage);
            return;
        }

        if (cameraRetryCount < maxRetryAttempts) {
            cameraRetryCount++;
            showTemporaryToast(`${friendlyMessage} Retrying... (${cameraRetryCount}/${maxRetryAttempts})`, "warning", 3000);
            setTimeout(() => { startCameraScanner(cameraId); }, 1500);
        } else {
            switchToManualMode(`${friendlyMessage} Switching to Manual Input.`);
        }
    }

    function stopScanner(resetMode = true) {
        if (resetMode) { isCameraMode = false; }
        if (html5QrCode) {
            if (html5QrCode.isScanning) {
                html5QrCode.stop().then(() => { html5QrCode = null; }).catch(() => { html5QrCode = null; });
            } else {
                html5QrCode = null;
            }
        }
    }

    // ===================== TRIGGER SCAN BUTTON PRESS ACTION =====================
    triggerScanBtn.addEventListener('click', function() {
        captureAndAnalyzeFrame();
    });

    function captureAndAnalyzeFrame() {
        const video = document.querySelector('#reader video');
        if (!video || video.readyState < 2) {
            showTemporaryToast("Camera stream loading... Align QR code and press SCAN again.", "warning");
            return;
        }

        const origHtml = triggerScanBtn.innerHTML;
        triggerScanBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Analyzing QR Code...';
        triggerScanBtn.disabled = true;

        const canvas = document.createElement('canvas');
        const vw = video.videoWidth || 640;
        const vh = video.videoHeight || 480;

        const angles = [0, 90];
        let angleIdx = 0;

        function tryNextAngle() {
            if (angleIdx >= angles.length) {
                triggerScanBtn.innerHTML = origHtml;
                triggerScanBtn.disabled = false;
                showTemporaryToast("No valid QR code detected. Hold camera steady over QR sticker and press SCAN.", "warning", 3500);
                return;
            }

            const angle = angles[angleIdx++];
            if (angle === 0) {
                canvas.width = vw;
                canvas.height = vh;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, vw, vh);
            } else {
                canvas.width = vh;
                canvas.height = vw;
                const ctx = canvas.getContext('2d');
                ctx.translate(vh / 2, vw / 2);
                ctx.rotate(angle * Math.PI / 180);
                ctx.drawImage(video, -vw / 2, -vh / 2);
            }

            canvas.toBlob(blob => {
                if (!blob) {
                    tryNextAngle();
                    return;
                }

                const file = new File([blob], "scan_frame.png", { type: "image/png" });

                if (!tempFileDecoder) {
                    tempFileDecoder = new Html5Qrcode("reader-temp-canvas");
                }

                tempFileDecoder.scanFile(file, true)
                    .then(decodedText => {
                        triggerScanBtn.innerHTML = origHtml;
                        triggerScanBtn.disabled = false;
                        onScanSuccess(decodedText);
                    })
                    .catch(err => {
                        tryNextAngle();
                    });
            }, 'image/png');
        }

        tryNextAngle();
    }

    // ===================== MODE TOGGLE =====================
    toggleModeBtn.addEventListener('click', function() {
        if (manualContainer.style.display === 'none') {
            switchToManualMode();
        } else {
            initScanner();
        }
    });

    function switchToManualMode(reason = null) {
        stopScanner(true);
        scannerContainer.style.display = 'none';
        scanTriggerContainer.style.display = 'none';
        cameraSelectContainer.style.display = 'none';
        flashlightContainer.style.display = 'none';
        manualContainer.style.display = 'block';
        toggleModeBtn.innerHTML = '<i class="fa-solid fa-camera me-1"></i> Switch to Camera Mode';

        if (reason) { showTemporaryToast(reason, "warning", 4000); }
        manualCodeInput.value = '';
        manualCodeInput.focus();
    }

    // ===================== CAMERA SWITCH =====================
    cameraSelect.addEventListener('change', function(e) {
        const selectedId = e.target.value;
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(() => { html5QrCode = null; startCameraScanner(selectedId); }).catch(() => { html5QrCode = null; startCameraScanner(selectedId); });
        } else {
            startCameraScanner(selectedId);
        }
    });

    // ===================== FLASHLIGHT =====================
    flashlightToggleBtn.addEventListener('click', function() {
        if (html5QrCode && html5QrCode.isScanning) {
            try {
                html5QrCode.toggleFlashlight().then(isTorchOn => {
                    torchState = isTorchOn;
                    if (torchState) {
                        flashlightToggleBtn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Flashlight ON';
                        flashlightToggleBtn.className = 'btn btn-light w-100 py-2.5 rounded-pill fw-bold text-dark shadow-sm border border-warning';
                    } else {
                        flashlightToggleBtn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> Toggle Flashlight / Torch';
                        flashlightToggleBtn.className = 'btn btn-warning w-100 py-2.5 rounded-pill fw-bold text-dark shadow-sm';
                    }
                }).catch(e => { console.error("Torch toggle failed:", e); });
            } catch(e) { console.error("Torch call failed:", e); }
        }
    });

    // ===================== MANUAL ENTRY =====================
    manualSubmitBtn.addEventListener('click', function() {
        const code = manualCodeInput.value.trim();
        if (!code) { alert('Please enter or scan a valid code.'); return; }
        onScanSuccess(code);
    });
    manualCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { manualSubmitBtn.click(); }
    });

    // ===================== ON SCAN SUCCESS (VERIFICATION) =====================
    function onScanSuccess(decodedText) {
        hideAlertBanner();

        // Pause camera during verification
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.pause(true);
        }

        scanResultCard.style.display = 'none';
        
        const loader = document.createElement('div');
        loader.className = 'alert alert-info text-center py-2.5 mb-2 fw-semibold animate-pulse';
        loader.id = 'qr-verifying-loader';
        loader.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Verifying Tag Authenticity...';
        scannerView.insertBefore(loader, scannerView.firstChild);

        const formData = new FormData();
        formData.append('qr_code', decodedText);
        formData.append('stage', stageSelect.value);
        formData.append('csrf_token', "<?= \App\Core\Session::csrfToken() ?>");

        fetch("<?= base_url('company/production/qr-tracking/verify') ?>", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            loader.remove();
            if (data.success) {
                currentScannedCode = decodedText;
                
                codeDisplay.innerText = data.product.batch_no;
                document.getElementById('scanned-style-no-display').innerText = data.product.style_no;
                document.getElementById('scanned-style-name-display').innerText = data.product.style_name;
                document.getElementById('scanned-category-display').innerText = data.product.category + ' | ' + data.product.brand;
                document.getElementById('scanned-fabric-display').innerText = data.product.composition;
                sizeDisplay.innerText = data.product.size;
                serialDisplay.innerText = '#' + String(data.product.serial).padStart(4, '0') + ' / ' + String(data.product.target_qty).padStart(4, '0');

                // Display verification card overlay directly over camera (0 scrolling)
                scanResultCard.style.display = 'flex';
            } else {
                if (data.already_validated) {
                    showAlertBanner("ALREADY VALIDATED IN THIS STAGE", data.message, true);
                } else {
                    showAlertBanner("SEQUENCE ORDER MISMATCH", data.message, false);
                }
            }
        })
        .catch(err => {
            loader.remove();
            console.error(err);
            showAlertBanner("CONNECTION ERROR", "Unable to connect to verification server. Please check internet connection.", false);
        });
    }

    // ===================== PASS / FAIL LOGGING =====================
    passBtn.addEventListener('click', function() { submitLog('pass'); });
    failBtn.addEventListener('click', function() { submitLog('fail'); });

    function submitLog(status) {
        if (!currentScannedCode) return;

        const durationSeconds = Math.round((new Date() - pieceStartTime) / 1000);
        const stage = stageSelect.value;

        const formData = new FormData();
        formData.append('qr_code', currentScannedCode);
        formData.append('stage', stage);
        formData.append('status', status);
        formData.append('duration_seconds', durationSeconds);
        formData.append('csrf_token', "<?= \App\Core\Session::csrfToken() ?>");

        passBtn.disabled = true;
        failBtn.disabled = true;

        fetch("<?= base_url('company/production/qr-tracking/log') ?>", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                scanCount++;
                piecesCountEl.innerText = scanCount;
                
                showTemporaryToast(data.message, status === 'pass' ? "success" : "danger", 2500);
                
                scanResultCard.style.display = 'none';
                currentScannedCode = null;
                manualCodeInput.value = '';
                pieceStartTime = new Date();
                
                if (manualContainer.style.display !== 'none') {
                    manualCodeInput.focus();
                } else if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.resume();
                }
            } else {
                scanResultCard.style.display = 'none';
                showAlertBanner("LOGGING FAILED", data.message, false);
            }
        })
        .catch(err => {
            console.error(err);
            scanResultCard.style.display = 'none';
            showAlertBanner("CONNECTION FAILURE", "Failed to communicate with production server.", false);
        })
        .finally(() => {
            passBtn.disabled = false;
            failBtn.disabled = false;
        });
    }

    // ===================== TIMER =====================
    function updateTimer() {
        const diff = new Date() - sessionStartTime;
        const totalSecs = Math.floor(diff / 1000);
        const mins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;
        timerEl.innerText = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    // ===================== TOAST HELPER =====================
    function showTemporaryToast(message, type = "info", duration = 4000) {
        const toast = document.createElement('div');
        const alertClass = type === "success" ? "alert-success" : (type === "warning" ? "alert-warning" : (type === "danger" ? "alert-danger" : "alert-info"));
        toast.className = `alert ${alertClass} text-center py-2.5 mb-2 small fw-bold font-monospace shadow-sm`;
        toast.innerText = message;
        const container = scannerView;
        container.insertBefore(toast, container.firstChild);
        setTimeout(() => toast.remove(), duration);
    }

    // ===================== VISIBILITY / LIFECYCLE =====================
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            stopScanner(false);
        } else if (document.visibilityState === 'visible') {
            if (isCameraMode && scannerView.style.display !== 'none') {
                initScanner();
            }
        }
    });
    window.addEventListener('pagehide', function() { stopScanner(true); });
    window.addEventListener('beforeunload', function() { stopScanner(true); });
});
</script>

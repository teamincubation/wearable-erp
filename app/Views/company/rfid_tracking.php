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
    }
    #reader__scan_region {
        background: #000 !important;
    }
    #reader__dashboard {
        display: none !important; /* Hide html5-qrcode controls */
    }
</style>

<!-- Mobile Application Container Wrapper -->
<div class="d-flex justify-content-center align-items-center py-3 d-print-none" style="min-height: 90vh; background: #f1f5f9;">
    <div class="mobile-app-card shadow-lg d-flex flex-column" style="width: 100%; max-width: 480px; background: #ffffff; overflow: hidden;">
        
        <!-- Header Brand -->
        <div class="mobile-app-header bg-dark text-white p-3 text-center position-relative">
            <h5 class="m-0 fw-bold"><i class="fa-solid fa-qrcode text-primary me-2"></i> RFID Tracking Hub</h5>
            <small class="text-secondary">Garment Floor Scan Unit</small>
            
            <!-- Exit button in scanner screen (hidden on selection screen) -->
            <button type="button" id="complete-btn" class="btn btn-sm btn-outline-danger rounded-pill px-3 position-absolute end-0 top-50 translate-middle-y me-3" style="display: none; font-size: 11px;">
                <i class="fa-solid fa-circle-check me-1"></i> Complete
            </button>
        </div>

        <div class="mobile-app-body p-4 flex-grow-1">
            <!-- Stage selection view -->
            <div id="selection-view">
                <div class="text-center mb-4">
                    <div class="app-icon-circle bg-light-primary mb-3">
                        <i class="fa-solid fa-industry fs-1 text-primary"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Stage Setup</h4>
                    <p class="text-secondary small">Select your operational line and click Start to launch the camera scanner.</p>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">SELECT WIP STAGE</label>
                    <select id="stage-select" class="form-select form-select-lg text-dark fw-bold border-2" style="border-radius: 12px;">
                        <option value="">-- Choose Stage --</option>
                        <?php foreach ($stages as $stg): ?>
                            <option value="<?= $stg ?>"><?= str_replace('_', ' ', strtoupper($stg)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" id="start-work-btn" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                    <i class="fa-solid fa-play me-2"></i> Start Work / Scan
                </button>
                
                <div class="mt-4 text-center">
                    <span class="badge bg-light text-secondary rounded-pill px-3 py-2">
                        Logged User: <strong><?= htmlspecialchars(\App\Core\Session::get('user_name')) ?></strong>
                    </span>
                </div>
            </div>

            <!-- Active scanning viewport screen -->
            <div id="scanner-view" style="display: none;">
                <div class="text-center mb-3">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1.5 rounded-pill fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">
                        <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span> SCANNING ACTIVE: <span id="active-stage-label" class="text-uppercase"></span>
                    </span>
                </div>

                <!-- Video Scanner Viewport -->
                <div class="scanner-container mb-3 position-relative" id="scanner-container">
                    <div id="reader" style="width: 100%; border-radius: 16px; overflow: hidden; border: 3px solid #0f172a; background: #000;"></div>
                </div>

                <!-- Manual Barcode Input Fallback (Hidden by default, shown if camera fails or clicked) -->
                <div id="manual-input-container" class="card border border-2 p-3 mb-3 bg-light" style="display: none; border-radius: 16px;">
                    <div class="text-center">
                        <label class="form-label small fw-bold text-secondary mb-2"><i class="fa-solid fa-keyboard me-1"></i> MANUAL RFID TAG INPUT</label>
                        <input type="text" id="manual-code-input" class="form-control form-control-lg text-center font-monospace mb-2" placeholder="e.g. BATCH-001-S-0005" style="border-radius: 10px; border: 2px solid #cbd5e1;">
                        <button type="button" id="manual-submit-btn" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold">
                            <i class="fa-solid fa-circle-check me-1"></i> Submit Scanned Code
                        </button>
                    </div>
                </div>

                <!-- Switch Mode Button Trigger -->
                <div class="text-center mb-3">
                    <button type="button" id="toggle-mode-btn" class="btn btn-sm btn-link text-decoration-none text-secondary fw-bold">
                        <i class="fa-solid fa-keyboard me-1"></i> Switch to Manual Entry Mode
                    </button>
                </div>

                <!-- Scanned Result Card -->
                <div id="scan-result-card" class="card border-2 border-primary mb-3 bg-light" style="display: none; border-radius: 16px;">
                    <div class="card-body p-3 text-center">
                        <div class="small text-secondary fw-bold">SCANNED RFID ITEM</div>
                        <h5 id="scanned-code-display" class="fw-bold font-monospace text-primary my-2"></h5>
                        <div class="row g-2 mb-3">
                            <div class="col-6 text-start">
                                <span class="text-secondary small d-block">SIZE</span>
                                <strong id="scanned-size-display" class="text-dark fs-5 font-monospace"></strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-secondary small d-block">SERIAL NO</span>
                                <strong id="scanned-serial-display" class="text-dark fs-5 font-monospace"></strong>
                            </div>
                        </div>

                        <!-- Touch Buttons Pass/Fail -->
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6">
                                <button type="button" id="pass-btn" class="btn btn-success btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm">
                                    <i class="fa-solid fa-circle-check me-1"></i> PASS
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" id="fail-btn" class="btn btn-danger btn-lg w-100 py-3 rounded-pill fw-bold shadow-sm">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> FAIL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live stats during scan session -->
                <div class="d-flex justify-content-between text-secondary small px-2 mt-2">
                    <span>Scanned pieces: <strong id="pieces-count" class="text-dark">0</strong></span>
                    <span>Elapsed: <strong id="elapsed-timer" class="text-dark">00:00</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load camera scanner dependency -->
<script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectionView = document.getElementById('selection-view');
    const scannerView = document.getElementById('scanner-view');
    const stageSelect = document.getElementById('stage-select');
    const startWorkBtn = document.getElementById('start-work-btn');
    const completeBtn = document.getElementById('complete-btn');
    const activeStageLabel = document.getElementById('active-stage-label');
    const scanResultCard = document.getElementById('scan-result-card');
    const codeDisplay = document.getElementById('scanned-code-display');
    const sizeDisplay = document.getElementById('scanned-size-display');
    const serialDisplay = document.getElementById('scanned-serial-display');
    const passBtn = document.getElementById('pass-btn');
    const failBtn = document.getElementById('fail-btn');
    const piecesCountEl = document.getElementById('pieces-count');
    const timerEl = document.getElementById('elapsed-timer');

    // Mode Toggle Elements
    const toggleModeBtn = document.getElementById('toggle-mode-btn');
    const manualContainer = document.getElementById('manual-input-container');
    const scannerContainer = document.getElementById('scanner-container');
    const manualCodeInput = document.getElementById('manual-code-input');
    const manualSubmitBtn = document.getElementById('manual-submit-btn');

    let html5QrCode = null;
    let scanCount = 0;
    let sessionStartTime = null;
    let pieceStartTime = null;
    let timerInterval = null;
    let currentScannedCode = null;

    // Start scanning session
    startWorkBtn.addEventListener('click', function() {
        const stage = stageSelect.value;
        if (!stage) {
            alert('Please select a production stage first.');
            return;
        }

        activeStageLabel.innerText = stage.replace('_', ' ');
        selectionView.style.display = 'none';
        scannerView.style.display = 'block';
        completeBtn.style.display = 'block';

        sessionStartTime = new Date();
        pieceStartTime = new Date();
        scanCount = 0;
        piecesCountEl.innerText = '0';
        scanResultCard.style.display = 'none';

        // Start timer
        clearInterval(timerInterval);
        timerInterval = setInterval(updateTimer, 1000);

        initScanner();
    });

    // Complete session
    completeBtn.addEventListener('click', function() {
        stopScanner();
        clearInterval(timerInterval);

        alert(`Session Completed! Total logged pieces: ${scanCount}. Returning to stage selection.`);

        scannerView.style.display = 'none';
        completeBtn.style.display = 'none';
        selectionView.style.display = 'block';
    });

    function initScanner() {
        // Reset manual UI visibility
        manualContainer.style.display = 'none';
        scannerContainer.style.display = 'block';
        toggleModeBtn.innerHTML = '<i class="fa-solid fa-keyboard me-1"></i> Switch to Manual Entry Mode';

        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess
        ).catch(err => {
            console.warn('Camera access denied or unavailable. Falling back to manual entry mode: ', err);
            switchToManualMode("Camera is not accessible or not supported on this browser/device. Manual input mode enabled.");
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode = null;
            }).catch(err => {
                console.error(err);
            });
        }
    }

    // Toggle camera / manual modes
    toggleModeBtn.addEventListener('click', function() {
        if (manualContainer.style.display === 'none') {
            switchToManualMode();
        } else {
            manualContainer.style.display = 'none';
            scannerContainer.style.display = 'block';
            toggleModeBtn.innerHTML = '<i class="fa-solid fa-keyboard me-1"></i> Switch to Manual Entry Mode';
            initScanner();
        }
    });

    function switchToManualMode(reason = null) {
        stopScanner();
        scannerContainer.style.display = 'none';
        manualContainer.style.display = 'block';
        toggleModeBtn.innerHTML = '<i class="fa-solid fa-camera me-1"></i> Switch to Camera Mode';

        if (reason) {
            const toast = document.createElement('div');
            toast.className = 'alert alert-warning text-center py-2 mb-2 small fw-bold';
            toast.innerText = reason;
            manualContainer.parentNode.insertBefore(toast, manualContainer);
            setTimeout(() => toast.remove(), 4000);
        }
        manualCodeInput.value = '';
        manualCodeInput.focus();
    }

    // Handle manual entry submit
    manualSubmitBtn.addEventListener('click', function() {
        const code = manualCodeInput.value.trim();
        if (!code) {
            alert('Please enter or scan a valid code.');
            return;
        }
        onScanSuccess(code);
    });

    manualCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            manualSubmitBtn.click();
        }
    });

    function onScanSuccess(decodedText) {
        // Pause camera scanning during pass/fail log interaction
        if (html5QrCode) {
            html5QrCode.pause(true);
        }

        currentScannedCode = decodedText;
        codeDisplay.innerText = decodedText;

        // Parse decoded text (format: BATCH_NO-SIZE-SERIAL)
        const parts = decodedText.split('-');
        if (parts.length >= 3) {
            const serial = parts.pop();
            const size = parts.pop();
            sizeDisplay.innerText = size;
            serialDisplay.innerText = '#' + parseInt(serial);
        } else {
            sizeDisplay.innerText = 'N/A';
            serialDisplay.innerText = 'N/A';
        }

        scanResultCard.style.display = 'block';
    }

    // Pass and Fail action triggers
    passBtn.addEventListener('click', function() {
        submitLog('pass');
    });

    failBtn.addEventListener('click', function() {
        submitLog('fail');
    });

    function submitLog(status) {
        if (!currentScannedCode) return;

        const durationSeconds = Math.round((new Date() - pieceStartTime) / 1000);
        const stage = stageSelect.value;

        // Prepare POST payload
        const formData = new FormData();
        formData.append('qr_code', currentScannedCode);
        formData.append('stage', stage);
        formData.append('status', status);
        formData.append('duration_seconds', durationSeconds);
        formData.append('csrf_token', "<?= \App\Core\Session::getCsrfToken() ?>");

        // Disable buttons during request
        passBtn.disabled = true;
        failBtn.disabled = true;

        fetch("<?= base_url('company/production/rfid-tracking/log') ?>", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                scanCount++;
                piecesCountEl.innerText = scanCount;
                
                // Show a brief alert
                const alertClass = status === 'pass' ? 'alert-success' : 'alert-danger';
                const toast = document.createElement('div');
                toast.className = `alert ${alertClass} text-center py-2 mb-2 font-monospace fw-bold`;
                toast.innerText = data.message;
                scanResultCard.parentNode.insertBefore(toast, scanResultCard);
                setTimeout(() => toast.remove(), 2500);
                
                // Reset card & input
                scanResultCard.style.display = 'none';
                currentScannedCode = null;
                manualCodeInput.value = '';
                pieceStartTime = new Date(); // Reset timer for next piece
                
                if (manualContainer.style.display !== 'none') {
                    manualCodeInput.focus();
                } else if (html5QrCode) {
                    html5QrCode.resume();
                }
            } else {
                alert('Logging Error: ' + data.message);
                if (html5QrCode && manualContainer.style.display === 'none') {
                    html5QrCode.resume();
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Connection failure. Verify internet connectivity.');
            if (html5QrCode && manualContainer.style.display === 'none') {
                html5QrCode.resume();
            }
        })
        .finally(() => {
            passBtn.disabled = false;
            failBtn.disabled = false;
        });
    }

    function updateTimer() {
        if (!sessionStartTime) return;
        const diff = Math.round((new Date() - sessionStartTime) / 1000);
        const mins = Math.floor(diff / 60);
        const secs = diff % 60;
        timerEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
});
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error | Wearable ERP</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.ico') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19 !important;
            color: #f8fafc !important;
            overflow: hidden !important;
        }

        /* Fixed Fullscreen Wrapper to cover sidebar and top header completely */
        .error-fullscreen-wrapper {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999999 !important;
            background-color: #0b0f19 !important;
            overflow-y: auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .bg-mesh-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .glow-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.45;
            animation: floatGlow 12s infinite alternate ease-in-out;
        }

        .blob-1 {
            width: 450px;
            height: 450px;
            top: -100px;
            left: -100px;
            background: radial-gradient(circle, #ef4444, #f59e0b);
        }

        .blob-2 {
            width: 500px;
            height: 500px;
            bottom: -150px;
            right: -100px;
            background: radial-gradient(circle, #8b5cf6, #ec4899);
            animation-delay: -6s;
        }

        @keyframes floatGlow {
            0% { transform: scale(0.9) translate(0, 0); }
            100% { transform: scale(1.15) translate(30px, 40px); }
        }

        .error-card-box {
            position: relative;
            z-index: 10;
            padding: 2rem 1rem;
            width: 100%;
            max-width: 560px;
        }

        .glass-card {
            background: rgba(19, 26, 41, 0.85);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            padding: 3.5rem 2.5rem;
            text-align: center;
        }

        .gradient-500 {
            font-size: 7.5rem;
            font-weight: 800;
            line-height: 0.85;
            background: linear-gradient(135deg, #f87171 0%, #fb923c 50%, #facc15 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.04em;
            margin-bottom: 1.25rem;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.35);
            transition: all 0.25s ease;
        }
        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(239, 68, 68, 0.5);
            color: #ffffff;
        }

        .btn-glass-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 50px;
            transition: all 0.25s ease;
        }
        .btn-glass-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <!-- Fullscreen Fixed Overlay Container (Covers Sidebar & Header) -->
    <div class="error-fullscreen-wrapper">
        
        <!-- Background Animated Mesh Blobs -->
        <div class="bg-mesh-container">
            <div class="glow-blob blob-1"></div>
            <div class="glow-blob blob-2"></div>
        </div>

        <!-- 500 Glass Card Box -->
        <div class="error-card-box">
            <div class="glass-card">
                <div class="gradient-500">500</div>
                <h3 class="fw-bold text-white mb-2" style="font-size: 1.75rem;">Internal Server Error</h3>
                <p class="text-secondary mb-4 fs-6" style="line-height: 1.6; color: #94a3b8 !important;">
                    <?= htmlspecialchars($message ?? 'An unexpected system error occurred. Our engineering team has been notified.') ?>
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= base_url('company/dashboard') ?>" class="btn btn-gradient-primary text-decoration-none">
                        <i class="fa-solid fa-rotate-left me-2"></i> Reload Portal
                    </a>
                    <button onclick="history.back()" class="btn btn-glass-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Go Back
                    </button>
                </div>

                <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-muted small" style="font-size: 12px; color: #64748b !important;">
                    Wearable ERP Enterprise | System Diagnostics & Recovery
                </div>
            </div>
        </div>

    </div>

</body>
</html>

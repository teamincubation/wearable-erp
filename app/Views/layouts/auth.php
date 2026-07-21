<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Wearable ERP' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card fade-in-up">
            <a href="<?= base_url() ?>" class="auth-brand">
                <i class="fa-solid fa-shirt"></i> Wearable ERP
            </a>

            <!-- System Flash Alerts -->
            <?php if ($error = \App\Core\Session::getFlash('error')): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success = \App\Core\Session::getFlash('success')): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    <div><?= $success ?></div>
                </div>
            <?php endif; ?>

            <?php if ($info = \App\Core\Session::getFlash('info')): ?>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <div><?= $info ?></div>
                </div>
            <?php endif; ?>

            <!-- Page Body Content -->
            <?= $content ?>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

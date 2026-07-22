<?php
$tenant = \App\Core\Session::get('current_tenant');
$logoUrl = ($tenant && !empty($tenant['logo'])) ? base_url($tenant['logo']) : null;
$faviconUrl = $logoUrl ?: base_url('assets/images/favicon.ico');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'RFID Floor Scanner | Wearable ERP' ?></title>
    <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom ERP Core CSS -->
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
        }
    </style>
</head>
<body>

    <!-- System Flash Alerts -->
    <div class="container mt-3" style="max-width: 480px;">
        <?php if ($error = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-danger d-flex align-items-center" style="border-radius: 12px;" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success d-flex align-items-center" style="border-radius: 12px;" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main View Content -->
    <?= $content ?>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

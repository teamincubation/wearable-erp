<div class="card shadow-lg border-0" style="border-radius: 24px; background: #ffffff;">
    <div class="card-body p-4 p-md-5">
        <!-- Tenant Company Branding Header -->
        <div class="text-center mb-4">
            <?php if (!empty($company['logo']) && file_exists(dirname(__DIR__, 2) . '/' . $company['logo'])): ?>
                <img src="<?= base_url($company['logo']) ?>" alt="<?= htmlspecialchars($company['name']) ?>" class="img-fluid mb-3" style="max-height: 65px; object-fit: contain;">
            <?php else: ?>
                <div class="app-icon-circle bg-primary bg-opacity-10 text-primary mb-3 mx-auto" style="width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-building fs-2 text-primary"></i>
                </div>
            <?php endif; ?>

            <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($company['name']) ?></h3>
            <span class="badge bg-primary-subtle text-primary font-monospace fw-bold px-3 py-1.5 rounded-pill" style="font-size: 11.5px;">
                <i class="fa-solid fa-link me-1"></i> https://erp.mywellgro.online/<?= htmlspecialchars($company['subdomain']) ?>/login
            </span>
            <p class="text-secondary small mt-2 mb-0">Enter your employee/admin credentials to access <?= htmlspecialchars($company['name']) ?> Wearable ERP.</p>
        </div>

        <?php if (!empty($flash_error)): ?>
            <div class="alert alert-danger text-center border-0 shadow-sm py-3 mb-4 rounded-3 small font-monospace">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($flash_error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_success)): ?>
            <div class="alert alert-success text-center border-0 shadow-sm py-3 mb-4 rounded-3 small font-monospace">
                <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($flash_success) ?>
            </div>
        <?php endif; ?>

        <!-- Form Post to Tenant Login Endpoint -->
        <form action="<?= base_url(htmlspecialchars($company['subdomain']) . '/login') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            
            <div class="mb-3 text-start">
                <label for="email" class="form-label small fw-bold text-secondary text-uppercase">Email / Mobile Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-envelope"></i></span>
                    <input type="text" class="form-control form-control-lg text-dark" id="email" name="email" placeholder="admin@<?= htmlspecialchars($company['subdomain']) ?>.mywellgro.online" required style="font-size: 14px;">
                </div>
            </div>

            <div class="mb-4 text-start">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small fw-bold text-secondary text-uppercase m-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control form-control-lg text-dark" id="password" name="password" placeholder="••••••••" required style="font-size: 14px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In to <?= htmlspecialchars($company['name']) ?>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <small class="text-secondary font-monospace" style="font-size: 11px;">
                <i class="fa-solid fa-lock me-1"></i> Protected by Wearable ERP Tenant Isolation
            </small>
        </div>
    </div>
</div>

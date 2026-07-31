<div class="card shadow-lg border-0" style="border-radius: 24px; background: #ffffff;">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="app-icon-circle bg-primary bg-opacity-10 text-primary mb-3 mx-auto" style="width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-layer-group fs-2 text-primary"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Wearable ERP</h3>
            <span class="badge bg-primary-subtle text-primary font-monospace fw-bold px-3 py-1.5 rounded-pill" style="font-size: 11px; letter-spacing: 0.5px;">
                <i class="fa-solid fa-shield-halved me-1"></i> SaaS Portal Access
            </span>
            <p class="text-secondary small mt-2 mb-0">Enter your credentials to log into Developer Portal or Tenant ERP.</p>
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

        <form action="<?= base_url('login') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            
            <div class="mb-3 text-start">
                <label for="email" class="form-label small fw-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Email / Username / Employee Code</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-user"></i></span>
                    <input type="text" class="form-control form-control-lg text-dark" id="email" name="email" placeholder="admin / user@company.com" required style="font-size: 14px;">
                </div>
            </div>

            <div class="mb-4 text-start">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small fw-bold text-secondary text-uppercase m-0" style="letter-spacing: 0.5px;">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control form-control-lg text-dark border-end-0" id="password" name="password" placeholder="••••••••" required style="font-size: 14px;">
                    <span class="input-group-text bg-white text-secondary cursor-pointer border-start-0" id="togglePassword" style="cursor: pointer;">
                        <i class="fa-regular fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In to Portal
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <small class="text-secondary font-monospace" style="font-size: 11px;">
                <i class="fa-solid fa-lock me-1"></i> Wearable ERP Unified Enterprise Portal
            </small>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

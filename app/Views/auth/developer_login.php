<div class="card shadow-lg border-0" style="border-radius: 24px; background: #0f172a; color: #ffffff;">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge bg-indigo-500 bg-opacity-20 text-indigo-400 border border-indigo-500 px-3 py-1.5 rounded-pill font-monospace fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">
                <i class="fa-solid fa-code me-1"></i> DEVELOPER PORTAL
            </span>
            <h3 class="fw-bold text-white mb-1"><i class="fa-solid fa-layer-group text-primary me-2"></i> SaaS Platform Admin</h3>
            <p class="text-secondary small">Access global tenant management, billing & feature flag controls.</p>
        </div>

        <?php if (!empty($flash_error)): ?>
            <div class="alert alert-danger text-center border-0 shadow-sm py-3 mb-4 rounded-3 font-monospace small" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($flash_error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_success)): ?>
            <div class="alert alert-success text-center border-0 shadow-sm py-3 mb-4 rounded-3 font-monospace small" style="background: rgba(16, 185, 129, 0.15); color: #6ee7b7;">
                <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars($flash_success) ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('developer/login') ?>" method="POST">
            <?= \App\Core\Session::csrfField() ?>
            
            <div class="mb-3">
                <label for="email" class="form-label small fw-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Developer Email / Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-user-gear"></i></span>
                    <input type="text" class="form-control form-control-lg bg-dark text-white border-secondary" id="email" name="email" placeholder="admin / dev@wearableerp.com" required style="font-size: 14px;">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Developer Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control form-control-lg bg-dark text-white border-secondary border-end-0" id="password" name="password" placeholder="••••••••" required style="font-size: 14px;">
                    <span class="input-group-text bg-dark border-secondary text-secondary cursor-pointer border-start-0" id="togglePassword" style="cursor: pointer;">
                        <i class="fa-regular fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Log In to Developer Portal
            </button>
        </form>

        <div class="mt-4 pt-3 border-top border-secondary opacity-50 text-center">
            <small class="text-secondary font-monospace" style="font-size: 11px;">
                <i class="fa-solid fa-shield-halved me-1"></i> Developer Portal Isolation System v2.5
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

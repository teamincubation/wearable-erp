<?php
$currentTenant = \App\Core\Session::get('current_tenant');
?>

<?php if (!$currentTenant): ?>
    <div class="mb-4 text-center">
        <h4 class="fw-bold mb-1">Enter Company Key</h4>
        <p class="text-secondary" style="font-size: 14px;">Enter your 6-digit Company Identification Key (CIK) to access your portal</p>
    </div>

    <form action="<?= base_url('login/cik') ?>" method="POST">
        <?= \App\Core\Session::csrfField() ?>

        <div class="mb-4">
            <label for="cik" class="form-label fw-semibold">Company Identification Key (CIK)</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-key text-secondary"></i></span>
                <input type="text" name="cik" id="cik" class="form-control text-center font-monospace fw-bold" style="font-size: 18px; letter-spacing: 4px;" placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="off" autofocus>
            </div>
            <div class="form-text text-secondary small">Your 6-digit company CIK was generated during onboarding.</div>
        </div>

        <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
            <i class="fa-solid fa-circle-check me-2"></i> Verify CIK
        </button>
    </form>
<?php else: ?>
    <div class="mb-4 text-center">
        <h4 class="fw-bold mb-1">
            <?php if ($currentTenant['subdomain'] === 'erp'): ?>
                Developer Portal
            <?php else: ?>
                Login | <?= htmlspecialchars($currentTenant['name']) ?>
            <?php endif; ?>
        </h4>
        <p class="text-secondary" style="font-size: 14px;">Please enter your credentials below</p>
    </div>

    <form action="<?= base_url('login') ?>" method="POST">
        <?= \App\Core\Session::csrfField() ?>

        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email Address / Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-regular fa-envelope text-secondary"></i></span>
                <input type="text" name="email" id="email" class="form-control" placeholder="name@company.com or Username" required 
                       value="<?php 
                            if ($currentTenant['subdomain'] === 'erp') echo 'admin@mywellgro.online';
                            elseif ($currentTenant['subdomain'] === 'tocco') echo 'adnan@toccoexports.com';
                       ?>">
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="password" class="form-label fw-semibold m-0">Password</label>
                <a href="<?= base_url('forgot-password') ?>" class="text-primary text-decoration-none" style="font-size: 13px;">Forgot Password?</a>
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-secondary"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required
                       value="<?php 
                            if ($currentTenant['subdomain'] === 'erp') echo 'Admin@1234';
                            elseif ($currentTenant['subdomain'] === 'tocco') echo 'Tocco@1234';
                       ?>">
                <button type="button" class="btn btn-light border" id="togglePasswordBtn" style="cursor: pointer;" title="Toggle Password Visibility">
                    <i class="fa-regular fa-eye text-secondary" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Log In
        </button>
    </form>

    <div class="text-center mt-2">
        <a href="<?= base_url('login/change-cik') ?>" class="text-secondary small text-decoration-none"><i class="fa-solid fa-arrows-rotate me-1"></i> Switch Company / Change Key</a>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (passwordInput && toggleBtn && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'fa-regular fa-eye-slash text-primary' : 'fa-regular fa-eye text-secondary';
        });
    }
});
</script>

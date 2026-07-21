<div class="mb-4 text-center">
    <h4 class="fw-bold mb-1">
        <?php if (!empty($tenant) && $tenant !== 'erp'): ?>
            Login | <?= htmlspecialchars(ucfirst($tenant)) ?> ERP
        <?php elseif (!empty($tenant) && $tenant === 'erp'): ?>
            Developer Portal
        <?php else: ?>
            Sign In to Platform
        <?php endif; ?>
    </h4>
    <p class="text-secondary" style="font-size: 14px;">Please enter your credentials below</p>
</div>

<form action="<?= base_url('login') ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fa-regular fa-envelope text-secondary"></i></span>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required 
                   value="<?php 
                        if (!empty($tenant) && $tenant === 'erp') echo 'admin@mywellgro.online';
                        elseif (!empty($tenant) && $tenant === 'tocco') echo 'adnan@toccoexports.com';
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
                        if (!empty($tenant) && $tenant === 'erp') echo 'Admin@1234';
                        elseif (!empty($tenant) && $tenant === 'tocco') echo 'Tocco@1234';
                   ?>">
            <button type="button" class="btn btn-light border" id="togglePasswordBtn" style="cursor: pointer;" title="Toggle Password Visibility">
                <i class="fa-regular fa-eye text-secondary" id="togglePasswordIcon"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Log In
    </button>

    <div class="text-center pt-2">
        <a href="<?= base_url() ?>" class="text-secondary text-decoration-none" style="font-size: 14px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Main Portal
        </a>
    </div>
</form>

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

<div class="mt-4 p-3 bg-light rounded text-secondary" style="font-size: 12px; border: 1px dashed var(--border-color);">
    <strong><i class="fa-solid fa-circle-info text-primary"></i> Review & Test Credentials:</strong>
    <ul class="m-0 ps-3 mt-1">
        <li><strong>Developer Admin:</strong> admin@mywellgro.online / Admin@1234</li>
        <li><strong>TOCCO Exports:</strong> adnan@toccoexports.com / Tocco@1234</li>
    </ul>
</div>

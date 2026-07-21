<div class="mb-4 text-center">
    <h4 class="fw-bold mb-1"><i class="fa-solid fa-key text-warning me-2"></i> 2FA Verification</h4>
    <p class="text-secondary" style="font-size: 14px;">Enter the 6-digit authentication code</p>
</div>

<form action="<?= base_url('two-factor') ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>

    <div class="mb-4 text-center">
        <input type="text" name="code" id="code" class="form-control text-center fs-2 fw-bold tracking-widest" 
               placeholder="000000" maxlength="6" pattern="[0-8]*" inputmode="numeric" required autofocus
               style="letter-spacing: 8px;">
        <small class="text-muted d-block mt-2">Enter code <strong>123456</strong> or <strong>654321</strong> for testing.</small>
    </div>

    <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
        <i class="fa-solid fa-shield-check me-2"></i> Verify Code
    </button>

    <div class="text-center">
        <a href="<?= base_url('logout') ?>" class="text-danger text-decoration-none" style="font-size: 14px;">
            Cancel & Logout
        </a>
    </div>
</form>

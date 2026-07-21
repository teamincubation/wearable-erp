<div class="mb-4 text-center">
    <h4 class="fw-bold mb-1">Create New Password</h4>
    <p class="text-secondary" style="font-size: 14px;">Enter your new secure password below</p>
</div>

<form action="<?= base_url('reset-password') ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">New Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-secondary"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="Min 8 characters" required>
        </div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-secondary"></i></span>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
    </div>

    <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
        <i class="fa-solid fa-circle-check me-2"></i> Save and Log In
    </button>
</form>

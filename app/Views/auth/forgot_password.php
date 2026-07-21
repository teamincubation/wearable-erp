<div class="mb-4 text-center">
    <h4 class="fw-bold mb-1">Recover Password</h4>
    <p class="text-secondary" style="font-size: 14px;">We will send reset instructions to your inbox</p>
</div>

<form action="<?= base_url('forgot-password') ?>" method="POST">
    <?= \App\Core\Session::csrfField() ?>

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fa-regular fa-envelope text-secondary"></i></span>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required>
        </div>
    </div>

    <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
        <i class="fa-solid fa-paper-plane me-2"></i> Send Recovery Email
    </button>

    <div class="text-center pt-2">
        <a href="<?= base_url('login') ?>" class="text-secondary text-decoration-none" style="font-size: 14px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
        </a>
    </div>
</form>

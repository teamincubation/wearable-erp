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
            <button type="button" class="btn btn-light border" id="togglePasswordBtn1" style="cursor: pointer;">
                <i class="fa-regular fa-eye text-secondary" id="toggleIcon1"></i>
            </button>
        </div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-secondary"></i></span>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
            <button type="button" class="btn btn-light border" id="togglePasswordBtn2" style="cursor: pointer;">
                <i class="fa-regular fa-eye text-secondary" id="toggleIcon2"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-pepp-primary w-100 py-2.5 mb-3">
        <i class="fa-solid fa-circle-check me-2"></i> Save and Log In
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupToggle(inputId, btnId, iconId) {
        const input = document.getElementById(inputId);
        const btn = document.getElementById(btnId);
        const icon = document.getElementById(iconId);
        if (input && btn && icon) {
            btn.addEventListener('click', function() {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'fa-regular fa-eye-slash text-primary' : 'fa-regular fa-eye text-secondary';
            });
        }
    }
    setupToggle('password', 'togglePasswordBtn1', 'toggleIcon1');
    setupToggle('confirm_password', 'togglePasswordBtn2', 'toggleIcon2');
});
</script>

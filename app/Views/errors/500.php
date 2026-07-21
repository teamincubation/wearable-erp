<div class="text-center py-5">
    <div class="mb-4 text-danger fs-1"><i class="fa-solid fa-server"></i> 500</div>
    <h3 class="fw-bold">Server Error</h3>
    <p class="text-secondary"><?= htmlspecialchars($message ?? 'An unexpected error occurred. Please try again later.') ?></p>
    <a href="<?= base_url() ?>" class="btn btn-pepp-primary mt-3">
        <i class="fa-solid fa-rotate-left me-1"></i> Reload Application
    </a>
</div>

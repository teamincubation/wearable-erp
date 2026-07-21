<div class="text-center mb-4">
    <h2 class="fw-bold" style="color: var(--primary-color);">Garment SaaS ERP</h2>
    <p class="text-secondary">Enterprise Resource Planning designed for Apparel Manufacturers in India</p>
</div>

<div class="mb-4">
    <h5 class="fw-semibold text-secondary mb-3"><i class="fa-solid fa-industry text-primary"></i> Active Pilot Customers</h5>
    
    <div class="list-group mb-3">
        <?php if (!empty($companies)): ?>
            <?php foreach ($companies as $comp): ?>
                <a href="<?= base_url('login?tenant=' . $comp['subdomain']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3" style="border-radius: var(--border-radius-sm); margin-bottom: 8px;">
                    <div>
                        <strong class="text-primary"><?= htmlspecialchars($comp['name']) ?></strong>
                        <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($comp['city']) ?>, <?= htmlspecialchars($comp['state']) ?></div>
                    </div>
                    <span class="badge bg-primary rounded-pill">Subdomain: <?= htmlspecialchars($comp['subdomain']) ?> <i class="fa-solid fa-circle-chevron-right ms-1"></i></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning">No companies onboarded yet. Please log into the Developer Portal to create one.</div>
        <?php endif; ?>
    </div>
</div>

<div class="mb-4 pt-2 border-top">
    <h5 class="fw-semibold text-secondary mb-3"><i class="fa-solid fa-code text-info"></i> SaaS Owner / Developer</h5>
    <a href="<?= base_url('login?tenant=erp') ?>" class="btn w-100 p-3 text-white fw-bold d-flex justify-content-between align-items-center" style="background-color: #0f172a; border-radius: var(--border-radius-sm); box-shadow: var(--shadow-sm);">
        <span><i class="fa-solid fa-gears me-2 text-info"></i> Enter Developer Admin Portal</span>
        <i class="fa-solid fa-arrow-right-to-bracket"></i>
    </a>
</div>

<div class="mt-3 text-center">
    <small class="text-secondary">Demo Login Details are inside the documentation.</small>
</div>

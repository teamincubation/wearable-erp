<div class="mb-4">
    <h3 class="fw-bold">Platform Releases</h3>
    <p class="text-secondary m-0">Publish version updates and release logs to all tenant instances</p>
</div>

<div class="row">
    <!-- Version logs -->
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-code-fork text-primary me-2"></i> Deployment Log</h5>
            </div>
            <div class="pepp-card-body p-4">
                <?php if (!empty($versions)): ?>
                    <div class="position-relative border-start border-2 border-primary ps-4 ms-2">
                        <?php foreach ($versions as $v): ?>
                            <div class="mb-4 position-relative">
                                <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 6px;"></div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h5 class="fw-bold m-0 text-dark">Release v<?= htmlspecialchars($v['version']) ?></h5>
                                    <span class="badge bg-light text-secondary"><?= date('d-M-Y H:i', strtotime($v['released_at'])) ?></span>
                                </div>
                                <div class="p-3 bg-light rounded text-secondary" style="font-size: 14px;">
                                    <?= nl2br(htmlspecialchars($v['release_notes'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-secondary">No version deployment records available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Release form -->
    <div class="col-md-4">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> Deploy Release</h5>
            </div>
            <div class="pepp-card-body">
                <form action="<?= base_url('developer/versions/create') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Version Tag</label>
                        <input type="text" name="version" class="form-control" placeholder="e.g. 1.0.1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Release Changelog Notes</label>
                        <textarea name="release_notes" class="form-control" rows="6" placeholder="Describe fixes, modifications, and new features..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-pepp-primary w-100">
                        <i class="fa-solid fa-circle-check me-1"></i> Trigger Deployment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

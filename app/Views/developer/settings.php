<div class="mb-4">
    <h3 class="fw-bold">Platform Settings</h3>
    <p class="text-secondary m-0">Manage global SaaS configurations, mail servers, and systems variables</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-gears text-primary me-2"></i> Configuration Parameters</h5>
            </div>
            <div class="pepp-card-body">
                <form action="<?= base_url('developer/settings') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-envelope me-1"></i> SMTP Mail Server settings</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" placeholder="smtp.mailtrap.io" 
                                   value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.mailtrap.io') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">SMTP Port</label>
                            <input type="text" name="smtp_port" class="form-control" placeholder="2525" 
                                   value="<?= htmlspecialchars($settings['smtp_port'] ?? '2525') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Encryption</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Username</label>
                            <input type="text" name="smtp_user" class="form-control" placeholder="smtp-username" 
                                   value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Password</label>
                            <input type="password" name="smtp_pass" class="form-control" placeholder="••••••••" 
                                   value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-floppy-disk me-1"></i> Automatic Backups</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Backup Frequency</label>
                            <select name="backup_frequency" class="form-select">
                                <option value="daily" <?= ($settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= ($settings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= ($settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Backup Storage Destination</label>
                            <select name="backup_storage" class="form-select">
                                <option value="local" <?= ($settings['backup_storage'] ?? 'local') === 'local' ? 'selected' : '' ?>>Local Storage (storage/backups)</option>
                                <option value="s3" <?= ($settings['backup_storage'] ?? '') === 's3' ? 'selected' : '' ?>>AWS S3 Bucket</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-lock me-1"></i> Security Parameters</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Session Lifetime (Seconds)</label>
                            <input type="number" name="session_lifetime" class="form-control" 
                                   value="<?= htmlspecialchars($settings['session_lifetime'] ?? '7200') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Security Level</label>
                            <select name="security_level" class="form-select">
                                <option value="high" <?= ($settings['security_level'] ?? 'high') === 'high' ? 'selected' : '' ?>>High (All MFA & CSRF Enforced)</option>
                                <option value="medium" <?= ($settings['security_level'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-brands fa-whatsapp me-1"></i> Developer Contact Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Developer WhatsApp Number <span class="text-danger">*</span></label>
                            <input type="text" name="developer_whatsapp" class="form-control" placeholder="e.g. 919876543210 (include country code)" 
                                   value="<?= htmlspecialchars($settings['developer_whatsapp'] ?? '919876543210') ?>" required>
                            <span class="text-muted small">This number will be used by tenants to contact for subscription renewals.</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pepp-primary">
                        <i class="fa-solid fa-circle-check me-1"></i> Save Platform Configurations
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="pepp-card bg-light border-dashed">
            <div class="pepp-card-body">
                <h6 class="fw-bold"><i class="fa-solid fa-server text-secondary me-2"></i> Environment Details</h6>
                <hr>
                <div style="font-size: 14px;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">PHP Version:</span>
                        <strong><?= phpversion() ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Web Server:</span>
                        <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'PHP Development Server') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">OS Version:</span>
                        <strong>Windows (Local)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Database:</span>
                        <strong>MySQL 8.x (PDO)</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="pepp-card">
            <div class="pepp-card-header d-flex justify-content-between align-items-center">
                <h5 class="pepp-card-title m-0"><i class="fa-solid fa-key text-primary me-2"></i> Tenant Developer Backdoor Login Credentials</h5>
                <form action="<?= base_url('developer/settings/generate-credentials') ?>" method="POST" class="m-0">
                    <?= \App\Core\Session::csrfField() ?>
                    <button type="submit" class="btn btn-sm btn-pepp-primary">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate Missing Credentials
                    </button>
                </form>
            </div>
            <div class="pepp-card-body">
                <p class="text-secondary small mb-3">Below is the auto-generated backdoor credentials list for each ERP tenant. Developer can use these usernames and passwords to bypass regular login authentication and view draft-labeled items. No activities are logged during these developer sessions.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ERP Tenant Name</th>
                                <th>Subdomain</th>
                                <th>Backdoor Username</th>
                                <th>Backdoor Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $comp): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($comp['name']) ?></strong></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($comp['subdomain']) ?></span></td>
                                        <td><code><?= htmlspecialchars($comp['dev_username'] ?? 'Not Generated') ?></code></td>
                                        <td>
                                            <?php if (!empty($comp['dev_password'])): ?>
                                                <div class="input-group input-group-sm" style="max-width: 180px;">
                                                    <input type="password" class="form-control border-end-0 bg-light" value="<?= htmlspecialchars($comp['dev_password']) ?>" readonly id="pass_<?= $comp['id'] ?>">
                                                    <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePass(<?= $comp['id'] ?>)">
                                                        <i class="fa-solid fa-eye" id="eye_<?= $comp['id'] ?>"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-danger small">Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($comp['dev_username'])): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyCredentials('<?= htmlspecialchars($comp['dev_username']) ?>', '<?= htmlspecialchars($comp['dev_password']) ?>')">
                                                    <i class="fa-solid fa-copy me-1"></i> Copy
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">No ERP tenants found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(id) {
    var input = document.getElementById('pass_' + id);
    var eye = document.getElementById('eye_' + id);
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
function copyCredentials(user, pass) {
    var text = "Username: " + user + "\nPassword: " + pass;
    navigator.clipboard.writeText(text).then(function() {
        alert("Credentials copied to clipboard!");
    });
}
</script>

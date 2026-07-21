<div class="mb-4">
    <h3 class="fw-bold">ERP settings</h3>
    <p class="text-secondary m-0">Manage company details, billing parameters, and security protocols</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="pepp-card">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-sliders text-primary me-2"></i> Company Profile & Preferences</h5>
            </div>
            <div class="pepp-card-body">
                <form action="<?= base_url('company/settings') ?>" method="POST">
                    <?= \App\Core\Session::csrfField() ?>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-id-card me-1"></i> General Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Registered Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GSTIN (Tax ID)</label>
                            <input type="text" name="gstin" class="form-control" value="<?= htmlspecialchars($company['gstin'] ?? '') ?>" placeholder="e.g. 33AAAAT1234A1Z1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Corporate Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Factory / Office Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-globe me-1"></i> Regional Preferences</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Standard Timezone</label>
                            <select name="timezone" class="form-select">
                                <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' ?>>India (IST - Asia/Kolkata)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>Coordinated Universal Time (UTC)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Base Currency Code</label>
                            <select name="currency" class="form-select">
                                <option value="INR" <?= ($settings['currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>Indian Rupee (₹ - INR)</option>
                                <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>US Dollar ($ - USD)</option>
                                <option value="EUR" <?= ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>Euro (€ - EUR)</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-shield-halved me-1"></i> Security Enforcements</h6>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="mfa_required" id="mfa_required" class="form-check-input" value="1" 
                                <?= ($settings['mfa_required'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="mfa_required">Require Two-Factor Authentication (2FA) for all users</label>
                            <div class="form-text text-secondary" style="font-size: 12px;">Forces users to type a 6-digit confirmation pin upon signing in.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pepp-primary">
                        <i class="fa-solid fa-circle-check me-1"></i> Save Company Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar context cards -->
    <div class="col-md-4">
        <div class="pepp-card mb-4">
            <div class="pepp-card-header">
                <h5 class="pepp-card-title"><i class="fa-solid fa-file-contract text-secondary me-2"></i> Subscription Summary</h5>
            </div>
            <div class="pepp-card-body" style="font-size: 14px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Billing Tier:</span>
                    <strong>Garment Lifetime</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Max Users:</span>
                    <strong>9,999 (Unlimited)</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Max Branches:</span>
                    <strong>99 (Unlimited)</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Expiry Date:</span>
                    <strong>21-Jul-2036</strong>
                </div>
            </div>
        </div>

        <div class="pepp-card bg-light border-dashed">
            <div class="pepp-card-body" style="font-size: 13.5px;">
                <h6 class="fw-bold"><i class="fa-solid fa-industry"></i> TOCCO Exports Pilot</h6>
                <p class="text-secondary mt-2">
                    This instance is optimized specifically for apparel manufacturing exports, with customized workflows for Tiruppur, India.
                </p>
            </div>
        </div>
    </div>
</div>

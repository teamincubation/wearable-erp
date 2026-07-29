-- Wearable ERP SaaS Database Schema
-- Optimized for Garment / Apparel Manufacturing Industry
-- Supports Single Database Multi-Tenant Architecture

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `system_versions`;
DROP TABLE IF EXISTS `license_keys`;
DROP TABLE IF EXISTS `feature_flags`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `companies`;
DROP TABLE IF EXISTS `subscription_plans`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. SUBSCRIPTION PLANS TABLE
CREATE TABLE `subscription_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `billing_cycle` ENUM('trial', 'monthly', 'quarterly', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly',
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `max_users` INT NOT NULL DEFAULT 5,
  `max_branches` INT NOT NULL DEFAULT 1,
  `max_storage_mb` INT NOT NULL DEFAULT 1024,
  `api_access` TINYINT(1) NOT NULL DEFAULT 0,
  `features_json` JSON DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_plan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. COMPANIES (TENANTS) TABLE
CREATE TABLE `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `subdomain` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'India',
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `gstin` VARCHAR(15) DEFAULT NULL,
  `tc_agreement` TEXT DEFAULT NULL,
  `payment_slip` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `subscription_plan_id` INT DEFAULT NULL,
  `subscription_status` ENUM('trial', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'trial',
  `subscription_expires_at` DATETIME DEFAULT NULL,

  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_company_subscription_plan` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL,
  INDEX `idx_company_subdomain` (`subdomain`),
  INDEX `idx_company_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ROLES TABLE (Allows system-wide/global roles and tenant-specific roles)
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL represents system/global developer roles
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0, -- Prevents accidental deletion of admin roles
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_role_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_role_company` (`company_id`),
  UNIQUE KEY `uq_role_company_name` (`company_id`, `name`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. USERS TABLE
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL for developer portal admin users
  `role_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `employee_code` VARCHAR(100) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `email_verification_token` VARCHAR(100) DEFAULT NULL,
  `two_factor_secret` VARCHAR(100) DEFAULT NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_user_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `uq_company_employee_code` UNIQUE (`company_id`, `employee_code`),
  INDEX `idx_user_company` (`company_id`),
  INDEX `idx_user_email` (`email`),
  INDEX `idx_user_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PERMISSIONS TABLE
CREATE TABLE `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `module` VARCHAR(100) NOT NULL, -- Grouping e.g., 'crm', 'inventory', 'purchase', 'hr', 'production'
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ROLE PERMISSIONS PIVOT TABLE
CREATE TABLE `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. FEATURE FLAGS TABLE (Controls specific features per tenant)
CREATE TABLE `feature_flags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `feature_key` VARCHAR(100) NOT NULL,
  `status` ENUM('enabled', 'disabled', 'trial', 'beta', 'enterprise', 'premium', 'coming_soon') NOT NULL DEFAULT 'enabled',
  `release_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `label` ENUM('draft', 'beta', 'new', 'no_label') NOT NULL DEFAULT 'no_label',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_feature_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_company_feature` (`company_id`, `feature_key`, `deleted_at`),
  INDEX `idx_feature_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. LICENSE KEYS TABLE (For SaaS Licensing & Verification)
CREATE TABLE `license_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `license_key` VARCHAR(255) NOT NULL UNIQUE,
  `status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_license_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_license_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. AUDIT LOGS / ACTIVITY LOGS TABLE
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL for developer portal audits
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL, -- e.g., 'login', 'create', 'update', 'delete', 'export'
  `model_type` VARCHAR(100) DEFAULT NULL, -- e.g., 'User', 'Company', 'Role'
  `model_id` INT DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_audit_company` (`company_id`),
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. SYSTEM SETTINGS TABLE (Configurations per company/globally)
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT DEFAULT NULL, -- NULL represents system-wide settings
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uq_company_setting` (`company_id`, `setting_key`, `deleted_at`),
  INDEX `idx_setting_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. SYSTEM VERSIONS TABLE (Tracks ERP updates and deployments)
CREATE TABLE `system_versions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version` VARCHAR(50) NOT NULL UNIQUE,
  `release_notes` TEXT DEFAULT NULL,
  `status` ENUM('active', 'deprecated', 'beta') NOT NULL DEFAULT 'active',
  `released_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. SECURE USER SESSIONS TABLE
CREATE TABLE `user_sessions` (
  `id` VARCHAR(128) PRIMARY KEY,
  `user_id` INT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `last_activity` INT NOT NULL,
  `payload` TEXT NOT NULL,
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_session_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- SEED DATA
-- ==========================================================

-- A. SEED SUBSCRIPTION PLANS
INSERT INTO `subscription_plans` (`id`, `name`, `code`, `billing_cycle`, `price`, `max_users`, `max_branches`, `max_storage_mb`, `api_access`, `features_json`, `status`) VALUES
(1, 'Free Trial', 'trial', 'trial', 0.00, 3, 1, 512, 0, '{"inventory": true, "purchase": true, "hr": false, "payroll": false, "crm": true}', 'active'),
(2, 'Apparel Growth (Monthly)', 'growth_monthly', 'monthly', 4999.00, 15, 3, 5120, 0, '{"inventory": true, "purchase": true, "production": true, "hr": true, "payroll": true, "crm": true, "barcode": true}', 'active'),
(3, 'Enterprise Yearly', 'enterprise_yearly', 'yearly', 49999.00, 100, 10, 51200, 1, '{"inventory": true, "purchase": true, "production": true, "hr": true, "payroll": true, "crm": true, "barcode": true, "rfid": true, "ai_reports": true, "customer_portal": true, "vendor_portal": true}', 'active'),
(4, 'Garment Lifetime', 'lifetime', 'lifetime', 199999.00, 9999, 99, 1048576, 1, '{"inventory": true, "purchase": true, "production": true, "hr": true, "payroll": true, "crm": true, "barcode": true, "rfid": true, "ai_reports": true, "customer_portal": true, "vendor_portal": true, "mobile_app": true}', 'active');

-- B. SEED GLOBAL SYSTEM/DEVELOPER ROLES
INSERT INTO `roles` (`id`, `company_id`, `name`, `description`, `is_system`) VALUES
(1, NULL, 'Super Admin', 'Developer / System Owner with full SaaS control privileges', 1);

-- C. SEED GLOBAL DEVELOPER USER (Passes bcrypt password 'Admin@1234')
-- Hashed value: $2y$10$1XzQkRs/Ube6HQkM3Ffj5.JCuWwna2JP3PyNiVB3zItPzwVs8k0vW
INSERT INTO `users` (`id`, `company_id`, `role_id`, `name`, `email`, `password_hash`, `email_verified_at`, `status`) VALUES
(1, NULL, 1, 'Wearable Dev Admin', 'admin@mywellgro.online', '$2y$10$1XzQkRs/Ube6HQkM3Ffj5.JCuWwna2JP3PyNiVB3zItPzwVs8k0vW', NOW(), 'active');

-- D. SEED MODULE PERMISSIONS
INSERT INTO `permissions` (`id`, `name`, `description`, `module`) VALUES
-- Developer Portal Permissions
(1, 'developer.dashboard', 'Access developer overview dashboard', 'developer'),
(2, 'developer.companies', 'View and manage onboarding companies', 'developer'),
(3, 'developer.subscriptions', 'Configure billing plans and active pricing', 'developer'),
(4, 'developer.features', 'Manage platform feature flags globally', 'developer'),
(5, 'developer.versions', 'Manage code release versioning and updates', 'developer'),
(6, 'developer.logs', 'View global security audits and error logs', 'developer'),
(7, 'developer.settings', 'Manage global system configurations', 'developer'),

-- Company ERP Tenant Permissions
(8, 'company.dashboard', 'View company operations metrics dashboard', 'tenant'),
(9, 'company.settings', 'Manage company general parameters & metadata', 'tenant'),
(10, 'company.users.view', 'View employee user logs and settings', 'tenant'),
(11, 'company.users.create', 'Add new company employee user records', 'tenant'),
(12, 'company.users.edit', 'Modify existing user profile and access levels', 'tenant'),
(13, 'company.users.delete', 'Remove/Deactivate company employee records', 'tenant'),
(14, 'company.roles.view', 'View roles and their permission lists', 'tenant'),
(15, 'company.roles.manage', 'Add, Edit, or Delete custom company roles', 'tenant'),
(16, 'company.logs', 'View company internal audit history trail', 'tenant');

-- E. MAP SUPER ADMIN TO ALL PERMISSIONS
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

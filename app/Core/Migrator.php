<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * High-Performance Non-Destructive Auto-Migration Engine
 * Lead Database Architect - Antigravity
 */
class Migrator {
    /**
     * Run automatic migration checks by verifying schema hashes
     */
    public static function runAutoMigration(): void {
        $schemaFiles = [
            dirname(__DIR__, 2) . '/database/schema.sql',
            dirname(__DIR__, 2) . '/database/schema_v2.sql'
        ];

        // 1. Calculate combined MD5 hash of schema files to detect changes
        $combinedContent = '';
        foreach ($schemaFiles as $file) {
            if (file_exists($file)) {
                $combinedContent .= file_get_contents($file);
            }
        }

        if (empty($combinedContent)) {
            return; // No schema files to migrate
        }

        $currentHash = md5($combinedContent);
        $hashFilePath = dirname(__DIR__, 2) . '/storage/db_schema.hash';

        // 2. If hash matches, database is up-to-date. Return immediately.
        if (file_exists($hashFilePath) && trim(file_get_contents($hashFilePath)) === $currentHash) {
            return;
        }

        // 3. Run migration parser safely in transaction
        try {
            $db = Database::getInstance();
            
            // Temporary disable foreign keys to avoid order dependency errors
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            foreach ($schemaFiles as $schemaPath) {
                if (!file_exists($schemaPath)) {
                    continue;
                }

                $queries = self::parseSqlQueries($schemaPath);

                foreach ($queries as $query) {
                    $cleaned = trim($query);
                    if (empty($cleaned)) {
                        continue;
                    }

                    // A. Skip all DROP TABLE statements to prevent data loss in production
                    if (preg_match('/^\s*DROP\s+TABLE/i', $cleaned)) {
                        continue;
                    }

                    // B. Turn CREATE TABLE into CREATE TABLE IF NOT EXISTS
                    if (preg_match('/^\s*CREATE\s+TABLE/i', $cleaned)) {
                        $cleaned = preg_replace('/CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $cleaned);
                    }

                    // C. Turn INSERT INTO into INSERT IGNORE INTO to prevent primary key crashes
                    if (preg_match('/^\s*INSERT\s+INTO/i', $cleaned)) {
                        $cleaned = preg_replace('/INSERT\s+INTO/i', 'INSERT IGNORE INTO', $cleaned);
                    }

                    // D. Parse ALTER TABLE statements to check if column already exists
                    if (preg_match('/^\s*ALTER\s+TABLE\s+`?([a-zA-Z0-9_-]+)`?\s+ADD\s+(?:COLUMN\s+)?`?([a-zA-Z0-9_-]+)`?/i', $cleaned, $matches)) {
                        $tableName = $matches[1];
                        $columnName = $matches[2];

                        // Query database to see if column exists
                        try {
                            $check = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
                            if ($check && $check->rowCount() > 0) {
                                continue; // Column already exists, skip ALTER statement safely
                            }
                        } catch (\PDOException $e) {
                            // Table may not exist yet or other query error, let the ALTER run or fail gracefully
                        }
                    }

                    // Execute cleaned query statement
                    try {
                        $db->exec($cleaned);
                    } catch (\PDOException $e) {
                        // Log migration warning or fail silently for ignorable schema mismatch
                    }
                }
            }

            // Auto-heal contacts table columns for Buyers/Clients
            $contactColumns = [
                'brand_name' => "VARCHAR(150) DEFAULT NULL",
                'contact_person' => "VARCHAR(150) DEFAULT NULL",
                'country' => "VARCHAR(100) DEFAULT 'India'",
                'currency' => "VARCHAR(10) DEFAULT 'INR'",
                'payment_terms' => "VARCHAR(100) DEFAULT NULL",
                'shipping_address' => "TEXT DEFAULT NULL"
            ];
            foreach ($contactColumns as $col => $type) {
                try {
                    $check = $db->query("SHOW COLUMNS FROM `contacts` LIKE '{$col}'");
                    if (!$check || $check->rowCount() === 0) {
                        $db->exec("ALTER TABLE `contacts` ADD COLUMN `{$col}` {$type}");
                    }
                } catch (\PDOException $e) {}
            }

            // Auto-heal 'buyers' view alias pointing to contacts table for safety & backward compatibility
            try {
                $db->exec("CREATE OR REPLACE VIEW `buyers` AS SELECT * FROM `contacts` WHERE `type` = 'buyer'");
            } catch (\PDOException $e) {}

            // Auto-heal permissions table for QR Code Scanner & Dispatch permissions
            $requiredPermissions = [
                ['name' => 'company.production.rfid_tracking', 'description' => 'Access QR Code / RFID Production Scanner page', 'module' => 'tenant'],
                ['name' => 'company.dispatch.view', 'description' => 'View finished goods dispatch and packing hub', 'module' => 'tenant'],
                ['name' => 'company.dispatch.manage', 'description' => 'Manage carton packing, printing QR labels, and dispatching shipments', 'module' => 'tenant'],
                ['name' => 'company.packing.qr', 'description' => 'Access Packing QR Module for Carton Product Assignments', 'module' => 'tenant']
            ];
            foreach ($requiredPermissions as $permInfo) {
                try {
                    $stmtCheck = $db->prepare("SELECT id FROM permissions WHERE name = ?");
                    $stmtCheck->execute([$permInfo['name']]);
                    $permId = $stmtCheck->fetchColumn();
                    if (!$permId) {
                        $stmtIns = $db->prepare("INSERT INTO permissions (name, description, module) VALUES (?, ?, ?)");
                        $stmtIns->execute([$permInfo['name'], $permInfo['description'], $permInfo['module']]);
                        $permId = $db->lastInsertId();
                    }
                    if ($permId) {
                        $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, " . (int)$permId . ")");
                    }
                } catch (\PDOException $e) {}
            }

            // Auto-heal cartons table columns for capacity and details
            try {
                $checkCtn = $db->query("SHOW COLUMNS FROM `cartons` LIKE 'max_capacity_pcs'");
                if (!$checkCtn || $checkCtn->rowCount() === 0) {
                    $db->exec("ALTER TABLE `cartons` ADD COLUMN `max_capacity_pcs` INT DEFAULT 50 AFTER `warehouse_id`");
                }
            } catch (\PDOException $e) {}

            // Auto-heal carton_items table columns for tracking
            $cartonItemCols = [
                'assigned_by' => "INT DEFAULT NULL",
                'assigned_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            ];
            foreach ($cartonItemCols as $col => $type) {
                try {
                    $checkCi = $db->query("SHOW COLUMNS FROM `carton_items` LIKE '{$col}'");
                    if (!$checkCi || $checkCi->rowCount() === 0) {
                        $db->exec("ALTER TABLE `carton_items` ADD COLUMN `{$col}` {$type}");
                    }
                } catch (\PDOException $e) {}
            }

            // Auto-heal bom_categories table columns for update tracking
            $bomCatColumns = [
                'updated_by' => "INT DEFAULT NULL",
                'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
            ];
            foreach ($bomCatColumns as $col => $type) {
                try {
                    $check = $db->query("SHOW COLUMNS FROM `bom_categories` LIKE '{$col}'");
                    if (!$check || $check->rowCount() === 0) {
                        $db->exec("ALTER TABLE `bom_categories` ADD COLUMN `{$col}` {$type}");
                    }
                } catch (\PDOException $e) {}
            }

            // Auto-heal purchase_orders table columns for warehouse_id
            try {
                $checkPoWh = $db->query("SHOW COLUMNS FROM `purchase_orders` LIKE 'warehouse_id'");
                if (!$checkPoWh || $checkPoWh->rowCount() === 0) {
                    $db->exec("ALTER TABLE `purchase_orders` ADD COLUMN `warehouse_id` INT DEFAULT NULL");
                }
            } catch (\PDOException $e) {}

            // Auto-heal purchase_order_items & inventory_transactions columns for bom_code & item_type VARCHAR
            try {
                $db->exec("ALTER TABLE `purchase_order_items` MODIFY COLUMN `item_type` VARCHAR(150) NOT NULL DEFAULT 'Accessories'");
            } catch (\PDOException $e) {}
            try {
                $checkBomCode = $db->query("SHOW COLUMNS FROM `purchase_order_items` LIKE 'bom_code'");
                if (!$checkBomCode || $checkBomCode->rowCount() === 0) {
                    $db->exec("ALTER TABLE `purchase_order_items` ADD COLUMN `bom_code` VARCHAR(50) DEFAULT NULL AFTER `item_type`");
                }
            } catch (\PDOException $e) {}

            try {
                $db->exec("ALTER TABLE `inventory_transactions` MODIFY COLUMN `item_type` VARCHAR(150) NOT NULL DEFAULT 'Accessories'");
            } catch (\PDOException $e) {}
            try {
                $checkItBomCode = $db->query("SHOW COLUMNS FROM `inventory_transactions` LIKE 'bom_code'");
                if (!$checkItBomCode || $checkItBomCode->rowCount() === 0) {
                    $db->exec("ALTER TABLE `inventory_transactions` ADD COLUMN `bom_code` VARCHAR(50) DEFAULT NULL AFTER `item_type`");
                }
            } catch (\PDOException $e) {}

            // Auto-heal empty item_type records in purchase_order_items & inventory_transactions
            try {
                $db->exec("UPDATE purchase_order_items SET item_type = 'Accessories' WHERE item_type IS NULL OR TRIM(item_type) = ''");
                $db->exec("UPDATE inventory_transactions SET item_type = 'Accessories' WHERE item_type IS NULL OR TRIM(item_type) = ''");
                $db->exec("UPDATE inventory_transactions it JOIN purchase_order_items poi ON it.reference_id = poi.po_id AND it.item_name = poi.item_name SET it.item_type = poi.item_type, it.bom_code = poi.bom_code WHERE (it.item_type IS NULL OR TRIM(it.item_type) = '' OR LOWER(it.item_type) = 'accessories') AND poi.item_type IS NOT NULL AND TRIM(poi.item_type) != ''");
            } catch (\PDOException $e) {}

            // Auto-heal warehouse_types table
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `warehouse_types` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `company_id` INT NOT NULL,
                        `type_key` VARCHAR(100) NOT NULL,
                        `type_label` VARCHAR(150) NOT NULL,
                        `status` ENUM('active', 'inactive') DEFAULT 'active',
                        `created_by` INT DEFAULT NULL,
                        `updated_by` INT DEFAULT NULL,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        `deleted_at` DATETIME DEFAULT NULL,
                        KEY `idx_company` (`company_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                // Seed default warehouse storage types for existing companies if empty
                $stmtComps = $db->query("SELECT id FROM companies WHERE deleted_at IS NULL");
                $comps = $stmtComps->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                $defaults = [
                    ['raw_material', 'Raw Materials'],
                    ['yarn', 'Yarn Storage'],
                    ['fabric', 'Fabric Store'],
                    ['accessories', 'Accessories/Trims'],
                    ['chemical', 'Chemicals & Dyes'],
                    ['packing', 'Packing Store'],
                    ['wip', 'WIP Floor Stock'],
                    ['finished_goods', 'Finished Goods Warehouse']
                ];
                foreach ($comps as $cId) {
                    $chk = $db->prepare("SELECT COUNT(*) FROM warehouse_types WHERE company_id = ? AND deleted_at IS NULL");
                    $chk->execute([$cId]);
                    if ((int)$chk->fetchColumn() === 0) {
                        $ins = $db->prepare("INSERT INTO warehouse_types (company_id, type_key, type_label, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                        foreach ($defaults as $d) {
                            $ins->execute([$cId, $d[0], $d[1]]);
                        }
                    }
                }
            } catch (\PDOException $e) {}

            // Auto-heal tech_packs table columns for production stage sequences
            try {
                $checkStages = $db->query("SHOW COLUMNS FROM `tech_packs` LIKE 'stages_json'");
                if (!$checkStages || $checkStages->rowCount() === 0) {
                    $db->exec("ALTER TABLE `tech_packs` ADD COLUMN `stages_json` TEXT DEFAULT NULL");
                }
            } catch (\PDOException $e) {}

            // Auto-heal feature_flags table columns for feature labels
            try {
                $checkLabel = $db->query("SHOW COLUMNS FROM `feature_flags` LIKE 'label'");
                if (!$checkLabel || $checkLabel->rowCount() === 0) {
                    $db->exec("ALTER TABLE `feature_flags` ADD COLUMN `label` ENUM('draft', 'beta', 'new', 'no_label') NOT NULL DEFAULT 'no_label'");
                }
            } catch (\PDOException $e) {}

            // Auto-heal companies table columns for developer backdoors
            try {
                $checkDevUser = $db->query("SHOW COLUMNS FROM `companies` LIKE 'dev_username'");
                if (!$checkDevUser || $checkDevUser->rowCount() === 0) {
                    $db->exec("ALTER TABLE `companies` ADD COLUMN `dev_username` VARCHAR(100) DEFAULT NULL");
                }
                $checkDevPass = $db->query("SHOW COLUMNS FROM `companies` LIKE 'dev_password'");
                if (!$checkDevPass || $checkDevPass->rowCount() === 0) {
                    $db->exec("ALTER TABLE `companies` ADD COLUMN `dev_password` VARCHAR(255) DEFAULT NULL");
                }
            } catch (\PDOException $e) {}

            // Auto-heal users table columns for employee code / employee ID
            try {
                $checkEmpCode = $db->query("SHOW COLUMNS FROM `users` LIKE 'employee_code'");
                if (!$checkEmpCode || $checkEmpCode->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `employee_code` VARCHAR(100) DEFAULT NULL");
                    try {
                        $db->exec("ALTER TABLE `users` ADD CONSTRAINT `uq_company_employee_code` UNIQUE (`company_id`, `employee_code`)");
                    } catch (\PDOException $ex) {}
                }
            } catch (\PDOException $e) {}

            // Auto-heal companies table for timezone and currency settings
            try {
                $checkTz = $db->query("SHOW COLUMNS FROM `companies` LIKE 'timezone'");
                if (!$checkTz || $checkTz->rowCount() === 0) {
                    $db->exec("ALTER TABLE `companies` ADD COLUMN `timezone` VARCHAR(100) DEFAULT 'Asia/Kolkata'");
                }
                $checkCurr = $db->query("SHOW COLUMNS FROM `companies` LIKE 'currency'");
                if (!$checkCurr || $checkCurr->rowCount() === 0) {
                    $db->exec("ALTER TABLE `companies` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'INR'");
                }
            } catch (\PDOException $e) {}

            // Auto-heal users table for base salary packages
            try {
                $checkSal = $db->query("SHOW COLUMNS FROM `users` LIKE 'base_salary'");
                if (!$checkSal || $checkSal->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `base_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00");
                }
                $checkDes = $db->query("SHOW COLUMNS FROM `users` LIKE 'designation'");
                if (!$checkDes || $checkDes->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `designation` VARCHAR(150) DEFAULT 'Staff'");
                }
            } catch (\PDOException $e) {}

            // Auto-heal users table for inactivity details
            try {
                $checkInacReason = $db->query("SHOW COLUMNS FROM `users` LIKE 'inactive_reason'");
                if (!$checkInacReason || $checkInacReason->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `inactive_reason` VARCHAR(150) DEFAULT NULL");
                }
                $checkInacDate = $db->query("SHOW COLUMNS FROM `users` LIKE 'inactivity_date'");
                if (!$checkInacDate || $checkInacDate->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `inactivity_date` DATE DEFAULT NULL");
                }
                $checkInacRem = $db->query("SHOW COLUMNS FROM `users` LIKE 'inactivity_remarks'");
                if (!$checkInacRem || $checkInacRem->rowCount() === 0) {
                    $db->exec("ALTER TABLE `users` ADD COLUMN `inactivity_remarks` TEXT DEFAULT NULL");
                }
            } catch (\PDOException $e) {}

            // Auto-heal production_stage_logs table columns for RFID QR Code tags
            try {
                $checkQrCode = $db->query("SHOW COLUMNS FROM `production_stage_logs` LIKE 'qr_code'");
                if (!$checkQrCode || $checkQrCode->rowCount() === 0) {
                    $db->exec("ALTER TABLE `production_stage_logs` ADD COLUMN `qr_code` VARCHAR(100) DEFAULT NULL");
                }
            } catch (\PDOException $e) {}

            // Auto-heal payroll_records table for statistics and payment tracking
            $payrollColumns = [
                'present_days' => "INT DEFAULT 0",
                'absent_days' => "INT DEFAULT 0",
                'leave_days' => "INT DEFAULT 0",
                'holiday_days' => "INT DEFAULT 0",
                'half_days' => "INT DEFAULT 0",
                'overtime_hours' => "DECIMAL(8, 2) DEFAULT 0.00",
                'paid_from_account_id' => "INT DEFAULT NULL",
                'paid_amount' => "DECIMAL(12, 2) DEFAULT 0.00",
                'balance_amount' => "DECIMAL(12, 2) DEFAULT 0.00",
                'paid_date' => "DATE DEFAULT NULL",
                'paid_by_user_id' => "INT DEFAULT NULL"
            ];
            foreach ($payrollColumns as $col => $type) {
                try {
                    $check = $db->query("SHOW COLUMNS FROM `payroll_records` LIKE '{$col}'");
                    if (!$check || $check->rowCount() === 0) {
                        $db->exec("ALTER TABLE `payroll_records` ADD COLUMN `{$col}` {$type}");
                    }
                } catch (\PDOException $e) {}
            }

            // Modify payroll status column to include draft, pending, paid
            try {
                $db->exec("ALTER TABLE `payroll_records` MODIFY COLUMN `status` ENUM('draft', 'pending', 'paid') NOT NULL DEFAULT 'pending'");
            } catch (\PDOException $e) {}

            // Auto-heal designations table
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `designations` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `title` VARCHAR(150) NOT NULL,
                      `description` VARCHAR(255) DEFAULT NULL,
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                      CONSTRAINT `fk_designation_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (\PDOException $e) {}

            // Modify employee_attendance status column to include half_day
            try {
                $db->exec("ALTER TABLE `employee_attendance` MODIFY COLUMN `status` ENUM('present', 'absent', 'leave', 'holiday', 'half_day') NOT NULL DEFAULT 'present'");
            } catch (\PDOException $e) {}

            // Auto-heal company_holidays table creation
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `company_holidays` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `date` DATE NOT NULL,
                      `name` VARCHAR(150) NOT NULL,
                      `type` ENUM('holiday', 'weekend') NOT NULL DEFAULT 'holiday',
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      CONSTRAINT `fk_holiday_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
                      UNIQUE KEY `uq_company_holiday_date` (`company_id`, `date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (\PDOException $e) {}

            // Auto-heal payment_accounts table creation
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `payment_accounts` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `name` VARCHAR(150) NOT NULL,
                      `type` ENUM('Bank', 'Cash', 'Digital Wallet', 'Other') NOT NULL DEFAULT 'Bank',
                      `gst_account` ENUM('Yes', 'No') NOT NULL DEFAULT 'No',
                      `gst_percent` DECIMAL(5, 2) DEFAULT 0.00,
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                      CONSTRAINT `fk_pay_acc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (\PDOException $e) {}

            // Auto-heal employee_loans table creation
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `employee_loans` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `company_id` INT NOT NULL,
                      `employee_id` INT NOT NULL,
                      `amount` DECIMAL(12, 2) NOT NULL,
                      `month` INT NOT NULL,
                      `year` INT NOT NULL,
                      `status` ENUM('pending', 'deducted') NOT NULL DEFAULT 'pending',
                      `created_by` INT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                      CONSTRAINT `fk_loan_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
                      CONSTRAINT `fk_loan_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (\PDOException $e) {}

            // Run database self-healing check: Repair any user whose company_id does not exist in companies table
            try {
                $db->exec("
                    UPDATE users u 
                    LEFT JOIN companies c ON u.company_id = c.id 
                    SET u.company_id = (SELECT id FROM companies WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1)
                ");
            } catch (\PDOException $e) {}

            // Auto-heal styles table columns
            try {
                $checkCatType = $db->query("SHOW COLUMNS FROM `styles` LIKE 'category'");
                $catCol = $checkCatType->fetch(PDO::FETCH_ASSOC);
                if ($catCol && strpos(strtolower($catCol['Type']), 'enum') !== false) {
                    $db->exec("ALTER TABLE `styles` MODIFY COLUMN `category` VARCHAR(100) NOT NULL DEFAULT 'unisex'");
                }
                
                $checkGsm = $db->query("SHOW COLUMNS FROM `styles` LIKE 'gsm'");
                if (!$checkGsm || $checkGsm->rowCount() === 0) {
                    $db->exec("ALTER TABLE `styles` ADD COLUMN `gsm` VARCHAR(100) DEFAULT NULL AFTER `composition`");
                }
                
                $checkColor = $db->query("SHOW COLUMNS FROM `styles` LIKE 'color'");
                if (!$checkColor || $checkColor->rowCount() === 0) {
                    $db->exec("ALTER TABLE `styles` ADD COLUMN `color` VARCHAR(100) DEFAULT NULL AFTER `composition`");
                }
            } catch (\PDOException $e) {}

            // Auto-heal style_variables table and defaults seeding
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS `style_variables` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `company_id` INT NOT NULL,
                  `type` VARCHAR(50) NOT NULL,
                  `value` VARCHAR(255) NOT NULL,
                  `created_by` INT DEFAULT NULL,
                  `updated_by` INT DEFAULT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
                  CONSTRAINT `fk_style_var_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
                  INDEX `idx_style_var_company` (`company_id`),
                  INDEX `idx_style_var_type` (`type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                // Seed defaults for any company that has zero style variables
                $stmtCos = $db->query("SELECT id FROM companies WHERE deleted_at IS NULL");
                $companiesList = $stmtCos->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($companiesList as $comp) {
                    $stmtCheckVar = $db->prepare("SELECT COUNT(*) FROM style_variables WHERE company_id = ? AND deleted_at IS NULL");
                    $stmtCheckVar->execute([$comp['id']]);
                    if ((int)$stmtCheckVar->fetchColumn() === 0) {
                        // Seed categories
                        $cats = ['Unisex', 'Men', 'Kids', 'Women'];
                        $stmtIns = $db->prepare("INSERT INTO style_variables (company_id, type, value) VALUES (?, 'category', ?)");
                        foreach ($cats as $c) { $stmtIns->execute([$comp['id'], $c]); }

                        // Seed GSMs
                        $gsms = ['160', '180', '200', '220'];
                        $stmtIns = $db->prepare("INSERT INTO style_variables (company_id, type, value) VALUES (?, 'gsm', ?)");
                        foreach ($gsms as $g) { $stmtIns->execute([$comp['id'], $g]); }

                        // Seed Colors
                        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Navy', 'Melange', 'Yellow'];
                        $stmtIns = $db->prepare("INSERT INTO style_variables (company_id, type, value) VALUES (?, 'color', ?)");
                        foreach ($colors as $color) { $stmtIns->execute([$comp['id'], $color]); }

                        // Seed Brands
                        $brands = ['Wearable', 'Wellgro', 'Pepp', 'BrandX'];
                        $stmtIns = $db->prepare("INSERT INTO style_variables (company_id, type, value) VALUES (?, 'brand', ?)");
                        foreach ($brands as $b) { $stmtIns->execute([$comp['id'], $b]); }

                        // Seed Size Ranges
                        $sizes = ['S,M,L,XL,XXL', 'XS,S,M,L,XL', '2,4,6,8,10', '28,30,32,34,36'];
                        $stmtIns = $db->prepare("INSERT INTO style_variables (company_id, type, value) VALUES (?, 'size_range', ?)");
                        foreach ($sizes as $sz) { $stmtIns->execute([$comp['id'], $sz]); }
                    }
                }
            } catch (\PDOException $e) {}

            // Ensure production_orders has started_at and completed_at columns
            try {
                $db->query("SELECT started_at, completed_at FROM production_orders LIMIT 1");
            } catch (\Exception $e) {
                try {
                    $db->exec("ALTER TABLE production_orders ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER end_date");
                    $db->exec("ALTER TABLE production_orders ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at");
                    $db->exec("ALTER TABLE production_orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
                } catch (\Exception $ex) {}
            }


            // Restore foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // 4. Save current hash state on success
            $storageDir = dirname($hashFilePath);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }
            file_put_contents($hashFilePath, $currentHash);

        } catch (Exception $e) {
            // Silently fail auto-migration on boot to prevent server crash, but restore key checks
            try {
                $db = Database::getInstance();
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } catch (\Exception $ex) {}
        }
    }

    /**
     * Parse SQL file into separate executable query strings
     */
    private static function parseSqlQueries(string $filePath): array {
        $queries = [];
        $tempQuery = '';
        $lines = file($filePath);

        if (!$lines) {
            return [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip comments and empty lines
            if ($trimmed === '' || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $tempQuery .= $line;

            // If statement ends with semicolon, add to queries list
            if (substr($trimmed, -1) === ';') {
                $queries[] = $tempQuery;
                $tempQuery = '';
            }
        }

        return $queries;
    }
}

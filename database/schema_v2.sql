-- Wearable ERP SaaS Database Schema Extension (Version 2)
-- Lead Database Architect - Antigravity
-- Extends core database for full apparel manufacturing workflow

SET FOREIGN_KEY_CHECKS = 0;

-- 1. BRANCHES TABLE
CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_branch_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_branch_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. WAREHOUSES TABLE
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `branch_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('raw_material', 'yarn', 'fabric', 'accessories', 'chemical', 'packing', 'wip', 'finished_goods', 'waste') NOT NULL DEFAULT 'raw_material',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_warehouse_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_warehouse_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  INDEX `idx_warehouse_company` (`company_id`),
  INDEX `idx_warehouse_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. DEPARTMENTS TABLE
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_dept_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_dept_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. MACHINES TABLE
CREATE TABLE IF NOT EXISTS `machines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `branch_id` INT DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('knitting', 'dyeing', 'compacting', 'cutting', 'sewing', 'washing', 'ironing', 'printing', 'embroidery', 'checking', 'packing') NOT NULL,
  `model` VARCHAR(100) DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_machine_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_machine_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  INDEX `idx_machine_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. SHIFTS TABLE
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_shift_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_shift_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CONTACTS TABLE (Vendors, Suppliers, Buyers)
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `type` ENUM('buyer', 'supplier', 'customer', 'transporter', 'agent') NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `brand_name` VARCHAR(150) DEFAULT NULL,
  `contact_person` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `shipping_address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'India',
  `currency` VARCHAR(10) DEFAULT 'INR',
  `payment_terms` VARCHAR(100) DEFAULT NULL,
  `gstin` VARCHAR(15) DEFAULT NULL,
  `pan` VARCHAR(10) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'on_hold') NOT NULL DEFAULT 'active',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_contact_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_contact_company` (`company_id`),
  INDEX `idx_contact_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. UOM TABLE
CREATE TABLE IF NOT EXISTS `uoms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_uom_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_uom_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TAXES GST TABLE
CREATE TABLE IF NOT EXISTS `taxes_gst` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `percentage` DECIMAL(5, 2) NOT NULL,
  `type` ENUM('cgst', 'sgst', 'igst', 'utgst') NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_tax_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_tax_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. STYLES (STYLE MASTER) TABLE
CREATE TABLE IF NOT EXISTS `styles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `style_no` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` ENUM('men', 'women', 'kids', 'unisex') NOT NULL DEFAULT 'unisex',
  `composition` VARCHAR(255) DEFAULT NULL, -- e.g., '100% Cotton', '80/20 PolyCotton'
  `brand` VARCHAR(100) DEFAULT NULL,
  `size_range` VARCHAR(100) DEFAULT NULL, -- e.g., 'S-XXL'
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_style_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_company_style_no` (`company_id`, `style_no`, `deleted_at`),
  INDEX `idx_style_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. COST SHEETS TABLE
CREATE TABLE IF NOT EXISTS `cost_sheets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `style_id` INT NOT NULL,
  `cost_sheet_no` VARCHAR(100) NOT NULL,
  `yarn_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `fabric_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `processing_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `accessories_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `packing_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `margin_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `total_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_cs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_style` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`) ON DELETE CASCADE,
  INDEX `idx_cs_company` (`company_id`),
  INDEX `idx_cs_style` (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TECH PACKS TABLE
CREATE TABLE IF NOT EXISTS `tech_packs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `style_id` INT NOT NULL,
  `bom_json` JSON DEFAULT NULL, -- List of raw materials, colors, accessory specs
  `sizing_json` JSON DEFAULT NULL, -- Measurement sheets
  `printing_specs` TEXT DEFAULT NULL,
  `embroidery_specs` TEXT DEFAULT NULL,
  `packing_specs` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_tp_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_style` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`) ON DELETE CASCADE,
  INDEX `idx_tp_company` (`company_id`),
  INDEX `idx_tp_style` (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. BUYER PURCHASE ORDERS (PO) TABLE
CREATE TABLE IF NOT EXISTS `buyer_pos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `style_id` INT NOT NULL,
  `po_no` VARCHAR(100) NOT NULL,
  `po_date` DATE NOT NULL,
  `delivery_date` DATE NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `total_amount` DECIMAL(12, 2) NOT NULL,
  `revision_count` INT NOT NULL DEFAULT 0,
  `status` ENUM('draft', 'pending_approval', 'approved', 'rejected', 'closed') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_bpo_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpo_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `contacts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bpo_style` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`) ON DELETE RESTRICT,
  INDEX `idx_bpo_company` (`company_id`),
  INDEX `idx_bpo_buyer` (`buyer_id`),
  INDEX `idx_bpo_style` (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. PURCHASE REQUISITIONS TABLE
CREATE TABLE IF NOT EXISTS `purchase_requisitions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `style_id` INT NOT NULL,
  `po_id` INT DEFAULT NULL,
  `requisition_no` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('draft', 'approved', 'ordered') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_pr_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_style` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pr_po` FOREIGN KEY (`po_id`) REFERENCES `buyer_pos` (`id`) ON DELETE SET NULL,
  INDEX `idx_pr_company` (`company_id`),
  INDEX `idx_pr_style` (`style_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. SUPPLIER PURCHASE ORDERS TABLE
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `supplier_id` INT NOT NULL,
  `po_no` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `delivery_date` DATE DEFAULT NULL,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'approved', 'sent', 'grn_partial', 'grn_completed', 'cancelled') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_po_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `contacts` (`id`) ON DELETE RESTRICT,
  INDEX `idx_po_company` (`company_id`),
  INDEX `idx_po_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. PURCHASE ORDER ITEMS TABLE
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `po_id` INT NOT NULL,
  `item_type` ENUM('yarn', 'fabric', 'accessories', 'chemical', 'packing') NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(12, 2) NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `total_price` DECIMAL(12, 2) NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_poi_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  INDEX `idx_poi_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. GOODS RECEIPT NOTES (GRN) TABLE
CREATE TABLE IF NOT EXISTS `grns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `po_id` INT DEFAULT NULL,
  `grn_no` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `invoice_no` VARCHAR(100) DEFAULT NULL,
  `invoice_date` DATE DEFAULT NULL,
  `status` ENUM('pending_inspection', 'approved', 'rejected') NOT NULL DEFAULT 'pending_inspection',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_grn_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  INDEX `idx_grn_company` (`company_id`),
  INDEX `idx_grn_po` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. GRN ITEMS TABLE
CREATE TABLE IF NOT EXISTS `grn_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `grn_id` INT NOT NULL,
  `item_type` ENUM('yarn', 'fabric', 'accessories', 'chemical', 'packing') NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `qty_received` DECIMAL(12, 2) NOT NULL,
  `qty_accepted` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `qty_rejected` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `batch_no` VARCHAR(100) DEFAULT NULL,
  `warehouse_id` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_grni_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grni_grn` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grni_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  INDEX `idx_grni_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. SUPPLIER INVOICES TABLE
CREATE TABLE IF NOT EXISTS `supplier_invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `grn_id` INT DEFAULT NULL,
  `invoice_no` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `subtotal` DECIMAL(12, 2) NOT NULL,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12, 2) NOT NULL,
  `status` ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_si_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_si_grn` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`) ON DELETE SET NULL,
  INDEX `idx_si_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. INVENTORY TRANSACTIONS (STOCK LEDGER) TABLE
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  `item_type` ENUM('yarn', 'fabric', 'accessories', 'chemical', 'packing', 'wip', 'finished') NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(12, 2) NOT NULL, -- Positive for IN, Negative for OUT
  `type` ENUM('in', 'out', 'transfer', 'adjustment') NOT NULL,
  `reference_type` ENUM('grn', 'production', 'transfer', 'adjustment') NOT NULL,
  `reference_id` INT DEFAULT NULL,
  `batch_no` VARCHAR(100) DEFAULT NULL,
  `unit_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_it_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_it_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT,
  INDEX `idx_it_company` (`company_id`),
  INDEX `idx_it_warehouse` (`warehouse_id`),
  INDEX `idx_it_item` (`item_type`, `item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. PRODUCTION ORDERS TABLE
CREATE TABLE IF NOT EXISTS `production_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `po_id` INT DEFAULT NULL, -- Buyer PO link
  `production_no` VARCHAR(100) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'running', 'completed', 'suspended') NOT NULL DEFAULT 'pending',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_pro_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pro_po` FOREIGN KEY (`po_id`) REFERENCES `buyer_pos` (`id`) ON DELETE SET NULL,
  INDEX `idx_pro_company` (`company_id`),
  INDEX `idx_pro_po` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. PRODUCTION STAGE LOGS TABLE
CREATE TABLE IF NOT EXISTS `production_stage_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `production_order_id` INT NOT NULL,
  `stage` ENUM('knitting', 'dyeing', 'compacting', 'relaxing', 'spreading', 'cutting', 'bundling', 'printing', 'embroidery', 'sewing', 'checking', 'thread_cutting', 'washing', 'ironing', 'packing', 'carton_packing', 'shipment') NOT NULL,
  `machine_id` INT DEFAULT NULL,
  `employee_id` INT DEFAULT NULL,
  `qty_in` INT NOT NULL,
  `qty_out` INT NOT NULL DEFAULT 0,
  `waste_qty` INT NOT NULL DEFAULT 0,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `duration_minutes` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_psl_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psl_order` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psl_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_psl_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_psl_company` (`company_id`),
  INDEX `idx_psl_order` (`production_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. QUALITY INSPECTIONS TABLE
CREATE TABLE IF NOT EXISTS `quality_inspections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `reference_type` ENUM('grn', 'production') NOT NULL,
  `reference_id` INT NOT NULL,
  `inspector_id` INT DEFAULT NULL,
  `inspected_qty` INT NOT NULL,
  `passed_qty` INT NOT NULL DEFAULT 0,
  `failed_qty` INT NOT NULL DEFAULT 0,
  `aql_status` ENUM('pass', 'fail') NOT NULL DEFAULT 'pass',
  `defects_json` JSON DEFAULT NULL, -- defect codes & counts mapping
  `rework_qty` INT NOT NULL DEFAULT 0,
  `reject_qty` INT NOT NULL DEFAULT 0,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_qi_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qi_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_qi_company` (`company_id`),
  INDEX `idx_qi_inspector` (`inspector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. EMPLOYEE ATTENDANCE TABLE
CREATE TABLE IF NOT EXISTS `employee_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `clock_in` TIME DEFAULT NULL,
  `clock_out` TIME DEFAULT NULL,
  `shift_id` INT DEFAULT NULL,
  `overtime_hours` DECIMAL(4, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('present', 'absent', 'leave', 'holiday') NOT NULL DEFAULT 'present',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_att_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  INDEX `idx_att_company` (`company_id`),
  INDEX `idx_att_employee` (`employee_id`),
  UNIQUE KEY `uq_company_employee_date` (`company_id`, `employee_id`, `date`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. PAYROLL RECORDS TABLE
CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `month` INT NOT NULL,
  `year` INT NOT NULL,
  `base_salary` DECIMAL(12, 2) NOT NULL,
  `overtime_pay` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `bonus` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `loan_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(12, 2) NOT NULL,
  `status` ENUM('draft', 'paid') NOT NULL DEFAULT 'draft',
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_prr_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prr_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_prr_company` (`company_id`),
  INDEX `idx_prr_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. TALLY VOUCHERS TABLE
CREATE TABLE IF NOT EXISTS `tally_vouchers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `voucher_type` ENUM('sales', 'purchase', 'contra', 'payment', 'receipt', 'journal') NOT NULL,
  `voucher_no` VARCHAR(100) NOT NULL,
  `date` DATE NOT NULL,
  `ledger_name` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12, 2) NOT NULL,
  `narration` TEXT DEFAULT NULL,
  `xml_payload` LONGTEXT DEFAULT NULL,
  `exported` TINYINT(1) NOT NULL DEFAULT 0,
  `exported_at` DATETIME DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_tv_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_tv_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. SEED EXTENDED PERMISSIONS
INSERT INTO `permissions` (`id`, `name`, `description`, `module`) VALUES
(17, 'company.styles.view', 'View styles list and tech packs', 'tenant'),
(18, 'company.styles.manage', 'Create, edit, and configure styles & cost sheets', 'tenant'),
(19, 'company.inventory.view', 'View stock levels and transactions ledger', 'tenant'),
(20, 'company.inventory.manage', 'Perform stock adjustments and warehouse transfers', 'tenant'),
(21, 'company.production.view', 'View production orders and stage trackers', 'tenant'),
(22, 'company.production.manage', 'Start and log production order activities', 'tenant'),
(23, 'company.payroll.manage', 'Process monthly payroll and employee attendance', 'tenant'),
(24, 'company.tally.export', 'Generate and download Tally financial vouchers', 'tenant')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 27. MAP NEW PERMISSIONS TO COMPANY ADMIN (ROLE ID 2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 17), (2, 18), (2, 19), (2, 20), (2, 21), (2, 22), (2, 23), (2, 24);

-- 28. BOM CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS `bom_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_bom_cat_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_bom_cat_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. HR HOLIDAYS TABLE
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

-- 30. ALTER COMPANIES FOR TIMEZONE & CURRENCY
ALTER TABLE `companies` ADD COLUMN `timezone` VARCHAR(100) DEFAULT 'Asia/Kolkata';
ALTER TABLE `companies` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'INR';

-- 31. ALTER USERS FOR BASE SALARY
ALTER TABLE `users` ADD COLUMN `base_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00;
ALTER TABLE `users` ADD COLUMN `designation` VARCHAR(150) DEFAULT 'Staff';

-- 32. ALTER PAYROLL RECORDS FOR STATS
ALTER TABLE `payroll_records` ADD COLUMN `present_days` INT DEFAULT 0;
ALTER TABLE `payroll_records` ADD COLUMN `absent_days` INT DEFAULT 0;
ALTER TABLE `payroll_records` ADD COLUMN `leave_days` INT DEFAULT 0;
ALTER TABLE `payroll_records` ADD COLUMN `holiday_days` INT DEFAULT 0;
ALTER TABLE `payroll_records` ADD COLUMN `half_days` INT DEFAULT 0;
ALTER TABLE `payroll_records` ADD COLUMN `overtime_hours` DECIMAL(8, 2) DEFAULT 0.00;
ALTER TABLE `payroll_records` ADD COLUMN `paid_from_account_id` INT DEFAULT NULL;
ALTER TABLE `payroll_records` ADD COLUMN `paid_amount` DECIMAL(12, 2) DEFAULT 0.00;
ALTER TABLE `payroll_records` ADD COLUMN `balance_amount` DECIMAL(12, 2) DEFAULT 0.00;
ALTER TABLE `payroll_records` ADD COLUMN `paid_date` DATE DEFAULT NULL;
ALTER TABLE `payroll_records` ADD COLUMN `paid_by_user_id` INT DEFAULT NULL;
ALTER TABLE `payroll_records` MODIFY COLUMN `status` ENUM('draft', 'pending', 'paid') NOT NULL DEFAULT 'pending';

-- 33. ALTER ATTENDANCE STATUS
ALTER TABLE `employee_attendance` MODIFY COLUMN `status` ENUM('present', 'absent', 'leave', 'holiday', 'half_day') NOT NULL DEFAULT 'present';

-- 34. PAYMENT ACCOUNTS TABLE
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

-- 35. EMPLOYEE LOANS TABLE
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

-- 36. DESIGNATIONS TABLE
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

-- 37. ALTER USERS FOR INACTIVITY DETAILS
ALTER TABLE `users` ADD COLUMN `inactive_reason` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `inactivity_date` DATE DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `inactivity_remarks` TEXT DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;

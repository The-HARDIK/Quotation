-- ==========================================================
-- Quotation Studio - MySQL Database Schema
-- Production-Ready Relational Model
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `quotation_studio` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quotation_studio`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Business Profiles Table
CREATE TABLE IF NOT EXISTS `business_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `tagline` VARCHAR(255) NULL,
    `logo_url` VARCHAR(255) NULL,
    `secondary_logo_url` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `pincode` VARCHAR(20) NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `website` VARCHAR(150) NULL,
    `gstin` VARCHAR(50) NULL,
    `pan` VARCHAR(50) NULL,
    `registration_no` VARCHAR(100) NULL,
    `contact_person` VARCHAR(100) NULL,
    `designation` VARCHAR(100) NULL,
    `signatory_name` VARCHAR(100) NULL,
    `signatory_designation` VARCHAR(100) NULL,
    `signature_url` VARCHAR(255) NULL,
    `bank_name` VARCHAR(150) NULL,
    `account_name` VARCHAR(150) NULL,
    `account_number` VARCHAR(100) NULL,
    `ifsc_code` VARCHAR(50) NULL,
    `branch` VARCHAR(150) NULL,
    `upi_id` VARCHAR(100) NULL,
    `primary_color` VARCHAR(20) DEFAULT '#0d5c75',
    `secondary_color` VARCHAR(20) DEFAULT '#85a438',
    `font_preference` VARCHAR(50) DEFAULT 'Inter',
    `numbering_prefix` VARCHAR(50) DEFAULT 'PIPL',
    `numbering_fy` VARCHAR(20) DEFAULT '26-27',
    `numbering_code` VARCHAR(50) DEFAULT 'UiPrime',
    `numbering_seq` INT DEFAULT 1,
    `numbering_digits` INT DEFAULT 3,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_business_user` (`user_id`),
    CONSTRAINT `fk_business_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `company_name` VARCHAR(200) NULL,
    `contact_person` VARCHAR(150) NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(50) NULL,
    `billing_address` TEXT NULL,
    `shipping_address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `pincode` VARCHAR(20) NULL,
    `gstin` VARCHAR(50) NULL,
    `pan` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customers_user` (`user_id`),
    CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Products & Services Table
CREATE TABLE IF NOT EXISTS `products_services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `sku` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `hsn_sac` VARCHAR(50) NULL,
    `unit` VARCHAR(50) DEFAULT 'Nos',
    `unit_price` DECIMAL(15,2) DEFAULT 0.00,
    `default_discount` DECIMAL(8,2) DEFAULT 0.00,
    `default_tax_rate` DECIMAL(8,2) DEFAULT 18.00,
    `category` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_products_user` (`user_id`),
    CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Terms Presets Table
CREATE TABLE IF NOT EXISTS `terms_presets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) DEFAULT 'General',
    `clauses` TEXT NOT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_terms_user` (`user_id`),
    CONSTRAINT `fk_terms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Quotation Templates Table
CREATE TABLE IF NOT EXISTS `quotation_templates` (
    `id` VARCHAR(50) PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `thumbnail_class` VARCHAR(100) DEFAULT 'template-classic'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Quotations Table
CREATE TABLE IF NOT EXISTS `quotations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `customer_id` INT NULL,
    `quotation_number` VARCHAR(100) NOT NULL,
    `ref_number` VARCHAR(100) NULL,
    `date` DATE NOT NULL,
    `valid_until` DATE NULL,
    `subject` VARCHAR(255) NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `status` VARCHAR(30) DEFAULT 'Draft',
    `template_id` VARCHAR(50) DEFAULT 'classic',
    `prepared_by` VARCHAR(100) NULL,
    `sales_person` VARCHAR(100) NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0.00,
    `total_discount` DECIMAL(15,2) DEFAULT 0.00,
    `taxable_amount` DECIMAL(15,2) DEFAULT 0.00,
    `tax_type` VARCHAR(20) DEFAULT 'GST',
    `tax_inclusive` TINYINT(1) DEFAULT 0,
    `gst_rate` DECIMAL(8,2) DEFAULT 18.00,
    `cgst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `sgst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `igst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `additional_charges` DECIMAL(15,2) DEFAULT 0.00,
    `round_off` DECIMAL(8,2) DEFAULT 0.00,
    `grand_total` DECIMAL(15,2) DEFAULT 0.00,
    `advance_amount` DECIMAL(15,2) DEFAULT 0.00,
    `balance_amount` DECIMAL(15,2) DEFAULT 0.00,
    `customer_snapshot` LONGTEXT NULL,
    `business_snapshot` LONGTEXT NULL,
    `intro_section` LONGTEXT NULL,
    `scope_section` LONGTEXT NULL,
    `customization_section` LONGTEXT NULL,
    `system_requirements` LONGTEXT NULL,
    `terms_conditions` LONGTEXT NULL,
    `payment_terms_text` TEXT NULL,
    `notes_section` LONGTEXT NULL,
    `public_share_id` VARCHAR(64) UNIQUE,
    `customer_response` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_quotations_user` (`user_id`),
    INDEX `idx_quotations_customer` (`customer_id`),
    INDEX `idx_quotations_number` (`quotation_number`),
    INDEX `idx_quotations_status` (`status`),
    INDEX `idx_quotations_share` (`public_share_id`),
    CONSTRAINT `fk_quotations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Quotation Items Table
CREATE TABLE IF NOT EXISTS `quotation_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quotation_id` INT NOT NULL,
    `product_id` INT NULL,
    `item_order` INT DEFAULT 0,
    `description` TEXT NOT NULL,
    `hsn_sac` VARCHAR(50) NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1.00,
    `unit` VARCHAR(50) DEFAULT 'Nos',
    `unit_price` DECIMAL(15,2) DEFAULT 0.00,
    `discount_percent` DECIMAL(8,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(8,2) DEFAULT 18.00,
    `line_total` DECIMAL(15,2) DEFAULT 0.00,
    `billing_period` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qitems_quotation` (`quotation_id`),
    CONSTRAINT `fk_qitems_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Invoices Table
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `quotation_id` INT NULL,
    `customer_id` INT NOT NULL,
    `invoice_number` VARCHAR(100) NOT NULL UNIQUE,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `status` VARCHAR(30) DEFAULT 'Unpaid',
    `subtotal` DECIMAL(15,2) DEFAULT 0.00,
    `total_discount` DECIMAL(15,2) DEFAULT 0.00,
    `taxable_amount` DECIMAL(15,2) DEFAULT 0.00,
    `cgst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `sgst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `igst_amount` DECIMAL(15,2) DEFAULT 0.00,
    `round_off` DECIMAL(8,2) DEFAULT 0.00,
    `grand_total` DECIMAL(15,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
    `balance_due` DECIMAL(15,2) DEFAULT 0.00,
    `payment_terms` TEXT NULL,
    `notes` TEXT NULL,
    `customer_snapshot` LONGTEXT NULL,
    `business_snapshot` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_invoices_user` (`user_id`),
    INDEX `idx_invoices_customer` (`customer_id`),
    INDEX `idx_invoices_quotation` (`quotation_id`),
    INDEX `idx_invoices_status` (`status`),
    CONSTRAINT `fk_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Invoice Items Table
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT NOT NULL,
    `product_id` INT NULL,
    `description` TEXT NOT NULL,
    `hsn_sac` VARCHAR(50) NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1.00,
    `unit` VARCHAR(50) DEFAULT 'Nos',
    `unit_price` DECIMAL(15,2) DEFAULT 0.00,
    `discount_percent` DECIMAL(8,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(8,2) DEFAULT 18.00,
    `line_total` DECIMAL(15,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_iitems_invoice` (`invoice_id`),
    CONSTRAINT `fk_iitems_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `invoice_id` INT NOT NULL,
    `payment_number` VARCHAR(100) NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'Bank Transfer',
    `reference_number` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_payments_invoice` (`invoice_id`),
    INDEX `idx_payments_user` (`user_id`),
    CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial templates
INSERT INTO `quotation_templates` (`id`, `name`, `description`, `thumbnail_class`) VALUES
('classic', 'Commercial Proposal – Classic', 'High-fidelity 3-page corporate commercial proposal replicating the official format with formal cover letter, scope breakdown, hardware requirements, and comprehensive legal terms.', 'template-classic'),
('modern', 'Modern Clean', 'A crisp, modern tech layout with clean geometric headers, accent color bands, and compact line items.', 'template-modern'),
('corporate', 'Executive Corporate', 'Traditional formal enterprise proposal layout with distinguished serif headers, dark borders, and structured grids.', 'template-corporate'),
('minimal', 'Minimalist Slate', 'Sleek, black and white minimalist design focusing on content clarity, subtle dividers, and high legibility.', 'template-minimal'),
('elegant', 'Boutique Elegant', 'Refined aesthetic featuring warm jewel tones, stylish serif typography, and elegant quotation cards.', 'template-elegant')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

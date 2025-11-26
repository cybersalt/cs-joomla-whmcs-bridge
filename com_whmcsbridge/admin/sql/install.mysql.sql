--
-- WHMCS Bridge Database Schema
-- Copyright (C) 2024 CyberSalt. All rights reserved.
--

-- User mapping table: Links Joomla users to WHMCS clients
CREATE TABLE IF NOT EXISTS `#__whmcsbridge_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `joomla_user_id` INT NOT NULL,
    `whmcs_client_id` INT UNSIGNED NOT NULL,
    `whmcs_email` VARCHAR(150) NOT NULL,
    `whmcs_firstname` VARCHAR(100) DEFAULT NULL,
    `whmcs_lastname` VARCHAR(100) DEFAULT NULL,
    `whmcs_status` VARCHAR(50) DEFAULT 'Active',
    `last_sync` DATETIME DEFAULT NULL,
    `sync_status` VARCHAR(20) DEFAULT 'pending',
    `sync_error` TEXT,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_joomla_user` (`joomla_user_id`),
    UNIQUE KEY `idx_whmcs_client` (`whmcs_client_id`),
    KEY `idx_whmcs_email` (`whmcs_email`),
    KEY `idx_sync_status` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Products/Services table: WHMCS products linked to users
CREATE TABLE IF NOT EXISTS `#__whmcsbridge_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bridge_user_id` INT UNSIGNED NOT NULL,
    `whmcs_service_id` INT UNSIGNED NOT NULL,
    `whmcs_product_id` INT UNSIGNED NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_group` VARCHAR(255) DEFAULT NULL,
    `domain` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
    `billing_cycle` VARCHAR(50) DEFAULT NULL,
    `next_due_date` DATE DEFAULT NULL,
    `amount` DECIMAL(10,2) DEFAULT NULL,
    `currency_code` VARCHAR(3) DEFAULT 'USD',
    `registration_date` DATE DEFAULT NULL,
    `last_sync` DATETIME DEFAULT NULL,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_whmcs_service` (`whmcs_service_id`),
    KEY `idx_bridge_user` (`bridge_user_id`),
    KEY `idx_whmcs_product` (`whmcs_product_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Group mapping table: Maps WHMCS product groups/statuses to Joomla user groups
CREATE TABLE IF NOT EXISTS `#__whmcsbridge_groupmaps` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `map_type` VARCHAR(50) NOT NULL DEFAULT 'product',
    `whmcs_identifier` VARCHAR(255) NOT NULL,
    `whmcs_name` VARCHAR(255) DEFAULT NULL,
    `joomla_group_id` INT NOT NULL,
    `priority` INT UNSIGNED DEFAULT 0,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL,
    `modified` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_map_type` (`map_type`),
    KEY `idx_whmcs_identifier` (`whmcs_identifier`),
    KEY `idx_joomla_group` (`joomla_group_id`),
    KEY `idx_published` (`published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Sync log table: Audit trail for all sync operations
CREATE TABLE IF NOT EXISTS `#__whmcsbridge_sync_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sync_type` VARCHAR(50) NOT NULL,
    `sync_direction` VARCHAR(20) DEFAULT 'whmcs_to_joomla',
    `started` DATETIME NOT NULL,
    `completed` DATETIME DEFAULT NULL,
    `total_records` INT UNSIGNED DEFAULT 0,
    `created_records` INT UNSIGNED DEFAULT 0,
    `updated_records` INT UNSIGNED DEFAULT 0,
    `failed_records` INT UNSIGNED DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'running',
    `error_details` TEXT,
    `initiated_by` INT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sync_type` (`sync_type`),
    KEY `idx_status` (`status`),
    KEY `idx_started` (`started`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

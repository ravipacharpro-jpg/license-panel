SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- MEXX SDK PANEL - Fresh Database Schema
-- --------------------------------------------------------
DROP TABLE IF EXISTS `key_app_connections`;
DROP TABLE IF EXISTS `key_devices`;
DROP TABLE IF EXISTS `logs`;
DROP TABLE IF EXISTS `referral_codes`;
DROP TABLE IF EXISTS `sdk_keys`;
DROP TABLE IF EXISTS `server_settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('owner','admin','reseller') NOT NULL DEFAULT 'reseller',
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `telegram_username` varchar(100) DEFAULT NULL,
  `telegram_chat_id` bigint(20) DEFAULT NULL,
  `is_tg_verified` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `status` enum('Active','Banned') NOT NULL DEFAULT 'Active',
  `referred_by_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_tenant_code` (`tenant_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sdk_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(50) NOT NULL,
  `created_by` int(11) NOT NULL,
  `sdk_key` varchar(100) NOT NULL,
  `duration_hours` int(11) NOT NULL DEFAULT 720,
  `device_limit` int(11) NOT NULL DEFAULT 1,
  `package_mode` enum('single','multi') NOT NULL DEFAULT 'single',
  `allowed_packages` text DEFAULT NULL,
  `status` enum('Unused','Active','Expired','Banned') NOT NULL DEFAULT 'Unused',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sdk_key` (`sdk_key`),
  KEY `idx_tenant_code` (`tenant_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `key_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `device_id` varchar(150) NOT NULL,
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key_device` (`key_id`,`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `key_app_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_id` int(11) NOT NULL,
  `package_name` varchar(150) NOT NULL,
  `app_name` varchar(150) NOT NULL,
  `status` enum('Active','Banned') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_app_connection` (`key_id`,`package_name`,`app_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(50) NOT NULL,
  `log_type` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `sdk_key` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `referral_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(50) NOT NULL,
  `created_by` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `role_assigned` enum('admin','reseller') NOT NULL DEFAULT 'reseller',
  `initial_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `server_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_code` varchar(50) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_setting_unique` (`tenant_code`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- SEED DATA: Default tenant "MEXX001" + new branding
-- --------------------------------------------------------
INSERT INTO `server_settings` (`tenant_code`, `setting_key`, `setting_value`) VALUES
('MEXX001', 'panel_name', '𝐅𝐄𝐀𝐓𝐔𝐑𝐄𝐒𝐓𝐈𝐂 𝐋𝐄𝐀𝐊𝐒'),
('MEXX001', 'panel_subtitle', 'SDK LICENSE MANAGEMENT'),
('MEXX001', 'maintenance_mode', '0'),
('MEXX001', 'success_message', 'Server is online'),
('MEXX001', 'notification_title', 'System Note'),
('MEXX001', 'notification_message', 'Welcome to Featurestic Leaks Panel!');

-- --------------------------------------------------------
-- SEED DATA: Owner/admin account
-- Username: mexxadmin
-- Password: Mexx@9ad515f2   <-- CHANGE THIS after first login (Settings page)
-- --------------------------------------------------------
INSERT INTO `users` (`tenant_code`, `username`, `email`, `password_hash`, `role`, `wallet_balance`, `status`) VALUES
('MEXX001', 'mexxadmin', 'admin@mexxpanel.local', '$2y$12$nNaBJofp7rIuG93gCXU/1eUqP.zyiupNnjELCqrn0JH3M0F3Iz/QW', 'owner', 0.00, 'Active');

-- --------------------------------------------------------
-- FOREIGN KEYS
-- --------------------------------------------------------
ALTER TABLE `key_app_connections`
  ADD CONSTRAINT `fk_app_conn_key_id` FOREIGN KEY (`key_id`) REFERENCES `sdk_keys` (`id`) ON DELETE CASCADE;

ALTER TABLE `key_devices`
  ADD CONSTRAINT `fk_key_id` FOREIGN KEY (`key_id`) REFERENCES `sdk_keys` (`id`) ON DELETE CASCADE;

COMMIT;

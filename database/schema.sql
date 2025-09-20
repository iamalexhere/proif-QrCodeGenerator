-- QR Generator with URL Shortener Database Schema
-- MySQL/MariaDB Compatible

-- Create database (run this first if database doesn't exist)
-- CREATE DATABASE qr_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE qr_generator;

-- Table to store shortened URLs
CREATE TABLE `short_urls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_url` text NOT NULL,
  `short_code` varchar(10) NOT NULL,
  `clicks` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `short_code_unique` (`short_code`),
  KEY `idx_short_code_active` (`short_code`, `is_active`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store click analytics
CREATE TABLE `url_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `short_url_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','bot') DEFAULT NULL,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_url_analytics_short_url` (`short_url_id`),
  KEY `idx_clicked_at` (`clicked_at`),
  KEY `idx_ip_address` (`ip_address`),
  CONSTRAINT `fk_url_analytics_short_url` FOREIGN KEY (`short_url_id`) REFERENCES `short_urls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store QR code generation history (optional)
CREATE TABLE `qr_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `short_url_id` bigint(20) unsigned DEFAULT NULL,
  `original_url` text NOT NULL,
  `qr_settings` json DEFAULT NULL, -- Store color, logo, size settings
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_qr_codes_short_url` (`short_url_id`),
  KEY `idx_generated_at` (`generated_at`),
  CONSTRAINT `fk_qr_codes_short_url` FOREIGN KEY (`short_url_id`) REFERENCES `short_urls` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for rate limiting
CREATE TABLE `rate_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(10) unsigned NOT NULL DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_request` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address_unique` (`ip_address`),
  KEY `idx_window_start` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample data for testing (optional)
-- INSERT INTO `short_urls` (`original_url`, `short_code`, `ip_address`) VALUES
-- ('https://example.com', 'test123', '127.0.0.1'),
-- ('https://google.com', 'google1', '127.0.0.1');

-- Create indexes for better performance
-- Already included above, but here are additional ones for large datasets:

-- Composite index for analytics queries
CREATE INDEX `idx_analytics_composite` ON `url_analytics` (`short_url_id`, `clicked_at`);

-- Index for active URLs
CREATE INDEX `idx_active_urls` ON `short_urls` (`is_active`, `created_at`);
CREATE DATABASE IF NOT EXISTS `cypwn`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `cypwn`;

CREATE TABLE IF NOT EXISTS `ipa` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `legacy_index` INT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `developer_name` VARCHAR(255) NOT NULL DEFAULT '',
  `subtitle` TEXT NULL,
  `category` VARCHAR(120) NOT NULL DEFAULT 'Other',
  `version` VARCHAR(100) NOT NULL DEFAULT '',
  `version_date` VARCHAR(100) NOT NULL DEFAULT '',
  `description` LONGTEXT NULL,
  `iconURL` VARCHAR(500) NOT NULL DEFAULT '',
  `downloadURL` VARCHAR(500) NOT NULL DEFAULT '',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `tool_type` ENUM('free','paid') NOT NULL DEFAULT 'free',
  `screenshots` JSON NULL,
  `icon_asset` VARCHAR(500) NOT NULL DEFAULT '',
  `screenshot_assets` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ipa_category` (`category`),
  INDEX `idx_ipa_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- trollStore table will be added later as requested.

-- CyPwn IPA Library - Database Schema
-- Local XAMPP: import this whole file (creates cypwn database).
-- cPanel / live server: use schema_tables_only.sql inside your existing database instead.

CREATE DATABASE IF NOT EXISTS cypwn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cypwn;

CREATE TABLE IF NOT EXISTS apps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_type ENUM('ipa', 'trollstore') NOT NULL DEFAULT 'ipa',
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(512) NOT NULL DEFAULT '',
    developer_name VARCHAR(255) NOT NULL DEFAULT '',
    subtitle VARCHAR(255) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT '',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    version VARCHAR(50) NOT NULL DEFAULT '',
    app_size VARCHAR(50) NOT NULL DEFAULT '',
    version_date DATE NULL,
    description TEXT,
    download_url VARCHAR(1024) NOT NULL DEFAULT '',
    screenshots JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_store_type (store_type),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

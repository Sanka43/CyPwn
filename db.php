<?php
declare(strict_types=1);

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('CYPWN_DB_HOST') ?: '127.0.0.1';
    $port = (int)(getenv('CYPWN_DB_PORT') ?: 3306);
    $dbName = getenv('CYPWN_DB_NAME') ?: 'cypwn';
    $user = getenv('CYPWN_DB_USER') ?: 'root';
    $pass = getenv('CYPWN_DB_PASS') ?: '';

    $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
    $bootstrap = new PDO($dsnNoDb, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $bootstrap->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', str_replace('`', '``', $dbName)));

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensureIpaTable($pdo);
    return $pdo;
}

function ensureIpaTable(PDO $pdo): void
{
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS ipa (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  legacy_index INT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  developer_name VARCHAR(255) NOT NULL DEFAULT '',
  subtitle TEXT NULL,
  category VARCHAR(120) NOT NULL DEFAULT 'Other',
  version VARCHAR(100) NOT NULL DEFAULT '',
  version_date VARCHAR(100) NOT NULL DEFAULT '',
  description LONGTEXT NULL,
  iconURL VARCHAR(500) NOT NULL DEFAULT '',
  downloadURL VARCHAR(500) NOT NULL DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  tool_type ENUM('free','paid') NOT NULL DEFAULT 'free',
  screenshots JSON NULL,
  icon_asset VARCHAR(500) NOT NULL DEFAULT '',
  screenshot_assets JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_category (category),
  INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $pdo->exec($sql);

    // Cleanup legacy column if older schema had detailURL.
    $columnStmt = $pdo->query("SHOW COLUMNS FROM ipa LIKE 'detailURL'");
    $column = $columnStmt ? $columnStmt->fetch() : false;
    if ($column) {
        $pdo->exec('ALTER TABLE ipa DROP COLUMN detailURL');
    }
}

function normalizeJsonArrayField($value): array
{
    if (is_array($value)) {
        return array_values(array_filter(array_map(static fn($item) => trim((string)$item), $value), static fn($item) => $item !== ''));
    }

    if (is_string($value) && trim($value) !== '') {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(static fn($item) => trim((string)$item), $decoded), static fn($item) => $item !== ''));
        }
    }

    return [];
}

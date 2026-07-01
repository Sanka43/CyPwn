-- Run once in phpMyAdmin → SQL tab (after schema_tables_only.sql import)
-- Login: username admin / password admin123
-- Change password after first login (see DEPLOY.md)

INSERT INTO admin_users (username, password_hash)
VALUES (
    'admin',
    '$2y$10$5vTt.2WO2TXTyUyhelV.5ulsIVWQrKs80ETNlsmaQM9alO/MapJEe'
);

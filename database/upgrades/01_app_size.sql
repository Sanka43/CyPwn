-- Existing database only: adds manual app size field (skip if already in schema_tables_only.sql)
ALTER TABLE apps
    ADD COLUMN app_size VARCHAR(50) NOT NULL DEFAULT '' AFTER version;

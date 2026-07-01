-- Existing database only: adds display order for apps
ALTER TABLE apps
    ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER category;

UPDATE apps SET sort_order = id * 10;

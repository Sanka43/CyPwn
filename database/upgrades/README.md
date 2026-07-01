# Database upgrades (existing sites only)

Fresh installs: use **`schema_tables_only.sql`** only — it already includes all columns.

Run these in phpMyAdmin **only if** you get errors about a missing column:

| File | When |
|------|------|
| `01_app_size.sql` | `app_size` column missing |
| `02_sort_order.sql` | `sort_order` column missing |

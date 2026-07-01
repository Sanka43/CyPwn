# CyPwn IPA Library — Hosting & backup guide

## Files to upload (production)

Upload everything **except**:

| Do not upload | Reason |
|---------------|--------|
| `config/database.local.php` | Create fresh on server with cPanel MySQL credentials |
| `.git/` | Not needed on server |
| `test/` | Dev/scrape only — production uses `assets/repo/` |
| `README.md`, `DEPLOY.md` | Optional (docs only) |
| `database/seed_admin.php` | Run once via CLI, then delete from server |
| `database/schema.sql` | Local XAMPP only (creates `cypwn` database) |
| Cursor temp images under `assets/c__*` | Not part of the app |

**Must upload:** `index.php`, `Packages.php`, `Packages.html`, `app.php`, `config/`, `includes/`, `admin/`, `api/`, `assets/` (including **`assets/repo/packages.json`** and **`assets/repo/repoimg/`** — ~951 icons), `partials/`, `database/schema_tables_only.sql`, `.htaccess`

---

## Step 1 — cPanel MySQL database

1. cPanel → **MySQL Databases**
2. Create database (e.g. `cypwbhnp_cypwn`)
3. Create user + strong password
4. **Add user to database** → All privileges

Note the **database name**, **username**, and **password**.

---

## Step 2 — Upload project

1. cPanel → **File Manager** → `public_html` (or subdomain folder)
2. Upload project files (ZIP extract or FTP)
3. Ensure `public_html/index.php` exists at site root

---

## Step 3 — Database config

1. Copy on server:

   `config/database.example.php` → `config/database.local.php`

2. Edit `config/database.local.php`:

```php
return [
    'host' => 'localhost',
    'name' => 'cypwbhnp_cypwn',      // your cPanel DB name
    'user' => 'cypwbhnp_dbuser',     // your cPanel DB user
    'pass' => 'your_strong_password',
];
```

3. Save. **Never share** this file publicly.

---

## Step 4 — Import tables

1. cPanel → **phpMyAdmin**
2. Select your database (`cypwbhnp_cypwn`)
3. **Import** → choose `database/schema_tables_only.sql` → Go
4. Confirm tables: `apps`, `admin_users`

---

## Step 5 — Create admin user (no Terminal)

1. phpMyAdmin → your database → **SQL** tab
2. Import or paste contents of **`database/create_admin.sql`** → **Go**
3. Login: `admin` / `admin123` — **change password after first login** (see HOSTING-GUIDE.md section H)

Do **not** upload `database/seed_admin.php` to production (Terminal-only script).

**Optional (Terminal only):** `php database/seed_admin.php`

Admin URL: `https://yourdomain.com/admin/login.php`

---

## Step 6 — Site config

Edit `config/site.php`:

- Announcement text and links
- Nav URLs (Discord, Repo, etc.)
- `base_path` → `''` if site is at domain root (e.g. `cypwn.com`)

**CyPwn Repo page:** `Packages.php` loads `assets/repo/packages.json` and icons from `assets/repo/repoimg/`. After upload, open `https://yourdomain.com/Packages.php` and confirm package cards show icons.

---

## Step 7 — Permissions

Ensure these folders are **writable** by PHP (755 or 775):

- `assets/ipa/icons/`
- `assets/ipa/screenshots/`
- `assets/trollstore/icons/`
- `assets/trollstore/screenshots/`

---

## Step 8 — Post-deploy security

- [ ] Delete `database/seed_admin.php` on server
- [ ] Change admin password (use a new user or update hash in DB)
- [ ] Confirm `config/database.local.php` is not downloadable
- [ ] Enable HTTPS in cPanel (SSL/TLS)
- [ ] Keep `database/.htaccess` (blocks web access to SQL tools)

---

## Database backup (kadima / backup)

### Method 1 — cPanel (easiest)

1. cPanel → **phpMyAdmin**
2. Select your database
3. **Export** tab
4. Format: **SQL**
5. Check **Add CREATE TABLE** and **Add DROP TABLE** (optional, for full restore)
6. Click **Export** → save `.sql` file to your PC

Schedule: weekly or before every update.

### Method 2 — cPanel Backup Wizard

1. cPanel → **Backup**
2. Download **MySQL Databases** backup (includes your DB)

### Method 3 — Command line (SSH)

```bash
mysqldump -u DB_USER -p DB_NAME > backup_2026-06-04.sql
```

Replace `DB_USER` and `DB_NAME` with cPanel values.

### Restore from backup

1. phpMyAdmin → select database
2. **Import** → choose your `.sql` backup file → Go

Or empty database first if you need a clean restore.

---

## Local XAMPP (development)

1. Import `database/schema.sql` (creates `cypwn`)
2. Copy `config/database.example.php` → `config/database.local.php` (root / empty password)
3. Run: `php database/seed_admin.php`
4. Open: `http://localhost/cypwn/`

---

## Upgrading an old database

If the site was deployed before `sort_order` or `app_size` existed, run scripts in `database/upgrades/` (see `database/upgrades/README.md`).

---

## Troubleshooting

| Error | Fix |
|-------|-----|
| `Table apps doesn't exist` | Import `schema_tables_only.sql` |
| `Configuration required` | Create `config/database.local.php` |
| 500 after deploy | Check cPanel **Errors**, PHP 8+, DB credentials |
| Uploads fail | Folder permissions on `assets/*/icons` and `screenshots` |

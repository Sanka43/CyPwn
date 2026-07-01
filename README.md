# CyPwn IPA Library

PHP + MySQL app library (IPA & TrollStore) with public store, admin CRUD, category filters, and drag-to-reorder.

## Quick start (local)

1. XAMPP: Apache + MySQL on
2. Import `database/schema.sql`
3. Copy `config/database.example.php` → `config/database.local.php` (defaults: `cypwn` / `root` / no password)
4. `php database/seed_admin.php` → login `admin` / `admin123`
5. Open `http://localhost/cypwn/`

## Production hosting

- **[HOSTING-GUIDE.md](HOSTING-GUIDE.md)** — A–Z cPanel guide (no Terminal)
- **[DEPLOY.md](DEPLOY.md)** — checklist, backup, troubleshooting

## Config

| File | Purpose |
|------|---------|
| `config/site.php` | Site name, banner, nav links |
| `config/database.local.php` | MySQL credentials (not in git) |

## Features

- Public store with IPA / TrollStore tabs and search
- Admin: add/edit/delete apps, category filter, reorder (updates public site)
- Uploads: `assets/ipa/` and `assets/trollstore/`
- CSRF + PDO + password hashing

## License

Private project.

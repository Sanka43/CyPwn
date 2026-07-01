# Hosting guide A–Z (cPanel, no Terminal)

මේ guide එක **Terminal / SSH නැතුව** cPanel + phpMyAdmin + File Manager පමණක් use කරලා site එක host කරන හැටි.

---

## A. මුලින්ම ඕනේ දේවල්

| Item | විස්තරය |
|------|---------|
| Web hosting | cPanel තියෙන shared hosting (PHP 8+, MySQL) |
| Domain | e.g. `cypwn.com` → `public_html` එකට point වෙලා |
| Project ZIP | PC එකේ `cypwn` folder එක |

---

## B. cPanel එකට login

Hosting provider එකෙන් ලැබෙන **cPanel URL**, **username**, **password** use කරලා login වෙන්න.

---

## C. MySQL database හදනවා

1. cPanel home → **MySQL® Databases** (හෝ **MySQL Databases**)
2. **Create New Database**
   - Name එකට ඔයා `cypwn` type කළත් cPanel ඉදිරියේ **prefix** එකතු කරයි  
   - Example: `cypwbhnp_cypwn` ← **මේ full name එකම** පස්සේ config එකේ දාන්න
3. **Create** click
4. **MySQL Users** section එකේ:
   - Username + **strong password** හදන්න (notepad එකේ save කරගන්න)
   - **Create User**
5. **Add User To Database**:
   - User + Database select කරන්න
   - **Add**
   - Privileges: **ALL PRIVILEGES** → **Make Changes**

**ලියාගන්න (වැදගත්):**

```
Database name:  cypwbhnp_cypwn    (ඔයාගේ එක වෙනස් වෙන්න පුළුවන්)
Database user:  cypwbhnp_xxxxx
Password:       ********
Host:           localhost
```

---

## D. Project files upload කරනවා

### D1. ZIP හදන්න (PC එකේ)

Folder එක compress කරන්න. ZIP එකට **මේවා ඇතුළත් නොකරන්න** (optional / security):

- `config/database.local.php` (local file — server එකේ අලුතින් හදනවා)
- `.git` folder

### D2. cPanel File Manager

1. **File Manager** → `public_html` open කරන්න  
   (subdomain නම් ඒ folder එක — e.g. `public_html/cypwn.com`)
2. පරන files තියෙනවා නම් backup කරලා clear කරන්න (අවශ්‍ය නම්)
3. **Upload** → ZIP file එක upload
4. ZIP එක select කරලා **Extract**
5. Extract වුණාම `index.php` **direct** `public_html` යට තියෙනවාද බලන්න:

```
public_html/
  index.php          ← තියෙන්න ඕනේ
  admin/
  config/
  assets/
  ...
```

ZIP එකේ ඇතුළේ extra folder එකක් (`cypwn/`) ආවොත් files එක පාරක් `public_html` එකට move කරන්න.

---

## E. Database config file (database.local.php)

1. File Manager → `public_html/config/`
2. `database.example.php` select → **Copy**
3. Copy එකේ name: `database.local.php`
4. `database.local.php` → **Edit**

```php
<?php

declare(strict_types=1);

return [
    'host' => 'localhost',
    'name' => 'cypwbhnp_cypwn',       // C section එකේ FULL database name
    'user' => 'cypwbhnp_xxxxx',       // C section එකේ database user
    'pass' => 'YOUR_PASSWORD_HERE',   // C section එකේ password
];
```

5. **Save Changes**

---

## F. Database tables import (phpMyAdmin)

1. cPanel → **phpMyAdmin**
2. වම් side එකේ **ඔයාගේ database** click (e.g. `cypwbhnp_cypwn`)
3. ඉහළ **Import** tab
4. **Choose File** → PC එකෙන් `database/schema_tables_only.sql` select
5. Scroll down → **Import** / **Go**
6. Success message එක එනවා
7. වම් side **Structure** — tables දෙක තියෙනවාද බලන්න:
   - `apps`
   - `admin_users`

Error ආවොත් file size limit — phpMyAdmin **SQL** tab එක open කරලා file එකේ text copy-paste කර run කරන්න පුළුවන්.

---

## G. Admin user හදනවා (Terminal නැතුව)

`database/seed_admin.php` web එකෙන් run වෙන්නේ නැහැ (security block). **phpMyAdmin** use කරන්න:

1. phpMyAdmin → database select → **SQL** tab
2. PC එකේ `database/create_admin.sql` file එක open කරලා contents copy කරන්න  
   (හෝ Import කරන්න)
3. **Go** click

Default login:

| Field | Value |
|-------|--------|
| URL | `https://yourdomain.com/admin/login.php` |
| Username | `admin` |
| Password | `admin123` |

**පළමු login එකෙන් පස්සේ password change කරන්න** (H section).

`database/seed_admin.php` server එකට upload කළා නම් **File Manager එකෙන් delete** කරන්න.

---

## H. Admin password change (phpMyAdmin)

### H1. නව password hash එක (local PC — XAMPP)

CMD / PowerShell:

```text
cd C:\xampp\php
php -r "echo password_hash('MyNewSecurePass99', PASSWORD_DEFAULT);"
```

Output එක copy කරන්න (`$2y$10$...` දිග string එක).

### H2. phpMyAdmin

1. Database → table `admin_users` → **Browse**
2. `admin` row → **Edit**
3. `password_hash` field එකට copy කළ hash එක paste
4. **Go** save

---

## I. Site settings (config/site.php)

File Manager → `config/site.php` → **Edit**

- Announcement text
- Nav links (Discord, Repo URLs)
- Domain root නම්: `'base_path' => ''`

Save.

---

## J. Folder permissions (uploads)

File Manager → folders select → **Permissions** (හෝ Change Permissions):

| Folder | Permission |
|--------|------------|
| `assets/ipa/icons` | 755 or 775 |
| `assets/ipa/screenshots` | 755 or 775 |
| `assets/trollstore/icons` | 755 or 775 |
| `assets/trollstore/screenshots` | 755 or 775 |

**Write** tick එක on නම් 775 හරි.

---

## K. SSL (HTTPS)

cPanel → **SSL/TLS Status** හෝ **Let's Encrypt** → domain එකට SSL enable  
පස්සේ site open: `https://cypwn.com`

---

## L. Site test කරනවා

| Test | URL |
|------|-----|
| Public store | `https://yourdomain.com/` |
| Admin | `https://yourdomain.com/admin/login.php` |

- Public page load වෙනවාද
- Admin login වෙනවාද
- App එකක් add කරලා icon upload වෙනවාද

---

## M. Database backup (kadima) — Terminal නැතුව

**සතියකට වරක් හෝ update එකකට පෙර:**

1. cPanel → **phpMyAdmin**
2. Database select (e.g. `cypwbhnp_cypwn`)
3. **Export** tab
4. Method: **Quick** හෝ **Custom**
5. Format: **SQL**
6. **Export** → `.sql` file PC එකට save

**Restore (අවශ්‍ය නම්):**

1. phpMyAdmin → database select
2. **Import** → backup `.sql` file → **Go**

**වෙන ක්‍රමය:** cPanel → **Backup** → Download **MySQL Databases** backup

---

## N. Security checklist

- [ ] `admin123` default password change කළා
- [ ] `database/seed_admin.php` server එකේ නැහැ (delete)
- [ ] `config/database.local.php` password strong
- [ ] HTTPS on
- [ ] Admin URL share නොකරන්න public එකට

---

## Troubleshooting

| ප්‍රශ්නය | විසඳුම |
|---------|--------|
| HTTP 500 | cPanel → **Errors** / Error Log බලන්න |
| `Table apps doesn't exist` | F section — `schema_tables_only.sql` import නැවත |
| `Configuration required` | E section — `database.local.php` නැහැ හෝ වැරදියි |
| Database connection failed | Database name **full** name ද? User database එකට add කළාද? |
| Upload fail | J section permissions |
| Login fail | G section SQL නැවත; hash වැරදිද බලන්න |

---

## Quick reference — file map on server

```
public_html/
  index.php
  .htaccess
  config/
    database.php          ← auto loads database.local.php
    database.local.php    ← ඔයා හදන file (credentials)
    site.php
  database/
    schema_tables_only.sql   ← import via phpMyAdmin
    create_admin.sql         ← admin user SQL
  admin/login.php
  assets/...
```

**Local PC database name:** `cypwn` (XAMPP)  
**cPanel database name:** සමහර විට `accountname_cypwn` — config එකේ **phpMyAdmin එකේ පෙන්වන exact name** දාන්න.

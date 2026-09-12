# MEXX SDK Panel — Ghost UI License-Key Panel

Fresh vanilla PHP + MySQLi panel with **ghost click FX** (phantom pop + synth ghost voice on every button, no audio files) and **3 themes** (Ghost Dark / Ghost Blue / Ghost Light, cookie-based, topbar sparkle switch).

## Features
- Login / Register / Forgot (Telegram OTP optional) / Logout
- Dashboard (key stats, expiration)
- License Keys: list, bulk generate (HWID limit, package binding), edit, ban/unban/delete
- Team (admin/reseller roles, wallet balance)
- Referral codes, Tenants (multi-tenant codes, default `MEXX001`)
- Server settings (branding, maintenance mode, API messages)
- Logs, built-in API tester (`/tester`)
- SDK validation API: `POST /api/connect/MEXX001` (game key + serial/device lock)
- Branding: edit `PANEL_NAME` in env / `app/db.php`

## Setup (cPanel/shared hosting)
1. MySQL DB + user banao, `sql.sql` import karo (admin auto-creates).
2. `app/db.php` me credentials **ya** `.env` file me `DB_HOST/DB_USER/DB_PASS/DB_NAME` set karo.
3. Upload to `public_html`, open `/login`.

Default login (change immediately!):
- User: `mexxadmin` / Pass: `Mexx@9ad515f2`
- Tenant: `MEXX001` (Android API: `/api/connect/MEXX001`)

## Local run (needs MySQL/MariaDB)
```bash
cp .env.example .env   # fill DB creds
php -S localhost:8000  # .htaccess nahi chalega yaha; use: php -S localhost:8000 index.php?uri=login
```
Note: `php -S` ignores `.htaccess`, so open routes via `index.php?uri=login` etc.

## Render deploy (Docker)
Render has **no managed MySQL** — external MySQL chahiye (Railway/Aiven/free host):
1. Push repo, Render → New Web Service → repo, Runtime Docker.
2. Env set karo: `DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS` (+ optional branding).
3. Us MySQL pe `sql.sql` import karo (admin row included).
4. Deploy → `/login` kholo.

## Structure
```
index.php (router) | auth/ | views/ | api/connect.php | app/db.php functions.php icons.php
assets/ghost-fx.js | sessions/ | sql.sql
```

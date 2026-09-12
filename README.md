# KURO Panel — Ghost Theme Edition

CodeIgniter 4 mod/key panel (keys, users, referrals, ESP feature toggles, modname, on/off maintenance, device-locked key validation API) with the **Ghost theme system**: 3 variants (Ghost Dark / Ghost Blue / Ghost Light) + ghost click FX (phantom pop + synth voice) panel-wide.

## Ghost theme
- Switcher in navbar (stars icon) + persists via `ghost_theme` cookie
- Files: `app/Views/Layout/Ghost.php` (CSS vars + switcher styles), `assets/ghost-fx.js` (click FX, auto-tints from `--accent-3`)
- Layout wired in `app/Views/Layout/Starter.php` + `app/Views/Layout/Header.php`

## Setup (cPanel/shared hosting)
1. MySQL DB banao, `/storage` me di `ownerpanel.sql` jaisi schema import karo (tables: users, keys_code, referral_code, history, Feature, modname, onoff, lib, _ftext).
2. `.env` me DB + `app.baseURL` set karo (ya `app/db.php` conn + `app/Config/Database.php`).
3. Upload, open `/login`. Admin user DB me banao (bcrypt via `password_hash`).

## Local run (needs MySQL/MariaDB + PHP 8.0/8.1)
```bash
# DB
mysql -u root -e "CREATE DATABASE kuro_panel"
mysql -u root kuro_panel < ownerpanel.sql
# admin pass reset:
php -r '$h=password_hash("admin123",PASSWORD_BCRYPT); echo $h;'
mysql -u root kuro_panel -e "UPDATE users SET password='<hash>' WHERE username='admin'"
# serve (docroot = public/)
php -S localhost:8080 -t public
```

## Render deploy (Docker, external MySQL)
Render has **no managed MySQL** — Railway/Aiven/free host use karo:
1. Push, Render → New Web Service → repo, Runtime Docker.
2. Env: `DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS`, `APP_BASE_URL=https://<app>.onrender.com/`, `CI_ENVIRONMENT=production`.
3. Import schema into that MySQL, create admin.
4. Deploy → `/login`.

## Key validation API (mod menus)
`POST /connect` with `game`, `user_key`, `serial` → `{status, data:{real, token, modname, ESP/Item/AIM..., expired_date, device...}}`. Maintenance mode via `onoff` table.

## Structure
```
public/index.php (front) | app/Controllers (Auth/Keys/User/Connect) | app/Views (Layout inc. Ghost, Auth, Keys, Admin, User, Server) | app/Models | assets/ghost-fx.js
```

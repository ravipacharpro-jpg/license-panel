# KURO Panel — Ghost Theme Edition (SQLite, zero-config)

CodeIgniter 4 mod/key panel (keys, users, referrals, ESP feature toggles, modname, on/off maintenance, device-locked key validation API) with the **Ghost theme system**: 3 variants (Ghost Dark / Ghost Blue / Ghost Light) + ghost click FX (phantom pop + synth voice) panel-wide.

Runs on **SQLite by default** — no MySQL needed. DB file auto-creates at `writable/kuro.sqlite` on first run (schema + admin seed included).

## Ghost theme
- Switcher in navbar (stars icon) + persists via `ghost_theme` cookie
- Files: `app/Views/Layout/Ghost.php` (CSS vars + switcher styles), `assets/ghost-fx.js` (click FX, auto-tints from `--accent-3`)
- Layout wired in `app/Views/Layout/Starter.php` + `app/Views/Layout/Header.php`

## Setup (cPanel/shared hosting, PHP 8.0/8.1 + sqlite3)
1. Upload files, open `/login`. DB + admin auto-create.
2. Default login (change immediately!): `admin` / `admin123`
3. For external MySQL instead: set `DB_DRIVER=mysql` + `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS` (env or `.env`), import `sql/mysql.sql`, then insert an admin row (bcrypt of md5-salted password, see local run).

## Local run
```bash
php -S localhost:8080 -t .   # docroot = repo ROOT
# open http://localhost:8080/login  (admin / admin123)
```

## Render deploy (Docker, no external DB needed)
1. Push, Render → New Web Service → repo, Runtime Docker.
2. Env: `APP_BASE_URL=https://<app>.onrender.com/`, `CI_ENVIRONMENT=production`.
3. Deploy → `/login`. SQLite file auto-creates (note: Render free disk is ephemeral — redeploys reset data; attach a Disk at `/var/www/html/writable` for persistence).

## Key validation API (mod menus)
`POST /connect` with `game`, `user_key`, `serial` → `{status, data:{real, token, modname, ESP/Item/AIM..., expired_date, device...}}`. Maintenance mode via `onoff` table.

## Structure
```
index.php (front) | app/Controllers (Auth/Keys/User/Connect) | app/Views (Layout inc. Ghost, Auth, Keys, Admin, User, Server) | app/Models | assets/ghost-fx.js | sql/sqlite.sql
```

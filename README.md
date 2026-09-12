# NEXUS License Panel — Mod Panel (MultiPanelX features, glass UI)

Self-hosted mod + license selling panel. Same features as MultiPanelX reference, same dark neon glass UI.

## Features
- Landing + mods showcase + Login/Signup glass cards
- Dashboard (same glass UI, tab system):
  - ADMIN/OWNER: overview + 7-day Chart.js analytics, Manage Mods, Manage Plans (minutes/hours/days/months/lifetime), License Keys (bulk generate → available pool, block/unblock/expire/delete), Orders (UPI UTR approve → auto key), APK upload, Transactions ledger, Manage Clients + direct balance-add, Referral Codes (signup tokens), API Docs, My Profile, Site Settings
  - USER: overview + chart, Store (plans + UPI QR modal + UTR order, available-keys wallet buy, purchased vault), My Orders, Wallet (balance + history, top-up via admin), Downloads (purchased APKs), API Docs, My Profile
- Store flow: plan → UPI QR (`upi://pay`) → UTR submit → pending → admin Approve = key auto-create + assign
- Wallet: admin direct credit (balance-add), instant wallet purchase of available keys
- Keys: mod-based, statuses available/sold/blocked/expired, single device lock
- API:
  - `GET /api.php?key=XXX&device_id=YYY` → `{status:success/error, message, data:{mod_name,duration,sold_at,device_id}}`
  - `POST /api.php api_key+action=block/unblock/expire/delete/edit` (remote admin)
- Extras: global search (Ctrl+K), Chart.js analytics, profile/password, site settings (name/tagline/telegram/support/UPI)
- DB: SQLite default (zero-config) / MySQL via env, PDO + auto-migrate
- Deploy: Dockerfile (php:8.2-apache) + render.yaml

## Quick local run
```bash
cp .env.example .env
php -S localhost:8000 -t public
# open http://localhost:8000
# pehla signup = auto OWNER
```
Owner CLI:
```bash
php scripts/create_owner.php "Owner" "owner@mail.com" "pass123"
```

## Typical setup (admin)
1. Login → Manage Mods → Add mod (e.g. BGMI ESP v2.1)
2. Manage Plans → Add plan (mod + 30 days + ₹299)
3. APKs → upload APK for mod
4. Site Settings → UPI ID + branding save
5. User Store se Buy → UTR → Orders → Approve = key auto-gen
6. User: Downloads se APK + keys copy → app me `GET /api.php?key=&device_id=` verify

## Render live (Docker)
1. Push to GitHub, Render → New → Web Service → repo
2. Runtime Docker, Dockerfile `./Dockerfile`
3. Env: `DB_DRIVER=sqlite`, `APP_URL=https://YOUR.onrender.com`
4. Deploy. SQLite `/var/www/html/data/database.sqlite` me banega.
> Free Render pe uploads/SQLite redeploy par reset ho sakte hain. Permanent ke liye Disk ya MySQL (`DB_DRIVER=mysql` + host/name/user/pass).

## API examples
```bash
curl "http://localhost:8000/api.php?key=XXXX-XXXX&device_id=DEV123"
curl -X POST http://localhost:8000/api.php -d 'api_key=SECRET&action=block&key_id=12'
```

## Structure
```
public/index.php login.php signup.php dashboard.php actions.php api.php download.php assets/
src/db.php helpers.php auth.php
config/schema.sql
Dockerfile render.yaml .env.example
```

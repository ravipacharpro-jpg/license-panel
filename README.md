# NEXUS License Panel — Premium PHP License + Wallet + Referral + Mods Store

Dark neon glassmorphism UI (same). Andar MultiPanelX-style full features: mods, plans, store UPI orders, APK downloads, referral tokens, charts, global search.

## Features (UI same, features MultiPanelX jaisi)
- Landing (particles + gradient) + available-mods showcase + Login/Signup glass cards
- Dashboard role-based (same glass UI, tab system):
  - OWNER/ADMIN: overview + 7-day Chart.js analytics, mods CRUD, plans (minutes/hours/days/months/lifetime), license keys (generic + mod keys, available/sold/blocked/expired), orders approve (UTR verify → auto key generate), topups approve, users + direct balance-add, signup tokens, APK upload, logs, API docs, profile, settings
  - RESELLER/USER: overview + chart, Store (plans grid + UPI QR modal + UTR submit), My Orders, Keys vault, Wallet (QR topup), Downloads (purchased APKs), Referral commission + link, Logs, API docs, Profile
- Store flow: plan → UPI QR (`upi://pay`) → UTR submit → pending → admin Approve = key auto-create + assign
- Wallet: UPI QR topup request → approve + referral commission
- Referral: commission link + admin signup tokens (gated registration optional)
- Keys: generic (1day/7days/30days/lifetime) + mod keys (numeric duration, sold_to/sold_at, device_id single-lock + hwid multi-limit)
- API:
  - `GET /api.php?key=XXX&device_id=YYY` → `{status:success/error, message, data:{mod_name,duration,sold_at,device_id}}` (MultiPanelX compatible)
  - `POST /api/validate.php {"key","hwid"}` → `{valid:true/false}` (old clients)
  - `POST /api.php api_key+action=block/unblock/expire/delete/edit` (remote admin)
- Extras: global search (Ctrl+K), Chart.js analytics, profile/password, site settings (name/tagline/telegram/support/UPI/api key/signup-token toggle)
- DB: SQLite default (zero-config) / MySQL via env, PDO + auto-migrate (old DBs alter-safe)
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
1. Login → Mods → Add mod (e.g. BGMI ESP v2.1)
2. Plans → Add plan (mod + 30 days + ₹299)
3. Downloads/APKs → upload APK for mod
4. Settings → UPI ID + branding save
5. Share Store link; user Buy → UTR → Orders → Approve = key auto-gen
6. User: Downloads se APK + My Keys se key copy → app me `GET /api.php?key=&device_id=` verify

## Render live (Docker)
1. Push to GitHub, Render → New → Web Service → repo
2. Runtime Docker, Dockerfile `./Dockerfile`
3. Env: `DB_DRIVER=sqlite`, `APP_URL=https://YOUR.onrender.com`
4. Deploy. SQLite `/var/www/html/data/database.sqlite` me banega.
> Free Render pe uploads/SQLite redeploy par reset ho sakte hain. Permanent ke liye Disk ya MySQL (`DB_DRIVER=mysql` + host/name/user/pass).

## API examples
```bash
curl "http://localhost:8000/api.php?key=XXXX-XXXX&device_id=DEV123"
curl -X POST http://localhost:8000/api/validate.php -H "Content-Type: application/json" -d '{"key":"XXXX","hwid":"DEV123"}'
curl -X POST http://localhost:8000/api.php -d 'api_key=SECRET&action=block&key_id=12'
```

## Structure
```
public/index.php login.php signup.php dashboard.php actions.php api.php api/validate.php download.php assets/
src/db.php helpers.php auth.php
config/schema.sql
Dockerfile render.yaml .env.example
```

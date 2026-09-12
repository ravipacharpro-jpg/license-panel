# NEXUS License Panel — Premium PHP License + Wallet + Referral

Dark neon glassmorphism panel. Owner / Admin / Reseller roles. Key engine + HWID lock + wallet top-up + referral commission + logs + `POST /api/validate`.

## Features
- Landing (particles + gradient) + Login/Signup glass cards
- Dashboard role-based:
  - OWNER: analytics, all users, pricing, revenue, ban/unban, role change, topup approve
  - ADMIN: key gen/revoke, resellers, logs, topup approve
  - RESELLER: wallet, buy keys, UPI topup request, referral link + commission, sales history
- Keys: 1day/7days/30days/lifetime, device limit, expires_at, active/expired/revoked
- API: `POST /api/validate` `{"key":"...","hwid":"..."}` → `{valid:true/false}`
- DB: SQLite default (zero-config) / MySQL via env, PDO + auto-migrate
- Deploy: Dockerfile (php:8.2-apache) + render.yaml

## Quick local run
```bash
cp .env.example .env
php -S localhost:8000 -t public
# open http://localhost:8000
# pehla signup = auto OWNER
```
Owner CLI se banana ho:
```bash
php scripts/create_owner.php "Owner" "owner@mail.com" "pass123"
```

## GitHub push
```bash
cd license-panel
git init
git add .
git commit -m "license panel v1"
git branch -M main
git remote add origin https://github.com/YOURNAME/license-panel.git
git push -u origin main
```

## Render live (Docker)
1. GitHub repo push karo
2. Render.com → New → Web Service → repo select
3. Runtime: Docker, Dockerfile: `./Dockerfile`
4. Env: `DB_DRIVER=sqlite`, `APP_URL=https://YOUR.onrender.com`
5. Deploy → live! SQLite file `/var/www/html/data/database.sqlite` me banega.
> Note: Free Render pe redeploy par SQLite reset ho sakta hai. Permanent chahiye to Render Disk lagao ya MySQL env use karo (`DB_DRIVER=mysql` + DB_HOST/DB_NAME/DB_USER/DB_PASS).

## API example
```bash
curl -X POST http://localhost:8000/api/validate.php \
 -H "Content-Type: application/json" \
 -d '{"key":"ABCD-EFGH-IJKL-MNOP","hwid":"DEV123"}'
```

## Structure
```
public/index.php login.php signup.php dashboard.php actions.php api/validate.php assets/
src/db.php helpers.php auth.php
config/schema.sql
Dockerfile render.yaml .env.example
```

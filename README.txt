MEXX SDK PANEL — Setup Guide
=============================

Ye ek fresh, 100% original, non-encrypted PHP license-key admin panel hai.
Koi hidden/obfuscated code nahi — sab kuch transparent aur editable hai.

Panel branding: FEATURESTIC LEAKS (edit app/db.php > PANEL_NAME to change)

STEP 1 — Database
------------------
1. cPanel / hosting me MySQL Database + User banao.
2. phpMyAdmin me `sql.sql` file import karo — isse tables aur admin account
   apne aap ban jayenge.

STEP 2 — Configure app/db.php
-------------------------------
`app/db.php` file khol ke ye 3 cheezein apni actual hosting details se badlo:
  - $username  -> tumhara MySQL DB username
  - $password  -> tumhara MySQL DB password
  - $database  -> tumhara MySQL DB name

STEP 3 — Telegram Bot (optional, OTP/forgot-password ke liye)
----------------------------------------------------------------
1. Telegram pe @BotFather se naya bot banao.
2. Uska token `app/db.php` me TELEGRAM_BOT_TOKEN me daalo.

STEP 4 — Upload & Login
-------------------------
Sab files apne hosting root (public_html) me upload karo.
Login page: https://yourdomain.com/login

DEFAULT LOGIN CREDENTIALS
---------------------------
Username: mexxadmin
Password: Mexx@9ad515f2

⚠️ IMPORTANT: Login karte hi "My Settings" page se ye password turant badal do.

TENANT CODE
------------
Default tenant code: MEXX001
Ye code apne Android app ke API calls me use karna hoga
(/api/connect/MEXX001)

FEATURES INCLUDED
-------------------
- 3 selectable color themes (Nebula Purple, Cyber Emerald, Crimson Vortex)
  — switch from the topbar sparkle icon inside the panel, or from the
  color dots on the login page. Applies panel-wide via cookie.
- Login / Register / Forgot Password (Telegram OTP)
- License Key generation (bulk, HWID limit, single/multi package binding)
- Key ban/unban/delete
- Team management (admin/reseller roles, wallet balance)
- Referral codes system
- Server settings (panel branding, maintenance mode, custom API messages)
- Event/threat logs
- Built-in API tester
- Multi-tenant support (owner can create more tenant codes)
- SDK validation API at /api/connect

Sab code plain PHP + MySQLi hai, koi third-party paid dependency nahi.

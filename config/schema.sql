-- License Panel Schema (SQLite default, MySQL compatible via db.php transform)
-- Tables: users, license_keys, transactions, logs, settings
-- + MultiPanelX features: mods, mod_plans, mod_apks, referral_tokens

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'reseller',
  wallet_balance REAL NOT NULL DEFAULT 0,
  referral_code TEXT UNIQUE,
  referred_by INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS license_keys (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  key_string TEXT NOT NULL UNIQUE,
  created_by INTEGER NOT NULL,
  assigned_to TEXT NULL,
  duration_type TEXT NOT NULL DEFAULT '30days',
  expires_at DATETIME NULL,
  device_limit INTEGER NOT NULL DEFAULT 1,
  hwid TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mod_id INTEGER NULL,
  price REAL NOT NULL DEFAULT 0,
  sold_to INTEGER NULL,
  sold_at DATETIME NULL,
  device_id TEXT NULL,
  duration INTEGER NULL
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL DEFAULT 'topup',
  amount REAL NOT NULL DEFAULT 0,
  reference TEXT NULL,
  status TEXT NOT NULL DEFAULT 'completed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  plan_id INTEGER NULL,
  upi_txn_id TEXT NULL
);

CREATE TABLE IF NOT EXISTS logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  action TEXT NOT NULL,
  target_key_id INTEGER NULL,
  details TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
  k TEXT PRIMARY KEY,
  v TEXT NOT NULL
);

-- MultiPanelX: mods catalog
CREATE TABLE IF NOT EXISTS mods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT NULL,
  image_url TEXT NULL,
  version TEXT NULL,
  features TEXT NULL,
  purchase_link TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- MultiPanelX: per-mod pricing plans (hours/days/months/minutes/lifetime)
CREATE TABLE IF NOT EXISTS mod_plans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mod_id INTEGER NOT NULL,
  plan_name TEXT NOT NULL,
  duration INTEGER NOT NULL DEFAULT 30,
  duration_type TEXT NOT NULL DEFAULT 'days',
  price REAL NOT NULL DEFAULT 0,
  features TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- MultiPanelX: APK files per mod
CREATE TABLE IF NOT EXISTS mod_apks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mod_id INTEGER NOT NULL,
  file_name TEXT NOT NULL,
  file_path TEXT NOT NULL,
  file_size INTEGER NOT NULL DEFAULT 0,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- MultiPanelX: signup referral tokens (gated registration)
CREATE TABLE IF NOT EXISTS referral_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  created_by INTEGER NOT NULL,
  expires_at DATETIME NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

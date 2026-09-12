-- License Panel Schema (SQLite default, MySQL compatible via db.php transform)
-- Tables: users, license_keys, transactions, logs, settings

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
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL DEFAULT 'topup',
  amount REAL NOT NULL DEFAULT 0,
  reference TEXT NULL,
  status TEXT NOT NULL DEFAULT 'completed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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

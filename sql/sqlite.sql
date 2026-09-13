-- KURO Panel schema (SQLite). Auto-created on first run if writable/kuro.sqlite is missing.
-- Admin login: admin / admin123 (change immediately after login!)

CREATE TABLE IF NOT EXISTS credit (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL DEFAULT ''
);
INSERT OR IGNORE INTO credit (id, name) VALUES (1, '');

CREATE TABLE IF NOT EXISTS Feature (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ESP TEXT NOT NULL DEFAULT 'on',
  Item TEXT NOT NULL DEFAULT 'on',
  SilentAim TEXT NOT NULL DEFAULT 'on',
  AIM TEXT NOT NULL DEFAULT 'on',
  BulletTrack TEXT NOT NULL DEFAULT 'on',
  Memory TEXT NOT NULL DEFAULT 'on',
  Floating TEXT NOT NULL DEFAULT 'on',
  Setting TEXT NOT NULL DEFAULT 'on'
);
INSERT OR IGNORE INTO Feature (id, ESP, Item, SilentAim, AIM, BulletTrack, Memory, Floating, Setting) VALUES
(1, 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on');

CREATE TABLE IF NOT EXISTS history (
  id_history INTEGER PRIMARY KEY AUTOINCREMENT,
  keys_id TEXT DEFAULT NULL,
  user_do TEXT DEFAULT NULL,
  info TEXT NOT NULL DEFAULT '',
  created_at TEXT DEFAULT NULL,
  updated_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS keys_code (
  id_keys INTEGER PRIMARY KEY AUTOINCREMENT,
  game TEXT NOT NULL DEFAULT '',
  user_key TEXT DEFAULT NULL,
  duration INTEGER DEFAULT NULL,
  expired_date TEXT DEFAULT NULL,
  max_devices INTEGER DEFAULT NULL,
  devices TEXT DEFAULT NULL,
  status INTEGER DEFAULT 1,
  registrator TEXT DEFAULT NULL,
  created_at TEXT DEFAULT NULL,
  updated_at TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_keys_user_key ON keys_code(user_key);

CREATE TABLE IF NOT EXISTS lib (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  file TEXT NOT NULL DEFAULT '',
  file_type TEXT NOT NULL DEFAULT '',
  file_size TEXT NOT NULL DEFAULT '',
  time TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS modname (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  modname TEXT NOT NULL DEFAULT 'VIP MOD'
);
INSERT OR IGNORE INTO modname (id, modname) VALUES (1, 'VIP MOD');

CREATE TABLE IF NOT EXISTS onoff (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  status TEXT NOT NULL DEFAULT 'off',
  myinput TEXT NOT NULL DEFAULT ''
);
INSERT OR IGNORE INTO onoff (id, status, myinput) VALUES (1, 'off', '');

CREATE TABLE IF NOT EXISTS referral_code (
  id_reff INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL DEFAULT '',
  Referral TEXT NOT NULL DEFAULT '',
  level INTEGER NOT NULL DEFAULT 0,
  set_saldo INTEGER NOT NULL DEFAULT 0,
  used_by TEXT NOT NULL DEFAULT '',
  created_by TEXT NOT NULL DEFAULT 'Owner',
  created_at TEXT DEFAULT NULL,
  updated_at TEXT DEFAULT NULL,
  acc_expiration TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS users (
  id_users INTEGER PRIMARY KEY AUTOINCREMENT,
  fullname TEXT DEFAULT NULL,
  username TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL DEFAULT '',
  reset_link_token TEXT NOT NULL DEFAULT '',
  exp_date TEXT NOT NULL DEFAULT '',
  level INTEGER NOT NULL DEFAULT 3,
  saldo INTEGER DEFAULT 0,
  status INTEGER DEFAULT 1,
  uplink TEXT DEFAULT NULL,
  password TEXT NOT NULL DEFAULT '',
  user_ip TEXT NOT NULL DEFAULT '',
  created_at TEXT DEFAULT NULL,
  updated_at TEXT DEFAULT NULL,
  expiration_date TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username_email ON users(username, email);
-- Default owner: admin / admin123 (CHANGE AFTER FIRST LOGIN)
INSERT OR IGNORE INTO users (id_users, fullname, username, email, reset_link_token, exp_date, level, saldo, status, uplink, password, user_ip, created_at, updated_at, expiration_date) VALUES
(1, 'admin', 'admin', 'admin@local', '', datetime('now'), 1, 0, 1, 'Owner', '$2y$08$JApvU6WURH6m6g5fIjztM.q5i5OsmwxIwUjriRkNB/klbxNTulJIi', '127.0.0.1', datetime('now'), datetime('now'), '2050-01-01 00:00:00');

CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  `key` TEXT NOT NULL UNIQUE,
  `value` TEXT NOT NULL DEFAULT '',
  updated_at TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_settings_key ON settings(`key`);
INSERT OR IGNORE INTO settings (`key`, `value`) VALUES ('auto_referral', '0');
INSERT OR IGNORE INTO settings (`key`, `value`) VALUES ('owner_referral_code', '');
INSERT OR IGNORE INTO settings (`key`, `value`) VALUES ('owner_saldo', '999999999');

CREATE TABLE IF NOT EXISTS _ftext (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  _status TEXT NOT NULL DEFAULT 'Safe',
  _ftext TEXT NOT NULL DEFAULT 'MOD STATUS :- 100% SAFE'
);
INSERT OR IGNORE INTO _ftext (id, _status, _ftext) VALUES (1, 'Safe', 'MOD STATUS :- 100% SAFE');

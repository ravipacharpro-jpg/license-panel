<?php
// src/db.php - PDO connection (SQLite default, MySQL via env) + auto-migrate

function env_val($key, $default = null) {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    // try ../.env file (project root)
    static $dotenv = null;
    if ($dotenv === null) {
        $dotenv = [];
        $paths = [__DIR__ . '/../.env', __DIR__ . '/../../.env', dirname(__DIR__) . '/.env'];
        foreach ($paths as $p) {
            if (is_file($p)) {
                $lines = file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') continue;
                    $pos = strpos($line, '=');
                    if ($pos === false) continue;
                    $k = trim(substr($line, 0, $pos));
                    $val = trim(substr($line, $pos + 1), " \t\"'");
                    $dotenv[$k] = $val;
                }
                break;
            }
        }
    }
    return $dotenv[$key] ?? $default;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $driver = strtolower((string)env_val('DB_DRIVER', 'sqlite'));

    if ($driver === 'mysql') {
        // Support DATABASE_URL (mysql://user:pass@host:port/db) or discrete vars
        $url = env_val('DATABASE_URL', '');
        if ($url !== '' && str_starts_with($url, 'mysql://')) {
            $parts = parse_url($url);
            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 3306;
            $db   = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? '';
            $pass = $parts['pass'] ?? '';
        } else {
            $host = env_val('DB_HOST', 'localhost');
            $port = env_val('DB_PORT', '3306');
            $db   = env_val('DB_NAME', 'license_panel');
            $user = env_val('DB_USER', 'root');
            $pass = env_val('DB_PASS', '');
        }
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        migrate_mysql($pdo);
        seed_settings($pdo);
        return $pdo;
    }

    // ---- SQLite (default, perfect for Render quick deploy) ----
    $defaultPath = dirname(__DIR__) . '/data/database.sqlite';
    // On Render with Dockerfile, /var/www/html/data is writable
    $sqlitePath = env_val('SQLITE_PATH', $defaultPath);
    // Fallback: if configured path not writable (local dev), use project data dir
    $dir = dirname($sqlitePath);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!is_dir($dir) || !is_writable($dir)) {
        $sqlitePath = $defaultPath;
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
    }
    $needInit = !is_file($sqlitePath);
    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL;');
    $pdo->exec('PRAGMA foreign_keys=ON;');
    // Always run CREATE IF NOT EXISTS (safe)
    $schemaFile = __DIR__ . '/../config/schema.sql';
    if (is_file($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
    }
    seed_settings($pdo);
    return $pdo;
}

function migrate_mysql(PDO $pdo): void {
    // MySQL DDL (AUTOINCREMENT -> AUTO_INCREMENT)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      role VARCHAR(20) NOT NULL DEFAULT 'reseller',
      wallet_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
      referral_code VARCHAR(32) UNIQUE,
      referred_by INT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS license_keys (
      id INT AUTO_INCREMENT PRIMARY KEY,
      key_string VARCHAR(64) NOT NULL UNIQUE,
      created_by INT NOT NULL,
      assigned_to VARCHAR(255) NULL,
      duration_type VARCHAR(20) NOT NULL DEFAULT '30days',
      expires_at DATETIME NULL,
      device_limit INT NOT NULL DEFAULT 1,
      hwid TEXT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      type VARCHAR(20) NOT NULL DEFAULT 'topup',
      amount DECIMAL(12,2) NOT NULL DEFAULT 0,
      reference VARCHAR(255) NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'completed',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NULL,
      action VARCHAR(100) NOT NULL,
      target_key_id INT NULL,
      details TEXT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      k VARCHAR(100) PRIMARY KEY,
      v TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function seed_settings(PDO $pdo): void {
    $defaults = [
        'price_1day' => '49',
        'price_7days' => '149',
        'price_30days' => '299',
        'price_lifetime' => '999',
        'referral_percent' => '10',
        'app_name' => 'NEXUS License Panel',
        'upi_id' => 'owner@upi',
    ];
    // detect driver for upsert syntax
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    foreach ($defaults as $k => $v) {
        $st = $pdo->prepare('SELECT v FROM settings WHERE k = ?');
        $st->execute([$k]);
        if (!$st->fetch()) {
            if ($driver === 'mysql') {
                $ins = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?)');
            } else {
                $ins = $pdo->prepare('INSERT OR IGNORE INTO settings (k, v) VALUES (?, ?)');
            }
            $ins->execute([$k, $v]);
        }
    }
}

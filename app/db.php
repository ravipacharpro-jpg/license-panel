<?php
// app/db.php - Database Connection & Global Configuration
// MEXX SDK PANEL - Fresh Build

/* =========================================================================
   1. GLOBAL CONFIGURATION
========================================================================= */

// Helper: read env (Render/cPanel) with fallback. Also loads ../.env file if present.
function mexx_env(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
    static $file = null;
    if ($file === null) {
        $file = [];
        $p = __DIR__ . '/../.env';
        if (is_file($p)) {
            foreach (file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                $file[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1), " \t\"'");
            }
        }
    }
    return $file[$key] ?? $default;
}

// --- Telegram Bot (create your own bot via @BotFather and paste the token) ---
define('TELEGRAM_BOT_TOKEN', mexx_env('TELEGRAM_BOT_TOKEN', 'PASTE_YOUR_NEW_BOT_TOKEN_HERE'));
define('TELEGRAM_BOT_URL', mexx_env('TELEGRAM_BOT_URL', 'https://t.me/YourMexxBot'));

// --- Panel Branding ---
define('PANEL_NAME', mexx_env('PANEL_NAME', '𝐅𝐄𝐀𝐓𝐔𝐑𝐄𝐒𝐓𝐈𝐂 𝐋𝐄𝐀𝐊𝐒'));
define('PANEL_SUBTITLE', mexx_env('PANEL_SUBTITLE', 'SDK LICENSE MANAGEMENT'));

/* =========================================================================
   2. DATABASE CREDENTIALS
   Local: copy .env.example to .env. cPanel: edit values. Render: set env vars.
========================================================================= */

class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    public $conn;

    public function __construct() {
        $this->host     = mexx_env('DB_HOST', 'localhost');
        $this->username = mexx_env('DB_USER', 'parallax_stmvfwaf');
        $this->password = mexx_env('DB_PASS', 'PASTE_YOUR_DB_PASSWORD_HERE');
        $this->database = mexx_env('DB_NAME', 'parallax_stmvfwaf');
        $this->port     = (int)mexx_env('DB_PORT', '3306');
    }

    public function getConnection() {
        $this->conn = null;

        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        try {
            $this->conn = @new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
            if ($this->conn->connect_error) {
                throw new Exception($this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Throwable $e) {
            die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
        return $this->conn;
    }
}
?>

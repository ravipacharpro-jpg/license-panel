<?php
// conn.php - SQLite backend (zero-config, Render-friendly).
// Auto-creates writable/kuro.sqlite from sql/sqlite.sql on first run.
// Override path via SQLITE_PATH env. Old mysqli call sites must use kq()/KRes.
if (!function_exists('kenv')) {
    function kenv($k, $d = '') {
        $v = getenv($k);
        if ($v !== false && $v !== '') return $v;
        if (isset($_ENV[$k]) && $_ENV[$k] !== '') return (string)$_ENV[$k];
        return $d;
    }
}
if (!class_exists('KRes')) {
    class KRes {
        public $rows = [];
        public $pos = 0;
        public $affected = 0;
        public function __construct($rows = [], $affected = 0) { $this->rows = $rows; $this->affected = $affected; }
        public function fetch_assoc() { return $this->pos < count($this->rows) ? $this->rows[$this->pos++] : null; }
        public function fetch_array() {
            $r = $this->fetch_assoc();
            if ($r === null) return null;
            return array_merge(array_values($r), $r);
        }
        public function num_rows() { return count($this->rows); }
    }
}
if (!function_exists('kdb')) {
    function kdb() {
        static $pdo = null;
        if ($pdo) return $pdo;
        $d = __DIR__;
        $cands = [
            $d . '/writable/kuro.sqlite',
            dirname($d) . '/writable/kuro.sqlite',
            dirname($d, 2) . '/writable/kuro.sqlite',
            dirname($d, 3) . '/writable/kuro.sqlite',
        ];
        $path = kenv('SQLITE_PATH', '');
        if ($path === '') {
            foreach ($cands as $p) {
                if (is_file($p) || is_dir(dirname($p))) { $path = $p; break; }
            }
            if ($path === '') $path = $cands[0];
        }
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0777, true);
        $fresh = !is_file($path);
        try {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA journal_mode=WAL;');
        } catch (Throwable $e) {
            die('Database error: ' . $e->getMessage());
        }
        if ($fresh) {
            $schema = '';
            $sd = __DIR__;
            foreach ([$sd . '/sql/sqlite.sql', dirname($sd) . '/sql/sqlite.sql', dirname($sd, 2) . '/sql/sqlite.sql', dirname($sd, 3) . '/sql/sqlite.sql'] as $p) {
                if (is_file($p)) { $schema = $p; break; }
            }
            if ($schema !== '') {
                try { $pdo->exec(file_get_contents($schema)); }
                catch (Throwable $e) { die('Database init failed: ' . $e->getMessage()); }
            }
            kq("UPDATE users SET saldo=999999999 WHERE level=1");
        }
        return $pdo;
    }
}
if (!function_exists('kq')) {
    function kq($sql, $params = []) {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        try {
            $st = kdb()->prepare($sql);
            $st->execute($params);
            if (preg_match('/^\s*(SELECT|PRAGMA|WITH|EXPLAIN)\b/i', $sql)) {
                return new KRes($st->fetchAll(PDO::FETCH_ASSOC));
            }
            return new KRes([], $st->rowCount());
        } catch (Throwable $e) {
            die('Database query failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('db_connect')) {
    function db_connect() { return kdb(); }
}
$conn = kdb();

if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        $r = kq("SELECT `value` FROM `settings` WHERE `key` = ?", [$key]);
        $row = $r->fetch_assoc();
        return $row ? $row['value'] : $default;
    }
}
if (!function_exists('setSetting')) {
    function setSetting($key, $value) {
        kq("INSERT OR REPLACE INTO `settings` (`key`, `value`) VALUES (?, ?)", [$key, $value]);
        return true;
    }
}
?>

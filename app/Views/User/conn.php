<?php
// conn.php - env-driven MySQLi connection (shared pattern across panel).
// Configure via real environment vars or root .env file:
//   DB_HOST / DB_PORT / DB_USER / DB_PASS / DB_NAME
if (!function_exists('kuro_env')) {
    function kuro_env($k, $d = '') {
        $v = getenv($k);
        if ($v !== false && $v !== '') return $v;
        if (isset($_ENV[$k]) && $_ENV[$k] !== '') return (string)$_ENV[$k];
        foreach ([__DIR__ . '/../../.env', __DIR__ . '/../.env', __DIR__ . '/.env'] as $p) {
            if (is_file($p)) {
                $lines = @file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (!is_array($lines)) continue;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') continue;
                    $pos = strpos($line, '=');
                    if ($pos !== false && trim(substr($line, 0, $pos)) === $k) {
                        return trim(substr($line, $pos + 1), " \t\"'");
                    }
                }
            }
        }
        return $d;
    }
}

$servername = kuro_env('DB_HOST', 'localhost');
$username   = kuro_env('DB_USER', 'root');
$password   = kuro_env('DB_PASS', '');
$dbname     = kuro_env('DB_NAME', 'kuro_panel');
$dbport     = (int)kuro_env('DB_PORT', '3306');

$conn = mysqli_connect($servername, $username, $password, $dbname, $dbport);

if (!$conn) {

die(" PROBLEM WITH CONNECTION : " . mysqli_connect_error());

}
  
?>

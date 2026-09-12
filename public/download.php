<?php
// public/download.php - serve APK only to owners/admins or purchasers of that mod
require_once __DIR__ . '/../src/auth.php';
$user = require_login();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Bad request'); }
$st = $pdo->prepare('SELECT a.*, m.name AS mod_name FROM mod_apks a LEFT JOIN mods m ON m.id=a.mod_id WHERE a.id=? LIMIT 1');
$st->execute([$id]);
$apk = $st->fetch();
if (!$apk) { http_response_code(404); exit('Not found'); }
$role = $user['role'];
$allowed = in_array($role, ['owner','admin'], true);
if (!$allowed) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM license_keys WHERE mod_id=? AND sold_to=? LIMIT 1');
    $c->execute([(int)$apk['mod_id'], (int)$user['id']]);
    $allowed = ((int)$c->fetchColumn() > 0);
}
if (!$allowed) { http_response_code(403); exit('Purchase this mod to download.'); }
$abs = dirname(__DIR__) . '/' . $apk['file_path'];
if (!is_file($abs)) { http_response_code(404); exit('File missing on server.'); }
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . basename($apk['file_name']) . '"');
header('Content-Length: ' . filesize($abs));
readfile($abs);
exit;

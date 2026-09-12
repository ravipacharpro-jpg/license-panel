<?php
// scripts/create_owner.php - CLI: php scripts/create_owner.php "Name" "email" "password"
if (php_sapi_name() !== 'cli') exit("CLI only\n");
require_once __DIR__ . '/../src/helpers.php';
[$s, $name, $email, $pass] = array_pad($argv, 4, null);
if (!$name || !$email || !$pass) exit("Usage: php scripts/create_owner.php \"Name\" \"email@x.com\" \"password123\"\n");
$pdo = db();
$chk = $pdo->prepare('SELECT id FROM users WHERE email=?'); $chk->execute([$email]);
if ($chk->fetch()) exit("Email already exists.\n");
$hash = password_hash($pass, PASSWORD_DEFAULT);
$ref = generate_referral_code($pdo);
$pdo->prepare("INSERT INTO users (name,email,password_hash,role,wallet_balance,referral_code,status) VALUES (?,?,?, 'owner', 0, ?, 'active')")
    ->execute([$name, $email, $hash, $ref]);
echo "Owner created: $email referral=$ref id=".$pdo->lastInsertId()."\n";

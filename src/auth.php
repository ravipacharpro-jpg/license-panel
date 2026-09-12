<?php
// src/auth.php
require_once __DIR__ . '/helpers.php';

function require_login(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}

function require_role(array $user, array $roles): void {
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Forbidden: insufficient role');
    }
}

function can_manage_users(array $u): bool {
    return in_array($u['role'], ['owner', 'admin'], true);
}
function is_owner(array $u): bool { return $u['role'] === 'owner'; }

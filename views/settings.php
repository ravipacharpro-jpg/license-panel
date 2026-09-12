<?php require_once 'views/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $_SESSION['user_id']);
        $upd->execute();
        $upd->close();
        $message = 'Password updated successfully.';
    }
}
?>


<div class="card" style="max-width:420px;">
  <h3><?= icon('lock', 16) ?> Change Password</h3>
  <?php if ($error): ?><div style="color:#f87171;font-size:13px;margin-bottom:10px;"><?= e($error) ?></div><?php endif; ?>
  <?php if ($message): ?><div style="color:#4ade80;font-size:13px;margin-bottom:10px;"><?= e($message) ?></div><?php endif; ?>
  <form method="POST">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Current Password</label>
    <input type="password" name="current_password" required style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">New Password</label>
    <input type="password" name="new_password" required style="width:100%;margin-bottom:16px;">
    <button type="submit" class="btn">Update Password</button>
  </form>
</div>

<?php require_once 'views/footer.php'; ?>

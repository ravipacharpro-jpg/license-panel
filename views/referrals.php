<?php require_once 'views/header.php';
require_role(['owner','admin']);

$tenant_code = $_SESSION['tenant_code'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_assigned = ($_POST['role_assigned'] ?? 'reseller') === 'admin' ? 'admin' : 'reseller';
    $initial_balance = floatval($_POST['initial_balance'] ?? 0);
    $max_uses = intval($_POST['max_uses'] ?? 0);
    $code = generate_random_string(8, 'REF-');

    $stmt = $conn->prepare("INSERT INTO referral_codes (tenant_code, created_by, code, role_assigned, initial_balance, max_uses) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissdi", $tenant_code, $_SESSION['user_id'], $code, $role_assigned, $initial_balance, $max_uses);
    $stmt->execute();
    $stmt->close();
    $message = "Referral code created: {$code}";
}

$codes = [];
$stmt = $conn->prepare("SELECT * FROM referral_codes WHERE tenant_code = ? ORDER BY id DESC");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $codes[] = $row;
$stmt->close();
?>

<?php if ($message): ?><div class="card" style="border-color:#7c5cff;"><?= e($message) ?></div><?php endif; ?>

<div class="card" style="max-width:480px;">
  <h3><?= icon('plus-key', 16) ?> Create Referral Code</h3>
  <form method="POST">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Assign Role</label>
    <select name="role_assigned" style="width:100%;margin-bottom:14px;">
      <option value="reseller">Reseller</option>
      <option value="admin">Admin</option>
    </select>
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Initial Wallet Balance</label>
    <input type="number" step="0.01" name="initial_balance" value="0" style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Max Uses (0 = unlimited)</label>
    <input type="number" name="max_uses" value="0" style="width:100%;margin-bottom:16px;">
    <button type="submit" class="btn">Create Referral Code</button>
  </form>
</div>

<div class="card">
  <h3><?= icon('referral', 16) ?> Active Codes</h3>
  <table>
    <tr><th>Code</th><th>Role</th><th>Balance</th><th>Max Uses</th><th>Status</th><th>Created</th></tr>
    <?php foreach ($codes as $c): ?>
      <tr>
        <td><code><?= e($c['code']) ?></code></td>
        <td><?= e(ucfirst($c['role_assigned'])) ?></td>
        <td>₹<?= number_format($c['initial_balance'], 2) ?></td>
        <td><?= $c['max_uses'] == 0 ? 'Unlimited' : $c['max_uses'] ?></td>
        <td><span class="pill pill-<?= $c['is_active'] ? 'active' : 'banned' ?>"><?= $c['is_active'] ? 'Active' : 'Disabled' ?></span></td>
        <td><?= format_date($c['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once 'views/footer.php'; ?>

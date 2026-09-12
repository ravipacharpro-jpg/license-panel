<?php require_once 'views/header.php';
require_role(['owner','admin']);

$tenant_code = $_SESSION['tenant_code'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_action'])) {
    $uid = intval($_POST['user_id']);
    $act = $_POST['member_action'];
    if ($act === 'ban') {
        $stmt = $conn->prepare("UPDATE users SET status='Banned' WHERE id=? AND tenant_code=?");
        $stmt->bind_param("is", $uid, $tenant_code); $stmt->execute(); $stmt->close();
        $message = 'Member banned.';
    } elseif ($act === 'unban') {
        $stmt = $conn->prepare("UPDATE users SET status='Active' WHERE id=? AND tenant_code=?");
        $stmt->bind_param("is", $uid, $tenant_code); $stmt->execute(); $stmt->close();
        $message = 'Member reactivated.';
    }
}

$members = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE tenant_code = ? ORDER BY id DESC");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $members[] = $row;
$stmt->close();
?>

<?php if ($message): ?><div class="card" style="border-color:#7c5cff;"><?= e($message) ?></div><?php endif; ?>

<div class="card">
  <h3><?= icon('team', 16) ?> All Members</h3>
  <table>
    <tr><th>Username</th><th>Email</th><th>Role</th><th>Wallet</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
    <?php foreach ($members as $m): ?>
      <tr>
        <td><?= e($m['username']) ?></td>
        <td><?= e($m['email']) ?></td>
        <td><?= e(ucfirst($m['role'])) ?></td>
        <td>₹<?= number_format($m['wallet_balance'], 2) ?></td>
        <td><span class="pill pill-<?= $m['status'] === 'Active' ? 'active' : 'banned' ?>"><?= e($m['status']) ?></span></td>
        <td><?= format_date($m['created_at']) ?></td>
        <td>
          <?php if ($m['id'] != $_SESSION['user_id']): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
            <?php if ($m['status'] === 'Banned'): ?>
              <button class="btn btn-sm btn-outline" name="member_action" value="unban"><?= icon('check-circle', 13) ?> Unban</button>
            <?php else: ?>
              <button class="btn btn-sm btn-red" name="member_action" value="ban"><?= icon('ban', 13) ?> Ban</button>
            <?php endif; ?>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once 'views/footer.php'; ?>

<?php require_once 'views/header.php';
require_role(['owner','admin']);

$tenant_code = $_SESSION['tenant_code'];

$logs = [];
$stmt = $conn->prepare("SELECT * FROM logs WHERE tenant_code = ? ORDER BY id DESC LIMIT 200");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $logs[] = $row;
$stmt->close();
?>


<div class="card">
  <h3><?= icon('logs', 16) ?> All Events</h3>
  <table>
    <tr><th>Type</th><th>Category</th><th>Message</th><th>Key</th><th>IP</th><th>Time</th></tr>
    <?php if (empty($logs)): ?>
      <tr><td colspan="6" style="color:#666;">No logs yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= e($log['log_type']) ?></td>
        <td><span class="pill pill-<?= $log['category']==='warning' ? 'expired' : ($log['category']==='error' ? 'banned' : 'active') ?>"><?= e($log['category']) ?></span></td>
        <td><?= e($log['message']) ?></td>
        <td><?= e($log['sdk_key'] ?? '-') ?></td>
        <td><?= e($log['ip_address']) ?></td>
        <td><?= format_date($log['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once 'views/footer.php'; ?>

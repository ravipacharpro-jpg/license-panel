<?php require_once 'views/header.php';

$tenant_code = $_SESSION['tenant_code'];

$total_keys = $conn->query("SELECT COUNT(*) c FROM sdk_keys WHERE tenant_code='" . $conn->real_escape_string($tenant_code) . "'")->fetch_assoc()['c'];
$active_keys = $conn->query("SELECT COUNT(*) c FROM sdk_keys WHERE tenant_code='" . $conn->real_escape_string($tenant_code) . "' AND status='Active'")->fetch_assoc()['c'];
$expired_keys = $conn->query("SELECT COUNT(*) c FROM sdk_keys WHERE tenant_code='" . $conn->real_escape_string($tenant_code) . "' AND status='Expired'")->fetch_assoc()['c'];
$total_users = $conn->query("SELECT COUNT(*) c FROM users WHERE tenant_code='" . $conn->real_escape_string($tenant_code) . "'")->fetch_assoc()['c'];

$recent_logs = [];
$stmt = $conn->prepare("SELECT * FROM logs WHERE tenant_code = ? ORDER BY id DESC LIMIT 8");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $recent_logs[] = $row;
$stmt->close();
?>

<div class="grid" style="margin-bottom:22px;">
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon"><?= icon('key', 18) ?></div>
    </div>
    <div class="num"><?= $total_keys ?></div>
    <div class="lbl">Total Keys</div>
  </div>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="color:var(--success);background:linear-gradient(135deg,rgba(52,211,153,0.22),rgba(52,211,153,0.06));border-color:rgba(52,211,153,0.3);"><?= icon('check-circle', 18) ?></div>
    </div>
    <div class="num" style="color:var(--success);"><?= $active_keys ?></div>
    <div class="lbl">Active Keys</div>
  </div>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="color:var(--warning);background:linear-gradient(135deg,rgba(251,191,36,0.22),rgba(251,191,36,0.06));border-color:rgba(251,191,36,0.3);"><?= icon('alert', 18) ?></div>
    </div>
    <div class="num" style="color:var(--warning);"><?= $expired_keys ?></div>
    <div class="lbl">Expired Keys</div>
  </div>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon"><?= icon('team', 18) ?></div>
    </div>
    <div class="num"><?= $total_users ?></div>
    <div class="lbl">Team Members</div>
  </div>
</div>

<div class="card">
  <h3><?= icon('logs', 16) ?> Recent Activity</h3>
  <table>
    <tr><th>Type</th><th>Message</th><th>Key</th><th>IP</th><th>Time</th></tr>
    <?php if (empty($recent_logs)): ?>
      <tr><td colspan="5" style="color:var(--text-faint);">No activity yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($recent_logs as $log): ?>
      <tr>
        <td><?= e($log['category']) ?></td>
        <td><?= e($log['message']) ?></td>
        <td><?= $log['sdk_key'] ? '<code>'.e($log['sdk_key']).'</code>' : '<span style="color:var(--text-faint);">—</span>' ?></td>
        <td><?= e($log['ip_address']) ?></td>
        <td style="color:var(--text-dim);"><?= format_date($log['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once 'views/footer.php'; ?>

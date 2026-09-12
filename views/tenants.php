<?php require_once 'views/header.php';
require_role(['owner']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_tenant = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['tenant_code'] ?? ''));
    if (!empty($new_tenant)) {
        $defaults = [
            'panel_name' => PANEL_NAME,
            'panel_subtitle' => PANEL_SUBTITLE,
            'maintenance_mode' => '0',
        ];
        foreach ($defaults as $key => $value) {
            $stmt = $conn->prepare("INSERT IGNORE INTO server_settings (tenant_code, setting_key, setting_value) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $new_tenant, $key, $value);
            $stmt->execute();
            $stmt->close();
        }
        $message = "Tenant '{$new_tenant}' created.";
    }
}

$tenants = [];
$res = $conn->query("SELECT DISTINCT tenant_code FROM server_settings");
while ($row = $res->fetch_assoc()) $tenants[] = $row['tenant_code'];
?>

<?php if ($message): ?><div class="card" style="border-color:#7c5cff;"><?= e($message) ?></div><?php endif; ?>

<div class="card" style="max-width:420px;">
  <h3><?= icon('plus-key', 16) ?> Create Tenant</h3>
  <form method="POST">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">New Tenant Code (e.g. MEXX002)</label>
    <input type="text" name="tenant_code" required style="width:100%;margin-bottom:16px;">
    <button type="submit" class="btn">Create Tenant</button>
  </form>
</div>

<div class="card">
  <h3><?= icon('tenants', 16) ?> All Tenants</h3>
  <table>
    <tr><th>Tenant Code</th></tr>
    <?php foreach ($tenants as $t): ?>
      <tr><td><code><?= e($t) ?></code></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once 'views/footer.php'; ?>

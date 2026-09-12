<?php require_once 'views/header.php';
require_role(['owner','admin']);

$tenant_code = $_SESSION['tenant_code'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'panel_name' => $_POST['panel_name'] ?? '',
        'panel_subtitle' => $_POST['panel_subtitle'] ?? '',
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'maintenance_message' => $_POST['maintenance_message'] ?? '',
        'success_message' => $_POST['success_message'] ?? '',
        'notification_title' => $_POST['notification_title'] ?? '',
        'notification_message' => $_POST['notification_message'] ?? '',
    ];
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO server_settings (tenant_code, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("sss", $tenant_code, $key, $value);
        $stmt->execute();
        $stmt->close();
    }
    $message = 'Settings saved.';
}

$current = [];
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM server_settings WHERE tenant_code = ?");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $current[$row['setting_key']] = $row['setting_value'];
$stmt->close();

function sv($current, $key, $default = '') { return e($current[$key] ?? $default); }
?>

<?php if ($message): ?><div class="card" style="border-color:#7c5cff;"><?= e($message) ?></div><?php endif; ?>

<div class="card" style="max-width:520px;">
  <h3><?= icon('settings', 16) ?> Branding & Behavior</h3>
  <form method="POST">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Panel Name</label>
    <input type="text" name="panel_name" value="<?= sv($current,'panel_name',PANEL_NAME) ?>" style="width:100%;margin-bottom:14px;">

    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Panel Subtitle</label>
    <input type="text" name="panel_subtitle" value="<?= sv($current,'panel_subtitle',PANEL_SUBTITLE) ?>" style="width:100%;margin-bottom:14px;">

    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:14px;">
      <input type="checkbox" name="maintenance_mode" <?= ($current['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>> Maintenance Mode
    </label>

    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Maintenance Message</label>
    <input type="text" name="maintenance_message" value="<?= sv($current,'maintenance_message','Servers are under maintenance.') ?>" style="width:100%;margin-bottom:14px;">

    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">API Success Message</label>
    <input type="text" name="success_message" value="<?= sv($current,'success_message','Server is online') ?>" style="width:100%;margin-bottom:14px;">

    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Notification Title</label>
    <input type="text" name="notification_title" value="<?= sv($current,'notification_title','System Note') ?>" style="width:100%;margin-bottom:14px;">

    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Notification Message</label>
    <input type="text" name="notification_message" value="<?= sv($current,'notification_message','Welcome!') ?>" style="width:100%;margin-bottom:18px;">

    <button type="submit" class="btn">Save Settings</button>
  </form>
</div>

<div class="card" style="max-width:520px;">
  <h3><?= icon('tenants', 16) ?> Your Tenant Code</h3>
  <p style="font-size:13px;color:#a0a0b8;">Use this in your Android app's API calls:</p>
  <code style="background:#0d0d16;padding:6px 10px;border-radius:6px;color:#4ade80;"><?= e($tenant_code) ?></code>
</div>

<?php require_once 'views/footer.php'; ?>

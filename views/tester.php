<?php require_once 'views/header.php';

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'action' => 'connect',
        'tenant_code' => $_POST['tenant_code'] ?? $_SESSION['tenant_code'],
        'user_key' => $_POST['user_key'] ?? '',
        'package_name' => $_POST['package_name'] ?? 'com.test.app',
        'app_name' => $_POST['app_name'] ?? 'TestApp',
        'device_id' => $_POST['device_id'] ?? 'TEST-DEVICE-001',
    ];

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/api/connect';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = $response;
}
?>


<div class="card" style="max-width:520px;">
  <h3><?= icon('tester', 16) ?> Simulate a Connection</h3>
  <form method="POST">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Tenant Code</label>
    <input type="text" name="tenant_code" value="<?= e($_SESSION['tenant_code']) ?>" style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">License Key</label>
    <input type="text" name="user_key" required style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Package Name</label>
    <input type="text" name="package_name" value="com.test.app" style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">App Name</label>
    <input type="text" name="app_name" value="TestApp" style="width:100%;margin-bottom:14px;">
    <label style="display:block;font-size:12px;color:#aaa;margin-bottom:6px;">Device ID</label>
    <input type="text" name="device_id" value="TEST-DEVICE-001" style="width:100%;margin-bottom:18px;">
    <button type="submit" class="btn">Test Connection</button>
  </form>
</div>

<?php if ($result): ?>
<div class="card" style="max-width:520px;">
  <h3><?= icon('check-circle', 16) ?> Response</h3>
  <pre style="background:#0d0d16;padding:14px;border-radius:8px;color:#4ade80;overflow:auto;"><?= e($result) ?></pre>
</div>
<?php endif; ?>

<?php require_once 'views/footer.php'; ?>

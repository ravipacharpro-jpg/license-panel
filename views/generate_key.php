<?php require_once 'views/header.php';

$tenant_code = $_SESSION['tenant_code'];
$generated_keys = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration_hours = intval($_POST['duration_hours'] ?? 720);
    $device_limit   = intval($_POST['device_limit'] ?? 1);
    $package_mode   = ($_POST['package_mode'] ?? 'single') === 'multi' ? 'multi' : 'single';
    $quantity       = max(1, min(50, intval($_POST['quantity'] ?? 1)));
    $prefix         = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['prefix'] ?? 'MEXX'));

    for ($i = 0; $i < $quantity; $i++) {
        $key = generate_random_string(14, $prefix . '-');
        $stmt = $conn->prepare("INSERT INTO sdk_keys (tenant_code, created_by, sdk_key, duration_hours, device_limit, package_mode, status) VALUES (?, ?, ?, ?, ?, ?, 'Unused')");
        $stmt->bind_param("sisiis", $tenant_code, $_SESSION['user_id'], $key, $duration_hours, $device_limit, $package_mode);
        $stmt->execute();
        $stmt->close();
        $generated_keys[] = $key;
    }
    write_log($conn, $tenant_code, 'panel', 'success', "Generated {$quantity} key(s)");
}
?>

<div class="grid" style="align-items:flex-start;gap:20px;">
  <div class="card" style="flex:1;min-width:320px;max-width:440px;">
    <h3><?= icon('plus-key', 16) ?> Generate License Key</h3>
    <form method="POST">
      <div class="field" style="margin-bottom:14px;">
        <label class="field-label">Key Prefix</label>
        <input type="text" name="prefix" value="MEXX" style="width:100%;">
      </div>
      <div class="field" style="margin-bottom:14px;">
        <label class="field-label">Duration (hours, 0 = lifetime)</label>
        <input type="number" name="duration_hours" value="720" style="width:100%;">
      </div>
      <div class="field" style="margin-bottom:14px;">
        <label class="field-label">Device Limit (0 = unlimited)</label>
        <input type="number" name="device_limit" value="1" style="width:100%;">
      </div>
      <div class="field" style="margin-bottom:14px;">
        <label class="field-label">Package Mode</label>
        <select name="package_mode" style="width:100%;">
          <option value="single">Single App</option>
          <option value="multi">Multi App</option>
        </select>
      </div>
      <div class="field" style="margin-bottom:18px;">
        <label class="field-label">Quantity</label>
        <input type="number" name="quantity" value="1" min="1" max="50" style="width:100%;">
      </div>
      <button type="submit" class="btn" style="width:100%;justify-content:center;"><?= icon('sparkle', 15) ?> Generate</button>
    </form>
  </div>

  <?php if ($generated_keys): ?>
  <div class="card" style="flex:1;min-width:320px;">
    <h3><?= icon('check-circle', 16) ?> Generated Successfully</h3>
    <div style="display:flex;flex-direction:column;gap:8px;max-height:340px;overflow-y:auto;">
      <?php foreach ($generated_keys as $gk): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;background:#0b0b14;border:1px solid var(--border-soft);border-radius:10px;padding:9px 12px;">
          <code style="background:transparent;border:0;padding:0;"><?= e($gk) ?></code>
          <button class="btn-icon" style="background:transparent;border:0;color:var(--text-faint);cursor:pointer;" onclick="copyToClipboard('<?= e($gk) ?>','Key')" title="Copy"><?= icon('copy', 15) ?></button>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-outline btn-sm" style="margin-top:14px;" onclick="copyToClipboard(<?= json_encode(implode("\n", $generated_keys)) ?>,'All keys')"><?= icon('copy', 13) ?> Copy All</button>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'views/footer.php'; ?>

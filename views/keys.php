<?php require_once 'views/header.php';

$tenant_code = $_SESSION['tenant_code'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key_action'])) {
    $key_id = intval($_POST['key_id']);
    $act = $_POST['key_action'];
    if ($act === 'ban') {
        $stmt = $conn->prepare("UPDATE sdk_keys SET status='Banned' WHERE id=? AND tenant_code=?");
        $stmt->bind_param("is", $key_id, $tenant_code);
        $stmt->execute(); $stmt->close();
        $message = 'Key banned.';
    } elseif ($act === 'unban') {
        $stmt = $conn->prepare("UPDATE sdk_keys SET status='Active' WHERE id=? AND tenant_code=?");
        $stmt->bind_param("is", $key_id, $tenant_code);
        $stmt->execute(); $stmt->close();
        $message = 'Key reactivated.';
    } elseif ($act === 'delete') {
        $stmt = $conn->prepare("DELETE FROM sdk_keys WHERE id=? AND tenant_code=?");
        $stmt->bind_param("is", $key_id, $tenant_code);
        $stmt->execute(); $stmt->close();
        $message = 'Key deleted.';
    }
}

$keys = [];
$stmt = $conn->prepare("SELECT * FROM sdk_keys WHERE tenant_code = ? ORDER BY id DESC LIMIT 200");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $keys[] = $row;
$stmt->close();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:16px;flex-wrap:wrap;">
  <div class="input-icon" style="position:relative;flex:1;min-width:220px;max-width:340px;">
    <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-faint);"><?= icon('search', 15) ?></span>
    <input type="text" id="keySearch" placeholder="Search by key, package, status..." style="width:100%;padding-left:38px;" onkeyup="filterKeys()">
  </div>
  <a href="/generate-key" class="btn"><?= icon('plus-key', 15) ?> Generate New Key</a>
</div>

<?php if ($message): ?><div class="alert-box alert-success"><?= icon('check-circle', 16) ?><?= e($message) ?></div><?php endif; ?>

<div class="card">
  <table id="keysTable">
    <tr><th>Key</th><th>Status</th><th>Duration</th><th>Devices</th><th>Package</th><th>Expires</th><th>Actions</th></tr>
    <?php if (empty($keys)): ?>
      <tr><td colspan="7" style="color:var(--text-faint);">No keys generated yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($keys as $k): ?>
      <tr>
        <td>
          <code><?= e($k['sdk_key']) ?></code>
          <button class="btn-icon" style="background:transparent;border:0;color:var(--text-faint);cursor:pointer;vertical-align:middle;" onclick="copyToClipboard('<?= e($k['sdk_key']) ?>','Key')" title="Copy key"><?= icon('copy', 14) ?></button>
        </td>
        <td><span class="pill pill-<?= strtolower($k['status']) ?>"><?= e($k['status']) ?></span></td>
        <td><?= $k['duration_hours'] > 0 ? $k['duration_hours'] . 'h' : 'Lifetime' ?></td>
        <td><?= $k['device_limit'] == 0 ? 'Unlimited' : $k['device_limit'] ?></td>
        <td><?= e($k['allowed_packages'] ?? 'ALL') ?></td>
        <td style="color:var(--text-dim);"><?= format_date($k['expires_at']) ?></td>
        <td>
          <form method="POST" style="display:inline-flex;gap:6px;">
            <input type="hidden" name="key_id" value="<?= $k['id'] ?>">
            <?php if ($k['status'] === 'Banned'): ?>
              <button class="btn btn-sm btn-outline" name="key_action" value="unban"><?= icon('check-circle', 13) ?> Unban</button>
            <?php else: ?>
              <button class="btn btn-sm btn-outline" name="key_action" value="ban"><?= icon('ban', 13) ?> Ban</button>
            <?php endif; ?>
            <button class="btn btn-sm btn-red" name="key_action" value="delete" onclick="return confirm('Delete this key permanently?')"><?= icon('trash', 13) ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<script>
function filterKeys() {
  const q = document.getElementById('keySearch').value.toLowerCase();
  document.querySelectorAll('#keysTable tr').forEach((row, i) => {
    if (i === 0) return;
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>

<?php require_once 'views/footer.php'; ?>

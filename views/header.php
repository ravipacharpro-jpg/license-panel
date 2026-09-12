<?php
require_login();
require_once __DIR__ . '/../app/icons.php';
$db = new Database();
$conn = $db->getConnection();
$active = $route_path ?? '';

$nav_items = [
    'dashboard'       => ['Dashboard',      'dashboard', ['owner','admin','reseller']],
    'keys'            => ['License Keys',   'key',       ['owner','admin','reseller']],
    'generate-key'    => ['Generate Key',   'plus-key',  ['owner','admin','reseller']],
    'team'            => ['Team',           'team',      ['owner','admin']],
    'referrals'       => ['Referrals',      'referral',  ['owner','admin']],
    'logs'            => ['Logs',           'logs',      ['owner','admin']],
    'tester'          => ['API Tester',     'tester',    ['owner','admin','reseller']],
    'server-settings' => ['Server Settings','server',    ['owner','admin']],
    'tenants'         => ['Tenants',        'tenants',   ['owner']],
    'settings'        => ['My Settings',    'settings',  ['owner','admin','reseller']],
];
$initials = strtoupper(substr($_SESSION['username'], 0, 2));
$mexx_theme_attr = $_COOKIE['mexx_theme'] ?? 'nebula';
if (!in_array($mexx_theme_attr, ['nebula', 'azure', 'light'])) $mexx_theme_attr = 'nebula';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($mexx_theme_attr) ?>">
<head>
<meta charset="UTF-8">
<title><?= e(PANEL_NAME) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<?php require __DIR__ . '/theme.php'; ?>
</head>
<body>
<div class="ghost-watermark">
  <svg viewBox="0 0 200 130" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M40 118V55C40 26 63 3 92 3h16c29 0 52 23 52 52v63l-16-14-15 14-15-14-15 14-15-14-15 14-15-14-14 14z"/>
    <circle cx="78" cy="52" r="5.5" fill="currentColor" stroke="none"/>
    <circle cx="122" cy="52" r="5.5" fill="currentColor" stroke="none"/>
  </svg>
</div>
<div class="layout" style="position:relative;z-index:1;">
  <div class="sidebar">
    <div class="brand">
      <div class="brand-mark"><?= icon('ghost', 20) ?></div>
      <div class="brand-text">
        <h2><?= e(PANEL_NAME) ?></h2>
        <span><?= e(PANEL_SUBTITLE) ?></span>
      </div>
    </div>

    <div class="nav-group-label">Menu</div>
    <?php foreach ($nav_items as $uri => $item):
        list($label, $ic, $roles) = $item;
        if (!in_array(strtolower($_SESSION['role']), $roles)) continue;
    ?>
      <a href="/<?= $uri ?>" class="nav-link <?= $active === $uri ? 'active' : '' ?>">
        <?= icon($ic, 17) ?><span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>

    <a href="/logout" class="nav-link logout-link"><?= icon('logout', 17) ?><span>Logout</span></a>

    <div class="ghost-card">
      <div class="ghost-card-art"><?= icon('ghost', 54) ?></div>
      <div class="ghost-card-title">Ghost Mode</div>
      <div class="ghost-card-sub"><span class="ghost-status-dot"></span> Panel secured &amp; watching</div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <div class="topbar-title">
        <h1><?= e(ucwords(str_replace('-', ' ', $active ?: 'Dashboard'))) ?></h1>
        <p>Welcome back to your control center</p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="theme-switch">
          <div class="theme-switch-btn" id="themeSwitchBtn" onclick="toggleThemeDropdown()"><?= icon('sparkle', 16) ?></div>
          <div class="theme-dropdown" id="themeDropdown">
            <div class="theme-option <?= $mexx_theme_attr === 'nebula' ? 'active' : '' ?>" onclick="setMexxTheme('nebula')">
              <span class="theme-dot dot-nebula"></span> Ghost Dark <span class="theme-check"><?= icon('check-circle', 14) ?></span>
            </div>
            <div class="theme-option <?= $mexx_theme_attr === 'azure' ? 'active' : '' ?>" onclick="setMexxTheme('azure')">
              <span class="theme-dot dot-azure"></span> Ghost Blue <span class="theme-check"><?= icon('check-circle', 14) ?></span>
            </div>
            <div class="theme-option <?= $mexx_theme_attr === 'light' ? 'active' : '' ?>" onclick="setMexxTheme('light')">
              <span class="theme-dot dot-light"></span> Ghost Light <span class="theme-check"><?= icon('check-circle', 14) ?></span>
            </div>
          </div>
        </div>
        <div class="user-chip">
          <div class="avatar"><?= e($initials) ?></div>
          <div>
            <div class="u-name"><?= e($_SESSION['username']) ?></div>
          </div>
          <span class="role-pill"><?= e($_SESSION['role']) ?></span>
        </div>
      </div>
    </div>

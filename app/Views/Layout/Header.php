<style>
    .custom-navbar {
        background: rgba(93, 63, 159, 0.8) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    .navbar-brand {
        color: white !important;
        font-weight: 600;
    }
    .nav-link {
        color: rgba(255, 255, 255, 0.8) !important;
        transition: all 0.3s;
    }
    .nav-link:hover {
        color: white !important;
        transform: translateY(-2px);
    }
    .dropdown-menu {
        background-color: rgba(93, 63, 159, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 1030;
    }
    .dropdown-item {
        color: white !important;
    }
    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .dropdown-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.3);
    }
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
    
    /* Added styles for right alignment */
    .user-dropdown {
        margin-left: auto;
    }
    
    .dropdown-menu-custom {
        position: absolute;
        right: 0;
        left: auto !important;
        min-width: 200px;
    }
</style>

<header>
  <nav class="navbar navbar-expand-md navbar-dark shadow-sm align-middle custom-navbar" role="alert">
        <div class="container px-3">
            <a class="navbar-brand" href="<?= site_url() ?>"><i class="bi bi-box text-white px-2"></i><?= BASE_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Ghost theme switch (3 variants, persists via cookie) -->
            <?php $gh = $_COOKIE['ghost_theme'] ?? 'nebula'; if (!in_array($gh, ['nebula','azure','light'])) $gh = 'nebula'; ?>
            <div class="theme-switch ms-2">
                <div class="theme-switch-btn" id="themeSwitchBtn" onclick="toggleThemeDropdown()" title="Ghost theme">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="theme-dropdown" id="themeDropdown">
                    <div class="theme-option <?= $gh === 'nebula' ? 'active' : '' ?>" onclick="setGhostTheme('nebula')">
                        <span class="theme-dot dot-nebula"></span> Ghost Dark <span class="theme-check"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <div class="theme-option <?= $gh === 'azure' ? 'active' : '' ?>" onclick="setGhostTheme('azure')">
                        <span class="theme-dot dot-azure"></span> Ghost Blue <span class="theme-check"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <div class="theme-option <?= $gh === 'light' ? 'active' : '' ?>" onclick="setGhostTheme('light')">
                        <span class="theme-dot dot-light"></span> Ghost Light <span class="theme-check"><i class="bi bi-check-circle"></i></span>
                    </div>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <?php if (session()->has('userid')) : ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('keys') ?>">Keys</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('keys/generate') ?>">Generate</a>
                        </li>
                    </ul>
                    
                    <!-- Changed this section for better right alignment -->
                    <ul class="navbar-nav user-dropdown">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle pe-2"></i><?= getName($user) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?= site_url('settings') ?>">
                                        <i class="bi bi-gear"></i> Settings
                                    </a>
                                </li>
                               
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                    <?php if (($user->level == 1) || ($user->level ==2)) : ?>
                                 
                                    
                                      <li>
                                    <a class="dropdown-item" href="<?= site_url('Server') ?>">
                                        <i class="bi-controller"></i> Online System
                                    </a>
                                </li>
                                    
                                    <li>
                                        <a class="dropdown-item" href="<?= site_url('admin/manage-users') ?>">
                                            <i class="bi bi-person-check"></i> manage Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= site_url('admin/create-referral') ?>">
                                            <i class="bi bi-person-plus"></i> Create Referral
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?= site_url('logout') ?>">
                                        <i class="bi bi-box-arrow-in-left"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
            </div>
        <?php endif; ?>

        </div>
    </nav>
</header>
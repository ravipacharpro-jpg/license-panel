<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>

<style>
    body {
        background-image: url('https://images.unsplash.com/photo-1564475228765-f0c3292f2dec?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
        background-size: cover;
        background-position: center;
        height: 100vh;
        background-attachment: fixed;
        background-color: #2d1b4e;
    }
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .card {
        background-color: rgba(93, 63, 159, 0.7) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 100%;
    }
    .card-header {
        background-color: transparent;
        border-bottom: none;
        color: white;
        text-align: center;
        font-size: 24px;
        padding-top: 20px;
    }
    .card-body {
        padding: 30px;
    }
    .form-control {
        background-color: rgba(93, 63, 159, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white !important;
        border-radius: 50px;
        padding: 12px 20px;
    }
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    .form-control:focus {
        background-color: rgba(93, 63, 159, 0.6);
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
    }
    label {
        color: white;
        margin-left: 10px;
        font-size: 14px;
    }
    .form-check-label {
        color: white;
    }
    .form-check-input {
        background-color: rgba(93, 63, 159, 0.4);
        border-color: rgba(255, 255, 255, 0.3);
    }
    .form-check-input:checked {
        background-color: #8e65e3;
    }
    .btn-login {
        background-color: white;
        color: #5d3f9f;
        border-radius: 50px;
        padding: 10px;
        font-weight: bold;
        width: 100%;
        border: none;
        transition: all 0.3s;
    }
    .btn-login:hover {
        background-color: #f0f0f0;
        transform: translateY(-2px);
    }
    .forgot-password {
        color: white;
        text-decoration: none;
        font-size: 14px;
    }
    .forgot-password:hover {
        text-decoration: underline;
        color: white;
    }
    .register-link {
        color: white;
        text-decoration: none;
    }
    .register-link:hover {
        text-decoration: underline;
    }
    .after-card {
        color: white !important;
    }
    .after-card a {
        color: #a78bfa !important;
    }
    .input-icon {
        position: relative;
    }
    .input-icon i {
        position: absolute;
        right: 15px;
        top: 42px;
        color: white;
    }
</style>

<div class="login-container">
    <div class="col-lg-4">
        <?= $this->include('Layout/msgStatus') ?>
        
        <div class="card shadow-sm mb-5">
            <div class="card-header">
                Login
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <div class="form-group mb-4 input-icon">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="username" id="username" aria-describedby="help-username" placeholder="Your username" required minlength="4">
                    <i class="bi bi-person-fill"></i>
                    <?php if ($validation->hasError('username')) : ?>
                        <small id="help-username" class="form-text text-warning"><?= $validation->getError('username') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-4 input-icon">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" aria-describedby="help-password" placeholder="Your password" required minlength="6">
                    <i class="bi bi-lock-fill"></i>
                    <?php if ($validation->hasError('password')) : ?>
                        <small id="help-password" class="form-text text-warning"><?= $validation->getError('password') ?></small>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" name="ip" value="<?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'); ?>" id="ip">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="stay_log" id="stay_log" value="yes">
                        <label class="form-check-label" for="stay_log" data-bs-toggle="tooltip" data-bs-placement="top" title="Keep session more than 30 minutes">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <div class="form-group mb-3">
                    <button type="submit" class="btn-login">Login</button>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-white mb-0">
                        Don't have an account? <a href="<?= site_url('register') ?>" class="register-link">Register</a>
                    </p>
                </div>
                <?= form_close() ?>
            </div>
        </div>
        
        <p class="text-center after-card">
            <small class="px-auto p-2 rounded">
                TO BUY PANEL DM HERE :-
                <a href="https://t.me/L359D" target="_blank"><i class="bi bi-telegram"></i> @L359D</a>
            </small>
        </p>
    </div>
</div>

<?= $this->endSection() ?>
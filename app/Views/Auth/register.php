<?php

include('conn.php');
include('mail.php');

// For Credits
$sql = "SELECT * FROM credit where id=1";
$result = kq($sql);
$credit = $result->fetch_assoc();

?>

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
    }
    .card-header {
        background-color: transparent !important;
        border-bottom: none;
        color: white !important;
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
    .btn-register {
        background-color: white;
        color: #5d3f9f;
        border-radius: 50px;
        padding: 10px;
        font-weight: bold;
        width: 100%;
        border: none;
        transition: all 0.3s;
    }
    .btn-register:hover {
        background-color: #f0f0f0;
        transform: translateY(-2px);
    }
    .after-card {
        color: white !important;
    }
    .after-card a {
        color: #a78bfa !important;
        text-decoration: none;
    }
    .after-card a:hover {
        text-decoration: underline;
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
                Register
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <?= csrf_field() ?>
                <div class="form-group mb-3 input-icon">
                    <label for="email">E-mail</label>
                    <input type="email" class="form-control mt-2" name="email" id="email" aria-describedby="help-email" placeholder="Enter Your Current Mail" minlength="13" maxlength="40" value="<?= old('email') ?>" required>
                    <i class="bi bi-envelope-fill"></i>
                    <?php if ($validation->hasError('email')) : ?>
                        <small id="help-email" class="form-text text-warning"><?= $validation->getError('email') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3 input-icon">
                    <label for="username">Username</label>
                    <input type="text" class="form-control mt-2" name="username" id="username" aria-describedby="help-username" placeholder="Your username" minlength="4" maxlength="24" value="<?= old('username') ?>" required>
                    <i class="bi bi-person-fill"></i>
                    <?php if ($validation->hasError('username')) : ?>
                        <small id="help-username" class="form-text text-warning"><?= $validation->getError('username') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3 input-icon">
                    <label for="fullname">Fullname</label>
                    <input type="text" class="form-control mt-2" name="fullname" id="fullname" aria-describedby="help-fullname" placeholder="Your fullname" minlength="4" maxlength="24" value="<?= old('fullname') ?>" required>
                    <i class="bi bi-person-badge-fill"></i>
                    <?php if ($validation->hasError('fullname')) : ?>
                        <small id="help-fullname" class="form-text text-warning"><?= $validation->getError('fullname') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3 input-icon">
                    <label for="password">Password</label>
                    <input type="password" class="form-control mt-2" name="password" id="password" aria-describedby="help-password" placeholder="Enter Password" minlength="6" maxlength="24" required>
                    <i class="bi bi-lock-fill toggle-password" style="cursor: pointer;"></i>
                    <?php if ($validation->hasError('password')) : ?>
                        <small id="help-password" class="form-text text-warning"><?= $validation->getError('password') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3 input-icon">
                    <label for="password2">Confirm Password</label>
                    <input type="password" name="password2" id="password2" class="form-control mt-2" placeholder="Confirm password" aria-describedby="help-password2" minlength="6" maxlength="24" required>
                    <i class="bi bi-lock-fill toggle-password2" style="cursor: pointer;"></i>
                    <?php if ($validation->hasError('password2')) : ?>
                        <small id="help-password2" class="form-text text-warning"><?= $validation->getError('password2') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3 input-icon">
                    <label for="referral">Referral Code</label>
                    <input type="text" name="referral" id="referral" class="form-control mt-2" placeholder="Your Referral Code" aria-describedby="help-referral" value="<?= old('referral') ?: (getSetting('auto_referral') == '1' ? getSetting('owner_referral_code') : '') ?>" maxlength="25" required>
                    <i class="bi bi-gift-fill"></i>
                    <?php if ($validation->hasError('referral')) : ?>
                        <small id="help-referral" class="form-text text-warning"><?= $validation->getError('referral') ?></small>
                    <?php endif; ?>
                </div>
              <!---->
                <div class="form-group mb-3 input-icon">
                    <label for="ip" class="form-label">IP Address</label>
                    <input type="text" id="ip" class="form-control" placeholder="<?php echo $user_ip ?>" readonly>
                    <i class="bi bi-globe"></i>
                </div>
                <!---->
                
                <div class="form-group mb-4 mt-4">
                    <button type="submit" class="btn-register" onclick="popup()">Register</button>
                </div>
                <?= form_close() ?>

            </div>
        </div>
        <p class="text-center after-card">
            <small class="px-auto p-2 rounded">
                Already have an account?
                <a href="<?= site_url('login') ?>">Login here</a>
            </small>
        </p>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('js') ?>
<script> 
    $(document).on('click', '.toggle-password', function() {
        $(this).toggleClass("bi-lock-fill bi-unlock-fill");
        var input = $("#password");
        input.attr('type') === 'password' ? input.attr('type','text') : input.attr('type','password')
    });
    $(document).on('click', '.toggle-password2', function() {
        $(this).toggleClass("bi-lock-fill bi-unlock-fill");
        var input = $("#password2");
        input.attr('type') === 'password' ? input.attr('type','text') : input.attr('type','password')
    });
</script> 
<?= $this->endSection() ?>

<div class="text-center mb-4">
    <small class="text-white">TO BUY PANEL DM HERE :- <a href="https://t.me/L359D" target="_blank" style="color:#0088cc;"><i class="bi bi-telegram"></i> @L359D</a></small>
</div>
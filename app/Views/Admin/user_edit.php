<?php
include('mail.php');
?>

<?= $this->extend('Layout/Starter') ?>

<?= $this->section('content') ?>
<style>
    body {
        background-image: url('https://images.unsplash.com/photo-1564475228765-f0c3292f2dec?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-color: #2d1b4e;
    }
    
    .card {
        background-color: rgba(93, 63, 159, 0.7) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        margin-bottom: 25px;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
    }
    
    .card-header {
        background-color: rgba(93, 63, 159, 0.4) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        color: white !important;
        font-weight: 600;
        padding: 15px 20px;
    }
    
    .card-body {
        padding: 20px;
        color: white;
    }
    
    .form-control, .form-select {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .form-control:focus, .form-select:focus {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: rgba(167, 139, 250, 0.5);
        color: white;
        box-shadow: 0 0 0 0.25rem rgba(167, 139, 250, 0.25);
    }
    
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .form-select option {
        background-color: rgba(93, 63, 159, 0.9);
        color: white;
    }
    
    .form-label {
        color: white;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .btn-outline-dark {
        color: white;
        border-color: rgba(255, 255, 255, 0.3);
        background-color: rgba(93, 63, 159, 0.3);
    }
    
    .btn-outline-dark:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .btn-outline-light {
        color: white;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .animate-in {
        animation: fadeIn 0.5s ease-out forwards;
        opacity: 0;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="row justify-content-center pt-3">
    <div class="col-lg-8">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
    <div class="col-lg-8 mb-3 animate-in">
        <div class="card mb-5">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-person-badge me-2"></i> Acc Info &middot; <?= getName($target) ?>
                    </div>
                    <div>
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('admin/manage-users') ?>">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $target->id_users ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" aria-describedby="help-username" value="<?= old('username') ?: $target->username ?>">
                        <?php if ($validation->hasError('username')) : ?>
                            <small id="help-username" class="form-text text-danger"><?= $validation->getError('username') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fullname" class="form-label">Fullname</label>
                        <input type="text" name="fullname" id="fullname" class="form-control" placeholder="Enter full name" aria-describedby="help-fullname" value="<?= old('fullname') ?: $target->fullname ?>">
                        <?php if ($validation->hasError('fullname')) : ?>
                            <small id="help-fullname" class="form-text text-danger"><?= $validation->getError('fullname') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">Roles</label>
                        <?php $sel_level = ['' => '&mdash; Select Roles &mdash;', '1' => 'Owner', '2' => 'Admin', '3' => 'Reseller']; ?>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'level', 'id' => 'level'], $sel_level, $target->level) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <?php $sel_status = ['' => '&mdash; Select Status &mdash;', '2' => 'Banned/Block', '1' => 'Active', '3' => 'Expired',]; ?>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'status', 'id' => 'status'], $sel_status, $target->status) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="saldo" class="form-label">Saldo</label>
                        <input type="number" name="saldo" id="saldo" class="form-control" placeholder="Enter saldo amount" aria-describedby="help-saldo" value="<?= old('saldo') ?: $target->saldo ?>">
                        <?php if ($validation->hasError('saldo')) : ?>
                            <small id="help-saldo" class="form-text text-danger"><?= $validation->getError('saldo') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="uplink" class="form-label">Uplink</label>
                        <input type="text" name="uplink" id="uplink" class="form-control" placeholder="Enter uplink" aria-describedby="help-uplink" value="<?= old('uplink') ?: $target->uplink ?>">
                        <?php if ($validation->hasError('uplink')) : ?>
                            <small id="help-uplink" class="form-text text-danger"><?= $validation->getError('uplink') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="expiration" class="form-label">Expiration</label>
                        <input type="text" name="expiration" id="expiration" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" aria-describedby="help-expiration" value="<?= old('expiration') ?: $target->expiration_date ?>">
                        <?php if ($validation->hasError('expiration')) : ?>
                            <small id="help-expiration" class="form-text text-danger"><?= $validation->getError('expiration') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-outline-dark">
                            <i class="bi bi-save me-2"></i> Update Account Information
                        </button>
                    </div>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const animatedElements = document.querySelectorAll('.animate-in');
        animatedElements.forEach(element => {
            element.style.animationPlayState = 'running';
        });
    });
</script>
<?= $this->endSection() ?>
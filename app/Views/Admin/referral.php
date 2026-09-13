<?php
include('conn.php');
include('mail.php');

// For Highest id Ref
$sqli = "SELECT * FROM referral_code
ORDER BY id_reff DESC
LIMIT 1;";
$result = kq($sqli);
$id_reff = $result->fetch_assoc();

// For Referral Code
$sql = "SELECT Referral FROM referral_code";
$result = kq($sql);
$refcode = $result->fetch_assoc();
$row = $refcode;

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
    }
    
    .input-group-text {
        background-color: rgba(93, 63, 159, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .btn-outline-dark {
        color: white;
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .btn-outline-dark:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .table {
        color: white !important;
    }
    
    .table-bordered {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    .table thead th {
        background-color: rgba(93, 63, 159, 0.4);
        border-color: rgba(255, 255, 255, 0.1) !important;
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

<div class="row">
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
    <div class="col-lg-4 mb-3 animate-in" style="animation-delay: 0.1s">
        <div class="card">
          <div class="card-header">
                <i class="bi bi-ticket-perforated me-2"></i> Generate <?= $title ?>
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <?= csrf_field() ?>
                <div class="form-group mb-3">
                    <label for="set_saldo" class="form-label">You can set with multiple saldo</label>
                    <div class="input-group mt-2">
                        <span class="input-group-text"><i class="bi bi-currency-rupee"></i></span>
                        <input type="number" class="form-control" name="set_saldo" id="set_saldo" minlength="1" maxlength="11" value="5">
                    </div>
                    <?php if ($validation->hasError('set_saldo')) : ?>
                        <small id="help-saldo" class="text-danger"><?= $validation->getError('set_saldo') ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group mb-3">
                    <label for="accExpire" class="form-label">Account Expiration</label>
                    <?= form_dropdown(['class' => 'form-select', 'name' => 'accExpire', 'id' => 'accExpire'], $accExpire, old('accExpire') ?: '') ?>
                    <?php if ($validation->hasError('accExpire')) : ?>
                        <small id="help-accExpire" class="text-danger"><?= $validation->getError('accExpire') ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group mb-3">
                    <label for="accLevel" class="form-label">Account Level</label>
                    <?= form_dropdown(['class' => 'form-select', 'name' => 'accLevel', 'id' => 'accLevel'], $accLevel, old('accLevel') ?: '') ?>
                    <?php if ($validation->hasError('accLevel')) : ?>
                        <small id="help-accLevel" class="text-danger"><?= $validation->getError('accLevel') ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-dark w-100">
                        <i class="bi bi-plus-circle me-2"></i> Create Code
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8 animate-in" style="animation-delay: 0.2s">
        <?php if ($code) : ?>
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> History Generate - Total <?= $total_code ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Referral</th>
                                    <th>Hashed</th>
                                    <th>Saldo</th>
                                    <th>Level</th>
                                    <th>Expiration</th>
                                    <th>Used by</th>
                                    <th>Create by</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($code as $c) : ?>
                                    <tr>
                                        <td><?= $c->id_reff ?></td>
                                        <td><span class="badge bg-primary"><?= $c->Referral ?></span></td>
                                        <td><span class="text-warning"><?= substr($c->code, 1, 15) ?></span></td>
                                        <td><span class="text-success">₹<?= $c->set_saldo ?></span></td>
                                        <td><?= $c->level ?></td>
                                        <td><?= $c->acc_expiration ?></td>
                                        <td><?= $c->used_by ?: '<span class="text-muted">Not used</span>' ?></td>
                                        <td><?= $c->created_by ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
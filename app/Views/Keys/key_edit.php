<?= $this->extend('Layout/Starter') ?>
<?= $this->section('content') ?>
<?php
// $exCount = 0;
// if ($key->devices) {
//     $ex = explode(',', reduce_multiples($key->devices, ",", true));
//     $listDevice = "";
//     foreach ($ex as $ld) {
//         $listDevice .= "$ld\n";
//     }
//     $exCount = count($ex);
// }
?>

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
    
    .btn-outline-info {
        color: white;
        border-color: rgba(13, 202, 240, 0.5);
    }
    
    .btn-outline-info:hover {
        background-color: rgba(13, 202, 240, 0.2);
        color: white;
    }
    
    .btn-outline-light {
        color: white;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .text-muted {
        color: rgba(255, 255, 255, 0.6) !important;
    }
    
    .maxDev {
        background-color: rgba(13, 202, 240, 0.2) !important;
        border: 1px solid rgba(13, 202, 240, 0.3);
        padding: 4px 8px !important;
        border-radius: 50px !important;
    }
    
    textarea {
        min-height: 100px;
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

<div class="row pb-5 justify-content-center">
    <div class="col-lg-8">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
    <div class="col-lg-8 mb-3 animate-in">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col pt-1">
                        <i class="bi bi-key-fill me-2"></i> Key Information
                    </div>
                    <div class="col">
                        <div class="text-end">
                            <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys/generate') ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Generate Key"><i class="bi bi-plus-circle"></i></a>
                            <a class="btn btn-sm btn-outline-light" href="<?= site_url('keys') ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View All Keys"><i class="bi bi-list"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open('keys/edit') ?>

                <?= csrf_field() ?>
                <div class="row">
                    <input type="hidden" name="id_keys" value="<?= $key->id_keys ?>">
                    <?php if (($user->level == 1) || ($user->level ==2)) : ?>
                        <div class="col-lg-6 mb-3">
                            <label for="game" class="form-label">Games</label>
                            <input type="text" name="game" id="game" class="form-control" placeholder="RandomKey" aria-describedby="help-game" value="<?= old('game') ?: $key->game ?>">
                            <?php if ($validation->hasError('game')) : ?>
                                <small id="help-game" class="text-danger"><?= $validation->getError('game') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="user_key" class="form-label">User Key</label>
                            <input type="text" name="user_key" id="user_key" class="form-control" placeholder="RandomKey" aria-describedby="help-user_key" value="<?= old('user_key') ?: $key->user_key ?>">
                            <?php if ($validation->hasError('user_key')) : ?>
                                <small id="help-user_key" class="text-danger"><?= $validation->getError('user_key') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="duration" class="form-label">Duration <small class="text-muted">(in hours)</small></label>
                            <input type="number" name="duration" id="duration" class="form-control" placeholder="3" aria-describedby="help-duration" value="<?= old('duration') ?: $key->duration ?>">
                            <?php if ($validation->hasError('duration')) : ?>
                                <small id="help-duration" class="text-danger"><?= $validation->getError('duration') ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="max_devices" class="form-label">Max Devices</label>
                            <input type="number" name="max_devices" id="max_devices" class="form-control" placeholder="3" aria-describedby="help-max_devices" value="<?= old('max_devices') ?: $key->max_devices ?>">
                            <?php if ($validation->hasError('max_devices')) : ?>
                                <small id="help-max_devices" class="text-danger"><?= $validation->getError('max_devices') ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-3" id="col-status">
                        <label for="status" class="form-label">Status</label>
                        <?php $sel_status = ['' => '&mdash; Select Status &mdash;', '0' => 'Banned/Block', '1' => 'Active',]; ?>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'status', 'id' => 'status'], $sel_status, $key->status) ?>
                        <?php if ($validation->hasError('status')) : ?>
                            <small id="help-status" class="text-danger"><?= $validation->getError('status') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="registrator" class="form-label">Registrator</label>
                        <input type="text" name="registrator" id="registrator" class="form-control" placeholder="nata" aria-describedby="help-registrator" value="<?= old('registrator') ?: $key->registrator ?>">
                        <?php if ($validation->hasError('registrator')) : ?>
                            <small id="help-registrator" class="text-danger"><?= $validation->getError('registrator') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="expired_date" class="form-label">Expired <?= !$key->expired_date ? '<span class="badge bg-warning text-dark">(Not started yet)</span>' : ''  ?></label>
                        <input type="text" name="expired_date" id="expired_date" class="form-control" placeholder="<?= $time::now() ?>" aria-describedby="help-expired_date" value="<?= old('expired_date') ?: $key->expired_date ?>">
                        <?php if ($validation->hasError('expired_date')) : ?>
                            <small id="help-expired_date" class="text-danger"><?= $validation->getError('expired_date') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-12 mb-4">
                        <label for="devices" class="form-label">Devices <span class="maxDev"><?= $key_info->total ?>/<?= $key->max_devices ?></span> <small class="text-muted">(Separately with enter)</small></label>
                        <textarea class="form-control" name="devices" id="devices" rows="<?= ($key_info->total > $key->max_devices) ? 3 : $key_info->total ?>"><?= old('devices') ?: ($key_info->total ? $key_info->devices : '') ?></textarea>
                        <?php if ($validation->hasError('devices')) : ?>
                            <small id="help-devices" class="text-danger"><?= $validation->getError('devices') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-6">
                        <button class="btn btn-outline-info btnUpdate w-100" disabled>
                            <i class="bi bi-save me-2"></i> Update User Key
                        </button>
                    </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $(document).ready(function() {
        var level = "<?= $user->level ?>";
        if (level != 1) $("#registrator, #expired_date, #devices").attr('disabled', true);
        $("input, select, textarea").change(function() {
            $(".btnUpdate").attr('disabled', false);
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Animation
        const animatedElements = document.querySelectorAll('.animate-in');
        animatedElements.forEach(element => {
            element.style.animationPlayState = 'running';
        });
    });
    
    var total = "<?= $key_info->total ?>";
    $("#max_devices").change(function() {
        $(".maxDev").html(total + '/' + $(this).val());
        $("#devices").attr('rows', $(this).val());
    });
</script>
<?= $this->endSection() ?>
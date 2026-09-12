<?php
include('conn.php');
include('mail.php');

// for maintainece mode
$sql1 ="select * from onoff where id=1";
$result1 = kq($sql1);
$userDetails1 = $result1->fetch_assoc();

// for ftext and status
$sql2 ="select * from _ftext where id=1";
$result2 = kq($sql2);
$userDetails2 = $result2->fetch_assoc();

// for Features Status
$sql3 = "SELECT * FROM Feature WHERE id=1";
$result3 = kq($sql3);
$ModFeatureStatus = $result3->fetch_assoc();

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
    
    .input-group-text {
        background-color: rgba(93, 63, 159, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .btn-outline-primary, .btn-outline-danger, .btn-outline-warning, .btn-outline-success {
        color: white !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        background-color: rgba(93, 63, 159, 0.3) !important;
    }
    
    .btn-outline-primary:hover {
        background-color: rgba(13, 110, 253, 0.2) !important;
        color: white !important;
    }
    
    .btn-outline-danger:hover {
        background-color: rgba(220, 53, 69, 0.2) !important;
        color: white !important;
    }
    
    .btn-outline-warning:hover {
        background-color: rgba(255, 193, 7, 0.2) !important;
        color: white !important;
    }
    
    .btn-outline-success:hover {
        background-color: rgba(25, 135, 84, 0.2) !important;
        color: white !important;
    }
    
    /* Switch styling */
    .hacks {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding: 10px 15px;
        margin-bottom: 10px;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .hacks:hover {
        background-color: rgba(255, 255, 255, 0.15);
    }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: .4s;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }
    
    input:checked + .slider {
        background-color: #a78bfa;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .slider.round {
        border-radius: 34px;
    }
    
    .slider.round:before {
        border-radius: 50%;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <?= $this->include('Layout/msgStatus') ?>
    </div>
</div>

<div class="row">
    <?php if($user->level != 2) : ?>
    <div class="col-lg-6">
        <div class="card mb-3 animate-in" style="animation-delay: 0.1s">
            <div class="card-header">
                <i class="bi bi-server me-2"></i> 𝑺𝒆𝒓𝒗𝒆𝒓 𝑩𝒂𝒔𝒆𝒅 𝑴𝒐𝒅
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <input type="hidden" name="status_form" value="1">
                <div class="form-group mb-3">
                    <label for="status" class="mb-2">Current Maintenance Mode: <span class="text-info"><?php echo $userDetails1['status']; ?></span></label>
                    <div class="mb-3">
                        <label id="esp" class="hacks">
                            𝐌𝐚𝐢𝐧𝐭𝐞𝐧𝐚𝐧𝐜𝐞 𝐌𝐨𝐝𝐞
                            <div class="switch">
                                <input type="checkbox" name="radios" id="radio" value="on" <?php if ($userDetails1['status'] == "on"){?> checked="checked" <?php } ?>>
                                <span class="slider round"/>
                            </div>
                        </label>
                    </div>
                    <label for="modname" class="mb-2">𝑶𝒇𝒇𝒍𝒊𝒏𝒆 𝑴𝒔𝒈: <span class="text-info"><?php echo $userDetails1['myinput']; ?></span></label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Offline Msg</span>
                        <textarea class="form-control" placeholder="𝑆𝑒𝑟𝑣𝑒𝑟 𝑖𝑠 𝑈𝑛𝑑𝑒𝑟 𝑀𝑎𝑖𝑛𝑡𝑎𝑖𝑛𝑎𝑛𝑐𝑒" name="myInput" id="myInput" rows="1"></textarea>
                    </div>
                    <?php if ($validation->hasError('modname')) : ?>
                        <small id="help-modname" class="text-danger"><?= $validation->getError('modname') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group my-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-clockwise me-2"></i> 𝑼𝒑𝒅𝒂𝒕𝒆
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-lg-6">
        <div class="card mb-3 animate-in" style="animation-delay: 0.2s">
            <div class="card-header">
                <i class="bi bi-toggles me-2"></i> 𝐌𝐨𝐝 𝐅𝐞𝐚𝐭𝐮𝐫𝐞
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <input type="hidden" name="feature_form" value="1">
                <div class="form-group mb-3">
                    <label for="status" class="mb-3">Current Status:</label>
                    <div class="mb-2 text-info small">
                        ESP: <?php echo $ModFeatureStatus['ESP']; ?> | 
                        Items: <?php echo $ModFeatureStatus['Item']; ?> | 
                        AIM: <?php echo $ModFeatureStatus['AIM']; ?> | 
                        SilentAim: <?php echo $ModFeatureStatus['SilentAim']; ?> | 
                        BulletTrack: <?php echo $ModFeatureStatus['BulletTrack']; ?> | 
                        Memory: <?php echo $ModFeatureStatus['Memory']; ?> | 
                        Floating Texts: <?php echo $ModFeatureStatus['Floating']; ?> | 
                        Setting: <?php echo $ModFeatureStatus['Setting']; ?>
                    </div>
                    
                    <label id="ESP" class="hacks">
                        𝐄𝐒𝐏
                        <div class="switch">
                            <input type="checkbox" name="ESP" id="ESP" value="on" <?php if ($ModFeatureStatus['ESP'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="Item" class="hacks">
                        Items
                        <div class="switch">
                            <input type="checkbox" name="Item" id="Item" value="on" <?php if ($ModFeatureStatus['Item'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="AIM" class="hacks">
                        𝐀𝐢𝐦-𝐁𝐨𝐭
                        <div class="switch">
                            <input type="checkbox" name="AIM" id="AIM" value="on" <?php if ($ModFeatureStatus['AIM'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="SilentAim" class="hacks">
                        Silent Aim
                        <div class="switch">
                            <input type="checkbox" name="SilentAim" id="SilentAim" value="on" <?php if ($ModFeatureStatus['SilentAim'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="BulletTrack" class="hacks">
                        𝐁𝐮𝐥𝐥𝐞𝐭 𝐓𝐫𝐚𝐜𝐤
                        <div class="switch">
                            <input type="checkbox" name="BulletTrack" id="BulletTrack" value="on" <?php if ($ModFeatureStatus['BulletTrack'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="Memory" class="hacks">
                        Memory
                        <div class="switch">
                            <input type="checkbox" name="Memory" id="Memory" value="on" <?php if ($ModFeatureStatus['Memory'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="Floating" class="hacks">
                        Floating Texts
                        <div class="switch">
                            <input type="checkbox" name="Floating" id="Floating" value="on" <?php if ($ModFeatureStatus['Floating'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                    <label id="Setting" class="hacks">
                        Settings
                        <div class="switch">
                            <input type="checkbox" name="Setting" id="Setting" value="on" <?php if ($ModFeatureStatus['Setting'] == "on"){?> checked="checked" <?php } ?>>
                            <span class="slider round"/>
                        </div>
                    </label>
                </div>
                <div class="form-group my-2">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-arrow-clockwise me-2"></i> 𝑼𝒑𝒅𝒂𝒕𝒆
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card mb-3 animate-in" style="animation-delay: 0.3s">
            <div class="card-header">
                <i class="bi bi-pencil-square me-2"></i> 𝑪𝒉𝒂𝒏𝒈𝒆 𝑴𝒐𝒅 𝑵𝒂𝒎𝒆
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <input type="hidden" name="modname_form" value="1">
                <div class="form-group mb-3">
                    <label for="modname" class="mb-2">Current Mod Name: <span class="text-info"><?php echo $row['modname']; ?></span></label>
                    <input type="text" name="modname" id="modname" class="form-control mt-2" placeholder="𝐸𝑛𝑡𝑒𝑟 𝑌𝑜𝑢𝑟 𝑁𝑒𝑤 𝑀𝑜𝑑 𝑁𝑎𝑚𝑒" aria-describedby="help-modname" REQUIRED>
                    <?php if ($validation->hasError('modname')) : ?>
                        <small id="help-modname" class="text-danger"><?= $validation->getError('modname') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group my-2">
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-clockwise me-2"></i> 𝑼𝒑𝒅𝒂𝒕𝒆
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card mb-3 animate-in" style="animation-delay: 0.4s">
            <div class="card-header">
                <i class="bi bi-chat-text me-2"></i> 𝑪𝒉𝒂𝒏𝒈𝒆 𝑭𝒍𝒐𝒂𝒕𝒊𝒏𝒈 𝑻𝒆𝒙𝒕
            </div>
            <div class="card-body">
                <?= form_open() ?>
                <?= csrf_field() ?>
                <input type="hidden" name="_ftext" value="1">
                <div class="form-group mb-3">
                    <label for="status" class="mb-2">Current Mod Status: <span class="text-info"><?php echo $userDetails2['_status']; ?></span></label>
                    <div class="mb-3">
                        <label id="esp" class="hacks">
                            𝐒𝐚𝐟𝐞 𝐌𝐨𝐝𝐞
                            <div class="switch">
                                <input type="checkbox" name="_ftextr" id="_ftextr" value="Safe" <?php if ($userDetails2['_status'] == "Safe"){?> checked="checked" <?php } ?>>
                                <span class="slider round"/>
                            </div>
                        </label>
                    </div>
                    <label for="_ftext" class="mb-2">Current Floating Text: <span class="text-info"><?php echo $userDetails2['_ftext']; ?></span></label>
                    <input type="text" name="_ftext" id="_ftext" class="form-control mt-2" placeholder="𝐺𝑖𝑣𝑒 𝐹𝑒𝑒𝑑𝑏𝑎𝑐𝑘 𝐸𝑙𝑠𝑒 𝐾𝑒𝑦 𝑅𝑒𝑚𝑜𝑣𝑒𝑑!" aria-describedby="help-_ftext" REQUIRED>
                    <?php if ($validation->hasError('_ftext')) : ?>
                        <small id="help-_ftext" class="text-danger"><?= $validation->getError('_ftext') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group my-2">
                    <button type="submit" class="btn btn-outline-success">
                        <i class="bi bi-arrow-clockwise me-2"></i> 𝑼𝒑𝒅𝒂𝒕𝒆
                    </button>
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

<style>
    .animate-in {
        animation: fadeIn 0.5s ease-out forwards;
        animation-play-state: paused;
        opacity: 0;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
<?= $this->endSection() ?>
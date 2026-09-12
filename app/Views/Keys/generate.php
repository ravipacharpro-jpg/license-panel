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
    
    .btn-outline-dark {
        color: white;
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .btn-outline-dark:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: rgba(255, 255, 255, 0.5);
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.2);
        border-color: rgba(40, 167, 69, 0.3);
        color: #4ade80;
    }
    
    .key-sensi {
        filter: blur(4px);
        transition: filter 0.3s ease;
    }
    
    .key-sensi:hover {
        filter: blur(0);
    }
    
    .form-check-input {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .form-check-input:checked {
        background-color: #a78bfa;
        border-color: #a78bfa;
    }
    
    .form-check-label {
        color: white;
    }
    
    .copy-btn {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .copy-btn:hover {
        color: #a78bfa;
        transform: scale(1.2);
    }
    
    .key-display {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 10px 15px;
        border-radius: 8px;
        margin: 15px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    textarea {
        width: 0%;
        height: 0px;
        padding: 0x 0px;
        border: 0px solid #00000000; 
        border-radius: 0px;
        background-color: #00000000;
        font-size: 16px;
        resize: none;
    }
    
    /* Keep your existing button styles */
    .button-49,
    .button-49:after {
        /* existing styles */
    }
    
    .button-85 {
        /* existing styles */
    }
</style>

<div class="row justify-content-center mt-4">
    <div class="col-lg-6">
        <?= $this->include('Layout/msgStatus') ?>
        <?php if (session()->getFlashdata('user_key')) : ?>
            <div class="card mb-4 animate-in" style="animation-delay: 0.1s">
                <div class="card-header">
                    <i class="bi bi-check-circle-fill me-2"></i> License Generated Successfully
                </div>
                <div class="card-body">
                    <div class="alert alert-success" role="alert">
                        <div class="mb-2"><strong>Game:</strong> <?= session()->getFlashdata('game') ?> / <?= session()->getFlashdata('duration') ?> Hours</div>
                        <div class="mb-2"><strong>License:</strong> <span class="key-sensi"><?= session()->getFlashdata('user_key') ?></span></div>
                        <div class="mb-2"><strong>Available for:</strong> <?= session()->getFlashdata('max_devices') ?> Devices</div>
                        <small>
                            <i>Duration will start when license login.</i>
                        </small>
                    </div>
                    
                    <div class="key-display">
                        <strong><?= session()->getFlashdata('user_key') ?></strong>
                        <i class="bi bi-clipboard copy-btn" name="copy" id="copybtn" onclick="copyText()"></i>
                    </div>
                    
                    <textarea type="" name="mytext" id="mytext"><?= session()->getFlashdata('user_key') ?></textarea>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card animate-in" style="animation-delay: 0.2s">
            <div class="card-header">
                <div class="row">
                    <div class="col pt-1">
                        <i class="bi bi-key-fill me-2"></i> Create License
                    </div>
                    <div class="col text-end">
                        <a class="btn btn-sm btn-outline-dark" href="<?= site_url('keys') ?>"><i class="bi bi-people"></i> View Keys</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <?= csrf_field() ?>
                <div class="row">
                    <div class="form-group col-lg-6 mb-3">
                        <label for="game" class="form-label">Game</label>
                        <?= form_dropdown(['class' => 'form-select', 'name' => 'game', 'id' => 'game'], $game, old('game') ?: '') ?>
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-game" class="text-danger"><?= $validation->getError('game') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group col-lg-6 mb-3">
                        <label for="max_devices" class="form-label">Max Devices</label>
                        <input type="number" name="max_devices" id="max_devices" class="form-control" placeholder="1" value="<?= old('max_devices') ?: 1 ?>">
                        <?php if ($validation->hasError('game')) : ?>
                            <small id="help-max_devices" class="text-danger"><?= $validation->getError('max_devices') ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="duration" class="form-label">Duration</label>
                    <?= form_dropdown(['class' => 'form-select', 'name' => 'duration', 'id' => 'duration'], $duration, old('duration') ?: '') ?>
                    <?php if ($validation->hasError('duration')) : ?>
                        <small id="help-duration" class="text-danger"><?= $validation->getError('duration') ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" minlength="4" maxlength="16" value="" name="check" onchange="fupi(this)" id="check">
                    <label class="form-check-label" for="check">
                        Custom Key
                    </label>
                </div>

                <div class="form-group mb-3">
                    <label for="custom" id="cuslabel" class="form-label">Input Your Key</label>
                    <input type="text" minlength="4" maxlength="16" name="cuslicense" class="form-control" id="custom">
                </div>
                
                <div class="form-group mb-3">
                    <label for="hulala" id="labula" class="form-label">Bulk Keys</label>         
                    <select class="form-select" aria-label="Default select example" id="hulala" name="loopcount">
                        <option value="5">1 Keys</option>
                        <option value="1">5 Keys</option>
                        <option value="2">10 Keys</option>
                        <option value="3">25 Keys</option>
                        <option value="3">50 Keys</option>
                        <option value="4">100 Keys</option>
                    </select>
                </div>
                
                <input type="text" id="textinput" name="custominput" hidden>
                
                <div class="form-group mb-4">
                    <label for="estimation" class="form-label">Estimation</label>
                    <input type="text" id="estimation" class="form-control" placeholder="Your order will total" readonly>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-dark w-100">
                        <i class="bi bi-key me-2"></i> Generate License
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
    
    function copyText() {
        var mytext = document.getElementById("mytext"); 
        mytext.select();
        document.execCommand("copy");
        
        // Show feedback
        const copyBtn = document.getElementById("copybtn");
        copyBtn.classList.remove("bi-clipboard");
        copyBtn.classList.add("bi-clipboard-check");
        
        setTimeout(() => {
            copyBtn.classList.remove("bi-clipboard-check");
            copyBtn.classList.add("bi-clipboard");
        }, 2000);
    }
</script>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $(document).ready(function() {
        var price = JSON.parse('<?= $price ?>');
        getPrice(price);
        // When selected
        $("#max_devices, #duration, #game").change(function() {
            getPrice(price);
        });
        // try to get price
        function getPrice(price) {
            var price = price;
            var device = $("#max_devices").val();
            var durate = $("#duration").val();
            var gprice = price[durate];
            if (gprice != NaN) {
                var result = (device * gprice);
                $("#estimation").val(result);
            } else {
                $("#estimation").val('Estimation error');
            }
        }
    });
    
    function getOption() {
        var kop = document.getElementById('keysmode').value;
    }
    
    $(document).ready(function() {
        document.getElementById("custom").style.display = "none";
        document.getElementById("cuslabel").style.display = "none";
    });

    function fupi(obj) {
        if($(obj).is(":checked")){
            document.getElementById("custom").style.display = "block";
            document.getElementById("cuslabel").style.display = "block";
            $('#hulala option').prop('selected', function() {
                return this.defaultSelected;
            });
            document.getElementById("hulala").style.display = "none";
            document.getElementById("labula").style.display = "none";
            document.getElementById("textinput").value = "custom";
            const input = document.getElementById('custom');
            input.removeAttribute('required');
        } else {
            document.getElementById("custom").style.display = "none";
            document.getElementById("cuslabel").style.display = "none";
            document.getElementById("hulala").style.display = "block";
            document.getElementById("labula").style.display = "block";
            document.getElementById("textinput").value = "auto";
            const input = document.getElementById('custom');
        }
    }
</script>
<?= $this->endSection() ?>
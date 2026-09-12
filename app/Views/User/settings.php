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
    
    .form-control {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: rgba(167, 139, 250, 0.5);
        color: white;
        box-shadow: 0 0 0 0.25rem rgba(167, 139, 250, 0.25);
    }
    
    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    label {
        color: white;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .btn-outline-primary, .btn-outline-success {
        color: white;
        border-color: rgba(255, 255, 255, 0.3);
        background-color: rgba(93, 63, 159, 0.3);
    }
    
    .btn-outline-primary:hover {
        background-color: rgba(13, 110, 253, 0.2);
        color: white;
        border-color: rgba(13, 110, 253, 0.5);
    }
    
    .btn-outline-success:hover {
        background-color: rgba(25, 135, 84, 0.2);
        color: white;
        border-color: rgba(25, 135, 84, 0.5);
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
    <div class="col-lg-6 animate-in" style="animation-delay: 0.1s">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-key me-2"></i> Change Password
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <?= csrf_field() ?>
                <input type="hidden" name="password_form" value="1">
                <div class="form-group mb-3">
                    <label for="current">Current Password</label>
                    <input type="password" name="current" id="current" class="form-control mt-2" placeholder="Enter your current password">
                    <?php if ($validation->hasError('current')) : ?>
                        <small id="help-current" class="text-danger"><?= $validation->getError('current') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control mt-2" placeholder="Enter your new password" aria-describedby="help-password">
                    <?php if ($validation->hasError('password')) : ?>
                        <small id="help-password" class="text-danger"><?= $validation->getError('password') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3">
                    <label for="password2">Confirm Password</label>
                    <input type="password" name="password2" id="password2" class="form-control mt-2" placeholder="Confirm your new password" aria-describedby="help-password2">
                    <?php if ($validation->hasError('password2')) : ?>
                        <small id="help-password2" class="text-danger"><?= $validation->getError('password2') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group my-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-check-circle me-2"></i> Change Password
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 animate-in" style="animation-delay: 0.2s">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-circle me-2"></i> Account Information
            </div>
            <div class="card-body">
                <?= form_open() ?>

                <?= csrf_field() ?>
                <input type="hidden" name="fullname_form" value="1">
                <div class="form-group mb-3">
                    <label for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" class="form-control mt-2" placeholder="Enter your full name" aria-describedby="help-fullname" value="<?= old('fullname') ?: ($user->fullname ?: '') ?>">
                    <?php if ($validation->hasError('fullname')) : ?>
                        <small id="help-fullname" class="text-danger"><?= $validation->getError('fullname') ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group my-3">
                    <button type="submit" class="btn btn-outline-success">
                        <i class="bi bi-save me-2"></i> Update Account
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
<?= $this->endSection() ?>
<?php

function color($value) {
if($value == 1) {
return "#0000FF";
} else {
return "#FF0000";
}
}
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
    
    .alert-dark {
        background-color: rgba(33, 37, 41, 0.7);
        border-color: rgba(33, 37, 41, 0.8);
        color: white;
        backdrop-filter: blur(10px);
        border-radius: 10px;
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
    
    .btn-dark {
        background-color: rgba(33, 37, 41, 0.7);
        border-color: rgba(33, 37, 41, 0.8);
    }
    
    .btn-dark:hover {
        background-color: rgba(33, 37, 41, 0.9);
        border-color: rgba(33, 37, 41, 1);
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
    <div class="col-lg-12 animate-in" style="animation-delay: 0.1s">
        <div class="alert alert-dark" role="alert">
            <strong>INFO :</strong> Search specify user by their (username, fullname, saldo or uplink).
        </div>
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i class="bi bi-people-fill me-2"></i>
                    <h2 class="mb-0">𝐌𝐚𝐧𝐚𝐠𝐞 𝐔𝐬𝐞𝐫𝐬</h2>
                </div>
            </div>
            <div class="card-body">
                <?php if ($user_list) : ?>

                <div class="table-responsive">
                    <table id="usersTable" class="table table-bordered table-hover text-center" style="width:100%">
                        <thead>
                            <tr>
                                <th scope="row">ID</th>
                                <th>Username</th>
                                <th>Fullname</th>
                                <th>Level</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th>Uplink</th>
                                <th>Expiration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_list as $u) : ?>
                            <tr>
                                <td><?= $u->id_users ?></td>
                                <td><?= $u->username ?></td>
                                <td><?= $u->fullname ?></td>
                                <td>
                                <?php if($u->level == 1) : ?>
                                     <span class="badge bg-primary">Owner</span>
                                <?php elseif($u->level == 2) : ?>
                                     <span class="badge bg-info">Admin</span>
                                <?php else : ?>
                                     <span class="badge bg-secondary">Reseller</span>
                                <?php endif; ?>
                                </td>
                                <td>
                                <?php if($u->level == 1) : ?>
                                     <span class="text-primary">&infin;</span>
                                <?php else : ?>
                                     <span class="text-success"><?= $u->saldo ?></span>
                                <?php endif; ?>
                                </td>
                                <td>
                                <?php if($u->status == 1) : ?>
                                      <span class="badge bg-success">Active</span>
                                <?php elseif($u->status == 2) : ?>
                                      <span class="badge bg-danger">Banned</span>
                                <?php else : ?>
                                      <span class="badge bg-warning text-dark">Expired</span>
                                <?php endif; ?>
                                </td>
                                <td><?= $u->uplink ?></td>
                                <td><?= $u->expiration_date ?></td>
                                <td>
                                <a href="user/<?php echo $u->id_users ?>" class="btn btn-dark btn-sm"><i class="bi bi-pencil-square"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                       <tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('css') ?>
<?= link_tag("https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css") ?>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<?= script_tag("https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js") ?>

<?= script_tag("https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js") ?>
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "order": [[0, "desc"]]
        });
        
        // Animation
        const animatedElements = document.querySelectorAll('.animate-in');
        animatedElements.forEach(element => {
            element.style.animationPlayState = 'running';
        });
    });
</script>
<?= $this->endSection() ?>
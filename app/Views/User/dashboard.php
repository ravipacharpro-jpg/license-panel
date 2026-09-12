<?php
// Improve database connection handling
include('conn.php');
include('mail.php');
include('UserMail.php');

// Add connection error handling
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// For Credits
$sql = "SELECT * FROM credit where id=1";
$result = mysqli_query($conn, $sql);
$credit = mysqli_fetch_assoc($result);
mysqli_free_result($result);

// For Keys count
$sql = "SELECT COUNT(*) as id_keys FROM keys_code";
$result = mysqli_query($conn, $sql);
$keycount = mysqli_fetch_assoc($result);
mysqli_free_result($result);

// For Active Keys count
$sql = "SELECT COUNT(devices) as devices FROM keys_code";
$result = mysqli_query($conn, $sql);
$active = mysqli_fetch_assoc($result);
mysqli_free_result($result);

// For In-Active Keys Count
$sql = "SELECT COUNT(*) as devices FROM keys_code where devices IS NULL";
$result = mysqli_query($conn, $sql);
$inactive = mysqli_fetch_assoc($result);
mysqli_free_result($result);

// For Users Count
$sql = "SELECT COUNT(*) as id_users FROM users";
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_assoc($result);
mysqli_free_result($result);

$userid = session()->userid;
$sql = "SELECT `expiration_date` FROM `users` WHERE `id_users` = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$period = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Close the connection when done with all queries
mysqli_close($conn);

function HoursToDays($value)
{
    if($value == 1) {
       return "$value Hour";
    } else if($value >= 2 && $value < 24) {
       return "$value Hours";
    } else if($value == 24) {
       $darkespyt = $value/24;
       return "$darkespyt Day";
    } else if($value > 24) {
       $darkespyt = $value/24;
       return "$darkespyt Days";
    }
}

$dateTime = strtotime($period['expiration_date']);
$getDateTime = date("F d, Y H:i:s", $dateTime);
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
        font-family: 'Poppins', sans-serif;
    }
    
    .dashboard-container {
        padding: 20px;
    }
    
    .card {
        background-color: rgba(93, 63, 159, 0.7) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
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
        font-size: 1.2rem;
        padding: 15px 20px;
        text-align: center;
    }
    
    .card-body {
        padding: 20px;
        color: white;
    }
    
    #exp {
        font-family: 'Nova Mono', monospace;
        text-align: center;
        color: #fff;
        font-size: 3rem;
        text-shadow: 0px 0px 20px #a78bfa;
        margin: 0;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    .list-group-item {
        background-color: rgba(93, 63, 159, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        transition: all 0.3s ease;
        padding: 12px 20px;
        margin-bottom: 5px;
        border-radius: 8px;
    }
    
    .list-group-item:hover {
        background-color: rgba(93, 63, 159, 0.5);
        transform: translateX(5px);
    }
    
    .badge {
        padding: 8px 15px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
    }
    
    .badge.text-success {
        background-color: rgba(40, 167, 69, 0.2);
        color: #4ade80 !important;
    }
    
    .badge.text-danger {
        background-color: rgba(220, 53, 69, 0.2);
        color: #f87171 !important;
    }
    
    .badge.text-primary {
        background-color: rgba(13, 110, 253, 0.2);
        color: #60a5fa !important;
    }
    
    .badge.text-dark {
        background-color: rgba(255, 255, 255, 0.2);
        color: white !important;
    }
    
    .badge.text-muted {
        background-color: rgba(108, 117, 125, 0.2);
        color: #d1d5db !important;
    }
    
    .table {
        color: white;
    }
    
    .table-bordered {
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .stat-card {
        text-align: center;
        padding: 15px;
    }
    
    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: #a78bfa;
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 5px;
        background: linear-gradient(45deg, #a78bfa, #60a5fa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .stat-label {
        font-size: 1rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .animate-in {
        animation: fadeIn 0.5s ease-out forwards;
        opacity: 0;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media only screen and (max-width: 768px) {
        #exp { font-size: 2rem; }
        .stat-value { font-size: 1.8rem; }
    }
</style>

<div class="dashboard-container">
    <div class="row">
        <div class="col-lg-12">
            <?= $this->include('Layout/msgStatus') ?>
        </div>
        
        <!-- Stats Cards Row -->
        <div class="col-lg-12 mb-4">
            <div class="row">
                <div class="col-md-3 col-sm-6 animate-in" style="animation-delay: 0.1s">
                    <div class="card">
                        <div class="card-body stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $keycount['id_keys']; ?></div>
                            <div class="stat-label">Total Keys</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 animate-in" style="animation-delay: 0.2s">
                    <div class="card">
                        <div class="card-body stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-unlock-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $active['devices']; ?></div>
                            <div class="stat-label">Used Keys</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 animate-in" style="animation-delay: 0.3s">
                    <div class="card">
                        <div class="card-body stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $inactive['devices']; ?></div>
                            <div class="stat-label">Unused Keys</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 animate-in" style="animation-delay: 0.4s">
                    <div class="card">
                        <div class="card-body stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $users['id_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Expiration Card -->
        <div class="col-lg-8 animate-in" style="animation-delay: 0.5s">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> Expiration
                </div>
                <div class="card-body">
                    <p id="exp"></p>
                </div>
            </div>
        </div>
        
        <!-- User Info Card -->
        <div class="col-lg-4 animate-in" style="animation-delay: 0.6s">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2"></i> Information
                </div>
                <div class="card-body">
                    <ul class="list-group list-hover mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-fill me-2"></i> Role</span>
                            <span class="badge text-dark">
                                <?= getLevel($user->level) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-wallet-fill me-2"></i> Balance</span>
                            <span class="badge text-dark">
                                ₹<?= $user->saldo ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-fill me-2"></i> Login Time</span>
                            <span class="badge text-dark">
                                <?= $time::parse(session()->time_since)->humanize() ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-box-arrow-right me-2"></i> Auto Log-out</span>
                            <span class="badge text-dark">
                                <?= $time::now()->difference($time::parse(session()->time_login))->humanize() ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- History Card -->
        <div class="col-lg-8 animate-in" style="animation-delay: 0.7s">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i> History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover text-center">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Info</th>
                                    <th>Code</th>
                                    <th>Duration</th>
                                    <th>Devices</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h) : ?>
                                    <?php $in = explode("|", $h->info) ?>
                                    <tr>
                                        <td><span class="align-middle badge text-dark">#3812<?= $h->id_history ?></span></td>
                                        <td><?= $in[0] ?></td>
                                        <td><span class="align-middle badge text-dark"><?= $in[1] ?>**</span></td>
                                        <td><span class="align-middle badge text-dark"><?= HoursToDays($in[2]); ?></span></td>
                                        <td><span class="align-middle badge text-primary"><?= $in[3] ?> Devices</span></td>
                                        <td><i class="align-middle badge text-muted"><?= $time::parse($h->created_at)->humanize() ?></i></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Activity Chart -->
        <div class="col-lg-4 animate-in" style="animation-delay: 0.8s">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity me-2"></i> Activity
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 250px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Required JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Expiration Timer
    var countDownTimer = new Date("<?php echo "$getDateTime"; ?>").getTime();
    // Update the count down every 1 second
    var interval = setInterval(function() {
        var current = new Date().getTime();
        // Find the difference between current and the count down date
        var diff = countDownTimer - current;
        // Countdown Time calculation for days, hours, minutes and seconds
        var days = Math.floor(diff / (1000 * 60 * 60 * 24));
        var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById("exp").innerHTML = days + "d : " + hours + "h : " +
        minutes + "m : " + seconds + "s ";
        // Display Expired, if the count down is over
        if (diff < 0) {
            clearInterval(interval);
            document.getElementById("exp").innerHTML = "EXPIRED";
        }
    }, 1000);
    
    // Animation for elements
    document.addEventListener('DOMContentLoaded', function() {
        const animatedElements = document.querySelectorAll('.animate-in');
        animatedElements.forEach(element => {
            element.style.animationPlayState = 'running';
        });
        
        // Activity Chart (Doughnut)
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(activityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Used Keys', 'Unused Keys'],
                datasets: [{
                    data: [<?php echo $active['devices']; ?>, <?php echo $inactive['devices']; ?>],
                    backgroundColor: [
                        'rgba(74, 222, 128, 0.8)',
                        'rgba(248, 113, 113, 0.8)'
                    ],
                    borderColor: [
                        'rgba(74, 222, 128, 1)',
                        'rgba(248, 113, 113, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white',
                            padding: 20
                        }
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>
<?= $this->endSection() ?>
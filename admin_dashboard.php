<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$admin_username = $_SESSION['username']; 


$total_staff = 0;
$total_heads = 0;
$trainings_completed = 0;
$active_programs = 0;

$query_total_staff = "SELECT COUNT(*) AS total FROM users WHERE role IN ('staff', 'head')";
$result_total_staff = $conn->query($query_total_staff);
// total heads
$query_total_heads = "SELECT COUNT(*) AS total FROM users WHERE role = 'head'";
$result_total_heads = $conn->query($query_total_heads);
if ($result_total_heads) {
    $row = $result_total_heads->fetch_assoc();
    $total_heads = $row['total'];
}
if ($result_total_staff) {
    $row = $result_total_staff->fetch_assoc();
    $total_staff = $row['total'];
}


// Fixed: Only count trainings with status = 'completed'
if ($conn->query("SHOW TABLES LIKE 'trainings'")->num_rows == 1) {
    $query_trainings = "SELECT COUNT(*) AS total FROM user_trainings WHERE status = 'completed'";
    $result_trainings = $conn->query($query_trainings);
    if ($result_trainings) {     
        $row = $result_trainings->fetch_assoc();
        $trainings_completed = $row['total'];
    }
}


// This is also a placeholder for now
$active_programs = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        font-family: 'Montserrat', sans-serif;
        display: flex;
        background-color: #f0f2f5;
        transition: margin-left 0.3s ease-in-out;
    }

    .main-content {
        flex-grow: 1;
        padding: 2rem;
        transition: margin-left 0.3s ease-in-out;
    }

    @media (min-width: 992px) {
        .sidebar-lg {
            width: 250px;
            background-color: #1a237e;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .main-content {
            margin-left: 250px;
        }
    }

    body.toggled .sidebar-lg {
        width: 80px; 
        padding-top: 1rem;
        color: white; 
    }

    body.toggled .sidebar-lg .profile-pic {
        display: none; 
    }

    body.toggled .sidebar-lg .nav-link {
        text-align: center; 
        padding: 12px 0;
        color: white; 
    }

    body.toggled .sidebar-lg .nav-link i,
    body.toggled .sidebar-lg .nav-link span {
        margin: 0; 
        color: white;
    }

    body.toggled .sidebar-lg .nav-link span {
        display: none;
    }

    body.toggled .main-content {
        margin-left: 80px; 
    }



    .sidebar-lg .nav-link { 
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 5px;
        margin: 5px 15px;
        transition: background-color 0.2s;
        white-space: nowrap;
        overflow: hidden;
    }
    
    .sidebar-lg .nav-link:hover, .sidebar-lg .nav-link.active {
        background-color: #3f51b5;
    }
    
    .offcanvas .nav-link { color: #ffffff; }

    body.toggled .sidebar-lg h5 {
        display: none; 
    }

    body.toggled .main-content {
        margin-left: 80px;
    }

   
    body.toggled .profile-pic {
        display: none !important; 
    }

    /* Match staff/head sidebar toggle button style */
    .sidebar-lg .btn-toggle {
        background-color: transparent;
        border: none;
        color: #ffffff;
        padding: 6px 10px;
    }
    
    .sidebar-lg .btn-toggle:focus { 
        box-shadow: none; 
    }
    
    .sidebar-lg .btn-toggle:hover { 
        background-color: transparent; 
    }

    .stats-cards {
        display: flex;
        justify-content: space-around;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
        text-align: center;
        flex-basis: 30%;
    }

    .card h3 {
        margin: 0 0 0.5rem;
        color: #1a237e;
    }

    .card p {
        font-size: 2.5rem;
        font-weight: bold;
        color: #333;
    }

    .content-box {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    .content-box h2 {
        margin-top: 0;
        color: #1a237e;
        border-bottom: 2px solid #1a237e;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    th, td {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f2f2f2;
    }
</style>
</head>
<body id="body">

    <div class="offcanvas offcanvas-start bg-dark text-white d-lg-none" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SDU Admin</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item">
                    <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="staff_report.php">Directory & Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_profile.php">View Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="edit_profile.php">Edit Profile</a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>

   <div class="sidebar-lg d-none d-lg-block">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h5 class="m-0 text-white">SDU Admin</h5>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        
        <div class="profile-pic text-center mb-4">
            <div style="width: 80px; height: 80px; background-color: #ffffffff; border-radius: 50%; margin: 0 auto;"></div>
            <p class="mt-2 text-white">Admin</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="staff_report.php">
                    <i class="fas fa-users me-2"></i> <span>Directory & Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_profile.php">
                    <i class="fas fa-user me-2"></i> <span>View Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="edit_profile.php">
                    <i class="fas fa-user-edit me-2"></i> <span>Edit Profile</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </button>

        <?php if ($view === 'overview'): ?>
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</h1>
            </div>
            <div class="stats-cards">
                <div class="card">
                    <h3>Total Staff</h3>
                    <p><?php echo $total_staff; ?></p>
                </div>
                <div class="card">
                    <h3>Total Heads</h3>
                    <p><?php echo $total_heads; ?></p>
                </div>
                <div class="card">
                    <h3>Trainings Completed</h3>
                    <p><?php echo $trainings_completed; ?></p>
                </div>
            </div>
            <div class="content-box">
                <h2>Recent Activity</h2>
                <p>Content for recent activity will go here.</p>
            </div>
        <?php elseif ($view === 'staff-directory'): ?>
            <div class="content-box">
                <h2>Staff Directory</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Program</th>
                            <th>Job Function</th>
                            <th>Office</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query_staff = "SELECT * FROM users WHERE role IN ('staff', 'head')";
                        $result_staff = $conn->query($query_staff);
                        if ($result_staff->num_rows > 0) {
                            while ($staff = $result_staff->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($staff['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($staff['position']) . "</td>";
                                echo "<td>" . htmlspecialchars($staff['program']) . "</td>";
                                echo "<td>" . htmlspecialchars($staff['job_function']) . "</td>";
                                echo "<td>" . htmlspecialchars($staff['office']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No staff members found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'training-records'): ?>
            <div class="content-box">
                <h2>Training Records</h2>
                <p>This section will show a list of trainings and the staff who attended them. </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    const body = document.getElementById('body');
                    body.classList.toggle('toggled');
                });
            }
        });
    </script>
</body>
</html>
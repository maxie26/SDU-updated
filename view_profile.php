<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$stmt_fetch = $conn->prepare("SELECT u.username, u.email, u.role, u.created_at, s.position, s.program, s.job_function, s.office 
                              FROM users u 
                              LEFT JOIN staff_details s ON u.id = s.user_id 
                              WHERE u.id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data) {
    header("Location: login.php");
    exit();
}


$user_data['username'] = $user_data['username'] ?? '';
$user_data['email'] = $user_data['email'] ?? '';
$user_data['role'] = $user_data['role'] ?? '';
$user_data['position'] = $user_data['position'] ?? 'Not specified';
$user_data['program'] = $user_data['program'] ?? 'Not specified';
$user_data['job_function'] = $user_data['job_function'] ?? 'Not specified';
$user_data['office'] = $user_data['office'] ?? 'Not specified';
$user_data['created_at'] = $user_data['created_at'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; display: flex; background-color: #f0f2f5; }
        .main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }
        @media (min-width: 992px) { .main-content { margin-left: 250px; } }
        .sidebar { width: 250px; background-color: #1a237e; color: white; height: 100vh; position: fixed; padding-top: 2rem; transition: width 0.3s ease-in-out; }
        .sidebar .nav-link { color: white; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; white-space: nowrap; overflow: hidden; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #3f51b5; }
        .sidebar .btn-toggle { background: transparent; border: none; color: #fff; padding: 6px 10px; }
        @media (min-width: 992px) { body.toggled .sidebar { width: 80px; } body.toggled .main-content { margin-left: 80px; } body.toggled .sidebar .nav-link span { display: none; } }
        .content-box { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; max-width: 900px; margin: 0 auto; }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            flex: 0 0 30%;
        }
        .info-value {
            color: #212529;
            flex: 1;
            text-align: right;
        }
        .badge-role {
            font-size: 0.9rem;
            padding: 8px 16px;
            
        }
        .btn-edit {
            background: #1a237e;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .back-link {
            margin-bottom: 20px;
        }
        .back-link a {
            color: #1a237e;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #1a237e;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
        }
        .back-link a:hover { background-color: #1a237e; color: #ffffff; }
    </style>
</head>
<body id="body">
    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0"><?= $_SESSION['role'] === 'head' ? 'Office Head Dashboard' : ($_SESSION['role'] === 'admin' ? 'Admin Dashboard' : 'Staff Dashboard') ?></h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a class="nav-link" href="admin_dashboard.php?view=overview"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
                <?php elseif ($_SESSION['role'] === 'head'): ?>
                    <a class="nav-link" href="office_head_dashboard.php?view=overview"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
                <?php else: ?>
                    <a class="nav-link" href="staff_dashboard.php?view=overview"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
                <?php endif; ?>
            </li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="staff_report.php"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
            <?php elseif ($_SESSION['role'] === 'head'): ?>
            <li class="nav-item"><a class="nav-link" href="office_head_dashboard.php?view=office-directory"><i class="fas fa-users me-2"></i> <span>Office Directory</span></a></li>
            <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="staff_dashboard.php?view=training-records"><i class="fas fa-book-open me-2"></i> <span>Training Records</span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link active" href="view_profile.php"><i class="fas fa-user me-2"></i> <span>View Profile</span></a></li>
            <li class="nav-item"><a class="nav-link" href="edit_profile.php"><i class="fas fa-user-edit me-2"></i> <span>Edit Profile</span></a></li>
            <li class="nav-item mt-auto"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-box">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr($user_data['username'], 0, 2)); ?></div>
                <h2 class="mb-2"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                <span class="badge bg-primary"><?php echo ucfirst($user_data['role']); ?></span>
            </div>
            <div class="profile-info">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-envelope me-2"></i>Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>
                <?php if ($_SESSION['role'] !== 'admin'): ?>
                <div class="info-row"><div class="info-label"><i class="fas fa-briefcase me-2"></i>Position</div><div class="info-value"><?php echo htmlspecialchars($user_data['position']); ?></div></div>
                <div class="info-row"><div class="info-label"><i class="fas fa-tasks me-2"></i>Job Function</div><div class="info-value"><?php echo htmlspecialchars($user_data['job_function']); ?></div></div>
                <div class="info-row"><div class="info-label"><i class="fas fa-building me-2"></i>Office</div><div class="info-value"><?php echo htmlspecialchars($user_data['office']); ?></div></div>
                <?php endif; ?>
                <div class="info-row"><div class="info-label"><i class="fas fa-calendar-plus me-2"></i>Member Since</div><div class="info-value"><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></div></div>
            </div>
            <div class="text-center mt-4">
                <a href="edit_profile.php" class="btn-edit"><i class="fas fa-edit"></i> Edit Profile</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('sidebar-toggle');
            if (btn) { btn.addEventListener('click', function(){ var b = document.getElementById('body') || document.body; b.classList.toggle('toggled'); }); }
        });
    </script>
</body>
</html>

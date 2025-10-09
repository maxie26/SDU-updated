<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','head']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_GET['id'];

$stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ? AND role IN ('staff', 'head')");
$stmt_user->bind_param("i", $staff_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$stmt_user->close();

if (!$user_data) {
    echo "Staff member not found.";
    exit();
}

$staff_username = $user_data['username'];

// If viewer is a head, enforce same office restriction
if ($_SESSION['role'] === 'head') {
    $head_id = $_SESSION['user_id'];
    $head_office = '';
    $staff_office = '';
    $stmt = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $head_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { $head_office = $row['office'] ?? ''; }
    $stmt->close();

    $stmt = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { $staff_office = $row['office'] ?? ''; }
    $stmt->close();

    if (empty($head_office) || $head_office !== $staff_office) {
        http_response_code(403);
        echo "You are not authorized to view this staff member's trainings.";
        exit();
    }
}

$query_records = "SELECT t.title, ut.completion_date FROM user_trainings ut JOIN trainings t ON ut.training_id = t.id WHERE ut.user_id = ?";
$stmt_records = $conn->prepare($query_records);
$stmt_records->bind_param("i", $staff_id);
$stmt_records->execute();
$result_records = $stmt_records->get_result();
$stmt_records->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Records for <?php echo htmlspecialchars($staff_username); ?></title>
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
        .content-box { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; max-width: 1000px; margin: 0 auto; }
    </style>
</head>
<body id="body">
    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0"><?= $_SESSION['role'] === 'admin' ? 'Admin Dashboard' : 'Office Head Dashboard' ?></h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php?view=overview"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="staff_report.php"><i class="fas fa-users me-2"></i> <span>Directory & Reports</span></a></li>
            <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="office_head_dashboard.php?view=overview"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="office_head_dashboard.php?view=office-directory"><i class="fas fa-users me-2"></i> <span>Office Directory</span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="view_profile.php"><i class="fas fa-user me-2"></i> <span>View Profile</span></a></li>
            <li class="nav-item"><a class="nav-link" href="edit_profile.php"><i class="fas fa-user-edit me-2"></i> <span>Edit Profile</span></a></li>
            <li class="nav-item mt-auto"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Training Records for <?php echo htmlspecialchars($staff_username); ?></h2>
                <?php $back = ($_SESSION['role']==='admin') ? 'admin_dashboard.php?view=overview' : 'office_head_dashboard.php?view=office-directory'; ?>
                <a href="<?php echo $back; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            </div>

            <?php if ($result_records->num_rows > 0): ?>
                <table class="table table-striped mt-4">
                    <thead>
                        <tr>
                            <th scope="col">Training Title</th>
                            <th scope="col">Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['completion_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info mt-4" role="alert">
                    This staff member has no completed trainings.
                </div>
            <?php endif; ?>
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
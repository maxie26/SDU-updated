<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head') {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$username = $_SESSION['username'];
$head_user_id = $_SESSION['user_id'];

// fetch head office
$office = '';
$stmt_office = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
$stmt_office->bind_param("i", $head_user_id);
$stmt_office->execute();
$res_office = $stmt_office->get_result();
if ($row = $res_office->fetch_assoc()) {
    $office = $row['office'] ?? '';
}
$stmt_office->close();

// overview stats for head (within office)
$total_staff_in_office = 0;
$completed_trainings_in_office = 0;

if (!empty($office)) {
    $stmt_count_staff = $conn->prepare("SELECT COUNT(*) AS total FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.role = 'staff' AND s.office = ?");
    $stmt_count_staff->bind_param("s", $office);
    $stmt_count_staff->execute();
    $result_staff_count = $stmt_count_staff->get_result();
    if ($result_staff_count) {
        $row = $result_staff_count->fetch_assoc();
        $total_staff_in_office = (int)$row['total'];
    }
    $stmt_count_staff->close();

    $stmt_completed = $conn->prepare("SELECT COUNT(*) AS total FROM user_trainings ut JOIN users u ON ut.user_id = u.id LEFT JOIN staff_details s ON u.id = s.user_id WHERE ut.status = 'completed' AND s.office = ?");
    $stmt_completed->bind_param("s", $office);
    $stmt_completed->execute();
    $result_completed = $stmt_completed->get_result();
    if ($result_completed) {
        $row = $result_completed->fetch_assoc();
        $completed_trainings_in_office = (int)$row['total'];
    }
    $stmt_completed->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Head Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            background-color: #f0f2f5;
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem;
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
        }
        .sidebar {
            width: 250px;
            background-color: #1a237e;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .sidebar .nav-link { color: white; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #3f51b5; }
        .content-box { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; }
        /* Transparent sidebar toggle like admin */
        .sidebar .btn-toggle { background-color: transparent; border: none; color: #ffffff; padding: 6px 10px; }
        .sidebar .btn-toggle:focus { box-shadow: none; }
        .sidebar .btn-toggle:hover { background-color: transparent; }
        @media (min-width: 992px) {
            body.toggled .sidebar { width: 80px; }
            body.toggled .main-content { margin-left: 80px; }
            .sidebar .nav-link { transition: all 0.2s; }
            body.toggled .sidebar .nav-link { text-align: center; padding: 12px 0; }
            body.toggled .sidebar .nav-link i { margin-right: 0; }
            body.toggled .sidebar .nav-link span { display: none; }
            body.toggled .sidebar h3 { display: none; }
        }
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; text-align: center; }
        .card h3 { margin: 0 0 0.5rem; color: #1a237e; font-size: 1rem; font-weight: 700; }
        .card p { font-size: 2.5rem; font-weight: bold; color: #333; margin: 0; }
    </style>
    </head>
<body id="body">

    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0">Office Head Dashboard</h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'office-directory' ? 'active' : '' ?>" href="?view=office-directory">
                    <i class="fas fa-users me-2"></i> <span>Office Staff Directory</span>
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
        <?php if ($view === 'overview'): ?>
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Your office: <strong><?php echo htmlspecialchars($office ?: 'Not set'); ?></strong></p>
            </div>
            <div class="stats-cards">
                <div class="card">
                    <h3>Staff in Your Office</h3>
                    <p><?php echo $total_staff_in_office; ?></p>
                </div>
                <div class="card">
                    <h3>Completed Trainings (Office)</h3>
                    <p><?php echo $completed_trainings_in_office; ?></p>
                </div>
            </div>
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Your Actions</h2>
                    <a href="add_training.php" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Add Training</a>
                </div>
                <p>Use the Office Staff Directory to view and review trainings of staff in your office.</p>
            </div>
        <?php elseif ($view === 'office-directory'): ?>
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Office Staff Directory</h2>
                </div>
                <?php
                if (!empty($office)) {
                    $stmt = $conn->prepare("SELECT u.id, u.username, u.email, s.position, s.program, s.job_function FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.role = 'staff' AND s.office = ? ORDER BY u.username");
                    $stmt->bind_param("s", $office);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = false;
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Program</th>
                                <th>Job Function</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="view_staff_trainings.php?id=<?php echo $row['id']; ?>">
                                            <i class="fas fa-eye"></i> View Trainings
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No staff found or office not set.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('sidebar-toggle');
            if (btn) {
                btn.addEventListener('click', function(){
                    var b = document.getElementById('body') || document.body;
                    b.classList.toggle('toggled');
                });
            }
        });
    </script>
</body>
</html>



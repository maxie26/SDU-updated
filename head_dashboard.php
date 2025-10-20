<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head') {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$head_user_id = $_SESSION['user_id'];
$head_username = $_SESSION['username'];

// Get head office
$stmt_office = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
$stmt_office->bind_param("i", $head_user_id);
$stmt_office->execute();
$office_res = $stmt_office->get_result();
$head_office_row = $office_res->fetch_assoc();
$stmt_office->close();
$head_office = $head_office_row['office'] ?? null;

// Stats for overview
$total_staff_in_office = 0;
$completed_trainings_in_office = 0;

if ($head_office) {
    // Count staff in office
    $stmt_count_staff = $conn->prepare("SELECT COUNT(*) AS total FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.role = 'staff' AND s.office = ?");
    $stmt_count_staff->bind_param("s", $head_office);
    $stmt_count_staff->execute();
    $res_count_staff = $stmt_count_staff->get_result();
    if ($res_count_staff) { $total_staff_in_office = (int)$res_count_staff->fetch_assoc()['total']; }
    $stmt_count_staff->close();

    // Count completed trainings in office
    $stmt_count_train = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM user_trainings ut
         JOIN users u ON ut.user_id = u.id
         LEFT JOIN staff_details s ON u.id = s.user_id
         WHERE u.role = 'staff' AND s.office = ? AND ut.status = 'completed'"
    );
    $stmt_count_train->bind_param("s", $head_office);
    $stmt_count_train->execute();
    $res_count_train = $stmt_count_train->get_result();
    if ($res_count_train) { $completed_trainings_in_office = (int)$res_count_train->fetch_assoc()['total']; }
    $stmt_count_train->close();
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
    }
    .sidebar {
        width: 250px;
        background-color: #1a237e;
        color: white;
        height: 100vh;
        position: fixed;
        padding-top: 2rem;
    }
    .sidebar .nav-link {
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        margin: 5px 15px;
        transition: background-color 0.2s;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        background-color: #3f51b5;
    }
    .content-box {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 1.5rem;
        text-align: center;
    }
    .card h3 { margin: 0 0 0.5rem; color: #1a237e; font-size: 0.9rem; }
    .card p { font-size: 2rem; font-weight: bold; color: #333; margin: 0; }
</style>
</head>
<body>

    <div class="sidebar">
        <h3 class="text-center mb-4">Head Dashboard</h3>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i>Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'staff' ? 'active' : '' ?>" href="?view=staff">
                    <i class="fas fa-users me-2"></i>My Office Staff
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'trainings' ? 'active' : '' ?>" href="?view=trainings">
                    <i class="fas fa-book-open me-2"></i>Staff Trainings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_profile.php">
                    <i class="fas fa-user me-2"></i>My Profile
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <?php if ($view === 'overview'): ?>
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($head_username); ?>!</h1>
                <p>Overview of your office.</p>
            </div>
            <?php if ($head_office): ?>
            <div class="stats-cards">
                <div class="card">
                    <h3>Staff in Office</h3>
                    <p><?php echo $total_staff_in_office; ?></p>
                </div>
                <div class="card">
                    <h3>Completed Trainings</h3>
                    <p><?php echo $completed_trainings_in_office; ?></p>
                </div>
                <div class="card">
                    <h3>Office</h3>
                    <p style="font-size:1.2rem; font-weight:600; color:#1a237e; "><?php echo htmlspecialchars($head_office); ?></p>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">Your office is not set. Update it on your <a href="edit_profile.php" class="alert-link">profile</a>.</div>
            <?php endif; ?>

            <div class="content-box">
                <h2>Recent Activity</h2>
                <p>Coming soon.</p>
            </div>

        <?php elseif ($view === 'staff'): ?>
            <div class="content-box">
                <h2>My Office Staff</h2>
                <?php if (!$head_office): ?>
                    <div class="alert alert-warning">Set your office on your <a href="edit_profile.php" class="alert-link">profile</a> to see staff.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Program</th>
                                <th>Job Function</th>
                                <th>Office</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt_staff = $conn->prepare(
                                "SELECT u.username, u.email, s.position, s.program, s.job_function, s.office
                                 FROM users u LEFT JOIN staff_details s ON u.id = s.user_id
                                 WHERE u.role = 'staff' AND s.office = ?"
                            );
                            $stmt_staff->bind_param("s", $head_office);
                            $stmt_staff->execute();
                            $result_staff = $stmt_staff->get_result();
                            if ($result_staff && $result_staff->num_rows > 0):
                                while ($row = $result_staff->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['office'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr><td colspan="6" class="text-center">No staff members found.</td></tr>
                            <?php endif; $stmt_staff->close(); ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($view === 'trainings'): ?>
            <div class="content-box">
                <h2>Staff Trainings</h2>
                <?php if (!$head_office): ?>
                    <div class="alert alert-warning">Set your office on your <a href="edit_profile.php" class="alert-link">profile</a> to see trainings.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Training</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt_tr = $conn->prepare(
                                "SELECT u.username, t.title, ut.completion_date, ut.status
                                 FROM user_trainings ut
                                 JOIN users u ON ut.user_id = u.id
                                 LEFT JOIN staff_details s ON u.id = s.user_id
                                 JOIN trainings t ON ut.training_id = t.id
                                 WHERE u.role = 'staff' AND s.office = ?
                                 ORDER BY ut.completion_date DESC"
                            );
                            $stmt_tr->bind_param("s", $head_office);
                            $stmt_tr->execute();
                            $res_tr = $stmt_tr->get_result();
                            if ($res_tr && $res_tr->num_rows > 0):
                                while ($row = $res_tr->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['completion_date']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>"><?php echo ucfirst($row['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr><td colspan="4" class="text-center">No training records found.</td></tr>
                            <?php endif; $stmt_tr->close(); ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



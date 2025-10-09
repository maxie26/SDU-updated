<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $position = isset($_POST['position']) ? $_POST['position'] : '';
    $program = isset($_POST['program']) ? $_POST['program'] : '';
    $job_function = isset($_POST['job_function']) ? $_POST['job_function'] : '';
    $office = isset($_POST['office']) ? $_POST['office'] : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    $conn->begin_transaction();

    try {
        
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt_user->bind_param("ssi", $email, $hashed, $user_id);
        } else {
            $stmt_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt_user->bind_param("si", $email, $user_id);
        }
        if (!$stmt_user->execute()) {
            throw new Exception("Error updating user information: " . $stmt_user->error);
        }
        $stmt_user->close();

        $stmt_check = $conn->prepare("SELECT user_id FROM staff_details WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $stmt_details = $conn->prepare("UPDATE staff_details SET position = ?, program = ?, job_function = ?, office = ? WHERE user_id = ?");
            $stmt_details->bind_param("ssssi", $position, $program, $job_function, $office, $user_id);
            if (!$stmt_details->execute()) {
                throw new Exception("Error updating staff details: " . $stmt_details->error);
            }
            $stmt_details->close();
        } else {
            $stmt_details = $conn->prepare("INSERT INTO staff_details (user_id, position, program, job_function, office) VALUES (?, ?, ?, ?, ?)");
            $stmt_details->bind_param("issss", $user_id, $position, $program, $job_function, $office);
            if (!$stmt_details->execute()) {
                throw new Exception("Error inserting staff details: " . $stmt_details->error);
            }
            $stmt_details->close();
        }
        $stmt_check->close();

        $conn->commit();
        $success_message = "Profile updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

$stmt_fetch = $conn->prepare("SELECT u.email, s.position, s.program, s.job_function, s.office FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data) {
    header("Location: login.php");
    exit();
}

$user_data['email'] = $user_data['email'] ?? '';
$user_data['position'] = $user_data['position'] ?? '';
$user_data['program'] = $user_data['program'] ?? '';
$user_data['job_function'] = $user_data['job_function'] ?? '';
$user_data['office'] = $user_data['office'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; display: flex; background-color: #f0f2f5; }
        .main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }
        @media (min-width: 992px) { .main-content { margin-left: 250px; } }
        .sidebar { width: 250px; background-color: #1a237e; color: white; height: 100vh; position: fixed; padding-top: 2rem; transition: width 0.3s ease-in-out; }
        .sidebar .nav-link { color: white; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; white-space: nowrap; overflow: hidden; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #3f51b5; }
        .sidebar .btn-toggle { background: transparent; border: none; color: #fff; padding: 6px 10px; }
        @media (min-width: 992px) { body.toggled .sidebar { width: 80px; } body.toggled .main-content { margin-left: 80px; } body.toggled .sidebar .nav-link span { display: none; } }
        .content-box { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; max-width: 700px; margin: 0 auto; }
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
            <li class="nav-item"><a class="nav-link" href="view_profile.php"><i class="fas fa-user me-2"></i> <span>View Profile</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="edit_profile.php"><i class="fas fa-user-edit me-2"></i> <span>Edit Profile</span></a></li>
            <li class="nav-item mt-auto"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="content-box">
            <h2 class="mb-4">Edit Profile</h2>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="edit_profile.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <?php if ($_SESSION['role'] !== 'admin'): ?>
                <div class="mb-3">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($user_data['position']); ?>">
                </div>
                <div class="mb-3">
                    <label for="job_function" class="form-label">Job Function</label>
                    <input type="text" class="form-control" id="job_function" name="job_function" value="<?php echo htmlspecialchars($user_data['job_function']); ?>">
                </div>
                <div class="mb-3">
                    <label for="office" class="form-label">Office</label>
                    <select class="form-control" id="office" name="office">
                        <option value="">Select your office</option>
                        <?php
                        $offices = [
                            'Ateneo Center for Culture & the Arts (ACCA)',
                            'Ateneo Center for Environment & Sustainability (ACES)',
                            'Ateneo Center for Leadership & Governance (ACLG)',
                            'Ateneo Peace Institute (API)',
                            'Center for Community Extension Services (CCES)',
                            'Ateneo Learning and Teaching Excellence Center (ALTEC)'
                        ];
                        foreach ($offices as $opt) {
                            $sel = ($user_data['office'] === $opt) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($opt).'" '.$sel.'>'.htmlspecialchars($opt).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password (optional)</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>

            <div class="text-center mt-3">
                <a href="view_profile.php" class="btn btn-outline-secondary me-2">View Profile</a>
                <?php
                $backHref = 'staff_dashboard.php';
                if ($_SESSION['role'] === 'admin') { $backHref = 'admin_dashboard.php'; }
                elseif ($_SESSION['role'] === 'head') { $backHref = 'office_head_dashboard.php'; }
                ?>
                <a href="<?php echo $backHref; ?>" class="btn btn-outline-primary">Go back to Dashboard</a>
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
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
            $stmt_details = $conn->prepare("UPDATE staff_details SET position = ?, program = NULL, job_function = NULL, office = ? WHERE user_id = ?");
            $stmt_details->bind_param("ssi", $position, $office, $user_id);
            if (!$stmt_details->execute()) {
                throw new Exception("Error updating staff details: " . $stmt_details->error);
            }
            $stmt_details->close();
        } else {
            $stmt_details = $conn->prepare("INSERT INTO staff_details (user_id, position, program, job_function, office) VALUES (?, ?, NULL, NULL, ?)");
            $stmt_details->bind_param("iss", $user_id, $position, $office);
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

$stmt_fetch = $conn->prepare("SELECT u.email, s.position, s.office FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.id = ?");
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
        body { 
            background-color: #f6f7ffff;
            font-family: 'Montserrat', sans-serif; 
        }
        .container { 
            max-width: 600px; 
            margin-top: 50px; 
        }
        .card { 
            padding: 20px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center mb-4">Edit Profile</h2>

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
                    <label for="office" class="form-label">Office</label>
                    <select class="form-control" id="office" name="office">
                        <option value="">Loading offices...</option>
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
                if ($_SESSION['role'] === 'admin') {
                    $backHref = 'admin_dashboard.php';
                } elseif ($_SESSION['role'] === 'head') {
                    $backHref = 'office_head_dashboard.php';
                }
                ?>
                <a href="<?php echo $backHref; ?>" class="btn btn-outline-primary">Go back to Dashboard</a>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const officeSelect = document.getElementById('office');
        if (!officeSelect) return;
        fetch('offices_api.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const current = <?php echo json_encode($user_data['office']); ?>;
                officeSelect.innerHTML = '<option value="">Select your office</option>';
                if (data && data.success && Array.isArray(data.data)) {
                    data.data.forEach(function(o){
                        const opt = document.createElement('option');
                        opt.value = o.name;
                        opt.textContent = o.name;
                        if (current === o.name) opt.selected = true;
                        officeSelect.appendChild(opt);
                    });
                } else {
                    officeSelect.innerHTML = '<option value="">No offices available</option>';
                }
            })
            .catch(() => {
                officeSelect.innerHTML = '<option value="">Failed to load offices</option>';
            });
    });
    </script>
</body>
</html>
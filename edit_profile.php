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
    $job_function = isset($_POST['job_function']) ? $_POST['job_function'] : '';
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
            $stmt_details = $conn->prepare("UPDATE staff_details SET position = ?, job_function = ? WHERE user_id = ?");
            $stmt_details->bind_param("ssi", $position, $job_function, $user_id);
            if (!$stmt_details->execute()) {
                throw new Exception("Error updating staff details: " . $stmt_details->error);
            }
            $stmt_details->close();
        } else {
            $stmt_details = $conn->prepare("INSERT INTO staff_details (user_id, position, job_function) VALUES (?, ?, ?)");
            $stmt_details->bind_param("iss", $user_id, $position, $job_function);
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

$stmt_fetch = $conn->prepare("SELECT u.email, s.position, s.job_function FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.id = ?");
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
$user_data['job_function'] = $user_data['job_function'] ?? '';

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; 
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto;
        }
        .card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        .btn-outline-secondary, .btn-outline-primary {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .alert {
            border-radius: 12px;
            border: none;
        }
        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .alert-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .card {
                padding: 2rem;
                border-radius: 16px;
            }
            
            .row {
                flex-direction: column;
            }
            
            .col-md-6 {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem 0;
            }
            
            .container {
                padding: 0.5rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center mb-4 text-dark fw-bold">Edit Profile</h2>
            <p class="text-center text-muted mb-4">Update your personal information and preferences</p>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="edit_profile.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
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
                
                <?php endif; ?>
                
                <div class="mb-4">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                    <div class="form-text">Leave blank if you don't want to change your password</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Update Profile</button>
                </div>
            </form>

            <div class="text-center mt-4">
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <a href="view_profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-eye me-1"></i> View Profile
                    </a>
                    <?php
                    $backHref = 'staff_dashboard.php';
                    if ($_SESSION['role'] === 'admin') {
                        $backHref = 'admin_dashboard.php';
                    } elseif ($_SESSION['role'] === 'head') {
                        $backHref = 'office_head_dashboard.php';
                    }
                    ?>
                    <a href="<?php echo $backHref; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
    </script>
</body>
</html>
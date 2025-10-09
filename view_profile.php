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
        body { 
            font-family: 'Montserrat', sans-serif; 
            background-color: #07106eff;
        }
        .container { 
            max-width: 800px; 
            margin-top: 50px; 
        }
        .card { 
            padding: 30px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            border-radius: 10px;
        }
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
<body>
    <div class="container">
        <div class="back-link">
            <?php
            $backHref = 'staff_dashboard.php';
            if ($_SESSION['role'] === 'admin') {
                $backHref = 'admin_dashboard.php';
            } elseif ($_SESSION['role'] === 'head') {
                $backHref = 'office_head_dashboard.php';
            }
            ?>
            <a href="<?php echo $backHref; ?>">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user_data['username'], 0, 2)); ?>
                </div>
                <h2 class="mb-2"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                <span class="badge badge-role bg-primary"><?php echo ucfirst($user_data['role']); ?></span>
            </div>

            <div class="profile-info">
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-envelope me-2"></i>Email Address
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></div>
                </div>

                <?php if ($_SESSION['role'] !== 'admin'): ?>
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-briefcase me-2"></i>Position
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['position']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-tasks me-2"></i>Job Function
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['job_function']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-building me-2"></i>Office
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['office']); ?></div>
                </div>
                <?php endif; ?>

                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-calendar-plus me-2"></i>Member Since
                    </div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="edit_profile.php" class="btn-edit">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

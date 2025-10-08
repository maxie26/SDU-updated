<?php
session_start();
include("db.php");

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all staff members and their details for display
$query = "SELECT u.id, u.username, u.email, s.position, s.program, s.job_function, s.office 
          FROM users u 
          LEFT JOIN staff_details s ON u.id = s.user_id
          WHERE u.role IN ('staff', 'head')";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Montserrat', sans-serif; 
            background-color: #f0f2f5; 
        }
        .container { 
            max-width: 1200px; 
            margin-top: 50px; 
        }
        .card { 
            padding: 20px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
        .table-responsive { 
            overflow-x: auto; 
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <nav class="back-link">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Go back to Dashboard
            </a>
        </nav>
        <h2 class="text-center mb-4">Staff Directory</h2>
        
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['office'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="view_staff_trainings.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View Trainings
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff members found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
include("db.php");

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$office_query = "SELECT DISTINCT office FROM staff_details WHERE office IS NOT NULL AND office != '' ORDER BY office";
$office_result = $conn->query($office_query);

$selected_office = isset($_GET['office']) ? $_GET['office'] : '';

$main_query = "
    SELECT
        u.username,
        s.position,
        s.program,
        s.job_function,
        s.office,
        GROUP_CONCAT(CONCAT(t.title, ' (', ut.completion_date, ')') SEPARATOR ';<br>') AS trainings
    FROM users u
    LEFT JOIN staff_details s ON u.id = s.user_id
    LEFT JOIN user_trainings ut ON u.id = ut.user_id
    LEFT JOIN trainings t ON ut.training_id = t.id
    WHERE u.role IN ('staff', 'head')
";

if (!empty($selected_office)) {
    $main_query .= " AND s.office = ?";
}

$main_query .= " GROUP BY u.id ORDER BY u.username";

if (!empty($selected_office)) {
    $stmt = $conn->prepare($main_query);
    $stmt->bind_param("s", $selected_office);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($main_query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Competencies Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Montserrat', sans-serif;
            background-color: #f0f2f5; 
        }
        .container { 
            max-width: 1400px; 
            margin-top: 50px; 
        }
        .card { 
            padding: 20px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
        .table-responsive { 
            overflow-x: auto; 
        }
        .back-link { 
            margin-bottom: 20px; 
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
        
        <h2 class="text-center mb-4">Staff Competencies Report</h2>
        
        <div class="mb-3 d-flex align-items-center">
            <label for="officeFilter" class="form-label me-2 mb-0">Filter by Office:</label>
            <select id="officeFilter" class="form-select w-auto">
                <option value="combined_staff_report.php">All Offices</option>
                <?php while ($office_row = $office_result->fetch_assoc()): ?>
                    <option value="combined_staff_report.php?office=<?php echo urlencode($office_row['office']); ?>"
                            <?php if ($selected_office === $office_row['office']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($office_row['office']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Position</th>
                        <th>Program</th>
                        <th>Job Function</th>
                        <th>Office</th>
                        <th>Trainings & Completion Dates</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['office'] ?? 'N/A'); ?></td>
                                <td><?php echo $row['trainings'] ? $row['trainings'] : 'No trainings recorded.'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No staff members found for this filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('officeFilter').addEventListener('change', function() {
        window.location.href = this.value;
    });
</script>
</body>
</html>
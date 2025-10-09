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
        body { font-family: 'Montserrat', sans-serif; background-color: #f0f2f5; }
        .container { max-width: 800px; margin-top: 50px; }
        .card { padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center mb-4">Training Records for **<?php echo htmlspecialchars($staff_username); ?>**</h2>
            <div class="d-flex justify-content-end mb-3">
                <?php $back = ($_SESSION['role']==='admin') ? 'staff_directory_view.php' : 'office_head_dashboard.php?view=office-directory'; ?>
                <a href="<?php echo $back; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
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
</body>
</html>
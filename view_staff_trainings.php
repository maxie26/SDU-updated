<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) {
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
                <a href="staff_directory_view.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Staff Directory
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
<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'head'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['id'] ?? null;
$message = "";

if (!$training_id) {
    header("Location: staff_dashboard.php?view=training-records");
    exit();
}


$stmt_fetch = $conn->prepare("SELECT ut.id, ut.completion_date, ut.status, t.title, t.description 
                              FROM user_trainings ut 
                              JOIN trainings t ON ut.training_id = t.id 
                              WHERE ut.id = ? AND ut.user_id = ?");
$stmt_fetch->bind_param("ii", $training_id, $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows === 0) {
    header("Location: staff_dashboard.php?view=training-records");
    exit();
}

$training_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $completion_date = $_POST['completion_date'];
    $status = $_POST['status'];

    $conn->begin_transaction();
    try {
        $stmt_training = $conn->prepare("UPDATE trainings SET title = ?, description = ?, training_date = ? WHERE id = (SELECT training_id FROM user_trainings WHERE id = ?)");
        $stmt_training->bind_param("sssi", $title, $description, $completion_date, $training_id);
        $stmt_training->execute();
        $stmt_training->close();

        $stmt_user_training = $conn->prepare("UPDATE user_trainings SET completion_date = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt_user_training->bind_param("ssii", $completion_date, $status, $training_id, $user_id);
        $stmt_user_training->execute();
        $stmt_user_training->close();

        $conn->commit();
        $message = "<div class='alert alert-success'>Training updated successfully!</div>";
        

        $stmt_fetch = $conn->prepare("SELECT ut.id, ut.completion_date, ut.status, t.title, t.description 
                                      FROM user_trainings ut 
                                      JOIN trainings t ON ut.training_id = t.id 
                                      WHERE ut.id = ? AND ut.user_id = ?");
        $stmt_fetch->bind_param("ii", $training_id, $user_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        $training_data = $result_fetch->fetch_assoc();
        $stmt_fetch->close();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f0f2f5; }
        .container { max-width: 600px; margin-top: 50px; }
        .card { padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="text-center mb-4">Edit Training</h2>
            <?php echo $message; ?>
            <form action="edit_training.php?id=<?php echo $training_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Training Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($training_data['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($training_data['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="completion_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="completion_date" name="completion_date" value="<?php echo $training_data['completion_date']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="completed" <?php echo $training_data['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="upcoming" <?php echo $training_data['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Training</button>
            </form>
            <div class="text-center mt-3">
                <a href="staff_dashboard.php?view=training-records">Go back to Training Records</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'head'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['id'] ?? null;
$new_status = $_GET['status'] ?? null;
$redirect_url = $_SESSION['role'] === 'head' ? 'office_head_dashboard.php?view=training-records' : 'staff_dashboard.php?view=training-records';

if (!$training_id || !$new_status || !in_array($new_status, ['completed', 'upcoming'])) {
    header("Location: " . $redirect_url);
    exit();
}


$stmt_check = $conn->prepare("SELECT id FROM user_trainings WHERE id = ? AND user_id = ?");
$stmt_check->bind_param("ii", $training_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    header("Location: " . $redirect_url);
    exit();
}
$stmt_check->close();

$stmt_update = $conn->prepare("UPDATE user_trainings SET status = ? WHERE id = ? AND user_id = ?");
$stmt_update->bind_param("sii", $new_status, $training_id, $user_id);
$stmt_update->execute();
$stmt_update->close();

header("Location: " . $redirect_url);
exit();
?>

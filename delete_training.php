<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'head'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['id'] ?? null;

if (!$training_id) {
    header("Location: staff_dashboard.php?view=training-records");
    exit();
}

$stmt_check = $conn->prepare("SELECT training_id FROM user_trainings WHERE id = ? AND user_id = ?");
$stmt_check->bind_param("ii", $training_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    header("Location: staff_dashboard.php?view=training-records");
    exit();
}

$training_data = $result_check->fetch_assoc();
$training_id_to_delete = $training_data['training_id'];
$stmt_check->close();

$conn->begin_transaction();
try {
   
    $stmt_user_training = $conn->prepare("DELETE FROM user_trainings WHERE id = ? AND user_id = ?");
    $stmt_user_training->bind_param("ii", $training_id, $user_id);
    $stmt_user_training->execute();
    $stmt_user_training->close();


    $stmt_check_usage = $conn->prepare("SELECT COUNT(*) as count FROM user_trainings WHERE training_id = ?");
    $stmt_check_usage->bind_param("i", $training_id_to_delete);
    $stmt_check_usage->execute();
    $result_usage = $stmt_check_usage->get_result();
    $usage_data = $result_usage->fetch_assoc();
    $stmt_check_usage->close();

   
    if ($usage_data['count'] == 0) {
        $stmt_training = $conn->prepare("DELETE FROM trainings WHERE id = ?");
        $stmt_training->bind_param("i", $training_id_to_delete);
        $stmt_training->execute();
        $stmt_training->close();
    }

    $conn->commit();
    header("Location: staff_dashboard.php?view=training-records&message=deleted");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header("Location: staff_dashboard.php?view=training-records&message=error");
    exit();
}
?>

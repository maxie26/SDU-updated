<?php

session_start();
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$role = $_SESSION['role'];
if (!in_array($role, ['admin','head'])) {
    echo json_encode(['success'=>false,'error'=>'Permission denied']);
    exit;
}

$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
if ($staff_id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Missing staff_id']);
    exit;
}

$stmt = $conn->prepare("
    SELECT t.id AS training_id, t.title, ut.status, ut.assigned_at, ut.completed_at
    FROM user_trainings ut
    LEFT JOIN trainings t ON t.id = ut.training_id
    WHERE ut.user_id = ? AND (ut.status IS NULL OR ut.status != 'completed')
    ORDER BY ut.assigned_at DESC
");
if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();
$res = $stmt->get_result();
$def = [];
while ($row = $res->fetch_assoc()) {
    $def[] = $row;
}
$stmt->close();

echo json_encode(['success'=>true,'deficiencies'=>$def]);
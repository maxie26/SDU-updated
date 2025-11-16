<?php
session_start();
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

$ids = isset($_POST['ids']) ? $_POST['ids'] : null;
if (!$ids || !is_array($ids)) {
    echo json_encode(['success'=>false,'error'=>'Missing ids']);
    exit;
}

// Filter out empty values
$ids = array_filter(array_map('intval', $ids));
if (empty($ids)) {
    echo json_encode(['success'=>false,'error'=>'No valid ids']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "UPDATE messages SET is_read = 1 WHERE id IN ($placeholders) AND receiver_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

$params = array_merge($ids, [$user_id]);
$types = str_repeat('i', count($params));
$bind_params = [$types];
foreach ($params as &$p) {
    $bind_params[] = &$p;
}
call_user_func_array([$stmt, 'bind_param'], $bind_params);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'affected'=>$stmt->affected_rows]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
$stmt->close();
<?php
session_start();
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Optional param "staff_id": admin/head may request conversation for a staff member
$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

if ($staff_id > 0) {
    if (!in_array($role, ['admin','head'])) {
        echo json_encode(['success'=>false,'error'=>'Permission denied']);
        exit;
    }
    // return messages between current user (admin/head) and the staff member
    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, 
               s.username AS sender_name, r.username AS receiver_name
        FROM messages m
        LEFT JOIN users s ON s.id = m.sender_id
        LEFT JOIN users r ON r.id = m.receiver_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    if (!$stmt) {
        echo json_encode(['success'=>false,'error'=>$conn->error]);
        exit;
    }
    $stmt->bind_param("iiii", $user_id, $staff_id, $staff_id, $user_id);
} else {
    // default: staff inbox (messages where receiver_id = current user)
    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, 
               s.username AS sender_name
        FROM messages m
        LEFT JOIN users s ON s.id = m.sender_id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC
    ");
    if (!$stmt) {
        echo json_encode(['success'=>false,'error'=>$conn->error]);
        exit;
    }
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

echo json_encode(['success'=>true,'messages'=>$messages]);
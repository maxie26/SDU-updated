<?php
session_start();
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($receiver_id <= 0 || $message === '') {
    echo json_encode(['success'=>false,'error'=>'Missing receiver or message']);
    exit;
}

// Only admin/head may send to staff
if (!in_array($role, ['admin','head'])) {
    echo json_encode(['success'=>false,'error'=>'Permission denied']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

$stmt->bind_param("iis", $sender_id, $receiver_id, $message);
if ($stmt->execute()) {
    echo json_encode(['success'=>true,'id'=>$stmt->insert_id,'message'=>'Message sent successfully']);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
$stmt->close();

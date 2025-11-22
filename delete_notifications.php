<?php
session_start();
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

$ids = null;
// accept form posted ids[] or JSON { ids: [] }
if (isset($_POST['ids'])) {
    $ids = $_POST['ids'];
} else {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['ids'])) {
            $ids = $decoded['ids'];
        }
    }
}

if (!$ids || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Missing ids']);
    exit;
}

$ids = array_filter(array_map('intval', $ids));
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'No valid ids']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "DELETE FROM messages WHERE id IN ($placeholders) AND receiver_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

$params = array_merge($ids, [$user_id]);
$types = str_repeat('i', count($params));
$bind_params = [];
$bind_params[] = & $types;
foreach ($params as $key => $val) {
    $bind_params[] = & $params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bind_params);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'affected'=>$stmt->affected_rows]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
$stmt->close();

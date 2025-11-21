<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$senderId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$message = trim($_POST['message'] ?? '');
$audience = $_POST['audience'] ?? 'all';

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

$allowedAudiences = ['all', 'staff', 'heads'];
if ($role === 'head') {
    $allowedAudiences = ['office-staff'];
    if ($audience === 'all') {
        $audience = 'office-staff';
    }
}

if (!in_array($audience, $allowedAudiences, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid audience']);
    exit;
}

$receivers = [];

if ($role === 'admin') {
    switch ($audience) {
        case 'staff':
            $sql = "SELECT id FROM users WHERE role = 'staff' AND id <> ?";
            break;
        case 'heads':
            $sql = "SELECT id FROM users WHERE role = 'head' AND id <> ?";
            break;
        case 'all':
        default:
            $sql = "SELECT id FROM users WHERE id <> ?";
            break;
    }
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $senderId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $receivers[] = (int)$row['id'];
        }
        $stmt->close();
    }
} elseif ($role === 'head') {
    // Only allow sending to staff within the head's office
    $office = '';
    $stmtOffice = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    if ($stmtOffice) {
        $stmtOffice->bind_param('i', $senderId);
        $stmtOffice->execute();
        $resOffice = $stmtOffice->get_result();
        $office = $resOffice && $resOffice->num_rows ? ($resOffice->fetch_assoc()['office'] ?? '') : '';
        $stmtOffice->close();
    }

    if (!$office) {
        echo json_encode(['success' => false, 'error' => 'Office is not set for your account']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT u.id
        FROM users u
        JOIN staff_details s ON u.id = s.user_id
        WHERE u.role = 'staff' AND s.office = ? AND u.id <> ?");
    if ($stmt) {
        $stmt->bind_param('si', $office, $senderId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $receivers[] = (int)$row['id'];
        }
        $stmt->close();
    }
}

if (empty($receivers)) {
    echo json_encode(['success' => false, 'error' => 'No recipients found for the selected audience']);
    exit;
}

$stmtInsert = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
if (!$stmtInsert) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare insert statement']);
    exit;
}

$inserted = 0;
foreach ($receivers as $receiverId) {
    $stmtInsert->bind_param('iis', $senderId, $receiverId, $message);
    if ($stmtInsert->execute()) {
        $inserted++;
    }
}
$stmtInsert->close();

echo json_encode([
    'success' => true,
    'count' => $inserted,
    'message' => 'Notification sent successfully'
]);


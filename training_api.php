<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff','head'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $completionDate = $_POST['completion_date'] ?? null;
        $status = $_POST['status'] ?? 'completed';

        if ($title === '' || !$completionDate || !in_array($status, ['completed','upcoming'])) {
            throw new Exception('Invalid input');
        }

        $conn->begin_transaction();
        $stmt1 = $conn->prepare("INSERT INTO trainings (title, description, training_date) VALUES (?, ?, ?)");
        $stmt1->bind_param('sss', $title, $description, $completionDate);
        $stmt1->execute();
        $trainingId = $stmt1->insert_id;
        $stmt1->close();

        $stmt2 = $conn->prepare("INSERT INTO user_trainings (user_id, training_id, completion_date, status) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param('iiss', $userId, $trainingId, $completionDate, $status);
        $stmt2->execute();
        $stmt2->close();
        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update') {
        $recordId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $completionDate = $_POST['completion_date'] ?? null;
        $status = $_POST['status'] ?? 'completed';

        if ($recordId <= 0 || $title === '' || !$completionDate || !in_array($status, ['completed','upcoming'])) {
            throw new Exception('Invalid input');
        }

        // Verify record ownership
        $stmt0 = $conn->prepare("SELECT training_id FROM user_trainings WHERE id = ? AND user_id = ?");
        $stmt0->bind_param('ii', $recordId, $userId);
        $stmt0->execute();
        $res0 = $stmt0->get_result();
        if ($res0->num_rows === 0) {
            throw new Exception('Record not found');
        }
        $row0 = $res0->fetch_assoc();
        $trainingId = (int)$row0['training_id'];
        $stmt0->close();

        $conn->begin_transaction();
        $stmt1 = $conn->prepare("UPDATE trainings SET title = ?, description = ?, training_date = ? WHERE id = ?");
        $stmt1->bind_param('sssi', $title, $description, $completionDate, $trainingId);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE user_trainings SET completion_date = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt2->bind_param('ssii', $completionDate, $status, $recordId, $userId);
        $stmt2->execute();
        $stmt2->close();
        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Throwable $e) {
    if ($conn && $conn->errno === 0) {
        // try rollback safely
        @$conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Operation failed']);
}



<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $result = $conn->query("SELECT id, name, code FROM offices WHERE is_active = 1 ORDER BY name");
    $offices = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offices[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'code' => $row['code']
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $offices]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load offices']);
}



<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

// Only admin can fetch staff list
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

// Return staff and head users with deficiency counts
$sql = "
    SELECT u.id, u.username, u.email,
           COUNT(CASE WHEN (ut.status IS NULL OR ut.status != 'completed') THEN 1 END) AS deficiency_count
    FROM users u
    LEFT JOIN user_trainings ut ON ut.user_id = u.id
    WHERE u.role IN ('staff','head')
    GROUP BY u.id
    ORDER BY deficiency_count DESC, u.username ASC
";

$res = $conn->query($sql);
$out = ['success' => true, 'staff' => []];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $out['staff'][] = $r;
    }
}

echo json_encode($out);

?>

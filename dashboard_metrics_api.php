<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$scope = $_GET['scope'] ?? '';
$role = $_SESSION['role'] ?? '';

if (!in_array($scope, ['admin', 'head'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown scope']);
    exit;
}

if ($scope === 'admin' && $role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if ($scope === 'head' && $role !== 'head') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

function buildMonthBuckets(int $months = 6): array {
    $buckets = [];
    $now = new DateTime('first day of this month');
    for ($i = $months - 1; $i >= 0; $i--) {
        $clone = clone $now;
        $clone->modify("-{$i} months");
        $key = $clone->format('Y-m');
        $buckets[$key] = [
            'label' => $clone->format('M'),
            'value' => 0
        ];
    }
    return $buckets;
}

function fetchMonthlyAttendance(mysqli $conn, string $extraWhere = '', string $types = '', array $params = []): array {
    $sql = "
        SELECT DATE_FORMAT(ut.completion_date, '%Y-%m') AS ym,
               DATE_FORMAT(ut.completion_date, '%b') AS month_label,
               COUNT(*) AS total
        FROM user_trainings ut
        JOIN users u ON ut.user_id = u.id
        LEFT JOIN staff_details s ON ut.user_id = s.user_id
        WHERE ut.status = 'completed'
          AND ut.completion_date IS NOT NULL
          AND ut.completion_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
          {$extraWhere}
        GROUP BY ym
        ORDER BY ym ASC";

    $buckets = buildMonthBuckets();

    if ($types && $params) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $ym = $row['ym'];
                if (isset($buckets[$ym])) {
                    $buckets[$ym]['value'] = (int)$row['total'];
                }
            }
            $stmt->close();
        }
    } else {
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ym = $row['ym'];
                if (isset($buckets[$ym])) {
                    $buckets[$ym]['value'] = (int)$row['total'];
                }
            }
        }
    }

    return [
        'labels' => array_column($buckets, 'label'),
        'values' => array_column($buckets, 'value'),
    ];
}

function fetchScalar(mysqli $conn, string $sql, string $types = '', array $params = []) {
    if ($types && $params) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_row() : [0];
        $stmt->close();
        return (int)($row[0] ?? 0);
    }
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_row();
        return (int)($row[0] ?? 0);
    }
    return 0;
}

$payload = [];

if ($scope === 'admin') {
    $attendance = fetchMonthlyAttendance($conn);

    $totalStaff = fetchScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'staff'");
    $activeParticipants = fetchScalar(
        $conn,
        "SELECT COUNT(DISTINCT ut.user_id)
         FROM user_trainings ut
         JOIN users u ON ut.user_id = u.id
         WHERE u.role = 'staff' AND ut.status IN ('completed','upcoming')"
    );
    $headCount = fetchScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'head'");

    // Get heads and staff counts per office
    $headsByOffice = [];
    $staffByOffice = [];
    
    // Get heads per office
    $sqlHeads = "
        SELECT COALESCE(NULLIF(s.office, ''), 'Unassigned') AS office,
               COUNT(*) AS total
        FROM users u
        LEFT JOIN staff_details s ON u.id = s.user_id
        WHERE u.role = 'head'
        GROUP BY office
        ORDER BY office ASC";
    $resHeads = $conn->query($sqlHeads);
    if ($resHeads) {
        while ($row = $resHeads->fetch_assoc()) {
            $headsByOffice[] = [
                'office' => $row['office'],
                'total' => (int)$row['total']
            ];
        }
    }
    
    // Get staff per office
    $sqlStaff = "
        SELECT COALESCE(NULLIF(s.office, ''), 'Unassigned') AS office,
               COUNT(*) AS total
        FROM users u
        LEFT JOIN staff_details s ON u.id = s.user_id
        WHERE u.role = 'staff'
        GROUP BY office
        ORDER BY office ASC";
    $resStaff = $conn->query($sqlStaff);
    if ($resStaff) {
        while ($row = $resStaff->fetch_assoc()) {
            $staffByOffice[] = [
                'office' => $row['office'],
                'total' => (int)$row['total']
            ];
        }
    }
    
    // Combine offices and create unified data structure
    $allOffices = [];
    $officeMap = [];
    
    // Add all offices from heads
    foreach ($headsByOffice as $item) {
        $office = $item['office'];
        if (!isset($officeMap[$office])) {
            $officeMap[$office] = ['office' => $office, 'heads' => 0, 'staff' => 0];
            $allOffices[] = $office;
        }
        $officeMap[$office]['heads'] = $item['total'];
    }
    
    // Add all offices from staff
    foreach ($staffByOffice as $item) {
        $office = $item['office'];
        if (!isset($officeMap[$office])) {
            $officeMap[$office] = ['office' => $office, 'heads' => 0, 'staff' => 0];
            $allOffices[] = $office;
        }
        $officeMap[$office]['staff'] = $item['total'];
    }
    
    // Convert to array format
    $byOffice = [];
    foreach ($allOffices as $office) {
        $byOffice[] = $officeMap[$office];
    }
    
    // Sort by total (heads + staff) descending
    usort($byOffice, function($a, $b) {
        $totalA = $a['heads'] + $a['staff'];
        $totalB = $b['heads'] + $b['staff'];
        if ($totalA === $totalB) {
            return strcmp($a['office'], $b['office']);
        }
        return $totalB - $totalA;
    });

    $payload = [
        'attendance' => $attendance,
        'participation' => [
            'active' => $activeParticipants,
            'total' => $totalStaff
        ],
        'heads' => [
            'total' => $headCount,
            'byOffice' => $byOffice
        ]
    ];
} elseif ($scope === 'head') {
    $headUserId = $_SESSION['user_id'];
    $headOffice = '';
    $stmtOffice = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    if ($stmtOffice) {
        $stmtOffice->bind_param('i', $headUserId);
        $stmtOffice->execute();
        $resOffice = $stmtOffice->get_result();
        $headOffice = $resOffice && $resOffice->num_rows ? ($resOffice->fetch_assoc()['office'] ?? '') : '';
        $stmtOffice->close();
    }

    $extraWhere = '';
    $types = '';
    $params = [];
    if ($headOffice) {
        $extraWhere = " AND s.office = ?";
        $types = 's';
        $params = [$headOffice];
    }

    $attendance = fetchMonthlyAttendance($conn, $extraWhere, $types, $params);

    $totalStaffOffice = $headOffice
        ? fetchScalar(
            $conn,
            "SELECT COUNT(*)
             FROM users u
             LEFT JOIN staff_details s ON u.id = s.user_id
             WHERE u.role = 'staff' AND s.office = ?",
            's',
            [$headOffice]
        )
        : fetchScalar($conn, "SELECT COUNT(*) FROM users WHERE role = 'staff'");

    $activeParticipants = $headOffice
        ? fetchScalar(
            $conn,
            "SELECT COUNT(DISTINCT ut.user_id)
             FROM user_trainings ut
             JOIN users u ON ut.user_id = u.id
             LEFT JOIN staff_details s ON u.id = s.user_id
             WHERE u.role = 'staff'
               AND s.office = ?
               AND ut.status IN ('completed','upcoming')",
            's',
            [$headOffice]
        )
        : fetchScalar(
            $conn,
            "SELECT COUNT(DISTINCT ut.user_id)
             FROM user_trainings ut
             JOIN users u ON ut.user_id = u.id
             WHERE u.role = 'staff'
               AND ut.status IN ('completed','upcoming')"
        );

    $staffPerOffice = [];
    $sqlStaff = "
        SELECT COALESCE(NULLIF(s.office, ''), 'Unassigned') AS office,
               COUNT(*) AS total
        FROM users u
        LEFT JOIN staff_details s ON u.id = s.user_id
        WHERE u.role = 'staff'
        GROUP BY office
        ORDER BY total DESC, office ASC";
    $resStaff = $conn->query($sqlStaff);
    if ($resStaff) {
        while ($row = $resStaff->fetch_assoc()) {
            $staffPerOffice[] = [
                'office' => $row['office'],
                'total' => (int)$row['total']
            ];
        }
    }

    $payload = [
        'attendance' => $attendance,
        'participation' => [
            'active' => $activeParticipants,
            'total' => $totalStaffOffice
        ],
        'staffPerOffice' => $staffPerOffice,
        'headOffice' => $headOffice
    ];
}

echo json_encode(['success' => true, 'data' => $payload]);


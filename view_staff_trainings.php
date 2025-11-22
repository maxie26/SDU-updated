<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','head']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_GET['id'];

$stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ? AND role IN ('staff', 'head')");
$stmt_user->bind_param("i", $staff_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$stmt_user->close();

if (!$user_data) {
    echo "Staff member not found.";
    exit();
}

$staff_username = $user_data['username'];

// If viewer is a head, enforce same office restriction
if ($_SESSION['role'] === 'head') {
    $head_id = $_SESSION['user_id'];
    $head_office = '';
    $staff_office = '';
    $stmt = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $head_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { $head_office = $row['office'] ?? ''; }
    $stmt->close();

    $stmt = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) { $staff_office = $row['office'] ?? ''; }
    $stmt->close();

    if (empty($head_office) || $head_office !== $staff_office) {
        http_response_code(403);
        echo "You are not authorized to view this staff member's trainings.";
        exit();
    }
}

$query_records = "SELECT t.title, t.description, ut.completion_date, ut.status FROM user_trainings ut JOIN trainings t ON ut.training_id = t.id WHERE ut.user_id = ? ORDER BY ut.completion_date DESC";
$stmt_records = $conn->prepare($query_records);
$stmt_records->bind_param("i", $staff_id);
$stmt_records->execute();
$result_records = $stmt_records->get_result();
$stmt_records->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Records for <?php echo htmlspecialchars($staff_username); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: transparent;
            margin: 0;
            padding: 0;
        }

        .modal-content-wrapper {
            padding: 0;
            margin: 0;
        }

        .modal-header-section {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            margin: 0;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        h2 .username {
            color: #020381;
            font-weight: 800;
        }

        .modal-body-section {
            padding: 1.5rem;
        }

        /* Table styling - clean and simple */
        .table {
            margin: 0;
            border: none;
            width: 100%;
        }

        .table thead th {
            background: #020381;
            color: white;
            font-weight: 600;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border: none;
            text-align: left;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: #374151;
            font-size: 0.95rem;
            background-color: white;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-striped tbody tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .table tbody tr {
            transition: background-color 0.15s ease;
        }

        .table tbody tr:hover td {
            background-color: #f3f4f6 !important;
        }

        .table .badge {
            padding: 0.4rem 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 12px;
        }

        /* Alert */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
            font-size: 0.95rem;
            background-color: #eff6ff;
            color: #1e40af;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-header-section,
            .modal-body-section {
                padding: 1rem;
            }
            h2 {
                font-size: 1.25rem;
            }
            .table thead th {
                padding: 0.75rem;
                font-size: 0.75rem;
            }
            .table tbody td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
        }

    </style>
</head>
<body>
    <div class="modal-content-wrapper">
        <div class="modal-header-section">
            <h2>Training Records for <span class="username"><?php echo htmlspecialchars($staff_username); ?></span></h2>
        </div>

        <div class="modal-body-section">
            <?php if ($result_records->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Training Title</th>
                                <th scope="col">Description</th>
                                <th scope="col">Completion Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_records->fetch_assoc()): 
                                $status = $row['status'] ?? 'completed';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['completion_date']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $status === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    This staff member has no completed trainings.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

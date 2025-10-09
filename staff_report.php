<?php
session_start();
include("db.php");

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$office_query = "SELECT DISTINCT office FROM staff_details WHERE office IS NOT NULL AND office != '' ORDER BY office";
$office_result = $conn->query($office_query);
$offices = [];
if ($office_result) {
    while ($r = $office_result->fetch_assoc()) { $offices[] = $r['office']; }
}

$selected_offices = isset($_GET['offices']) && is_array($_GET['offices']) ? array_filter($_GET['offices']) : [];
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
$selected_period = isset($_GET['period']) ? $_GET['period'] : '';

$main_query = "
    SELECT
        u.username,
        u.role,
        s.position,
        /* program removed */
        s.job_function,
        s.office,
        GROUP_CONCAT(CONCAT(t.title, ' (', ut.completion_date, ')') SEPARATOR ';<br>') AS trainings
    FROM users u
    LEFT JOIN staff_details s ON u.id = s.user_id
    LEFT JOIN user_trainings ut ON u.id = ut.user_id
    LEFT JOIN trainings t ON ut.training_id = t.id
    WHERE u.role IN ('staff', 'head')
";

if (!empty($selected_offices)) {
    // build placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($selected_offices), '?'));
    $main_query .= " AND s.office IN (".$placeholders.")";
}

if ($selected_role === 'staff' || $selected_role === 'head') {
    $main_query .= " AND u.role = '" . $conn->real_escape_string($selected_role) . "'";
}

// bi-yearly filter by completion_date of trainings (optional)
if ($selected_period === 'H1') {
    $main_query .= " AND (ut.completion_date BETWEEN CONCAT(YEAR(CURDATE()), '-01-01') AND CONCAT(YEAR(CURDATE()), '-06-30'))";
} elseif ($selected_period === 'H2') {
    $main_query .= " AND (ut.completion_date BETWEEN CONCAT(YEAR(CURDATE()), '-07-01') AND CONCAT(YEAR(CURDATE()), '-12-31'))";
}

$main_query .= " GROUP BY u.id ORDER BY u.username";

if (!empty($selected_offices)) {
    $stmt = $conn->prepare($main_query);
    if ($stmt) {
        // dynamic types string
        $types = str_repeat('s', count($selected_offices));
        $stmt->bind_param($types, ...$selected_offices);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
    }
} else {
    $result = $conn->query($main_query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory & Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Montserrat', sans-serif;
            background-color: #07106eff; 
        }
        .container { 
            max-width: 1400px; 
            margin-top: 50px; 
        }
        .card { 
            padding: 20px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
        .table-responsive { 
            overflow-x: auto; 
        }
        .back-link { 
            margin-bottom: 20px; 
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <nav class="back-link">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Go back to Dashboard
            </a>
        </nav>
        
        <h2 class="text-center mb-4">Staff/Head Directory & Bi-Yearly Report</h2>
        
        <form class="row gy-2 gx-3 align-items-center mb-3" method="get" action="staff_report.php" id="filtersForm">
            <div class="col-auto">
                <label class="form-label mb-0" for="officesDropdown">Offices</label>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="officesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo empty($selected_offices) ? 'All' : (count($selected_offices) . ' selected'); ?>
                    </button>
                    <ul class="dropdown-menu p-2" aria-labelledby="officesDropdown" style="max-height:240px; overflow:auto; min-width: 320px;">
                        <li>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="office-all" <?php echo empty($selected_offices) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="office-all">All Offices</label>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($offices as $idx => $off): $id = 'office-'.($idx+1); ?>
                        <li>
                            <div class="form-check">
                                <input class="form-check-input office-check" type="checkbox" id="<?php echo $id; ?>" value="<?php echo htmlspecialchars($off); ?>" <?php echo in_array($off, $selected_offices) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $id; ?>"><?php echo htmlspecialchars($off); ?></label>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="officesHidden"></div>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0" for="role">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="">All</option>
                    <option value="staff" <?php if ($selected_role==='staff') echo 'selected'; ?>>Staff</option>
                    <option value="head" <?php if ($selected_role==='head') echo 'selected'; ?>>Head</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0" for="period">Period</label>
                <select class="form-select" id="period" name="period">
                    <option value="">All</option>
                    <option value="H1" <?php if ($selected_period==='H1') echo 'selected'; ?>>H1 (Jan-Jun)</option>
                    <option value="H2" <?php if ($selected_period==='H2') echo 'selected'; ?>>H2 (Jul-Dec)</option>
                </select>
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i> Apply Filters</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Position</th>
                        <th>Job Function</th>
                        <th>Office</th>
                        <th>Trainings & Completion Dates</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                                <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['office'] ?? 'N/A'); ?></td>
                                <td><?php echo $row['trainings'] ? $row['trainings'] : 'No trainings recorded.'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No staff members found for this filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function(){
        const form = document.getElementById('filtersForm');
        const allBox = document.getElementById('office-all');
        const officeChecks = Array.from(document.querySelectorAll('.office-check'));
        const hiddenContainer = document.getElementById('officesHidden');
        const dropdownBtn = document.getElementById('officesDropdown');

        function updateButtonLabel(){
            const checked = officeChecks.filter(c => c.checked).length;
            dropdownBtn.textContent = checked === 0 ? 'All' : (checked + ' selected');
        }

        function syncAllState(){
            allBox.checked = officeChecks.every(c => !c.checked);
        }

        allBox.addEventListener('change', function(){
            if (allBox.checked) {
                officeChecks.forEach(c => c.checked = false);
                updateButtonLabel();
            }
        });

        officeChecks.forEach(c => c.addEventListener('change', function(){
            if (this.checked) { allBox.checked = false; }
            syncAllState();
            updateButtonLabel();
        }));

        form.addEventListener('submit', function(){
            hiddenContainer.innerHTML = '';
            const selected = officeChecks.filter(c => c.checked).map(c => c.value);
            selected.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'offices[]';
                input.value = val;
                hiddenContainer.appendChild(input);
            });
        });

        // initial
        syncAllState();
        updateButtonLabel();
    })();
</script>
</body>
</html>
<?php
session_start();
include("db.php");

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Load offices dynamically from offices table
$office_query = "SELECT name FROM offices WHERE is_active = 1 ORDER BY name";
$office_result = $conn->query($office_query);
$offices = [];
if ($office_result) {
    while ($r = $office_result->fetch_assoc()) { $offices[] = $r['name']; }
}

$selected_offices = isset($_GET['offices']) && is_array($_GET['offices']) ? array_filter($_GET['offices']) : [];
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
$selected_period = isset($_GET['period']) ? $_GET['period'] : '';

$main_query = "
    SELECT
        u.username,
        u.role,
        s.position,
        s.program,
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
        }
        .container { 
            max-width: 1400px; 
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card { 
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding: 1.5rem;
        }
        .table-responsive { 
            overflow-x: auto; 
        }
        .back-link { 
            margin-bottom: 2rem; 
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
        }
        .btn-primary {
            background-color: #1a237e;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #3f51b5;
        }
        .form-select, .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-select:focus, .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody tr:hover,
        .table-hover tbody tr:hover,
        .table-striped tbody tr:hover {
            background-color: transparent !important;
        }
        .table tbody tr:nth-child(even):hover {
            background-color: transparent !important;
        }
        .badge {
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
            
            .card {
                padding: 1.5rem;
                border-radius: 16px;
            }
            
            .form {
                flex-direction: column;
                gap: 1rem;
            }
            
            .col-auto {
                width: 100%;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 0.5rem auto;
                padding: 0 0.25rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .dropdown-menu {
                min-width: 280px;
            }
        }
    </style>
</head>
<body>

<?php $embed = isset($_GET['embed']) && $_GET['embed'] == '1'; ?>

<div class="container" style="<?php echo $embed ? 'max-width:100%; margin:0; padding:0;' : '' ; ?>">
    <div class="card" style="<?php echo $embed ? 'border-radius:12px; box-shadow:none; background:#fff;' : '' ; ?>">
        <?php if (!$embed): ?>
        <nav class="back-link">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Go back to Dashboard
            </a>
        </nav>
        <?php endif; ?>
        
        <h2 class="text-center mb-4 text-dark fw-bold">Staff/Head Directory & Bi-Yearly Report</h2>
        <p class="text-center text-muted mb-4">Training Records</p>
        
        <form class="row gy-2 gx-3 align-items-center mb-3" method="get" action="staff_report.php" id="filtersForm">
            <?php if ($embed): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
            <div class="col-auto">
                <label class="form-label mb-0" for="officesDropdown">Offices</label>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="officesDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 200px;">
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
                <select class="form-select" id="role" name="role" style="min-width: 200px;">
                    <option value="">All</option>
                    <option value="staff" <?php if ($selected_role==='staff') echo 'selected'; ?>>Staff</option>
                    <option value="head" <?php if ($selected_role==='head') echo 'selected'; ?>>Head</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0" for="period">Period</label>
                <select class="form-select" id="period" name="period" style="min-width: 200px;">
                    <option value="">All</option>
                    <option value="H1" <?php if ($selected_period==='H1') echo 'selected'; ?>>1st Half (Jan-Jun)</option>
                    <option value="H2" <?php if ($selected_period==='H2') echo 'selected'; ?>>2nd Half (Jul-Dec)</option>
                </select>
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i> Apply Filters</button>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
            <button type="button" class="btn btn-outline-secondary" id="printReport">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item" type="button" data-export="pdf"><i class="fas fa-file-pdf me-1 text-danger"></i> PDF</button></li>
                    <li><button class="dropdown-item" type="button" data-export="excel"><i class="fas fa-file-excel me-1 text-success"></i> Excel</button></li>
                </ul>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped" id="directoryTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                            <th>Position</th>
                            <th>PROGRAM</th>
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
                                <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['office'] ?? 'N/A'); ?></td>
                                <td><?php echo $row['trainings'] ? $row['trainings'] : 'No trainings recorded.'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff members found for this filter.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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

    (function(){
        const table = document.getElementById('directoryTable');
        const printBtn = document.getElementById('printReport');
        const exportMenu = document.querySelectorAll('[data-export]');

        if (printBtn && table) {
            printBtn.addEventListener('click', function(){
                const printWindow = window.open('', '', 'width=1200,height=900');
                if (!printWindow) return;
                const doc = printWindow.document;
                doc.write('<html><head><title>Directory & Reports</title>');
                doc.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
                doc.write('</head><body class="p-4">');
                doc.write('<h3>Directory & Reports</h3>');
                doc.write(table.outerHTML);
                doc.write('</body></html>');
                doc.close();
                printWindow.focus();
                printWindow.onload = () => printWindow.print();
            });
        }

        function exportToCSV() {
            if (!table) return;
            const rows = Array.from(table.querySelectorAll('tr'));
            const csv = rows.map(row => {
                return Array.from(row.querySelectorAll('th,td'))
                    .map(cell => `"${cell.innerText.replace(/"/g, '""')}"`)
                    .join(',');
            }).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'directory-report.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportToPDF() {
            if (!table || !window.jspdf || !window.jspdf.jsPDF) return;
            const doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
            doc.setFontSize(16);
            doc.text('Directory & Reports', 40, 40);
            doc.autoTable({
                html: '#directoryTable',
                startY: 60,
                styles: { fontSize: 8, cellPadding: 4 },
                headStyles: { fillColor: [26, 35, 126] }
            });
            doc.save('directory-report.pdf');
        }

        exportMenu.forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.getAttribute('data-export');
                if (type === 'excel') {
                    exportToCSV();
                } else if (type === 'pdf') {
                    exportToPDF();
                }
            });
        });
    })();
</script>
</body>
</html>
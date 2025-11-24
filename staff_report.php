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
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background-color: #fff;
        }
        .table thead th {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
            border: none;
            border-bottom: 3px solid #0d47a1;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        .table thead th:first-child {
            border-top-left-radius: 8px;
        }
        .table thead th:last-child {
            border-top-right-radius: 8px;
        }
        .table tbody td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
            color: #374151;
            font-size: 0.9rem;
            word-wrap: break-word;
            max-width: 200px;
        }
        .table tbody td:last-child {
            max-width: 400px;
            line-height: 1.6;
        }
        .table tbody tr {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .table tbody tr:hover {
            background-color: #f3f4f6 !important;
            border-left-color: #6366f1;
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        .table tbody tr:nth-child(even):hover {
            background-color: #f3f4f6 !important;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 8px;
        }
        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 8px;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
            .table thead th,
            .table tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
            .table tbody td:last-child {
                max-width: 250px;
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
            .table thead th,
            .table tbody td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }
            .table tbody td:last-child {
                max-width: 200px;
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
            <div class="col-auto" style="position:relative;">
                <label class="form-label mb-0" for="officesToggle">Offices</label>
                <button class="btn btn-outline-secondary" type="button" id="officesToggle" aria-expanded="false" style="min-width:200px;">
                    <span id="officesToggleLabel"><?php echo empty($selected_offices) ? 'All' : (count($selected_offices) . ' selected'); ?></span>
                    <span class="ms-2"><i class="fas fa-caret-down"></i></span>
                </button>

                <div id="officesPanel" class="custom-offices-menu" style="display:none; position:absolute; top:56px; left:0; min-width:320px; max-height:260px; overflow:auto; background:#fff; border:1px solid #e5e7eb; box-shadow:0 8px 24px rgba(0,0,0,0.12); padding:8px; z-index:2500;">
                    <ul class="list-group" style="border: none;">
                        <li class="list-group-item border-0 p-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="office-all" <?php echo empty($selected_offices) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="office-all">All Offices</label>
                            </div>
                        </li>
                        <li class="list-group-item border-0 p-1"><hr class="dropdown-divider"></li>
                        <?php foreach ($offices as $idx => $off): $id = 'office-'.($idx+1); ?>
                            <li class="list-group-item border-0 p-1">
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
                        <th>Program</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
    // Check library loading
    window.addEventListener('load', function() {
        console.log('Window loaded, checking libraries...');
        console.log('window.jspdf:', typeof window.jspdf);
        console.log('window.jsPDF:', typeof window.jsPDF);
        
        if (typeof window.jspdf === 'undefined' && typeof window.jsPDF === 'undefined') {
            console.error('jsPDF library not loaded! Check network tab for failed requests.');
            console.log('Available globals:', Object.keys(window).filter(k => k.toLowerCase().includes('pdf')));
        } else {
            console.log('jsPDF library loaded successfully');
        }
    });
</script>
<script>
    // No in-panel Apply button; external "Apply Filters" button will submit selected offices.
</script>
<script>
    (function(){
        const form = document.getElementById('filtersForm');
        const allBox = document.getElementById('office-all');
        const officeChecks = Array.from(document.querySelectorAll('.office-check'));
        const hiddenContainer = document.getElementById('officesHidden');
        const dropdownBtn = document.getElementById('officesDropdown');
        const officesToggle = document.getElementById('officesToggle');
        const officesPanel = document.getElementById('officesPanel');
        const officesToggleLabel = document.getElementById('officesToggleLabel');

        function updateButtonLabel(){
            const checked = officeChecks.filter(c => c.checked).length;
            if (officesToggleLabel) officesToggleLabel.textContent = checked === 0 ? 'All' : (checked + ' selected');
            if (dropdownBtn) dropdownBtn.textContent = checked === 0 ? 'All' : (checked + ' selected');
        }

        function syncAllState(){
            allBox.checked = officeChecks.every(c => !c.checked);
        }

        if (allBox) {
            allBox.addEventListener('change', function(){
                if (allBox.checked) {
                    officeChecks.forEach(c => c.checked = false);
                    updateButtonLabel();
                }
            });
        }

        officeChecks.forEach(c => c.addEventListener('change', function(){
            if (this.checked) { allBox.checked = false; }
            syncAllState();
            updateButtonLabel();
        }));

        // Toggle custom offices panel
        if (officesToggle && officesPanel) {
            officesToggle.addEventListener('click', function(e){
                e.stopPropagation();
                const isVisible = officesPanel.style.display === 'block';
                officesPanel.style.display = isVisible ? 'none' : 'block';
            });

            // Close when clicking outside
            document.addEventListener('click', function(ev){
                if (!officesPanel.contains(ev.target) && ev.target !== officesToggle) {
                    officesPanel.style.display = 'none';
                }
            });

            // Prevent clicks inside panel from closing
            officesPanel.addEventListener('click', function(ev){ ev.stopPropagation(); });
        }

        // No in-panel Apply button; users will click the external "Apply Filters" button to submit selections.

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

        // PRINT
        if (printBtn && table) {
            printBtn.addEventListener('click', function(){
                const printWindow = window.open('', '', 'width=1200,height=900');
                if (!printWindow) return;
                const doc = printWindow.document;
                doc.write('<html><head><title>Directory & Reports</title>');
                doc.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
                doc.write('</head><body class="p-4">');
                doc.write('<h3>Directory & Reports</h3>');
               
                // Clone table
                const tableClone = table.cloneNode(true);
                // Clean HTML in trainings column (replace <br> with newlines for display)
                tableClone.querySelectorAll('tbody td:last-child').forEach(cell => {
                    cell.innerHTML = cell.innerHTML.replace(/<br\s*\/?>/gi, '<br>');
                });

                doc.write(tableClone.outerHTML);
                doc.write('</body></html>');
                doc.close();
                printWindow.focus();
                printWindow.onload = () => printWindow.print();
            });
        }

        // EXPORT TO CSV
        function exportToCSV() {
            if (!table) return;

            const rows = Array.from(table.querySelectorAll("tr"));

            const csv = rows.map(row => {
                return Array.from(row.querySelectorAll("th,td"))
                    .map(cell => {
                        let text = cell.innerHTML.replace(/<br\s*\/?>/gi, " | ").trim();
                        text = text.replace(/"/g, '""'); 
                        return `"${text}"`;
                    })
                    .join(",");
            }).join("\n");

            const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "staff-directory-" + new Date().toISOString().split("T")[0] + ".csv";
            link.click();
        }

        // EXPORT TO PDF
        function exportToPDF() {
    if (!table) {
        alert("Table not found.");
        return;
    }

    // Check if jsPDF is available
    if (typeof window.jspdf === 'undefined' && typeof window.jsPDF === 'undefined') {
        alert("jsPDF library failed to load. Please refresh the page.");
        return;
    }

    try {
        // Get current period selection
        const periodSelect = document.getElementById('period');
        const selectedPeriod = periodSelect ? periodSelect.value : '';
        let periodLabel = 'All';
        if (selectedPeriod === 'H1') {
            periodLabel = '1st Half (Jan-Jun)';
        } else if (selectedPeriod === 'H2') {
            periodLabel = '2nd Half (Jul-Dec)';
        }

        // Get jsPDF - try both possible locations
        let jsPDF;
        if (window.jspdf && window.jspdf.jsPDF) {
            jsPDF = window.jspdf.jsPDF;
        } else if (window.jsPDF) {
            jsPDF = window.jsPDF;
        } else {
            alert("jsPDF not found. Please refresh the page.");
            return;
        }

        // Create jsPDF instance
        const doc = new jsPDF({
            orientation: "landscape",
            unit: "pt",
            format: "a4"
        });

        // Extract table data as arrays
        const headers = [];
        const rows = [];
        
        // Get headers
        const headerRow = table.querySelector('thead tr');
        if (headerRow) {
            headerRow.querySelectorAll('th').forEach(th => {
                headers.push(th.textContent.trim());
            });
        }
        
        // Get data rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
                // Replace <br> tags with " | " for better PDF display
                let text = td.innerHTML.replace(/<br\s*\/?>/gi, " | ");
                // Remove any remaining HTML tags
                text = text.replace(/<[^>]*>/g, '').trim();
                row.push(text || 'N/A');
            });
            // Only add row if it's not the "No staff members found" message
            if (row.length > 0 && !row[0].includes('No staff members found')) {
                rows.push(row);
            }
        });

        // Header
        doc.setFontSize(16);
        doc.text("Staff/Head Directory & Bi-Yearly Report - Training Records", 40, 40);
        doc.setFontSize(12);
        doc.text("Period: " + periodLabel, 40, 60);
        doc.text("Generated: " + new Date().toLocaleDateString(), 40, 75);

        // AutoTable using data arrays (more reliable than HTML parsing)
        const autoTableOptions = {
            head: [headers],
            body: rows,
            startY: 95,
            styles: {
                fontSize: 7,
                cellPadding: 3,
                overflow: "linebreak",
                cellWidth: "wrap"
            },
            headStyles: {
                fillColor: [26, 35, 126],
                textColor: [255, 255, 255],
                fontStyle: "bold"
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { top: 95, left: 40, right: 40 },
            columnStyles: {
                6: { cellWidth: 200 } // Wider column for trainings
            }
        };
        
        // Try different ways to call autoTable
        if (typeof doc.autoTable === 'function') {
            doc.autoTable(autoTableOptions);
        } else if (window.jspdf && typeof window.jspdf.autoTable === 'function') {
            window.jspdf.autoTable(doc, autoTableOptions);
        } else if (jsPDF && typeof jsPDF.autoTable === 'function') {
            jsPDF.autoTable(doc, autoTableOptions);
        } else if (typeof window.autoTable === 'function') {
            window.autoTable(doc, autoTableOptions);
        } else {
            throw new Error("autoTable plugin not loaded. Please refresh the page and try again.");
        }

        // Save PDF with period in filename
        const filename = "staff-directory-" + periodLabel.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase() + "-" + new Date().toISOString().split("T")[0] + ".pdf";
        doc.save(filename);

    } catch (err) {
        console.error(err);
        alert("PDF Export Error: " + err.message);
    }
}


        // BUTTON HANDLERS
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
<script>
    // Prevent dropdown from closing when clicking checkboxes inside the offices dropdown
    // old dropdown close-prevention removed: using a custom panel instead
</script>
</body>
</html>


<!-- chore: update file to refresh latest commit message -->

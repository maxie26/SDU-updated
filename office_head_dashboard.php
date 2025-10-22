<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head') {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$username = $_SESSION['username'];
$head_user_id = $_SESSION['user_id'];

// fetch head office
$office = '';
$stmt_office = $conn->prepare("SELECT office FROM staff_details WHERE user_id = ?");
$stmt_office->bind_param("i", $head_user_id);
$stmt_office->execute();
$res_office = $stmt_office->get_result();
if ($row = $res_office->fetch_assoc()) {
    $office = $row['office'] ?? '';
}
$stmt_office->close();

// overview stats for head (within office)
$total_staff_in_office = 0;
$completed_trainings_in_office = 0;

if (!empty($office)) {
    $stmt_count_staff = $conn->prepare("SELECT COUNT(*) AS total FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.role = 'staff' AND s.office = ?");
    $stmt_count_staff->bind_param("s", $office);
    $stmt_count_staff->execute();
    $result_staff_count = $stmt_count_staff->get_result();
    if ($result_staff_count) {
        $row = $result_staff_count->fetch_assoc();
        $total_staff_in_office = (int)$row['total'];
    }
    $stmt_count_staff->close();

    $stmt_completed = $conn->prepare("SELECT COUNT(*) AS total FROM user_trainings ut JOIN users u ON ut.user_id = u.id LEFT JOIN staff_details s ON u.id = s.user_id WHERE ut.status = 'completed' AND s.office = ?");
    $stmt_completed->bind_param("s", $office);
    $stmt_completed->execute();
    $result_completed = $stmt_completed->get_result();
    if ($result_completed) {
        $row = $result_completed->fetch_assoc();
        $completed_trainings_in_office = (int)$row['total'];
    }
    $stmt_completed->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Head Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            display: flex;
            background-color: #f0f2f5;
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem;
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
        }
        .sidebar {
            width: 250px;
            background-color: #1a237e;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .sidebar .nav-link { color: white; padding: 12px 20px; border-radius: 5px; margin: 5px 15px; transition: background-color 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #3f51b5; }
        .content-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .content-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        .content-box h2 {
            margin-top: 0;
            color: #1e293b;
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 1.5rem;
        }
        /* Transparent sidebar toggle like admin */
        .sidebar .btn-toggle { background-color: transparent; border: none; color: #ffffff; padding: 6px 10px; }
        .sidebar .btn-toggle:focus { box-shadow: none; }
        .sidebar .btn-toggle:hover { background-color: transparent; }
        @media (min-width: 992px) {
            body.toggled .sidebar { width: 80px; }
            body.toggled .main-content { margin-left: 80px; }
            .sidebar .nav-link { transition: all 0.2s; }
            body.toggled .sidebar .nav-link { text-align: center; padding: 12px 0; }
            body.toggled .sidebar .nav-link i { margin-right: 0; }
            body.toggled .sidebar .nav-link span { display: none; }
            body.toggled .sidebar h3 { display: none; }
        }
        .header h1 { 
            font-size: 2rem; 
            font-weight: 800; 
            margin-bottom: .25rem; 
            color: #1e293b;
        }
        .header p { 
            color: #6b7280; 
            font-size: .95rem; 
            margin: 0; 
        }

        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .card { 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 2rem 1.5rem;
            text-align: center;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        .card h3 {
            margin: 0 0 1rem;
            color: var(--card-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .card p {
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0;
            color: var(--card-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card:nth-child(1) { 
            --card-color: #6366f1;
            --card-color-light: #a5b4fc;
        }
        .card:nth-child(2) { 
            --card-color: #10b981;
            --card-color-light: #6ee7b7;
        }

        /* Buttons consistency */
        .btn-primary,
        .btn-info,
        .btn-success { border-radius: 10px; padding: .6rem 1rem; font-weight: 600; }

        /* Center modals */
        .modal-dialog { display: flex; align-items: center; min-height: calc(100vh - 1rem); }
        .modal-content { border-radius: 14px; }
        .modal-header .btn-close { margin: -0.25rem -0.25rem -0.25rem auto; }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0 !important;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .card {
                padding: 1.5rem 1rem;
            }
            
            .card p {
                font-size: 2rem;
            }
            
            .content-box {
                padding: 1.5rem;
                border-radius: 16px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .card {
                padding: 1rem;
            }
            
            .content-box {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
    </head>
<body id="body">

    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0">Office Head Dashboard</h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'office-directory' ? 'active' : '' ?>" href="?view=office-directory">
                    <i class="fas fa-users me-2"></i> <span>Office Staff Directory</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#viewProfileModal">
                    <i class="fas fa-user me-2"></i> <span>View Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-user-edit me-2"></i> <span>Edit Profile</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <?php if ($view === 'overview'): ?>
            <div class="header mb-3">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>Your office: <strong><?php echo htmlspecialchars($office ?: 'Not set'); ?></strong></p>
            </div>
            <div class="stats-cards">
                <div class="card">
                    <h3>Staff in Your Office</h3>
                    <p><?php echo $total_staff_in_office; ?></p>
                </div>
                <div class="card">
                    <h3>Completed Trainings (Office)</h3>
                    <p><?php echo $completed_trainings_in_office; ?></p>
                </div>
            </div>
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Your Actions</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal"><i class="fas fa-plus-circle me-1"></i> Add Training</button>
                </div>
                <p>Use the Office Staff Directory to view and review trainings of staff in your office.</p>
            </div>
        <?php elseif ($view === 'office-directory'): ?>
            <div class="content-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Office Staff Directory</h2>
                </div>
                <?php
                if (!empty($office)) {
                    $stmt = $conn->prepare("SELECT u.id, u.username, u.email, s.position, s.program, s.job_function FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.role = 'staff' AND s.office = ? ORDER BY u.username");
                    $stmt->bind_param("s", $office);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = false;
                }
                ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Program</th>
                                <th>Job Function</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['program'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['job_function'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-view-trainings data-user-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-eye"></i> View Trainings
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No staff found or office not set.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('sidebar-toggle');
            if (btn) {
                btn.addEventListener('click', function(){
                    var b = document.getElementById('body') || document.body;
                    b.classList.toggle('toggled');
                });
            }

            // Load View Profile into modal when opened
            const viewProfileModal = document.getElementById('viewProfileModal');
            if (viewProfileModal) {
                viewProfileModal.addEventListener('show.bs.modal', function () {
                    fetch('view_profile_api.php', { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { document.getElementById('profileContent').innerHTML = html; })
                        .catch(() => { document.getElementById('profileContent').innerHTML = '<div class="alert alert-danger">Failed to load profile</div>'; });
                });
            }

            // Load Edit Profile form into modal when opened
            const attachSaveHandler = function() {
                const profileForm = document.getElementById('profileForm');
                const saveBtn = document.getElementById('saveProfileBtn');
                if (!profileForm || !saveBtn) return;
                const newBtn = saveBtn.cloneNode(true);
                saveBtn.parentNode.replaceChild(newBtn, saveBtn);
                newBtn.addEventListener('click', function(){
                    const formData = new FormData(profileForm);
                    const feedback = document.getElementById('editProfileFeedback');
                    fetch('edit_profile_api.php?action=save', { method: 'POST', body: formData, credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                feedback.innerHTML = '<div class="alert alert-success">Profile updated successfully!</div>';
                                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide(); window.location.reload(); }, 1000);
                            } else {
                                feedback.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to update profile') + '</div>';
                            }
                        })
                        .catch(() => { feedback.innerHTML = '<div class="alert alert-danger">Request failed</div>'; });
                });
            };

            const editProfileModal = document.getElementById('editProfileModal');
            if (editProfileModal) {
                editProfileModal.addEventListener('show.bs.modal', function () {
                    fetch('edit_profile_api.php', { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { document.getElementById('editProfileContent').innerHTML = html; attachSaveHandler(); })
                        .catch(() => { document.getElementById('editProfileContent').innerHTML = '<div class="alert alert-danger">Failed to load form</div>'; });
                });
            }

            // Staff trainings modal
            const staffTrainingsModal = document.getElementById('staffTrainingsModal');
            document.querySelectorAll('[data-view-trainings]')?.forEach(function(btn){
                btn.addEventListener('click', function(){
                    const uid = this.getAttribute('data-user-id');
                    const body = document.getElementById('staffTrainingsContent');
                    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
                    const modal = new bootstrap.Modal(staffTrainingsModal);
                    modal.show();
                    fetch('view_staff_trainings.php?id=' + encodeURIComponent(uid), { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { body.innerHTML = html; })
                        .catch(() => { body.innerHTML = '<div class="alert alert-danger">Failed to load trainings</div>'; });
                });
            });

            // Handle Add Training form submit
            const addForm = document.getElementById('addTrainingForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    const fd = new FormData(addForm);
                    fetch('training_api.php?action=create', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(data => {
                            const fb = document.getElementById('addTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training added!</div>';
                                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('addTrainingModal')).hide(); window.location.reload(); }, 800);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(() => { document.getElementById('addTrainingFeedback').innerHTML = '<div class="alert alert-danger">Request failed</div>'; });
                });
            }
        });
    </script>

    <!-- Staff Trainings Modal -->
    <div class="modal fade" id="staffTrainingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Staff Trainings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="staffTrainingsContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- View Profile Modal -->
    <div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="profileContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editProfileContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn" style="background-color: #1a237e; border-color: #1a237e;">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Training Modal -->
    <div class="modal fade" id="addTrainingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Training</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addTrainingForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Training Title</label>
                            <input type="text" name="title" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="completion_date" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="completed">Completed</option>
                                <option value="upcoming">Upcoming</option>
                            </select>
                        </div>
                        <div id="addTrainingFeedback"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>



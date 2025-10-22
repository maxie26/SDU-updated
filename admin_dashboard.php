<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$admin_username = $_SESSION['username']; 


$total_staff = 0;
$total_heads = 0;
$trainings_completed = 0;
$active_programs = 0;

$query_total_staff = "SELECT COUNT(*) AS total FROM users WHERE role IN ('staff', 'head')";
$result_total_staff = $conn->query($query_total_staff);
// total heads
$query_total_heads = "SELECT COUNT(*) AS total FROM users WHERE role = 'head'";
$result_total_heads = $conn->query($query_total_heads);
if ($result_total_heads) {
    $row = $result_total_heads->fetch_assoc();
    $total_heads = $row['total'];
}
if ($result_total_staff) {
    $row = $result_total_staff->fetch_assoc();
    $total_staff = $row['total'];
}


// Fixed: Only count trainings with status = 'completed'
if ($conn->query("SHOW TABLES LIKE 'trainings'")->num_rows == 1) {
    $query_trainings = "SELECT COUNT(*) AS total FROM user_trainings WHERE status = 'completed'";
    $result_trainings = $conn->query($query_trainings);
    if ($result_trainings) {     
        $row = $result_trainings->fetch_assoc();
        $trainings_completed = $row['total'];
    }
}


// This is also a placeholder for now
$active_programs = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { 
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; 
        display: flex; 
        background-color: #f0f2f5;
        transition: margin-left 0.3s ease-in-out; 
    }

    .main-content { flex-grow: 1; padding: 2rem; transition: margin-left 0.3s ease-in-out; }

    @media (min-width: 992px) {
        .sidebar-lg {
            width: 250px;
            background-color: #1a237e;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 2rem;
            transition: width 0.3s ease-in-out;
        }
        .main-content {
            margin-left: 250px;
        }
    }

    body.toggled .sidebar-lg {
        width: 80px; 
        padding-top: 1rem;
        color: white; 
    }

    body.toggled .sidebar-lg .profile-pic {
        display: none; 
    }

    body.toggled .sidebar-lg .nav-link {
        text-align: center; 
        padding: 12px 0;
        color: white; 
    }

    body.toggled .sidebar-lg .nav-link i,
    body.toggled .sidebar-lg .nav-link span {
        margin: 0; 
        color: white;
    }

    body.toggled .sidebar-lg .nav-link span {
        display: none;
    }

    body.toggled .main-content {
        margin-left: 80px; 
    }



    .sidebar-lg .nav-link { 
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 5px;
        margin: 5px 15px;
        transition: background-color 0.2s;
        white-space: nowrap;
        overflow: hidden;
    }
    
    .sidebar-lg .nav-link:hover, .sidebar-lg .nav-link.active {
        background-color: #3f51b5;
    }
    
    .offcanvas .nav-link { color: #ffffff; }

    body.toggled .sidebar-lg h5 {
        display: none; 
    }

    body.toggled .main-content {
        margin-left: 80px;
    }

   
    body.toggled .profile-pic {
        display: none !important; 
    }

    /* Match staff/head sidebar toggle button style */
    .sidebar-lg .btn-toggle {
        background-color: transparent;
        border: none;
        color: #ffffff;
        padding: 6px 10px;
    }
    
    .sidebar-lg .btn-toggle:focus { 
        box-shadow: none; 
    }
    
    .sidebar-lg .btn-toggle:hover { 
        background-color: transparent; 
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

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
    .card:nth-child(3) { 
        --card-color: #f59e0b;
        --card-color-light: #fbbf24;
    }

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

    table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0;
        margin-top: 1.5rem;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }

    th, td { 
        text-align: left; 
        padding: 16px 20px; 
        border-bottom: 1px solid #f1f5f9; 
    }

    th { 
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); 
        color: #475569; 
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    tr:hover td {
        background-color: #f8fafc;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .btn-primary {
        background-color: #1a237e !important;
        border-color: #1a237e !important;
    }
    .btn-primary:hover {
        background-color: #3f51b5 !important;
        border-color: #3f51b5 !important;
    }

     /* Disable all hover effects in directory content */
     #directoryContent .table tbody tr:hover,
     #directoryContent .table-hover tbody tr:hover,
     #directoryContent .table-striped tbody tr:hover,
     #directoryContent .table tbody tr:nth-child(even):hover,
     #directoryContent .table tbody tr:nth-child(odd):hover {
         background-color: transparent !important;
     }

     /* Fix modal text colors to match office head and staff dashboards */
     .modal-body .form-label {
         color: #1e293b !important;
     }
     .modal-body .form-control-plaintext {
         color: #1e293b !important;
     }
     .modal-body .badge {
         color: #fff !important;
     }

     /* Fix sidebar text colors to stay white */
     .sidebar-lg .nav-link {
         color: #ffffff !important;
     }
     .sidebar-lg .nav-link:hover,
     .sidebar-lg .nav-link:focus,
     .sidebar-lg .nav-link:active,
     .sidebar-lg .nav-link:visited {
         color: #ffffff !important;
     }
     .sidebar-lg .nav-link.active {
         color: #ffffff !important;
     }

     /* Center modals like staff and office head dashboards */
     .modal-dialog {
         display: flex;
         align-items: center;
         min-height: calc(100vh - 1rem);
     }
     .modal-content {
         border-radius: 14px;
     }
     .modal-header .btn-close {
         margin: -0.25rem -0.25rem -0.25rem auto;
     }
</style>
</head>
<body id="body">

    <div class="offcanvas offcanvas-start bg-dark text-white d-lg-none" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">SDU Admin</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
                <li class="nav-item">
                    <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="staff_report.php">Directory & Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_profile.php">View Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="edit_profile.php">Edit Profile</a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>

   <div class="sidebar-lg d-none d-lg-block">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h5 class="m-0 text-white">SDU Admin</h5>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        
        
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'directory' ? 'active' : '' ?>" href="?view=directory">
                    <i class="fas fa-users me-2"></i> <span>Directory & Reports</span>
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
        <button class="btn btn-primary d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </button>

        <?php if ($view === 'overview'): ?>
            <div class="header mb-4">
                <h1 class="fw-bold mb-2" style="color: #1e293b;">Welcome back, <?php echo htmlspecialchars($admin_username); ?>!</h1>
                <p class="mb-0" style="color: #6b7280;">Here's what's happening with your organization today.</p>
            </div>
            <div class="stats-cards">
                <div class="card">
                    <h3>Total Staff</h3>
                    <p><?php echo $total_staff; ?></p>
                </div>
                <div class="card">
                    <h3>Total Heads</h3>
                    <p><?php echo $total_heads; ?></p>
                </div>
                <div class="card">
                    <h3>Trainings Completed</h3>
                    <p><?php echo $trainings_completed; ?></p>
                </div>
            </div>
            <div class="content-box">
                <h2>Recent Activity</h2>
                <p>Content for recent activity will go here.</p>
            </div>
        <?php elseif ($view === 'directory'): ?>
            <div class="content-box">
                <h2>Directory & Reports</h2>
                <div id="directoryContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle');
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    const body = document.getElementById('body');
                    body.classList.toggle('toggled');
                });
            }

            // Load View Profile modal content
            const viewProfileModal = document.getElementById('viewProfileModal');
            if (viewProfileModal) {
                viewProfileModal.addEventListener('show.bs.modal', function () {
                    fetch('view_profile_api.php', { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => { document.getElementById('profileContent').innerHTML = html; })
                        .catch(() => { document.getElementById('profileContent').innerHTML = '<div class="alert alert-danger">Failed to load profile</div>'; });
                });
            }

            // Load Edit Profile form into modal and handle save
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

            // Load Directory & Reports content when directory view is active
            const directoryContent = document.getElementById('directoryContent');
            function loadDirectory(queryString) {
                const qs = queryString ? ('?' + queryString) : '?embed=1';
                const url = qs.includes('embed=1') ? ('staff_report.php' + (qs.startsWith('?') ? qs : ('?' + qs)))
                                                   : ('staff_report.php?embed=1' + (qs ? ('&' + qs.replace(/^\?/, '')) : ''));
                directoryContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
                fetch(url, { credentials: 'same-origin' })
                    .then(r => r.text())
                    .then(html => {
                        directoryContent.innerHTML = html;
                        attachDirectoryHandlers();
                    })
                    .catch(() => { directoryContent.innerHTML = '<div class="alert alert-danger">Failed to load Directory & Reports</div>'; });
            }

            function attachDirectoryHandlers() {
                const form = directoryContent.querySelector('#filtersForm');
                if (!form) return;
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    // gather params
                    const role = directoryContent.querySelector('#role')?.value || '';
                    const period = directoryContent.querySelector('#period')?.value || '';
                    const officeChecks = Array.from(directoryContent.querySelectorAll('.office-check'));
                    const selected = officeChecks.filter(c => c.checked).map(c => c.value);
                    const params = new URLSearchParams();
                    params.set('embed', '1');
                    if (role) params.set('role', role);
                    if (period) params.set('period', period);
                    selected.forEach(v => params.append('offices[]', v));
                    loadDirectory(params.toString());
                });
            }

            if (directoryContent && window.location.search.includes('view=directory')) {
                loadDirectory('embed=1');
            }
        });
    </script>
</body>
</html>
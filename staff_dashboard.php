<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'head'])) {
    header("Location: login.php");
    exit();
}

$view = isset($_GET['view']) ? $_GET['view'] : 'overview';
$staff_username = $_SESSION['username']; 

$message = "";
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = "<div class='alert alert-success'>Training record deleted successfully!</div>";
            break;
        case 'error':
            $message = "<div class='alert alert-danger'>Error deleting training record!</div>";
            break;
    }
}

$user_id = $_SESSION['user_id'];
$trainings_completed = 0;
$trainings_upcoming = 0;

// completed trainings count
$query_completed = "SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'completed'";
$stmt_completed = $conn->prepare($query_completed);
$stmt_completed->bind_param("i", $user_id);
$stmt_completed->execute();
$result_completed = $stmt_completed->get_result();
if ($result_completed) {
    $row = $result_completed->fetch_assoc();
    $trainings_completed = $row['total'];
}
$stmt_completed->close();

// upcoming trainings count
$query_upcoming = "SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'upcoming'";
$stmt_upcoming = $conn->prepare($query_upcoming);
$stmt_upcoming->bind_param("i", $user_id);
$stmt_upcoming->execute();
$result_upcoming = $stmt_upcoming->get_result();
if ($result_upcoming) {
    $row = $result_upcoming->fetch_assoc();
    $trainings_upcoming = $row['total'];
}
$stmt_upcoming->close();

// recent activities 
$query_recent = "SELECT t.title, ut.completion_date FROM user_trainings ut 
                  JOIN trainings t ON ut.training_id = t.id 
                  WHERE ut.user_id = ? AND ut.status = 'completed' 
                  ORDER BY ut.completion_date DESC LIMIT 5";
$stmt_recent = $conn->prepare($query_recent);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();

// upcoming trainings 
$query_upcoming = "SELECT t.title, ut.completion_date FROM user_trainings ut 
                    JOIN trainings t ON ut.training_id = t.id 
                    WHERE ut.user_id = ? AND ut.status = 'upcoming' 
                    ORDER BY ut.completion_date ASC LIMIT 3";
$stmt_upcoming = $conn->prepare($query_upcoming);
$stmt_upcoming->bind_param("i", $user_id);
$stmt_upcoming->execute();
$result_upcoming = $stmt_upcoming->get_result();

// recent activities (both completed and upcoming)
$query_all_activities = "SELECT t.title, ut.completion_date, ut.status, ut.created_at FROM user_trainings ut 
                          JOIN trainings t ON ut.training_id = t.id 
                          WHERE ut.user_id = ? 
                          ORDER BY ut.created_at DESC LIMIT 5";
$stmt_activities = $conn->prepare($query_all_activities);
$stmt_activities->bind_param("i", $user_id);
$stmt_activities->execute();
$result_activities = $stmt_activities->get_result();

if ($view === 'training-records') {
    $query_records = "SELECT ut.id, t.title, ut.completion_date, ut.status FROM user_trainings ut JOIN trainings t ON ut.training_id = t.id WHERE ut.user_id = ? ORDER BY ut.completion_date DESC";
    $stmt_records = $conn->prepare($query_records);
    $stmt_records->bind_param("i", $user_id);
    $stmt_records->execute();
    $result_records = $stmt_records->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            background-color: #f0f2f5;
        }
        @media (min-width: 992px) {
            body.toggled .sidebar { width: 80px; }
            body.toggled .main-content { margin-left: 80px; }
            .sidebar .nav-link { transition: all 0.2s; white-space: nowrap; overflow: hidden; }
            body.toggled .sidebar .nav-link { text-align: center; padding: 12px 0; }
            body.toggled .sidebar .nav-link i { margin-right: 0; }
            body.toggled .sidebar .nav-link span { display: none; }
            body.toggled .sidebar h3 { display: none; }
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
        .sidebar .nav-link {
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 5px 15px;
            transition: background-color 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #3f51b5;
        }
        .content-box { background-color: #fff; border-radius: 12px; box-shadow: 0 6px 14px rgba(0,0,0,0.08); padding: 1.5rem; border: 1px solid #eef0f6; }
        /* Transparent sidebar toggle like admin */
        .sidebar .btn-toggle {
            background-color: transparent;
            border: none;
            color: #ffffff;
            padding: 6px 10px;
        }
        .sidebar .btn-toggle:focus { box-shadow: none; }
        .sidebar .btn-toggle:hover { background-color: transparent; }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
            padding: 1.25rem 1.5rem;
            text-align: center;
            border: 1px solid #eef0f6;
        }
        .card h3 {
            margin: 0 0 0.5rem;
            color: #0d47a1;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .card p {
            font-size: 2.25rem;
            font-weight: 800;
            color: #263238;
            margin: 0;
        }
        .stats-cards .card:nth-child(1) { border-top: 4px solid #00c853; }
        .stats-cards .card:nth-child(2) { border-top: 4px solid #ff6d00; }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .action-card {
            background: #1a237e;
            color: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .action-card:hover {
            transform: translateY(-5px);
        }
        .action-card a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .action-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .btn-group .btn {
            margin-right: 5px;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        .progress-bar {
            background-color: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body id="body">

    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-3 mb-3">
            <h3 class="m-0"><?= $_SESSION['role'] === 'head' ? 'Office Head Dashboard' : 'Staff Dashboard' ?></h3>
            <button id="sidebar-toggle" class="btn btn-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $view === 'overview' ? 'active' : '' ?>" href="?view=overview">
                    <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view === 'training-records' ? 'active' : '' ?>" href="?view=training-records">
                    <i class="fas fa-book-open me-2"></i> <span>Training Records</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_profile.php">
                    <i class="fas fa-user me-2"></i> <span>View Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="edit_profile.php">
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
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($staff_username); ?>!</h1>
                <p>Here you can view your personal overview and progress.</p>
            </div>
            
            <div class="stats-cards">
                <div class="card">
                    <h3>Trainings Completed</h3>
                    <p><?php echo $trainings_completed; ?></p>
                </div>
                <div class="card">
                    <h3>Upcoming Trainings</h3>
                    <p><?php echo $trainings_upcoming; ?></p>
                </div>
            </div>

    
            <div class="quick-actions">
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#addTrainingModal" style="cursor:pointer;">
                    <i class="fas fa-plus-circle"></i>
                    <h4>Add Training</h4>
                    <p>Record a new training</p>
                </div>
                <div class="action-card">
                    <a href="view_profile.php">
                        <i class="fas fa-user"></i>
                        <h4>View Profile</h4>
                        <p>Check your information</p>
                    </a>
                </div>
                <div class="action-card">
                    <a href="?view=training-records">
                        <i class="fas fa-book-open"></i>
                        <h4>Training Records</h4>
                        <p>Manage your trainings</p>
                    </a>
                </div>
            </div>


            <?php if ($result_upcoming && $result_upcoming->num_rows > 0): ?>
            <div class="content-box mt-4">
                <h2>Upcoming Trainings</h2>
                <div class="list-group">
                    <?php while ($upcoming = $result_upcoming->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($upcoming['title']); ?></h6>
                                <small class="text-muted">Scheduled for <?php echo date('M d, Y', strtotime($upcoming['completion_date'])); ?></small>
                            </div>
                            <span class="badge bg-warning rounded-pill">Upcoming</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-box mt-4">
                <h2>Recent Activity</h2>
            <?php if ($result_activities && $result_activities->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($activity = $result_activities->fetch_assoc()): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($activity['status'] === 'completed'): ?>
                                            Completed on <?php echo date('M d, Y', strtotime($activity['completion_date'])); ?>
                                        <?php else: ?>
                                            Added on <?php echo date('M d, Y', strtotime($activity['created_at'])); ?> - Scheduled for <?php echo date('M d, Y', strtotime($activity['completion_date'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $activity['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Training Activities Yet</h5>
                        <p class="text-muted">Start by adding your first training record!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                            <i class="fas fa-plus-circle me-1"></i> Add Your First Training
                        </button>
                    </div>
                <?php endif; ?>
            </div>
      <?php elseif ($view === 'training-records'): ?>
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>My Training Records</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                <i class="fas fa-plus-circle me-1"></i> Add Training
            </button>
        </div>
        <?php echo $message; ?>
        <?php if ($result_records && $result_records->num_rows > 0): ?>
            <table class="table table-striped mt-4">
                <thead>
                    <tr>
                        <th scope="col">Training Title</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['completion_date']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($row['status'] === 'upcoming'): ?>
                                        <a href="update_training_status.php?id=<?php echo $row['id']; ?>&status=completed" 
                                           class="btn btn-success btn-sm" 
                                           onclick="return confirm('Mark this training as completed?')">
                                            <i class="fas fa-check"></i> Mark Completed
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTrainingModal"
                                        data-training-id="<?php echo $row['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                        data-date="<?php echo htmlspecialchars($row['completion_date']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="delete_training.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this training record?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info mt-4" role="alert">
                You have not completed any trainings yet.
            </div>
        <?php endif; ?>
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
            // Build Add Training Modal dynamically
            var addModalEl = document.createElement('div');
            addModalEl.className = 'modal fade';
            addModalEl.id = 'addTrainingModal';
            addModalEl.tabIndex = -1;
            addModalEl.innerHTML = '\
        <div class="modal-dialog">\
          <div class="modal-content">\
            <div class="modal-header">\
              <h5 class="modal-title">Add Training</h5>\
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\
            </div>\
            <form id="addTrainingForm">\
              <div class="modal-body">\
                <div class="mb-3">\
                  <label class="form-label">Training Title</label>\
                  <input type="text" name="title" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Description</label>\
                  <textarea name="description" class="form-control" rows="3"></textarea>\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Date</label>\
                  <input type="date" name="completion_date" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Status</label>\
                  <select name="status" class="form-control" required>\
                    <option value="completed">Completed</option>\
                    <option value="upcoming">Upcoming</option>\
                  </select>\
                </div>\
                <div id="addTrainingFeedback"></div>\
              </div>\
              <div class="modal-footer">\
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>\
                <button type="submit" class="btn btn-primary">Save</button>\
              </div>\
            </form>\
          </div>\
        </div>';
            document.body.appendChild(addModalEl);

            // Build Edit Training Modal dynamically
            var editModalEl = document.createElement('div');
            editModalEl.className = 'modal fade';
            editModalEl.id = 'editTrainingModal';
            editModalEl.tabIndex = -1;
            editModalEl.innerHTML = '\
        <div class="modal-dialog">\
          <div class="modal-content">\
            <div class="modal-header">\
              <h5 class="modal-title">Edit Training</h5>\
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\
            </div>\
            <form id="editTrainingForm">\
              <div class="modal-body">\
                <input type="hidden" name="id" />\
                <div class="mb-3">\
                  <label class="form-label">Training Title</label>\
                  <input type="text" name="title" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Description</label>\
                  <textarea name="description" class="form-control" rows="3"></textarea>\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Date</label>\
                  <input type="date" name="completion_date" class="form-control" required />\
                </div>\
                <div class="mb-3">\
                  <label class="form-label">Status</label>\
                  <select name="status" class="form-control" required>\
                    <option value="completed">Completed</option>\
                    <option value="upcoming">Upcoming</option>\
                  </select>\
                </div>\
                <div id="editTrainingFeedback"></div>\
              </div>\
              <div class="modal-footer">\
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>\
                <button type="submit" class="btn btn-primary">Update</button>\
              </div>\
            </form>\
          </div>\
        </div>';
            document.body.appendChild(editModalEl);

            var editTrainingModal = document.getElementById('editTrainingModal');
            if (editTrainingModal) {
                editTrainingModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    var form = document.getElementById('editTrainingForm');
                    form.elements['id'].value = button.getAttribute('data-training-id');
                    form.elements['title'].value = button.getAttribute('data-title');
                    form.elements['completion_date'].value = button.getAttribute('data-date');
                    form.elements['status'].value = button.getAttribute('data-status');
                });
            }

            var addForm = document.getElementById('addTrainingForm');
            if (addForm) {
                addForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(addForm);
                    fetch('training_api.php?action=create', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            var fb = document.getElementById('addTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training added!</div>';
                                setTimeout(function(){ window.location.reload(); }, 600);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(function(){
                            var fb = document.getElementById('addTrainingFeedback');
                            fb.innerHTML = '<div class="alert alert-danger">Request failed</div>';
                        });
                });
            }

            var editForm = document.getElementById('editTrainingForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(editForm);
                    fetch('training_api.php?action=update', { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(data){
                            var fb = document.getElementById('editTrainingFeedback');
                            if (data.success) {
                                fb.innerHTML = '<div class="alert alert-success">Training updated!</div>';
                                setTimeout(function(){ window.location.reload(); }, 600);
                            } else {
                                fb.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed') + '</div>';
                            }
                        })
                        .catch(function(){
                            var fb = document.getElementById('editTrainingFeedback');
                            fb.innerHTML = '<div class="alert alert-danger">Request failed</div>';
                        });
                });
            }
        });
    </script>
</body>
</html>
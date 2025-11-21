<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Please log in to view your profile.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt_fetch = $conn->prepare("SELECT u.username, u.email, u.role, s.position, s.office, s.job_function FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data) {
    echo '<div class="alert alert-danger">User data not found.</div>';
    exit();
}

// Set default values
$user_data['position'] = !empty($user_data['position']) ? $user_data['position'] : 'Not specified';
$user_data['office'] = !empty($user_data['office']) ? $user_data['office'] : 'Not specified';
$user_data['job_function'] = !empty($user_data['job_function']) ? $user_data['job_function'] : 'Not specified';
?>

<div class="profile-info">
    <?php if ($user_data['role'] === 'admin'): ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Username</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['username']); ?></p>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Email</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['email']); ?></p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Role</label>
            <p class="form-control-plaintext">
                <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></span>
            </p>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Username</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['username']); ?></p>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Email</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['email']); ?></p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Role</label>
            <p class="form-control-plaintext">
                <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></span>
            </p>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Position</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['position']); ?></p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Office</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['office']); ?></p>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Job Function</label>
            <p class="form-control-plaintext"><?php echo htmlspecialchars($user_data['job_function']); ?></p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <label class="form-label fw-bold">Training Statistics</label>
            <div class="row">
                <?php
                // Get training statistics
                $query_completed = "SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'completed'";
                $stmt_completed = $conn->prepare($query_completed);
                $stmt_completed->bind_param("i", $user_id);
                $stmt_completed->execute();
                $result_completed = $stmt_completed->get_result();
                $completed_count = $result_completed->fetch_assoc()['total'];
                $stmt_completed->close();

                $query_upcoming = "SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'upcoming'";
                $stmt_upcoming = $conn->prepare($query_upcoming);
                $stmt_upcoming->bind_param("i", $user_id);
                $stmt_upcoming->execute();
                $result_upcoming = $stmt_upcoming->get_result();
                $upcoming_count = $result_upcoming->fetch_assoc()['total'];
                $stmt_upcoming->close();
                ?>
                <div class="col-12 mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Completed Trainings:</span>
                        <span><?php echo $completed_count; ?></span>
                    </div>
                </div>
                <div class="col-12 mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Upcoming Trainings:</span>
                        <span><?php echo $upcoming_count; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="mt-4 text-end">
        <button type="button" class="btn btn-primary" onclick="switchToEditMode()">
            <i class="fas fa-edit me-2"></i> Edit Profile
        </button>
    </div>
</div>

<script>
function switchToEditMode() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
    if (modal) {
        modal.hide();
        setTimeout(() => {
            initProfileModal('edit');
            const editModal = new bootstrap.Modal(document.getElementById('profileModal'));
            editModal.show();
        }, 300);
    }
}
</script>

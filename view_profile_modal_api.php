<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Please log in to view your profile.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'staff';

$stmt_fetch = $conn->prepare("
    SELECT u.username, u.email, u.role, u.created_at,
           s.position, s.program, s.job_function, s.office
    FROM users u
    LEFT JOIN staff_details s ON u.id = s.user_id
    WHERE u.id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data) {
    echo '<div class="alert alert-danger">User data not found.</div>';
    exit();
}

$user_data['username'] = $user_data['username'] ?? '';
$user_data['email'] = $user_data['email'] ?? '';
$user_data['role'] = $user_data['role'] ?? '';
$user_data['position'] = $user_data['position'] ?? '';
$user_data['program'] = $user_data['program'] ?? '';
$user_data['job_function'] = $user_data['job_function'] ?? '';
$user_data['office'] = $user_data['office'] ?? '';
$user_data['created_at'] = $user_data['created_at'] ?? '';

$completed_count = 0;
$upcoming_count = 0;
$stmt_completed = $conn->prepare("SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'completed'");
$stmt_completed->bind_param("i", $user_id);
$stmt_completed->execute();
$res_completed = $stmt_completed->get_result();
if ($res_completed) {
    $completed_count = (int)($res_completed->fetch_assoc()['total'] ?? 0);
}
$stmt_completed->close();

$stmt_upcoming = $conn->prepare("SELECT COUNT(*) AS total FROM user_trainings WHERE user_id = ? AND status = 'upcoming'");
$stmt_upcoming->bind_param("i", $user_id);
$stmt_upcoming->execute();
$res_upcoming = $stmt_upcoming->get_result();
if ($res_upcoming) {
    $upcoming_count = (int)($res_upcoming->fetch_assoc()['total'] ?? 0);
}
$stmt_upcoming->close();
?>

<style>
    .profile-summary {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 15px 30px rgba(15, 23, 42, 0.08);
    }
    .profile-edit {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    }
    .avatar-circle {
        width: 110px;
        height: 110px;
        background: linear-gradient(135deg, #1a237e 0%, #4c6ef5 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 2rem;
        margin: 0 auto 1rem;
        font-weight: 700;
    }
    .info-chip {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        background: #f8fafc;
    }
    .stat-card {
        border-radius: 16px;
        padding: 1.25rem;
        background: #f1f5f9;
    }
    .stat-card h6 {
        margin-bottom: 0.35rem;
        font-size: 0.9rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card span {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
    }
    .form-control, .form-select {
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }
    .btn-primary {
        background-color: #1a237e;
        border-color: #1a237e;
        border-radius: 12px;
        padding: 0.85rem 1.5rem;
        font-weight: 600;
    }
    .btn-outline-secondary {
        border-radius: 12px;
        padding: 0.65rem 1.25rem;
    }
</style>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="profile-summary h-100">
            <div class="text-center mb-4">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($user_data['username'], 0, 2)); ?>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user_data['username']); ?></h4>
                <span class="badge bg-primary text-uppercase"><?php echo htmlspecialchars($user_data['role']); ?></span>
            </div>
            <div class="mb-3">
                <label class="text-uppercase text-muted small mb-1">Email</label>
                <div class="info-chip"><?php echo htmlspecialchars($user_data['email']); ?></div>
            </div>
            <?php if ($role !== 'admin'): ?>
            <div class="mb-3">
                <label class="text-uppercase text-muted small mb-1">Position</label>
                <div class="info-chip"><?php echo $user_data['position'] ? htmlspecialchars($user_data['position']) : 'Not specified'; ?></div>
            </div>
            <div class="mb-3">
                <label class="text-uppercase text-muted small mb-1">Program</label>
                <div class="info-chip"><?php echo $user_data['program'] ? htmlspecialchars($user_data['program']) : 'Not specified'; ?></div>
            </div>
            <div class="mb-3">
                <label class="text-uppercase text-muted small mb-1">Job Function</label>
                <div class="info-chip"><?php echo $user_data['job_function'] ? htmlspecialchars($user_data['job_function']) : 'Not specified'; ?></div>
            </div>
            <div class="mb-3">
                <label class="text-uppercase text-muted small mb-1">Office</label>
                <div class="info-chip"><?php echo $user_data['office'] ? htmlspecialchars($user_data['office']) : 'Not specified'; ?></div>
            </div>
            <?php endif; ?>
            <div class="mb-4">
                <label class="text-uppercase text-muted small mb-1">Member since</label>
                <div class="info-chip">
                    <?php echo $user_data['created_at'] ? date('M d, Y', strtotime($user_data['created_at'])) : 'Not available'; ?>
                </div>
            </div>
            <?php if ($role !== 'admin'): ?>
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card text-center">
                        <h6>Completed</h6>
                        <span><?php echo $completed_count; ?></span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card text-center">
                        <h6>Upcoming</h6>
                        <span><?php echo $upcoming_count; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="profile-edit">
            <h5 class="mb-3">Edit details</h5>
            <form id="profileUpdateForm" novalidate>
                <div class="mb-3">
                    <label class="form-label">Email address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <?php if ($role !== 'admin'): ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" name="position" value="<?php echo htmlspecialchars($user_data['position']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Program</label>
                        <input type="text" class="form-control" name="program" value="<?php echo htmlspecialchars($user_data['program']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Job Function</label>
                        <input type="text" class="form-control" name="job_function" value="<?php echo htmlspecialchars($user_data['job_function']); ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Office</label>
                    <select class="form-select" name="office" id="modal_office">
                        <option value="">Select Office</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" placeholder="Leave blank to keep current password">
                    <small class="text-muted">Use a strong passphrase with at least 8 characters.</small>
                </div>
                <div id="profileFeedback" class="mt-3"></div>
                <div class="mt-4 d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save changes
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="resetProfileForm">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store original form values for reset functionality
const originalFormValues = {
    email: '<?php echo htmlspecialchars($user_data['email'], ENT_QUOTES); ?>',
    position: '<?php echo htmlspecialchars($user_data['position'] ?? '', ENT_QUOTES); ?>',
    program: '<?php echo htmlspecialchars($user_data['program'] ?? '', ENT_QUOTES); ?>',
    job_function: '<?php echo htmlspecialchars($user_data['job_function'] ?? '', ENT_QUOTES); ?>',
    office: '<?php echo htmlspecialchars($user_data['office'] ?? '', ENT_QUOTES); ?>',
    new_password: ''
};

// Load offices dynamically
(async function() {
    try {
        const res = await fetch('offices_api.php');
        const data = await res.json();
        if (data.success && data.data) {
            const select = document.getElementById('modal_office');
            if (select) {
                const currentOffice = '<?php echo htmlspecialchars($user_data['office'] ?? '', ENT_QUOTES); ?>';
                // Clear existing options except the first one
                while (select.options.length > 1) {
                    select.remove(1);
                }
                data.data.forEach(office => {
                    const option = document.createElement('option');
                    option.value = office.name;
                    option.textContent = office.name;
                    if (office.name === currentOffice) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        }
    } catch (e) {
        console.error('Failed to load offices:', e);
    }
})();

// Handle form submission
(function(){
    const form = document.getElementById('profileUpdateForm');
    const feedback = document.getElementById('profileFeedback');
    const resetBtn = document.getElementById('resetProfileForm');
    if (!form) return;

    form.addEventListener('submit', function(e){
        e.preventDefault();
        
        // Validate required fields
        const email = form.querySelector('input[name="email"]');
        if (!email || !email.value.trim()) {
            feedback.innerHTML = '<div class="alert alert-danger">Email address is required.</div>';
            return;
        }
        
        const formData = new FormData(form);
        
        // Ensure all fields are properly included
        const officeSelect = form.querySelector('select[name="office"]');
        if (officeSelect) {
            formData.set('office', officeSelect.value || '');
        }
        
        fetch('edit_profile_api.php?action=save', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.json();
        })
        .then(data => {
            if (data.success) {
                feedback.innerHTML = '<div class="alert alert-success">Profile updated successfully.</div>';
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
                    if (modal) modal.hide();
                    window.location.reload();
                }, 1200);
            } else {
                feedback.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to update profile') + '</div>';
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            feedback.innerHTML = '<div class="alert alert-danger">Request failed. Please try again.</div>';
        });
    });

    // Reset button - restore original values
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            const emailInput = form.querySelector('input[name="email"]');
            const positionInput = form.querySelector('input[name="position"]');
            const jobFunctionInput = form.querySelector('input[name="job_function"]');
            const officeSelect = form.querySelector('select[name="office"]');
            const passwordInput = form.querySelector('input[name="new_password"]');
            
            if (emailInput) emailInput.value = originalFormValues.email;
            if (positionInput) positionInput.value = originalFormValues.position;
            if (jobFunctionInput) jobFunctionInput.value = originalFormValues.job_function;
            if (officeSelect) {
                // Set the office value
                officeSelect.value = originalFormValues.office || '';
                // Trigger change event to ensure UI updates
                officeSelect.dispatchEvent(new Event('change'));
            }
            if (passwordInput) passwordInput.value = '';
            
            // Clear any feedback messages
            if (feedback) feedback.innerHTML = '';
        });
    }
})();
</script>


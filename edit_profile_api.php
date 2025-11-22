<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['action']) && $_GET['action'] === 'save') {
        echo json_encode(['success' => false, 'error' => 'Please log in to edit your profile.']);
    } else {
        echo '<div class="alert alert-danger">Please log in to edit your profile.</div>';
    }
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if (isset($_GET['action']) && $_GET['action'] === 'save') {
    header('Content-Type: application/json');
    
    // Validate required fields
    if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
        echo json_encode(['success' => false, 'error' => 'Email address is required.']);
        exit();
    }
    
    $email = trim($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
        exit();
    }
    
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $program = isset($_POST['program']) ? trim($_POST['program']) : '';
    $job_function = isset($_POST['job_function']) ? trim($_POST['job_function']) : '';
    $office = isset($_POST['office']) ? trim($_POST['office']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    $conn->begin_transaction();

    try {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt_user->bind_param("ssi", $email, $hashed, $user_id);
        } else {
            $stmt_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt_user->bind_param("si", $email, $user_id);
        }
        
        if (!$stmt_user->execute()) {
            throw new Exception("Error updating user information: " . $stmt_user->error);
        }
        $stmt_user->close();

        // Update staff_details for staff and head roles
        if ($_SESSION['role'] !== 'admin') {
            $stmt_check = $conn->prepare("SELECT user_id FROM staff_details WHERE user_id = ?");
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $stmt_details = $conn->prepare("UPDATE staff_details SET position = ?, program = ?, job_function = ?, office = ? WHERE user_id = ?");
                $stmt_details->bind_param("ssssi", $position, $program, $job_function, $office, $user_id);
                if (!$stmt_details->execute()) {
                    throw new Exception("Error updating staff details: " . $stmt_details->error);
                }
                $stmt_details->close();
            } else {
                $stmt_details = $conn->prepare("INSERT INTO staff_details (user_id, position, program, job_function, office) VALUES (?, ?, ?, ?, ?)");
                $stmt_details->bind_param("issss", $user_id, $position, $program, $job_function, $office);
                if (!$stmt_details->execute()) {
                    throw new Exception("Error inserting staff details: " . $stmt_details->error);
                }
                $stmt_details->close();
            }
            $stmt_check->close();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

$stmt_fetch = $conn->prepare("SELECT u.email, s.position, s.program, s.job_function, s.office FROM users u LEFT JOIN staff_details s ON u.id = s.user_id WHERE u.id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$user_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$user_data) {
    echo '<div class="alert alert-danger">User data not found.</div>';
    exit();
}

$user_data['email'] = $user_data['email'] ?? '';
$user_data['position'] = $user_data['position'] ?? '';
$user_data['program'] = $user_data['program'] ?? '';
$user_data['job_function'] = $user_data['job_function'] ?? '';
$user_data['office'] = $user_data['office'] ?? '';
?>

<div id="editProfileForm">
    <form id="profileForm">
        <div class="mb-3">
            <label for="modal_email" class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="modal_email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
        
        <?php if ($_SESSION['role'] !== 'admin'): ?>
        <div class="mb-3">
            <label for="modal_position" class="form-label">Position</label>
            <input type="text" class="form-control" id="modal_position" name="position" value="<?php echo htmlspecialchars($user_data['position']); ?>">
        </div>
        <div class="mb-3">
            <label for="modal_job_function" class="form-label">Job Function</label>
            <input type="text" class="form-control" id="modal_job_function" name="job_function" value="<?php echo htmlspecialchars($user_data['job_function']); ?>">
        </div>
        <div class="mb-3">
            <label for="modal_office" class="form-label">Office</label>
            <select class="form-select" id="modal_office" name="office">
                <option value="">Select Office</option>
            </select>
        </div>
        
        <?php endif; ?>
        
        <div class="mb-3">
            <label for="modal_new_password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="modal_new_password" name="new_password" placeholder="Leave blank to keep current password">
            <div class="form-text">Leave blank if you don't want to change your password</div>
        </div>
        
        <div id="editProfileFeedback"></div>
    </form>
</div>

<script>
// Load offices dynamically
(async function() {
    try {
        const res = await fetch('offices_api.php');
        const data = await res.json();
        if (data.success && data.data) {
            const select = document.getElementById('modal_office');
            if (select) {
                const currentOffice = '<?php echo htmlspecialchars($user_data['office']); ?>';
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
</script>

<script>

    // Handle form submission
    const profileForm = document.getElementById('profileForm');
    const saveBtn = document.getElementById('saveProfileBtn');
    
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const formData = new FormData(profileForm);
            const feedback = document.getElementById('editProfileFeedback');
            
            fetch('edit_profile_api.php?action=save', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedback.innerHTML = '<div class="alert alert-success">Profile updated successfully!</div>';
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
                        if (modal) modal.hide();
                        window.location.reload();
                    }, 1500);
                } else {
                    feedback.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to update profile') + '</div>';
                }
            })
            .catch(error => {
                feedback.innerHTML = '<div class="alert alert-danger">Request failed</div>';
            });
        });
    }
});
</script>

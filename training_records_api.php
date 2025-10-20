<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Please log in to view training records.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

$query_records = "SELECT ut.id, t.title, ut.completion_date, ut.status FROM user_trainings ut JOIN trainings t ON ut.training_id = t.id WHERE ut.user_id = ? ORDER BY ut.completion_date DESC";
$stmt_records = $conn->prepare($query_records);
$stmt_records->bind_param("i", $user_id);
$stmt_records->execute();
$result_records = $stmt_records->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Your Training Records</h6>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
        <i class="fas fa-plus me-1"></i> Add Training
    </button>
</div>

<?php if ($result_records->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($record = $result_records->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['title']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['completion_date'])); ?></td>
                        <td>
                            <span class="badge <?php echo $record['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php if ($record['status'] === 'upcoming'): ?>
                                    <button class="btn btn-success btn-sm" onclick="updateTrainingStatus(<?php echo $record['id']; ?>, 'completed')">
                                        <i class="fas fa-check"></i> Complete
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTrainingModal"
                                    data-training-id="<?php echo $record['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($record['title']); ?>"
                                    data-date="<?php echo htmlspecialchars($record['completion_date']); ?>"
                                    data-status="<?php echo htmlspecialchars($record['status']); ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteTraining(<?php echo $record['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="text-center py-4">
        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
        <h6 class="text-muted">No Training Records Yet</h6>
        <p class="text-muted">Start by adding your first training record!</p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
            <i class="fas fa-plus-circle me-1"></i> Add Your First Training
        </button>
    </div>
<?php endif; ?>

<script>
function updateTrainingStatus(id, status) {
    if (confirm('Mark this training as ' + status + '?')) {
        fetch('update_training_status.php?id=' + id + '&status=' + status, { credentials: 'same-origin' })
            .then(response => response.text())
            .then(data => {
                if (data.includes('successfully')) {
                    // Refresh the modal content
                    fetch('training_records_api.php', { credentials: 'same-origin' })
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('trainingRecordsContent').innerHTML = data;
                        });
                } else {
                    alert('Error updating training status');
                }
            })
            .catch(error => {
                alert('Error updating training status');
            });
    }
}

function deleteTraining(id) {
    if (confirm('Are you sure you want to delete this training record?')) {
        fetch('delete_training.php?id=' + id, { credentials: 'same-origin' })
            .then(response => response.text())
            .then(data => {
                if (data.includes('successfully')) {
                    // Refresh the modal content
                    fetch('training_records_api.php', { credentials: 'same-origin' })
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('trainingRecordsContent').innerHTML = data;
                        });
                } else {
                    alert('Error deleting training record');
                }
            })
            .catch(error => {
                alert('Error deleting training record');
            });
    }
}
</script>

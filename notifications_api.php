<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Please log in to view notifications.</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications (messages from admin/head to this user)
$query = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, 
                 u.username AS sender_name, u.role AS sender_role
          FROM messages m
          LEFT JOIN users u ON m.sender_id = u.id
          WHERE m.receiver_id = ? AND u.role IN ('admin', 'head')
          ORDER BY m.created_at DESC
          LIMIT 50";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><i class="fas fa-bell me-2"></i>Your Notifications</h6>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
            <i class="fas fa-check-double me-1"></i> Mark All Read
        </button>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="list-group">
        <?php while($notification = $result->fetch_assoc()): ?>
            <div class="list-group-item <?php echo $notification['is_read'] == 0 ? 'list-group-item-warning' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                <div class="d-flex w-100 justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <i class="fas fa-<?php echo $notification['sender_role'] === 'admin' ? 'user-shield' : 'user-tie'; ?> me-2"></i>
                            <?php echo htmlspecialchars($notification['sender_name']); ?>
                            <span class="badge bg-<?php echo $notification['sender_role'] === 'admin' ? 'primary' : 'info'; ?> ms-2">
                                <?php echo ucfirst($notification['sender_role']); ?>
                            </span>
                        </h6>
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                        </small>
                    </div>
                    <?php if ($notification['is_read'] == 0): ?>
                        <button class="btn btn-sm btn-outline-success ms-2" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
        <h6 class="text-muted">No Notifications</h6>
        <p class="text-muted small">You're all caught up! New notifications will appear here.</p>
    </div>
<?php endif; ?>

<script>
function markAsRead(id) {
    fetch('mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ids[]=' + id,
        credentials: 'same-origin'
    })
    .then(() => {
        const item = document.querySelector(`[data-id="${id}"]`);
        if (item) {
            item.classList.remove('list-group-item-warning');
            const btn = item.querySelector('button');
            if (btn) btn.remove();
        }
        updateUnreadCount();
    });
}

function markAllAsRead() {
    const unread = Array.from(document.querySelectorAll('.list-group-item-warning')).map(el => el.getAttribute('data-id'));
    if (unread.length === 0) return;
    
    const formData = new FormData();
    unread.forEach(id => formData.append('ids[]', id));
    
    fetch('mark_read.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(() => {
        document.querySelectorAll('.list-group-item-warning').forEach(item => {
            item.classList.remove('list-group-item-warning');
            const btn = item.querySelector('button');
            if (btn) btn.remove();
        });
        updateUnreadCount();
    });
}

function updateUnreadCount() {
    fetch('get_unread_count.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            const sidebarBadge = document.getElementById('unreadBadge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                }
                if (sidebarBadge) {
                    sidebarBadge.textContent = data.count;
                    sidebarBadge.style.display = 'inline-block';
                }
            } else {
                if (badge) badge.style.display = 'none';
                if (sidebarBadge) sidebarBadge.style.display = 'none';
            }
        });
}
</script>


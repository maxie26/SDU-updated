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

<style>
    .notifications-container .list-group-item { border-radius: 10px; margin-bottom: 8px; }
    .notifications-container .unread { background: linear-gradient(90deg,#eef6ff,#f7fbff); border-left:4px solid #6366f1; }
    .notifications-container .mark-read-btn { min-width:36px; }
    .notifications-header { padding-bottom: .5rem; border-bottom: 1px solid #eef2ff; margin-bottom: .75rem; }
</style>

<div class="notifications-container">
<div class="notifications-header d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><i class="fas fa-bell me-2"></i>Your Notifications</h6>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-sm btn-primary me-2" id="markAllReadBtn">
            <i class="fas fa-check-double me-1"></i> Mark All Read
        </button>
        <button type="button" class="btn btn-sm btn-danger" id="deleteAllBtn">
            <i class="fas fa-trash me-1"></i> Delete All
        </button>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="list-group">
        <?php while($notification = $result->fetch_assoc()): ?>
            <?php $isUnread = intval($notification['is_read']) === 0; ?>
            <div class="list-group-item py-3 <?php echo $isUnread ? 'unread' : ''; ?> d-flex align-items-start" data-id="<?php echo $notification['id']; ?>">
                <div class="me-3">
                    <i class="fas fa-<?php echo $notification['sender_role'] === 'admin' ? 'user-shield' : 'user-tie'; ?> fa-2x text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="fw-semibold mb-1"><?php echo htmlspecialchars($notification['sender_name']); ?> <span class="badge bg-secondary ms-2 text-uppercase"><?php echo htmlspecialchars($notification['sender_role']); ?></span></div>
                            <div class="text-muted small mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></div>
                            <div class="small text-muted"><i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></div>
                        </div>
                        <div class="ms-3 text-end">
                            <?php if ($isUnread): ?>
                                <button class="btn btn-sm btn-success mark-read-btn" data-id="<?php echo $notification['id']; ?>" title="Mark as read"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                                <span class="text-muted small">Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
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
// Event delegation for per-notification mark-as-read buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.mark-read-btn');
    if (btn) {
        console.log('mark-read-btn clicked', btn.getAttribute('data-id'));
        const id = btn.getAttribute('data-id');
        if (!id) return;
        btn.disabled = true;
        fetch('mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: [id] }),
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            console.log('mark_read response', data);
            if (data && data.success) {
                const item = document.querySelector(`[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const actionBtn = item.querySelector('.mark-read-btn');
                    if (actionBtn) actionBtn.remove();
                }
                updateUnreadCount();
            } else {
                console.error('Mark read failed', data);
                btn.disabled = false;
            }
        })
        .catch(err => { console.error(err); btn.disabled = false; });
    }

    // Mark all read button
    const allBtn = e.target.closest('#markAllReadBtn');
    if (allBtn) {
        console.log('markAllReadBtn clicked');
        allBtn.disabled = true;
        const unreadEls = Array.from(document.querySelectorAll('.list-group-item.unread'));
        const ids = unreadEls.map(el => el.getAttribute('data-id')).filter(Boolean);
        if (ids.length === 0) {
            allBtn.disabled = false;
            return;
        }

        fetch('mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids }),
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            console.log('mark_all response', data);
            if (data && data.success) {
                unreadEls.forEach(item => {
                    item.classList.remove('unread');
                    const actionBtn = item.querySelector('.mark-read-btn');
                    if (actionBtn) actionBtn.remove();
                });
                updateUnreadCount();
            } else {
                console.error('Mark all read failed', data);
            }
            allBtn.disabled = false;
        })
        .catch(err => { console.error(err); allBtn.disabled = false; });
    }

    // Delete all button - remove ALL notifications (read + unread)
    const delBtn = e.target.closest('#deleteAllBtn');
    if (delBtn) {
        delBtn.disabled = true;
        // select all notification items, not just unread ones
        const allEls = Array.from(document.querySelectorAll('.list-group-item'));
        const ids = allEls.map(el => el.getAttribute('data-id')).filter(Boolean);
        if (ids.length === 0) { delBtn.disabled = false; return; }

        fetch('delete_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids }),
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            console.log('delete_all response', data);
            if (data && data.success) {
                allEls.forEach(item => item.remove());
                updateUnreadCount();
            } else {
                console.error('Delete all failed', data);
            }
            delBtn.disabled = false;
        })
        .catch(err => { console.error(err); delBtn.disabled = false; });
    }
});

function updateUnreadCount() {
    fetch('get_unread_count.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            const sidebarBadge = document.getElementById('unreadBadge');
            const count = (data && typeof data.count === 'number') ? data.count : 0;
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = 'block';
                }
                if (sidebarBadge) {
                    sidebarBadge.textContent = count;
                    sidebarBadge.style.display = 'inline-block';
                }
            } else {
                if (badge) badge.style.display = 'none';
                if (sidebarBadge) sidebarBadge.style.display = 'none';
            }
        })
        .catch(err => console.error('Unread count failed', err));
</script>


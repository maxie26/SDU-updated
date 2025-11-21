<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';

// Handle POST actions: send message, mark_read, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark selected messages as read
    if (isset($_POST['action']) && $_POST['action'] === 'mark_read' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ids = array_filter($ids);
        if (!empty($ids)) {
            $in = implode(',', $ids);
            $sql = "UPDATE messages SET is_read = 1 WHERE id IN ($in) AND receiver_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) { $stmt->bind_param('i', $user_id); $stmt->execute(); $stmt->close(); }
        }
        header('Location: messages_inbox.php'); exit;
    }

    // Delete selected messages (allowed if user is sender or receiver)
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ids = array_filter($ids);
        if (!empty($ids)) {
            $in = implode(',', $ids);
            $sql = "DELETE FROM messages WHERE id IN ($in) AND (receiver_id = ? OR sender_id = ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) { $stmt->bind_param('ii', $user_id, $user_id); $stmt->execute(); $stmt->close(); }
        }
        header('Location: messages_inbox.php'); exit;
    }

    // Sending a message (optional)
    if (isset($_POST['message']) && isset($_POST['receiver_id'])) {
        $receiver_id = intval($_POST['receiver_id']);
        $message = trim($_POST['message']);
        if ($receiver_id > 0 && $message !== '') {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('iis', $user_id, $receiver_id, $message);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: messages_inbox.php?view=' . $receiver_id);
            exit;
        }
    }
}

// Fetch notifications sent to this user
$notifications = [];
$stmt = $conn->prepare("SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, 
    COALESCE(s.username, 'System') AS sender_name,
    s.role AS sender_role
    FROM messages m
    LEFT JOIN users s ON s.id = m.sender_id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// If view specified, show single notification/message and mark it read
$view_id = isset($_GET['view']) ? intval($_GET['view']) : 0;
$view_message = null;
if ($view_id > 0) {
    // mark as read if receiver is current user
    $upd = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ? AND is_read = 0");
    if ($upd) { $upd->bind_param('ii', $view_id, $user_id); $upd->execute(); $upd->close(); }

    $stmt2 = $conn->prepare("SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, COALESCE(s.username,'System') AS sender_name,
        s.role AS sender_role
        FROM messages m
        LEFT JOIN users s ON s.id = m.sender_id
        WHERE m.id = ? AND m.receiver_id = ?");
    if ($stmt2) {
        $stmt2->bind_param('ii', $view_id, $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $view_message = $res2->fetch_assoc();
        $stmt2->close();
    }
}

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .unread { background-color: #e7f3ff; font-weight: 600; border-left: 4px solid #667eea; }
        .message-item { cursor: pointer; transition: background 0.2s; }
        .conversation-msg { margin-bottom: 10px; padding: 10px; border-radius: 5px; }
        .msg-sender { background-color: #e7f3ff; text-align: left; }
        .msg-self { background-color: #d4edda; text-align: right; }
        h2 { color: white; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-inbox"></i> Your Inbox</h2>
        <a href="staff_dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-md-4">
                <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Notifications</h5>
                    <small class="text-white">Admins & office heads</small>
                </div>
                <div class="card-body p-2">
                    <form method="post" id="notifForm">
                        <div class="mb-2 d-flex gap-2">
                            <button type="submit" name="action" value="mark_read" class="btn btn-sm btn-success">Mark Selected Read</button>
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete selected notifications?')">Delete Selected</button>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($notifications)): ?>
                                <li class="list-group-item text-muted text-center">No notifications</li>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <?php $isUnread = intval($n['is_read']) === 0; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start p-2 <?php echo $isUnread ? 'unread' : ''; ?>">
                                        <div style="flex:1">
                                            <label style="display:block;">
                                                <input type="checkbox" name="ids[]" value="<?php echo intval($n['id']); ?>"> 
                                                <a href="?view=<?php echo intval($n['id']); ?>" class="text-decoration-none ms-2 <?php echo $isUnread ? 'fw-bold' : ''; ?>">
                                                    <?php echo esc($n['sender_name']); ?>
                                                    <?php if (!empty($n['sender_role'])): ?>
                                                        <span class="badge bg-secondary ms-1 text-uppercase"><?php echo esc($n['sender_role']); ?></span>
                                                    <?php endif; ?>
                                                    — <?php echo esc(substr($n['message'], 0, 80)); ?><?php echo (strlen($n['message'])>80)?'...':''; ?>
                                                </a>
                                            </label>
                                            <p class="mb-0 small text-muted"><?php echo date('M j, Y H:i', strtotime($n['created_at'])); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </form>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

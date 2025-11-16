<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
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
        .message-item:hover { background-color: #f8f9fa; }
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
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Messages</h5>
                </div>
                <div class="card-body p-0">
                    <ul id="messageList" class="list-group list-group-flush"></ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Conversation</h5>
                </div>
                <div class="card-body" id="conversation" style="min-height: 300px; max-height: 400px; overflow-y: auto; background-color: #f9f9f9;">
                    <p class="text-muted text-center">Select a message to view conversation</p>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <button id="markReadBtn" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Mark Selected Read</button>
                        <button id="deleteBtn" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete Selected</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const userId = <?php echo $user_id; ?>;

async function loadInbox() {
    const res = await fetch('get_messages.php');
    const j = await res.json();
    const list = document.getElementById('messageList');
    list.innerHTML = '';
    
    if (!j.success || !j.messages.length) {
        list.innerHTML = '<li class="list-group-item text-muted text-center">No messages</li>';
        return;
    }
    
    j.messages.forEach(m => {
        const li = document.createElement('li');
        li.className = 'list-group-item message-item d-flex justify-content-between align-items-start p-3';
        if (m.is_read == 0) li.classList.add('unread');
        li.dataset.id = m.id;
        li.dataset.senderId = m.sender_id;
        
        const date = new Date(m.created_at).toLocaleDateString();
        li.innerHTML = `
            <div style="flex:1">
                <strong>${escapeHtml(m.sender_name || 'System')}</strong>
                <p class="mb-0 small text-muted">${date}</p>
            </div>
            <input type="checkbox" class="msg-check" data-id="${m.id}">
        `;
        
        li.addEventListener('click', (e) => {
            if (e.target.tagName !== 'INPUT') {
                showConversation(m.sender_id);
            }
        });
        list.appendChild(li);
    });
}

async function showConversation(sender_id) {
    const res = await fetch('get_messages.php?staff_id=' + sender_id);
    const j = await res.json();
    const conv = document.getElementById('conversation');
    conv.innerHTML = '';
    
    if (!j.success || !j.messages.length) {
        conv.innerHTML = '<p class="text-muted">No messages with this sender.</p>';
        return;
    }
    
    j.messages.forEach(m => {
        const div = document.createElement('div');
        div.className = 'conversation-msg ' + (m.sender_id == userId ? 'msg-self' : 'msg-sender');
        const time = new Date(m.created_at).toLocaleTimeString();
        div.innerHTML = `
            <small class="d-block text-muted">${time}</small>
            <p class="mb-1">${escapeHtml(m.message)}</p>
        `;
        conv.appendChild(div);
    });

    // Auto-mark as read
    const idsToMark = j.messages.filter(x => x.receiver_id == userId && x.is_read == 0).map(x => x.id);
    if (idsToMark.length) {
        const params = new URLSearchParams();
        idsToMark.forEach(i => params.append('ids[]', i));
        await fetch('mark_read.php', { method: 'POST', body: params });
        loadInbox();
    }
}

document.getElementById('markReadBtn').addEventListener('click', async () => {
    const checks = document.querySelectorAll('.msg-check:checked');
    if (!checks.length) {
        alert('Select messages first');
        return;
    }
    const ids = [];
    checks.forEach(c => ids.push(c.dataset.id));
    const params = new URLSearchParams();
    ids.forEach(i => params.append('ids[]', i));
    
    const res = await fetch('mark_read.php', { method: 'POST', body: params });
    const j = await res.json();
    if (j.success) {
        loadInbox();
        alert('Marked as read');
    }
});

document.getElementById('deleteBtn').addEventListener('click', async () => {
    alert('Delete feature coming soon');
});

function escapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

loadInbox();
setInterval(loadInbox, 10000); // refresh every 10 seconds
</script>
</body>
</html>
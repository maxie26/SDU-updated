<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Staff list will be loaded dynamically via `get_staff.php`
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messaging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .table-striped tbody tr:hover { background-color: #f8f9fa; }
        .btn-primary { background-color: #667eea; border: none; }
        .btn-primary:hover { background-color: #5568d3; }
        .badge { font-size: 0.9rem; }
        h2 { color: white; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-envelope"></i> Staff Messaging Center</h2>
        <a href="admin_dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Staff with Training Deficiencies</h5>
        </div>
        <div class="card-body">
            <div id="staffTableContainer">Loading staff...</div>
        </div>
    </div>
</div>

<!-- Composer Modal -->
<div class="modal fade" id="composerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="composerForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-envelope-open"></i> Message <strong id="composerName"></strong></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="receiver_id" id="composerReceiver" value="">
            <div id="deficiencyList" class="mb-3"></div>
            <div class="mb-3">
                <label class="form-label"><strong>Message</strong></label>
                <textarea name="message" id="composerMessage" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
            </div>
            <div id="composerAlert"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fas fa-history"></i> Message History with <strong id="historyName"></strong></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="historyConversation" style="max-height: 400px; overflow-y: auto;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Load staff dynamically and render table
async function loadStaffs() {
    const container = document.getElementById('staffTableContainer');
    container.innerHTML = 'Loading staff...';
    try {
        const res = await fetch('get_staff.php');
        const j = await res.json();
        if (!j.success) {
            container.innerHTML = '<div class="alert alert-danger">Failed to load staff</div>';
            return;
        }
        if (!j.staff || !j.staff.length) {
            container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No staff found.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Staff Name</th><th>Email</th><th>Deficiencies</th><th>Actions</th></tr></thead><tbody>';
        j.staff.forEach(s => {
            html += `<tr data-id="${s.id}" data-name="${escapeHtml(s.username)}">` +
                    `<td>${escapeHtml(s.username)}</td>` +
                    `<td>${escapeHtml(s.email || '')}</td>` +
                    `<td><span class="badge bg-warning text-dark">${parseInt(s.deficiency_count||0)}</span></td>` +
                    `<td>` +
                    `<button class="btn btn-primary btn-sm compose-btn" data-id="${s.id}" data-name="${escapeHtml(s.username)}"><i class="fas fa-pen"></i> Compose</button> ` +
                    `<button class="btn btn-outline-secondary btn-sm view-history-btn" data-id="${s.id}" data-name="${escapeHtml(s.username)}"><i class="fas fa-history"></i> History</button>` +
                    `</td></tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;

        // attach handlers via delegation
        container.querySelectorAll('.compose-btn').forEach(btn => btn.addEventListener('click', composeHandler));
        container.querySelectorAll('.view-history-btn').forEach(btn => btn.addEventListener('click', historyHandler));

    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger">Error loading staff.</div>';
    }
}

function composeHandler(e) {
    const btn = e.currentTarget;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    document.getElementById('composerName').textContent = name;
    document.getElementById('composerReceiver').value = id;
    document.getElementById('composerMessage').value = '';
    document.getElementById('composerAlert').innerHTML = '';

    // Load deficiencies
    fetch('get_deficiencies.php?staff_id=' + id).then(r => r.json()).then(j => {
        const list = document.getElementById('deficiencyList');
        list.innerHTML = '';
        if (j.success && j.deficiencies && j.deficiencies.length) {
            let html = '<div class="alert alert-warning"><strong>Incomplete Trainings:</strong><ul class="mb-0">';
            j.deficiencies.forEach(d => {
                html += '<li>' + (d.title || 'Untitled') + ' — <small>Status: ' + (d.status || 'Not Started').toUpperCase() + '</small></li>';
            });
            html += '</ul></div>';
            list.innerHTML = html;
        } else {
            list.innerHTML = '<div class="alert alert-info"><i class="fas fa-check-circle"></i> No deficiencies found.</div>';
        }
        var modal = new bootstrap.Modal(document.getElementById('composerModal'));
        modal.show();
    }).catch(()=>{
        document.getElementById('deficiencyList').innerHTML = '<div class="alert alert-danger">Failed to load deficiencies.</div>';
        var modal = new bootstrap.Modal(document.getElementById('composerModal'));
        modal.show();
    });
}

function historyHandler(e) {
    const btn = e.currentTarget;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    document.getElementById('historyName').textContent = name;
    const conv = document.getElementById('historyConversation');
    conv.innerHTML = '';

    fetch('get_messages.php?staff_id=' + id).then(r => r.json()).then(j => {
        if (!j.success || !j.messages.length) {
            conv.innerHTML = '<p class="text-muted">No messages with this staff.</p>';
        } else {
            j.messages.forEach(m => {
                const div = document.createElement('div');
                div.className = 'mb-3 p-2 border-bottom';
                const isSender = m.sender_id == <?php echo $_SESSION['user_id']; ?>;
                div.innerHTML = '<small class="text-muted d-block">' +
                    (isSender ? '<i class="fas fa-arrow-right text-primary"></i> To ' : '<i class="fas fa-arrow-left text-success"></i> From ') +
                    (m.sender_name || 'System') + ' — ' + m.created_at + '</small>' +
                    '<div class="mt-1">' + escapeHtml(m.message) + '</div>';
                conv.appendChild(div);
            });
        }
        var modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
    }).catch(()=>{
        conv.innerHTML = '<p class="text-danger">Failed to load messages.</p>';
        var modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
    });
}

// Composer form submission
document.getElementById('composerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const receiver = document.getElementById('composerReceiver').value;
    const message = document.getElementById('composerMessage').value.trim();
    
    if (!message) {
        document.getElementById('composerAlert').innerHTML = '<div class="alert alert-danger">Message cannot be empty.</div>';
        return;
    }
    
    const fd = new FormData();
    fd.append('receiver_id', receiver);
    fd.append('message', message);

    const res = await fetch('send_message.php', { method: 'POST', body: fd });
    const j = await res.json();
    const alertBox = document.getElementById('composerAlert');
    
    if (j.success) {
        alertBox.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Message sent successfully!</div>';
        document.getElementById('composerMessage').value = '';
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('composerModal')).hide();
        }, 1500);
    } else {
        alertBox.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (j.error || 'Failed to send') + '</div>';
    }
});

function escapeHtml(text) {
    try {
        if (text === null || text === undefined) return '(no message)';
        text = String(text);
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    } catch (e) {
        return '(unrenderable message)';
    }
}

// initialize
loadStaffs();
</script>
</body>
</html>
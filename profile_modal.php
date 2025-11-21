<?php
// Reusable Profile Modal Component
// Usage: Include this file and use the modal HTML structure
?>
<!-- Unified Profile Modal - Original UI Design -->
<style>
    #profileModal .modal-dialog {
        max-width: 1200px;
        margin: 1.75rem auto;
    }
    #profileModal .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    #profileModal .modal-header {
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-radius: 20px 20px 0 0;
    }
    #profileModal .modal-body {
        padding: 2rem;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
    #profileModal .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding: 1rem 2rem;
        background: #f8fafc;
        border-radius: 0 0 20px 20px;
    }
    @media (max-width: 991px) {
        #profileModal .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }
        #profileModal .modal-body {
            padding: 1.5rem;
            max-height: calc(100vh - 150px);
        }
        #profileModal .modal-header,
        #profileModal .modal-footer {
            padding: 1rem 1.5rem;
        }
    }
    @media (max-width: 576px) {
        #profileModal .modal-body {
            padding: 1rem;
        }
        #profileModal .modal-header,
        #profileModal .modal-footer {
            padding: 0.75rem 1rem;
        }
    }
</style>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="profileModalTitle" style="color: #1e293b;">Profile Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="profileModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="profileModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Unified Profile Modal Handler - Original UI Design
let profileModalInitialized = false;

function initProfileModal(mode = 'view') {
    const modal = document.getElementById('profileModal');
    const title = document.getElementById('profileModalTitle');
    const content = document.getElementById('profileModalContent');
    const footer = document.getElementById('profileModalFooter');
    
    if (!modal) return;
    
    // Only attach listener once
    if (!profileModalInitialized) {
        modal.addEventListener('show.bs.modal', function() {
            title.textContent = 'Profile Center';
            footer.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
            content.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
            
            // Load the original UI design
            fetch('view_profile_modal_api.php', { credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => { 
                    content.innerHTML = html; 
                })
                .catch(() => { 
                    content.innerHTML = '<div class="alert alert-danger">Failed to load profile</div>'; 
                });
        });
        profileModalInitialized = true;
    }
}
</script>


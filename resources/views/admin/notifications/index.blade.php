@extends('layouts.app')

@section('title', 'Notification Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Notification Management</h1>
        </div>
        <div>
            <button class="btn-header-white" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Add New Notification
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <!-- Notifications Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="notificationsList">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">Loading notifications...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Create Notification</h2>
        <form id="notificationForm">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="notifTitle" required>
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea id="notifMessage" required rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select id="notifType" required>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                    <option value="reminder">Reminder</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select id="notifPriority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Create & Send</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadNotifications();
});

async function loadNotifications() {
    const container = document.getElementById('notificationsList');
    container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Loading notifications...</td></tr>';
    
    const result = await API.get('/notifications');
    
    if (result.success) {
        const notifications = result.data.data?.data || result.data.data || [];
        displayNotifications(notifications);
    } else {
        container.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #dc3545;">Error loading notifications: ' + (result.error || 'Unknown error') + '</td></tr>';
    }
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    
    if (notifications.length === 0) {
        container.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No notifications found</td></tr>';
        return;
    }

        container.innerHTML = notifications.map(notif => {
        const typeBadge = `<span class="badge badge-${notif.type}">${notif.type}</span>`;
        const priorityBadge = `<span class="badge badge-priority-${notif.priority}">${notif.priority}</span>`;
        const statusBadge = notif.is_active 
            ? `<span class="badge badge-success">Active</span>` 
            : `<span class="badge badge-secondary">Inactive</span>`;
        const createdBy = notif.creator?.name || 'System';
        const createdAt = formatDateTime(notif.created_at);
        
        return `
            <tr class="table-row-clickable" onclick="window.location.href='{{ route('admin.notifications.show', '') }}/${notif.id}'">
                <td>${notif.id}</td>
                <td>${notif.title}</td>
                <td>${typeBadge}</td>
                <td>${priorityBadge}</td>
                <td>${createdBy}</td>
                <td>${createdAt}</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    }).join('');
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

window.showCreateModal = function() {
    document.getElementById('notificationForm').reset();
    document.getElementById('notificationModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('notificationModal').style.display = 'none';
};

// Bind form submit event
document.getElementById('notificationForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const data = {
        title: document.getElementById('notifTitle').value,
        message: document.getElementById('notifMessage').value,
        type: document.getElementById('notifType').value,
        priority: document.getElementById('notifPriority').value,
        target_audience: 'all'
    };

    const result = await API.post('/notifications', data);

    if (result.success) {
        // Send notification
        const sendResult = await API.post(`/notifications/${result.data.data.id}/send`, {});
        
        window.closeModal();
        loadNotifications();
        showToast('Notification created and sent successfully!', 'success');
    } else {
        showToast('Error creating notification: ' + (result.error || 'Unknown error'), 'error');
    }
});
</script>

<style>
/* Page Header Styling */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.page-header-content {
    padding: 10px 0;
}

.page-header-content h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.btn-header-white {
    background-color: #ffffff;
    color: #cb2d3e; 
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* Table Container */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f8f9fa;
}

.data-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #e9ecef;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    color: #495057;
}

.data-table tbody tr {
    transition: background 0.2s;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.table-row-clickable {
    cursor: pointer;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-error {
    background: #f8d7da;
    color: #721c24;
}

.badge-reminder {
    background: #e2e3e5;
    color: #383d41;
}

.badge-priority-low {
    background: #e2e3e5;
    color: #383d41;
}

.badge-priority-medium {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-priority-high {
    background: #fff3cd;
    color: #856404;
}

.badge-priority-urgent {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2c3e50;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-primary {
    background: #cb2d3e;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.btn-primary:hover {
    background: #a01a2a;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.btn-secondary:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }
}
</style>
@endsection

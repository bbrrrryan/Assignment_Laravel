@extends('layouts.app')

@section('title', 'Notifications - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Notification</h1>
        <button id="createNotificationBtn" class="btn-primary" onclick="showCreateModal()" style="display: none;">
            <i class="fas fa-plus"></i> Create Notification
        </button>
    </div>

    <hr class="notification-divider">

    <div id="notificationsList" class="notifications-container">
        <p>Loading notifications...</p>
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
                <label>Target Audience *</label>
                <select id="notifAudience" required>
                    <option value="all">All Users</option>
                    <option value="students">Students</option>
                    <option value="staff">Staff</option>
                    <option value="admins">Admins</option>
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
// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initNotifications();
});

function initNotifications() {
    // Update UI based on user role
    const createBtn = document.getElementById('createNotificationBtn');
    
    if (API.isAdmin()) {
        // Show create button for admin
        if (createBtn) {
            createBtn.style.display = 'block';
        }
    } else {
        // Hide create button for students
        if (createBtn) {
            createBtn.style.display = 'none';
        }
    }
    
    loadNotifications();
}

async function loadNotifications() {
    showLoading(document.getElementById('notificationsList'));
    
    // Load user's notifications (all users see their own notifications)
    const endpoint = '/notifications/user/my-notifications';

    const result = await API.get(endpoint);
    
    if (result.success) {
        const notifications = result.data.data?.data || result.data.data || [];
        displayNotifications(notifications);
    } else {
        showError(document.getElementById('notificationsList'), result.error || 'Failed to load notifications');
        console.error('Error loading notifications:', result);
    }
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');

    if (notifications.length === 0) {
        container.innerHTML = '<p>No notifications found</p>';
        return;
    }

    container.innerHTML = `
        <table class="notification-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Sender</th>
                    <th>Date</th>
                    <th>Mark as Unread</th>
                </tr>
            </thead>
            <tbody>
                ${notifications.map(notif => {
                    const isRead = notif.pivot?.is_read || false;
                    const sender = notif.creator?.name || 'System';
                    const date = formatDateTime(notif.created_at);
                    return `
                    <tr class="notification-row ${isRead ? 'read' : 'unread'}" onclick="handleRowClick(event, ${notif.id}, ${isRead})">
                        <td class="notification-title ${isRead ? 'read-text' : ''}">
                            <i class="fas fa-${getNotificationIcon(notif.type)} notification-type-icon type-${notif.type}"></i>
                            ${notif.title}
                        </td>
                        <td class="notification-sender ${isRead ? 'read-text' : ''}">${sender}</td>
                        <td class="notification-date ${isRead ? 'read-text' : ''}">${date}</td>
                        <td class="notification-actions" onclick="event.stopPropagation()">
                            ${isRead ? `<button class="btn-unread-icon" onclick="markAsUnread(${notif.id}, event)" title="Mark as Unread">
                                <i class="fas fa-envelope-open"></i>
                            </button>` : ''}
                        </td>
                    </tr>
                `;
                }).join('')}
            </tbody>
        </table>
    `;
}

function getNotificationIcon(type) {
    const icons = {
        'info': 'info-circle',
        'warning': 'exclamation-triangle',
        'success': 'check-circle',
        'error': 'times-circle',
        'reminder': 'bell'
    };
    return icons[type] || 'bell';
}

// Make functions global
window.showCreateModal = function() {
    document.getElementById('notificationForm').reset();
    document.getElementById('notificationModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('notificationModal').style.display = 'none';
};

// Handle row click - mark as read and navigate
async function handleRowClick(event, id, isRead) {
    // If not read, mark as read first
    if (!isRead) {
        try {
            await API.put(`/notifications/${id}/read`, {});
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    // Navigate to notification page
    window.location.href = `/notifications/${id}`;
}

window.markAsUnread = async function(id, event) {
    if (event) {
        event.stopPropagation();
    }
    
    const result = await API.put(`/notifications/${id}/unread`, {});
    
    if (result.success) {
        loadNotifications();
        showToast('Notification marked as unread', 'success');
    } else {
        showToast('Error marking notification as unread: ' + (result.error || 'Unknown error'), 'error');
    }
};

// Bind form submit event
(function() {
    const notificationForm = document.getElementById('notificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const data = {
                title: document.getElementById('notifTitle').value,
                message: document.getElementById('notifMessage').value,
                type: document.getElementById('notifType').value,
                target_audience: document.getElementById('notifAudience').value
            };

            const result = await API.post('/notifications', data);

            if (result.success) {
                // Send notification
                const sendResult = await API.post(`/notifications/${result.data.data.id}/send`, {});
                
                window.closeModal();
                loadNotifications();
                alert('Notification created and sent!');
            } else {
                alert(result.error || 'Error creating notification');
            }
        });
    }
})();
</script>

<style>
.notification-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.notification-table thead {
    background: #f8f9fa;
}

.notification-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #e0e0e0;
}

.notification-table th:first-child {
    width: 50%;
    text-align: left;
}

.notification-table th:nth-child(2) {
    width: 20%;
    text-align: left;
}

.notification-table th:nth-child(3) {
    width: 20%;
    text-align: left;
}

.notification-table th:last-child {
    width: 10%;
    text-align: center;
}

.notification-row {
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid #f0f0f0;
}

.notification-row:hover {
    background: #f8f9fa;
}

.notification-row.unread {
    background: #fff;
}

.notification-row.read {
    background: #fafafa;
    opacity: 0.7;
}

.notification-row.read:hover {
    opacity: 0.9;
}

.notification-table td {
    padding: 12px 16px;
    vertical-align: middle;
}

.notification-title {
    font-weight: 500;
    color: #2c3e50;
}

.notification-title.read-text {
    color: #999;
}

.notification-type-icon {
    margin-right: 8px;
    font-size: 14px;
}

.notification-type-icon.type-info {
    color: #0c5460;
}

.notification-type-icon.type-warning {
    color: #856404;
}

.notification-type-icon.type-success {
    color: #155724;
}

.notification-type-icon.type-error {
    color: #721c24;
}

.notification-type-icon.type-reminder {
    color: #383d41;
}

.notification-sender {
    color: #666;
    text-align: left;
}

.notification-sender.read-text {
    color: #999;
}

.notification-date {
    color: #666;
    text-align: left;
    font-size: 0.9em;
}

.notification-date.read-text {
    color: #999;
}

.notification-actions {
    text-align: center;
}

.btn-unread-icon {
    padding: 8px;
    font-size: 14px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    background: transparent;
    color: #999;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.btn-unread-icon:hover {
    background: #f0f0f0;
    color: #666;
}

.notification-divider {
    border: none;
    border-top: 2px solid #e0e0e0;
    margin: 20px 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
}

.page-header h1 {
    margin: 0;
    color: #2c3e50;
    font-size: 2em;
}
</style>
@endsection


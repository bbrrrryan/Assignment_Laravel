@extends('layouts.app')

@section('title', 'Notifications - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Notification</h1>
    </div>

    <hr class="notification-divider">

    <div id="notificationsList" class="notifications-container">
        <p>Loading notifications...</p>
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
    loadNotifications();
}

async function loadNotifications() {
    const container = document.getElementById('notificationsList');
    container.innerHTML = '<p>Loading notifications...</p>';
    
    // Admin sees all notifications, regular users see only their own
    const endpoint = API.isAdmin() ? '/notifications' : '/notifications/user/my-notifications';

    try {
        const result = await API.get(endpoint);
        
        console.log('Notification API Response:', result);
        
        if (result.success) {
            // Handle paginated response: result.data.data is the pagination object
            // result.data.data.data is the actual array of notifications
            let notifications = [];
            
            if (result.data && result.data.data) {
                // Check if it's a paginated response
                if (Array.isArray(result.data.data)) {
                    // Direct array response
                    notifications = result.data.data;
                } else if (result.data.data.data && Array.isArray(result.data.data.data)) {
                    // Paginated response
                    notifications = result.data.data.data;
                } else if (result.data.data.data && Array.isArray(result.data.data)) {
                    // Alternative structure
                    notifications = result.data.data;
                }
            }
            
            console.log('Parsed notifications:', notifications);
            console.log('Notifications count:', notifications.length);
            
            displayNotifications(notifications);
        } else {
            container.innerHTML = `<p style="color: red;">Error: ${result.error || 'Failed to load notifications'}</p>`;
            console.error('Error loading notifications:', result);
        }
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error loading notifications: ${error.message}</p>`;
        console.error('Exception loading notifications:', error);
    }
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');

    if (notifications.length === 0) {
        container.innerHTML = '<p>No notifications found</p>';
        return;
    }

    const isAdmin = API.isAdmin();
    
    container.innerHTML = `
        <table class="notification-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Sender</th>
                    ${isAdmin ? '<th>Target Audience</th>' : ''}
                    <th>Date</th>
                    ${!isAdmin ? '<th>Mark as Unread</th>' : ''}
                </tr>
            </thead>
            <tbody>
                ${notifications.map(notif => {
                    // Admin viewing all notifications may not have pivot data
                    // Regular users viewing their notifications will have pivot data
                    const isRead = notif.pivot?.is_read || false;
                    const sender = notif.creator?.name || 'System';
                    const date = formatDateTime(notif.created_at);
                    const isAdmin = API.isAdmin();
                    const hasPivot = notif.pivot !== undefined && notif.pivot !== null;
                    
                    return `
                    <tr class="notification-row ${isRead ? 'read' : 'unread'}" onclick="handleRowClick(event, ${notif.id}, ${isRead})">
                        <td class="notification-title ${isRead ? 'read-text' : ''}">
                            <i class="fas fa-${getNotificationIcon(notif.type)} notification-type-icon type-${notif.type}"></i>
                            ${notif.title}
                        </td>
                        <td class="notification-sender ${isRead ? 'read-text' : ''}">${sender}</td>
                        ${isAdmin ? `<td class="notification-audience ${isRead ? 'read-text' : ''}">${notif.target_audience ? notif.target_audience.charAt(0).toUpperCase() + notif.target_audience.slice(1) : 'All'}</td>` : ''}
                        <td class="notification-date ${isRead ? 'read-text' : ''}">${date}</td>
                        ${!isAdmin ? `<td class="notification-actions" onclick="event.stopPropagation()">
                            ${hasPivot && isRead ? `<button class="btn-unread-icon" onclick="markAsUnread(${notif.id}, event)" title="Mark as Unread">
                                <i class="fas fa-envelope-open"></i>
                            </button>` : ''}
                        </td>` : ''}
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

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Handle row click - mark as read and navigate
async function handleRowClick(event, id, isRead) {
    // Only mark as read if user has this notification (has pivot data)
    // Admin viewing all notifications may not have pivot, so skip marking as read
    const isAdmin = API.isAdmin();
    
    // For regular users, mark as read if not already read
    if (!isAdmin && !isRead) {
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


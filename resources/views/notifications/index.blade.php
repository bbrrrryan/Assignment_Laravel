@extends('layouts.app')

@section('title', 'Notifications - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 id="notificationsTitle">Notifications</h1>
        <button id="createNotificationBtn" class="btn-primary" onclick="showCreateModal()" style="display: none;">
            <i class="fas fa-plus"></i> Create Notification
        </button>
    </div>

    <div class="tabs" id="notificationTabs">
        <button class="tab-btn active" onclick="showTab('all')">All</button>
        <button class="tab-btn" onclick="showTab('unread')">Unread</button>
        <button class="tab-btn" onclick="showTab('my')">My Notifications</button>
    </div>

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

let currentTab = 'all';

function initNotifications() {
    // Update UI based on user role
    const titleElement = document.getElementById('notificationsTitle');
    const createBtn = document.getElementById('createNotificationBtn');
    const tabsContainer = document.getElementById('notificationTabs');
    
    if (API.isAdmin()) {
        if (titleElement) {
            titleElement.textContent = 'Notifications';
        }
        // Show create button and all tabs for admin
        if (createBtn) {
            createBtn.style.display = 'block';
        }
        if (tabsContainer) {
            tabsContainer.style.display = 'flex';
        }
    } else {
        if (titleElement) {
            titleElement.textContent = 'My Notifications';
        }
        // Hide create button for students
        if (createBtn) {
            createBtn.style.display = 'none';
        }
        // Hide "All" tab for students, only show "My Notifications"
        if (tabsContainer) {
            const allTab = tabsContainer.querySelector('button[onclick="showTab(\'all\')"]');
            const unreadTab = tabsContainer.querySelector('button[onclick="showTab(\'unread\')"]');
            if (allTab) allTab.style.display = 'none';
            if (unreadTab) unreadTab.style.display = 'none';
            // Set default tab to 'my' for students
            currentTab = 'my';
            const myTab = tabsContainer.querySelector('button[onclick="showTab(\'my\')"]');
            if (myTab) {
                myTab.classList.add('active');
            }
        }
    }
    
    loadNotifications();
}

async function loadNotifications() {
    showLoading(document.getElementById('notificationsList'));
    
    // Use appropriate endpoint based on user role and tab
    let endpoint;
    if (API.isAdmin()) {
        if (currentTab === 'my') {
            endpoint = '/notifications/user/my-notifications';
        } else {
            endpoint = '/notifications';
        }
    } else {
        // Students can only access their own notifications
        endpoint = '/notifications/user/my-notifications';
    }

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
    
    if (currentTab === 'unread') {
        notifications = notifications.filter(n => !n.pivot?.is_read);
    }

    if (notifications.length === 0) {
        container.innerHTML = '<p>No notifications found</p>';
        return;
    }

    container.innerHTML = notifications.map(notif => `
        <div class="notification-card ${notif.pivot?.is_read ? '' : 'unread'}">
            <div class="notification-icon">
                <i class="fas fa-${getNotificationIcon(notif.type)}"></i>
            </div>
            <div class="notification-content">
                <h3>${notif.title}</h3>
                <p>${notif.message}</p>
                <div class="notification-meta">
                    <span class="notification-type type-${notif.type}">${notif.type}</span>
                    <span class="notification-time">${formatDateTime(notif.created_at)}</span>
                </div>
            </div>
            <div class="notification-actions">
                ${!notif.pivot?.is_read ? `<button class="btn-sm" onclick="markAsRead(${notif.id})">Mark Read</button>` : ''}
                <button class="btn-sm" onclick="viewNotification(${notif.id})">View</button>
            </div>
        </div>
    `).join('');
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
window.showTab = function(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    loadNotifications();
};

window.showCreateModal = function() {
    document.getElementById('notificationForm').reset();
    document.getElementById('notificationModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('notificationModal').style.display = 'none';
};

window.markAsRead = async function(id) {
    const result = await API.put(`/notifications/${id}/read`, {});
    
    if (result.success) {
        loadNotifications();
    } else {
        alert(result.error || 'Error marking notification as read');
    }
};

window.viewNotification = function(id) {
    window.location.href = `/notifications/${id}`;
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
@endsection


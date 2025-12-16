// Admin Notification Management
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadNotifications(1);
    initSearch();
    
    // Form submission handler
    const form = document.getElementById('notificationForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
});

let currentPage = 1;
let searchTimeout;

function debounce(func, wait) {
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(searchTimeout);
            func(...args);
        };
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(later, wait);
    };
}

function initSearch() {
    const searchInput = document.getElementById('notificationSearchInput');
    const searchClearBtn = document.getElementById('notificationSearchClear');
    
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            loadNotifications(1);
        }, 300);
        
        searchInput.addEventListener('input', function() {
            if (searchClearBtn) {
                if (this.value.trim()) {
                    searchClearBtn.style.display = 'flex';
                } else {
                    searchClearBtn.style.display = 'none';
                }
            }
            debouncedSearch();
        });
    }
}

function clearNotificationSearch() {
    const searchInput = document.getElementById('notificationSearchInput');
    const searchClearBtn = document.getElementById('notificationSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        loadNotifications(1);
    }
}

async function loadNotifications(page = 1) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align: center; padding: 40px;">Loading notifications...</p>';
    currentPage = page;
    
    try {
        const searchInput = document.getElementById('notificationSearchInput');
        const typeFilter = document.getElementById('notificationTypeFilter');
        const priorityFilter = document.getElementById('notificationPriorityFilter');
        
        const params = new URLSearchParams();
        params.append('per_page', 15);
        params.append('page', page);
        
        if (searchInput && searchInput.value.trim()) {
            params.append('search', searchInput.value.trim());
        }
        if (typeFilter && typeFilter.value) {
            params.append('type', typeFilter.value);
        }
        if (priorityFilter && priorityFilter.value) {
            params.append('priority', priorityFilter.value);
        }
        
        const result = await API.get(`/notifications?${params.toString()}`);
        
        if (result && result.success !== false && result.data) {
            // API.get wraps response: result.data = { status, message, data: paginator }
            const paginator = result.data.data || result.data;
            const notifications = Array.isArray(paginator.data) ? paginator.data : [];
            const pagination = paginator;
            
            if (!notifications || notifications.length === 0) {
                container.innerHTML = '<p style="text-align: center; padding: 40px;">No notifications found.</p>';
                return;
            }
            
            displayNotifications(notifications, pagination);
        } else {
            container.innerHTML = '<p style="text-align: center; padding: 40px; color: #dc3545;">Error loading notifications.</p>';
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        container.innerHTML = '<p style="text-align: center; padding: 40px; color: #dc3545;">Error loading notifications: ' + error.message + '</p>';
    }
}

function displayNotifications(notifications, pagination) {
    const container = document.getElementById('notificationsList');
    
    let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Target Audience</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    notifications.forEach(notification => {
        const typeBadge = getTypeBadge(notification.type);
        const priorityBadge = getPriorityBadge(notification.priority);
        const statusBadge = notification.is_active 
            ? '<span class="badge badge-success">Active</span>' 
            : '<span class="badge badge-secondary">Inactive</span>';
        const createdAt = formatDateTime(notification.created_at);
        const creatorName = notification.creator ? notification.creator.name : 'System';
        
        html += `
            <tr>
                <td>#${notification.id}</td>
                <td>${escapeHtml(notification.title)}</td>
                <td>${typeBadge}</td>
                <td>${priorityBadge}</td>
                <td>${notification.target_audience || 'all'}</td>
                <td>${escapeHtml(creatorName)}</td>
                <td>${createdAt}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-action btn-view" onclick="viewNotification(${notification.id})" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action btn-edit" onclick="editNotification(${notification.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteNotification(${notification.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    // Add pagination
    if (pagination && pagination.last_page > 1) {
        html += `<div class="pagination">`;
        if (pagination.current_page > 1) {
            html += `<button class="btn-pagination" onclick="loadNotifications(${pagination.current_page - 1})">Previous</button>`;
        }
        html += `<span class="pagination-info">Page ${pagination.current_page} of ${pagination.last_page}</span>`;
        if (pagination.current_page < pagination.last_page) {
            html += `<button class="btn-pagination" onclick="loadNotifications(${pagination.current_page + 1})">Next</button>`;
        }
        html += `</div>`;
    }
    
    container.innerHTML = html;
}

function getTypeBadge(type) {
    const badges = {
        'info': '<span class="badge badge-info">Info</span>',
        'warning': '<span class="badge badge-warning">Warning</span>',
        'success': '<span class="badge badge-success">Success</span>',
        'error': '<span class="badge badge-danger">Error</span>',
        'reminder': '<span class="badge badge-secondary">Reminder</span>'
    };
    return badges[type] || '<span class="badge">' + type + '</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge badge-secondary">Low</span>',
        'medium': '<span class="badge badge-info">Medium</span>',
        'high': '<span class="badge badge-warning">High</span>',
        'urgent': '<span class="badge badge-danger">Urgent</span>'
    };
    return badges[priority] || '<span class="badge">' + (priority || 'Medium') + '</span>';
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Notification';
    document.getElementById('notificationForm').reset();
    document.getElementById('notificationId').value = '';
    document.getElementById('submitBtn').textContent = 'Create Notification';
    document.getElementById('targetUsersGroup').style.display = 'none';
    document.getElementById('notificationModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('notificationModal').style.display = 'none';
}

function toggleTargetUsers() {
    const targetAudience = document.getElementById('notificationTargetAudience').value;
    const targetUsersGroup = document.getElementById('targetUsersGroup');
    if (targetAudience === 'specific') {
        targetUsersGroup.style.display = 'block';
    } else {
        targetUsersGroup.style.display = 'none';
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = {
        title: document.getElementById('notificationTitle').value,
        message: document.getElementById('notificationMessage').value,
        type: document.getElementById('notificationType').value,
        priority: document.getElementById('notificationPriority').value,
        target_audience: document.getElementById('notificationTargetAudience').value,
        is_active: document.getElementById('notificationIsActive').checked
    };
    
    const targetUserIds = document.getElementById('targetUserIds').value;
    if (formData.target_audience === 'specific' && targetUserIds) {
        formData.target_user_ids = targetUserIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
    }
    
    const notificationId = document.getElementById('notificationId').value;
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        let result;
        if (notificationId) {
            result = await API.put(`/notifications/${notificationId}`, formData);
        } else {
            result = await API.post('/notifications', formData);
        }
        
        if (result && result.success !== false) {
            alert('Notification saved successfully!');
            closeModal();
            loadNotifications(currentPage);
        } else {
            alert('Error saving notification: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving notification:', error);
        alert('Error saving notification: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = notificationId ? 'Update Notification' : 'Create Notification';
    }
}

async function viewNotification(id) {
    window.location.href = `/notifications/${id}`;
}

async function editNotification(id) {
    try {
        const result = await API.get(`/notifications/${id}`);
        if (result && result.success !== false && result.data) {
            const notification = result.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Notification';
            document.getElementById('notificationId').value = notification.id;
            document.getElementById('notificationTitle').value = notification.title || '';
            document.getElementById('notificationMessage').value = notification.message || '';
            document.getElementById('notificationType').value = notification.type || 'info';
            document.getElementById('notificationPriority').value = notification.priority || 'medium';
            document.getElementById('notificationTargetAudience').value = notification.target_audience || 'all';
            document.getElementById('notificationIsActive').checked = notification.is_active !== false;
            document.getElementById('submitBtn').textContent = 'Update Notification';
            
            if (notification.target_user_ids && Array.isArray(notification.target_user_ids)) {
                document.getElementById('targetUserIds').value = notification.target_user_ids.join(',');
            }
            
            toggleTargetUsers();
            document.getElementById('notificationModal').style.display = 'flex';
        }
    } catch (error) {
        console.error('Error loading notification:', error);
        alert('Error loading notification: ' + error.message);
    }
}

async function deleteNotification(id) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }
    
    try {
        const result = await API.delete(`/notifications/${id}`);
        if (result && result.success !== false) {
            alert('Notification deleted successfully!');
            loadNotifications(currentPage);
        } else {
            alert('Error deleting notification: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
        alert('Error deleting notification: ' + error.message);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('notificationModal');
    if (event.target === modal) {
        closeModal();
    }
}


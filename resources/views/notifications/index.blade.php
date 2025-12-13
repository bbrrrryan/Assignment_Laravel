@extends('layouts.app')

@section('title', 'Notifications - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 id="pageTitle">Announcements & Notifications</h1>
    </div>

    <hr class="notification-divider">

    <div id="notificationsList" class="notifications-container">
        <p>Loading...</p>
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
    container.innerHTML = '<p>Loading...</p>';
    
    const isAdmin = API.isAdmin();
    
    try {
        let items = [];
        
        if (isAdmin) {
            // For admin: show pending bookings
            document.getElementById('pageTitle').textContent = 'Pending Bookings';
            
            const bookingsResult = await API.get('/bookings/pending?limit=100');
            console.log('Bookings API Response:', bookingsResult);
            
            if (bookingsResult.success && bookingsResult.data && bookingsResult.data.bookings) {
                const bookings = bookingsResult.data.bookings;
                items = bookings.map(booking => ({
                    id: booking.id,
                    type: 'booking',
                    title: `Booking Request - ${booking.facility_name}`,
                    content: `Booking #${booking.booking_number} from ${booking.user_name} for ${booking.booking_date} ${booking.start_time} - ${booking.end_time}`,
                    created_at: booking.created_at,
                    booking: booking,
                }));
            }
        } else {
            // For regular users: show announcements and notifications
            document.getElementById('pageTitle').textContent = 'Announcements & Notifications';
            
            // Get all items (both read and unread) for the full page view
            // Note: only_unread defaults to false, so we don't need to pass it
            const result = await API.get('/notifications/user/unread-items?limit=100');
            console.log('Unread items API Response (full):', result);
            console.log('API Response structure:', JSON.stringify(result, null, 2));
            
            if (result && result.success) {
                // Check multiple possible response structures
                if (result.data && Array.isArray(result.data.items)) {
                    items = result.data.items;
                    console.log('Items found in result.data.items:', items.length);
                } else if (result.data && result.data.data && Array.isArray(result.data.data.items)) {
                    items = result.data.data.items;
                    console.log('Items found in result.data.data.items:', items.length);
                } else {
                    console.error('Unexpected API response structure:', result);
                    console.error('result.data:', result.data);
                    console.error('result.data.items:', result.data?.items);
                }
                
                // Log debug info if available
                if (result.data && result.data.debug) {
                    console.log('Debug info:', result.data.debug);
                }
            } else {
                console.error('API call failed or returned unsuccessful result:', result);
            }
        }
        
        console.log('Items to display:', items.length);
        console.log('Items array:', items);
        
        if (items.length === 0) {
            console.warn('No items found! This might be a problem.');
        }
        
        displayNotifications(items, isAdmin);
        
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error loading data: ${error.message}</p>`;
        console.error('Exception loading data:', error);
    }
}

function displayNotifications(items, isAdmin) {
    const container = document.getElementById('notificationsList');

    if (items.length === 0) {
        container.innerHTML = '<p>No items found</p>';
        return;
    }
    
    if (isAdmin) {
        // Display bookings for admin
        container.innerHTML = `
            <table class="notification-table">
                <thead>
                    <tr>
                        <th>Booking Number</th>
                        <th>Facility</th>
                        <th>User</th>
                        <th>Date & Time</th>
                        <th>Purpose</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${items.map(item => {
                        const booking = item.booking;
                        return `
                        <tr class="notification-row unread">
                            <td class="notification-title">${booking.booking_number}</td>
                            <td>${booking.facility_name}</td>
                            <td>${booking.user_name}</td>
                            <td>${booking.booking_date} ${booking.start_time} - ${booking.end_time}</td>
                            <td>${booking.purpose ? (booking.purpose.length > 50 ? booking.purpose.substring(0, 50) + '...' : booking.purpose) : '-'}</td>
                            <td class="notification-actions">
                                <button class="btn-approve-small" onclick="approveBookingFromPage(${booking.id}, event)" title="Approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn-reject-small" onclick="rejectBookingFromPage(${booking.id}, event)" title="Reject">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                <a href="/bookings/${booking.id}" class="btn-view-small">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `;
                    }).join('')}
                </tbody>
            </table>
        `;
    } else {
        // Display announcements and notifications for regular users
        container.innerHTML = `
            <table class="notification-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Sender</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${items.map(item => {
                        const icon = item.type === 'announcement' ? 'bullhorn' : 'bell';
                        const typeLabel = item.type === 'announcement' ? 'Announcement' : 'Notification';
                        const url = item.type === 'announcement' ? `/announcements/${item.id}` : `/notifications/${item.id}`;
                        const isRead = item.is_read === true || item.is_read === 1;
                        const sender = item.creator || 'System';
                        const date = formatDateTime(item.created_at || item.pivot_created_at);
                        const hasPivot = item.is_read !== undefined;
                        
                        return `
                        <tr class="notification-row ${isRead ? 'read' : 'unread'}" onclick="handleRowClick(event, '${item.type}', ${item.id}, ${isRead}, '${url}')">
                            <td class="notification-type-cell">
                                <i class="fas fa-${icon} notification-type-icon type-${item.type}"></i>
                                ${typeLabel}
                            </td>
                            <td class="notification-title ${isRead ? 'read-text' : ''}">${item.title}</td>
                            <td class="notification-sender ${isRead ? 'read-text' : ''}">${sender}</td>
                            <td class="notification-date ${isRead ? 'read-text' : ''}">${date}</td>
                            <td class="notification-actions" onclick="event.stopPropagation()">
                                ${hasPivot && isRead ? `<button class="btn-unread-icon" onclick="markAsUnread('${item.type}', ${item.id}, event)" title="Mark as Unread">
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
async function handleRowClick(event, type, id, isRead, url) {
    if (event) {
        event.stopPropagation();
    }
    
    // For regular users, mark as read if not already read
    if (!isRead) {
        try {
            if (type === 'announcement') {
                await API.put(`/announcements/${id}/read`, {});
            } else if (type === 'notification') {
                await API.put(`/notifications/${id}/read`, {});
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }
    
    // Navigate to detail page
    window.location.href = url;
}

window.markAsUnread = async function(type, id, event) {
    if (event) {
        event.stopPropagation();
    }
    
    try {
        let result;
        if (type === 'announcement') {
            result = await API.put(`/announcements/${id}/unread`, {});
        } else {
            result = await API.put(`/notifications/${id}/unread`, {});
        }
        
        if (result && result.success !== false) {
            loadNotifications();
            const typeLabel = type === 'announcement' ? 'Announcement' : 'Notification';
            showToast(`${typeLabel} marked as unread`, 'success');
        } else {
            showToast('Error: ' + (result?.message || result?.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error marking as unread:', error);
        showToast('Error marking as unread: ' + (error.message || 'Unknown error'), 'error');
    }
};

// Admin functions for booking approval/rejection
window.approveBookingFromPage = async function(bookingId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    if (!confirm('Are you sure you want to approve this booking?')) {
        return;
    }
    
    try {
        const result = await API.put(`/bookings/${bookingId}/approve`, {});
        if (result.success) {
            showToast('Booking approved successfully', 'success');
            loadNotifications();
        } else {
            showToast('Error: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error approving booking:', error);
        showToast('Error approving booking', 'error');
    }
};

window.rejectBookingFromPage = async function(bookingId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    const reason = prompt('Please enter rejection reason:');
    if (!reason || reason.trim() === '') {
        return;
    }
    
    try {
        const result = await API.put(`/bookings/${bookingId}/reject`, { reason: reason.trim() });
        if (result.success) {
            showToast('Booking rejected successfully', 'success');
            loadNotifications();
        } else {
            showToast('Error: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (error) {
        console.error('Error rejecting booking:', error);
        showToast('Error rejecting booking', 'error');
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

.notification-type-cell {
    font-weight: 500;
    color: #666;
}

.notification-type-icon.type-announcement {
    color: #007bff;
}

.btn-approve-small, .btn-reject-small, .btn-view-small {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    margin: 0 3px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-approve-small {
    background: #28a745;
    color: white;
}

.btn-approve-small:hover {
    background: #218838;
}

.btn-reject-small {
    background: #dc3545;
    color: white;
}

.btn-reject-small:hover {
    background: #c82333;
}

.btn-view-small {
    background: #17a2b8;
    color: white;
}

.btn-view-small:hover {
    background: #138496;
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


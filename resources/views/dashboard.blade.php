@extends('layouts.app')

@section('title', 'Dashboard - TARUMT FMS')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <span id="userName"></span>!</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-info">
                <h3 id="facilitiesCount">-</h3>
                <p>Total Facilities</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3 id="bookingsCount">-</h3>
                <p>Active Bookings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-info">
                <h3 id="notificationsCount">-</h3>
                <p>Unread Notifications</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3 id="loyaltyPoints">-</h3>
                <p>Loyalty Points</p>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="dashboard-section">
            <h2>Recent Bookings</h2>
            <div id="recentBookings" class="table-container">
                <p>Loading...</p>
            </div>
        </div>

        <div class="dashboard-section">
            <h2>Recent Notifications</h2>
            <div id="recentNotifications" class="notifications-list">
                <p>Loading...</p>
            </div>
        </div>
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

    // Check authentication
    if (!API.requireAuth()) {
        // Redirect will happen in requireAuth
        return;
    }

    // Set user name
    const user = API.getUser();
    if (user) {
        document.getElementById('userName').textContent = user.name || 'User';
    }

    // Load dashboard data
    loadDashboard();
});

// Fetch dashboard data
async function loadDashboard() {
    try {
        // Load facilities count
        const facilitiesResult = await API.get('/facilities');
        if (facilitiesResult.success) {
            const facilities = facilitiesResult.data.data?.data || facilitiesResult.data.data || [];
            document.getElementById('facilitiesCount').textContent = facilities.length;
        } else {
            console.error('Error loading facilities:', facilitiesResult);
        }

        // Load bookings count - use appropriate endpoint based on user role
        const bookingsEndpoint = API.isAdmin() ? '/bookings' : '/bookings/user/my-bookings';
        const bookingsResult = await API.get(bookingsEndpoint);
        if (bookingsResult.success) {
            const bookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
            const activeBookings = bookings.filter(b => b.status === 'approved' || b.status === 'pending');
            document.getElementById('bookingsCount').textContent = activeBookings.length;
        } else {
            console.error('Error loading bookings:', bookingsResult);
            // Set default value if error
            document.getElementById('bookingsCount').textContent = '0';
        }

        // Load notifications
        const notifResult = await API.get('/notifications/user/my-notifications');
        if (notifResult.success) {
            const notifications = notifResult.data.data?.data || notifResult.data.data || [];
            const unread = notifications.filter(n => !n.pivot?.is_read);
            document.getElementById('notificationsCount').textContent = unread.length;
            displayRecentNotifications(notifications.slice(0, 5));
        }

        // Load loyalty points
        const pointsResult = await API.get('/loyalty/points');
        if (pointsResult.success) {
            document.getElementById('loyaltyPoints').textContent = pointsResult.data.total_points || 0;
        }

        // Load recent bookings
        const myBookingsResult = await API.get('/bookings/user/my-bookings');
        if (myBookingsResult.success) {
            const bookings = myBookingsResult.data.data || [];
            displayRecentBookings(bookings.slice(0, 5));
        }

    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function displayRecentBookings(bookings) {
    const container = document.getElementById('recentBookings');
    if (bookings.length === 0) {
        container.innerHTML = '<p>No bookings found</p>';
        return;
    }

    const html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${bookings.slice(0, 5).map(booking => `
                    <tr>
                        <td>${booking.facility?.name || 'N/A'}</td>
                        <td>${formatDate(booking.booking_date)}</td>
                        <td>${new Date(booking.start_time).toLocaleTimeString()}</td>
                        <td><span class="status-badge status-${booking.status}">${booking.status}</span></td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    container.innerHTML = html;
}

function displayRecentNotifications(notifications) {
    const container = document.getElementById('recentNotifications');
    if (notifications.length === 0) {
        container.innerHTML = '<p>No notifications</p>';
        return;
    }

    const html = notifications.slice(0, 5).map(notif => `
        <div class="notification-item">
            <div class="notification-icon">
                <i class="fas fa-${getNotificationIcon(notif.type)}"></i>
            </div>
            <div class="notification-content">
                <h4>${notif.title}</h4>
                <p>${notif.message}</p>
                <span class="notification-time">${formatDateTime(notif.created_at)}</span>
            </div>
        </div>
    `).join('');
    container.innerHTML = html;
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
</script>
@endsection


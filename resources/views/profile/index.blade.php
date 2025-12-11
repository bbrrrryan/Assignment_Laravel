@extends('layouts.app')

@section('title', 'My Profile - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>My Profile</h1>
    </div>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2 id="profileName">Loading...</h2>
                <p id="profileEmail">Loading...</p>
                <p id="profileRole" class="role-badge">Loading...</p>
            </div>

            <div class="profile-content">
                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Name</label>
                            <input type="text" id="profileNameInput" class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <input type="email" id="profileEmailInput" class="form-control" readonly>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <input type="text" id="profilePhoneInput" class="form-control">
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <textarea id="profileAddressInput" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="info-item">
                            <label>Role</label>
                            <input type="text" id="profileRoleInput" class="form-control" readonly>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <input type="text" id="profileStatusInput" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Change Password</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>New Password</label>
                            <input type="password" id="newPasswordInput" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="info-item">
                            <label>Confirm New Password</label>
                            <input type="password" id="confirmPasswordInput" class="form-control" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-primary" onclick="updateProfile()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button class="btn-secondary" onclick="loadProfile()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Activity History Section -->
        <div class="profile-card" style="margin-top: 20px;">
            <div class="profile-content">
                <div class="profile-section">
                    <h3>Account Activity History</h3>
                    <div id="activityLogs" class="activity-logs">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i> Loading activity history...
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    loadProfile();
    loadActivityLogs();
});

async function loadProfile() {
    const result = await API.get('/me');

    if (result.success) {
        const user = result.data.user;
        displayProfile(user);
    } else {
        alert('Error loading profile: ' + (result.error || 'Unknown error'));
    }
}

function displayProfile(user) {
    // Display header info
    document.getElementById('profileName').textContent = user.name || 'N/A';
    document.getElementById('profileEmail').textContent = user.email || 'N/A';
    
    const roleName = user.role ? (typeof user.role === 'object' ? user.role.name : user.role) : 'N/A';
    document.getElementById('profileRole').textContent = roleName.charAt(0).toUpperCase() + roleName.slice(1);
    document.getElementById('profileRole').className = 'role-badge role-' + roleName.toLowerCase();

    // Fill form fields
    document.getElementById('profileNameInput').value = user.name || '';
    document.getElementById('profileEmailInput').value = user.email || '';
    document.getElementById('profilePhoneInput').value = user.phone_number || '';
    document.getElementById('profileAddressInput').value = user.address || '';
    document.getElementById('profileRoleInput').value = roleName;
    document.getElementById('profileStatusInput').value = user.status || 'N/A';

    // Clear password fields
    document.getElementById('newPasswordInput').value = '';
    document.getElementById('confirmPasswordInput').value = '';
}

async function updateProfile() {
    const name = document.getElementById('profileNameInput').value;
    const phone = document.getElementById('profilePhoneInput').value;
    const address = document.getElementById('profileAddressInput').value;
    const newPassword = document.getElementById('newPasswordInput').value;
    const confirmPassword = document.getElementById('confirmPasswordInput').value;

    if (!name) {
        alert('Name is required');
        return;
    }

    if (newPassword && newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }

    const data = {
        name: name,
        phone_number: phone || null,
        address: address || null,
    };

    if (newPassword) {
        data.password = newPassword;
        data.password_confirmation = confirmPassword;
    }

    const result = await API.put('/users/profile/update', data);

    if (result.success) {
        alert('Profile updated successfully!');
        loadProfile();
        
        // Update localStorage user data
        const updatedUser = result.data.data;
        localStorage.setItem('user', JSON.stringify(updatedUser));
        
        // Update header name
        const headerName = document.querySelector('#authLinks span');
        if (headerName) {
            headerName.textContent = updatedUser.name || 'User';
        }
    } else {
        alert('Error updating profile: ' + (result.error || 'Unknown error'));
    }
}

// Activity Logs Functions
let currentActivityPage = 1;

async function loadActivityLogs(page = 1) {
    const container = document.getElementById('activityLogs');
    currentActivityPage = page;
    
    const result = await API.get(`/users/profile/activity-logs?page=${page}`);
    
    if (result.success) {
        const paginationData = result.data.data;
        const logs = paginationData?.data || paginationData || [];
        displayActivityLogs(logs, paginationData);
    } else {
        container.innerHTML = '<p class="error-text">Error loading activity history: ' + (result.error || 'Unknown error') + '</p>';
    }
}

function displayActivityLogs(logs, paginationData) {
    const container = document.getElementById('activityLogs');
    
    if (logs.length === 0) {
        container.innerHTML = '<p>No activity history found.</p>';
        return;
    }
    
    const currentPage = paginationData?.current_page || 1;
    const lastPage = paginationData?.last_page || 1;
    const total = paginationData?.total || logs.length;
    
    // Show info that only last 30 records are displayed
    const infoText = total >= 30 
        ? '<p class="activity-info">Showing last 30 activity records (10 per page)</p>' 
        : `<p class="activity-info">Showing ${total} activity record${total > 1 ? 's' : ''} (10 per page)</p>`;
    
    let paginationHTML = '';
    if (lastPage > 1) {
        paginationHTML = `
            <div class="pagination">
                <button class="btn-pagination" onclick="loadActivityLogs(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${currentPage} of ${lastPage}</span>
                <button class="btn-pagination" onclick="loadActivityLogs(${currentPage + 1})" ${currentPage === lastPage ? 'disabled' : ''}>
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        `;
    }
    
    container.innerHTML = `
        ${infoText}
        <div class="activity-table">
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    ${logs.map(log => `
                        <tr>
                            <td><span class="badge badge-${getActionBadgeClass(log.action)}">${log.action || 'N/A'}</span></td>
                            <td>${log.description || '-'}</td>
                            <td>${log.ip_address || '-'}</td>
                            <td>${formatDateTime(log.created_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ${paginationHTML}
    `;
}

function getActionBadgeClass(action) {
    if (!action) return 'secondary';
    const actionLower = action.toLowerCase();
    if (actionLower.includes('login')) return 'success';
    if (actionLower.includes('logout')) return 'warning';
    if (actionLower.includes('update') || actionLower.includes('create')) return 'info';
    if (actionLower.includes('delete')) return 'danger';
    return 'secondary';
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

</script>

<style>
.profile-container {
    max-width: 900px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    text-align: center;
}

.profile-avatar {
    font-size: 80px;
    margin-bottom: 20px;
}

.profile-header h2 {
    margin: 10px 0;
    font-size: 1.8em;
}

.profile-header p {
    margin: 5px 0;
    opacity: 0.9;
}

.role-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    margin-top: 10px;
    font-size: 0.9em;
}

.role-badge.role-admin {
    background: rgba(255,193,7,0.3);
}

.role-badge.role-staff {
    background: rgba(33,150,243,0.3);
}

.role-badge.role-student {
    background: rgba(76,175,80,0.3);
}

.profile-content {
    padding: 30px;
}

.profile-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.profile-section:last-of-type {
    border-bottom: none;
}

.profile-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item label {
    font-weight: 600;
    color: #555;
    margin-bottom: 8px;
    font-size: 0.9em;
}

.form-control {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.form-control:read-only {
    background: #f5f5f5;
    cursor: not-allowed;
}

.profile-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.activity-logs {
    margin-top: 20px;
}

.activity-table {
    overflow-x: auto;
}

.activity-table table {
    width: 100%;
    border-collapse: collapse;
}

.activity-table th,
.activity-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.activity-table th {
    background: #f5f5f5;
    font-weight: 600;
    color: #555;
}

.activity-table tr:hover {
    background: #f9f9f9;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.pagination-info {
    color: #555;
    font-size: 0.9em;
}

.btn-pagination {
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    color: #333;
    cursor: pointer;
    font-size: 0.9em;
    transition: all 0.3s;
}

.btn-pagination:hover:not(:disabled) {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.btn-pagination:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-pagination i {
    margin: 0 5px;
}

.loading-spinner {
    text-align: center;
    padding: 20px;
    color: #666;
}

.error-text {
    color: #dc3545;
}

.activity-info {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 15px;
    font-style: italic;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-actions {
        flex-direction: column;
    }
    
    .activity-table {
        font-size: 0.9em;
    }
    
    .activity-table th,
    .activity-table td {
        padding: 8px;
    }
    
    .pagination {
        flex-direction: column;
        gap: 10px;
    }
    
    .pagination-info {
        order: -1;
    }
}
</style>
@endsection


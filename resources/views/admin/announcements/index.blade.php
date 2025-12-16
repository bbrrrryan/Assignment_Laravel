{{-- Author: Liew Zi Li (announcement management) --}}
@extends('layouts.app')

@section('title', 'Announcement Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Announcements & Notifications</h1>
            <p>Manage all announcements and notifications in the system</p>
        </div>
        <div>
            <button class="btn-header-white" id="createButton" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Add New Announcement
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
        <button class="admin-tab active" onclick="switchTab('announcements', this)">
            <i class="fas fa-bullhorn"></i> Announcements
        </button>
        <button class="admin-tab" onclick="switchTab('notifications', this)">
            <i class="fas fa-bell"></i> Notifications
        </button>
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

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <form class="filters-form" id="announcementSearchForm">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="announcementSearchInput" placeholder="Search by title or content..." 
                           class="filter-input">
                    <button type="button" class="filter-clear-btn" id="announcementSearchClear" style="display: none;" onclick="clearAnnouncementSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <select id="typeFilter" class="filter-select">
                        <option value="">All Types</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                        <option value="reminder">Reminder</option>
                        <option value="general">General</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <select id="priorityFilter" class="filter-select">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="announcementSearchLoading" style="display: none; text-align: center; padding: 20px;">
        <i class="fas fa-spinner"></i> Loading announcements...
    </div>

    <!-- Announcements Section -->
    <div id="announcementsSection" class="tab-content active">
        <!-- Announcements Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id">ID <i class="sort-icon"></i></th>
                        <th class="sortable" data-sort="title">Title <i class="sort-icon"></i></th>
                        <th class="sortable" data-sort="type">Type <i class="sort-icon"></i></th>
                        <th class="sortable" data-sort="priority">Priority <i class="sort-icon"></i></th>
                        <th>Created By</th>
                        <th class="sortable" data-sort="created_at">Created At <i class="sort-icon"></i></th>
                        <th class="sortable" data-sort="is_active">Status <i class="sort-icon"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="announcementsList">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">Loading announcements...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination-wrapper" id="paginationWrapper"></div>
    </div>

    <!-- Notifications Section -->
    <div id="notificationsSection" class="tab-content" style="display: none;">
        <!-- Notifications Table -->
        <div id="notificationsList" class="table-container">
            <p style="text-align: center; padding: 40px;">Loading notifications...</p>
        </div>
    </div>
</div>

<!-- Create Announcement Modal -->
<div id="announcementModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Create Announcement</h2>
        <form id="announcementForm">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" id="announcementTitle" required>
            </div>
            <div class="form-group">
                <label>Content *</label>
                <textarea id="announcementContent" required rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select id="announcementType" required>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                    <option value="reminder">Reminder</option>
                    <option value="general">General</option>
                </select>
            </div>
            <div class="form-group">
                <label>Priority</label>
                <select id="announcementPriority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Create & Publish</button>
            </div>
        </form>
    </div>
</div>

<!-- Create/Edit Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="notificationModalTitle">Create Notification</h2>
            <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="notificationForm">
                <input type="hidden" id="notificationId">
                
                <div class="form-group">
                    <label for="notificationTitle">Title <span class="required">*</span></label>
                    <input type="text" id="notificationTitle" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="notificationMessage">Message <span class="required">*</span></label>
                    <textarea id="notificationMessage" class="form-control" rows="5" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="notificationType">Type <span class="required">*</span></label>
                        <select id="notificationType" class="form-control" required>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notificationPriority">Priority</label>
                        <select id="notificationPriority" class="form-control">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notificationTargetAudience">Target Audience <span class="required">*</span></label>
                    <select id="notificationTargetAudience" class="form-control" required onchange="toggleNotificationTargetUsers()">
                        <option value="all">All Users</option>
                        <option value="students">Students Only</option>
                        <option value="staff">Staff Only</option>
                        <option value="admins">Admins Only</option>
                        <option value="specific">Specific Users</option>
                    </select>
                </div>
                
                <div class="form-group" id="notificationTargetUsersGroup" style="display: none;">
                    <label for="notificationTargetUserIds">Select Users</label>
                    <input type="text" id="notificationTargetUserIds" class="form-control" placeholder="Enter user IDs separated by commas">
                    <small class="form-text">Example: 1,2,3</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="notificationIsActive" checked>
                        Active
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeNotificationModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="notificationSubmitBtn">Create Notification</button>
                </div>
            </form>
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

    loadAnnouncements(null, null, 1);
    
    // Add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            loadAnnouncements(sortBy, null, 1);
        });
    });
    
    // Real-time search on input (debounced)
    const searchInput = document.getElementById('announcementSearchInput');
    const searchClearBtn = document.getElementById('announcementSearchClear');
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            loadAnnouncements(null, null, 1);
        }, 300); // 300ms delay
        
        searchInput.addEventListener('input', function() {
            // Show/hide clear button
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
    
    // Filter changes trigger search
    const typeFilter = document.getElementById('typeFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            loadAnnouncements(null, null, 1);
        });
    }
    
    if (priorityFilter) {
        priorityFilter.addEventListener('change', function() {
            loadAnnouncements(null, null, 1);
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            if (currentTab === 'announcements') {
                loadAnnouncements(null, null, 1);
            }
        });
    }
    
    // Update search and filters to work with both tabs
    if (searchInput) {
        const debouncedSearchBoth = debounce(() => {
            if (currentTab === 'announcements') {
                loadAnnouncements(null, null, 1);
            } else {
                loadNotifications(1);
            }
        }, 300);
        
        // Add listener that works for both tabs
        const originalInput = searchInput.oninput;
        searchInput.addEventListener('input', function() {
            if (searchClearBtn) {
                if (this.value.trim()) {
                    searchClearBtn.style.display = 'flex';
                } else {
                    searchClearBtn.style.display = 'none';
                }
            }
            debouncedSearchBoth();
        });
    }
    
    if (typeFilter) {
        const originalTypeChange = typeFilter.onchange;
        typeFilter.addEventListener('change', function() {
            if (currentTab === 'announcements') {
                loadAnnouncements(null, null, 1);
            } else {
                loadNotifications(1);
            }
        });
    }
    
    if (priorityFilter) {
        const originalPriorityChange = priorityFilter.onchange;
        priorityFilter.addEventListener('change', function() {
            if (currentTab === 'announcements') {
                loadAnnouncements(null, null, 1);
            } else {
                loadNotifications(1);
            }
        });
    }
    
    // Prevent form submission
    const searchForm = document.getElementById('announcementSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performAnnouncementSearch();
        });
    }
});

let currentSortBy = 'created_at';
let currentSortOrder = 'desc';
let currentPage = 1;
let searchTimeout;
let currentTab = 'announcements';

// Debounce function
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

async function loadAnnouncements(sortBy = null, sortOrder = null, page = 1) {
    const container = document.getElementById('announcementsList');
    const loadingIndicator = document.getElementById('announcementSearchLoading');
    const tableContainer = document.querySelector('.table-container');
    
    container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Loading announcements...</td></tr>';
    
    // Show loading indicator
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
    }
    
    if (sortBy !== null) {
        if (currentSortBy === sortBy) {
            // Toggle sort order
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortOrder = 'asc';
        }
    }
    
    currentPage = page;
    
    // Get search and filter values
    const searchInput = document.getElementById('announcementSearchInput');
    const typeFilter = document.getElementById('typeFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    const search = searchInput ? searchInput.value.trim() : '';
    const type = typeFilter ? typeFilter.value : '';
    const priority = priorityFilter ? priorityFilter.value : '';
    const isActive = statusFilter ? statusFilter.value : '';
    
    const params = new URLSearchParams();
    params.append('sort_by', currentSortBy);
    params.append('sort_order', currentSortOrder);
    params.append('per_page', 10);
    params.append('page', currentPage);
    
    if (search) {
        params.append('search', search);
    }
    if (type) {
        params.append('type', type);
    }
    if (priority) {
        params.append('priority', priority);
    }
    if (isActive !== '') {
        params.append('is_active', isActive);
    }
    
    const result = await API.get(`/announcements?${params.toString()}`);
    
    if (result.success) {
        const responseData = result.data.data || result.data;
        const announcements = responseData?.data || responseData || [];
        const paginationData = responseData;
        displayAnnouncements(announcements, paginationData);
        updateSortIndicators();
    } else {
        container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #dc3545;">Error loading announcements: ' + (result.error || 'Unknown error') + '</td></tr>';
    }
    
    // Hide loading indicator
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    if (tableContainer) {
        tableContainer.style.opacity = '1';
    }
}

// Perform search function
function performAnnouncementSearch() {
    loadAnnouncements(null, null, 1);
}

// Clear announcement search
function clearAnnouncementSearch() {
    const searchInput = document.getElementById('announcementSearchInput');
    const searchClearBtn = document.getElementById('announcementSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        loadAnnouncements(null, null, 1);
    }
}

function updateSortIndicators() {
    // Remove all sort indicators
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'sort-icon';
        icon.textContent = '';
    });
    
    // Add indicator to current sort column
    const currentSortTh = document.querySelector(`th[data-sort="${currentSortBy}"]`);
    if (currentSortTh) {
        const icon = currentSortTh.querySelector('.sort-icon');
        if (icon) {
            icon.className = `sort-icon fas fa-sort-${currentSortOrder === 'asc' ? 'up' : 'down'}`;
        }
    }
}

function displayAnnouncements(announcements, paginationData = null) {
    const container = document.getElementById('announcementsList');
    const paginationWrapper = document.getElementById('paginationWrapper');
    
    if (announcements.length === 0) {
        container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No announcements found</td></tr>';
        if (paginationWrapper) {
            paginationWrapper.innerHTML = '';
        }
        return;
    }

    container.innerHTML = announcements.map(announcement => {
        const typeBadge = `<span class="badge badge-${announcement.type}">${announcement.type}</span>`;
        const priorityBadge = `<span class="badge badge-priority-${announcement.priority}">${announcement.priority}</span>`;
        const statusBadge = announcement.is_active 
            ? `<span class="badge badge-success">Active</span>` 
            : `<span class="badge badge-secondary">Inactive</span>`;
        const createdBy = announcement.creator?.name || 'System';
        const createdAt = formatDateTime(announcement.created_at);
        const showUrl = `/admin/announcements/${announcement.id}`;
        const editUrl = `/admin/announcements/${announcement.id}/edit`;
        
        return `
            <tr class="table-row-clickable" onclick="window.location.href='${showUrl}'">
                <td>${announcement.id}</td>
                <td>${announcement.title}</td>
                <td>${typeBadge}</td>
                <td>${priorityBadge}</td>
                <td>${createdBy}</td>
                <td>${createdAt}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <a href="${showUrl}" class="btn-sm btn-info" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="${editUrl}" class="btn-sm btn-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </td>
            </tr>
        `;
    }).join('');
    
    // Render pagination
    if (paginationData && paginationData.links && paginationData.links.length > 0) {
        let paginationHtml = '<ul class="pagination">';
        
        paginationData.links.forEach(link => {
            if (link.url) {
                const activeClass = link.active ? 'active' : '';
                const disabledClass = !link.url ? 'disabled' : '';
                const label = link.label.replace('&laquo;', '«').replace('&raquo;', '»');
                
                paginationHtml += `
                    <li class="page-item ${activeClass} ${disabledClass}">
                        <a class="page-link" href="${link.url || '#'}" ${link.url ? '' : 'onclick="return false;"'}>
                            ${label}
                        </a>
                    </li>
                `;
            }
        });
        
        paginationHtml += '</ul>';
        paginationWrapper.innerHTML = paginationHtml;
        
        // Attach click handlers to pagination links
        paginationWrapper.querySelectorAll('.page-link').forEach(link => {
            if (link.href && !link.closest('.page-item').classList.contains('disabled')) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page') || 1;
                    loadAnnouncements(null, null, page);
                });
            }
        });
    } else {
        paginationWrapper.innerHTML = '';
    }
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
    document.getElementById('announcementForm').reset();
    document.getElementById('announcementModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('announcementModal').style.display = 'none';
};

// Notification management functions
let notificationCurrentPage = 1;

window.loadNotifications = async function(page = 1) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align: center; padding: 40px;">Loading notifications...</p>';
    notificationCurrentPage = page;
    
    try {
        const searchInput = document.getElementById('announcementSearchInput');
        const typeFilter = document.getElementById('typeFilter');
        const priorityFilter = document.getElementById('priorityFilter');
        
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
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    notifications.forEach(notification => {
        const typeBadge = getNotificationTypeBadge(notification.type);
        const priorityBadge = getNotificationPriorityBadge(notification.priority);
        const statusBadge = notification.is_active 
            ? '<span class="badge badge-success">Active</span>' 
            : '<span class="badge badge-secondary">Inactive</span>';
        const createdAt = formatNotificationDateTime(notification.created_at);
        const creatorName = notification.creator ? notification.creator.name : 'System';
        
        html += `
            <tr>
                <td>#${notification.id}</td>
                <td>${escapeHtml(notification.title)}</td>
                <td>${typeBadge}</td>
                <td>${priorityBadge}</td>
                <td>${escapeHtml(creatorName)}</td>
                <td>${createdAt}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-action btn-view" onclick="viewNotification(${notification.id})" title="View">
                        <i class="fas fa-eye"></i>
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

function getNotificationTypeBadge(type) {
    const badges = {
        'info': '<span class="badge badge-info">Info</span>',
        'warning': '<span class="badge badge-warning">Warning</span>',
        'success': '<span class="badge badge-success">Success</span>',
        'error': '<span class="badge badge-error">Error</span>',
        'reminder': '<span class="badge badge-reminder">Reminder</span>'
    };
    return badges[type] || '<span class="badge">' + type + '</span>';
}

function getNotificationPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge badge-priority-low">Low</span>',
        'medium': '<span class="badge badge-info">Medium</span>',
        'high': '<span class="badge badge-warning">High</span>',
        'urgent': '<span class="badge badge-error">Urgent</span>'
    };
    return badges[priority] || '<span class="badge">' + (priority || 'Medium') + '</span>';
}

function formatNotificationDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.showCreateNotificationModal = function() {
    document.getElementById('notificationModalTitle').textContent = 'Create Notification';
    document.getElementById('notificationForm').reset();
    document.getElementById('notificationId').value = '';
    document.getElementById('notificationSubmitBtn').textContent = 'Create Notification';
    document.getElementById('notificationTargetUsersGroup').style.display = 'none';
    document.getElementById('notificationModal').style.display = 'flex';
};

window.closeNotificationModal = function() {
    document.getElementById('notificationModal').style.display = 'none';
};

window.toggleNotificationTargetUsers = function() {
    const targetAudience = document.getElementById('notificationTargetAudience').value;
    const targetUsersGroup = document.getElementById('notificationTargetUsersGroup');
    if (targetAudience === 'specific') {
        targetUsersGroup.style.display = 'block';
    } else {
        targetUsersGroup.style.display = 'none';
    }
};

async function viewNotification(id) {
    window.location.href = `/notifications/${id}`;
}

async function editNotification(id) {
    try {
        const result = await API.get(`/notifications/${id}`);
        if (result && result.success !== false && result.data) {
            const notification = result.data;
            
            document.getElementById('notificationModalTitle').textContent = 'Edit Notification';
            document.getElementById('notificationId').value = notification.id;
            document.getElementById('notificationTitle').value = notification.title || '';
            document.getElementById('notificationMessage').value = notification.message || '';
            document.getElementById('notificationType').value = notification.type || 'info';
            document.getElementById('notificationPriority').value = notification.priority || 'medium';
            document.getElementById('notificationTargetAudience').value = notification.target_audience || 'all';
            document.getElementById('notificationIsActive').checked = notification.is_active !== false;
            document.getElementById('notificationSubmitBtn').textContent = 'Update Notification';
            
            if (notification.target_user_ids && Array.isArray(notification.target_user_ids)) {
                document.getElementById('notificationTargetUserIds').value = notification.target_user_ids.join(',');
            }
            
            toggleNotificationTargetUsers();
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
            loadNotifications(notificationCurrentPage);
        } else {
            alert('Error deleting notification: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
        alert('Error deleting notification: ' + error.message);
    }
}

// Notification form submission
if (document.getElementById('notificationForm')) {
    document.getElementById('notificationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            title: document.getElementById('notificationTitle').value,
            message: document.getElementById('notificationMessage').value,
            type: document.getElementById('notificationType').value,
            priority: document.getElementById('notificationPriority').value,
            target_audience: document.getElementById('notificationTargetAudience').value,
            is_active: document.getElementById('notificationIsActive').checked
        };
        
        const targetUserIds = document.getElementById('notificationTargetUserIds').value;
        if (formData.target_audience === 'specific' && targetUserIds) {
            formData.target_user_ids = targetUserIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
        }
        
        const notificationId = document.getElementById('notificationId').value;
        const submitBtn = document.getElementById('notificationSubmitBtn');
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
                closeNotificationModal();
                loadNotifications(notificationCurrentPage);
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
    });
}

// Tab switching function
window.switchTab = function(tab, element) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.admin-tab').forEach(btn => btn.classList.remove('active'));
    if (element) {
        element.classList.add('active');
    } else {
        // Find tab button by text content
        document.querySelectorAll('.admin-tab').forEach(btn => {
            if (btn.textContent.includes(tab === 'announcements' ? 'Announcements' : 'Notifications')) {
                btn.classList.add('active');
            }
        });
    }
    
    // Update tab content
    document.getElementById('announcementsSection').style.display = tab === 'announcements' ? 'block' : 'none';
    document.getElementById('notificationsSection').style.display = tab === 'notifications' ? 'block' : 'none';
    
    // Update create button - hide for notifications tab
    const createButton = document.getElementById('createButton');
    if (tab === 'announcements') {
        createButton.innerHTML = '<i class="fas fa-plus"></i> Add New Announcement';
        createButton.setAttribute('onclick', 'showCreateModal()');
        createButton.style.display = 'inline-flex';
        loadAnnouncements(null, null, 1);
    } else {
        // Hide create button for notifications tab
        createButton.style.display = 'none';
        loadNotifications(1);
    }
    
    // Update filters visibility - hide status filter for notifications
    const statusFilterWrapper = document.getElementById('statusFilter')?.closest('.filter-select-wrapper');
    if (statusFilterWrapper) {
        statusFilterWrapper.style.display = tab === 'notifications' ? 'none' : 'block';
    }
};

// Bind form submit event
document.getElementById('announcementForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const data = {
        title: document.getElementById('announcementTitle').value,
        content: document.getElementById('announcementContent').value,
        type: document.getElementById('announcementType').value,
        priority: document.getElementById('announcementPriority').value,
        target_audience: 'all',
    };

    const result = await API.post('/announcements', data);

    if (result.success) {
        // Publish announcement
        const publishResult = await API.post(`/announcements/${result.data.data.id}/publish`, {});
        
        window.closeModal();
        loadAnnouncements(null, null, 1);
        showToast('Announcement created and published successfully!', 'success');
    } else {
        showToast('Error creating announcement: ' + (result.error || 'Unknown error'), 'error');
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
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
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

a.btn-header-white {
    text-decoration: none;
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
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.data-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table th.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.data-table th.sortable:hover {
    background-color: #e9ecef;
}

.sort-icon {
    margin-left: 5px;
    font-size: 0.8em;
    color: #6c757d;
}

.data-table th.sortable:hover .sort-icon {
    color: #2c3e50;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    color: #495057;
    font-size: 0.95rem;
}

.data-table tbody tr {
    transition: background 0.2s ease;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.table-row-clickable {
    cursor: pointer;
    transition: background 0.2s ease;
}

.table-row-clickable:hover {
    background: #f8f9fa !important;
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

/* Filters Section Styling */
.filters-section {
    margin-bottom: 30px;
}

.filters-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-input-wrapper,
.filter-select-wrapper {
    position: relative;
    flex: 1;
    min-width: 200px;
}

.filter-input-wrapper {
    position: relative;
}

.filter-clear-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
    z-index: 2;
}

.filter-clear-btn:hover {
    color: #495057;
}

.filter-clear-btn i {
    font-size: 0.85rem;
}

.filter-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 1;
    pointer-events: none;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 12px 40px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-input::placeholder {
    color: #adb5bd;
}

.filter-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Search Button Styling */
.btn-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #ffffff;
    color: #cb2d3e;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    white-space: nowrap;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    color: white;
}

.btn-search:active {
    transform: translateY(0);
}

.btn-search i {
    font-size: 0.9rem;
}

/* Clear Button Styling */
.btn-clear {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #ffffff;
    color: #6c757d;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-clear:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-clear:active {
    transform: translateY(0);
}

.btn-clear i {
    font-size: 0.9rem;
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

/* Action buttons */
.actions {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}

.btn-sm.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-sm.btn-info:hover {
    background: #138496;
}

.btn-sm.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-sm.btn-warning:hover {
    background: #e0a800;
}

/* Pagination Styles */
.pagination-wrapper {
    margin-top: 30px;
    display: flex;
    justify-content: center; 
    padding-bottom: 40px;
}

.pagination-wrapper p { display: none; }

.pagination {
    display: flex;
    gap: 8px;
    align-items: center;
    border-radius: 0;
    margin: 0;
}

.page-item {
    margin: 0;
    border: none;
}

.page-item .page-link {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 15px;
    font-size: 1.2rem;
    font-weight: 600;
    color: #6c757d;
    background-color: #ffffff;
    border: 1px solid #e9ecef; 
    border-radius: 8px !important; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    text-decoration: none;
}

.page-item:first-child .page-link,
.page-item:last-child .page-link {
    border-radius: 8px !important;
}

.page-item:not(.active):not(.disabled) .page-link:hover {
    background-color: #f8f9fa;
    color: #667eea; 
    transform: translateY(-2px); 
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.15);
    border-color: #667eea;
    z-index: 2;
    text-decoration: none;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
    transform: scale(1.05);
}

.page-item.disabled .page-link {
    background-color: #f1f3f5;
    color: #adb5bd;
    border-color: #e9ecef;
    opacity: 0.7;
    pointer-events: none;
    box-shadow: none;
}

@media (max-width: 576px) {
    .page-item .page-link {
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        font-size: 0.85rem;
    }
    .pagination {
        gap: 5px;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-input-wrapper,
    .filter-select-wrapper {
        width: 100%;
    }
    
    .btn-search,
    .btn-clear {
        width: 100%;
        justify-content: center;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }
}

/* Admin Tabs */
.admin-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 2px solid #e9ecef;
}

.admin-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-tab:hover {
    color: #cb2d3e;
    background-color: #f8f9fa;
}

.admin-tab.active {
    color: #cb2d3e;
    border-bottom-color: #cb2d3e;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Notification Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background-color: #e2e3e5;
    color: #383d41;
}

/* Action Buttons */
.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin: 0 2px;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.btn-view {
    background-color: #17a2b8;
    color: white;
}

.btn-view:hover {
    background-color: #138496;
}

.btn-edit {
    background-color: #28a745;
    color: white;
}

.btn-edit:hover {
    background-color: #218838;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}

.btn-delete:hover {
    background-color: #c82333;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    padding: 20px;
}

.btn-pagination {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-pagination:hover {
    background-color: #f8f9fa;
    border-color: #cb2d3e;
    color: #cb2d3e;
}

.pagination-info {
    color: #6c757d;
    font-weight: 500;
}

/* Modal Styles for Notifications */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
    margin: 0;
    color: #2d3436;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #6c757d;
    line-height: 1;
}

.modal-close:hover {
    color: #2d3436;
}

.modal-body {
    padding: 30px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.required {
    color: #dc3545;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
}

.btn-primary,
.btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #cb2d3e;
    color: white;
}

.btn-primary:hover {
    background-color: #a01a2a;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 0.85rem;
    color: #6c757d;
}
</style>
@endsection


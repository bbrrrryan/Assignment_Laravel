{{-- Author: Liew Zi Li (announcement management) --}}
@extends('layouts.app')

@section('title', 'Announcement Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Announcement Management</h1>
            <p>Manage all announcements in the system</p>
        </div>
        <div>
            <button class="btn-header-white" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Add New Announcement
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
            loadAnnouncements(null, null, 1);
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
</style>
@endsection


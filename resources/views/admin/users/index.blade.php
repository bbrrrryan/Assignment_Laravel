@extends('layouts.app')

@section('title', 'User Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>User Management</h1>
            <p>Manage all users in the system</p>
        </div>
        <div>
            <a href="{{ route('admin.users.export-csv') }}" class="btn-header-white">
                <i class="fas fa-download"></i> Export CSV
            </a>
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

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning">
            <strong>Import Warnings:</strong>
            <ul>
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- CSV Upload Section -->
    <div class="csv-upload-section">
        <div class="csv-upload-card">
            <h3><i class="fas fa-file-upload"></i> Bulk Import Users (CSV)</h3>
            <form id="csvUploadForm" method="POST" action="{{ route('admin.users.upload-csv') }}" enctype="multipart/form-data" class="csv-form">
                @csrf
                <div class="csv-form-group">
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="csv-file-input" id="csvFileInput">
                    <small class="csv-help-text">
                        <strong>CSV Format:</strong> name, email, password, role, phone_number, address, status<br>
                        <strong>Example:</strong> John Doe, john@example.com, password123, student, 0123456789, Address, active
                    </small>
                </div>
                <button type="submit" class="btn-csv-upload" id="csvUploadBtn">
                    <i class="fas fa-upload"></i> Upload CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <form method="GET" action="{{ route('admin.users.index') }}" class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" name="search" id="userSearchInput" placeholder="Search by name or email..." 
                           value="{{ request('search') }}" class="filter-input">
                    <button type="button" class="filter-clear-btn" id="userSearchClear" style="display: none;" onclick="clearUserSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select name="status" id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <select name="role" id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
                
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="id">ID <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="name">Name <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="email">Email <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="role">Role <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="status">Status <i class="sort-icon"></i></th>
                    <th>Phone</th>
                    <th class="sortable" data-sort="created_at">Joined <i class="sort-icon"></i></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge badge-info">
                                {{ ucfirst($user->role ?? '-') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->phone_number ?? '-' }}</td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Loading Indicator -->
    <div id="searchLoading" style="display: none; text-align: center; padding: 20px;">
        <i class="fas fa-spinner fa-spin"></i> 正在搜索...
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

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
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* CSV Upload Section */
.csv-upload-section {
    margin-bottom: 30px;
}

.csv-upload-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.csv-upload-card h3 {
    margin: 0 0 20px 0;
    color: #495057;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.csv-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.csv-form-group {
    flex: 1;
    min-width: 300px;
}

.csv-file-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
}

.csv-file-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.csv-help-text {
    display: block;
    margin-top: 8px;
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.5;
}

.btn-csv-upload {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    white-space: nowrap;
}

.btn-csv-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-csv-upload:active {
    transform: translateY(0);
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

/* Responsive Design */
@media (max-width: 768px) {
    .filters-form,
    .csv-form {
        flex-direction: column;
    }
    
    .filter-input-wrapper,
    .filter-select-wrapper,
    .csv-form-group {
        width: 100%;
    }
    
    .btn-search,
    .btn-clear,
    .btn-csv-upload {
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

/* Table Container Enhancement */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.data-table {
    margin: 0;
}

.data-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.data-table th {
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.85rem;
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
    color: #495057;
}

/* Actions Buttons */
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

/* Custom Pagination Styling */
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

.page-link svg {
    width: 16px;
    height: 16px;
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
</style>

<script>
// Real-time search functionality
let searchTimeout;
let currentPage = 1;
let currentSortBy = 'created_at';
let currentSortOrder = 'desc';

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

// Fetch users from API
async function fetchUsers(search = '', status = '', role = '', page = 1, sortBy = null, sortOrder = null) {
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (role) params.append('role', role);
    params.append('per_page', 10);
    params.append('page', page);
    
    // Add sorting parameters
    if (sortBy !== null) {
        currentSortBy = sortBy;
    }
    if (sortOrder !== null) {
        currentSortOrder = sortOrder;
    }
    params.append('sort_by', currentSortBy);
    params.append('sort_order', currentSortOrder);
    
    const queryString = params.toString();
    // API.js already has baseURL='/api', so we just need '/users'
    const endpoint = '/users' + (queryString ? '?' + queryString : '');
    
    try {
        console.log('Fetching users from:', endpoint);
        console.log('API token exists:', !!API.getToken());
        
        const result = await API.get(endpoint);
        console.log('API result:', result);
        
        // API response structure: { success: true, data: { message: "...", data: { data: [...], links: [...] } } }
        if (result.success && result.data) {
            // Check if result.data.data exists (the paginated response)
            if (result.data.data) {
                // Return the paginated data structure: { data: [...], links: [...] }
                return result.data.data;
            } else {
                console.error('Unexpected API response structure:', result);
                return null;
            }
        } else {
            console.error('API request failed:', result);
            if (result.error) {
                console.error('Error message:', result.error);
            }
            // Check if it's an authentication error
            if (result.data && (result.data.message && result.data.message.includes('Unauthenticated'))) {
                console.error('Authentication failed. Token may be invalid or expired.');
            }
            return null;
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        return null;
    }
}

// Render users table
function renderUsersTable(usersData) {
    const tbody = document.getElementById('usersTableBody');
    const paginationWrapper = document.getElementById('paginationWrapper');
    
    if (!usersData || !usersData.data || usersData.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No users found</td></tr>';
        paginationWrapper.innerHTML = '';
        return;
    }
    
    // Render table rows
    tbody.innerHTML = usersData.data.map(user => {
        const statusBadgeClass = (user.status === 'active') ? 'success' : 'secondary';
        const role = user.role || '-';
        const phone = user.phone_number || '-';
        const joinDate = user.created_at ? new Date(user.created_at).toISOString().split('T')[0] : '-';
        const roleDisplay = escapeHtml(role.charAt(0).toUpperCase() + role.slice(1));
        const statusDisplay = escapeHtml((user.status || '').charAt(0).toUpperCase() + (user.status || '').slice(1));
        
        return '<tr>' +
            '<td>' + user.id + '</td>' +
            '<td>' + escapeHtml(user.name || '') + '</td>' +
            '<td>' + escapeHtml(user.email || '') + '</td>' +
            '<td><span class="badge badge-info">' + roleDisplay + '</span></td>' +
            '<td><span class="badge badge-' + statusBadgeClass + '">' + statusDisplay + '</span></td>' +
            '<td>' + escapeHtml(phone) + '</td>' +
            '<td>' + escapeHtml(joinDate) + '</td>' +
            '<td class="actions">' +
                '<a href="/admin/users/' + user.id + '" class="btn-sm btn-info" title="View">' +
                    '<i class="fas fa-eye"></i>' +
                '</a>' +
                '<a href="/admin/users/' + user.id + '/edit" class="btn-sm btn-warning" title="Edit">' +
                    '<i class="fas fa-edit"></i>' +
                '</a>' +
            '</td>' +
        '</tr>';
    }).join('');
    
    // Render pagination
    if (usersData.links && usersData.links.length > 0) {
        let paginationHtml = '<ul class="pagination">';
        
        usersData.links.forEach(link => {
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
                    performSearch(page);
                });
            }
        });
    } else {
        paginationWrapper.innerHTML = '';
    }
}

// Clear user search
function clearUserSearch() {
    const searchInput = document.getElementById('userSearchInput');
    const searchClearBtn = document.getElementById('userSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        performSearch(1);
    }
}

// Update sort indicators in table headers
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

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Perform search
async function performSearch(page = 1, sortBy = null, sortOrder = null) {
    // Handle sorting
    if (sortBy !== null) {
        if (currentSortBy === sortBy) {
            // Toggle sort order
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortOrder = 'asc';
        }
    }
    // Check if API is available
    if (typeof API === 'undefined') {
        console.error('API is not defined! Make sure api.js is loaded.');
        alert('API 未加载。请刷新页面重试。');
        return;
    }
    
    const searchInput = document.getElementById('userSearchInput');
    const statusFilter = document.getElementById('statusFilter');
    const roleFilter = document.getElementById('roleFilter');
    const loadingIndicator = document.getElementById('searchLoading');
    const tableContainer = document.querySelector('.table-container');
    
    const search = searchInput ? searchInput.value.trim() : '';
    const status = statusFilter ? statusFilter.value : '';
    const role = roleFilter ? roleFilter.value : '';
    
    currentPage = page;
    
    // Show loading indicator
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
    }
    
    try {
        const usersData = await fetchUsers(search, status, role, page, currentSortBy, currentSortOrder);
        
        if (usersData && usersData.data) {
            renderUsersTable(usersData);
            updateSortIndicators();
        } else {
            console.error('No users data received:', usersData);
            let errorMsg = '无法加载用户数据。';
            
            // Check if API is available
            if (typeof API === 'undefined') {
                errorMsg += ' API 未加载。请刷新页面重试。';
            } else if (!API.getToken()) {
                errorMsg += ' 未找到认证令牌。请重新登录。';
            } else {
                errorMsg += ' 请查看浏览器控制台（F12）获取详细错误信息。';
            }
            
            document.getElementById('usersTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center">' + errorMsg + '</td></tr>';
        }
    } catch (error) {
        console.error('Search error:', error);
        const errorMsg = 'Error: ' + (error.message || 'Unknown error. Please check console.');
        document.getElementById('usersTableBody').innerHTML = 
            '<tr><td colspan="8" class="text-center">' + errorMsg + '</td></tr>';
    } finally {
        // Hide loading indicator
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
        if (tableContainer) {
            tableContainer.style.opacity = '1';
        }
    }
}

// Initialize real-time search
document.addEventListener('DOMContentLoaded', function() {
    // Handle CSV Upload Form with AJAX
    var csvForm = document.getElementById('csvUploadForm');
    var csvUploadBtn = document.getElementById('csvUploadBtn');
    
    if (csvForm) {
        csvForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent normal form submission
            
            var formData = new FormData(csvForm);
            var originalBtnText = csvUploadBtn.innerHTML;
            
            // Disable button and show loading
            csvUploadBtn.disabled = true;
            csvUploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading now...';
            
            // Send AJAX request
            fetch('{{ route("admin.users.upload-csv") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                // Re-enable button
                csvUploadBtn.disabled = false;
                csvUploadBtn.innerHTML = originalBtnText;
                
                // Check if success
                if (data.success) {
                    // Show success toast
                    showToast(data.message, 'success');
                    
                    // Show errors if any
                    if (data.data && data.data.errors && data.data.errors.length > 0) {
                        var errorMessage = 'Errors:\n' + data.data.errors.join('\n');
                        showToast(errorMessage, 'warning');
                    }
                    
                    // Reload page after 2 seconds to show updated user list
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Show error toast
                    showToast(data.message || data.error || 'Cannot upload file', 'error');
                }
            })
            .catch(function(error) {
                // Re-enable button
                csvUploadBtn.disabled = false;
                csvUploadBtn.innerHTML = originalBtnText;
                
                // Show error toast
                showToast('Something went wrong: ' + error.message, 'error');
                console.error('Upload error:', error);
            });
        });
    }
    
    // Real-time search setup
    const searchInput = document.getElementById('userSearchInput');
    const statusFilter = document.getElementById('statusFilter');
    const roleFilter = document.getElementById('roleFilter');
    const searchForm = document.querySelector('.filters-form');
    
    // Prevent form submission, use AJAX instead
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(1);
        });
    }
    
    // Real-time search on input (debounced)
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            performSearch(1);
        }, 300); // 300ms delay
        
        searchInput.addEventListener('input', debouncedSearch);
    }
    
    // Filter changes trigger search
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            performSearch(1);
        });
    }
    
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            performSearch(1);
        });
    }
    
    // Add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            performSearch(1, sortBy, null);
        });
    });
});
</script>
@endsection

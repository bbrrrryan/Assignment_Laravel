// Author: Liew Zi Li (user management)
// real-time search function
let searchTimeout;

// User pagination state (similar to booking pagination)
if (typeof window.userCurrentPage === 'undefined') {
    window.userCurrentPage = 1;
}
if (typeof window.userPerPage === 'undefined') {
    window.userPerPage = 10;
}
if (typeof window.userTotalPages === 'undefined') {
    window.userTotalPages = 1;
}
if (typeof window.userTotalUsers === 'undefined') {
    window.userTotalUsers = 0;
}

let currentSortBy = 'created_at';
let currentSortOrder = 'desc';

// debounce function to make sure not call too many times
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

// fetch users from api
async function fetchUsers(search = '', status = '', role = '', page = 1, sortBy = null, sortOrder = null) {
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (role) params.append('role', role);
    params.append('per_page', window.userPerPage);
    params.append('page', page);
    
    // add sorting parameters
    if (sortBy !== null) {
        currentSortBy = sortBy;
    }
    if (sortOrder !== null) {
        currentSortOrder = sortOrder;
    }
    params.append('sort_by', currentSortBy);
    params.append('sort_order', currentSortOrder);
    
    const queryString = params.toString();
    // api.js already got baseURL='/api' so just need '/users'
    const endpoint = '/users' + (queryString ? '?' + queryString : '');
    
    try {
        const result = await API.get(endpoint);
        
        if (result.success && result.data) {
            if (result.data.data) {
                return result.data.data;
            } else {
                return null;
            }
        } else {
            return null;
        }
    } catch (error) {
        return null;
    }
}

// render users table
function renderUsersTable(usersData) {
    const tbody = document.getElementById('usersTableBody');
    const paginationWrapper = document.getElementById('paginationWrapper');
    
    if (!usersData || !usersData.data || usersData.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">no users found</td></tr>';
        paginationWrapper.innerHTML = '';
        return;
    }
    
    // Update pagination state
    if (usersData.current_page !== undefined) {
        window.userCurrentPage = usersData.current_page;
        window.userTotalPages = usersData.last_page || 1;
        window.userTotalUsers = usersData.total || 0;
        window.userPerPage = usersData.per_page || window.userPerPage;
    } else {
        // Fallback: if no pagination info, assume single page
        window.userCurrentPage = 1;
        window.userTotalPages = 1;
        window.userTotalUsers = usersData.data ? usersData.data.length : 0;
    }
    
    // render table rows
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
    
    // render custom pagination (similar to booking)
    if (paginationWrapper) {
        paginationWrapper.innerHTML = renderUserPagination();
    }
}

// clear user search
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

// update sort indicators in table headers
function updateSortIndicators() {
    // remove all sort indicators first
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'sort-icon';
        icon.textContent = '';
    });
    
    // add indicator to current sort column
    const currentSortTh = document.querySelector(`th[data-sort="${currentSortBy}"]`);
    if (currentSortTh) {
        const icon = currentSortTh.querySelector('.sort-icon');
        if (icon) {
            icon.className = `sort-icon fas fa-sort-${currentSortOrder === 'asc' ? 'up' : 'down'}`;
        }
    }
}

// escape html to prevent xss attack
function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// perform search function
async function performSearch(page = 1, sortBy = null, sortOrder = null) {
    // handle sorting
    if (sortBy !== null) {
        if (currentSortBy === sortBy) {
            // toggle sort order
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortOrder = 'asc';
        }
    }
    // Reset to first page when sorting or filtering
    if (sortBy !== null) {
        window.userCurrentPage = 1;
        page = 1;
    } else {
        window.userCurrentPage = page;
    }
    
    // check if api available or not
    if (typeof API === 'undefined') {
        alert('API not loaded yet. Please refresh the page.');
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
    
    // show loading indicator
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
            let errorMsg = 'Cannot load user data.';
            
            if (typeof API === 'undefined') {
                errorMsg += ' API not loaded. Please refresh the page.';
            } else if (!API.getToken()) {
                errorMsg += ' Token not found. Please login again.';
            }
            
            document.getElementById('usersTableBody').innerHTML = 
                '<tr><td colspan="8" class="text-center">' + errorMsg + '</td></tr>';
        }
    } catch (error) {
        const errorMsg = 'Error loading users: ' + (error.message || 'Unknown error');
        document.getElementById('usersTableBody').innerHTML = 
            '<tr><td colspan="8" class="text-center">' + errorMsg + '</td></tr>';
    } finally {
        // hide loading indicator
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
        if (tableContainer) {
            tableContainer.style.opacity = '1';
        }
    }
}

// initialize real-time search
document.addEventListener('DOMContentLoaded', function() {
    // handle csv upload form with ajax
    var csvForm = document.getElementById('csvUploadForm');
    var csvUploadBtn = document.getElementById('csvUploadBtn');
    
    if (csvForm) {
        csvForm.addEventListener('submit', function(e) {
            e.preventDefault(); // prevent normal form submission
            
            var formData = new FormData(csvForm);
            var originalBtnText = csvUploadBtn.innerHTML;
            
            // disable button and show loading
            csvUploadBtn.disabled = true;
            csvUploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading now...';
            
            // send ajax request - route will be provided from blade
            const uploadRoute = window.userUploadCsvRoute || '/admin/users/upload-csv';
            fetch(uploadRoute, {
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
                // re-enable button
                csvUploadBtn.disabled = false;
                csvUploadBtn.innerHTML = originalBtnText;
                
                // check if success or not
                if (data.success) {
                    // show success toast
                    if (typeof showToast !== 'undefined') {
                        showToast(data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    
                    // show errors if got any
                    if (data.data && data.data.errors && data.data.errors.length > 0) {
                        var errorMessage = 'Got errors:\n' + data.data.errors.join('\n');
                        if (typeof showToast !== 'undefined') {
                            showToast(errorMessage, 'warning');
                        } else {
                            alert(errorMessage);
                        }
                    }
                    
                    // reload page after 2 seconds to show updated user list
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // show error toast
                    const errorMsg = data.message || data.error || 'Cannot upload file';
                    if (typeof showToast !== 'undefined') {
                        showToast(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                }
            })
            .catch(function(error) {
                // re-enable button
                csvUploadBtn.disabled = false;
                csvUploadBtn.innerHTML = originalBtnText;
                
                // show error toast
                const errorMsg = 'Something went wrong: ' + error.message;
                if (typeof showToast !== 'undefined') {
                    showToast(errorMsg, 'error');
                } else {
                    alert(errorMsg);
                }
            });
        });
    }
    
    // real-time search setup
    const searchInput = document.getElementById('userSearchInput');
    const searchClearBtn = document.getElementById('userSearchClear');
    const statusFilter = document.getElementById('statusFilter');
    const roleFilter = document.getElementById('roleFilter');
    const searchForm = document.querySelector('.filters-form');
    
    // prevent form submission, use ajax instead
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(1);
        });
    }
    
    // real-time search on input (debounced)
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            performSearch(1);
        }, 300); // 300ms delay
        
        searchInput.addEventListener('input', function() {
            // show/hide clear button
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
    
    // filter changes trigger search (reset to page 1)
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            window.userCurrentPage = 1;
            performSearch(1);
        });
    }
    
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            window.userCurrentPage = 1;
            performSearch(1);
        });
    }
    
    // add click handlers to sortable headers
    document.querySelectorAll('.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            window.userCurrentPage = 1;
            performSearch(1, sortBy, null);
        });
    });
    
    // Load users on page load using API (replace Blade-rendered data)
    // Get initial filter values from URL or form
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search') || '';
    const initialStatus = urlParams.get('status') || '';
    const initialRole = urlParams.get('role') || '';
    const initialPage = parseInt(urlParams.get('page')) || 1;
    
    // Set filter values if they exist
    if (searchInput && initialSearch) {
        searchInput.value = initialSearch;
    }
    if (statusFilter && initialStatus) {
        statusFilter.value = initialStatus;
    }
    if (roleFilter && initialRole) {
        roleFilter.value = initialRole;
    }
    
    // Load users via API
    window.userCurrentPage = initialPage;
    performSearch(initialPage);
});

// Render pagination controls for users (similar to booking pagination)
function renderUserPagination() {
    if (window.userTotalPages <= 1) {
        return '<div class="pagination-info" style="margin-top: 20px; text-align: center; color: #666;">Showing all users</div>';
    }
    
    const startItem = (window.userCurrentPage - 1) * window.userPerPage + 1;
    const endItem = Math.min(window.userCurrentPage * window.userPerPage, window.userTotalUsers);
    
    let paginationHTML = '<div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
    
    // Pagination info
    paginationHTML += `<div class="pagination-info" style="color: #666;">
        Showing ${startItem} to ${endItem} of ${window.userTotalUsers} users
    </div>`;
    
    // Pagination controls
    paginationHTML += '<div class="pagination-controls" style="display: flex; gap: 5px; align-items: center;">';
    
    // First page
    if (window.userCurrentPage > 1) {
        paginationHTML += `<button onclick="performSearch(1)" class="pagination-btn" title="First page">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }
    
    // Previous page
    if (window.userCurrentPage > 1) {
        paginationHTML += `<button onclick="performSearch(${window.userCurrentPage - 1})" class="pagination-btn" title="Previous page">
            <i class="fas fa-angle-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-left"></i>
        </button>`;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, window.userCurrentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(window.userTotalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button onclick="performSearch(1)" class="pagination-btn">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === window.userCurrentPage) {
            paginationHTML += `<button class="pagination-btn pagination-btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button onclick="performSearch(${i})" class="pagination-btn">${i}</button>`;
        }
    }
    
    if (endPage < window.userTotalPages) {
        if (endPage < window.userTotalPages - 1) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        paginationHTML += `<button onclick="performSearch(${window.userTotalPages})" class="pagination-btn">${window.userTotalPages}</button>`;
    }
    
    // Next page
    if (window.userCurrentPage < window.userTotalPages) {
        paginationHTML += `<button onclick="performSearch(${window.userCurrentPage + 1})" class="pagination-btn" title="Next page">
            <i class="fas fa-angle-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-right"></i>
        </button>`;
    }
    
    // Last page
    if (window.userCurrentPage < window.userTotalPages) {
        paginationHTML += `<button onclick="performSearch(${window.userTotalPages})" class="pagination-btn" title="Last page">
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    }
    
    paginationHTML += '</div></div>';
    
    return paginationHTML;
}
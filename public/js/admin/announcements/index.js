/**
 * Author: Liew Zi Li
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadAnnouncements(null, null, 1);
    
    document.querySelectorAll('.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const sortBy = this.getAttribute('data-sort');
            loadAnnouncements(sortBy, null, 1);
        });
    });
    
    const searchInput = document.getElementById('announcementSearchInput');
    const searchClearBtn = document.getElementById('announcementSearchClear');
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            loadAnnouncements(null, null, 1);
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
    const pageRoot = document.getElementById('adminAnnouncementsPage');

    container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Loading announcements...</td></tr>';
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
    }
    
    if (sortBy !== null) {
        if (currentSortBy === sortBy) {
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortOrder = 'asc';
        }
    }
    
    currentPage = page;
    
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
        displayAnnouncements(announcements, paginationData, pageRoot);
        updateSortIndicators();
    } else {
        container.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #dc3545;">Error loading announcements: ' + (result.error || 'Unknown error') + '</td></tr>';
    }
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    if (tableContainer) {
        tableContainer.style.opacity = '1';
    }
}

function performAnnouncementSearch() {
    loadAnnouncements(null, null, 1);
}

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
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'sort-icon';
        icon.textContent = '';
    });
    
    const currentSortTh = document.querySelector(`th[data-sort="${currentSortBy}"]`);
    if (currentSortTh) {
        const icon = currentSortTh.querySelector('.sort-icon');
        if (icon) {
            icon.className = `sort-icon fas fa-sort-${currentSortOrder === 'asc' ? 'up' : 'down'}`;
        }
    }
}

function displayAnnouncements(announcements, paginationData = null, pageRoot = null) {
    const container = document.getElementById('announcementsList');
    const paginationWrapper = document.getElementById('paginationWrapper');

    const showBase = pageRoot ? pageRoot.dataset.showUrlBase : '';
    const editBase = pageRoot ? pageRoot.dataset.editUrlBase : '';
    
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
        const showUrl = showBase ? `${showBase}/${announcement.id}` : `#/announcements/${announcement.id}`;
        const editUrl = editBase ? `${editBase}/${announcement.id}` : `#/announcements/${announcement.id}/edit`;
        
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
    } else if (paginationWrapper) {
        paginationWrapper.innerHTML = '';
    }
}

// Convert UTC timestamps to local time for display
function formatDateTime(dateString) {
    if (!dateString) return '-';
    
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '-';
    
    // JavaScript Date automatically converts UTC to local time
    // Use local time methods to display in user's timezone
    return d.toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

window.showCreateModal = function() {
    const form = document.getElementById('announcementForm');
    if (form) {
        form.reset();
    }
    const modal = document.getElementById('announcementModal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeModal = function() {
    const modal = document.getElementById('announcementModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

const announcementForm = document.getElementById('announcementForm');
if (announcementForm) {
    announcementForm.addEventListener('submit', async function(e) {
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
            await API.post(`/announcements/${result.data.data.id}/publish`, {});
            
            window.closeModal();
            loadAnnouncements(null, null, 1);
            if (typeof showToast === 'function') {
                showToast('Announcement created and published successfully!', 'success');
            }
        } else if (typeof showToast === 'function') {
            showToast('Error creating announcement: ' + (result.error || 'Unknown error'), 'error');
        } else {
            alert('Error creating announcement: ' + (result.error || 'Unknown error'));
        }
    });
}

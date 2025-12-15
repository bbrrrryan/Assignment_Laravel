// Author: Liew Zi Li (notification management)
// wait for dom and api to be ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('api.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initNotifications();
    initSearch();
});

let currentPage = 1;
let currentPagination = null;
let currentSearch = '';
let searchTimeout;

function initNotifications() {
    loadNotifications(1, '');
}

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

// initialize search functionality
function initSearch() {
    const searchInput = document.getElementById('notificationSearchInput');
    const searchClearBtn = document.getElementById('notificationSearchClear');
    const isAdmin = API.isAdmin();
    
    // only show search for regular users
    if (isAdmin) {
        return;
    }
    
    if (searchInput) {
        // debounced search on input
        const debouncedSearch = debounce(() => {
            const searchTerm = searchInput.value.trim();
            loadNotifications(1, searchTerm);
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
    
    // clear search button
    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                this.style.display = 'none';
                loadNotifications(1, '');
            }
        });
    }
}

async function loadNotifications(page = 1, search = '') {
    const container = document.getElementById('notificationsList');
    container.innerHTML = '<p>Loading...</p>';
    
    currentPage = page;
    currentSearch = search;
    const isAdmin = API.isAdmin();
    
    try {
        let items = [];
        
        if (isAdmin) {
            // for admin: show pending bookings
            document.getElementById('pageTitle').textContent = 'Pending Bookings';
            
            const bookingsResult = await API.get('/bookings/pending?limit=100');
            console.log('bookings api response:', bookingsResult);
            
            if (bookingsResult.success && bookingsResult.data && bookingsResult.data.bookings) {
                const bookings = bookingsResult.data.bookings;
                items = bookings.map(booking => ({
                    id: booking.id,
                    type: 'booking',
                    title: `Booking Request - ${booking.facility_name}`,
                    content: `Booking ${booking.id} from ${booking.user_name} for ${booking.booking_date} ${booking.start_time} - ${booking.end_time}`,
                    created_at: booking.created_at,
                    booking: booking,
                }));
            }
        } else {
            // for regular users: show announcements and notifications
            document.getElementById('pageTitle').textContent = 'Announcements & Notifications';
            const pageSubtitle = document.getElementById('pageSubtitle');
            if (pageSubtitle) {
                pageSubtitle.textContent = 'View and manage your announcements and notifications';
            }
            
            // show search section for regular users only
            const searchSection = document.getElementById('searchSection');
            if (searchSection) {
                searchSection.style.display = 'block';
            }
            
            // get items with pagination (10 per page) and search
            let apiUrl = `/notifications/user/unread-items?per_page=10&page=${page}`;
            if (search) {
                apiUrl += `&search=${encodeURIComponent(search)}`;
            }
            const result = await API.get(apiUrl);
            console.log('unread items api response:', result);
            
            if (result && result.success && result.data) {
                // api.js wraps laravel response, so structure is: result.data.data.items
                const responseData = result.data.data || result.data;
                
                if (responseData && Array.isArray(responseData.items)) {
                    items = responseData.items;
                    currentPagination = responseData.pagination;
                } else {
                    console.error('api response structure wrong:', result);
                    console.error('responseData:', responseData);
                }
            } else {
                console.error('api call failed or returned unsuccessful result:', result);
            }
        }
        
        displayNotifications(items, isAdmin, currentPagination);
        
    } catch (error) {
        container.innerHTML = `<p style="color: red;">Error loading data: ${error.message}</p>`;
        console.error('exception loading data:', error);
    }
}

function displayNotifications(items, isAdmin, pagination = null) {
    const container = document.getElementById('notificationsList');

    if (items.length === 0) {
        container.innerHTML = '<p>no items found</p>';
        return;
    }
    
    if (isAdmin) {
        // display bookings for admin
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
                            <td class="notification-title">${booking.id}</td>
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
            // display announcements and notifications for regular users
            if (items.length === 0) {
                container.innerHTML = '<div class="table-container"><table class="notification-table"><tbody><tr><td colspan="6" class="text-center">no items found</td></tr></tbody></table></div>';
                return;
            }
            
            container.innerHTML = `
            <table class="notification-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
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
                        const isStarred = item.is_starred === true || item.is_starred === 1;
                        const sender = item.creator || 'System';
                        const date = formatDateTime(item.created_at);
                        const hasPivot = item.is_read !== undefined;
                        
                        return `
                        <tr class="notification-row ${isRead ? 'read' : 'unread'} ${isStarred ? 'starred' : ''}" onclick="handleRowClick(event, '${item.type}', ${item.id}, ${isRead}, '${url}')">
                            <td class="star-cell" onclick="event.stopPropagation()">
                                <button class="btn-star ${isStarred ? 'starred' : ''}" onclick="toggleStar('${item.type}', ${item.id}, event)" title="${isStarred ? 'Unstar' : 'Star'}">
                                    <i class="fas fa-star"></i>
                                </button>
                            </td>
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
        
        // render pagination after table
        if (pagination) {
            const paginationHtml = renderPagination(pagination);
            container.innerHTML += paginationHtml;
            // attach event listeners after pagination is rendered
            setTimeout(attachPaginationListeners, 0);
        }
    }
}

function renderPagination(pagination) {
    if (!pagination || pagination.last_page <= 1) {
        return '';
    }
    
    const currentPageNum = pagination.current_page;
    const lastPage = pagination.last_page;
    const total = pagination.total;
    const from = pagination.from || 0;
    const to = pagination.to || 0;
    
    let paginationHtml = '<div class="pagination-wrapper">';
    paginationHtml += `<div class="pagination-info">Showing ${from}-${to} / total ${total} items</div>`;
    paginationHtml += '<div class="pagination-buttons">';
    
    // previous button
    if (currentPageNum > 1) {
        paginationHtml += `<button class="btn-pagination" data-page="${currentPageNum - 1}">
            <i class="fas fa-chevron-left"></i> Previous
        </button>`;
    } else {
        paginationHtml += `<button class="btn-pagination" disabled>
            <i class="fas fa-chevron-left"></i> Previous
        </button>`;
    }
    
    // page numbers
    let startPage = Math.max(1, currentPageNum - 2);
    let endPage = Math.min(lastPage, currentPageNum + 2);
    
    if (startPage > 1) {
        paginationHtml += `<button class="btn-pagination" data-page="1">1</button>`;
        if (startPage > 2) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPageNum) {
            paginationHtml += `<button class="btn-pagination active">${i}</button>`;
        } else {
            paginationHtml += `<button class="btn-pagination" data-page="${i}">${i}</button>`;
        }
    }
    
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            paginationHtml += `<span class="pagination-ellipsis">...</span>`;
        }
        paginationHtml += `<button class="btn-pagination" data-page="${lastPage}">${lastPage}</button>`;
    }
    
    // next button
    if (currentPageNum < lastPage) {
        paginationHtml += `<button class="btn-pagination" data-page="${currentPageNum + 1}">
            Next <i class="fas fa-chevron-right"></i>
        </button>`;
    } else {
        paginationHtml += `<button class="btn-pagination" disabled>
            Next <i class="fas fa-chevron-right"></i>
        </button>`;
    }
    
    paginationHtml += '</div></div>';
    return paginationHtml;
}

// attach pagination event listeners
function attachPaginationListeners() {
    document.querySelectorAll('.pagination-wrapper .btn-pagination[data-page]').forEach(btn => {
        btn.addEventListener('click', function() {
            const page = parseInt(this.getAttribute('data-page'));
            loadNotifications(page, currentSearch);
        });
    });
}

async function toggleStar(type, id, event) {
    if (event) {
        event.stopPropagation();
    }
    
    try {
        const result = await API.put(`/notifications/star/${type}/${id}`, {});
        if (result.success) {
            // reload current page to reflect the change
            loadNotifications(currentPage, currentSearch);
        } else {
            alert('Error: ' + (result.error || 'Failed to toggle star'));
        }
    } catch (error) {
        console.error('error toggling star:', error);
        alert('Error toggling star: ' + error.message);
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

// handle row click - mark as read and navigate
async function handleRowClick(event, type, id, isRead, url) {
    if (event) {
        event.stopPropagation();
    }
    
    // for regular users, mark as read if not already read
    if (!isRead) {
        try {
            if (type === 'announcement') {
                await API.put(`/announcements/${id}/read`, {});
            } else if (type === 'notification') {
                await API.put(`/notifications/${id}/read`, {});
            }
        } catch (error) {
            console.error('error marking as read:', error);
        }
    }
    
    // navigate to detail page
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
            loadNotifications(currentPage, currentSearch);
            const typeLabel = type === 'announcement' ? 'Announcement' : 'Notification';
            if (typeof showToast !== 'undefined') {
                showToast(`${typeLabel} marked as unread already`, 'success');
            } else {
                alert(`${typeLabel} marked as unread already`);
            }
        } else {
            const errorMsg = 'Error: ' + (result?.message || result?.error || 'Unknown error');
            if (typeof showToast !== 'undefined') {
                showToast(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('error marking as unread:', error);
        const errorMsg = 'Error marking as unread: ' + (error.message || 'Unknown error');
        if (typeof showToast !== 'undefined') {
            showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
    }
};

// admin functions for booking approval/rejection
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
            if (typeof showToast !== 'undefined') {
                showToast('Booking approved successfully', 'success');
            } else {
                alert('Booking approved successfully');
            }
            loadNotifications();
        } else {
            const errorMsg = 'Error: ' + (result.error || 'Unknown error');
            if (typeof showToast !== 'undefined') {
                showToast(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('error approving booking:', error);
        if (typeof showToast !== 'undefined') {
            showToast('Error approving booking', 'error');
        } else {
            alert('Error approving booking');
        }
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
            if (typeof showToast !== 'undefined') {
                showToast('Booking rejected successfully', 'success');
            } else {
                alert('Booking rejected successfully');
            }
            loadNotifications();
        } else {
            const errorMsg = 'Error: ' + (result.error || 'Unknown error');
            if (typeof showToast !== 'undefined') {
                showToast(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('error rejecting booking:', error);
        if (typeof showToast !== 'undefined') {
            showToast('Error rejecting booking', 'error');
        } else {
            alert('Error rejecting booking');
        }
    }
};
/**
 * Author: Low Kim Hong
 */

let bookings = [];
let facilities = [];
let sortOrder = null; 

let currentPage = 1;
let perPage = 15;
let totalPages = 1;
let totalBookings = 0;

window.sortByDate = function() {
    if (sortOrder === 'date-asc') {
        sortOrder = 'date-desc';
    } else if (sortOrder === 'date-desc') {
        sortOrder = 'date-asc';
    } else {
        sortOrder = 'date-asc';
    }
    
    filterBookings();
};

window.sortByCreatedDate = function() {
    const previousSort = sortOrder;
    
    if (sortOrder === 'created-desc') {
        sortOrder = 'created-asc';
    } else if (sortOrder === 'created-asc') {
        sortOrder = 'created-desc';
    } else {
        sortOrder = 'created-desc';
    }
    
    console.log('sortByCreatedDate: Changed from', previousSort, 'to', sortOrder);
    
    filterBookings();
};

window.filterBookings = function() {
    currentPage = 1;
    loadBookings(1);
};

window.viewBooking = function(id) {
    window.location.href = `/bookings/${id}`;
};

let currentBookingId = null;

window.cancelBooking = function(id) {
    currentBookingId = id;
    
    initCancelModalListeners();
    
    document.getElementById('cancelReason').value = '';
    document.getElementById('customCancelReason').value = '';
    document.getElementById('customCancelReason').style.display = 'none';
    document.getElementById('confirmCancelBtn').disabled = true;
    
    document.getElementById('cancelBookingModal').style.display = 'flex';
};

function closeCancelModal() {
    document.getElementById('cancelBookingModal').style.display = 'none';
    currentBookingId = null;
}

function handleReasonChange() {
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
    const confirmBtn = document.getElementById('confirmCancelBtn');
    
    if (reasonSelect.value === 'other') {
        customReason.style.display = 'block';
        customReason.required = true;
    } else {
        customReason.style.display = 'none';
        customReason.required = false;
    }
    
    confirmBtn.disabled = !reasonSelect.value || (reasonSelect.value === 'other' && !customReason.value.trim());
}

function initCancelModalListeners() {
    const customReason = document.getElementById('customCancelReason');
    if (customReason && !customReason.hasAttribute('data-listener-added')) {
        customReason.setAttribute('data-listener-added', 'true');
        customReason.addEventListener('input', function() {
            const reasonSelect = document.getElementById('cancelReason');
            const confirmBtn = document.getElementById('confirmCancelBtn');
            if (reasonSelect && reasonSelect.value === 'other') {
                confirmBtn.disabled = !this.value.trim();
            }
        });
    }
}

async function confirmCancelBooking() {
    const bookingId = currentBookingId;
    
    if (!bookingId) {
        if (typeof showToast !== 'undefined') {
            showToast('Error: Booking ID is missing. Please try again.', 'error');
        } else {
            alert('Error: Booking ID is missing. Please try again.');
        }
        return;
    }
    
    if (typeof API === 'undefined') {
        if (typeof showToast !== 'undefined') {
            showToast('API not loaded', 'error');
        } else {
            alert('API not loaded');
        }
        return;
    }
    
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
    
    if (!reasonSelect.value) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select a reason for cancellation.', 'warning');
        } else {
            alert('Please select a reason for cancellation.');
        }
        return;
    }
    
    if (reasonSelect.value === 'other' && !customReason.value.trim()) {
        if (typeof showToast !== 'undefined') {
            showToast('Please provide a reason for cancellation.', 'warning');
        } else {
            alert('Please provide a reason for cancellation.');
        }
        return;
    }
    
    const reasonText = reasonSelect.value === 'other' 
        ? customReason.value.trim()
        : reasonSelect.options[reasonSelect.selectedIndex].text;
    
    const confirmBtn = document.getElementById('confirmCancelBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    
    closeCancelModal();
    
    try {
        const result = await API.put(`/bookings/${bookingId}/cancel`, { reason: reasonText });
        
        if (result.success) {
            if (typeof loadBookings === 'function') {
                loadBookings(currentPage || 1);
            }
            if (typeof showToast !== 'undefined') {
                showToast('Booking cancelled successfully!', 'success');
            } else {
                alert('Booking cancelled successfully!');
            }
        } else {
            alert('Error: ' + (result.error || 'Failed to cancel booking. Please try again.'));
        }
    } catch (error) {
        alert('Error: ' + (error.message || 'An unexpected error occurred. Please try again.'));
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Cancellation';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    const bookingsList = document.getElementById('bookingsList');
    
    if (bookingsList) {
        initBookings();
    }
});

function initBookings() {
    const bookingsTitle = document.getElementById('bookingsTitle');
    const bookingsSubtitle = document.getElementById('bookingsSubtitle');
    if (bookingsTitle) {
        bookingsTitle.textContent = 'My Bookings';
    }
    if (bookingsSubtitle) {
        bookingsSubtitle.textContent = 'Manage your facility bookings';
    }
    const newBookingBtn = document.getElementById('newBookingBtn');
    if (newBookingBtn) {
        newBookingBtn.style.display = 'block';
    }
    
    loadBookings(currentPage || 1);
    loadFacilitiesForFilter();
}

async function loadBookings(page = 1) {
    showLoading(document.getElementById('bookingsList'));
    currentPage = page;
    
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const facilityFilter = document.getElementById('facilityFilter');
    
    const search = searchInput ? searchInput.value.trim() : '';
    const status = statusFilter ? statusFilter.value : '';
    const facilityId = facilityFilter ? facilityFilter.value : '';
    
    const params = new URLSearchParams({
        page: page,
        per_page: perPage
    });
    
    if (search) {
        params.append('search', search);
    }
    if (status) {
        params.append('status', status);
    }
    if (facilityId) {
        params.append('facility_id', facilityId);
    }
    
    if (sortOrder) {
        if (sortOrder.startsWith('date-')) {
            params.append('sort_by', 'date');
            params.append('sort_order', sortOrder === 'date-asc' ? 'asc' : 'desc');
        } else if (sortOrder.startsWith('created-')) {
            params.append('sort_by', 'created_at');
            params.append('sort_order', sortOrder === 'created-asc' ? 'asc' : 'desc');
        }
    }
    
    const endpoint = `/bookings/user/my-bookings?${params.toString()}`;
    const result = await API.get(endpoint);
    
    if (result.success) {
        const responseData = result.data.data || result.data;
        
        if (responseData.data && Array.isArray(responseData.data)) {
            bookings = responseData.data;
            currentPage = responseData.current_page || page;
            totalPages = responseData.last_page || 1;
            totalBookings = responseData.total || 0;
            perPage = responseData.per_page || perPage;
        } else if (Array.isArray(responseData)) {
            bookings = responseData;
            currentPage = 1;
            totalPages = 1;
            totalBookings = responseData.length;
        } else {
            bookings = [];
            currentPage = 1;
            totalPages = 1;
            totalBookings = 0;
        }
        
        if (bookings.length === 0) {
            document.getElementById('bookingsList').innerHTML = '<p>No bookings found. Create your first booking!</p>';
        } else {
            displayBookings(bookings);
        }
    } else {
        const errorMsg = result.error || result.data?.message || 'Failed to load bookings';
        showError(document.getElementById('bookingsList'), errorMsg);
        console.error('Load bookings error:', result); 
    }
}

async function loadFacilitiesForFilter() {
    const result = await API.get('/facilities?per_page=100'); 
    
    if (result.success) {
        const facilitiesList = result.data.data?.data || result.data.data || [];
        const filterSelect = document.getElementById('facilityFilter');
        
        if (filterSelect && facilitiesList.length > 0) {
            filterSelect.innerHTML = '<option value="">All Facilities</option>' +
                facilitiesList.map(f => 
                    `<option value="${f.id}">${f.name}</option>`
                ).join('');
        }
    }
}

function showLoading(container) {
    if (container) {
        container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

function showError(container, message) {
    if (container) {
        container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${message}</div>`;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const date = formatDate(dateTimeString);
    const time = formatTimeNoSeconds(dateTimeString);
    return `${date} ${time}`;
}

function displayBookings(bookingsToShow) {
    const container = document.getElementById('bookingsList');
    if (bookingsToShow.length === 0) {
        container.innerHTML = '<div class="table-container"><table class="data-table"><tbody><tr><td colspan="8" class="text-center">No bookings found</td></tr></tbody></table></div>';
        return;
    }

    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Facility</th>
                    <th>
                        <div style="display: flex; align-items: center; gap: 5px; cursor: pointer;" onclick="sortByDate()">
                            <span>Date</span>
                            <i class="fas ${sortOrder === 'date-asc' ? 'fa-sort-up' : 'fa-sort-down'} sort-arrow ${sortOrder && sortOrder.startsWith('date-') ? 'active' : ''}"></i>
                        </div>
                    </th>
                    <th>Time</th>
                    <th>Attendees</th>
                    <th>Status</th>
                    <th>
                        <div style="display: flex; align-items: center; gap: 5px; cursor: pointer;" onclick="sortByCreatedDate()">
                            <span>Created Date</span>
                            <i class="fas ${sortOrder === 'created-asc' ? 'fa-sort-up' : 'fa-sort-down'} sort-arrow ${sortOrder && sortOrder.startsWith('created-') ? 'active' : ''}"></i>
                        </div>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${bookingsToShow.map(booking => `
                    <tr>
                        <td>${booking.id}</td>
                        <td>${booking.facility?.name || 'N/A'}</td>
                        <td>${formatDate(booking.booking_date)}</td>
                        <td>${formatTimeNoSeconds(booking.start_time)} - ${formatTimeNoSeconds(booking.end_time)}</td>
                        <td>${booking.expected_attendees || 'N/A'}</td>
                        <td>
                            <span class="badge badge-${booking.status === 'approved' ? 'success' : (booking.status === 'pending' ? 'warning' : (booking.status === 'rejected' ? 'danger' : 'secondary'))}">
                                ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                        </td>
                        <td>${formatDateTime(booking.created_at)}</td>
                        <td class="actions">
                            <button class="btn-sm btn-info" onclick="viewBooking(${booking.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${booking.status === 'pending' ? `
                                <button class="btn-sm btn-danger" onclick="cancelBooking(${booking.id})" title="Cancel">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        ${renderPagination()}
    `;
}

function renderPagination() {
    if (totalPages <= 1) {
        return '<div class="pagination-info" style="margin-top: 20px; text-align: center; color: #666;">Showing all bookings</div>';
    }
    
    const startItem = (currentPage - 1) * perPage + 1;
    const endItem = Math.min(currentPage * perPage, totalBookings);
    
    let paginationHTML = '<div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
    
    paginationHTML += `<div class="pagination-info" style="color: #666;">
        Showing ${startItem} to ${endItem} of ${totalBookings} bookings
    </div>`;
    
    paginationHTML += '<div class="pagination-controls" style="display: flex; gap: 5px; align-items: center;">';
    
    if (currentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(1)" class="pagination-btn" title="First page">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }
    
    if (currentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(${currentPage - 1})" class="pagination-btn" title="Previous page">
            <i class="fas fa-angle-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-left"></i>
        </button>`;
    }
    
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button onclick="loadBookings(1)" class="pagination-btn">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHTML += `<button class="pagination-btn pagination-btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button onclick="loadBookings(${i})" class="pagination-btn">${i}</button>`;
        }
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        paginationHTML += `<button onclick="loadBookings(${totalPages})" class="pagination-btn">${totalPages}</button>`;
    }
    
    if (currentPage < totalPages) {
        paginationHTML += `<button onclick="loadBookings(${currentPage + 1})" class="pagination-btn" title="Next page">
            <i class="fas fa-angle-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-right"></i>
        </button>`;
    }
    
    if (currentPage < totalPages) {
        paginationHTML += `<button onclick="loadBookings(${totalPages})" class="pagination-btn" title="Last page">
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

let bookings = [];
let facilities = [];
let sortOrder = null; // 'date-asc', 'date-desc', 'created-asc', 'created-desc', or null

// Pagination state
let currentPage = 1;
let perPage = 15;
let totalPages = 1;
let totalBookings = 0;

window.sortByDate = function() {
    // Toggle sort order: asc -> desc -> asc (only two states)
    // If currently sorting by date, toggle between asc and desc
    // Otherwise, start with asc
    if (sortOrder === 'date-asc') {
        sortOrder = 'date-desc';
    } else if (sortOrder === 'date-desc') {
        sortOrder = 'date-asc';
    } else {
        // First click or switching from other column - start with asc
        sortOrder = 'date-asc';
    }
    
    // Re-apply filters and sorting
    filterBookings();
};

window.sortByCreatedDate = function() {
    // Toggle sort order: desc -> asc -> desc (only two states)
    // Start with desc on first click to show immediate change
    const previousSort = sortOrder;
    
    if (sortOrder === 'created-desc') {
        sortOrder = 'created-asc';
    } else if (sortOrder === 'created-asc') {
        sortOrder = 'created-desc';
    } else {
        // First click or switching from other column - start with desc to show change
        sortOrder = 'created-desc';
    }
    
    console.log('sortByCreatedDate: Changed from', previousSort, 'to', sortOrder);
    
    // Re-apply filters and sorting
    filterBookings();
};

window.filterBookings = function() {
    // Reset to page 1 when filtering
    currentPage = 1;
    // Reload bookings from server with filters
    loadBookings(1);
};

window.viewBooking = function(id) {
    window.location.href = `/bookings/${id}`;
};

let currentBookingId = null;

window.cancelBooking = function(id) {
    currentBookingId = id;
    
    // Initialize listeners if not already done
    initCancelModalListeners();
    
    // Reset modal
    document.getElementById('cancelReason').value = '';
    document.getElementById('customCancelReason').value = '';
    document.getElementById('customCancelReason').style.display = 'none';
    document.getElementById('confirmCancelBtn').disabled = true;
    
    // Show modal
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
    
    // Enable confirm button if reason is selected
    confirmBtn.disabled = !reasonSelect.value || (reasonSelect.value === 'other' && !customReason.value.trim());
}

// Enable confirm button when custom reason is typed
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
    // Save booking ID before closing modal
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
    
    // Build reason text
    const reasonText = reasonSelect.value === 'other' 
        ? customReason.value.trim()
        : reasonSelect.options[reasonSelect.selectedIndex].text;
    
    // Disable confirm button
    const confirmBtn = document.getElementById('confirmCancelBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    
    // Close modal
    closeCancelModal();
    
    try {
        const result = await API.put(`/bookings/${bookingId}/cancel`, { reason: reasonText });
        
        if (result.success) {
            // Reload bookings list (stay on current page)
            if (typeof loadBookings === 'function') {
                loadBookings(currentPage || 1);
            }
            // Show success message
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

// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if API is loaded
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    // Check authentication
    if (!API.requireAuth()) return;

    // Check if we're on the bookings index page
    const bookingsList = document.getElementById('bookingsList');
    
    if (bookingsList) {
        // This is the bookings index page
        initBookings();
    }
});

function initBookings() {
    // Student: My Bookings (only their own bookings)
    // Only set these if the elements exist (not on admin page)
    const bookingsTitle = document.getElementById('bookingsTitle');
    const bookingsSubtitle = document.getElementById('bookingsSubtitle');
    if (bookingsTitle) {
        bookingsTitle.textContent = 'My Bookings';
    }
    if (bookingsSubtitle) {
        bookingsSubtitle.textContent = 'Manage your facility bookings';
    }
    // Show "New Booking" button for students
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
    
    // Get filter values
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const facilityFilter = document.getElementById('facilityFilter');
    
    const search = searchInput ? searchInput.value.trim() : '';
    const status = statusFilter ? statusFilter.value : '';
    const facilityId = facilityFilter ? facilityFilter.value : '';
    
    // Build query parameters
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
    
    // Apply sorting if sortOrder is set
    if (sortOrder) {
        if (sortOrder.startsWith('date-')) {
            params.append('sort_by', 'date');
            params.append('sort_order', sortOrder === 'date-asc' ? 'asc' : 'desc');
        } else if (sortOrder.startsWith('created-')) {
            params.append('sort_by', 'created_at');
            params.append('sort_order', sortOrder === 'created-asc' ? 'asc' : 'desc');
        }
    }
    
    // Students can only view their own bookings
    const endpoint = `/bookings/user/my-bookings?${params.toString()}`;
    const result = await API.get(endpoint);
    
    if (result.success) {
        // Handle paginated response
        const responseData = result.data.data || result.data;
        
        if (responseData.data && Array.isArray(responseData.data)) {
            // Paginated response
            bookings = responseData.data;
            currentPage = responseData.current_page || page;
            totalPages = responseData.last_page || 1;
            totalBookings = responseData.total || 0;
            perPage = responseData.per_page || perPage;
        } else if (Array.isArray(responseData)) {
            // Non-paginated response (fallback)
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
        console.error('Load bookings error:', result); // Debug
    }
}

// Load facilities for filter dropdown
async function loadFacilitiesForFilter() {
    const result = await API.get('/facilities?per_page=100'); // Load more facilities for filter
    
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

// Show loading state
function showLoading(container) {
    if (container) {
        container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

// Show error message
function showError(container, message) {
    if (container) {
        container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${message}</div>`;
    }
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return 'N/A';
    
    // JavaScript Date automatically converts UTC to local time
    // Use local time methods to display in user's timezone
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Format Date object to 12-hour format without seconds
// Handle timezone issues by extracting time directly from the datetime string
function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    // Always use Date object to properly handle timezone conversion
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    // JavaScript Date automatically converts UTC to local time
    // Use local time methods to display in user's timezone
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// Format date and time
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    // JavaScript Date automatically converts UTC to local time
    // Use local time methods to display in user's timezone
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

// Render pagination controls
function renderPagination() {
    if (totalPages <= 1) {
        return '<div class="pagination-info" style="margin-top: 20px; text-align: center; color: #666;">Showing all bookings</div>';
    }
    
    const startItem = (currentPage - 1) * perPage + 1;
    const endItem = Math.min(currentPage * perPage, totalBookings);
    
    let paginationHTML = '<div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
    
    // Pagination info
    paginationHTML += `<div class="pagination-info" style="color: #666;">
        Showing ${startItem} to ${endItem} of ${totalBookings} bookings
    </div>`;
    
    // Pagination controls
    paginationHTML += '<div class="pagination-controls" style="display: flex; gap: 5px; align-items: center;">';
    
    // First page
    if (currentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(1)" class="pagination-btn" title="First page">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }
    
    // Previous page
    if (currentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(${currentPage - 1})" class="pagination-btn" title="Previous page">
            <i class="fas fa-angle-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-left"></i>
        </button>`;
    }
    
    // Page numbers
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
    
    // Next page
    if (currentPage < totalPages) {
        paginationHTML += `<button onclick="loadBookings(${currentPage + 1})" class="pagination-btn" title="Next page">
            <i class="fas fa-angle-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-right"></i>
        </button>`;
    }
    
    // Last page
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

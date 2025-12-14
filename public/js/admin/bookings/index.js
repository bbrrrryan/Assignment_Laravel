let bookings = [];
let facilities = [];
let sortOrder = null; // 'date-asc', 'date-desc', 'created-asc', 'created-desc', or null

// Sorting functions
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
    if (sortOrder === 'created-desc') {
        sortOrder = 'created-asc';
    } else if (sortOrder === 'created-asc') {
        sortOrder = 'created-desc';
    } else {
        sortOrder = 'created-desc';
    }
    filterBookings();
};

// Filter bookings
window.filterBookings = function() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter');
    const facilityFilter = document.getElementById('facilityFilter');
    const rescheduleFilter = document.getElementById('rescheduleFilter');
    if (!statusFilter) return;
    const status = statusFilter.value;
    const facilityId = facilityFilter ? facilityFilter.value : '';
    const rescheduleStatus = rescheduleFilter ? rescheduleFilter.value : '';
    
    let filtered = bookings.filter(b => {
        const matchSearch = !search || 
            (b.booking_number && b.booking_number.toLowerCase().includes(search)) ||
            (b.facility?.name && b.facility.name.toLowerCase().includes(search)) ||
            (b.user?.name && b.user.name.toLowerCase().includes(search)) ||
            (b.purpose && b.purpose.toLowerCase().includes(search));
        const matchStatus = !status || b.status === status;
        const matchFacility = !facilityId || b.facility_id == facilityId;
        const matchReschedule = !rescheduleStatus || b.reschedule_status === rescheduleStatus;
        return matchSearch && matchStatus && matchFacility && matchReschedule;
    });
    
    // Apply sorting
    if (sortOrder) {
        const sortedArray = [...filtered].sort((a, b) => {
            if (sortOrder.startsWith('date-')) {
                const dateA = new Date(a.booking_date);
                const dateB = new Date(b.booking_date);
                
                if (sortOrder === 'date-asc') {
                    return dateA.getTime() - dateB.getTime();
                } else if (sortOrder === 'date-desc') {
                    return dateB.getTime() - dateA.getTime();
                }
            } else if (sortOrder.startsWith('created-')) {
                const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                
                const timeA = isNaN(dateA.getTime()) ? 0 : dateA.getTime();
                const timeB = isNaN(dateB.getTime()) ? 0 : dateB.getTime();
                
                if (sortOrder === 'created-asc') {
                    return timeA - timeB;
                } else if (sortOrder === 'created-desc') {
                    return timeB - timeA;
                }
            }
            return 0;
        });
        
        filtered = sortedArray;
    }
    
    displayBookings(filtered);
};

// View booking
window.viewBooking = function(id) {
    window.location.href = `/bookings/${id}`;
};

// Dropdown toggle
document.addEventListener('click', function(event) {
    if (event.target.closest('.user-dropdown')) {
        return;
    }
    
    if (!event.target.closest('.dropdown-menu-container')) {
        document.querySelectorAll('.dropdown-menu-container .dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

window.toggleBookingDropdown = function(id) {
    event.stopPropagation();
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    const button = event.target.closest('button');
    const allDropdowns = document.querySelectorAll('.dropdown-menu-container .dropdown-menu');
    
    allDropdowns.forEach(menu => {
        if (menu.id !== `booking-dropdown-${id}` && menu.id !== `dropdown-${id}`) {
            menu.classList.remove('show');
            menu.classList.remove('dropdown-up');
        }
    });
    
    const isShowing = dropdown.classList.contains('show');
    
    if (!isShowing) {
        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = 90;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        
        dropdown.style.display = 'block';
        dropdown.style.position = 'fixed';
        dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        
        const dropdownRect = dropdown.getBoundingClientRect();
        const spaceBelow = viewportHeight - dropdownRect.bottom;
        const spaceAbove = buttonRect.top;
        
        if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
            dropdown.classList.add('dropdown-up');
            dropdown.style.top = `${buttonRect.top - dropdownHeight - 5}px`;
        } else {
            dropdown.classList.remove('dropdown-up');
        }
        
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
        dropdown.classList.remove('dropdown-up');
        dropdown.style.display = 'none';
    }
};

window.toggleDropdown = function(id) {
    event.stopPropagation();
    const dropdown = document.getElementById(`dropdown-${id}`);
    const button = event.target.closest('button');
    const allDropdowns = document.querySelectorAll('.dropdown-menu-container .dropdown-menu');
    
    allDropdowns.forEach(menu => {
        if (menu.id !== `dropdown-${id}`) {
            menu.classList.remove('show');
            menu.classList.remove('dropdown-up');
        }
    });
    
    const isShowing = dropdown.classList.contains('show');
    
    if (!isShowing) {
        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = 90;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        
        dropdown.style.display = 'block';
        dropdown.style.position = 'fixed';
        dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        
        const dropdownRect = dropdown.getBoundingClientRect();
        const spaceBelow = viewportHeight - dropdownRect.bottom;
        const spaceAbove = buttonRect.top;
        
        if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
            dropdown.classList.add('dropdown-up');
            dropdown.style.top = `${buttonRect.top - dropdownHeight - 5}px`;
        } else {
            dropdown.classList.remove('dropdown-up');
        }
        
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
        dropdown.classList.remove('dropdown-up');
        dropdown.style.display = 'none';
    }
};

// Approve booking
window.approveBooking = async function(id) {
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    if (!confirm('Are you sure you want to approve this booking?')) return;
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/approve`);
    if (result.success) {
        loadBookings();
        alert('Booking approved successfully!');
    } else {
        alert(result.error || 'Error approving booking');
    }
};

// Reject booking
window.rejectBooking = async function(id) {
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    const reason = prompt('Please provide a reason for rejection:');
    if (reason === null) return;
    if (reason.trim() === '') {
        alert('Reason is required');
        return;
    }
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/reject`, { reason: reason });
    if (result.success) {
        loadBookings();
        alert('Booking rejected successfully!');
    } else {
        alert(result.error || 'Error rejecting booking');
    }
};

// Edit booking (admin can edit any booking)
window.editBooking = async function(id) {
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    
    const result = await API.get(`/bookings/${id}`);
    if (!result.success) {
        alert('Error loading booking details: ' + (result.error || 'Unknown error'));
        return;
    }
    
    // For now, redirect to show page where admin can see details
    // In future, can add edit modal here
    window.location.href = `/bookings/${id}`;
};

// Approve reschedule request
window.approveReschedule = async function(id) {
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    if (!confirm('Are you sure you want to approve this reschedule request?')) return;
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/approve-reschedule`);
    if (result.success) {
        loadBookings();
        alert('Reschedule request approved successfully!');
    } else {
        alert(result.error || 'Error approving reschedule request');
    }
};

// Reject reschedule request
window.rejectReschedule = async function(id) {
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    const reason = prompt('Please provide a reason for rejecting the reschedule request:');
    if (reason === null) return;
    if (reason.trim() === '') {
        alert('Reason is required');
        return;
    }
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/reject-reschedule`, { reason: reason });
    if (result.success) {
        loadBookings();
        alert('Reschedule request rejected successfully!');
    } else {
        alert(result.error || 'Error rejecting reschedule request');
    }
};

// Format functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    if (typeof date === 'string') {
        const timeMatch = date.match(/(\d{1,2}):(\d{2}):(\d{2})/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hour12 = hours % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    }
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    const isUTC = typeof date === 'string' && date.includes('Z');
    const hours = isUTC ? d.getUTCHours() : d.getHours();
    const minutes = String(isUTC ? d.getUTCMinutes() : d.getMinutes()).padStart(2, '0');
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

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initBookings();
});

function initBookings() {
    loadBookings();
    loadFacilitiesForFilter();
}

// Load all bookings (admin endpoint)
async function loadBookings() {
    showLoading(document.getElementById('bookingsList'));
    
    // Request all bookings with a high per_page limit
    const result = await API.get('/bookings?per_page=1000');
    
    if (result.success) {
        // Handle paginated response structure
        // Laravel paginate() returns: { data: [...], current_page: 1, ... }
        // Controller wraps it: { data: { data: [...], current_page: 1, ... } }
        let bookingsData = result.data;
        
        console.log('API Response structure:', bookingsData);
        
        // If data.data exists and is an array, use it (direct array response)
        // If data.data exists and has a .data property, use data.data.data (paginated response)
        if (bookingsData && bookingsData.data) {
            if (Array.isArray(bookingsData.data)) {
                bookings = bookingsData.data;
            } else if (bookingsData.data.data && Array.isArray(bookingsData.data.data)) {
                bookings = bookingsData.data.data;
            } else if (Array.isArray(bookingsData)) {
                bookings = bookingsData;
            } else {
                bookings = [];
            }
        } else if (Array.isArray(bookingsData)) {
            bookings = bookingsData;
        } else {
            bookings = [];
        }
        
        console.log('Loaded bookings count:', bookings.length);
        console.log('First booking sample:', bookings[0]);
        
        if (bookings.length === 0) {
            document.getElementById('bookingsList').innerHTML = '<p>No bookings found.</p>';
        } else {
            displayBookings(bookings);
        }
    } else {
        const errorMsg = result.error || result.data?.message || 'Failed to load bookings';
        showError(document.getElementById('bookingsList'), errorMsg);
        console.error('Load bookings error:', result);
    }
}

// Load facilities for filter
async function loadFacilitiesForFilter() {
    const result = await API.get('/facilities');
    
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

// Display bookings table
function displayBookings(bookingsToShow) {
    const container = document.getElementById('bookingsList');
    if (bookingsToShow.length === 0) {
        container.innerHTML = '<div class="table-container"><table class="data-table"><tbody><tr><td colspan="10" class="text-center">No bookings found</td></tr></tbody></table></div>';
        return;
    }

    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>User</th>
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
                    <th>Reschedule</th>
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
                ${bookingsToShow.map(booking => {
                    let rescheduleBadge = '';
                    if (booking.reschedule_status === 'pending') {
                        rescheduleBadge = '<span class="badge badge-warning" title="Reschedule request pending"><i class="fas fa-clock"></i> Pending</span>';
                    } else if (booking.reschedule_status === 'approved') {
                        rescheduleBadge = '<span class="badge badge-success"><i class="fas fa-check"></i> Approved</span>';
                    } else if (booking.reschedule_status === 'rejected') {
                        rescheduleBadge = '<span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>';
                    }
                    
                    return `
                    <tr>
                        <td>${booking.booking_number}</td>
                        <td>${booking.user?.name || 'N/A'}</td>
                        <td>${booking.facility?.name || 'N/A'}</td>
                        <td>${formatDate(booking.booking_date)}</td>
                        <td>${formatTimeNoSeconds(booking.start_time)} - ${formatTimeNoSeconds(booking.end_time)}</td>
                        <td>${booking.expected_attendees || 'N/A'}</td>
                        <td>
                            <span class="badge badge-${booking.status === 'approved' ? 'success' : (booking.status === 'pending' ? 'warning' : (booking.status === 'rejected' ? 'danger' : 'secondary'))}">
                                ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                        </td>
                        <td>${rescheduleBadge}</td>
                        <td>${formatDateTime(booking.created_at)}</td>
                        <td class="actions">
                            <button class="btn-sm btn-info" onclick="viewBooking(${booking.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-sm btn-warning" onclick="editBooking(${booking.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${booking.reschedule_status === 'pending' ? `
                                <div class="dropdown-menu-container">
                                    <button class="btn-sm btn-secondary" onclick="toggleDropdown(${booking.id})" title="More Actions" style="position: relative;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-${booking.id}">
                                        <button class="dropdown-item" onclick="approveReschedule(${booking.id})">
                                            <i class="fas fa-check text-success"></i> Approve Reschedule
                                        </button>
                                        <button class="dropdown-item" onclick="rejectReschedule(${booking.id})">
                                            <i class="fas fa-times text-danger"></i> Reject Reschedule
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                            ${booking.status === 'pending' ? `
                                <div class="dropdown-menu-container" style="display: inline-block;">
                                    <button class="btn-sm btn-secondary" onclick="toggleBookingDropdown(${booking.id})" title="Booking Actions" style="position: relative;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="booking-dropdown-${booking.id}">
                                        <button class="dropdown-item" onclick="approveBooking(${booking.id})">
                                            <i class="fas fa-check text-success"></i> Approve
                                        </button>
                                        <button class="dropdown-item" onclick="rejectBooking(${booking.id})">
                                            <i class="fas fa-times text-danger"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                        </td>
                    </tr>
                `;
                }).join('')}
            </tbody>
        </table>
    `;
}


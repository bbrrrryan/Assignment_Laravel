@extends('layouts.app')

@section('title', 'Bookings - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 id="bookingsTitle">My Bookings</h1>
            <p id="bookingsSubtitle">Manage your facility bookings</p>
        </div>
        <div>
            <button id="newBookingBtn" class="btn-header-white" onclick="showCreateModal()" style="display: none;">
                <i class="fas fa-plus"></i> New Booking
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <div class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search by booking number, facility or purpose..." 
                           class="filter-input" onkeyup="filterBookings()">
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <select id="facilityFilter" class="filter-select" onchange="filterBookings()">
                        <option value="">All Facilities</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <select id="statusFilter" class="filter-select" onchange="filterBookings()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Table -->
    <div id="bookingsList" class="table-container">
        <p>Loading bookings...</p>
    </div>
</div>

<!-- Cancel Booking Confirmation Modal -->
<div id="cancelBookingModal" class="cancel-modal" style="display: none;" onclick="if(event.target === this) closeCancelModal()">
    <div class="cancel-modal-content" onclick="event.stopPropagation()">
        <div class="cancel-modal-header">
            <div class="cancel-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Cancel Booking Confirmation</h3>
            <span class="cancel-modal-close" onclick="closeCancelModal()">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <p class="cancel-warning-text">
                Are you sure you want to cancel this booking? This action cannot be undone.
            </p>
            <div class="cancel-reason-section">
                <label for="cancelReason" class="cancel-reason-label">
                    <i class="fas fa-comment-alt"></i> Reason for Cancellation <span class="text-danger">*</span>
                </label>
                <select id="cancelReason" class="cancel-reason-select" onchange="handleReasonChange()">
                    <option value="">Select a reason...</option>
                    <option value="schedule_conflict">Schedule Conflict</option>
                    <option value="no_longer_needed">No Longer Needed</option>
                    <option value="found_alternative">Found Alternative Facility</option>
                    <option value="event_cancelled">Event Cancelled</option>
                    <option value="insufficient_attendees">Insufficient Attendees</option>
                    <option value="facility_issue">Facility Issue</option>
                    <option value="other">Other (Please specify)</option>
                </select>
                <textarea 
                    id="customCancelReason" 
                    class="cancel-custom-reason" 
                    placeholder="Please provide additional details..."
                    style="display: none;"
                    rows="3"
                ></textarea>
            </div>
        </div>
        <div class="cancel-modal-footer">
            <button class="btn-cancel-modal" onclick="closeCancelModal()">
                <i class="fas fa-times"></i> Keep Booking
            </button>
            <button class="btn-confirm-cancel" onclick="confirmCancelBooking()" id="confirmCancelBtn" disabled>
                <i class="fas fa-check"></i> Confirm Cancellation
            </button>
        </div>
    </div>
</div>

<!-- Create/Edit Booking Modal -->
<div id="bookingModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeModal()">&times;</span>
        
        <!-- Form Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-plus-circle me-2 text-primary" id="modalIcon"></i><span id="modalTitle">Create New Booking</span>
                </h5>
            </div>
            <div class="card-body">
                <form id="bookingForm">
                    <!-- Basic Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle me-2"></i>Basic Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="bookingFacility" class="form-label">Facility <span class="text-danger">*</span></label>
                                <select id="bookingFacility" class="form-select" required onchange="handleFacilityChange(this)">
                                    <option value="">Select Facility</option>
                                </select>
                                <small class="form-text text-muted">Select a facility to view available time slots for the next 3 days</small>
                            </div>
                        </div>
                    </div>

                    <!-- Time Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-clock me-2"></i>Time Information
                        </h5>
                        

                        <!-- Visual Timetable -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Select Time Slot <span class="text-danger">*</span></label>
                                <div id="timetableContainer" class="timetable-container">
                                    <div class="timetable-loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...
                                    </div>
                                </div>
                                <input type="hidden" id="bookingStartTime" required>
                                <input type="hidden" id="bookingEndTime" required>
                                <input type="hidden" id="selectedBookingDate" required>
                                <div id="timeSlotError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                                <small class="form-text text-muted">Click on an available time slot to select. Green slots are available, red slots are booked.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-calendar-alt me-2"></i>Additional Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="bookingPurpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                                <textarea id="bookingPurpose" class="form-control" required rows="3" placeholder="Enter the purpose of this booking..."></textarea>
                            </div>

                            <div class="col-12" id="attendeesFieldContainer" style="display: none;">
                                <label class="form-label">Attendees Passport <span class="text-danger">*</span></label>
                                <small class="form-text text-muted d-block mb-2">Enter passport numbers for each attendee</small>
                                <div id="attendeesList">
                                    <!-- Attendee inputs will be dynamically added here -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addAttendeeBtn" onclick="addAttendeeField()" style="display: none;">
                                    <i class="fas fa-plus me-1"></i> Add Attendee
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary" onclick="closeModal()">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> <span id="submitButtonText">Submit Booking</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let bookings = [];
let facilities = [];
let sortOrder = null; // 'date-asc', 'date-desc', 'created-asc', 'created-desc', or null

// Define global functions FIRST so they're available for onclick handlers
window.showCreateModal = function() {
    if (typeof loadFacilities === 'function') {
        loadFacilities();
    }
    const form = document.getElementById('bookingForm');
    if (form) {
        form.reset();
        delete form.dataset.bookingId;
    }
    
    // Reset modal title and button
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const submitButtonText = document.getElementById('submitButtonText');
    
    if (modalTitle) modalTitle.textContent = 'Create New Booking';
    if (modalIcon) modalIcon.className = 'fas fa-plus-circle me-2 text-primary';
    if (submitButtonText) submitButtonText.textContent = 'Submit Booking';
    
    // Reset time slot selection
    selectedTimeSlots = [];
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    const selectedDateInput = document.getElementById('selectedBookingDate');
    if (startTimeInput) startTimeInput.value = '';
    if (endTimeInput) endTimeInput.value = '';
    if (selectedDateInput) selectedDateInput.value = '';
    
    // Reset attendees field - hide by default, will show when facility is selected
    const attendeesContainer = document.getElementById('attendeesFieldContainer');
    const attendeesList = document.getElementById('attendeesList');
    const addAttendeeBtn = document.getElementById('addAttendeeBtn');
    if (attendeesContainer) {
        attendeesContainer.style.display = 'none';
    }
    if (attendeesList) {
        attendeesList.innerHTML = ''; // Clear all attendee fields
    }
    if (addAttendeeBtn) {
        addAttendeeBtn.style.display = 'none';
    }
    
    // Reset facility multi-attendees settings
    window.currentFacilityEnableMultiAttendees = false;
    window.currentFacilityMaxAttendees = null;
    
    // Clear timetable and show placeholder
    clearTimetable();
    
    // Clear any previous validation errors
    const timeSlotError = document.getElementById('timeSlotError');
    if (timeSlotError) {
        timeSlotError.style.display = 'none';
        timeSlotError.textContent = '';
    }
    
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
    }
    
    // Ensure facility select has event listener
    const facilitySelect = document.getElementById('bookingFacility');
    if (facilitySelect) {
        // Use onclick attribute for more reliable binding
        facilitySelect.onchange = function() {
            if (this.value) {
                loadTimetable(this.value);
            } else {
                clearTimetable();
            }
        };
        
        // If facility is already selected, load timetable
        if (facilitySelect.value) {
            setTimeout(() => {
                loadTimetable(facilitySelect.value);
            }, 100);
        }
    }
};

// Function to validate time range in real-time
window.validateTimeRange = function() {
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    const startTimeError = document.getElementById('startTimeError');
    const endTimeError = document.getElementById('endTimeError');
    
    // Clear previous errors
    clearTimeValidationErrors();
    
    // Check if both times are filled
    if (!startTimeInput || !endTimeInput || !startTimeInput.value || !endTimeInput.value) {
        return true; // Allow empty values during input
    }
    
    // Get facility time range (default to 08:00-20:00 if not set)
    const facilityTimeRange = window.currentFacilityTimeRange || { start: '08:00', end: '20:00' };
    const minTime = facilityTimeRange.start || '08:00';
    const maxTime = facilityTimeRange.end || '20:00';
    
    // Extract time from datetime string if needed
    let startTime = startTimeInput.value;
    let endTime = endTimeInput.value;
    
    // If it's a datetime string, extract just the time part
    if (startTime.includes(' ')) {
        startTime = startTime.split(' ')[1].substring(0, 5); // Extract HH:mm
    }
    if (endTime.includes(' ')) {
        endTime = endTime.split(' ')[1].substring(0, 5); // Extract HH:mm
    }
    
    // Validate start time is within allowed range
    if (startTime < minTime || startTime > maxTime) {
        if (startTimeError) {
            startTimeError.textContent = `Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            startTimeError.style.display = 'block';
        }
        startTimeInput.classList.add('is-invalid');
        startTimeInput.setCustomValidity(`Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    // Validate end time is within allowed range
    if (endTime < minTime || endTime > maxTime) {
        if (endTimeError) {
            endTimeError.textContent = `End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            endTimeError.style.display = 'block';
        }
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity(`End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    // Compare times - end time must be after start time
    if (startTime >= endTime) {
        showTimeValidationError('End time must be after start time');
        return false;
    }
    
    return true;
};

// Function to show time validation error
function showTimeValidationError(message) {
    const endTimeError = document.getElementById('endTimeError');
    const endTimeInput = document.getElementById('bookingEndTime');
    
    if (endTimeError) {
        endTimeError.textContent = message;
        endTimeError.style.display = 'block';
    }
    
    if (endTimeInput) {
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity(message);
    }
}

// Function to clear time validation errors
function clearTimeValidationErrors() {
    const startTimeError = document.getElementById('startTimeError');
    const endTimeError = document.getElementById('endTimeError');
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    
    if (startTimeError) {
        startTimeError.style.display = 'none';
        startTimeError.textContent = '';
    }
    
    if (endTimeError) {
        endTimeError.style.display = 'none';
        endTimeError.textContent = '';
    }
    
    if (startTimeInput) {
        startTimeInput.classList.remove('is-invalid');
        startTimeInput.setCustomValidity('');
    }
    
    if (endTimeInput) {
        endTimeInput.classList.remove('is-invalid');
        endTimeInput.setCustomValidity('');
    }
}

// Function to validate booking date (must be tomorrow or later)
window.validateBookingDate = function() {
    const dateInput = document.getElementById('bookingDate');
    const errorDiv = document.getElementById('bookingDateError');
    
    if (!dateInput || !dateInput.value) {
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
        dateInput?.classList.remove('is-invalid');
        return true;
    }
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    // Check if selected date is today or earlier
    if (selectedDate <= today) {
        if (errorDiv) {
            errorDiv.textContent = 'You can only book from tomorrow onwards. Please select a future date.';
            errorDiv.style.display = 'block';
        }
        dateInput.classList.add('is-invalid');
        dateInput.setCustomValidity('You can only book from tomorrow onwards');
        return false;
    }
    
    // Date is valid
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
    dateInput.classList.remove('is-invalid');
    dateInput.setCustomValidity('');
    
    // Update facilities when date is valid
    updateFacilitiesByDate();
    return true;
};

window.closeModal = function() {
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

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
    const search = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter');
    const facilityFilter = document.getElementById('facilityFilter');
    if (!statusFilter) return;
    const status = statusFilter.value;
    const facilityId = facilityFilter ? facilityFilter.value : '';
    
    let filtered = bookings.filter(b => {
        const matchSearch = !search || 
            (b.booking_number && b.booking_number.toLowerCase().includes(search)) ||
            (b.facility?.name && b.facility.name.toLowerCase().includes(search)) ||
            (b.purpose && b.purpose.toLowerCase().includes(search));
        const matchStatus = !status || b.status === status;
        const matchFacility = !facilityId || b.facility_id == facilityId;
        return matchSearch && matchStatus && matchFacility;
    });
    
    // Apply sorting if sortOrder is set
    if (sortOrder) {
        console.log('Sorting with order:', sortOrder, 'Filtered bookings count:', filtered.length);
        
        // Log first few bookings before sort
        if (filtered.length > 0) {
            console.log('Before sort - First booking created_at:', filtered[0].created_at, 'Last booking created_at:', filtered[filtered.length - 1].created_at);
            console.log('Before sort - All created_at values:', filtered.map(b => b.created_at));
        }
        
        // Create a copy to avoid mutating the original array during sort
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
                // Handle created_at sorting with null checks
                const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
                const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
                
                // Get timestamps, use 0 for invalid dates
                const timeA = isNaN(dateA.getTime()) ? 0 : dateA.getTime();
                const timeB = isNaN(dateB.getTime()) ? 0 : dateB.getTime();
                
                console.log(`Comparing: ${a.id} (${timeA}) vs ${b.id} (${timeB})`);
                
                if (sortOrder === 'created-asc') {
                    return timeA - timeB;
                } else if (sortOrder === 'created-desc') {
                    return timeB - timeA;
                }
            }
            return 0;
        });
        
        // Replace filtered array with sorted array
        filtered = sortedArray;
        
        // Log first few bookings after sort
        if (filtered.length > 0) {
            console.log('After sort - First booking created_at:', filtered[0].created_at, 'Last booking created_at:', filtered[filtered.length - 1].created_at);
            console.log('After sort - All created_at values:', filtered.map(b => b.created_at));
        }
    }
    
    if (typeof displayBookings === 'function') {
        displayBookings(filtered);
    } else {
        console.error('displayBookings function not found!');
    }
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
        alert('Error: Booking ID is missing. Please try again.');
        return;
    }
    
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
    
    if (!reasonSelect.value) {
        alert('Please select a reason for cancellation.');
        return;
    }
    
    if (reasonSelect.value === 'other' && !customReason.value.trim()) {
        alert('Please provide a reason for cancellation.');
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
            // Reload bookings list
            if (typeof loadBookings === 'function') {
                loadBookings();
            }
            // Show success message
            alert('✅ Booking cancelled successfully!');
        } else {
            alert('❌ Error: ' + (result.error || 'Failed to cancel booking. Please try again.'));
        }
    } catch (error) {
        alert('❌ Error: ' + (error.message || 'An unexpected error occurred. Please try again.'));
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Cancellation';
        }
    }
}

// Admin functions for managing bookings - defined at top level for onclick handlers
// Close dropdown when clicking outside (only for booking page dropdowns, not user dropdown)
document.addEventListener('click', function(event) {
    // Don't interfere with user dropdown in navigation
    if (event.target.closest('.user-dropdown')) {
        return;
    }
    
    if (!event.target.closest('.dropdown-menu-container')) {
        // Only close dropdowns inside dropdown-menu-container
        document.querySelectorAll('.dropdown-menu-container .dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

window.toggleDropdown = function(id) {
    event.stopPropagation();
    const dropdown = document.getElementById(`dropdown-${id}`);
    const button = event.target.closest('button');
    // Only select dropdowns inside dropdown-menu-container, not user dropdown
    const allDropdowns = document.querySelectorAll('.dropdown-menu-container .dropdown-menu');
    
    // Close all other dropdowns (only booking page dropdowns)
    allDropdowns.forEach(menu => {
        if (menu.id !== `dropdown-${id}`) {
            menu.classList.remove('show');
            menu.classList.remove('dropdown-up');
        }
    });
    
    // Toggle current dropdown
    const isShowing = dropdown.classList.contains('show');
    
    if (!isShowing) {
        // Calculate position
        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = 90; // Approximate height of dropdown (2 items)
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        
        // Show dropdown first to get its dimensions
        dropdown.style.display = 'block';
        dropdown.style.position = 'fixed';
        dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        
        // Check if dropdown would be cut off at bottom
        const dropdownRect = dropdown.getBoundingClientRect();
        const spaceBelow = viewportHeight - dropdownRect.bottom;
        const spaceAbove = buttonRect.top;
        
        // If not enough space below but enough space above, show above
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

window.approveBooking = async function(id) {
    // Close dropdown
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    if (!confirm('Are you sure you want to approve this booking?')) return;
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/approve`);
    if (result.success) {
        if (typeof loadBookings === 'function') {
            loadBookings();
        }
        alert('Booking approved successfully!');
    } else {
        alert(result.error || 'Error approving booking');
    }
};

window.rejectBooking = async function(id) {
    // Close dropdown
    const dropdown = document.getElementById(`dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    const reason = prompt('Please provide a reason for rejection:');
    if (reason === null) return; // User cancelled
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
        if (typeof loadBookings === 'function') {
            loadBookings();
        }
        alert('Booking rejected successfully!');
    } else {
        alert(result.error || 'Error rejecting booking');
    }
};

window.editBooking = async function(id) {
    // Load booking details and show edit modal
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    
    const result = await API.get(`/bookings/${id}`);
    if (!result.success) {
        alert('Error loading booking details: ' + (result.error || 'Unknown error'));
        return;
    }
    
    const booking = result.data.data || result.data;
    
    // Populate date first (needed for facility loading)
    const bookingDate = booking.booking_date || '';
    const dateInput = document.getElementById('bookingDate');
    
    // Set minimum date to tomorrow (users can only book from tomorrow onwards)
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
        dateInput.value = bookingDate;
        // Validate the date after setting it
        if (typeof validateBookingDate === 'function') {
            validateBookingDate();
        }
    }
    
    // Load facilities with the booking date to check capacity
    if (typeof loadFacilities === 'function') {
        await loadFacilities(bookingDate);
    }
    
    // Populate form with booking data
    const facilityId = booking.facility_id || '';
    document.getElementById('bookingFacility').value = facilityId;
    
    // Update time input constraints and load timetable based on facility
    if (facilityId) {
        await updateTimeInputConstraints(facilityId);
        if (typeof loadTimetable === 'function') {
            await loadTimetable(facilityId);
        }
    }
    
    // Extract time from datetime strings and set selected time slot
    if (booking.start_time && booking.end_time) {
        const startDate = new Date(booking.start_time);
        const endDate = new Date(booking.end_time);
        const startHours = String(startDate.getHours()).padStart(2, '0');
        const startMinutes = String(startDate.getMinutes()).padStart(2, '0');
        const endHours = String(endDate.getHours()).padStart(2, '0');
        const endMinutes = String(endDate.getMinutes()).padStart(2, '0');
        
        const startTime = `${startHours}:${startMinutes}`;
        const endTime = `${endHours}:${endMinutes}`;
        const bookingDateStr = booking.booking_date || bookingDate;
        
        // Set hidden inputs
        document.getElementById('selectedBookingDate').value = bookingDateStr;
        document.getElementById('bookingStartTime').value = `${bookingDateStr} ${startTime}:00`;
        document.getElementById('bookingEndTime').value = `${bookingDateStr} ${endTime}:00`;
        
        // Set selected time slots (for editing, we'll set it as a single slot for now)
        // When editing, we show the booking as a single continuous range
        selectedTimeSlots = [{
            date: bookingDateStr,
            start: startTime,
            end: endTime
        }];
        
        // Mark the corresponding slot as selected in the timetable
        setTimeout(() => {
            const slotId = `slot-${bookingDateStr}-${startTime}`;
            const slot = document.getElementById(slotId);
            if (slot) {
                slot.classList.add('selected');
            }
        }, 500);
    }
    
    document.getElementById('bookingPurpose').value = booking.purpose || '';
    
    // Handle attendees field based on facility settings
    // updateTimeInputConstraints will be called above, which will set up the field visibility
    // Load attendees if available
    const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
    const attendeesList = document.getElementById('attendeesList');
    
    if (enableMultiAttendees && attendeesList) {
        // Clear existing fields
        attendeesList.innerHTML = '';
        
        // Load attendees from booking if available
        if (booking.attendees && Array.isArray(booking.attendees) && booking.attendees.length > 0) {
            booking.attendees.forEach(attendee => {
                addAttendeeField();
                const lastField = attendeesList.lastElementChild;
                const input = lastField.querySelector('.attendee-passport-input');
                if (input && attendee.student_passport) {
                    input.value = attendee.student_passport;
                }
            });
        } else {
            // Add one empty field
            addAttendeeField();
        }
    }
    
    // Store booking ID for update
    document.getElementById('bookingForm').dataset.bookingId = id;
    
    // Change form title and submit button
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const submitButtonText = document.getElementById('submitButtonText');
    
    if (modalTitle) modalTitle.textContent = 'Edit Booking';
    if (modalIcon) modalIcon.className = 'fas fa-edit me-2 text-primary';
    if (submitButtonText) submitButtonText.textContent = 'Update Booking';
    
    // Show modal
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.deleteBooking = async function(id) {
    if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) return;
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.delete(`/bookings/${id}`);
    if (result.success) {
        if (typeof loadBookings === 'function') {
            loadBookings();
        }
        alert('Booking deleted successfully!');
    } else {
        alert(result.error || 'Error deleting booking');
    }
};

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
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

    initBookings();
});

function initBookings() {
    // Set minimum date to tomorrow for booking date input (users can only book from tomorrow onwards)
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
    }
    
    // Time input constraints will be set dynamically based on selected facility
    // Default values are set here, but will be updated when facility is selected
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    if (startTimeInput) {
        startTimeInput.min = '08:00';
        startTimeInput.max = '20:00';
    }
    if (endTimeInput) {
        endTimeInput.min = '08:00';
        endTimeInput.max = '20:00';
    }
    
    // Initialize facility time range and max booking hours
    window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
    window.currentFacilityMaxBookingHours = 1; // Default to 1 hour
    
    // Update title based on user role
    const isAdmin = API.isAdmin();
    const isStaff = API.isStaff();
    const isStudent = API.isStudent();
    const isManager = isAdmin || isStaff; // Admin and Staff can manage bookings
    
    if (isManager) {
        // Admin and Staff: Booking Management (manage all bookings)
        document.getElementById('bookingsTitle').textContent = 'Booking Management';
        document.getElementById('bookingsSubtitle').textContent = 'Manage all bookings in the system';
        // Hide "New Booking" button for admin/staff - they can only manage existing bookings
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.style.display = 'none';
        }
    } else {
        // Student: My Bookings (only their own bookings)
        document.getElementById('bookingsTitle').textContent = 'My Bookings';
        document.getElementById('bookingsSubtitle').textContent = 'Manage your facility bookings';
        // Show "New Booking" button for students only
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.style.display = 'block';
        }
        // Bind form submit event only for students
        bindBookingForm();
    }
    
    loadBookings();
    loadFacilities();
    loadFacilitiesForFilter();
}

async function loadBookings() {
    showLoading(document.getElementById('bookingsList'));
    
    // Admin and Staff can view all bookings, Students can only view their own
    const isAdmin = API.isAdmin();
    const isStaff = API.isStaff();
    const endpoint = (isAdmin || isStaff) ? '/bookings' : '/bookings/user/my-bookings';
    const result = await API.get(endpoint);
    
    if (result.success) {
        bookings = result.data.data?.data || result.data.data || [];
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

async function loadFacilities(bookingDate = null) {
    let url = '/facilities';
    if (bookingDate) {
        url += `?booking_date=${bookingDate}`;
    }
    
    const result = await API.get(url);
    
    if (result.success) {
        facilities = result.data.data?.data || result.data.data || [];
        const select = document.getElementById('bookingFacility');
        
        if (facilities.length === 0) {
            select.innerHTML = '<option value="">No facilities available. Please create a facility first.</option>';
            select.disabled = true;
            alert('No facilities available. Please create a facility first.');
        } else {
            select.disabled = false;
            const currentValue = select.value; // Preserve current selection if any
            select.innerHTML = '<option value="">Select Facility</option>' +
                facilities.map(f => {
                    // Only disable if facility status is not available
                    // Don't disable based on capacity because capacity is checked by time segments
                    const isDisabled = f.status !== 'available';
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const selectedAttr = (currentValue == f.id) ? 'selected' : '';
                    return `<option value="${f.id}" ${disabledAttr} ${selectedAttr}>${f.name} (${f.code}) - ${f.status}</option>`;
                }).join('');
        }
    } else {
        const select = document.getElementById('bookingFacility');
        select.innerHTML = '<option value="">Error loading facilities</option>';
        select.disabled = true;
        console.error('Error loading facilities:', result);
    }
}

// Function to update facilities when date is selected
window.updateFacilitiesByDate = function() {
    const dateInput = document.getElementById('bookingDate');
    if (dateInput && dateInput.value) {
        loadFacilities(dateInput.value);
    }
};

// Timetable functions
let selectedTimeSlots = []; // Array to store multiple selected time slots
let bookedSlots = {};

// Generate 3 days starting from tomorrow
function getNextThreeDays() {
    const days = [];
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayNamesLower = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    for (let i = 1; i <= 3; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const dayIndex = date.getDay();
        const dayName = dayNames[dayIndex];
        const dayOfWeek = dayNamesLower[dayIndex]; // Lowercase day name for matching
        const month = monthNames[date.getMonth()];
        const day = date.getDate();
        const year = date.getFullYear();
        
        // Format date as YYYY-MM-DD using local time (not UTC) to avoid timezone issues
        const monthStr = String(date.getMonth() + 1).padStart(2, '0');
        const dayStr = String(day).padStart(2, '0');
        const dateStr = `${year}-${monthStr}-${dayStr}`;
        
        days.push({
            date: dateStr,
            display: `${dayName}, ${month} ${day}`,
            fullDate: `${month} ${day}, ${year}`,
            dayOfWeek: dayOfWeek // Add day of week in lowercase for facility available_day matching
        });
    }
    
    return days;
}

// Generate time slots based on facility available time (fixed to 1 hour)
function generateTimeSlots(startTime = '08:00', endTime = '20:00') {
    const slots = [];
    
    // Parse start and end times
    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);
    
    // Convert to minutes for easier calculation
    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;
    const durationMinutes = 60; // Fixed to 1 hour
    
    // Generate slots (1 hour each)
    for (let currentMinutes = startMinutes; currentMinutes + durationMinutes <= endMinutes; currentMinutes += 60) {
        const slotStartHour = Math.floor(currentMinutes / 60);
        const slotStartMin = currentMinutes % 60;
        const slotEndMinutes = currentMinutes + durationMinutes;
        const slotEndHour = Math.floor(slotEndMinutes / 60);
        const slotEndMin = slotEndMinutes % 60;
        
        const slotStart = `${slotStartHour.toString().padStart(2, '0')}:${slotStartMin.toString().padStart(2, '0')}`;
        const slotEnd = `${slotEndHour.toString().padStart(2, '0')}:${slotEndMin.toString().padStart(2, '0')}`;
        
        slots.push({
            start: slotStart,
            end: slotEnd,
            display: `${formatTime12(slotStart)} - ${formatTime12(slotEnd)}`
        });
    }
    
    return slots;
}

// Format time to 12-hour format
function formatTime12(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// Format Date object to 12-hour format without seconds
// Handle timezone issues by extracting time directly from the datetime string
function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    // If date is a string, extract time directly to avoid timezone conversion issues
    if (typeof date === 'string') {
        // Try to extract time from various formats:
        // - "2025-12-15 08:00:00" (local format)
        // - "2025-12-15T08:00:00.000000Z" (ISO format with Z)
        // - "2025-12-15T08:00:00" (ISO format without timezone)
        
        // First, try to match time pattern HH:mm:ss
        const timeMatch = date.match(/(\d{1,2}):(\d{2}):(\d{2})/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            
            // If the string ends with 'Z' or has timezone, it's UTC
            // In that case, we need to check if we should use UTC or local time
            // For booking times, we want to use the time as stored (local time)
            // So we'll use the time directly from the string
            
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hour12 = hours % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    }
    
    // Fallback to Date object parsing
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    // If the date string contains 'Z' (UTC), use UTC methods
    // Otherwise, use local time methods
    const isUTC = typeof date === 'string' && date.includes('Z');
    const hours = isUTC ? d.getUTCHours() : d.getHours();
    const minutes = String(isUTC ? d.getUTCMinutes() : d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// Format DateTime to show both date and time
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const date = formatDate(dateTimeString);
    const time = formatTimeNoSeconds(dateTimeString);
    return `${date} ${time}`;
}

// Load timetable for selected facility
async function loadTimetable(facilityId) {
    console.log('Loading timetable for facility:', facilityId);
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    container.innerHTML = '<div class="timetable-loading"><i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...</div>';
    
    try {
        if (!facilityId) {
            throw new Error('Facility ID is required');
        }
        const days = getNextThreeDays();
        
        // Load facility info to get capacity, available_time, available_day, and max_booking_hours
        let facilityCapacity = null;
        let facilityStartTime = '08:00'; // Default start time
        let facilityEndTime = '20:00';   // Default end time
        let maxBookingHours = 1; // Default to 1 hour
        let facilityAvailableDays = null; // Facility available days array
        
        try {
            const facilityResult = await API.get(`/facilities/${facilityId}`);
            if (facilityResult.success && facilityResult.data) {
                const facility = facilityResult.data.data || facilityResult.data;
                facilityCapacity = facility.capacity;
                maxBookingHours = facility.max_booking_hours || 1; // Get max booking hours
                
                // Get available_time from facility
                if (facility.available_time && typeof facility.available_time === 'object') {
                    if (facility.available_time.start) {
                        facilityStartTime = facility.available_time.start;
                    }
                    if (facility.available_time.end) {
                        facilityEndTime = facility.available_time.end;
                    }
                }
                
                // Handle multi-attendees feature in loadTimetable
                const enableMultiAttendees = facility.enable_multi_attendees || false;
                const maxAttendees = facility.max_attendees || facility.capacity || 1000;
                
                // Store facility multi-attendees settings globally
                window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
                window.currentFacilityMaxAttendees = maxAttendees;
                window.currentFacilityEnableMultiAttendeesForTimetable = enableMultiAttendees;
                
                // Update attendees field visibility
                const attendeesContainer = document.getElementById('attendeesFieldContainer');
                const attendeesList = document.getElementById('attendeesList');
                const addAttendeeBtn = document.getElementById('addAttendeeBtn');
                if (attendeesContainer && attendeesList) {
                    if (enableMultiAttendees) {
                        // Show attendees field if multi-attendees is enabled
                        attendeesContainer.style.display = 'block';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'inline-block';
                        }
                        // Add first attendee field
                        attendeesList.innerHTML = '';
                        addAttendeeField();
                    } else {
                        // Hide attendees field if multi-attendees is disabled
                        attendeesContainer.style.display = 'none';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'none';
                        }
                        attendeesList.innerHTML = '';
                    }
                }
                
                // Get available_day from facility
                if (facility.available_day && Array.isArray(facility.available_day) && facility.available_day.length > 0) {
                    facilityAvailableDays = facility.available_day.map(day => day.toLowerCase()); // Ensure lowercase
                }
                
                // Store facility time range globally for validation
                window.currentFacilityTimeRange = {
                    start: facilityStartTime,
                    end: facilityEndTime
                };
                
                // Store max booking hours globally
                window.currentFacilityMaxBookingHours = maxBookingHours;
            }
        } catch (error) {
            console.error('Error loading facility info:', error);
        }
        
        // Generate time slots based on facility available time (fixed to 1 hour)
        const slots = generateTimeSlots(facilityStartTime, facilityEndTime);
        
        // Load booked slots for each day
        bookedSlots = {};
        const availabilityPromises = days.map(async (day) => {
            try {
                const result = await API.get(`/facilities/${facilityId}/availability?date=${day.date}`);
                console.log(`Availability result for ${day.date}:`, result);
                
                // Check different possible response structures
                let bookings = [];
                if (result.success && result.data) {
                    // Laravel API typically returns: { message: "...", data: { ... } }
                    // API.js wraps it as: { success: true, data: { message: "...", data: { ... } } }
                    if (result.data.data && result.data.data.bookings) {
                        bookings = result.data.data.bookings;
                    } else if (result.data.bookings) {
                        bookings = result.data.bookings;
                    } else if (Array.isArray(result.data)) {
                        bookings = result.data;
                    }
                }
                
                bookedSlots[day.date] = bookings.map(booking => {
                    // Handle booking format - API returns start_time and end_time as "HH:mm"
                    const startTime = booking.start_time || '';
                    const endTime = booking.end_time || '';
                    
                    return {
                        start_time: `${day.date} ${startTime}:00`,
                        end_time: `${day.date} ${endTime}:00`,
                        expected_attendees: booking.expected_attendees || 1
                    };
                });
            } catch (error) {
                console.error(`Error loading availability for ${day.date}:`, error);
                bookedSlots[day.date] = [];
            }
        });
        
        // Wait for all availability checks to complete
        await Promise.all(availabilityPromises);
        
        // Load user's existing bookings to highlight them
        let userBookingsByDate = {};
        try {
            const bookingsResult = await API.get('/bookings/user/my-bookings');
            if (bookingsResult.success) {
                const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                console.log('User bookings loaded:', userBookings);
                console.log('Current facility ID:', facilityId);
                
                // Group bookings by date and facility
                userBookings.forEach(booking => {
                    const bookingDate = booking.booking_date || '';
                    const bookingFacilityId = booking.facility_id || booking.facility?.id;
                    console.log('Checking booking:', {
                        bookingDate,
                        bookingFacilityId,
                        facilityId,
                        status: booking.status,
                        start_time: booking.start_time,
                        end_time: booking.end_time
                    });
                    
                    if (bookingDate && bookingFacilityId == facilityId && 
                        (booking.status === 'pending' || booking.status === 'approved')) {
                        // Normalize booking_date format
                        let normalizedDate = bookingDate;
                        if (typeof bookingDate === 'string' && bookingDate.includes('T')) {
                            normalizedDate = bookingDate.split('T')[0];
                        } else if (bookingDate instanceof Date) {
                            normalizedDate = bookingDate.toISOString().split('T')[0];
                        } else if (bookingDate && typeof bookingDate === 'object' && bookingDate.year) {
                            normalizedDate = `${bookingDate.year}-${String(bookingDate.month).padStart(2, '0')}-${String(bookingDate.day).padStart(2, '0')}`;
                        }
                        
                        if (!userBookingsByDate[normalizedDate]) {
                            userBookingsByDate[normalizedDate] = [];
                        }
                        userBookingsByDate[normalizedDate].push(booking);
                        console.log('Added booking to date:', normalizedDate, booking);
                    }
                });
                console.log('User bookings by date:', userBookingsByDate);
            }
        } catch (error) {
            console.error('Error loading user bookings:', error);
        }
        
        // Get enable_multi_attendees from facility
        let enableMultiAttendees = false;
        try {
            const facilityResult = await API.get(`/facilities/${facilityId}`);
            if (facilityResult.success && facilityResult.data) {
                const facility = facilityResult.data.data || facilityResult.data;
                enableMultiAttendees = facility.enable_multi_attendees || false;
            }
        } catch (error) {
            console.error('Error loading facility info for multi-attendees:', error);
        }
        
        // Render timetable with user bookings info and available days
        renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours, userBookingsByDate, facilityAvailableDays, enableMultiAttendees);
    } catch (error) {
        console.error('Error loading timetable:', error);
        container.innerHTML = '<div class="timetable-no-slots">Error loading timetable. Please try again.</div>';
    }
}

// Render timetable
function renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours = 1, userBookingsByDate = {}, facilityAvailableDays = null, enableMultiAttendees = false) {
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    let html = '<div class="timetable-days">';
    
    days.forEach(day => {
        const dayBookedSlots = bookedSlots[day.date] || [];
        
        // Check if this day is available based on facility's available_day
        const isDayAvailable = !facilityAvailableDays || facilityAvailableDays.length === 0 || 
                                facilityAvailableDays.includes(day.dayOfWeek);
        
        // Add class for unavailable days
        const dayClass = isDayAvailable ? 'timetable-day' : 'timetable-day unavailable-day';
        
        html += `
            <div class="${dayClass}" data-date="${day.date}" ${!isDayAvailable ? 'data-unavailable="true"' : ''}>
                <div class="timetable-day-header">
                    <div class="timetable-day-title">${day.display}</div>
                    <div class="timetable-day-date">${day.fullDate}</div>
                    ${!isDayAvailable ? '<div class="timetable-day-unavailable-badge"><i class="fas fa-ban"></i> Not Available</div>' : ''}
                </div>
                <div class="timetable-slots">
        `;
        
        slots.forEach(slot => {
            // If day is not available, mark all slots as disabled
            if (!isDayAvailable) {
                const slotId = `slot-${day.date}-${slot.start}`;
                html += `
                    <div class="timetable-slot unavailable" 
                         data-date="${day.date}" 
                         data-start="${slot.start}" 
                         data-end="${slot.end}"
                         id="${slotId}"
                         title="This facility is not available on ${day.display}">
                        <span class="timetable-slot-time">${slot.display}</span>
                        <span class="timetable-slot-unavailable-text">Not Available</span>
                    </div>
                `;
                return; // Skip to next slot
            }
            
            // Calculate total attendees for overlapping bookings in this time slot
            let totalAttendees = 0;
            let hasOverlappingBookings = false;
            const slotStart = new Date(`${day.date} ${slot.start}:00`);
            const slotEnd = new Date(`${day.date} ${slot.end}:00`);
            
            dayBookedSlots.forEach(booking => {
                try {
                    const bookingStart = new Date(booking.start_time);
                    const bookingEnd = new Date(booking.end_time);
                    
                    if (isNaN(bookingStart.getTime()) || isNaN(bookingEnd.getTime())) {
                        return;
                    }
                    
                    // Check if booking overlaps with this slot
                    if (slotStart < bookingEnd && slotEnd > bookingStart) {
                        hasOverlappingBookings = true;
                        // If enable_multi_attendees, each booking occupies full capacity
                        if (enableMultiAttendees) {
                            totalAttendees = facilityCapacity; // Full capacity occupied
                        } else {
                            totalAttendees += booking.expected_attendees || 1;
                        }
                    }
                } catch (error) {
                    console.error('Error processing booking:', booking, error);
                }
            });
            
            // Check if slot is available based on capacity
            // If no capacity info, fall back to conflict check
            let isAvailable = true;
            if (enableMultiAttendees) {
                // If multi-attendees is enabled, any booking makes the slot unavailable
                isAvailable = !hasOverlappingBookings;
            } else if (facilityCapacity !== null) {
                // Based on capacity: available if total attendees < capacity
                isAvailable = totalAttendees < facilityCapacity;
            } else {
                // Fallback: if there are any bookings, consider it booked
                isAvailable = totalAttendees === 0;
            }
            
            const slotId = `slot-${day.date}-${slot.start}`;
            
            // Check if this slot is already selected
            const isSelected = selectedTimeSlots.some(s => 
                s.date === day.date && s.start === slot.start && s.end === slot.end
            );
            
            // Check if this slot belongs to the current user's bookings FIRST
            // This must be checked before determining availability to ensure user bookings are highlighted
            const userBookingsOnDate = userBookingsByDate[day.date] || [];
            let isUserBooked = false;
            let userBookingStatus = null; // Track the status of user's booking (pending or approved)
            
            if (userBookingsOnDate.length > 0) {
                console.log(`Checking ${userBookingsOnDate.length} user bookings for date ${day.date}, slot ${slot.start}-${slot.end}`);
            }
            
            userBookingsOnDate.forEach(userBooking => {
                try {
                    // Get the booking date - normalize it first
                    let bookingDateStr = day.date;
                    if (userBooking.booking_date) {
                        if (typeof userBooking.booking_date === 'string') {
                            bookingDateStr = userBooking.booking_date.split('T')[0]; // Extract date part
                        } else if (userBooking.booking_date instanceof Date) {
                            bookingDateStr = userBooking.booking_date.toISOString().split('T')[0];
                        } else if (userBooking.booking_date.year) {
                            bookingDateStr = `${userBooking.booking_date.year}-${String(userBooking.booking_date.month).padStart(2, '0')}-${String(userBooking.booking_date.day).padStart(2, '0')}`;
                        }
                    }
                    
                    // Parse start_time and end_time - extract time part and combine with booking_date
                    let userBookingStart, userBookingEnd;
                    
                    if (userBooking.start_time) {
                        let startTimeStr = '';
                        if (typeof userBooking.start_time === 'string') {
                            startTimeStr = userBooking.start_time;
                        } else {
                            startTimeStr = String(userBooking.start_time);
                        }
                        
                        // Extract time part (HH:mm:ss) from the datetime string
                        // Format could be: "2025-12-13T08:00:00.000000Z" or "2025-12-13 08:00:00"
                        let timePart = '';
                        if (startTimeStr.includes('T')) {
                            // ISO format: "2025-12-13T08:00:00.000000Z"
                            const timeMatch = startTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
                            if (timeMatch) {
                                timePart = timeMatch[1].substring(0, 5); // Get HH:mm
                            }
                        } else if (startTimeStr.includes(' ')) {
                            // Space format: "2025-12-13 08:00:00"
                            const parts = startTimeStr.split(' ');
                            if (parts.length > 1) {
                                timePart = parts[1].substring(0, 5); // Get HH:mm
                            }
                        }
                        
                        // Combine booking_date with extracted time
                        if (timePart) {
                            userBookingStart = new Date(`${bookingDateStr} ${timePart}:00`);
                        } else {
                            // Fallback: try to parse as-is
                            userBookingStart = new Date(userBooking.start_time);
                        }
                    } else {
                        return; // Skip if no start_time
                    }
                    
                    if (userBooking.end_time) {
                        let endTimeStr = '';
                        if (typeof userBooking.end_time === 'string') {
                            endTimeStr = userBooking.end_time;
                        } else {
                            endTimeStr = String(userBooking.end_time);
                        }
                        
                        // Extract time part
                        let timePart = '';
                        if (endTimeStr.includes('T')) {
                            const timeMatch = endTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
                            if (timeMatch) {
                                timePart = timeMatch[1].substring(0, 5); // Get HH:mm
                            }
                        } else if (endTimeStr.includes(' ')) {
                            const parts = endTimeStr.split(' ');
                            if (parts.length > 1) {
                                timePart = parts[1].substring(0, 5); // Get HH:mm
                            }
                        }
                        
                        // Combine booking_date with extracted time
                        if (timePart) {
                            userBookingEnd = new Date(`${bookingDateStr} ${timePart}:00`);
                        } else {
                            userBookingEnd = new Date(userBooking.end_time);
                        }
                    } else {
                        return; // Skip if no end_time
                    }
                    
                    if (isNaN(userBookingStart.getTime()) || isNaN(userBookingEnd.getTime())) {
                        console.warn('Invalid date for user booking:', {
                            booking: userBooking,
                            bookingDate: bookingDateStr,
                            start: userBooking.start_time,
                            end: userBooking.end_time,
                            startParsed: userBookingStart,
                            endParsed: userBookingEnd
                        });
                        return;
                    }
                    
                    // Check if this slot overlaps with user's booking
                    // Match if the slot time exactly matches or overlaps with user's booking
                    const overlaps = slotStart < userBookingEnd && slotEnd > userBookingStart;
                    if (overlaps) {
                        console.log('Found user booking match!', {
                            slot: `${slot.start}-${slot.end}`,
                            bookingDate: bookingDateStr,
                            booking: `${userBookingStart.toLocaleTimeString()}-${userBookingEnd.toLocaleTimeString()}`,
                            status: userBooking.status,
                            slotStart: slotStart.toISOString(),
                            slotEnd: slotEnd.toISOString(),
                            bookingStart: userBookingStart.toISOString(),
                            bookingEnd: userBookingEnd.toISOString()
                        });
                        isUserBooked = true;
                        userBookingStatus = userBooking.status; // Store the booking status
                        return; // Found match, can exit early
                    }
                } catch (error) {
                    console.error('Error checking user booking:', userBooking, error);
                }
            });
            
            // Check max_booking_hours limit for this date
            const existingBookingHours = userBookingsOnDate.reduce((sum, b) => sum + (b.duration_hours || 1), 0);
            const hasReachedLimit = existingBookingHours >= maxBookingHours;
            
            // Determine slot class and disabled state
            // Priority: selected > user-booked (pending) > booked (approved) > available/booked
            let slotClass = '';
            let isDisabled = false;
            
            if (isSelected) {
                slotClass = 'selected';
            } else if (isUserBooked) {
                // User's own booking - check status
                if (userBookingStatus === 'pending') {
                    // Pending bookings: show as golden highlight
                    slotClass = 'user-booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as user-booked (pending)`);
                } else if (userBookingStatus === 'approved') {
                    // Approved bookings: show as red (booked)
                    slotClass = 'booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as booked (approved)`);
                } else {
                    // Other statuses: default to user-booked
                    slotClass = 'user-booked';
                }
                isDisabled = true; // Can't book the same slot twice
            } else if (isAvailable) {
                slotClass = 'available';
            } else {
                slotClass = 'booked';
            }
            
            // If user has reached max_booking_hours limit, disable all available slots for this date
            if (hasReachedLimit && isAvailable && !isSelected && !isUserBooked) {
                slotClass = 'disabled';
                isDisabled = true;
            } else if (!isAvailable && !isUserBooked) {
                isDisabled = true;
            }
            
            // Display attendees count (X/Capacity)
            // If enable_multi_attendees and has bookings, show as full capacity
            let displayAttendees = totalAttendees;
            if (enableMultiAttendees && hasOverlappingBookings) {
                displayAttendees = facilityCapacity; // Show as full capacity when multi-attendees is enabled
            }
            const attendeesInfo = facilityCapacity !== null 
                ? `${displayAttendees}/${facilityCapacity}` 
                : (displayAttendees > 0 ? `${displayAttendees}` : '');
            
            const onclickAttr = isDisabled ? '' : `onclick="selectTimeSlot('${day.date}', '${slot.start}', '${slot.end}', '${slotId}')"`;
            let titleAttr = '';
            if (isDisabled && hasReachedLimit) {
                titleAttr = `title="Max ${maxBookingHours}h limit reached"`;
            } else if (isUserBooked) {
                if (userBookingStatus === 'pending') {
                    titleAttr = `title="Your booking (Pending)"`;
                } else if (userBookingStatus === 'approved') {
                    titleAttr = `title="Your booking (Approved)"`;
                } else {
                    titleAttr = `title="Your booking"`;
                }
            }
            
            // Add data attribute to mark user bookings for easier identification
            const userBookingAttr = isUserBooked ? `data-user-booking="true" data-booking-status="${userBookingStatus || ''}"` : '';
            
            html += `
                <div class="timetable-slot ${slotClass}" 
                     data-date="${day.date}" 
                     data-start="${slot.start}" 
                     data-end="${slot.end}"
                     id="${slotId}"
                     ${userBookingAttr}
                     ${onclickAttr}
                     ${titleAttr}>
                    <span class="timetable-slot-time">${slot.display}</span>
                    ${attendeesInfo ? `<span class="timetable-slot-attendees">${attendeesInfo}</span>` : ''}
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Select time slot (supports multi-selection)
window.selectTimeSlot = async function(date, start, end, slotId) {
    const slot = document.getElementById(slotId);
    if (slot.classList.contains('booked') || slot.classList.contains('disabled') || slot.classList.contains('user-booked') || slot.classList.contains('unavailable')) {
        return;
    }
    
    const maxBookingHours = window.currentFacilityMaxBookingHours || 1;
    const facilitySelect = document.getElementById('bookingFacility');
    const facilityId = facilitySelect ? facilitySelect.value : null;
    
    // Check if slot is already selected
    const slotIndex = selectedTimeSlots.findIndex(s => 
        s.date === date && s.start === start && s.end === end
    );
    
    let newSelectedSlots = [];
    if (slotIndex >= 0) {
        // Deselect: remove from array
        selectedTimeSlots.splice(slotIndex, 1);
        slot.classList.remove('selected');
        newSelectedSlots = [...selectedTimeSlots];
    } else {
        // Select: add to array
        const newSlot = { date, start, end };
        
        // Calculate total selected slots for this date
        const sameDateSlots = selectedTimeSlots.filter(s => s.date === date);
        const totalSelectedSlots = sameDateSlots.length + 1; // +1 for the new slot being added
        
        // Check max_booking_hours limit BEFORE adding the slot
        // max_booking_hours limits the total number of hours (slots) user can book
        if (facilityId) {
            try {
                const bookingsResult = await API.get('/bookings/user/my-bookings');
                if (bookingsResult.success) {
                    const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                    const bookingsOnDate = userBookings.filter(b => {
                        const bookingDate = b.booking_date || '';
                        const bookingFacilityId = b.facility_id || b.facility?.id;
                        return bookingDate === date && bookingFacilityId == facilityId && 
                               (b.status === 'pending' || b.status === 'approved');
                    });
                    
                    const existingBookingHours = bookingsOnDate.reduce((sum, b) => sum + (b.duration_hours || 1), 0);
                    const selectedSlotsHours = totalSelectedSlots; // Each slot is 1 hour
                    const totalAfterSelection = existingBookingHours + selectedSlotsHours;
                    
                    if (totalAfterSelection > maxBookingHours) {
                        alert(`You have reached the maximum booking limit for this facility on this date.\n\nMaximum allowed: ${maxBookingHours} hour(s)\nYour current bookings: ${existingBookingHours} hour(s)\nSelected slots: ${sameDateSlots.length} hour(s)\nAfter selecting this slot: ${totalAfterSelection} hour(s)`);
                        return;
                    }
                }
            } catch (error) {
                console.error('Error checking booking limit:', error);
            }
        }
        
        // Add to selection
        selectedTimeSlots.push(newSlot);
        slot.classList.add('selected');
        newSelectedSlots = [...selectedTimeSlots];
    }
    
    // Update hidden inputs based on selected slots
    if (newSelectedSlots.length > 0) {
        // Filter slots for the same date
        const sameDateSlots = newSelectedSlots.filter(s => s.date === date);
        if (sameDateSlots.length > 0) {
            // Sort by start time
            sameDateSlots.sort((a, b) => a.start.localeCompare(b.start));
            
            // Get earliest start and latest end
            const earliestStart = sameDateSlots[0].start;
            const latestEnd = sameDateSlots[sameDateSlots.length - 1].end;
            
            const dateInput = document.getElementById('selectedBookingDate');
            const startInput = document.getElementById('bookingStartTime');
            const endInput = document.getElementById('bookingEndTime');
            
            if (dateInput) dateInput.value = date;
            if (startInput) startInput.value = `${date} ${earliestStart}:00`;
            if (endInput) endInput.value = `${date} ${latestEnd}:00`;
        }
    } else {
        // Clear inputs if no selection
        const dateInput = document.getElementById('selectedBookingDate');
        const startInput = document.getElementById('bookingStartTime');
        const endInput = document.getElementById('bookingEndTime');
        
        if (dateInput) dateInput.value = '';
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
    }
    
    // Clear error
    const errorDiv = document.getElementById('timeSlotError');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
};

// Clear timetable
function clearTimetable() {
    const container = document.getElementById('timetableContainer');
    if (container) {
        container.innerHTML = '<div class="timetable-no-slots">Please select a facility to view available time slots</div>';
    }
    selectedTimeSlots = [];
    // Clear all selected slots visually
    document.querySelectorAll('.timetable-slot.selected').forEach(s => {
        s.classList.remove('selected');
    });
}

// Add attendee passport input field
window.addAttendeeField = function() {
    const attendeesList = document.getElementById('attendeesList');
    const maxAttendees = window.currentFacilityMaxAttendees || 1000;
    
    if (!attendeesList) return;
    
    // Check if we've reached max attendees
    const currentCount = attendeesList.querySelectorAll('.attendee-field').length;
    if (currentCount >= maxAttendees) {
        alert(`Maximum ${maxAttendees} attendees allowed for this facility.`);
        return;
    }
    
    const fieldIndex = currentCount + 1;
    const attendeeField = document.createElement('div');
    attendeeField.className = 'attendee-field mb-2';
    attendeeField.innerHTML = `
        <div class="input-group">
            <span class="input-group-text">${fieldIndex}</span>
            <input type="text" class="form-control attendee-passport-input" 
                   placeholder="Enter passport number" 
                   required
                   data-index="${fieldIndex}">
            <button type="button" class="btn btn-outline-danger" onclick="removeAttendeeField(this)" ${currentCount === 0 ? 'disabled' : ''}>
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
    `;
    
    attendeesList.appendChild(attendeeField);
    
    // Update field numbers and enable/disable remove buttons
    updateAttendeeFieldNumbers();
}

// Remove attendee passport input field
window.removeAttendeeField = function(button) {
    const attendeeField = button.closest('.attendee-field');
    if (attendeeField) {
        attendeeField.remove();
        updateAttendeeFieldNumbers();
    }
}

// Update attendee field numbers
function updateAttendeeFieldNumbers() {
    const attendeesList = document.getElementById('attendeesList');
    if (!attendeesList) return;
    
    const fields = attendeesList.querySelectorAll('.attendee-field');
    const addAttendeeBtn = document.getElementById('addAttendeeBtn');
    const maxAttendees = window.currentFacilityMaxAttendees || 1000;
    
    fields.forEach((field, index) => {
        const numberSpan = field.querySelector('.input-group-text');
        const removeBtn = field.querySelector('button');
        const input = field.querySelector('.attendee-passport-input');
        
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
        if (input) {
            input.setAttribute('data-index', index + 1);
        }
        // Enable remove button if there's more than one field
        if (removeBtn) {
            removeBtn.disabled = fields.length <= 1;
        }
    });
    
    // Disable add button if max reached
    if (addAttendeeBtn) {
        addAttendeeBtn.disabled = fields.length >= maxAttendees;
    }
}

// Handle facility change
window.handleFacilityChange = function(select) {
    console.log('Facility changed to:', select.value);
    if (select && select.value) {
        // Update time input constraints based on facility
        updateTimeInputConstraints(select.value);
        loadTimetable(select.value);
    } else {
        clearTimetable();
        // Reset to default time range
        window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
        
        // Hide attendees field when no facility is selected
        const attendeesContainer = document.getElementById('attendeesFieldContainer');
        const attendeesInput = document.getElementById('bookingAttendees');
        if (attendeesContainer) {
            attendeesContainer.style.display = 'none';
        }
        if (attendeesInput) {
            attendeesInput.value = '1'; // Set default to 1
            attendeesInput.removeAttribute('required');
        }
        
        // Reset facility multi-attendees settings
        window.currentFacilityEnableMultiAttendees = false;
        window.currentFacilityMaxAttendees = null;
    }
};

// Update time input constraints based on facility
async function updateTimeInputConstraints(facilityId) {
    try {
        const facilityResult = await API.get(`/facilities/${facilityId}`);
        if (facilityResult.success && facilityResult.data) {
            const facility = facilityResult.data.data || facilityResult.data;
            let startTime = '08:00';
            let endTime = '20:00';
            
            // Get available_time from facility
            if (facility.available_time && typeof facility.available_time === 'object') {
                if (facility.available_time.start) {
                    startTime = facility.available_time.start;
                }
                if (facility.available_time.end) {
                    endTime = facility.available_time.end;
                }
            }
            
            // Update time input constraints
            const startTimeInput = document.getElementById('bookingStartTime');
            const endTimeInput = document.getElementById('bookingEndTime');
            if (startTimeInput) {
                startTimeInput.min = startTime;
                startTimeInput.max = endTime;
            }
            if (endTimeInput) {
                endTimeInput.min = startTime;
                endTimeInput.max = endTime;
            }
            
            // Store facility time range and max booking hours globally
            const maxBookingHours = facility.max_booking_hours || 1;
            window.currentFacilityTimeRange = { start: startTime, end: endTime };
            window.currentFacilityMaxBookingHours = maxBookingHours;
            
            // Handle multi-attendees feature
            const attendeesContainer = document.getElementById('attendeesFieldContainer');
            const attendeesList = document.getElementById('attendeesList');
            const addAttendeeBtn = document.getElementById('addAttendeeBtn');
            const enableMultiAttendees = facility.enable_multi_attendees || false;
            const maxAttendees = facility.max_attendees || facility.capacity || 1000;
            
            // Store facility multi-attendees settings globally
            window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
            window.currentFacilityMaxAttendees = maxAttendees;
            
            if (attendeesContainer && attendeesList) {
                if (enableMultiAttendees) {
                    // Show attendees field if multi-attendees is enabled
                    attendeesContainer.style.display = 'block';
                    if (addAttendeeBtn) {
                        addAttendeeBtn.style.display = 'inline-block';
                    }
                    // Add first attendee field if list is empty
                    if (attendeesList.children.length === 0) {
                        addAttendeeField();
                    }
                } else {
                    // Hide attendees field if multi-attendees is disabled
                    attendeesContainer.style.display = 'none';
                    if (addAttendeeBtn) {
                        addAttendeeBtn.style.display = 'none';
                    }
                    attendeesList.innerHTML = '';
                }
            }
        }
    } catch (error) {
        console.error('Error loading facility for time constraints:', error);
    }
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
                    <th>Booking #</th>
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
                        <td>${booking.booking_number}</td>
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
                            ${(API.isAdmin() || API.isStaff()) ? `
                                <button class="btn-sm btn-warning" onclick="editBooking(${booking.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${booking.status === 'pending' ? `
                                    <div class="dropdown-menu-container">
                                        <button class="btn-sm btn-secondary" onclick="toggleDropdown(${booking.id})" title="More Actions" style="position: relative;">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="dropdown-${booking.id}">
                                            <button class="dropdown-item" onclick="approveBooking(${booking.id})">
                                                <i class="fas fa-check text-success"></i> Approve
                                            </button>
                                            <button class="dropdown-item" onclick="rejectBooking(${booking.id})">
                                                <i class="fas fa-times text-danger"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                ` : ''}
                            ` : `
                                ${booking.status === 'pending' ? `
                                    <button class="btn-sm btn-danger" onclick="cancelBooking(${booking.id})" title="Cancel">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                ` : ''}
                            `}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Global functions are already defined at the top of the script

// Bind form submit event
function bindBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        // Remove existing event listener if any
        const newForm = bookingForm.cloneNode(true);
        bookingForm.parentNode.replaceChild(newForm, bookingForm);
        
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const bookingId = this.dataset.bookingId; // Check if editing

    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (!submitBtn) {
        alert('Submit button not found');
        return;
    }
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    // Validate time slot selection
    if (!selectedTimeSlots || selectedTimeSlots.length === 0) {
        const errorDiv = document.getElementById('timeSlotError');
        if (errorDiv) {
            errorDiv.textContent = 'Please select at least one time slot from the timetable';
            errorDiv.style.display = 'block';
        }
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    // Get all selected slots for the same date (should be continuous)
    const facilitySelect = document.getElementById('bookingFacility');
    
    // Group slots by date
    const slotsByDate = {};
    selectedTimeSlots.forEach(slot => {
        if (!slotsByDate[slot.date]) {
            slotsByDate[slot.date] = [];
        }
        slotsByDate[slot.date].push(slot);
    });
    
    // For now, we'll use the first date's slots (can be extended for multi-day)
    const dates = Object.keys(slotsByDate);
    if (dates.length === 0) {
        alert('Please select at least one time slot');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    const date = dates[0];
    const dateSlots = slotsByDate[date].sort((a, b) => a.start.localeCompare(b.start));
    
    // Get facility ID and check max_booking_hours limit
    const facilityId = facilitySelect ? facilitySelect.value : null;
    if (!facilityId) {
        alert('Please select a facility');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    const purpose = document.getElementById('bookingPurpose').value;
    if (!purpose) {
        alert('Please enter a purpose for the booking');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    // Check max_booking_hours limit (total hours from all selected slots)
    const maxBookingHours = window.currentFacilityMaxBookingHours || 1;
    try {
        const bookingsResult = await API.get('/bookings/user/my-bookings');
        if (bookingsResult.success) {
            const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
            const bookingsOnDate = userBookings.filter(b => {
                const bookingDate = b.booking_date || '';
                const bookingFacilityId = b.facility_id || b.facility?.id;
                return bookingDate === date && bookingFacilityId == facilityId && 
                       (b.status === 'pending' || b.status === 'approved');
            });
            
            const totalHours = bookingsOnDate.reduce((sum, b) => sum + (b.duration_hours || 1), 0);
            const newBookingHours = dateSlots.length; // Each slot is 1 hour
            
            if (totalHours + newBookingHours > maxBookingHours) {
                alert(`You have reached the maximum booking limit for this facility on this date.\n\nMaximum allowed: ${maxBookingHours} hour(s)\nYour current bookings: ${totalHours} hour(s)\nAfter this booking: ${totalHours + newBookingHours} hour(s)`);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
        }
    } catch (error) {
        console.error('Error checking booking limit:', error);
    }
    
    // Create a separate booking for each selected time slot
    // This allows non-continuous time slots (e.g., 8-9 and 11-12)
    // Get attendees passports: if multi-attendees is enabled, collect all passport inputs; otherwise default to 1
    let expectedAttendees = 1; // Default to 1
    let attendeesPassports = []; // Array to store passport numbers
    const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
    
    if (enableMultiAttendees) {
        // Collect all passport inputs
        const passportInputs = document.querySelectorAll('.attendee-passport-input');
        passportInputs.forEach(input => {
            const passport = input.value.trim();
            if (passport) {
                attendeesPassports.push(passport);
            }
        });
        
        if (attendeesPassports.length === 0) {
            alert('Please enter at least one attendee passport number.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        expectedAttendees = attendeesPassports.length;
    } else {
        // If multi-attendees is disabled, expectedAttendees is already set to 1
        expectedAttendees = 1;
    }
    
    // Ensure expectedAttendees is always a valid integer
    expectedAttendees = parseInt(expectedAttendees) || 1;
    
    try {
        let successCount = 0;
        let errorCount = 0;
        const errors = [];
        
        // Ensure date is in YYYY-MM-DD format first (before the loop)
        let bookingDate = date;
        if (date instanceof Date) {
            // If date is a Date object, convert to YYYY-MM-DD
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            bookingDate = `${year}-${month}-${day}`;
        } else if (typeof date === 'string') {
            // If date is a string, ensure it's in YYYY-MM-DD format
            // Remove any time portion if present
            bookingDate = date.split('T')[0].split(' ')[0];
        }
        
        // Create bookings for each slot
        for (const slot of dateSlots) {
            const slotStartTime = `${bookingDate} ${slot.start}:00`;
            const slotEndTime = `${bookingDate} ${slot.end}:00`;
            
            const data = {
                facility_id: parseInt(facilityId),
                booking_date: bookingDate,
                start_time: slotStartTime,
                end_time: slotEndTime,
                purpose: purpose,
                expected_attendees: expectedAttendees,
                attendees_passports: enableMultiAttendees ? attendeesPassports : []
            };
            
            try {
                const result = await API.post('/bookings', data);
                if (result.success) {
                    successCount++;
                } else {
                    errorCount++;
                    // Show detailed error message including validation errors
                    let errorMsg = result.error || result.data?.message || 'Unknown error';
                    if (result.data?.errors) {
                        const validationErrors = Object.values(result.data.errors).flat().join(', ');
                        errorMsg = validationErrors || errorMsg;
                    }
                    errors.push(`${slot.start}-${slot.end}: ${errorMsg}`);
                }
            } catch (error) {
                errorCount++;
                errors.push(`${slot.start}-${slot.end}: ${error.message}`);
            }
        }
        
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
        if (successCount > 0) {
            window.closeModal();
            loadBookings();
            
            // Reset form
            document.getElementById('bookingForm').reset();
            delete document.getElementById('bookingForm').dataset.bookingId;
            
            // Reset attendees field
            const attendeesContainer = document.getElementById('attendeesFieldContainer');
            const attendeesInput = document.getElementById('bookingAttendees');
            if (attendeesContainer) {
                attendeesContainer.style.display = 'none';
            }
            if (attendeesInput) {
                attendeesInput.value = '1'; // Set default to 1
                attendeesInput.removeAttribute('required');
            }
            
            // Reset facility multi-attendees settings
            window.currentFacilityEnableMultiAttendees = false;
            window.currentFacilityMaxAttendees = null;
            
            // Reset modal title and button
            const modalTitle = document.getElementById('modalTitle');
            const modalIcon = document.getElementById('modalIcon');
            const submitButtonText = document.getElementById('submitButtonText');
            
            if (modalTitle) modalTitle.textContent = 'Create New Booking';
            if (modalIcon) modalIcon.className = 'fas fa-plus-circle me-2 text-primary';
            if (submitButtonText) submitButtonText.textContent = 'Submit Booking';
            
            if (errorCount === 0) {
                alert(`Successfully created ${successCount} booking(s)!`);
            } else {
                alert(`Created ${successCount} booking(s), but ${errorCount} failed:\n\n${errors.join('\n')}`);
            }
        } else {
            alert(`Failed to create bookings:\n\n${errors.join('\n')}`);
        }
    } catch (error) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        alert('Error creating bookings: ' + error.message);
        console.error('Booking submission error:', error);
    }
        });
    }
}
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
    padding: 12px 15px 12px 45px;
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
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* Table Container Enhancement */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    position: relative;
    overflow: visible;
}

.table-container .data-table {
    overflow: hidden;
    border-radius: 12px;
}

.data-table {
    margin: 0;
    width: 100%;
    border-collapse: collapse;
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
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
}

.data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
    color: #2d3436;
}

.data-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Sort arrow styling */
.sort-arrow {
    font-size: 1rem;
    font-weight: 900;
    color: #495057;
    transition: all 0.2s ease;
    margin-left: 8px;
    display: inline-block;
    line-height: 1;
}

.sort-arrow.active {
    color: #667eea;
    font-weight: 900;
}

.sort-arrow:hover {
    color: #667eea;
    transform: scale(1.1);
}

.data-table th div:hover .sort-arrow {
    color: #667eea;
}

.data-table .actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    align-items: center;
}

/* Dropdown Menu Styles - Only for booking page dropdowns, not navigation */
.dropdown-menu-container {
    position: relative;
    display: inline-block;
}

/* Only style dropdowns inside dropdown-menu-container, not user dropdown in navigation */
.dropdown-menu-container .dropdown-menu {
    display: none;
    position: fixed;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 160px;
    z-index: 10000;
    margin-top: 5px;
    overflow: hidden;
    animation: slideDown 0.2s ease;
}

.dropdown-menu-container .dropdown-menu.dropdown-up {
    margin-top: 0;
    margin-bottom: 5px;
    animation: slideUp 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu-container .dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    background: white;
    color: #333;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: #333;
}

.dropdown-item i {
    width: 18px;
    text-align: center;
}

.dropdown-item .text-success {
    color: #28a745;
}

.dropdown-item .text-danger {
    color: #dc3545;
}

.data-table .text-center {
    text-align: center;
    padding: 40px;
    color: #636e72;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

/* Modal Styling - Reference Add New Facility */
.modal-large {
    max-width: 1200px !important;
    width: 95% !important;
    padding: 0 !important;
}

.modal-large .card {
    margin: 0;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.modal-large .card-header {
    padding: 1rem 1.5rem;
    background-color: #ffffff;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.modal-large .card-body {
    padding: 1.5rem;
}

/* Ensure form sections match Add New Facility spacing */
.modal-large .mb-4 {
    margin-bottom: 1.5rem !important;
}

.modal-large .row.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.modal-large .close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #636e72;
    cursor: pointer;
    z-index: 10;
    background: rgba(255, 255, 255, 0.9);
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.modal-large .close:hover {
    color: #2d3436;
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
}

/* Time Validation Error Styles */
.is-invalid {
    border-color: #dc3545 !important;
}

.is-invalid:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
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
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }

    .data-table {
        font-size: 0.85rem;
    }

    .data-table th,
    .data-table td {
        padding: 10px;
    }

    .data-table .actions {
        flex-direction: column;
    }
    
    .dropdown-menu-container .dropdown-menu {
        right: auto;
        left: 0;
    }
}

/* Cancel Booking Modal Styles */
.cancel-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.cancel-modal-content {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cancel-modal-header {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    padding: 25px;
    border-radius: 16px 16px 0 0;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    border-bottom: 2px solid #ffcccc;
}

.cancel-modal-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(245, 124, 0, 0.3);
}

.cancel-modal-header h3 {
    margin: 0;
    color: #d32f2f;
    font-size: 1.5rem;
    font-weight: 700;
    flex: 1;
}

.cancel-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.cancel-modal-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #333;
}

.cancel-modal-body {
    padding: 30px;
}

.cancel-warning-text {
    color: #555;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
    text-align: center;
}

.cancel-reason-section {
    margin-top: 20px;
}

.cancel-reason-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
    font-size: 0.95rem;
}

.cancel-reason-label i {
    color: #dc3545;
}

.cancel-reason-select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

.cancel-reason-select:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.cancel-reason-select:hover {
    border-color: #dc3545;
}

.cancel-custom-reason {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    margin-top: 12px;
    resize: vertical;
    transition: all 0.3s ease;
}

.cancel-custom-reason:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.cancel-modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    background: #f8f9fa;
    border-radius: 0 0 16px 16px;
}

.btn-cancel-modal {
    padding: 12px 24px;
    border: 2px solid #6c757d;
    background: white;
    color: #6c757d;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-cancel-modal:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.btn-confirm-cancel {
    padding: 12px 24px;
    border: none;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-confirm-cancel:hover:not(:disabled) {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
}

.btn-confirm-cancel:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

@media (max-width: 600px) {
    .cancel-modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .cancel-modal-header {
        flex-direction: column;
        text-align: center;
    }
    
    .cancel-modal-footer {
        flex-direction: column;
    }
    
    .btn-cancel-modal,
    .btn-confirm-cancel {
        width: 100%;
        justify-content: center;
    }
}

/* Timetable Styles */
.timetable-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-top: 10px;
}

.timetable-loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.timetable-days {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.timetable-day {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
    transition: all 0.3s;
}

.timetable-day.active {
    border-color: #cb2d3e;
    background: #fff5f7;
    box-shadow: 0 4px 12px rgba(203, 45, 62, 0.2);
}

.timetable-day-header {
    text-align: center;
    margin-bottom: 15px;
}

.timetable-day-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #2d3436;
    margin-bottom: 5px;
}

.timetable-day-date {
    font-size: 1.1rem;
    color: #cb2d3e;
    font-weight: 700;
}

.timetable-day.active .timetable-day-date {
    color: #a01a2a;
}

/* Unavailable day styles */
.timetable-day.unavailable-day {
    background: #e9ecef;
    border-color: #adb5bd;
    opacity: 0.7;
}

.timetable-day.unavailable-day .timetable-day-title {
    color: #6c757d;
}

.timetable-day.unavailable-day .timetable-day-date {
    color: #6c757d;
}

.timetable-day-unavailable-badge {
    margin-top: 8px;
    padding: 4px 8px;
    background: #dc3545;
    color: white;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    width:300px;
}

.timetable-day-unavailable-badge i {
    margin-right: 4px;
}

.timetable-slots {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.timetable-slot {
    padding: 10px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
    position: relative;
}

.timetable-slot:hover:not(.booked):not(.selected) {
    border-color: #cb2d3e;
    background: #fff5f7;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(203, 45, 62, 0.2);
}

.timetable-slot.available {
    border-color: #28a745;
    background: #d4edda;
    color: #155724;
}

.timetable-slot.available:hover {
    border-color: #218838;
    background: #c3e6cb;
}

.timetable-slot.booked:not(.user-booked) {
    border-color: #dc3545;
    background: #f8d7da;
    color: #721c24;
    cursor: not-allowed;
    opacity: 0.7;
}

.timetable-slot.selected {
    border-color: #cb2d3e;
    background: #cb2d3e;
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(203, 45, 62, 0.4);
}

.timetable-slot.user-booked {
    border-color: #ffc107 !important;
    background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%) !important;
    color: #212529 !important;
    font-weight: 700;
    box-shadow: 0 4px 16px rgba(255, 193, 7, 0.6) !important;
    animation: pulse-glow 2s ease-in-out infinite;
    position: relative;
    cursor: default;
    opacity: 1 !important;
}

.timetable-slot.user-booked::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, #ffc107, #ffb300, #ffc107);
    border-radius: 6px;
    z-index: -1;
    opacity: 0.7;
    animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% {
        box-shadow: 0 4px 16px rgba(255, 193, 7, 0.6);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 6px 20px rgba(255, 193, 7, 0.8);
        transform: scale(1.02);
    }
}

@keyframes glow-pulse {
    0%, 100% {
        opacity: 0.7;
    }
    50% {
        opacity: 1;
    }
}

.timetable-slot.user-booked:hover {
    border-color: #ffb300 !important;
    background: linear-gradient(135deg, #ffb300 0%, #ffa000 100%) !important;
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.8) !important;
    transform: translateY(-2px) scale(1.02);
    opacity: 1 !important;
}

.timetable-slot-time {
    display: block;
    font-size: 0.9rem;
}

.timetable-slot-attendees {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 4px;
    opacity: 0.9;
}

.timetable-slot.available .timetable-slot-attendees {
    color: #0d4e1a;
}

.timetable-slot.booked .timetable-slot-attendees {
    color: #8b1a1a;
}

.timetable-slot.selected .timetable-slot-attendees {
    color: rgba(255, 255, 255, 0.95);
}

.timetable-slot.user-booked .timetable-slot-attendees {
    color: #856404;
    font-weight: 700;
}

/* Unavailable slot styles */
.timetable-slot.unavailable {
    border-color: #adb5bd;
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
    position: relative;
}

.timetable-slot.unavailable:hover {
    border-color: #adb5bd;
    background: #e9ecef;
    transform: none;
    box-shadow: none;
}

.timetable-slot-unavailable-text {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 6px;
    color: #6c757d;
    font-style: italic;
    white-space: nowrap;
    letter-spacing: 0.3px;
    padding: 4px 12px;
    background-color: #dee2e6;
    border-radius: 4px;
    text-align: center;
    width: auto;
    min-width: 80%;
    max-width: 95%;
}

.timetable-no-slots {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    font-style: italic;
}

@media (max-width: 992px) {
    .timetable-days {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .timetable-slots {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection


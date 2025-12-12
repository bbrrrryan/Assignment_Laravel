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
                            <div class="col-md-6">
                                <label for="bookingFacility" class="form-label">Facility <span class="text-danger">*</span></label>
                                <select id="bookingFacility" class="form-select" required>
                                    <option value="">Select Facility</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="bookingDate" class="form-label">Booking Date <span class="text-danger">*</span></label>
                                <input type="date" id="bookingDate" class="form-control" required onchange="validateBookingDate()">
                                <small class="form-text text-muted">You can only book from tomorrow onwards</small>
                                <div id="bookingDateError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Time Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-clock me-2"></i>Time Information
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="bookingStartTime" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" id="bookingStartTime" class="form-control" required min="08:00" max="20:00" onchange="validateTimeRange()">
                                <div id="startTimeError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                                <small class="form-text text-muted">Available time: 8:00 AM - 8:00 PM</small>
                            </div>

                            <div class="col-md-6">
                                <label for="bookingEndTime" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" id="bookingEndTime" class="form-control" required min="08:00" max="20:00" onchange="validateTimeRange()">
                                <div id="endTimeError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                                <small class="form-text text-muted">Available time: 8:00 AM - 8:00 PM</small>
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

                            <div class="col-md-6">
                                <label for="bookingAttendees" class="form-label">Expected Attendees</label>
                                <input type="number" id="bookingAttendees" class="form-control" min="1" placeholder="Number of attendees">
                                <small class="form-text text-muted">Optional: Number of people expected to attend</small>
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
    
    // Set minimum date to tomorrow (users can only book from tomorrow onwards)
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        dateInput.min = tomorrowStr;
        dateInput.value = ''; // Clear any previous value
        // Also set max attribute to prevent selecting dates too far in the future (optional, can remove if not needed)
    }
    
    // Set time input constraints (8:00 AM - 8:00 PM)
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    if (startTimeInput) {
        startTimeInput.min = '08:00';
        startTimeInput.max = '20:00';
        startTimeInput.value = '';
    }
    if (endTimeInput) {
        endTimeInput.min = '08:00';
        endTimeInput.max = '20:00';
        endTimeInput.value = '';
    }
    
    // Clear date validation errors
    const dateErrorDiv = document.getElementById('bookingDateError');
    if (dateErrorDiv) {
        dateErrorDiv.style.display = 'none';
        dateErrorDiv.textContent = '';
    }
    if (dateInput) {
        dateInput.classList.remove('is-invalid');
        dateInput.setCustomValidity('');
    }
    
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
    }
    
    // Clear any previous validation errors
    clearTimeValidationErrors();
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
    
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    const minTime = '08:00';
    const maxTime = '20:00';
    
    // Validate start time is within allowed range (8:00 - 20:00)
    if (startTime < minTime || startTime > maxTime) {
        if (startTimeError) {
            startTimeError.textContent = 'Start time must be between 8:00 AM and 8:00 PM';
            startTimeError.style.display = 'block';
        }
        startTimeInput.classList.add('is-invalid');
        startTimeInput.setCustomValidity('Start time must be between 8:00 AM and 8:00 PM');
        return false;
    }
    
    // Validate end time is within allowed range (8:00 - 20:00)
    if (endTime < minTime || endTime > maxTime) {
        if (endTimeError) {
            endTimeError.textContent = 'End time must be between 8:00 AM and 8:00 PM';
            endTimeError.style.display = 'block';
        }
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity('End time must be between 8:00 AM and 8:00 PM');
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

window.filterBookings = function() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter');
    if (!statusFilter) return;
    const status = statusFilter.value;
    
    const filtered = bookings.filter(b => {
        const matchSearch = !search || 
            (b.booking_number && b.booking_number.toLowerCase().includes(search)) ||
            (b.facility?.name && b.facility.name.toLowerCase().includes(search)) ||
            (b.purpose && b.purpose.toLowerCase().includes(search));
        const matchStatus = !status || b.status === status;
        return matchSearch && matchStatus;
    });
    
    if (typeof displayBookings === 'function') {
        displayBookings(filtered);
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
// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-menu-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

window.toggleDropdown = function(id) {
    event.stopPropagation();
    const dropdown = document.getElementById(`dropdown-${id}`);
    const allDropdowns = document.querySelectorAll('.dropdown-menu');
    
    // Close all other dropdowns
    allDropdowns.forEach(menu => {
        if (menu.id !== `dropdown-${id}`) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('show');
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
    document.getElementById('bookingFacility').value = booking.facility_id || '';
    
    // Extract time from datetime strings
    if (booking.start_time) {
        const startDate = new Date(booking.start_time);
        const hours = String(startDate.getHours()).padStart(2, '0');
        const minutes = String(startDate.getMinutes()).padStart(2, '0');
        document.getElementById('bookingStartTime').value = `${hours}:${minutes}`;
    }
    
    if (booking.end_time) {
        const endDate = new Date(booking.end_time);
        const hours = String(endDate.getHours()).padStart(2, '0');
        const minutes = String(endDate.getMinutes()).padStart(2, '0');
        document.getElementById('bookingEndTime').value = `${hours}:${minutes}`;
    }
    
    document.getElementById('bookingPurpose').value = booking.purpose || '';
    document.getElementById('bookingAttendees').value = booking.expected_attendees || '';
    
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
    
    // Set time input constraints (8:00 AM - 8:00 PM)
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
    
    // Update title based on user role
    if (API.isAdmin()) {
        document.getElementById('bookingsTitle').textContent = 'Booking Management';
        document.getElementById('bookingsSubtitle').textContent = 'Manage all bookings in the system';
        // Hide "New Booking" button for admin - admin can only manage existing bookings
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.style.display = 'none';
        }
    } else {
        document.getElementById('bookingsTitle').textContent = 'My Bookings';
        document.getElementById('bookingsSubtitle').textContent = 'Manage your facility bookings';
        // Show "New Booking" button for students
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.style.display = 'block';
        }
        // Bind form submit event only for non-admin users
        bindBookingForm();
    }
    
    loadBookings();
    loadFacilities();
}

async function loadBookings() {
    showLoading(document.getElementById('bookingsList'));
    
    // If user is not admin, load only their own bookings
    const endpoint = API.isAdmin() ? '/bookings' : '/bookings/user/my-bookings';
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
                    const isDisabled = f.is_at_capacity || f.status !== 'available';
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const selectedAttr = (currentValue == f.id) ? 'selected' : '';
                    const capacityInfo = f.is_at_capacity 
                        ? ` (Full - ${f.total_approved_attendees}/${f.capacity} attendees)` 
                        : ` (${f.total_approved_attendees || 0}/${f.capacity} attendees)`;
                    return `<option value="${f.id}" ${disabledAttr} ${selectedAttr}>${f.name} (${f.code}) - ${f.status}${capacityInfo}</option>`;
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
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Attendees</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${bookingsToShow.map(booking => `
                    <tr>
                        <td>${booking.booking_number}</td>
                        <td>${booking.facility?.name || 'N/A'}</td>
                        <td>${formatDate(booking.booking_date)}</td>
                        <td>${new Date(booking.start_time).toLocaleTimeString()} - ${new Date(booking.end_time).toLocaleTimeString()}</td>
                        <td>${booking.purpose ? (booking.purpose.length > 30 ? booking.purpose.substring(0, 30) + '...' : booking.purpose) : 'N/A'}</td>
                        <td>${booking.expected_attendees || 'N/A'}</td>
                        <td>
                            <span class="badge badge-${booking.status === 'approved' ? 'success' : (booking.status === 'pending' ? 'warning' : (booking.status === 'rejected' ? 'danger' : 'secondary'))}">
                                ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                        </td>
                        <td class="actions">
                            <button class="btn-sm btn-info" onclick="viewBooking(${booking.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${API.isAdmin() ? `
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

    const date = document.getElementById('bookingDate').value;
    const startTimeInput = document.getElementById('bookingStartTime').value;
    const endTimeInput = document.getElementById('bookingEndTime').value;

    // Combine date and time to create datetime string
    let startTime = null;
    let endTime = null;
    
    if (date && startTimeInput) {
        // Combine booking date with start time
        startTime = `${date} ${startTimeInput}:00`;
    }
    
    if (date && endTimeInput) {
        // Combine booking date with end time
        endTime = `${date} ${endTimeInput}:00`;
    }
    
    // Double-check: Ensure start time is before end time
    if (startTime && endTime) {
        const startDateTime = new Date(startTime);
        const endDateTime = new Date(endTime);
        
        if (startDateTime >= endDateTime) {
            alert('Error: Start time must be before end time. Please check your time selection.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            return;
        }
    }

    // Validate booking date (must be tomorrow or later)
    if (!validateBookingDate()) {
        alert('You can only book from tomorrow onwards. Please select a future date.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }
    
    // Validate time range before submission (including time constraints 8:00-20:00)
    if (!validateTimeRange()) {
        alert('Please fix the time range error. Start and end times must be between 8:00 AM and 8:00 PM, and end time must be after start time.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }
    
    // Validation
    if (!date || !startTime || !endTime) {
        alert('Please fill in all required fields');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }

    const facilityId = document.getElementById('bookingFacility').value;
    if (!facilityId) {
        alert('Please select a facility');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }

    const purpose = document.getElementById('bookingPurpose').value;
    if (!purpose) {
        alert('Please enter a purpose for the booking');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }

    // Final validation: Ensure start time is before end time
    const startDateTime = new Date(startTime);
    const endDateTime = new Date(endTime);
    
    if (startDateTime >= endDateTime) {
        alert('Error: Start time must be before end time. Please check your time selection.\n\nStart: ' + startTime + '\nEnd: ' + endTime);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        return;
    }

    const data = {
        facility_id: parseInt(facilityId),
        booking_date: date,
        start_time: startTime,
        end_time: endTime,
        purpose: purpose,
        expected_attendees: document.getElementById('bookingAttendees').value ? parseInt(document.getElementById('bookingAttendees').value) : null
    };

    console.log('Submitting booking:', data); // Debug
    console.log('Start time input:', startTimeInput);
    console.log('End time input:', endTimeInput);
    console.log('Combined start time:', startTime);
    console.log('Combined end time:', endTime);
    console.log('Start DateTime object:', startDateTime);
    console.log('End DateTime object:', endDateTime);
    console.log('Is start < end?', startDateTime < endDateTime);

    try {
        let result;
        if (bookingId) {
            // Update existing booking
            result = await API.put(`/bookings/${bookingId}`, data);
        } else {
            // Create new booking
            result = await API.post('/bookings', data);
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }

        if (result.success) {
            window.closeModal();
            loadBookings();
            
            // Reset form
            document.getElementById('bookingForm').reset();
            delete document.getElementById('bookingForm').dataset.bookingId;
            
            // Reset modal title and button
            const modalTitle = document.getElementById('modalTitle');
            const modalIcon = document.getElementById('modalIcon');
            const submitButtonText = document.getElementById('submitButtonText');
            
            if (modalTitle) modalTitle.textContent = 'Create New Booking';
            if (modalIcon) modalIcon.className = 'fas fa-plus-circle me-2 text-primary';
            if (submitButtonText) submitButtonText.textContent = 'Submit Booking';
            
            alert(bookingId ? 'Booking updated successfully!' : 'Booking created successfully!');
        } else {
            // Show detailed error message
            let errorMsg = 'Error creating booking';
            if (result.error) {
                errorMsg = result.error;
            } else if (result.data?.message) {
                errorMsg = result.data.message;
            } else if (result.data?.errors) {
                // Handle validation errors
                const errors = result.data.errors;
                const errorList = Object.values(errors).flat().join('\n');
                errorMsg = 'Validation errors:\n' + errorList;
            }
            alert('Error: ' + errorMsg);
            console.error('Booking error:', result); // Debug
        }
    } catch (error) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        alert('Error creating booking: ' + error.message);
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
    overflow: hidden;
    border: 1px solid #e9ecef;
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

.data-table .actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    align-items: center;
}

/* Dropdown Menu Styles */
.dropdown-menu-container {
    position: relative;
    display: inline-block;
}


.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 160px;
    z-index: 1000;
    margin-top: 5px;
    overflow: hidden;
    animation: slideDown 0.2s ease;
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

.dropdown-menu.show {
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
    
    .dropdown-menu {
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
</style>
@endsection


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
                                <input type="date" id="bookingDate" class="form-control" required>
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
                                <input type="datetime-local" id="bookingStartTime" class="form-control" required min="">
                            </div>

                            <div class="col-md-6">
                                <label for="bookingEndTime" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="bookingEndTime" class="form-control" required min="">
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
    document.getElementById('modalTitle').textContent = 'Create New Booking';
    document.getElementById('modalIcon').className = 'fas fa-plus-circle me-2 text-primary';
    document.getElementById('submitButtonText').textContent = 'Submit Booking';
    
    // Set minimum date to today
    const today = new Date().toISOString().slice(0, 16);
    const dateInput = document.getElementById('bookingDate');
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
    if (startTimeInput) {
        startTimeInput.min = today;
    }
    if (endTimeInput) {
        endTimeInput.min = today;
    }
    
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
    }
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

window.cancelBooking = async function(id) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    const result = await API.put(`/bookings/${id}/cancel`, { reason: 'Cancelled by user' });
    if (result.success) {
        if (typeof loadBookings === 'function') {
            loadBookings();
        }
        alert('Booking cancelled successfully!');
    } else {
        alert(result.error || 'Error cancelling booking');
    }
};

// Admin functions for managing bookings - defined at top level for onclick handlers
window.approveBooking = async function(id) {
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
    
    // Load facilities first
    if (typeof loadFacilities === 'function') {
        await loadFacilities();
    }
    
    // Populate form with booking data
    document.getElementById('bookingFacility').value = booking.facility_id || '';
    document.getElementById('bookingDate').value = booking.booking_date || '';
    
    // Convert datetime strings to datetime-local format
    if (booking.start_time) {
        const startDate = new Date(booking.start_time);
        const startLocal = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('bookingStartTime').value = startLocal;
    }
    
    if (booking.end_time) {
        const endDate = new Date(booking.end_time);
        const endLocal = new Date(endDate.getTime() - endDate.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        document.getElementById('bookingEndTime').value = endLocal;
    }
    
    document.getElementById('bookingPurpose').value = booking.purpose || '';
    document.getElementById('bookingAttendees').value = booking.expected_attendees || '';
    
    // Store booking ID for update
    document.getElementById('bookingForm').dataset.bookingId = id;
    
    // Change form title and submit button
    document.getElementById('modalTitle').textContent = 'Edit Booking';
    document.getElementById('modalIcon').className = 'fas fa-edit me-2 text-primary';
    document.getElementById('submitButtonText').textContent = 'Update Booking';
    
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

async function loadFacilities() {
    const result = await API.get('/facilities');
    
    if (result.success) {
        facilities = result.data.data?.data || result.data.data || [];
        const select = document.getElementById('bookingFacility');
        
        if (facilities.length === 0) {
            select.innerHTML = '<option value="">No facilities available. Please create a facility first.</option>';
            select.disabled = true;
            alert('No facilities available. Please create a facility first.');
        } else {
            select.disabled = false;
            select.innerHTML = '<option value="">Select Facility</option>' +
                facilities.map(f => `<option value="${f.id}">${f.name} (${f.code}) - ${f.status}</option>`).join('');
        }
    } else {
        const select = document.getElementById('bookingFacility');
        select.innerHTML = '<option value="">Error loading facilities</option>';
        select.disabled = true;
        console.error('Error loading facilities:', result);
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
                                ${booking.status === 'pending' ? `
                                    <button class="btn-sm btn-success" onclick="approveBooking(${booking.id})" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn-sm btn-danger" onclick="rejectBooking(${booking.id})" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                                <button class="btn-sm btn-warning" onclick="editBooking(${booking.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-sm btn-danger" onclick="deleteBooking(${booking.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
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
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    const date = document.getElementById('bookingDate').value;
    const startTimeInput = document.getElementById('bookingStartTime').value;
    const endTimeInput = document.getElementById('bookingEndTime').value;

    // Convert datetime-local to Y-m-d H:i:s format (use local time, not UTC)
    let startTime = null;
    let endTime = null;
    
    if (startTimeInput) {
        const startDate = new Date(startTimeInput);
        // Use local time, not UTC
        const year = startDate.getFullYear();
        const month = String(startDate.getMonth() + 1).padStart(2, '0');
        const day = String(startDate.getDate()).padStart(2, '0');
        const hours = String(startDate.getHours()).padStart(2, '0');
        const minutes = String(startDate.getMinutes()).padStart(2, '0');
        startTime = `${year}-${month}-${day} ${hours}:${minutes}:00`;
    }
    
    if (endTimeInput) {
        const endDate = new Date(endTimeInput);
        // Use local time, not UTC
        const year = endDate.getFullYear();
        const month = String(endDate.getMonth() + 1).padStart(2, '0');
        const day = String(endDate.getDate()).padStart(2, '0');
        const hours = String(endDate.getHours()).padStart(2, '0');
        const minutes = String(endDate.getMinutes()).padStart(2, '0');
        endTime = `${year}-${month}-${day} ${hours}:${minutes}:00`;
    }

    // Validation
    if (!date || !startTime || !endTime) {
        alert('Please fill in all required fields');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }

    const facilityId = document.getElementById('bookingFacility').value;
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

    const data = {
        facility_id: parseInt(facilityId),
        booking_date: date,
        start_time: startTime,
        end_time: endTime,
        purpose: purpose,
        expected_attendees: document.getElementById('bookingAttendees').value ? parseInt(document.getElementById('bookingAttendees').value) : null
    };

    console.log('Submitting booking:', data); // Debug
    console.log('Start time:', startTime);
    console.log('End time:', endTime);

    try {
        let result;
        if (bookingId) {
            // Update existing booking
            result = await API.put(`/bookings/${bookingId}`, data);
        } else {
            // Create new booking
            result = await API.post('/bookings', data);
        }

        submitBtn.disabled = false;
        submitBtn.textContent = originalText;

        if (result.success) {
            window.closeModal();
            loadBookings();
            
            // Reset form
            document.getElementById('bookingForm').reset();
            delete document.getElementById('bookingForm').dataset.bookingId;
            
            // Reset modal title and button
            document.getElementById('modalTitle').textContent = 'Create New Booking';
            document.getElementById('modalIcon').className = 'fas fa-plus-circle me-2 text-primary';
            document.getElementById('submitButtonText').textContent = 'Submit Booking';
            
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
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
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
}
</style>
@endsection


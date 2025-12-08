@extends('layouts.app')

@section('title', 'Bookings - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 id="bookingsTitle">My Bookings</h1>
        <button id="newBookingBtn" class="btn-primary" onclick="showCreateModal()" style="display: none;">
            <i class="fas fa-plus"></i> New Booking
        </button>
    </div>

    <div class="filters">
        <select id="statusFilter" onchange="filterBookings()">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div id="bookingsList" class="table-container">
        <p>Loading bookings...</p>
    </div>
</div>

<!-- Create Booking Modal -->
<div id="bookingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Create New Booking</h2>
        <form id="bookingForm">
            <div class="form-group">
                <label>Facility *</label>
                <select id="bookingFacility" required></select>
            </div>
            <div class="form-group">
                <label>Booking Date *</label>
                <input type="date" id="bookingDate" required>
            </div>
            <div class="form-group">
                <label>Start Time *</label>
                <input type="datetime-local" id="bookingStartTime" required min="">
            </div>
            <div class="form-group">
                <label>End Time *</label>
                <input type="datetime-local" id="bookingEndTime" required min="">
            </div>
            <div class="form-group">
                <label>Purpose *</label>
                <textarea id="bookingPurpose" required rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Expected Attendees</label>
                <input type="number" id="bookingAttendees" min="1">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Submit Booking</button>
            </div>
        </form>
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
    }
    
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
    const statusFilter = document.getElementById('statusFilter');
    if (!statusFilter) return;
    const status = statusFilter.value;
    const filtered = status ? bookings.filter(b => b.status === status) : bookings;
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
    const modalTitle = document.querySelector('#bookingModal h2');
    if (modalTitle) {
        modalTitle.textContent = 'Edit Booking';
    }
    
    const submitBtn = document.querySelector('#bookingForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.textContent = 'Update Booking';
    }
    
    // Show modal
    window.showCreateModal();
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
        // Hide "New Booking" button for admin - admin can only manage existing bookings
        const newBookingBtn = document.getElementById('newBookingBtn');
        if (newBookingBtn) {
            newBookingBtn.style.display = 'none';
        }
    } else {
        document.getElementById('bookingsTitle').textContent = 'My Bookings';
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
        container.innerHTML = '<p>No bookings found</p>';
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
                        <td><span class="status-badge status-${booking.status}">${booking.status}</span></td>
                        <td>
                            <button class="btn-sm" onclick="viewBooking(${booking.id})">View</button>
                            ${API.isAdmin() ? `
                                ${booking.status === 'pending' ? `
                                    <button class="btn-sm btn-success" onclick="approveBooking(${booking.id})">Approve</button>
                                    <button class="btn-sm btn-danger" onclick="rejectBooking(${booking.id})">Reject</button>
                                ` : ''}
                                <button class="btn-sm" onclick="editBooking(${booking.id})">Edit</button>
                                <button class="btn-sm btn-danger" onclick="deleteBooking(${booking.id})">Delete</button>
                            ` : `
                                ${booking.status === 'pending' ? `<button class="btn-sm btn-danger" onclick="cancelBooking(${booking.id})">Cancel</button>` : ''}
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
            const modalTitle = document.querySelector('#bookingModal h2');
            if (modalTitle) {
                modalTitle.textContent = 'Create New Booking';
            }
            const submitBtn = document.querySelector('#bookingForm button[type="submit"]');
            if (submitBtn) {
                submitBtn.textContent = 'Submit Booking';
            }
            
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
@endsection


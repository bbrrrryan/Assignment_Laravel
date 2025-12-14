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

<!-- Reschedule Request Modal -->
<div id="rescheduleModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeRescheduleModal()">&times;</span>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Request Reschedule
                </h5>
            </div>
            <div class="card-body">
                <form id="rescheduleForm">
                    <input type="hidden" id="rescheduleBookingId">
                    
                    <!-- Current Booking Info -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle me-2"></i>Current Booking
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Date</label>
                                <input type="text" id="currentBookingDate" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Current Time</label>
                                <input type="text" id="currentBookingTime" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Facility</label>
                                <input type="text" id="currentFacility" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- New Booking Details -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3 border-bottom pb-2">
                            <i class="fas fa-calendar-check me-2"></i>New Booking Details
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="rescheduleFacility" class="form-label">Facility <span class="text-danger">*</span></label>
                                <select id="rescheduleFacility" class="form-select" required onchange="handleRescheduleFacilityChange(this)">
                                    <option value="">Select Facility</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Select New Time Slot <span class="text-danger">*</span></label>
                                <div id="rescheduleTimetableContainer" class="timetable-container">
                                    <div class="timetable-loading">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...
                                    </div>
                                </div>
                                <input type="hidden" id="rescheduleStartTime" required>
                                <input type="hidden" id="rescheduleEndTime" required>
                                <input type="hidden" id="rescheduleSelectedDate" required>
                                <div id="rescheduleTimeSlotError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                            </div>
                            
                            <div class="col-12">
                                <label for="rescheduleReason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                                <textarea id="rescheduleReason" class="form-control" required rows="3" placeholder="Please provide a reason for rescheduling..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary" onclick="closeRescheduleModal()">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/bookings/index.css') }}">
<script src="{{ asset('js/bookings/index.js') }}"></script>
<script>
// Reschedule modal functions
let rescheduleSelectedTimeSlots = [];
let rescheduleBookedSlots = {};

function showRescheduleModal(booking) {
    const modal = document.getElementById('rescheduleModal');
    const bookingIdInput = document.getElementById('rescheduleBookingId');
    const currentDateInput = document.getElementById('currentBookingDate');
    const currentTimeInput = document.getElementById('currentBookingTime');
    const currentFacilityInput = document.getElementById('currentFacility');
    
    // Set booking ID
    bookingIdInput.value = booking.id;
    
    // Set current booking info
    const bookingDate = new Date(booking.booking_date);
    currentDateInput.value = bookingDate.toLocaleDateString();
    
    const startTime = formatTimeNoSeconds(booking.start_time);
    const endTime = formatTimeNoSeconds(booking.end_time);
    currentTimeInput.value = `${startTime} - ${endTime}`;
    
    currentFacilityInput.value = booking.facility?.name || 'N/A';
    
    // Reset form
    document.getElementById('rescheduleForm').reset();
    bookingIdInput.value = booking.id;
    rescheduleSelectedTimeSlots = [];
    
    // Load facilities
    loadRescheduleFacilities(booking.facility_id);
    
    // Show modal
    modal.style.display = 'block';
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
    rescheduleSelectedTimeSlots = [];
}

async function loadRescheduleFacilities(currentFacilityId = null) {
    const result = await API.get('/facilities');
    const select = document.getElementById('rescheduleFacility');
    
    if (result.success) {
        const facilities = result.data.data?.data || result.data.data || [];
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select Facility</option>' +
            facilities.map(f => {
                const selected = currentFacilityId && f.id == currentFacilityId ? 'selected' : '';
                return `<option value="${f.id}" ${selected}>${f.name} (${f.code})</option>`;
            }).join('');
        
        // If current facility is set, load timetable
        if (currentFacilityId) {
            select.value = currentFacilityId;
            loadRescheduleTimetable(currentFacilityId);
        }
    }
}

window.handleRescheduleFacilityChange = function(select) {
    if (select && select.value) {
        loadRescheduleTimetable(select.value);
    } else {
        clearRescheduleTimetable();
    }
};

// Reuse timetable functions for reschedule
async function loadRescheduleTimetable(facilityId) {
    const container = document.getElementById('rescheduleTimetableContainer');
    container.innerHTML = '<div class="timetable-loading"><i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...</div>';
    
    try {
        // Use the same functions from index.js
        if (typeof getNextThreeDays === 'undefined' || typeof generateTimeSlots === 'undefined') {
            console.error('Required functions not found');
            container.innerHTML = '<div class="timetable-no-slots">Error: Required functions not loaded</div>';
            return;
        }
        
        const days = getNextThreeDays();
        const facilityResult = await API.get(`/facilities/${facilityId}`);
        
        let facilityStartTime = '08:00';
        let facilityEndTime = '20:00';
        let facilityCapacity = 1000;
        
        if (facilityResult.success && facilityResult.data) {
            const facility = facilityResult.data.data || facilityResult.data;
            window.currentRescheduleFacility = facility; // Store for later use
            facilityCapacity = facility.capacity || 1000;
            if (facility.available_time && typeof facility.available_time === 'object') {
                if (facility.available_time.start) facilityStartTime = facility.available_time.start;
                if (facility.available_time.end) facilityEndTime = facility.available_time.end;
            }
        }
        
        const slots = generateTimeSlots(facilityStartTime, facilityEndTime);
        
        // Load booked slots
        rescheduleBookedSlots = {};
        const availabilityPromises = days.map(async (day) => {
            try {
                const result = await API.get(`/facilities/${facilityId}/availability?date=${day.date}`);
                let bookings = [];
                if (result.success && result.data) {
                    if (result.data.data && result.data.data.bookings) {
                        bookings = result.data.data.bookings;
                    } else if (result.data.bookings) {
                        bookings = result.data.bookings;
                    }
                }
                
                rescheduleBookedSlots[day.date] = bookings.map(booking => ({
                    start_time: `${day.date} ${booking.start_time}:00`,
                    end_time: `${day.date} ${booking.end_time}:00`,
                    expected_attendees: booking.expected_attendees || 1
                }));
            } catch (error) {
                rescheduleBookedSlots[day.date] = [];
            }
        });
        
        await Promise.all(availabilityPromises);
        
        // Render timetable (simplified version for reschedule)
        renderRescheduleTimetable(days, slots, facilityId, facilityCapacity);
    } catch (error) {
        console.error('Error loading reschedule timetable:', error);
        container.innerHTML = '<div class="timetable-no-slots">Error loading timetable. Please try again.</div>';
    }
}

function renderRescheduleTimetable(days, slots, facilityId, facilityCapacity) {
    const container = document.getElementById('rescheduleTimetableContainer');
    let html = '<div class="timetable-days">';
    
    days.forEach(day => {
        const dayBookedSlots = rescheduleBookedSlots[day.date] || [];
        html += `<div class="timetable-day" data-date="${day.date}">
            <div class="timetable-day-header">
                <div class="timetable-day-title">${day.display}</div>
                <div class="timetable-day-date">${day.fullDate}</div>
            </div>
            <div class="timetable-slots">`;
        
        slots.forEach(slot => {
            const slotId = `reschedule-slot-${day.date}-${slot.start}`;
            const isSelected = rescheduleSelectedTimeSlots.some(s => 
                s.date === day.date && s.start === slot.start && s.end === slot.end
            );
            
            // Check if slot is booked
            let totalAttendees = 0;
            const slotStart = new Date(`${day.date} ${slot.start}:00`);
            const slotEnd = new Date(`${day.date} ${slot.end}:00`);
            
            dayBookedSlots.forEach(booking => {
                try {
                    const bookingStart = new Date(booking.start_time);
                    const bookingEnd = new Date(booking.end_time);
                    if (slotStart < bookingEnd && slotEnd > bookingStart) {
                        totalAttendees += booking.expected_attendees || 1;
                    }
                } catch (error) {
                    console.error('Error processing booking:', booking, error);
                }
            });
            
            const isAvailable = totalAttendees < facilityCapacity;
            
            const slotClass = isSelected ? 'selected' : (isAvailable ? 'available' : 'booked');
            const onclickAttr = isAvailable ? `onclick="selectRescheduleTimeSlot('${day.date}', '${slot.start}', '${slot.end}', '${slotId}')"` : '';
            
            html += `<div class="timetable-slot ${slotClass}" 
                     data-date="${day.date}" 
                     data-start="${slot.start}" 
                     data-end="${slot.end}"
                     id="${slotId}"
                     ${onclickAttr}>
                <span class="timetable-slot-time">${slot.display}</span>
            </div>`;
        });
        
        html += `</div></div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

window.selectRescheduleTimeSlot = function(date, start, end, slotId) {
    const slot = document.getElementById(slotId);
    if (slot.classList.contains('booked')) return;
    
    const slotIndex = rescheduleSelectedTimeSlots.findIndex(s => 
        s.date === date && s.start === start && s.end === end
    );
    
    if (slotIndex >= 0) {
        rescheduleSelectedTimeSlots.splice(slotIndex, 1);
        slot.classList.remove('selected');
    } else {
        rescheduleSelectedTimeSlots = [{ date, start, end }];
        document.querySelectorAll('#rescheduleTimetableContainer .timetable-slot.selected').forEach(s => {
            s.classList.remove('selected');
        });
        slot.classList.add('selected');
    }
    
    // Update hidden inputs
    if (rescheduleSelectedTimeSlots.length > 0) {
        const slot = rescheduleSelectedTimeSlots[0];
        document.getElementById('rescheduleSelectedDate').value = slot.date;
        document.getElementById('rescheduleStartTime').value = `${slot.date} ${slot.start}:00`;
        document.getElementById('rescheduleEndTime').value = `${slot.date} ${slot.end}:00`;
    }
    
    const errorDiv = document.getElementById('rescheduleTimeSlotError');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
};

function clearRescheduleTimetable() {
    const container = document.getElementById('rescheduleTimetableContainer');
    if (container) {
        container.innerHTML = '<div class="timetable-no-slots">Please select a facility to view available time slots</div>';
    }
    rescheduleSelectedTimeSlots = [];
}

// Submit reschedule request
document.getElementById('rescheduleForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const bookingId = document.getElementById('rescheduleBookingId').value;
    const reason = document.getElementById('rescheduleReason').value;
    
    if (!reason.trim()) {
        alert('Please provide a reason for rescheduling');
        return;
    }
    
    if (rescheduleSelectedTimeSlots.length === 0) {
        const errorDiv = document.getElementById('rescheduleTimeSlotError');
        if (errorDiv) {
            errorDiv.textContent = 'Please select a time slot';
            errorDiv.style.display = 'block';
        }
        return;
    }
    
    const slot = rescheduleSelectedTimeSlots[0];
    const requestedStartTime = `${slot.date} ${slot.start}:00`;
    const requestedEndTime = `${slot.date} ${slot.end}:00`;
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
    
    try {
        const result = await API.put(`/bookings/${bookingId}/request-reschedule`, {
            requested_booking_date: slot.date,
            requested_start_time: requestedStartTime,
            requested_end_time: requestedEndTime,
            reschedule_reason: reason
        });
        
        if (result.success) {
            closeRescheduleModal();
            loadBookings();
            alert('Reschedule request submitted successfully!');
        } else {
            alert('Error: ' + (result.error || 'Failed to submit reschedule request'));
        }
    } catch (error) {
        alert('Error: ' + (error.message || 'An unexpected error occurred'));
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>
@endsection

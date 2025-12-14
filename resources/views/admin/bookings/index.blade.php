@extends('layouts.app')

@section('title', 'Booking Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Booking Management</h1>
            <p>Manage all bookings in the system</p>
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
                    <input type="text" id="searchInput" placeholder="Search by booking number, facility, user or purpose..." 
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
                        <option value="completed">Completed</option>
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

<!-- Approve Booking Confirmation Modal -->
<div id="approveBookingModal" class="cancel-modal" style="display: none;" onclick="if(event.target === this) closeApproveModal()">
    <div class="cancel-modal-content" onclick="event.stopPropagation()">
        <div class="cancel-modal-header" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-bottom: 2px solid #bae6fd;">
            <div class="cancel-modal-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="color: #059669;">Approve Booking</h3>
            <span class="cancel-modal-close" onclick="closeApproveModal()">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <p class="cancel-warning-text" style="color: #059669;">
                Are you sure you want to approve this booking? This action will notify the user.
            </p>
        </div>
        <div class="cancel-modal-footer">
            <button class="btn-cancel-modal" onclick="closeApproveModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn-confirm-cancel" onclick="confirmApproveBooking()" id="confirmApproveBtn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fas fa-check"></i> Confirm Approval
            </button>
        </div>
    </div>
</div>

<!-- Reject Booking Confirmation Modal -->
<div id="rejectBookingModal" class="cancel-modal" style="display: none;" onclick="if(event.target === this) closeRejectModal()">
    <div class="cancel-modal-content" onclick="event.stopPropagation()">
        <div class="cancel-modal-header">
            <div class="cancel-modal-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h3>Reject Booking</h3>
            <span class="cancel-modal-close" onclick="closeRejectModal()">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <p class="cancel-warning-text">
                Are you sure you want to reject this booking? This action cannot be undone.
            </p>
            <div class="cancel-reason-section">
                <label for="rejectReason" class="cancel-reason-label">
                    <i class="fas fa-comment-alt"></i> Reason for Rejection <span class="text-danger">*</span>
                </label>
                <select id="rejectReason" class="cancel-reason-select" onchange="handleRejectReasonChange()">
                    <option value="">Select a reason...</option>
                    <option value="facility_unavailable">Facility Unavailable</option>
                    <option value="capacity_exceeded">Capacity Exceeded</option>
                    <option value="time_conflict">Time Conflict</option>
                    <option value="policy_violation">Policy Violation</option>
                    <option value="incomplete_information">Incomplete Information</option>
                    <option value="other">Other (Please specify)</option>
                </select>
                <textarea 
                    id="customRejectReason" 
                    class="cancel-custom-reason" 
                    placeholder="Please provide additional details..."
                    style="display: none;"
                    rows="3"
                ></textarea>
            </div>
        </div>
        <div class="cancel-modal-footer">
            <button class="btn-cancel-modal" onclick="closeRejectModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn-confirm-cancel" onclick="confirmRejectBooking()" id="confirmRejectBtn" disabled>
                <i class="fas fa-check"></i> Confirm Rejection
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

<link rel="stylesheet" href="{{ asset('css/admin/bookings/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/bookings/index.css') }}">
<script src="{{ asset('js/bookings/index.js') }}"></script>
<script src="{{ asset('js/admin/bookings/index.js') }}"></script>
@endsection


@extends('layouts.app')

@section('title', 'Booking Details - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Booking Details</h1>
        <a href="{{ auth()->user()->isAdmin() ? route('admin.bookings.index') : route('bookings.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>
    </div>

    <div id="bookingDetails" class="details-container">
        <p>Loading booking details...</p>
    </div>
</div>

@if(!auth()->user()->isAdmin())
<!-- Cancel Booking Confirmation Modal (User) -->
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
@endif

@if(auth()->user()->isAdmin())
<!-- Cancel Approved Booking Confirmation Modal (Admin) -->
<div id="adminCancelBookingModal" class="cancel-modal" style="display: none;" onclick="if(event.target === this) closeAdminCancelModal()">
    <div class="cancel-modal-content" onclick="event.stopPropagation()">
        <div class="cancel-modal-header" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-bottom: 2px solid #fbbf24;">
            <div class="cancel-modal-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                <i class="fas fa-ban"></i>
            </div>
            <h3 style="color: #d97706;">Cancel Approved Booking</h3>
            <span class="cancel-modal-close" onclick="closeAdminCancelModal()">&times;</span>
        </div>
        <div class="cancel-modal-body">
            <p class="cancel-warning-text" style="color: #d97706;">
                Are you sure you want to cancel this approved booking? This action will notify the user and cannot be undone.
            </p>
            <div class="cancel-reason-section">
                <label for="adminCancelReason" class="cancel-reason-label">
                    <i class="fas fa-comment-alt"></i> Reason for Cancellation <span class="text-danger">*</span>
                </label>
                <select id="adminCancelReason" class="cancel-reason-select" onchange="handleAdminCancelReasonChange()">
                    <option value="">Select a reason...</option>
                    <option value="facility_maintenance">Facility Maintenance</option>
                    <option value="emergency">Emergency Situation</option>
                    <option value="policy_violation">Policy Violation</option>
                    <option value="user_request">User Request</option>
                    <option value="other">Other (Please specify)</option>
                </select>
                <textarea 
                    id="customAdminCancelReason" 
                    class="cancel-custom-reason" 
                    placeholder="Please provide additional details..."
                    style="display: none;"
                    rows="3"
                ></textarea>
            </div>
        </div>
        <div class="cancel-modal-footer">
            <button class="btn-cancel-modal" onclick="closeAdminCancelModal()">
                <i class="fas fa-times"></i> Close
            </button>
            <button class="btn-confirm-cancel" onclick="confirmAdminCancelBooking()" id="confirmAdminCancelBtn" disabled style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <i class="fas fa-ban"></i> Confirm Cancellation
            </button>
        </div>
    </div>
</div>
@endif

<link rel="stylesheet" href="{{ asset('css/bookings/show.css') }}">
<script>
    // Set Blade variables for external JavaScript
    window.bookingId = {{ $id }};
    window.bookingsIndexUrl = '{{ auth()->user()->isAdmin() ? route('admin.bookings.index') : route('bookings.index') }}';
    window.isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
</script>
<script src="{{ asset('js/bookings/show.js') }}"></script>
@endsection


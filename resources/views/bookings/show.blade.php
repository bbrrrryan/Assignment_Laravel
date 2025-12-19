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
<div id="cancelBookingModal" class="modal" style="display: none;" onclick="if(event.target === this) closeCancelModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Cancel</h3>
            <span class="modal-close" onclick="closeCancelModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
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
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeCancelModal()">
                <i class="fas fa-times"></i> Keep Booking
            </button>
            <button type="button" class="btn-danger" onclick="confirmCancelBooking()" id="confirmCancelBtn" disabled>
                <i class="fas fa-ban"></i> Cancel Booking
            </button>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
<!-- Cancel Approved Booking Confirmation Modal (Admin) -->
<div id="adminCancelBookingModal" class="modal" style="display: none;" onclick="if(event.target === this) closeAdminCancelModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Cancel</h3>
            <span class="modal-close" onclick="closeAdminCancelModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel this approved booking? This action will notify the user and cannot be undone.</p>
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
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeAdminCancelModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn-danger" onclick="confirmAdminCancelBooking()" id="confirmAdminCancelBtn" disabled>
                <i class="fas fa-ban"></i> Cancel Booking
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


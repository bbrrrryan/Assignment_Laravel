@extends('layouts.app')

@section('title', 'Create Booking - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Create Booking</h1>
            <p>Book a facility for your event</p>
        </div>
        <div>
            <a href="{{ route('bookings.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form id="bookingForm">
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="bookingFacility">Facility <span class="required">*</span></label>
                    <select id="bookingFacility" class="form-select" required onchange="handleFacilityChange(this)">
                        <option value="">Select Facility</option>
                    </select>
                    <small>Select a facility to view available time slots for the next 3 days</small>
                </div>
            </div>

            <!-- Time Information Section -->
            <div class="form-section">
                <h3><i class="fas fa-clock"></i> Time Information</h3>
                
                <div class="form-group">
                    <label>Select Time Slot <span class="required">*</span></label>
                    <div id="timetableContainer" class="timetable-container">
                        <div class="timetable-no-slots">Please select a facility to view available time slots</div>
                    </div>
                    <input type="hidden" id="bookingStartTime" required>
                    <input type="hidden" id="bookingEndTime" required>
                    <input type="hidden" id="selectedBookingDate" required>
                    <div id="timeSlotError" class="text-danger" style="display: none; font-size: 0.875rem; margin-top: 5px;"></div>
                    <small>Click on an available time slot to select. Green slots are available, red slots are booked.</small>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Additional Information</h3>
                
                <div class="form-group">
                    <label for="bookingPurpose">Purpose <span class="required">*</span></label>
                    <textarea id="bookingPurpose" class="form-input" required rows="3" placeholder="Enter the purpose of this booking..."></textarea>
                </div>

                <div class="form-group" id="attendeesFieldContainer" style="display: none;">
                    <label>Attendees Passport <span class="required">*</span></label>
                    <small style="display: block; margin-bottom: 10px;">Enter passport numbers in format YYWMR##### (e.g., 25WMR00001)</small>
                    <div id="attendeesList">
                        <!-- Attendee inputs will be dynamically added here -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addAttendeeBtn" onclick="addAttendeeField()" style="display: none;">
                        <i class="fas fa-plus me-1"></i> Add Attendee
                    </button>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="{{ route('bookings.index') }}" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Submit Booking
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/bookings/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/bookings/create.css') }}">
<script src="{{ asset('js/bookings/create.js') }}"></script>
@endsection


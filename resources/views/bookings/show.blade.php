@extends('layouts.app')

@section('title', 'Booking Details - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Booking Details</h1>
        <a href="{{ route('bookings.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>
    </div>

    <div id="bookingDetails" class="details-container">
        <p>Loading booking details...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadBookingDetails();
});

async function loadBookingDetails() {
    const bookingId = {{ $id }};
    const result = await API.get(`/bookings/${bookingId}`);

    if (result.success) {
        const booking = result.data.data;
        displayBookingDetails(booking);
    } else {
        document.getElementById('bookingDetails').innerHTML = `
            <div class="error-message">
                <p>Error loading booking details: ${result.error || 'Unknown error'}</p>
                <a href="{{ route('bookings.index') }}" class="btn-primary">Back to Bookings</a>
            </div>
        `;
    }
}

function displayBookingDetails(booking) {
    const container = document.getElementById('bookingDetails');
    
    container.innerHTML = `
        <div class="details-card">
            <div class="details-section">
                <h2>Booking Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Booking Number:</span>
                    <span class="detail-value">${booking.booking_number || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${booking.status}">${booking.status || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Date:</span>
                    <span class="detail-value">${formatDate(booking.booking_date)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">${booking.duration_hours || 0} hours</span>
                </div>
            </div>

            <div class="details-section">
                <h2>Facility Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Facility:</span>
                    <span class="detail-value">${booking.facility?.name || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">${booking.facility?.location || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Capacity:</span>
                    <span class="detail-value">${booking.facility?.capacity || 'N/A'}</span>
                </div>
            </div>

            <div class="details-section">
                <h2>Booking Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Purpose:</span>
                    <span class="detail-value">${booking.purpose || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expected Attendees:</span>
                    <span class="detail-value">${booking.expected_attendees || 'N/A'}</span>
                </div>
                ${booking.special_requirements ? `
                <div class="detail-row">
                    <span class="detail-label">Special Requirements:</span>
                    <span class="detail-value">${typeof booking.special_requirements === 'object' ? JSON.stringify(booking.special_requirements) : booking.special_requirements}</span>
                </div>
                ` : ''}
            </div>

            ${booking.status === 'pending' ? `
            <div class="details-actions">
                <button class="btn-danger" onclick="cancelBooking(${booking.id})">Cancel Booking</button>
            </div>
            ` : ''}
        </div>
    `;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleTimeString();
}

async function cancelBooking(id) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;

    const result = await API.put(`/bookings/${id}/cancel`, { reason: 'Cancelled by user' });

    if (result.success) {
        alert('Booking cancelled successfully!');
        loadBookingDetails();
    } else {
        alert(result.error || 'Error cancelling booking');
    }
}
</script>

<style>
.details-container {
    max-width: 900px;
    margin: 0 auto;
}

.details-card {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.details-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.details-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.details-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
}

.detail-row {
    display: flex;
    margin-bottom: 15px;
    align-items: flex-start;
}

.detail-label {
    font-weight: 600;
    color: #555;
    min-width: 180px;
    margin-right: 20px;
}

.detail-value {
    color: #333;
    flex: 1;
}

.details-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.error-message {
    text-align: center;
    padding: 40px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
}
</style>
@endsection


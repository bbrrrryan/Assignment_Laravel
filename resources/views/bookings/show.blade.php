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
    
    if (!bookingId) {
        document.getElementById('bookingDetails').innerHTML = `
            <div class="error-message">
                <p>Invalid booking ID. Please check the URL.</p>
                <a href="{{ route('bookings.index') }}" class="btn-primary">Back to Bookings</a>
            </div>
        `;
        return;
    }
    
    try {
        const result = await API.get(`/bookings/${bookingId}`);

        if (result.success && result.data && result.data.data) {
            const booking = result.data.data;
            displayBookingDetails(booking);
        } else {
            // Handle case where booking is not found
            const errorMsg = result.error || result.data?.message || 'Booking not found';
            document.getElementById('bookingDetails').innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #dc3545; margin-bottom: 20px;"></i>
                    <h3>Booking Not Found</h3>
                    <p>${errorMsg}</p>
                    <p style="margin-top: 15px; color: #666;">The booking you're looking for may have been deleted or doesn't exist.</p>
                    <a href="{{ route('bookings.index') }}" class="btn-primary" style="margin-top: 20px; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        document.getElementById('bookingDetails').innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #dc3545; margin-bottom: 20px;"></i>
                <h3>Error Loading Booking</h3>
                <p>${error.message || 'An unexpected error occurred while loading booking details.'}</p>
                <a href="{{ route('bookings.index') }}" class="btn-primary" style="margin-top: 20px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
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
                ${booking.status === 'cancelled' && booking.cancellation_reason ? `
                <div class="detail-row">
                    <span class="detail-label">Cancellation Reason:</span>
                    <span class="detail-value" style="color: #dc3545; font-style: italic;">
                        <i class="fas fa-info-circle" style="margin-right: 5px;"></i>${booking.cancellation_reason}
                    </span>
                </div>
                ` : ''}
            </div>

            ${booking.status === 'pending' ? `
            <div class="details-actions">
                <div class="cancel-booking-section">
                    <div class="cancel-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>You can cancel this pending booking if needed</span>
                    </div>
                    <button class="btn-cancel-booking" onclick="cancelBooking(${booking.id}, this)">
                        <i class="fas fa-times-circle"></i>
                        <span>Cancel Booking</span>
                    </button>
                </div>
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

let currentBookingId = null;
let currentCancelButton = null;

function cancelBooking(id, buttonElement) {
    currentBookingId = id;
    currentCancelButton = buttonElement || document.querySelector('.btn-cancel-booking');
    
    // Initialize listeners if not already done
    initCancelModalListeners();
    
    // Reset modal
    document.getElementById('cancelReason').value = '';
    document.getElementById('customCancelReason').value = '';
    document.getElementById('customCancelReason').style.display = 'none';
    document.getElementById('confirmCancelBtn').disabled = true;
    
    // Show modal
    document.getElementById('cancelBookingModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelBookingModal').style.display = 'none';
    currentBookingId = null;
    currentCancelButton = null;
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
    // Save booking ID before closing modal (closeCancelModal sets currentBookingId to null)
    const bookingId = currentBookingId;
    
    if (!bookingId) {
        alert('Error: Booking ID is missing. Please try again.');
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
    
    // Get the button element
    const button = currentCancelButton;
    
    let originalHTML = '';
    if (button) {
        originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Cancelling...</span>';
    }
    
    // Disable confirm button
    const confirmBtn = document.getElementById('confirmCancelBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    
    // Close modal (this will set currentBookingId to null, but we've saved it above)
    closeCancelModal();
    
    try {
        const result = await API.put(`/bookings/${bookingId}/cancel`, { reason: reasonText });

        if (result.success) {
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'success-message';
            successMessage.innerHTML = `
                <div style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
                            border: 2px solid #28a745; 
                            border-radius: 12px; 
                            padding: 20px; 
                            text-align: center; 
                            margin: 20px 0;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h3 style="color: #155724; margin: 0 0 10px 0;">Booking Cancelled Successfully</h3>
                    <p style="color: #155724; margin: 0;">Your booking has been cancelled. Redirecting to bookings list...</p>
                </div>
            `;
            
            const container = document.getElementById('bookingDetails');
            if (container) {
                container.innerHTML = '';
                container.appendChild(successMessage);
            }
            
            // Redirect to bookings list after a short delay
            setTimeout(() => {
                window.location.href = '{{ route("bookings.index") }}';
            }, 2000);
        } else {
            if (button) {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
            alert('❌ Error: ' + (result.error || 'Failed to cancel booking. Please try again.'));
        }
    } catch (error) {
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        alert('❌ Error: ' + (error.message || 'An unexpected error occurred. Please try again.'));
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Cancellation';
        }
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
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.cancel-booking-section {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    border: 1px solid #ffcccc;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
}

.cancel-warning {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
    color: #d32f2f;
    font-size: 0.95rem;
    font-weight: 500;
}

.cancel-warning i {
    font-size: 1.2rem;
    color: #f57c00;
}

.btn-cancel-booking {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 14px 32px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-cancel-booking::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-cancel-booking:hover::before {
    width: 300px;
    height: 300px;
}

.btn-cancel-booking:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
}

.btn-cancel-booking:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-cancel-booking:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
}

.btn-cancel-booking i {
    font-size: 1.1rem;
}

.btn-cancel-booking:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.error-message {
    text-align: center;
    padding: 40px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.error-message h3 {
    color: #856404;
    margin: 15px 0;
    font-size: 1.5rem;
}

.success-message {
    animation: fadeIn 0.5s ease;
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

/* Close modal when clicking outside */
.cancel-modal-content {
    position: relative;
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


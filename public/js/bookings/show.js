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
    const bookingId = window.bookingId;
    
    if (!bookingId) {
        document.getElementById('bookingDetails').innerHTML = `
            <div class="error-message">
                <p>Invalid booking ID. Please check the URL.</p>
                <a href="${window.bookingsIndexUrl}" class="btn-primary">Back to Bookings</a>
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
                    <a href="${window.bookingsIndexUrl}" class="btn-primary" style="margin-top: 20px; display: inline-block;">
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
                <a href="${window.bookingsIndexUrl}" class="btn-primary" style="margin-top: 20px; display: inline-block;">
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
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">${booking.id || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${booking.status}">${booking.status || 'N/A'}</span>
                </div>
                ${booking.slots && booking.slots.length > 0 ? `
                <div class="detail-row">
                    <span class="detail-label">Time Slots:</span>
                    <span class="detail-value">
                        <div class="booking-slots-list">
                            ${booking.slots.map((slot, index) => {
                                // Format slot date
                                const slotDate = formatDate(slot.slot_date);
                                // Format slot time (slot.start_time and slot.end_time are time strings like "08:00:00")
                                const startTime = formatSlotTime(slot.start_time);
                                const endTime = formatSlotTime(slot.end_time);
                                return `
                                    <div class="booking-slot-item">
                                        <span class="slot-number">${index + 1}.</span>
                                        <span class="slot-date">${slotDate}</span>
                                        <span class="slot-time">${startTime} - ${endTime}</span>
                                        <span class="slot-duration">(${slot.duration_hours || 1} hour${slot.duration_hours > 1 ? 's' : ''})</span>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </span>
                </div>
                ` : `
                <div class="detail-row">
                    <span class="detail-label">Booking Date:</span>
                    <span class="detail-value">${formatDate(booking.booking_date)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</span>
                </div>
                `}
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">${booking.duration_hours || 0} hours</span>
                </div>
            </div>

            ${window.isAdmin && booking.user ? `
            <div class="details-section">
                <h2>User Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${booking.user.name || 'N/A'}</span>
                </div>
                ${booking.user.personal_id ? `
                <div class="detail-row">
                    <span class="detail-label">Student ID:</span>
                    <span class="detail-value">${booking.user.personal_id}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}

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
                ${booking.attendees && booking.attendees.length > 0 ? `
                <div class="detail-row">
                    <span class="detail-label">Attendees Passport:</span>
                    <span class="detail-value">
                        <div class="attendees-list">
                            ${booking.attendees.map((attendee, index) => `
                                <div class="attendee-item">
                                    <span class="attendee-number">${index + 1}.</span>
                                    <span class="attendee-passport">${attendee.student_passport || 'N/A'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </span>
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

            ${(booking.status === 'pending' || booking.status === 'approved') && !window.isAdmin ? `
            <div class="details-actions">
                <div class="cancel-booking-section">
                    <p class="cancel-warning-text">
                        <i class="fas fa-exclamation-triangle"></i>
                        You can cancel this ${booking.status} booking if needed
                    </p>
                    <button class="btn-danger" onclick="cancelBooking(${booking.id}, this)">
                        <i class="fas fa-ban"></i> Cancel Booking
                    </button>
                </div>
            </div>
            ` : ''}
            ${booking.status === 'approved' && window.isAdmin ? `
            <div class="details-actions">
                <div class="cancel-booking-section">
                    <p class="cancel-warning-text">
                        <i class="fas fa-exclamation-triangle"></i>
                        You can cancel this approved booking if needed
                    </p>
                    <button class="btn-danger" onclick="adminCancelBooking(${booking.id}, this)">
                        <i class="fas fa-ban"></i> Cancel Booking
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

function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    // If date is a string, extract time directly to avoid timezone conversion issues
    if (typeof date === 'string') {
        // Try to extract time from various formats:
        // - "2025-12-15 08:00:00" (local format)
        // - "2025-12-15T08:00:00.000000Z" (ISO format with Z)
        // - "2025-12-15T08:00:00" (ISO format without timezone)
        let timeStr = '';
        if (date.includes('T')) {
            const timeMatch = date.match(/T(\d{2}:\d{2}:\d{2})/);
            if (timeMatch) {
                timeStr = timeMatch[1];
            }
        } else if (date.includes(' ')) {
            const parts = date.split(' ');
            if (parts.length > 1) {
                timeStr = parts[1];
            }
        }
        
        if (timeStr) {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    }
    
    // Fallback to Date object parsing
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    // Check if the date string includes 'Z' (UTC indicator)
    const isUTC = typeof date === 'string' && date.includes('Z');
    const hours = isUTC ? d.getUTCHours() : d.getHours();
    const minutes = String(isUTC ? d.getUTCMinutes() : d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function formatTime(dateString) {
    return formatTimeNoSeconds(dateString);
}

function formatSlotTime(timeString) {
    if (!timeString) return 'N/A';
    
    // timeString is in format "HH:mm:ss" or "HH:mm"
    const timeParts = timeString.split(':');
    if (timeParts.length < 2) return timeString;
    
    const hour = parseInt(timeParts[0]);
    const minute = timeParts[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    
    return `${hour12}:${minute} ${ampm}`;
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
        if (typeof showToast !== 'undefined') {
            showToast('Error: Booking ID is missing. Please try again.', 'error');
        } else {
            alert('Error: Booking ID is missing. Please try again.');
        }
        return;
    }
    
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
    
    if (!reasonSelect.value) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select a reason for cancellation.', 'warning');
        } else {
            alert('Please select a reason for cancellation.');
        }
        return;
    }
    
    if (reasonSelect.value === 'other' && !customReason.value.trim()) {
        if (typeof showToast !== 'undefined') {
            showToast('Please provide a reason for cancellation.', 'warning');
        } else {
            alert('Please provide a reason for cancellation.');
        }
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
                window.location.href = window.bookingsIndexUrl;
            }, 2000);
        } else {
            if (button) {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
            alert('âŒ Error: ' + (result.error || 'Failed to cancel booking. Please try again.'));
        }
    } catch (error) {
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        alert('âŒ Error: ' + (error.message || 'An unexpected error occurred. Please try again.'));
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Cancellation';
        }
    }
}

// Admin cancel booking functions
let currentAdminCancelBookingId = null;

function adminCancelBooking(id, buttonElement) {
    currentAdminCancelBookingId = id;
    
    // Reset modal
    document.getElementById('adminCancelReason').value = '';
    document.getElementById('customAdminCancelReason').value = '';
    document.getElementById('customAdminCancelReason').style.display = 'none';
    document.getElementById('confirmAdminCancelBtn').disabled = true;
    
    // Show modal
    document.getElementById('adminCancelBookingModal').style.display = 'flex';
}

function closeAdminCancelModal() {
    document.getElementById('adminCancelBookingModal').style.display = 'none';
    currentAdminCancelBookingId = null;
    document.getElementById('adminCancelReason').value = '';
    document.getElementById('customAdminCancelReason').value = '';
    document.getElementById('customAdminCancelReason').style.display = 'none';
    document.getElementById('confirmAdminCancelBtn').disabled = true;
}

function handleAdminCancelReasonChange() {
    const reasonSelect = document.getElementById('adminCancelReason');
    const customReason = document.getElementById('customAdminCancelReason');
    const confirmBtn = document.getElementById('confirmAdminCancelBtn');
    
    if (reasonSelect.value === 'other') {
        customReason.style.display = 'block';
        customReason.required = true;
        confirmBtn.disabled = !customReason.value.trim();
    } else if (reasonSelect.value) {
        customReason.style.display = 'none';
        customReason.required = false;
        confirmBtn.disabled = false;
    } else {
        customReason.style.display = 'none';
        customReason.required = false;
        confirmBtn.disabled = true;
    }
}

// Add event listener for custom admin cancel reason input
document.addEventListener('DOMContentLoaded', function() {
    const customAdminCancelReason = document.getElementById('customAdminCancelReason');
    if (customAdminCancelReason) {
        customAdminCancelReason.addEventListener('input', function() {
            const confirmBtn = document.getElementById('confirmAdminCancelBtn');
            const reasonSelect = document.getElementById('adminCancelReason');
            if (reasonSelect && reasonSelect.value === 'other') {
                confirmBtn.disabled = !this.value.trim();
            }
        });
    }
});

async function confirmAdminCancelBooking() {
    if (!currentAdminCancelBookingId) {
        if (typeof showToast === 'function') {
            showToast('Error: Booking ID is missing. Please try again.', 'error');
        } else {
            alert('Error: Booking ID is missing. Please try again.');
        }
        return;
    }
    
    const reasonSelect = document.getElementById('adminCancelReason');
    const customReason = document.getElementById('customAdminCancelReason');
    let reasonText = '';
    
    if (!reasonSelect.value) {
        if (typeof showToast === 'function') {
            showToast('Please select a reason for cancellation.', 'warning');
        } else {
            alert('Please select a reason for cancellation.');
        }
        return;
    }
    
    if (reasonSelect.value === 'other') {
        if (!customReason.value.trim()) {
            if (typeof showToast === 'function') {
                showToast('Please provide a reason for cancellation.', 'warning');
            } else {
                alert('Please provide a reason for cancellation.');
            }
            return;
        }
        reasonText = customReason.value.trim();
    } else {
        reasonText = reasonSelect.options[reasonSelect.selectedIndex].text;
    }
    
    const confirmBtn = document.getElementById('confirmAdminCancelBtn');
    const originalText = confirmBtn.innerHTML;
    
    try {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        
        const result = await API.put(`/bookings/${currentAdminCancelBookingId}/cancel`, { reason: reasonText });
        closeAdminCancelModal();
        
        if (result.success) {
            if (typeof showToast === 'function') {
                showToast('Booking cancelled successfully!', 'success');
            } else {
                alert('Booking cancelled successfully!');
            }
            // Reload booking details to show updated status
            loadBookingDetails();
        } else {
            if (typeof showToast === 'function') {
                showToast(result.error || 'Error cancelling booking', 'error');
            } else {
                alert(result.error || 'Error cancelling booking');
            }
        }
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('Error cancelling booking: ' + (error.message || 'An unexpected error occurred'), 'error');
        } else {
            alert('Error cancelling booking: ' + (error.message || 'An unexpected error occurred'));
        }
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    }
}

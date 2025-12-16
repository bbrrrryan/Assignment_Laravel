// Use window object to avoid conflicts with bookings/index.js
// Initialize admin-specific variables only if they don't exist
if (typeof window.adminBookingsData === 'undefined') {
    window.adminBookingsData = [];
}
if (typeof window.adminSortOrderData === 'undefined') {
    window.adminSortOrderData = null; // 'date-asc', 'date-desc', 'created-asc', 'created-desc', or null
}

// Admin pagination state
if (typeof window.adminCurrentPage === 'undefined') {
    window.adminCurrentPage = 1;
}
if (typeof window.adminPerPage === 'undefined') {
    window.adminPerPage = 15;
}
if (typeof window.adminTotalPages === 'undefined') {
    window.adminTotalPages = 1;
}
if (typeof window.adminTotalBookings === 'undefined') {
    window.adminTotalBookings = 0;
}

// Sorting functions - reload with sort applied
window.sortByDate = function() {
    if (window.adminSortOrderData === 'date-asc') {
        window.adminSortOrderData = 'date-desc';
    } else if (window.adminSortOrderData === 'date-desc') {
        window.adminSortOrderData = 'date-asc';
    } else {
        window.adminSortOrderData = 'date-asc';
    }
    // Reset to first page when sorting
    window.adminCurrentPage = 1;
    loadBookings(1);
};

window.sortByCreatedDate = function() {
    if (window.adminSortOrderData === 'created-desc') {
        window.adminSortOrderData = 'created-asc';
    } else if (window.adminSortOrderData === 'created-asc') {
        window.adminSortOrderData = 'created-desc';
    } else {
        window.adminSortOrderData = 'created-desc';
    }
    // Reset to first page when sorting
    window.adminCurrentPage = 1;
    loadBookings(1);
};

// Filter bookings - reload with filters applied
window.filterBookings = function() {
    // Reset to first page when filtering
    window.adminCurrentPage = 1;
    loadBookings(1);
};

// View booking
window.viewBooking = function(id) {
    window.location.href = `/bookings/${id}`;
};

// Dropdown toggle
document.addEventListener('click', function(event) {
    if (event.target.closest('.user-dropdown')) {
        return;
    }
    
    if (!event.target.closest('.dropdown-menu-container')) {
        document.querySelectorAll('.dropdown-menu-container .dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

window.toggleBookingDropdown = function(id) {
    event.stopPropagation();
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    const button = event.target.closest('button');
    const allDropdowns = document.querySelectorAll('.dropdown-menu-container .dropdown-menu');
    
    allDropdowns.forEach(menu => {
        if (menu.id !== `booking-dropdown-${id}`) {
            menu.classList.remove('show');
            menu.classList.remove('dropdown-up');
        }
    });
    
    const isShowing = dropdown.classList.contains('show');
    
    if (!isShowing) {
        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = 90;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        
        dropdown.style.display = 'block';
        dropdown.style.position = 'fixed';
        dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        
        const dropdownRect = dropdown.getBoundingClientRect();
        const spaceBelow = viewportHeight - dropdownRect.bottom;
        const spaceAbove = buttonRect.top;
        
        if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
            dropdown.classList.add('dropdown-up');
            dropdown.style.top = `${buttonRect.top - dropdownHeight - 5}px`;
        } else {
            dropdown.classList.remove('dropdown-up');
        }
        
        dropdown.classList.add('show');
    } else {
        dropdown.classList.remove('show');
        dropdown.classList.remove('dropdown-up');
        dropdown.style.display = 'none';
    }
};

// Store current booking ID for approve/reject/cancel actions
let currentApproveBookingId = null;
let currentRejectBookingId = null;
let currentCancelBookingId = null;

// Approve booking - show modal
window.approveBooking = function(id) {
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    currentApproveBookingId = id;
    document.getElementById('approveBookingModal').style.display = 'flex';
};

// Close approve modal
function closeApproveModal() {
    document.getElementById('approveBookingModal').style.display = 'none';
    currentApproveBookingId = null;
}

// Confirm approve booking
window.confirmApproveBooking = async function() {
    if (!currentApproveBookingId) {
        if (typeof showToast !== 'undefined') {
            showToast('Error: Booking ID is missing. Please try again.', 'error');
        } else {
            alert('Error: Booking ID is missing. Please try again.');
        }
        return;
    }
    
    if (typeof API === 'undefined') {
        if (typeof showToast !== 'undefined') {
            showToast('API not loaded', 'error');
        } else {
            alert('API not loaded');
        }
        return;
    }
    
    const confirmBtn = document.getElementById('confirmApproveBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
        
        try {
            const result = await API.put(`/bookings/${currentApproveBookingId}/approve`);
            closeApproveModal();
            
            if (result.success) {
                loadBookings(window.adminCurrentPage || 1);
                if (typeof showToast !== 'undefined') {
                    showToast('Booking approved successfully!', 'success');
                } else {
                    alert('Booking approved successfully!');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Error approving booking', 'error');
                } else {
                    alert(result.error || 'Error approving booking');
                }
            }
        } catch (error) {
            if (typeof showToast !== 'undefined') {
                showToast('Error approving booking: ' + (error.message || 'An unexpected error occurred'), 'error');
            } else {
                alert('Error approving booking: ' + (error.message || 'An unexpected error occurred'));
            }
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        }
    }
};

// Reject booking - show modal
window.rejectBooking = function(id) {
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    currentRejectBookingId = id;
    // Reset form
    document.getElementById('rejectReason').value = '';
    document.getElementById('customRejectReason').value = '';
    document.getElementById('customRejectReason').style.display = 'none';
    document.getElementById('confirmRejectBtn').disabled = true;
    
    document.getElementById('rejectBookingModal').style.display = 'flex';
};

// Close reject modal
function closeRejectModal() {
    document.getElementById('rejectBookingModal').style.display = 'none';
    currentRejectBookingId = null;
    // Reset form
    document.getElementById('rejectReason').value = '';
    document.getElementById('customRejectReason').value = '';
    document.getElementById('customRejectReason').style.display = 'none';
    document.getElementById('confirmRejectBtn').disabled = true;
}

// Handle reject reason change
window.handleRejectReasonChange = function() {
    const reasonSelect = document.getElementById('rejectReason');
    const customReason = document.getElementById('customRejectReason');
    const confirmBtn = document.getElementById('confirmRejectBtn');
    
    if (reasonSelect.value === 'other') {
        customReason.style.display = 'block';
        customReason.required = true;
        confirmBtn.disabled = !customReason.value.trim();
    } else {
        customReason.style.display = 'none';
        customReason.required = false;
        confirmBtn.disabled = !reasonSelect.value;
    }
};

// Listen to custom reason input
document.addEventListener('DOMContentLoaded', function() {
    const customRejectReason = document.getElementById('customRejectReason');
    if (customRejectReason) {
        customRejectReason.addEventListener('input', function() {
            const confirmBtn = document.getElementById('confirmRejectBtn');
            const reasonSelect = document.getElementById('rejectReason');
            if (reasonSelect.value === 'other') {
                confirmBtn.disabled = !this.value.trim();
            }
        });
    }
});

// Confirm reject booking
window.confirmRejectBooking = async function() {
    if (!currentRejectBookingId) {
        if (typeof showToast !== 'undefined') {
            showToast('Error: Booking ID is missing. Please try again.', 'error');
        } else {
            alert('Error: Booking ID is missing. Please try again.');
        }
        return;
    }
    
    const reasonSelect = document.getElementById('rejectReason');
    const customReason = document.getElementById('customRejectReason');
    
    if (!reasonSelect.value) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select a reason for rejection.', 'warning');
        } else {
            alert('Please select a reason for rejection.');
        }
        return;
    }
    
    if (reasonSelect.value === 'other' && !customReason.value.trim()) {
        if (typeof showToast !== 'undefined') {
            showToast('Please provide a reason for rejection.', 'warning');
        } else {
            alert('Please provide a reason for rejection.');
        }
        return;
    }
    
    if (typeof API === 'undefined') {
        if (typeof showToast !== 'undefined') {
            showToast('API not loaded', 'error');
        } else {
            alert('API not loaded');
        }
        return;
    }
    
    // Build reason text
    let reasonText = reasonSelect.options[reasonSelect.selectedIndex].text;
    if (reasonSelect.value === 'other' && customReason.value.trim()) {
        reasonText = customReason.value.trim();
    }
    
    const confirmBtn = document.getElementById('confirmRejectBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
        
        try {
            const result = await API.put(`/bookings/${currentRejectBookingId}/reject`, { reason: reasonText });
            closeRejectModal();
            
            if (result.success) {
                loadBookings(window.adminCurrentPage || 1);
                if (typeof showToast !== 'undefined') {
                    showToast('Booking rejected successfully!', 'success');
                } else {
                    alert('Booking rejected successfully!');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Error rejecting booking', 'error');
                } else {
                    alert(result.error || 'Error rejecting booking');
                }
            }
        } catch (error) {
            if (typeof showToast !== 'undefined') {
                showToast('Error rejecting booking: ' + (error.message || 'An unexpected error occurred'), 'error');
            } else {
                alert('Error rejecting booking: ' + (error.message || 'An unexpected error occurred'));
            }
        } finally {
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        }
    }
};

// Cancel approved booking - show modal
window.cancelBooking = function(id) {
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    currentCancelBookingId = id;
    document.getElementById('cancelReason').value = '';
    document.getElementById('customCancelReason').value = '';
    document.getElementById('customCancelReason').style.display = 'none';
    document.getElementById('confirmCancelBtn').disabled = true;
    document.getElementById('cancelBookingModal').style.display = 'flex';
};

// Close cancel modal
function closeCancelModal() {
    document.getElementById('cancelBookingModal').style.display = 'none';
    currentCancelBookingId = null;
    document.getElementById('cancelReason').value = '';
    document.getElementById('customCancelReason').value = '';
    document.getElementById('customCancelReason').style.display = 'none';
    document.getElementById('confirmCancelBtn').disabled = true;
}

// Handle cancel reason change
window.handleCancelReasonChange = function() {
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
    const confirmBtn = document.getElementById('confirmCancelBtn');
    
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
};

// Add event listener for custom cancel reason input
document.addEventListener('DOMContentLoaded', function() {
    const customCancelReason = document.getElementById('customCancelReason');
    if (customCancelReason) {
        customCancelReason.addEventListener('input', function() {
            const confirmBtn = document.getElementById('confirmCancelBtn');
            const reasonSelect = document.getElementById('cancelReason');
            if (reasonSelect.value === 'other') {
                confirmBtn.disabled = !this.value.trim();
            }
        });
    }
});

// Confirm cancel booking
window.confirmCancelBooking = async function() {
    if (!currentCancelBookingId) {
        return;
    }
    
    const reasonSelect = document.getElementById('cancelReason');
    const customReason = document.getElementById('customCancelReason');
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
    
    const confirmBtn = document.getElementById('confirmCancelBtn');
    const originalText = confirmBtn.innerHTML;
    
    try {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        
        const result = await API.put(`/bookings/${currentCancelBookingId}/cancel`, { reason: reasonText });
        closeCancelModal();
        
        if (result.success) {
            if (typeof showToast === 'function') {
                showToast('Booking cancelled successfully!', 'success');
            } else {
                alert('Booking cancelled successfully!');
            }
            loadBookings(window.adminCurrentPage);
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
};

// Mark booking as complete
window.markComplete = async function(id) {
    const dropdown = document.getElementById(`booking-dropdown-${id}`);
    if (dropdown) dropdown.classList.remove('show');
    
    if (typeof showConfirm === 'function') {
        const confirmed = await showConfirm('Are you sure you want to mark this booking as completed?', 'Mark as Completed');
        if (!confirmed) return;
    } else {
        if (!confirm('Are you sure you want to mark this booking as completed?')) return;
    }
    
    if (typeof API === 'undefined') {
        if (typeof showToast !== 'undefined') {
            showToast('API not loaded', 'error');
        } else {
            alert('API not loaded');
        }
        return;
    }
    const result = await API.put(`/bookings/${id}/mark-complete`);
            if (result.success) {
                loadBookings(window.adminCurrentPage || 1);
                if (typeof showToast !== 'undefined') {
                    showToast('Booking marked as completed successfully!', 'success');
                } else {
                    alert('Booking marked as completed successfully!');
                }
    } else {
        if (typeof showToast !== 'undefined') {
            showToast(result.error || 'Error marking booking as completed', 'error');
        } else {
            alert(result.error || 'Error marking booking as completed');
        }
    }
};

// Edit booking function removed
window.editBooking = async function(id) {
    // Function removed - edit booking functionality has been disabled
    if (typeof showToast !== 'undefined') {
        showToast('Edit booking functionality has been disabled', 'info');
    } else {
        alert('Edit booking functionality has been disabled');
    }
    return;
};

// Removed edit booking implementation code below - all edit functionality has been removed

// Override form submit handler for admin booking updates
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        // Remove existing event listener if any (from bookings/index.js)
        const newForm = bookingForm.cloneNode(true);
        bookingForm.parentNode.replaceChild(newForm, bookingForm);
        
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const bookingId = this.dataset.bookingId; // Check if editing
            
            // If editing, use admin update API
            if (bookingId) {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    if (typeof showToast !== 'undefined') {
                        showToast('Submit button not found', 'error');
                    } else {
                        alert('Submit button not found');
                    }
                    return;
                }
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
                
                // Get form data
                const facilityId = document.getElementById('bookingFacility')?.value;
                let startTime = document.getElementById('bookingStartTime')?.value;
                let endTime = document.getElementById('bookingEndTime')?.value;
                let bookingDate = document.getElementById('selectedBookingDate')?.value;
                
                // Normalize time format - ensure it's YYYY-MM-DD HH:mm:ss
                // Handle case where value might be ISO format or incorrectly formatted
                if (startTime) {
                    // If it's an ISO format string, parse and reformat
                    if (startTime.includes('T') || startTime.includes('Z')) {
                        const dateObj = new Date(startTime);
                        if (!isNaN(dateObj.getTime())) {
                            const year = dateObj.getFullYear();
                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const day = String(dateObj.getDate()).padStart(2, '0');
                            const hours = String(dateObj.getHours()).padStart(2, '0');
                            const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                            const seconds = String(dateObj.getSeconds()).padStart(2, '0');
                            startTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                        }
                    }
                    // If it doesn't match expected format, try to fix it
                    if (!startTime.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                        // Try to extract date and time parts
                        const dateMatch = startTime.match(/(\d{4}-\d{2}-\d{2})/);
                        const timeMatch = startTime.match(/(\d{2}:\d{2}:\d{2})/);
                        if (dateMatch && timeMatch) {
                            startTime = `${dateMatch[1]} ${timeMatch[1]}`;
                        } else {
                            console.error('Cannot parse startTime:', startTime);
                        }
                    }
                }
                
                if (endTime) {
                    // If it's an ISO format string, parse and reformat
                    if (endTime.includes('T') || endTime.includes('Z')) {
                        const dateObj = new Date(endTime);
                        if (!isNaN(dateObj.getTime())) {
                            const year = dateObj.getFullYear();
                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const day = String(dateObj.getDate()).padStart(2, '0');
                            const hours = String(dateObj.getHours()).padStart(2, '0');
                            const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                            const seconds = String(dateObj.getSeconds()).padStart(2, '0');
                            endTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                        }
                    }
                    // If it doesn't match expected format, try to fix it
                    if (!endTime.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                        // Try to extract date and time parts
                        const dateMatch = endTime.match(/(\d{4}-\d{2}-\d{2})/);
                        const timeMatch = endTime.match(/(\d{2}:\d{2}:\d{2})/);
                        if (dateMatch && timeMatch) {
                            endTime = `${dateMatch[1]} ${timeMatch[1]}`;
                        } else {
                            console.error('Cannot parse endTime:', endTime);
                        }
                    }
                }
                // Get purpose value
                const purposeInput = document.getElementById('bookingPurpose');
                const purpose = purposeInput?.value || '';
                
                // Validate required fields
                if (!facilityId || !startTime || !endTime || !bookingDate || !purpose) {
                    const missingFields = [];
                    if (!facilityId) missingFields.push('Facility');
                    if (!startTime) missingFields.push('Start Time');
                    if (!endTime) missingFields.push('End Time');
                    if (!bookingDate) missingFields.push('Booking Date');
                    if (!purpose) missingFields.push('Purpose');
                    if (typeof showToast !== 'undefined') {
                        showToast('Please fill in all required fields: ' + missingFields.join(', '), 'warning');
                    } else {
                        alert('Please fill in all required fields:\n- ' + missingFields.join('\n- '));
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                
                // Validate time format (should be YYYY-MM-DD HH:mm:ss)
                if (!startTime.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                    if (typeof showToast !== 'undefined') {
                        showToast('Invalid start time format. Expected: YYYY-MM-DD HH:mm:ss', 'error');
                    } else {
                        alert('Invalid start time format. Expected: YYYY-MM-DD HH:mm:ss\nGot: ' + startTime);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                
                if (!endTime.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                    if (typeof showToast !== 'undefined') {
                        showToast('Invalid end time format. Expected: YYYY-MM-DD HH:mm:ss', 'error');
                    } else {
                        alert('Invalid end time format. Expected: YYYY-MM-DD HH:mm:ss\nGot: ' + endTime);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                
                // Validate date format (should be YYYY-MM-DD)
                if (!bookingDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    if (typeof showToast !== 'undefined') {
                        showToast('Invalid booking date format. Expected: YYYY-MM-DD', 'error');
                    } else {
                        alert('Invalid booking date format. Expected: YYYY-MM-DD\nGot: ' + bookingDate);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                
                // Get attendees passports if multi-attendees is enabled
                let attendeesPassports = [];
                let expectedAttendees = 1; // Default to 1
                const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
                
                if (enableMultiAttendees) {
                    const passportInputs = document.querySelectorAll('.attendee-passport-input');
                    passportInputs.forEach(input => {
                        const passport = input.value.trim();
                        if (passport) {
                            attendeesPassports.push(passport);
                        }
                    });
                    // Calculate expected_attendees from passport count
                    expectedAttendees = attendeesPassports.length > 0 ? attendeesPassports.length : 1;
                }
                
                // Build time_slots array from selectedTimeSlots
                const timeSlots = [];
                if (typeof selectedTimeSlots !== 'undefined' && selectedTimeSlots.length > 0) {
                    // Filter slots for the same date
                    const sameDateSlots = selectedTimeSlots.filter(s => s.date === bookingDate);
                    sameDateSlots.forEach(slot => {
                        timeSlots.push({
                            date: slot.date,
                            start_time: `${slot.date} ${slot.start}:00`,
                            end_time: `${slot.date} ${slot.end}:00`
                        });
                    });
                }
                
                const data = {
                    facility_id: parseInt(facilityId),
                    purpose: purpose,
                    expected_attendees: expectedAttendees,
                    attendees_passports: enableMultiAttendees ? attendeesPassports : []
                };
                
                // Use time_slots if available, otherwise use old format
                if (timeSlots.length > 0) {
                    data.time_slots = timeSlots;
                } else {
                    // Fallback to old format
                    data.booking_date = bookingDate;
                    data.start_time = startTime;
                    data.end_time = endTime;
                }
                
                // Debug: log the data being sent
                console.log('Submitting booking update data:', data);
                
                try {
                    const result = await API.put(`/bookings/${bookingId}`, data);
                    if (result.success) {
                        // Close modal
                        const modal = document.getElementById('bookingModal');
                        if (modal) {
                            modal.style.display = 'none';
                        }
                        
                        // Reset form
                        bookingForm.reset();
                        delete bookingForm.dataset.bookingId;
                        
                        // Reset modal title and button
                        const modalTitle = document.getElementById('modalTitle');
                        const modalIcon = document.getElementById('modalIcon');
                        const submitButtonText = document.getElementById('submitButtonText');
                        
                        if (modalTitle) {
                            modalTitle.textContent = 'Create New Booking';
                        }
                        if (modalIcon) {
                            modalIcon.className = 'fas fa-plus-circle me-2 text-primary';
                        }
                        if (submitButtonText) {
                            submitButtonText.textContent = 'Submit Booking';
                        }
                        
                        // Reload bookings (stay on current page)
                        loadBookings(window.adminCurrentPage || 1);
                        if (typeof showToast !== 'undefined') {
                            showToast('Booking updated successfully!', 'success');
                        } else {
                            alert('Booking updated successfully!');
                        }
                    } else {
                        let errorMsg = result.error || result.data?.message || 'Failed to update booking';
                        if (result.data?.errors) {
                            const validationErrors = Object.values(result.data.errors).flat().join(', ');
                            errorMsg = validationErrors || errorMsg;
                        }
                        // Show detailed error message
                        console.error('Booking update error:', result);
                        if (typeof showToast !== 'undefined') {
                            showToast('Error updating booking: ' + errorMsg, 'error');
                        } else {
                            alert('Error updating booking: ' + errorMsg + '\n\nPlease check the console for more details.');
                        }
                    }
                } catch (error) {
                    if (typeof showToast !== 'undefined') {
                        showToast('Error updating booking: ' + (error.message || 'An unexpected error occurred'), 'error');
                    } else {
                        alert('Error updating booking: ' + (error.message || 'An unexpected error occurred'));
                    }
                    console.error('Booking update error:', error);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        });
    }
});


// Format functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    if (typeof date === 'string') {
        const timeMatch = date.match(/(\d{1,2}):(\d{2}):(\d{2})/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hour12 = hours % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    }
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    const isUTC = typeof date === 'string' && date.includes('Z');
    const hours = isUTC ? d.getUTCHours() : d.getHours();
    const minutes = String(isUTC ? d.getUTCMinutes() : d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const date = formatDate(dateTimeString);
    const time = formatTimeNoSeconds(dateTimeString);
    return `${date} ${time}`;
}

// Helper functions for loading and error states
function showLoading(container) {
    if (!container) return;
    container.innerHTML = '<p>Loading bookings...</p>';
}

function showError(container, message) {
    if (!container) return;
    container.innerHTML = `<div class="error-message"><p>${message || 'An error occurred'}</p></div>`;
}

// Initialize admin bookings page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        if (typeof showToast !== 'undefined') {
            showToast('Error: API functions not loaded. Please refresh the page.', 'error');
        } else {
            alert('Error: API functions not loaded. Please refresh the page.');
        }
        return;
    }

    if (!API.requireAuth()) return;

    // Wait a bit to ensure all DOM elements are ready
    setTimeout(function() {
        initAdminBookings();
    }, 100);
});

function initAdminBookings() {
    // Check if required elements exist before proceeding
    const bookingsList = document.getElementById('bookingsList');
    if (!bookingsList) {
        console.error('bookingsList element not found in DOM');
        return;
    }
    
    loadBookings(1);
    loadFacilitiesForFilter();
}

// Load all bookings (admin endpoint) with pagination
async function loadBookings(page = 1) {
    const bookingsListContainer = document.getElementById('bookingsList');
    if (bookingsListContainer) {
        showLoading(bookingsListContainer);
    }
    
    window.adminCurrentPage = page;
    
    // Get filter values
    const search = document.getElementById('searchInput')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const facilityFilter = document.getElementById('facilityFilter')?.value || '';
    
    // Build query parameters
    let queryParams = `page=${page}&per_page=${window.adminPerPage}`;
    if (statusFilter) {
        queryParams += `&status=${encodeURIComponent(statusFilter)}`;
    }
    if (search) {
        queryParams += `&search=${encodeURIComponent(search)}`;
    }
    if (facilityFilter) {
        queryParams += `&facility_id=${encodeURIComponent(facilityFilter)}`;
    }
    
    // Add sorting parameters
    if (window.adminSortOrderData) {
        if (window.adminSortOrderData.startsWith('date-')) {
            queryParams += `&sort_by=date&sort_order=${window.adminSortOrderData === 'date-asc' ? 'asc' : 'desc'}`;
        } else if (window.adminSortOrderData.startsWith('created-')) {
            queryParams += `&sort_by=created_at&sort_order=${window.adminSortOrderData === 'created-asc' ? 'asc' : 'desc'}`;
        }
    }
    
    // Request bookings with pagination and filters
    const result = await API.get(`/bookings?${queryParams}`);
    
    if (result.success) {
        // Handle paginated response structure
        const responseData = result.data.data || result.data;
        
        if (responseData.data && Array.isArray(responseData.data)) {
            // Paginated response
            window.adminBookingsData = responseData.data;
            window.adminCurrentPage = responseData.current_page || page;
            window.adminTotalPages = responseData.last_page || 1;
            window.adminTotalBookings = responseData.total || 0;
            window.adminPerPage = responseData.per_page || window.adminPerPage;
        } else if (Array.isArray(responseData)) {
            // Non-paginated response (fallback)
            window.adminBookingsData = responseData;
            window.adminCurrentPage = 1;
            window.adminTotalPages = 1;
            window.adminTotalBookings = responseData.length;
        } else {
            window.adminBookingsData = [];
            window.adminCurrentPage = 1;
            window.adminTotalPages = 1;
            window.adminTotalBookings = 0;
        }
        
        console.log('Loaded bookings count:', window.adminBookingsData.length);
        console.log('Pagination info:', {
            currentPage: window.adminCurrentPage,
            totalPages: window.adminTotalPages,
            totalBookings: window.adminTotalBookings
        });
        
        if (bookingsListContainer) {
            if (window.adminBookingsData.length === 0) {
                bookingsListContainer.innerHTML = '<p>No bookings found.</p>';
            } else {
                displayBookings(window.adminBookingsData);
            }
        }
    } else {
        const errorMsg = result.error || result.data?.message || 'Failed to load bookings';
        if (bookingsListContainer) {
            showError(bookingsListContainer, errorMsg);
        }
        console.error('Load bookings error:', result);
    }
}

// Load facilities for filter
async function loadFacilitiesForFilter() {
    const result = await API.get('/facilities');
    
    if (result.success) {
        const facilitiesList = result.data.data?.data || result.data.data || [];
        const filterSelect = document.getElementById('facilityFilter');
        
        if (filterSelect && facilitiesList.length > 0) {
            filterSelect.innerHTML = '<option value="">All Facilities</option>' +
                facilitiesList.map(f => 
                    `<option value="${f.id}">${f.name}</option>`
                ).join('');
        }
    }
}

// Display bookings table
function displayBookings(bookingsToShow) {
    const container = document.getElementById('bookingsList');
    if (!container) {
        console.error('bookingsList container not found');
        return;
    }
    
    if (bookingsToShow.length === 0) {
        container.innerHTML = '<div class="table-container"><table class="data-table"><tbody><tr><td colspan="10" class="text-center">No bookings found</td></tr></tbody></table></div>';
        return;
    }

    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Facility</th>
                    <th>
                        <div style="display: flex; align-items: center; gap: 5px; cursor: pointer;" onclick="sortByDate()">
                            <span>Date</span>
                            <i class="fas ${window.adminSortOrderData === 'date-asc' ? 'fa-sort-up' : 'fa-sort-down'} sort-arrow ${window.adminSortOrderData && window.adminSortOrderData.startsWith('date-') ? 'active' : ''}"></i>
                        </div>
                    </th>
                    <th>Time</th>
                    <th>Attendees</th>
                    <th>Status</th>
                    <th>
                        <div style="display: flex; align-items: center; gap: 5px; cursor: pointer;" onclick="sortByCreatedDate()">
                            <span>Created Date</span>
                            <i class="fas ${window.adminSortOrderData === 'created-asc' ? 'fa-sort-up' : 'fa-sort-down'} sort-arrow ${window.adminSortOrderData && window.adminSortOrderData.startsWith('created-') ? 'active' : ''}"></i>
                        </div>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${bookingsToShow.map(booking => {
                    
                    return `
                    <tr>
                        <td>${booking.id}</td>
                        <td>${booking.user?.name || 'N/A'}</td>
                        <td>${booking.facility?.name || 'N/A'}</td>
                        <td>${formatDate(booking.booking_date)}</td>
                        <td>${formatTimeNoSeconds(booking.start_time)} - ${formatTimeNoSeconds(booking.end_time)}</td>
                        <td>${booking.expected_attendees || 'N/A'}</td>
                        <td>
                            <span class="badge badge-${booking.status === 'approved' ? 'success' : (booking.status === 'pending' ? 'warning' : (booking.status === 'rejected' ? 'danger' : (booking.status === 'completed' ? 'info' : 'secondary')))}">
                                ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                        </td>
                        <td>${formatDateTime(booking.created_at)}</td>
                        <td class="actions">
                            <button class="btn-sm btn-info" onclick="viewBooking(${booking.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${booking.status === 'pending' ? `
                                <div class="dropdown-menu-container" style="display: inline-block;">
                                    <button class="btn-sm btn-secondary" onclick="toggleBookingDropdown(${booking.id})" title="Booking Actions" style="position: relative;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="booking-dropdown-${booking.id}">
                                        <button class="dropdown-item" onclick="approveBooking(${booking.id})">
                                            <i class="fas fa-check text-success"></i> Approve
                                        </button>
                                        <button class="dropdown-item" onclick="rejectBooking(${booking.id})">
                                            <i class="fas fa-times text-danger"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                            ${booking.status === 'approved' ? `
                                <div class="dropdown-menu-container" style="display: inline-block;">
                                    <button class="btn-sm btn-secondary" onclick="toggleBookingDropdown(${booking.id})" title="Booking Actions" style="position: relative;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="booking-dropdown-${booking.id}">
                                        <button class="dropdown-item" onclick="markComplete(${booking.id})">
                                            <i class="fas fa-check-circle text-success"></i> Mark Complete
                                        </button>
                                        <button class="dropdown-item" onclick="cancelBooking(${booking.id})">
                                            <i class="fas fa-ban text-warning"></i> Cancel Booking
                                        </button>
                                    </div>
                                </div>
                            ` : ''}
                        </td>
                    </tr>
                `;
                }).join('')}
            </tbody>
        </table>
        ${renderAdminPagination()}
    `;
}

// Render pagination controls for admin
function renderAdminPagination() {
    if (window.adminTotalPages <= 1) {
        return '<div class="pagination-info" style="margin-top: 20px; text-align: center; color: #666;">Showing all bookings</div>';
    }
    
    const startItem = (window.adminCurrentPage - 1) * window.adminPerPage + 1;
    const endItem = Math.min(window.adminCurrentPage * window.adminPerPage, window.adminTotalBookings);
    
    let paginationHTML = '<div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
    
    // Pagination info
    paginationHTML += `<div class="pagination-info" style="color: #666;">
        Showing ${startItem} to ${endItem} of ${window.adminTotalBookings} bookings
    </div>`;
    
    // Pagination controls
    paginationHTML += '<div class="pagination-controls" style="display: flex; gap: 5px; align-items: center;">';
    
    // First page
    if (window.adminCurrentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(1)" class="pagination-btn" title="First page">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }
    
    // Previous page
    if (window.adminCurrentPage > 1) {
        paginationHTML += `<button onclick="loadBookings(${window.adminCurrentPage - 1})" class="pagination-btn" title="Previous page">
            <i class="fas fa-angle-left"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-left"></i>
        </button>`;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, window.adminCurrentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(window.adminTotalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `<button onclick="loadBookings(1)" class="pagination-btn">1</button>`;
        if (startPage > 2) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === window.adminCurrentPage) {
            paginationHTML += `<button class="pagination-btn pagination-btn-active">${i}</button>`;
        } else {
            paginationHTML += `<button onclick="loadBookings(${i})" class="pagination-btn">${i}</button>`;
        }
    }
    
    if (endPage < window.adminTotalPages) {
        if (endPage < window.adminTotalPages - 1) {
            paginationHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        paginationHTML += `<button onclick="loadBookings(${window.adminTotalPages})" class="pagination-btn">${window.adminTotalPages}</button>`;
    }
    
    // Next page
    if (window.adminCurrentPage < window.adminTotalPages) {
        paginationHTML += `<button onclick="loadBookings(${window.adminCurrentPage + 1})" class="pagination-btn" title="Next page">
            <i class="fas fa-angle-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-right"></i>
        </button>`;
    }
    
    // Last page
    if (window.adminCurrentPage < window.adminTotalPages) {
        paginationHTML += `<button onclick="loadBookings(${window.adminTotalPages})" class="pagination-btn" title="Last page">
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    } else {
        paginationHTML += `<button class="pagination-btn" disabled>
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    }
    
    paginationHTML += '</div></div>';
    
    return paginationHTML;
}


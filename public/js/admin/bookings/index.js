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

// Store current booking ID for approve/reject actions
let currentApproveBookingId = null;
let currentRejectBookingId = null;

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

// Edit booking (admin can edit any booking)
window.editBooking = async function(id) {
    if (typeof API === 'undefined') {
        if (typeof showToast !== 'undefined') {
            showToast('API not loaded', 'error');
        } else {
            alert('API not loaded');
        }
        return;
    }
    
    const result = await API.get(`/bookings/${id}`);
    if (!result.success) {
        if (typeof showToast !== 'undefined') {
            showToast('Error loading booking details: ' + (result.error || 'Unknown error'), 'error');
        } else {
            alert('Error loading booking details: ' + (result.error || 'Unknown error'));
        }
        return;
    }
    
    const booking = result.data.data || result.data;
    
    // Prevent editing completed, cancelled, or rejected bookings
    if (booking.status === 'completed' || booking.status === 'cancelled' || booking.status === 'rejected') {
        if (typeof showToast !== 'undefined') {
            showToast('Cannot edit completed, cancelled, or rejected bookings.', 'warning');
        } else {
            alert('Cannot edit completed, cancelled, or rejected bookings.');
        }
        return;
    }
    
    // Store booking data globally for form submission (needed when purpose field is disabled)
    window.currentEditingBooking = booking;
    
    // Store the user ID of the booking being edited - this is used to highlight all slots belonging to this user
    const editingUserId = booking.user_id || booking.user?.id || null;
    window.currentEditingUserId = editingUserId;
    
    // Store the original booking date (for reference, but admin can change it)
    const bookingDate = booking.booking_date || '';
    window.currentEditingBookingDate = bookingDate;
    
    // Load facilities with the booking date to check capacity
    if (typeof loadFacilities === 'function') {
        await loadFacilities(bookingDate);
    }
    
    // Populate form with booking data
    const facilityId = booking.facility_id || '';
    const facilitySelect = document.getElementById('bookingFacility');
    if (facilitySelect) {
        facilitySelect.value = facilityId;
    }
    
    // Extract time from datetime strings first (before loading timetable)
    let bookingDateStr = '';
    let startTime = '';
    let endTime = '';
    
    if (booking.start_time && booking.end_time) {
        // Extract time from datetime string directly to avoid timezone issues
        // booking.start_time and booking.end_time are datetime strings like "2025-12-16 20:00:00"
        // or ISO format like "2025-12-16T20:00:00.000000Z"
        
        let startTimeStr = booking.start_time;
        let endTimeStr = booking.end_time;
        
        // If it's ISO format, extract the time part
        if (startTimeStr.includes('T')) {
            // ISO format: "2025-12-16T20:00:00.000000Z" -> extract "20:00:00"
            const timeMatch = startTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
            if (timeMatch) {
                startTimeStr = timeMatch[1];
            }
        }
        
        if (endTimeStr.includes('T')) {
            const timeMatch = endTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
            if (timeMatch) {
                endTimeStr = timeMatch[1];
            }
        }
        
        // Extract HH:mm from the time string
        // Format could be "20:00:00" or "2025-12-16 20:00:00"
        const startTimeMatch = startTimeStr.match(/(\d{2}):(\d{2})/);
        const endTimeMatch = endTimeStr.match(/(\d{2}):(\d{2})/);
        
        if (startTimeMatch && endTimeMatch) {
            startTime = `${startTimeMatch[1]}:${startTimeMatch[2]}`;
            endTime = `${endTimeMatch[1]}:${endTimeMatch[2]}`;
        } else {
            // Fallback to Date object parsing (may have timezone issues)
            const startDate = new Date(booking.start_time);
            const endDate = new Date(booking.end_time);
            const startHours = String(startDate.getHours()).padStart(2, '0');
            const startMinutes = String(startDate.getMinutes()).padStart(2, '0');
            const endHours = String(endDate.getHours()).padStart(2, '0');
            const endMinutes = String(endDate.getMinutes()).padStart(2, '0');
            
            startTime = `${startHours}:${startMinutes}`;
            endTime = `${endHours}:${endMinutes}`;
        }
        
        bookingDateStr = booking.booking_date || bookingDate;
        
        // Normalize bookingDateStr format (remove time part if present)
        if (bookingDateStr && bookingDateStr.includes('T')) {
            bookingDateStr = bookingDateStr.split('T')[0];
        }
    }
    
    // Update time input constraints and load timetable based on facility
    if (facilityId) {
        if (typeof updateTimeInputConstraints === 'function') {
            await updateTimeInputConstraints(facilityId);
        }
        if (typeof loadTimetable === 'function') {
            await loadTimetable(facilityId);
            // After timetable loads, mark all slots belonging to this user as red
            // Use a small delay to ensure DOM is fully rendered
            markUserBookingSlots(editingUserId, bookingDate);
        }
    }
    
    // Set selected time slots AFTER timetable has loaded
    // Check if booking has slots (new format) or use old format
    if (booking.slots && booking.slots.length > 0) {
        // New format: multiple slots
        if (typeof selectedTimeSlots !== 'undefined') {
            selectedTimeSlots = [];
            booking.slots.forEach(slot => {
                // Parse slot_date - normalize to YYYY-MM-DD format
                let slotDate = slot.slot_date || bookingDateStr;
                if (slotDate) {
                    if (typeof slotDate === 'string') {
                        // If it's ISO format like "2025-12-16T00:00:00.000000Z", extract date part
                        if (slotDate.includes('T')) {
                            slotDate = slotDate.split('T')[0]; // Get YYYY-MM-DD from ISO format
                        } else if (slotDate.includes(' ')) {
                            slotDate = slotDate.split(' ')[0]; // Get YYYY-MM-DD from datetime string
                        }
                        // If it's already YYYY-MM-DD format, use as is
                    } else if (slotDate instanceof Date) {
                        // Convert Date object to YYYY-MM-DD
                        const year = slotDate.getFullYear();
                        const month = String(slotDate.getMonth() + 1).padStart(2, '0');
                        const day = String(slotDate.getDate()).padStart(2, '0');
                        slotDate = `${year}-${month}-${day}`;
                    } else if (slotDate.year) {
                        // If it's an object with year, month, day
                        slotDate = `${slotDate.year}-${String(slotDate.month).padStart(2, '0')}-${String(slotDate.day).padStart(2, '0')}`;
                    }
                }
                
                // Parse start_time and end_time - they are stored as TIME type (HH:mm:ss) in database
                let slotStart = null;
                let slotEnd = null;
                
                if (slot.start_time) {
                    if (typeof slot.start_time === 'string') {
                        // If it's a time string like "08:00:00", extract HH:mm
                        if (slot.start_time.match(/^\d{2}:\d{2}:\d{2}$/)) {
                            slotStart = slot.start_time.substring(0, 5); // Get HH:mm from "08:00:00"
                        } else if (slot.start_time.includes(' ')) {
                            // If it's a datetime string, extract time part
                            slotStart = slot.start_time.split(' ')[1]?.substring(0, 5);
                        } else if (slot.start_time.includes('T')) {
                            // ISO format
                            const timeMatch = slot.start_time.match(/T(\d{2}:\d{2})/);
                            if (timeMatch) slotStart = timeMatch[1];
                        }
                    }
                }
                
                if (slot.end_time) {
                    if (typeof slot.end_time === 'string') {
                        // If it's a time string like "09:00:00", extract HH:mm
                        if (slot.end_time.match(/^\d{2}:\d{2}:\d{2}$/)) {
                            slotEnd = slot.end_time.substring(0, 5); // Get HH:mm from "09:00:00"
                        } else if (slot.end_time.includes(' ')) {
                            // If it's a datetime string, extract time part
                            slotEnd = slot.end_time.split(' ')[1]?.substring(0, 5);
                        } else if (slot.end_time.includes('T')) {
                            // ISO format
                            const timeMatch = slot.end_time.match(/T(\d{2}:\d{2})/);
                            if (timeMatch) slotEnd = timeMatch[1];
                        }
                    }
                }
                
                if (slotStart && slotEnd && slotDate) {
                    selectedTimeSlots.push({
                        date: slotDate,
                        start: slotStart,
                        end: slotEnd
                    });
                }
            });
            
            console.log('Loaded selectedTimeSlots from booking.slots:', selectedTimeSlots);
            
            // After setting selectedTimeSlots, mark them as selected in the timetable
            // Use a delay to ensure timetable is fully rendered
            setTimeout(() => {
                selectedTimeSlots.forEach(slot => {
                    const slotId = `slot-${slot.date}-${slot.start}`;
                    const slotElement = document.getElementById(slotId);
                    if (slotElement) {
                        slotElement.classList.add('selected');
                        console.log('Marked slot as selected:', slotId);
                    } else {
                        console.warn('Slot element not found:', slotId);
                    }
                });
            }, 500); // Wait for timetable to render
        }
    } else if (booking.start_time && booking.end_time && startTime && endTime && bookingDateStr) {
        // Old format: single slot
        // Set hidden inputs (ensure they are set after timetable loads)
        const selectedDateInput = document.getElementById('selectedBookingDate');
        const startTimeInput = document.getElementById('bookingStartTime');
        const endTimeInput = document.getElementById('bookingEndTime');
        
        // Ensure date is in YYYY-MM-DD format
        let normalizedDate = bookingDateStr;
        if (normalizedDate.includes('T')) {
            normalizedDate = normalizedDate.split('T')[0];
        }
        
        if (selectedDateInput) {
            selectedDateInput.value = normalizedDate;
        }
        if (startTimeInput) {
            // Ensure format is YYYY-MM-DD HH:mm:ss
            startTimeInput.value = `${normalizedDate} ${startTime}:00`;
        }
        if (endTimeInput) {
            // Ensure format is YYYY-MM-DD HH:mm:ss
            endTimeInput.value = `${normalizedDate} ${endTime}:00`;
        }
        
        // Set selected time slots (use global selectedTimeSlots from bookings/index.js)
        // Access the selectedTimeSlots variable from the bookings/index.js scope
        if (typeof selectedTimeSlots !== 'undefined') {
            selectedTimeSlots = [{
                date: bookingDateStr,
                start: startTime,
                end: endTime
            }];
        } else if (typeof window.selectedTimeSlots !== 'undefined') {
            window.selectedTimeSlots = [{
                date: bookingDateStr,
                start: startTime,
                end: endTime
            }];
        }
        
        // Mark the corresponding slot as selected in the timetable
        // Use a longer delay to ensure timetable is fully rendered
        setTimeout(() => {
            const slotId = `slot-${bookingDateStr}-${startTime}`;
            const slot = document.getElementById(slotId);
            if (slot) {
                slot.classList.add('selected');
            }
            
            // Double-check that hidden inputs still have values (in case they were cleared)
            const selectedDateInput = document.getElementById('selectedBookingDate');
            const startTimeInput = document.getElementById('bookingStartTime');
            const endTimeInput = document.getElementById('bookingEndTime');
            
            // Normalize date format
            let normalizedDate = bookingDateStr;
            if (normalizedDate && normalizedDate.includes('T')) {
                normalizedDate = normalizedDate.split('T')[0];
            }
            
            if (selectedDateInput) {
                // Always set to ensure correct format
                if (!selectedDateInput.value || selectedDateInput.value.includes('T')) {
                    selectedDateInput.value = normalizedDate;
                }
            }
            if (startTimeInput) {
                // Always set to ensure correct format, especially if it contains ISO format
                const currentValue = startTimeInput.value;
                if (!currentValue || currentValue.includes('T') || currentValue.includes('Z') || !currentValue.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                    startTimeInput.value = `${normalizedDate} ${startTime}:00`;
                }
            }
            if (endTimeInput) {
                // Always set to ensure correct format, especially if it contains ISO format
                const currentValue = endTimeInput.value;
                if (!currentValue || currentValue.includes('T') || currentValue.includes('Z') || !currentValue.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                    endTimeInput.value = `${normalizedDate} ${endTime}:00`;
                }
            }
        }, 800);
    }
    
    const purposeInput = document.getElementById('bookingPurpose');
    if (purposeInput) {
        purposeInput.value = booking.purpose || '';
        // Disable purpose field for editing
        purposeInput.disabled = true;
        purposeInput.setAttribute('readonly', 'readonly');
    }
    
    // Handle attendees field based on facility settings
    const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
    const attendeesList = document.getElementById('attendeesList');
    
    if (enableMultiAttendees && attendeesList) {
        // Clear existing fields
        attendeesList.innerHTML = '';
        
        // Load attendees from booking if available
        if (booking.attendees && Array.isArray(booking.attendees) && booking.attendees.length > 0) {
            booking.attendees.forEach(attendee => {
                if (typeof addAttendeeField === 'function') {
                    addAttendeeField();
                    const lastField = attendeesList.lastElementChild;
                    const input = lastField ? lastField.querySelector('.attendee-passport-input') : null;
                    if (input && attendee.student_passport) {
                        input.value = attendee.student_passport;
                    }
                }
            });
        } else {
            // Add one empty field
            if (typeof addAttendeeField === 'function') {
                addAttendeeField();
            }
        }
    }
    
    // Store booking ID for update
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.dataset.bookingId = id;
    }
    
    // Change form title and submit button
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const submitButtonText = document.getElementById('submitButtonText');
    
    if (modalTitle) {
        modalTitle.textContent = 'Edit Booking';
    }
    if (modalIcon) {
        modalIcon.className = 'fas fa-edit me-2 text-primary';
    }
    if (submitButtonText) {
        submitButtonText.textContent = 'Update Booking';
    }
    
    // Show modal
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'block';
    }
};

// Function to mark all slots belonging to the user whose booking is being edited
function markUserBookingSlots(userId, bookingDate) {
    if (!userId) {
        return;
    }
    
    // Use requestAnimationFrame to ensure DOM is ready, then check with a small delay
    requestAnimationFrame(() => {
        // Try to mark immediately, if slots aren't ready, retry
        const tryMark = (attempts = 0) => {
            const allSlots = document.querySelectorAll('.timetable-slot');
            
            // If no slots found and we haven't tried too many times, retry
            if (allSlots.length === 0 && attempts < 10) {
                setTimeout(() => tryMark(attempts + 1), 100);
                return;
            }
            
            // Check if bookedSlots is populated
            if (typeof bookedSlots === 'undefined' || Object.keys(bookedSlots).length === 0) {
                if (attempts < 10) {
                    setTimeout(() => tryMark(attempts + 1), 100);
                    return;
                }
            }
            
            // Mark all slots belonging to this user
            allSlots.forEach(slot => {
                const slotDate = slot.getAttribute('data-date');
                const slotStart = slot.getAttribute('data-start');
                
                if (!slotDate || !slotStart) return;
                
                // Check if this slot is in the bookedSlots data and belongs to the editing user
                if (typeof bookedSlots !== 'undefined' && bookedSlots[slotDate]) {
                    bookedSlots[slotDate].forEach(booking => {
                        // Check if this booking belongs to the editing user
                        if (booking.user_id == userId) {
                            // Parse booking times
                            const bookingStart = new Date(booking.start_time);
                            const bookingEnd = new Date(booking.end_time);
                            const slotStartTime = new Date(`${slotDate} ${slotStart}:00`);
                            const slotEndTime = new Date(slotStartTime);
                            slotEndTime.setHours(slotEndTime.getHours() + 1);
                            
                            // Check if slot overlaps with booking
                            if (slotStartTime < bookingEnd && slotEndTime > bookingStart) {
                                // Mark this slot as red (belongs to the editing user)
                                // BUT: Don't remove 'selected' class if this slot is in selectedTimeSlots
                                const isInSelectedSlots = typeof selectedTimeSlots !== 'undefined' && 
                                    selectedTimeSlots.some(s => 
                                        s.date === slotDate && s.start === slotStart
                                    );
                                
                                if (!isInSelectedSlots) {
                                    // Not in selectedTimeSlots - mark as booked (not editable)
                                slot.classList.remove('available', 'selected', 'user-booked');
                                slot.classList.add('booked');
                                slot.style.border = '2px solid #dc3545'; // Red border
                                slot.style.backgroundColor = '#f8d7da'; // Light red background
                                } else {
                                    // In selectedTimeSlots - keep it selectable for editing
                                    // Don't add 'booked' class, keep it as 'selected' so it can be clicked to remove
                                    slot.classList.remove('available', 'user-booked', 'booked');
                                    slot.classList.add('selected');
                                    // Use a different style to show it's the current booking but editable
                                    slot.style.border = '2px solid #ffc107'; // Yellow/amber border to indicate editable
                                    slot.style.backgroundColor = '#fff3cd'; // Light yellow background
                                }
                                slot.setAttribute('data-editing-user-booking', 'true');
                            }
                        }
                    });
                }
            });
        };
        
        tryMark();
    });
}

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
                // Get purpose value - if disabled, get it from the original booking data
                const purposeInput = document.getElementById('bookingPurpose');
                const purpose = purposeInput?.disabled && window.currentEditingBooking 
                    ? window.currentEditingBooking.purpose 
                    : (purposeInput?.value || '');
                
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
                    <th>Booking #</th>
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
                        <td>${booking.booking_number}</td>
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
                            ${booking.status !== 'completed' && booking.status !== 'cancelled' && booking.status !== 'rejected' ? `
                            <button class="btn-sm btn-warning" onclick="editBooking(${booking.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            ` : ''}
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


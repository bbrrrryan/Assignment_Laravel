// Create Booking Page JavaScript
// This file handles only the create booking form functionality

let facilities = [];

// Facility pagination state
let facilityCurrentPage = 1;
let facilityHasMore = true;
let facilityLoading = false;
let allFacilities = [];

// Timetable state
let selectedTimeSlots = []; // Array to store multiple selected time slots
let bookedSlots = {};

// Define global functions FIRST so they're available for onclick handlers

// Function to validate time range in real-time
window.validateTimeRange = function() {
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    const startTimeError = document.getElementById('startTimeError');
    const endTimeError = document.getElementById('endTimeError');
    
    // Clear previous errors
    clearTimeValidationErrors();
    
    // Check if both times are filled
    if (!startTimeInput || !endTimeInput || !startTimeInput.value || !endTimeInput.value) {
        return true; // Allow empty values during input
    }
    
    // Get facility time range (default to 08:00-20:00 if not set)
    const facilityTimeRange = window.currentFacilityTimeRange || { start: '08:00', end: '20:00' };
    const minTime = facilityTimeRange.start || '08:00';
    const maxTime = facilityTimeRange.end || '20:00';
    
    // Extract time from datetime string if needed
    let startTime = startTimeInput.value;
    let endTime = endTimeInput.value;
    
    // If it's a datetime string, extract just the time part
    if (startTime.includes(' ')) {
        startTime = startTime.split(' ')[1].substring(0, 5); // Extract HH:mm
    }
    if (endTime.includes(' ')) {
        endTime = endTime.split(' ')[1].substring(0, 5); // Extract HH:mm
    }
    
    // Validate start time is within allowed range
    if (startTime < minTime || startTime > maxTime) {
        if (startTimeError) {
            startTimeError.textContent = `Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            startTimeError.style.display = 'block';
        }
        startTimeInput.classList.add('is-invalid');
        startTimeInput.setCustomValidity(`Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    // Validate end time is within allowed range
    if (endTime < minTime || endTime > maxTime) {
        if (endTimeError) {
            endTimeError.textContent = `End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            endTimeError.style.display = 'block';
        }
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity(`End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    // Compare times - end time must be after start time
    if (startTime >= endTime) {
        showTimeValidationError('End time must be after start time');
        return false;
    }
    
    return true;
};

// Function to show time validation error
function showTimeValidationError(message) {
    const endTimeError = document.getElementById('endTimeError');
    const endTimeInput = document.getElementById('bookingEndTime');
    
    if (endTimeError) {
        endTimeError.textContent = message;
        endTimeError.style.display = 'block';
    }
    
    if (endTimeInput) {
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity(message);
    }
}

// Function to clear time validation errors
function clearTimeValidationErrors() {
    const startTimeError = document.getElementById('startTimeError');
    const endTimeError = document.getElementById('endTimeError');
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    
    if (startTimeError) {
        startTimeError.style.display = 'none';
        startTimeError.textContent = '';
    }
    
    if (endTimeError) {
        endTimeError.style.display = 'none';
        endTimeError.textContent = '';
    }
    
    if (startTimeInput) {
        startTimeInput.classList.remove('is-invalid');
        startTimeInput.setCustomValidity('');
    }
    
    if (endTimeInput) {
        endTimeInput.classList.remove('is-invalid');
        endTimeInput.setCustomValidity('');
    }
}

// Function to validate booking date (must be tomorrow or later)
window.validateBookingDate = function() {
    const dateInput = document.getElementById('bookingDate');
    const errorDiv = document.getElementById('bookingDateError');
    
    if (!dateInput || !dateInput.value) {
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
        dateInput?.classList.remove('is-invalid');
        return true;
    }
    
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    // Check if selected date is today or earlier
    if (selectedDate <= today) {
        if (errorDiv) {
            errorDiv.textContent = 'You can only book from tomorrow onwards. Please select a future date.';
            errorDiv.style.display = 'block';
        }
        dateInput.classList.add('is-invalid');
        dateInput.setCustomValidity('You can only book from tomorrow onwards');
        return false;
    }
    
    // Date is valid
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
    dateInput.classList.remove('is-invalid');
    dateInput.setCustomValidity('');
    
    // Update facilities when date is valid
    updateFacilitiesByDate();
    return true;
};


// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if API is loaded
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    // Check authentication
    if (!API.requireAuth()) return;

    initCreateBooking();
});

async function initCreateBooking() {
    // Set minimum date to tomorrow for booking date input (users can only book from tomorrow onwards)
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
    }
    
    // Time input constraints will be set dynamically based on selected facility
    // Default values are set here, but will be updated when facility is selected
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    if (startTimeInput) {
        startTimeInput.min = '08:00';
        startTimeInput.max = '20:00';
    }
    if (endTimeInput) {
        endTimeInput.min = '08:00';
        endTimeInput.max = '20:00';
    }
    
    // Initialize facility time range and max booking hours
    window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
    window.currentFacilityMaxBookingHours = 1; // Default to 1 hour
    
    // Bind form submit event
    bindBookingForm();
    
    // Get facility_id from sessionStorage (API-based approach)
    const facilityIdFromStorage = sessionStorage.getItem('selectedFacilityId');
    
    // Load facilities for the dropdown
    await loadFacilities();
    
    // If facility_id is in sessionStorage, pre-select it after facilities are loaded
    if (facilityIdFromStorage) {
        await preSelectFacilityFromStorage(facilityIdFromStorage);
        // Clear the stored facility_id after using it
        sessionStorage.removeItem('selectedFacilityId');
    }
}

// Function to pre-select facility from sessionStorage (API-based approach)
async function preSelectFacilityFromStorage(facilityId) {
    const select = document.getElementById('bookingFacility');
    if (!select) {
        console.warn('bookingFacility select element not found');
        return;
    }
    
    // Wait a bit for the DOM to update with the new options
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Check if the facility exists in the dropdown
    let option = select.querySelector(`option[value="${facilityId}"]`);
    
    if (option && !option.disabled) {
        // Facility found in dropdown, select it
        select.value = facilityId;
        // Trigger the change handler to load timetable
        if (typeof handleFacilityChange === 'function') {
            handleFacilityChange(select);
        }
        return;
    }
    
    // Facility not found in dropdown - fetch it directly from API
    try {
        const result = await API.get(`/facilities/${facilityId}`);
        if (result.success && result.data) {
            const facility = result.data.data || result.data;
            
            // Check if facility is available (not disabled)
            if (facility.status === 'available') {
                // Add facility to dropdown if not already present
                if (!option) {
                    const optionElement = document.createElement('option');
                    optionElement.value = facility.id;
                    optionElement.textContent = `${facility.name} (${facility.code}) - ${facility.status}`;
                    // Insert after the "Select Facility" option
                    const firstOption = select.querySelector('option[value=""]');
                    if (firstOption && firstOption.nextSibling) {
                        select.insertBefore(optionElement, firstOption.nextSibling);
                    } else {
                        select.appendChild(optionElement);
                    }
                }
                
                // Select the facility
                select.value = facilityId;
                // Trigger the change handler to load timetable
                if (typeof handleFacilityChange === 'function') {
                    handleFacilityChange(select);
                }
            } else {
                console.warn(`Facility ID ${facilityId} is not available (status: ${facility.status})`);
            }
        } else {
            console.warn(`Facility ID ${facilityId} not found in API`);
        }
    } catch (error) {
        console.error('Error fetching facility from API:', error);
    }
}

async function loadFacilities(bookingDate = null, page = 1, append = false) {
    if (facilityLoading) return; // Prevent multiple simultaneous requests
    
    const select = document.getElementById('bookingFacility');
    if (!select) {
        console.warn('bookingFacility select element not found, skipping loadFacilities');
        return;
    }
    
    facilityLoading = true;
    let url = `/facilities?per_page=50&page=${page}`;
    // Note: API.get() automatically adds '/api' prefix, so '/facilities' becomes '/api/facilities'
    if (bookingDate) {
        url += `&booking_date=${bookingDate}`;
    }
    
    try {
        const result = await API.get(url);
        
        if (result.success) {
            const paginationData = result.data.data;
            const newFacilities = paginationData?.data || paginationData || [];
            
            if (append) {
                // Append to existing facilities
                allFacilities = [...allFacilities, ...newFacilities];
            } else {
                // Replace facilities
                allFacilities = newFacilities;
                facilityCurrentPage = 1;
            }
            
            facilities = allFacilities;
            
            // Check if there are more pages
            facilityHasMore = paginationData?.next_page_url ? true : false;
            facilityCurrentPage = page;
            
            if (allFacilities.length === 0) {
                select.innerHTML = '<option value="">No facilities available. Please create a facility first.</option>';
                select.disabled = true;
                if (!append) {
                    console.warn('No facilities available');
                }
            } else {
                select.disabled = false;
                const currentValue = select.value; // Preserve current selection if any
                
                // Build options HTML
                let optionsHTML = '<option value="">Select Facility</option>';
                optionsHTML += allFacilities.map(f => {
                    // Only disable if facility status is not available
                    // Don't disable based on capacity because capacity is checked by time segments
                    const isDisabled = f.status !== 'available';
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const selectedAttr = (currentValue == f.id) ? 'selected' : '';
                    return `<option value="${f.id}" ${disabledAttr} ${selectedAttr}>${f.name} (${f.code}) - ${f.status}</option>`;
                }).join('');
                
                // Add "Load More" option if there are more pages
                if (facilityHasMore) {
                    optionsHTML += `<option value="__load_more__" disabled style="font-style: italic; color: #666;">--- Scroll to load more ---</option>`;
                }
                
                select.innerHTML = optionsHTML;
                
                // Restore selection
                if (currentValue) {
                    select.value = currentValue;
                }
                
                // Add scroll event listener for loading more (works in most modern browsers)
                if (facilityHasMore && !select.dataset.scrollListenerAdded) {
                    select.dataset.scrollListenerAdded = 'true';
                    // Use both scroll and mousewheel events for better compatibility
                    select.addEventListener('scroll', handleFacilitySelectScroll);
                    select.addEventListener('wheel', handleFacilitySelectScroll);
                    // Also listen for when dropdown is opened
                    select.addEventListener('focus', function() {
                        // Check if we need to load more when dropdown opens
                        setTimeout(() => {
                            if (facilityHasMore && !facilityLoading) {
                                const scrollTop = select.scrollTop;
                                const scrollHeight = select.scrollHeight;
                                const clientHeight = select.clientHeight;
                                if (scrollHeight - scrollTop - clientHeight < 100) {
                                    const bookingDate = document.getElementById('bookingDate')?.value || null;
                                    loadFacilities(bookingDate, facilityCurrentPage + 1, true);
                                }
                            }
                        }, 100);
                    });
                }
                
                console.log(`Loaded ${allFacilities.length} facilities into dropdown`);
            }
        } else {
            if (!append) {
                select.innerHTML = '<option value="">Error loading facilities</option>';
                select.disabled = true;
            }
            console.error('Error loading facilities:', result);
        }
    } catch (error) {
        console.error('Exception loading facilities:', error);
        if (!append) {
            select.innerHTML = '<option value="">Error loading facilities</option>';
            select.disabled = true;
        }
    } finally {
        facilityLoading = false;
    }
}

// Handle scroll event on select dropdown
function handleFacilitySelectScroll(e) {
    const select = e.target;
    // Check if scrolled near bottom (within 50px)
    const scrollTop = select.scrollTop;
    const scrollHeight = select.scrollHeight;
    const clientHeight = select.clientHeight;
    
    if (scrollHeight - scrollTop - clientHeight < 50 && facilityHasMore && !facilityLoading) {
        // Load next page
        const bookingDate = document.getElementById('bookingDate')?.value || null;
        loadFacilities(bookingDate, facilityCurrentPage + 1, true);
    }
}

// Function to update facilities when date is selected
window.updateFacilitiesByDate = function() {
    const dateInput = document.getElementById('bookingDate');
    if (dateInput && dateInput.value) {
        loadFacilities(dateInput.value);
    }
};

// Timetable functions
// Generate 3 days starting from tomorrow
function getNextThreeDays() {
    const days = [];
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayNamesLower = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    for (let i = 1; i <= 3; i++) {
        const date = new Date();
        date.setDate(date.getDate() + i);
        const dayIndex = date.getDay();
        const dayName = dayNames[dayIndex];
        const dayOfWeek = dayNamesLower[dayIndex]; // Lowercase day name for matching
        const month = monthNames[date.getMonth()];
        const day = date.getDate();
        const year = date.getFullYear();
        
        // Format date as YYYY-MM-DD using local time (not UTC) to avoid timezone issues
        const monthStr = String(date.getMonth() + 1).padStart(2, '0');
        const dayStr = String(day).padStart(2, '0');
        const dateStr = `${year}-${monthStr}-${dayStr}`;
        
        days.push({
            date: dateStr,
            display: `${dayName}, ${month} ${day}`,
            fullDate: `${month} ${day}, ${year}`,
            dayOfWeek: dayOfWeek // Add day of week in lowercase for facility available_day matching
        });
    }
    
    return days;
}

// Generate time slots based on facility available time (fixed to 1 hour)
function generateTimeSlots(startTime = '08:00', endTime = '20:00') {
    const slots = [];
    
    // Parse start and end times
    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);
    
    // Convert to minutes for easier calculation
    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;
    const durationMinutes = 60; // Fixed to 1 hour
    
    // Generate slots (1 hour each)
    for (let currentMinutes = startMinutes; currentMinutes + durationMinutes <= endMinutes; currentMinutes += 60) {
        const slotStartHour = Math.floor(currentMinutes / 60);
        const slotStartMin = currentMinutes % 60;
        const slotEndMinutes = currentMinutes + durationMinutes;
        const slotEndHour = Math.floor(slotEndMinutes / 60);
        const slotEndMin = slotEndMinutes % 60;
        
        const slotStart = `${slotStartHour.toString().padStart(2, '0')}:${slotStartMin.toString().padStart(2, '0')}`;
        const slotEnd = `${slotEndHour.toString().padStart(2, '0')}:${slotEndMin.toString().padStart(2, '0')}`;
        
        slots.push({
            start: slotStart,
            end: slotEnd,
            display: `${formatTime12(slotStart)} - ${formatTime12(slotEnd)}`
        });
    }
    
    return slots;
}

// Format time to 12-hour format
function formatTime12(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// Format Date object to 12-hour format without seconds
// Handle timezone issues by extracting time directly from the datetime string
function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    // If date is a string, extract time directly to avoid timezone conversion issues
    if (typeof date === 'string') {
        // Try to extract time from various formats:
        // - "2025-12-15 08:00:00" (local format)
        // - "2025-12-15T08:00:00.000000Z" (ISO format with Z)
        // - "2025-12-15T08:00:00" (ISO format without timezone)
        
        // First, try to match time pattern HH:mm:ss
        const timeMatch = date.match(/(\d{1,2}):(\d{2}):(\d{2})/);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            
            // If the string ends with 'Z' or has timezone, it's UTC
            // In that case, we need to check if we should use UTC or local time
            // For booking times, we want to use the time as stored (local time)
            // So we'll use the time directly from the string
            
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hour12 = hours % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }
    }
    
    // Fallback to Date object parsing
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    // If the date string contains 'Z' (UTC), use UTC methods
    // Otherwise, use local time methods
    const isUTC = typeof date === 'string' && date.includes('Z');
    const hours = isUTC ? d.getUTCHours() : d.getHours();
    const minutes = String(isUTC ? d.getUTCMinutes() : d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

// Load timetable for selected facility
async function loadTimetable(facilityId) {
    console.log('Loading timetable for facility:', facilityId);
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    // Clear selected time slots when switching facilities
    selectedTimeSlots = [];
    // Clear all selected slots visually
    document.querySelectorAll('.timetable-slot.selected').forEach(s => {
        s.classList.remove('selected');
    });
    
    container.innerHTML = '<div class="timetable-loading"><i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...</div>';
    
    try {
        if (!facilityId) {
            throw new Error('Facility ID is required');
        }
        const days = getNextThreeDays();
        
        // Load facility info to get capacity, available_time, available_day, and max_booking_hours
        let facilityCapacity = null;
        let facilityType = null; // Facility type (classroom, auditorium, laboratory, etc.)
        let facilityStartTime = '08:00'; // Default start time
        let facilityEndTime = '20:00';   // Default end time
        let maxBookingHours = 1; // Default to 1 hour
        let facilityAvailableDays = null; // Facility available days array
        
        try {
            const facilityResult = await API.get(`/facilities/${facilityId}`);
            if (facilityResult.success && facilityResult.data) {
                const facility = facilityResult.data.data || facilityResult.data;
                facilityCapacity = facility.capacity;
                facilityType = facility.type; // Get facility type
                maxBookingHours = facility.max_booking_hours || 1; // Get max booking hours
                
                // Get available_time from facility
                if (facility.available_time && typeof facility.available_time === 'object') {
                    if (facility.available_time.start) {
                        facilityStartTime = facility.available_time.start;
                    }
                    if (facility.available_time.end) {
                        facilityEndTime = facility.available_time.end;
                    }
                }
                
                // Handle multi-attendees feature in loadTimetable
                const enableMultiAttendees = facility.enable_multi_attendees || false;
                const maxAttendees = facility.max_attendees || facility.capacity || 1000;
                
                // Store facility multi-attendees settings globally
                window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
                window.currentFacilityMaxAttendees = maxAttendees;
                window.currentFacilityEnableMultiAttendeesForTimetable = enableMultiAttendees;
                
                // Update attendees field visibility
                const attendeesContainer = document.getElementById('attendeesFieldContainer');
                const attendeesList = document.getElementById('attendeesList');
                const addAttendeeBtn = document.getElementById('addAttendeeBtn');
                if (attendeesContainer && attendeesList) {
                    if (enableMultiAttendees) {
                        // Show attendees field if multi-attendees is enabled
                        attendeesContainer.style.display = 'block';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'inline-block';
                        }
                        // Add first attendee field
                        attendeesList.innerHTML = '';
                        addAttendeeField();
                    } else {
                        // Hide attendees field if multi-attendees is disabled
                        attendeesContainer.style.display = 'none';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'none';
                        }
                        attendeesList.innerHTML = '';
                    }
                }
                
                // Get available_day from facility
                if (facility.available_day && Array.isArray(facility.available_day) && facility.available_day.length > 0) {
                    facilityAvailableDays = facility.available_day.map(day => day.toLowerCase()); // Ensure lowercase
                }
                
                // Store facility time range globally for validation
                window.currentFacilityTimeRange = {
                    start: facilityStartTime,
                    end: facilityEndTime
                };
                
                // Store max booking hours globally
                window.currentFacilityMaxBookingHours = maxBookingHours;
            }
        } catch (error) {
            console.error('Error loading facility info:', error);
        }
        
        // Generate time slots based on facility available time (fixed to 1 hour)
        const slots = generateTimeSlots(facilityStartTime, facilityEndTime);
        
        // Load booked slots for each day
        bookedSlots = {};
        const availabilityPromises = days.map(async (day) => {
            try {
                const result = await API.get(`/facilities/${facilityId}/availability?date=${day.date}`);
                console.log(`Availability result for ${day.date}:`, result);
                
                // Check different possible response structures
                let bookings = [];
                if (result.success && result.data) {
                    // Laravel API typically returns: { message: "...", data: { ... } }
                    // API.js wraps it as: { success: true, data: { message: "...", data: { ... } } }
                    if (result.data.data && result.data.data.bookings) {
                        bookings = result.data.data.bookings;
                    } else if (result.data.bookings) {
                        bookings = result.data.bookings;
                    } else if (Array.isArray(result.data)) {
                        bookings = result.data;
                    }
                }
                
                bookedSlots[day.date] = [];
                bookings.forEach(booking => {
                    // Check if booking has slots (new format) or use old format
                    if (booking.slots && booking.slots.length > 0) {
                        // New format: create separate entries for each slot
                        booking.slots.forEach(slot => {
                            // Parse slot_date
                            let slotDate = slot.slot_date || day.date;
                            if (slotDate && typeof slotDate === 'string') {
                                if (slotDate.includes('T')) {
                                    slotDate = slotDate.split('T')[0];
                                } else if (slotDate.includes(' ')) {
                                    slotDate = slotDate.split(' ')[0];
                                }
                            }
                            
                            // Only add slots for this day
                            if (slotDate === day.date) {
                                // Parse start_time and end_time (TIME type: HH:mm:ss)
                                let slotStartTime = slot.start_time || '';
                                let slotEndTime = slot.end_time || '';
                                
                                // Extract HH:mm from TIME format
                                if (slotStartTime && typeof slotStartTime === 'string') {
                                    if (slotStartTime.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                        slotStartTime = slotStartTime.substring(0, 5); // Get HH:mm
                                    } else if (slotStartTime.includes(' ')) {
                                        slotStartTime = slotStartTime.split(' ')[1]?.substring(0, 5);
                                    } else if (slotStartTime.includes('T')) {
                                        const timeMatch = slotStartTime.match(/T(\d{2}:\d{2})/);
                                        if (timeMatch) slotStartTime = timeMatch[1];
                                    }
                                }
                                
                                if (slotEndTime && typeof slotEndTime === 'string') {
                                    if (slotEndTime.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                        slotEndTime = slotEndTime.substring(0, 5); // Get HH:mm
                                    } else if (slotEndTime.includes(' ')) {
                                        slotEndTime = slotEndTime.split(' ')[1]?.substring(0, 5);
                                    } else if (slotEndTime.includes('T')) {
                                        const timeMatch = slotEndTime.match(/T(\d{2}:\d{2})/);
                                        if (timeMatch) slotEndTime = timeMatch[1];
                                    }
                                }
                                
                                if (slotStartTime && slotEndTime) {
                                    bookedSlots[day.date].push({
                                        id: booking.id,
                                        user_id: booking.user_id,
                                        start_time: `${day.date} ${slotStartTime}:00`,
                                        end_time: `${day.date} ${slotEndTime}:00`,
                                        expected_attendees: booking.expected_attendees || 1
                                    });
                                }
                            }
                        });
                    } else {
                        // Old format: use start_time and end_time
                        const startTime = booking.start_time || '';
                        const endTime = booking.end_time || '';
                        
                        bookedSlots[day.date].push({
                            id: booking.id,
                            user_id: booking.user_id,
                            start_time: `${day.date} ${startTime}:00`,
                            end_time: `${day.date} ${endTime}:00`,
                            expected_attendees: booking.expected_attendees || 1
                        });
                    }
                });
            } catch (error) {
                console.error(`Error loading availability for ${day.date}:`, error);
                bookedSlots[day.date] = [];
            }
        });
        
        // Wait for all availability checks to complete
        await Promise.all(availabilityPromises);
        
        // Load user's existing bookings to highlight them
        let userBookingsByDate = {};
        try {
            const bookingsResult = await API.get('/bookings/user/my-bookings');
            if (bookingsResult.success) {
                const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                console.log('User bookings loaded:', userBookings);
                console.log('Current facility ID:', facilityId);
                
                // Group bookings by date and facility
                userBookings.forEach(booking => {
                    const bookingDate = booking.booking_date || '';
                    const bookingFacilityId = booking.facility_id || booking.facility?.id;
                    console.log('Checking booking:', {
                        bookingDate,
                        bookingFacilityId,
                        facilityId,
                        status: booking.status,
                        start_time: booking.start_time,
                        end_time: booking.end_time
                    });
                    
                    if (bookingDate && bookingFacilityId == facilityId && 
                        (booking.status === 'pending' || booking.status === 'approved')) {
                        // Normalize booking_date format
                        let normalizedDate = bookingDate;
                        if (typeof bookingDate === 'string' && bookingDate.includes('T')) {
                            normalizedDate = bookingDate.split('T')[0];
                        } else if (bookingDate instanceof Date) {
                            normalizedDate = bookingDate.toISOString().split('T')[0];
                        } else if (bookingDate && typeof bookingDate === 'object' && bookingDate.year) {
                            normalizedDate = `${bookingDate.year}-${String(bookingDate.month).padStart(2, '0')}-${String(bookingDate.day).padStart(2, '0')}`;
                        }
                        
                        if (!userBookingsByDate[normalizedDate]) {
                            userBookingsByDate[normalizedDate] = [];
                        }
                        userBookingsByDate[normalizedDate].push(booking);
                        console.log('Added booking to date:', normalizedDate, booking);
                    }
                });
                console.log('User bookings by date:', userBookingsByDate);
            }
        } catch (error) {
            console.error('Error loading user bookings:', error);
        }
        
        // Get enable_multi_attendees from facility
        let enableMultiAttendees = false;
        try {
            const facilityResult = await API.get(`/facilities/${facilityId}`);
            if (facilityResult.success && facilityResult.data) {
                const facility = facilityResult.data.data || facilityResult.data;
                enableMultiAttendees = facility.enable_multi_attendees || false;
            }
        } catch (error) {
            console.error('Error loading facility info for multi-attendees:', error);
        }
        
        // Render timetable with user bookings info and available days
                renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours, userBookingsByDate, facilityAvailableDays, enableMultiAttendees, facilityType);
    } catch (error) {
        console.error('Error loading timetable:', error);
        container.innerHTML = '<div class="timetable-no-slots">Error loading timetable. Please try again.</div>';
    }
}

// Render timetable
function renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours = 1, userBookingsByDate = {}, facilityAvailableDays = null, enableMultiAttendees = false, facilityType = null) {
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    let html = '<div class="timetable-days">';
    
    days.forEach(day => {
        const dayBookedSlots = bookedSlots[day.date] || [];
        
        // Check if this day is available based on facility's available_day
        const isDayAvailable = !facilityAvailableDays || facilityAvailableDays.length === 0 || 
                                facilityAvailableDays.includes(day.dayOfWeek);
        
        // Add class for unavailable days
        const dayClass = isDayAvailable ? 'timetable-day' : 'timetable-day unavailable-day';
        
        html += `
            <div class="${dayClass}" data-date="${day.date}" ${!isDayAvailable ? 'data-unavailable="true"' : ''}>
                <div class="timetable-day-header">
                    <div class="timetable-day-title">${day.display}</div>
                    <div class="timetable-day-date">${day.fullDate}</div>
                    ${!isDayAvailable ? '<div class="timetable-day-unavailable-badge"><i class="fas fa-ban"></i> Not Available</div>' : ''}
                </div>
                <div class="timetable-slots">
        `;
        
        slots.forEach(slot => {
            // Check if admin is editing a booking - if so, disable slots on other dates
            // Admin can edit bookings and change dates, so don't restrict by date
            // (Removed date restriction to allow admin flexibility)
            const isDifferentDate = false; // Always false now - admin can select any date
            
            // If day is not available, mark all slots as disabled
            if (!isDayAvailable) {
                const slotId = `slot-${day.date}-${slot.start}`;
                html += `
                    <div class="timetable-slot unavailable" 
                         data-date="${day.date}" 
                         data-start="${slot.start}" 
                         data-end="${slot.end}"
                         id="${slotId}"
                         title="This facility is not available on ${day.display}">
                        <span class="timetable-slot-time">${slot.display}</span>
                        <span class="timetable-slot-unavailable-text">Not Available</span>
                    </div>
                `;
                return; // Skip to next slot
            }
            
            // Calculate total attendees for overlapping bookings in this time slot
            let totalAttendees = 0;
            let hasOverlappingBookings = false;
            const slotStart = new Date(`${day.date} ${slot.start}:00`);
            const slotEnd = new Date(`${day.date} ${slot.end}:00`);
            
            // Check if facility type requires full capacity occupation
            const isFullCapacityType = facilityType && ['classroom', 'auditorium', 'laboratory'].includes(facilityType);
            const requiresFullCapacity = enableMultiAttendees || isFullCapacityType;
            
            dayBookedSlots.forEach(booking => {
                try {
                    const bookingStart = new Date(booking.start_time);
                    const bookingEnd = new Date(booking.end_time);
                    
                    if (isNaN(bookingStart.getTime()) || isNaN(bookingEnd.getTime())) {
                        return;
                    }
                    
                    // Check if booking overlaps with this slot
                    if (slotStart < bookingEnd && slotEnd > bookingStart) {
                        hasOverlappingBookings = true;
                        // If enable_multi_attendees OR facility type is classroom/auditorium/laboratory, each booking occupies full capacity
                        if (requiresFullCapacity) {
                            totalAttendees = facilityCapacity; // Full capacity occupied
                        } else {
                            totalAttendees += booking.expected_attendees || 1;
                        }
                    }
                } catch (error) {
                    console.error('Error processing booking:', booking, error);
                }
            });
            
            // Check if slot is available based on capacity
            // If no capacity info, fall back to conflict check
            let isAvailable = true;
            if (requiresFullCapacity) {
                // If multi-attendees is enabled OR facility type requires full capacity, any booking makes the slot unavailable
                isAvailable = !hasOverlappingBookings;
            } else if (facilityCapacity !== null) {
                // Based on capacity: available if total attendees < capacity
                isAvailable = totalAttendees < facilityCapacity;
            } else {
                // Fallback: if there are any bookings, consider it booked
                isAvailable = totalAttendees === 0;
            }
            
            const slotId = `slot-${day.date}-${slot.start}`;
            
            // Check if this slot is already selected
            const isSelected = selectedTimeSlots.some(s => 
                s.date === day.date && s.start === slot.start && s.end === slot.end
            );
            
            // Check if this slot belongs to the current user's bookings FIRST
            // This must be checked before determining availability to ensure user bookings are highlighted
            const userBookingsOnDate = userBookingsByDate[day.date] || [];
            let isUserBooked = false;
            let userBookingStatus = null; // Track the status of user's booking (pending or approved)
            
            if (userBookingsOnDate.length > 0) {
                console.log(`Checking ${userBookingsOnDate.length} user bookings for date ${day.date}, slot ${slot.start}-${slot.end}`);
            }
            
            userBookingsOnDate.forEach(userBooking => {
                try {
                    // Get the booking date - normalize it first
                    let bookingDateStr = day.date;
                    if (userBooking.booking_date) {
                        if (typeof userBooking.booking_date === 'string') {
                            bookingDateStr = userBooking.booking_date.split('T')[0]; // Extract date part
                        } else if (userBooking.booking_date instanceof Date) {
                            bookingDateStr = userBooking.booking_date.toISOString().split('T')[0];
                        } else if (userBooking.booking_date.year) {
                            bookingDateStr = `${userBooking.booking_date.year}-${String(userBooking.booking_date.month).padStart(2, '0')}-${String(userBooking.booking_date.day).padStart(2, '0')}`;
                        }
                    }
                    
                    // Check if booking has slots (new format) - use slots instead of start_time/end_time
                    if (userBooking.slots && userBooking.slots.length > 0) {
                        // New format: check each slot individually
                        userBooking.slots.forEach(bookingSlot => {
                            // Parse slot_date
                            let slotDate = bookingSlot.slot_date || bookingDateStr;
                            if (slotDate && typeof slotDate === 'string') {
                                if (slotDate.includes('T')) {
                                    slotDate = slotDate.split('T')[0];
                                } else if (slotDate.includes(' ')) {
                                    slotDate = slotDate.split(' ')[0];
                                }
                            }
                            
                            // Only check slots on the same date
                            if (slotDate !== day.date) {
                                return; // Skip slots on different dates
                            }
                            
                            // Parse slot start_time and end_time (TIME type: HH:mm:ss)
                            let slotStartTime = null;
                            let slotEndTime = null;
                            
                            if (bookingSlot.start_time) {
                                let startTimeStr = String(bookingSlot.start_time);
                                // Extract HH:mm from TIME format (08:00:00) or datetime
                                if (startTimeStr.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                    slotStartTime = startTimeStr.substring(0, 5); // Get HH:mm
                                } else if (startTimeStr.includes(' ')) {
                                    slotStartTime = startTimeStr.split(' ')[1]?.substring(0, 5);
                                } else if (startTimeStr.includes('T')) {
                                    const timeMatch = startTimeStr.match(/T(\d{2}:\d{2})/);
                                    if (timeMatch) slotStartTime = timeMatch[1];
                                }
                            }
                            
                            if (bookingSlot.end_time) {
                                let endTimeStr = String(bookingSlot.end_time);
                                // Extract HH:mm from TIME format (09:00:00) or datetime
                                if (endTimeStr.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                    slotEndTime = endTimeStr.substring(0, 5); // Get HH:mm
                                } else if (endTimeStr.includes(' ')) {
                                    slotEndTime = endTimeStr.split(' ')[1]?.substring(0, 5);
                                } else if (endTimeStr.includes('T')) {
                                    const timeMatch = endTimeStr.match(/T(\d{2}:\d{2})/);
                                    if (timeMatch) slotEndTime = timeMatch[1];
                                }
                            }
                            
                            if (slotStartTime && slotEndTime) {
                                // Check if this timetable slot matches the booking slot
                                // Match if the slot time exactly matches the booking slot
                                if (slot.start === slotStartTime && slot.end === slotEndTime) {
                                    console.log('Found user booking slot match!', {
                                        slot: `${slot.start}-${slot.end}`,
                                        bookingSlot: `${slotStartTime}-${slotEndTime}`,
                                        status: userBooking.status
                                    });
                                    isUserBooked = true;
                                    userBookingStatus = userBooking.status;
                                    return; // Found match, can exit early
                                }
                            }
                        });
                        
                        // If we found a match, exit the forEach loop
                        if (isUserBooked) {
                            return;
                        }
                    } else {
                        // Old format: use start_time and end_time (backward compatibility)
                        let userBookingStart, userBookingEnd;
                        
                        if (userBooking.start_time) {
                            let startTimeStr = '';
                            if (typeof userBooking.start_time === 'string') {
                                startTimeStr = userBooking.start_time;
                            } else {
                                startTimeStr = String(userBooking.start_time);
                            }
                            
                            // Extract time part (HH:mm:ss) from the datetime string
                            let timePart = '';
                            if (startTimeStr.includes('T')) {
                                const timeMatch = startTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
                                if (timeMatch) {
                                    timePart = timeMatch[1].substring(0, 5); // Get HH:mm
                                }
                            } else if (startTimeStr.includes(' ')) {
                                const parts = startTimeStr.split(' ');
                                if (parts.length > 1) {
                                    timePart = parts[1].substring(0, 5); // Get HH:mm
                                }
                            }
                            
                            if (timePart) {
                                userBookingStart = new Date(`${bookingDateStr} ${timePart}:00`);
                            } else {
                                userBookingStart = new Date(userBooking.start_time);
                            }
                        } else {
                            return; // Skip if no start_time
                        }
                        
                        if (userBooking.end_time) {
                            let endTimeStr = '';
                            if (typeof userBooking.end_time === 'string') {
                                endTimeStr = userBooking.end_time;
                            } else {
                                endTimeStr = String(userBooking.end_time);
                            }
                            
                            let timePart = '';
                            if (endTimeStr.includes('T')) {
                                const timeMatch = endTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
                                if (timeMatch) {
                                    timePart = timeMatch[1].substring(0, 5); // Get HH:mm
                                }
                            } else if (endTimeStr.includes(' ')) {
                                const parts = endTimeStr.split(' ');
                                if (parts.length > 1) {
                                    timePart = parts[1].substring(0, 5); // Get HH:mm
                                }
                            }
                            
                            if (timePart) {
                                userBookingEnd = new Date(`${bookingDateStr} ${timePart}:00`);
                            } else {
                                userBookingEnd = new Date(userBooking.end_time);
                            }
                        } else {
                            return; // Skip if no end_time
                        }
                        
                        if (isNaN(userBookingStart.getTime()) || isNaN(userBookingEnd.getTime())) {
                            console.warn('Invalid date for user booking:', {
                                booking: userBooking,
                                bookingDate: bookingDateStr,
                                start: userBooking.start_time,
                                end: userBooking.end_time
                            });
                            return;
                        }
                        
                        // Check if this slot overlaps with user's booking
                        const overlaps = slotStart < userBookingEnd && slotEnd > userBookingStart;
                        if (overlaps) {
                            console.log('Found user booking match!', {
                                slot: `${slot.start}-${slot.end}`,
                                bookingDate: bookingDateStr,
                                booking: `${userBookingStart.toLocaleTimeString()}-${userBookingEnd.toLocaleTimeString()}`,
                                status: userBooking.status
                            });
                            isUserBooked = true;
                            userBookingStatus = userBooking.status;
                            return; // Found match, can exit early
                        }
                    }
                } catch (error) {
                    console.error('Error checking user booking:', userBooking, error);
                }
            });
            
            // Check max_booking_hours limit for this date
            // Calculate total hours from booking_slots to ensure accuracy
            // This matches the backend logic in BookingCapacityService
            const existingBookingHours = userBookingsOnDate.reduce((sum, b) => {
                // If booking has slots array, sum up duration_hours from slots
                if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                    // Normalize dates for comparison (handle both string and date formats)
                    // day.date might already be in YYYY-MM-DD format, but handle both cases
                    let targetDate = day.date;
                    if (targetDate && typeof targetDate === 'string') {
                        targetDate = targetDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                    }
                    
                    const slotsHours = b.slots
                        .filter(slot => {
                            // Handle different date formats from API
                            let slotDate = slot.slot_date;
                            if (!slotDate) {
                                // If slot_date is missing, use booking_date as fallback
                                if (b.booking_date) {
                                    slotDate = typeof b.booking_date === 'string' 
                                        ? b.booking_date.split('T')[0].split(' ')[0]
                                        : b.booking_date;
                                } else {
                                    return false; // Skip if no date available
                                }
                            } else if (typeof slotDate === 'string') {
                                slotDate = slotDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                            } else if (slotDate && slotDate.format) {
                                // If it's a moment.js or similar object
                                slotDate = slotDate.format('YYYY-MM-DD');
                            } else if (slotDate instanceof Date) {
                                slotDate = slotDate.toISOString().split('T')[0];
                            }
                            
                            return slotDate === targetDate; // Only count slots for this date
                        })
                        .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                    return sum + slotsHours;
                }
                // Fallback to duration_hours if slots not available (backward compatibility)
                return sum + (b.duration_hours || 1);
            }, 0);
            const hasReachedLimit = existingBookingHours >= maxBookingHours;
            
            // Determine slot class and disabled state
            // Priority: selected > user-booked (pending) > booked (approved) > available/booked
            let slotClass = '';
            let isDisabled = false;
            
            if (isSelected) {
                slotClass = 'selected';
            } else if (isUserBooked) {
                // User's own booking - check status
                if (userBookingStatus === 'pending') {
                    // Pending bookings: show as golden highlight
                    slotClass = 'user-booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as user-booked (pending)`);
                } else if (userBookingStatus === 'approved') {
                    // Approved bookings: show as red (booked)
                    slotClass = 'booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as booked (approved)`);
                } else {
                    // Other statuses: default to user-booked
                    slotClass = 'user-booked';
                }
                isDisabled = true; // Can't book the same slot twice
            } else if (isAvailable) {
                slotClass = 'available';
            } else {
                slotClass = 'booked';
            }
            
            // If user has reached max_booking_hours limit, disable all available slots for this date
            if (hasReachedLimit && isAvailable && !isSelected && !isUserBooked && !isDifferentDate) {
                slotClass = 'disabled';
                isDisabled = true;
            } else if (!isAvailable && !isUserBooked && !isDifferentDate && !isSelected) {
                isDisabled = true;
            }
            
            // Display attendees count (X/Capacity)
            // If enable_multi_attendees OR facility type is classroom/auditorium/laboratory and has bookings, show as full capacity
            let displayAttendees = totalAttendees;
            if (requiresFullCapacity && hasOverlappingBookings) {
                displayAttendees = facilityCapacity; // Show as full capacity when multi-attendees is enabled or facility type requires it
            }
            const attendeesInfo = facilityCapacity !== null 
                ? `${displayAttendees}/${facilityCapacity}` 
                : (displayAttendees > 0 ? `${displayAttendees}` : '');
            
            const onclickAttr = isDisabled ? '' : `onclick="selectTimeSlot('${day.date}', '${slot.start}', '${slot.end}', '${slotId}')"`;
            let titleAttr = '';
            if (isDisabled && hasReachedLimit) {
                titleAttr = `title="Max ${maxBookingHours}h limit reached"`;
            } else if (isUserBooked) {
                if (userBookingStatus === 'pending') {
                    titleAttr = `title="Your booking (Pending)"`;
                } else if (userBookingStatus === 'approved') {
                    titleAttr = `title="Your booking (Approved)"`;
                } else {
                    titleAttr = `title="Your booking"`;
                }
            }
            
            // Add data attribute to mark user bookings for easier identification
            const userBookingAttr = isUserBooked ? `data-user-booking="true" data-booking-status="${userBookingStatus || ''}"` : '';
            
            html += `
                <div class="timetable-slot ${slotClass}" 
                     data-date="${day.date}" 
                     data-start="${slot.start}" 
                     data-end="${slot.end}"
                     id="${slotId}"
                     ${userBookingAttr}
                     ${onclickAttr}
                     ${titleAttr}>
                    <span class="timetable-slot-time">${slot.display}</span>
                    ${attendeesInfo ? `<span class="timetable-slot-attendees">${attendeesInfo}</span>` : ''}
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Select time slot (supports multi-selection)
window.selectTimeSlot = async function(date, start, end, slotId) {
    const slot = document.getElementById(slotId);
    
    // Check if slot is selected first
    const isSelected = slot.classList.contains('selected');
    
    // Check for booked/disabled slots
    if (slot.classList.contains('booked') || slot.classList.contains('disabled') || slot.classList.contains('user-booked') || slot.classList.contains('unavailable')) {
        return;
    }
    
    const maxBookingHours = window.currentFacilityMaxBookingHours || 1;
    const facilitySelect = document.getElementById('bookingFacility');
    const facilityId = facilitySelect ? facilitySelect.value : null;
    
    // Check if slot is already selected
    const slotIndex = selectedTimeSlots.findIndex(s => 
        s.date === date && s.start === start && s.end === end
    );
    
    let newSelectedSlots = [];
    if (slotIndex >= 0) {
        // Deselect: remove from array
        selectedTimeSlots.splice(slotIndex, 1);
        
        // Remove selected class and make available
        slot.classList.remove('selected');
        slot.classList.add('available');
        
        newSelectedSlots = [...selectedTimeSlots];
    } else {
        // Select: add to array
        const newSlot = { date, start, end };
        
        // Check if they're trying to select a different date
        if (selectedTimeSlots.length > 0) {
            const existingSlotsOnOtherDates = selectedTimeSlots.filter(s => s.date !== date);
            
            if (existingSlotsOnOtherDates.length > 0) {
                // Get the first selected date to show in warning
                const firstSelectedDate = existingSlotsOnOtherDates[0].date;
                const firstDateFormatted = new Date(firstSelectedDate).toLocaleDateString();
                const newDateFormatted = new Date(date).toLocaleDateString();
                
                // Show warning toast and prevent selection
                if (typeof showToast !== 'undefined') {
                    showToast(`You can only select time slots on the same date. You have already selected slots on ${firstDateFormatted}. Please clear your current selection or select slots on the same date.`, 'warning');
                } else {
                    alert(`You can only select time slots on the same date.\n\nYou have already selected slots on: ${firstDateFormatted}\nYou are trying to select a slot on: ${newDateFormatted}\n\nPlease clear your current selection or select slots on the same date.`);
                }
                return; // Prevent selection
            }
        }
        
        // Calculate total selected slots for this date
        const sameDateSlots = selectedTimeSlots.filter(s => s.date === date);
        const totalSelectedSlots = sameDateSlots.length + 1; // +1 for the new slot being added
        
        // Check max_booking_hours limit BEFORE adding the slot
        // max_booking_hours limits the total number of hours (slots) user can book
        if (facilityId) {
            try {
                const bookingsResult = await API.get('/bookings/user/my-bookings');
                if (bookingsResult.success) {
                    const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                    
                    // Normalize target date for comparison
                    let targetDate = date;
                    if (targetDate && typeof targetDate === 'string') {
                        targetDate = targetDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                    }
                    
                    const bookingsOnDate = userBookings.filter(b => {
                        // Normalize booking date for comparison
                        let bookingDate = b.booking_date || '';
                        if (bookingDate && typeof bookingDate === 'string') {
                            bookingDate = bookingDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                        } else if (bookingDate && bookingDate.format) {
                            bookingDate = bookingDate.format('YYYY-MM-DD');
                        } else if (bookingDate instanceof Date) {
                            bookingDate = bookingDate.toISOString().split('T')[0];
                        }
                        
                        const bookingFacilityId = b.facility_id || b.facility?.id;
                        return bookingDate === targetDate && bookingFacilityId == facilityId && 
                               (b.status === 'pending' || b.status === 'approved');
                    });
                    
                    // Calculate total hours from booking_slots to ensure accuracy
                    // This matches the backend logic in BookingCapacityService
                    // targetDate is already normalized above
                    const existingBookingHours = bookingsOnDate.reduce((sum, b) => {
                        // If booking has slots array, sum up duration_hours from slots
                        if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                            const slotsHours = b.slots
                                .filter(slot => {
                                    // Handle different date formats from API
                                    let slotDate = slot.slot_date;
                                    if (!slotDate) {
                                        // If slot_date is missing, use booking_date as fallback
                                        if (b.booking_date) {
                                            slotDate = typeof b.booking_date === 'string' 
                                                ? b.booking_date.split('T')[0].split(' ')[0]
                                                : b.booking_date;
                                        } else {
                                            return false; // Skip if no date available
                                        }
                                    } else if (typeof slotDate === 'string') {
                                        slotDate = slotDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                                    } else if (slotDate && slotDate.format) {
                                        // If it's a moment.js or similar object
                                        slotDate = slotDate.format('YYYY-MM-DD');
                                    } else if (slotDate instanceof Date) {
                                        slotDate = slotDate.toISOString().split('T')[0];
                                    }
                                    return slotDate === targetDate; // Only count slots for this date
                                })
                                .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                            return sum + slotsHours;
                        }
                        // Fallback to duration_hours if slots not available (backward compatibility)
                        return sum + (b.duration_hours || 1);
                    }, 0);
                    const selectedSlotsHours = totalSelectedSlots; // Each slot is 1 hour
                    const totalAfterSelection = existingBookingHours + selectedSlotsHours;
                    
                    if (totalAfterSelection > maxBookingHours) {
                        if (typeof showToast !== 'undefined') {
                            showToast(`You have reached the maximum booking limit for this facility on this date. Maximum allowed: ${maxBookingHours} hour(s).`, 'warning');
                        } else {
                            alert(`You have reached the maximum booking limit for this facility on this date.\n\nMaximum allowed: ${maxBookingHours} hour(s)\nYour current bookings: ${existingBookingHours} hour(s)\nSelected slots: ${sameDateSlots.length} hour(s)\nAfter selecting this slot: ${totalAfterSelection} hour(s)`);
                        }
                        return;
                    }
                }
            } catch (error) {
                console.error('Error checking booking limit:', error);
            }
        }
        
        // Add to selection
        selectedTimeSlots.push(newSlot);
        slot.classList.add('selected');
        newSelectedSlots = [...selectedTimeSlots];
    }
    
    // Update hidden inputs based on selected slots
    if (newSelectedSlots.length > 0) {
        // Filter slots for the same date
        const sameDateSlots = newSelectedSlots.filter(s => s.date === date);
        if (sameDateSlots.length > 0) {
            // Sort by start time
            sameDateSlots.sort((a, b) => a.start.localeCompare(b.start));
            
            // Get earliest start and latest end
            const earliestStart = sameDateSlots[0].start;
            const latestEnd = sameDateSlots[sameDateSlots.length - 1].end;
            
            const dateInput = document.getElementById('selectedBookingDate');
            const startInput = document.getElementById('bookingStartTime');
            const endInput = document.getElementById('bookingEndTime');
            
            if (dateInput) dateInput.value = date;
            if (startInput) startInput.value = `${date} ${earliestStart}:00`;
            if (endInput) endInput.value = `${date} ${latestEnd}:00`;
        }
    } else {
        // Clear inputs if no selection
        const dateInput = document.getElementById('selectedBookingDate');
        const startInput = document.getElementById('bookingStartTime');
        const endInput = document.getElementById('bookingEndTime');
        
        if (dateInput) dateInput.value = '';
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
    }
    
    // Clear error
    const errorDiv = document.getElementById('timeSlotError');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
};

// Clear timetable
function clearTimetable() {
    const container = document.getElementById('timetableContainer');
    if (container) {
        container.innerHTML = '<div class="timetable-no-slots">Please select a facility to view available time slots</div>';
    }
    selectedTimeSlots = [];
    // Clear all selected slots visually
    document.querySelectorAll('.timetable-slot.selected').forEach(s => {
        s.classList.remove('selected');
    });
}

// Add attendee passport input field
window.addAttendeeField = function() {
    const attendeesList = document.getElementById('attendeesList');
    const maxAttendees = window.currentFacilityMaxAttendees || 1000;
    
    if (!attendeesList) return;
    
    // Check if we've reached max attendees
    const currentCount = attendeesList.querySelectorAll('.attendee-field').length;
    if (currentCount >= maxAttendees) {
        if (typeof showToast !== 'undefined') {
            showToast(`Maximum ${maxAttendees} attendees allowed for this facility.`, 'warning');
        } else {
            alert(`Maximum ${maxAttendees} attendees allowed for this facility.`);
        }
        return;
    }
    
    const fieldIndex = currentCount + 1;
    const attendeeField = document.createElement('div');
    attendeeField.className = 'attendee-field mb-2';
    attendeeField.innerHTML = `
        <div class="input-group">
            <span class="input-group-text">${fieldIndex}</span>
            <input type="text" class="form-control attendee-passport-input" 
                   placeholder="Enter passport number" 
                   required
                   data-index="${fieldIndex}">
            <button type="button" class="btn btn-outline-danger" onclick="removeAttendeeField(this)" ${currentCount === 0 ? 'disabled' : ''}>
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
    `;
    
    attendeesList.appendChild(attendeeField);
    
    // Update field numbers and enable/disable remove buttons
    updateAttendeeFieldNumbers();
}

// Remove attendee passport input field
window.removeAttendeeField = function(button) {
    const attendeeField = button.closest('.attendee-field');
    if (attendeeField) {
        attendeeField.remove();
        updateAttendeeFieldNumbers();
    }
}

// Update attendee field numbers
function updateAttendeeFieldNumbers() {
    const attendeesList = document.getElementById('attendeesList');
    if (!attendeesList) return;
    
    const fields = attendeesList.querySelectorAll('.attendee-field');
    const addAttendeeBtn = document.getElementById('addAttendeeBtn');
    const maxAttendees = window.currentFacilityMaxAttendees || 1000;
    
    fields.forEach((field, index) => {
        const numberSpan = field.querySelector('.input-group-text');
        const removeBtn = field.querySelector('button');
        const input = field.querySelector('.attendee-passport-input');
        
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
        if (input) {
            input.setAttribute('data-index', index + 1);
        }
        // Enable remove button if there's more than one field
        if (removeBtn) {
            removeBtn.disabled = fields.length <= 1;
        }
    });
    
    // Disable add button if max reached
    if (addAttendeeBtn) {
        addAttendeeBtn.disabled = fields.length >= maxAttendees;
    }
}

// Handle facility change
window.handleFacilityChange = function(select) {
    console.log('Facility changed to:', select.value);
    if (select && select.value) {
        // Update time input constraints based on facility
        updateTimeInputConstraints(select.value);
        loadTimetable(select.value);
    } else {
        clearTimetable();
        // Reset to default time range
        window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
        
        // Hide attendees field when no facility is selected
        const attendeesContainer = document.getElementById('attendeesFieldContainer');
        const attendeesInput = document.getElementById('bookingAttendees');
        if (attendeesContainer) {
            attendeesContainer.style.display = 'none';
        }
        if (attendeesInput) {
            attendeesInput.value = '1'; // Set default to 1
            attendeesInput.removeAttribute('required');
        }
        
        // Reset facility multi-attendees settings
        window.currentFacilityEnableMultiAttendees = false;
        window.currentFacilityMaxAttendees = null;
    }
};

// Update time input constraints based on facility
async function updateTimeInputConstraints(facilityId) {
    try {
        const facilityResult = await API.get(`/facilities/${facilityId}`);
        if (facilityResult.success && facilityResult.data) {
            const facility = facilityResult.data.data || facilityResult.data;
            let startTime = '08:00';
            let endTime = '20:00';
            
            // Get available_time from facility
            if (facility.available_time && typeof facility.available_time === 'object') {
                if (facility.available_time.start) {
                    startTime = facility.available_time.start;
                }
                if (facility.available_time.end) {
                    endTime = facility.available_time.end;
                }
            }
            
            // Update time input constraints
            const startTimeInput = document.getElementById('bookingStartTime');
            const endTimeInput = document.getElementById('bookingEndTime');
            if (startTimeInput) {
                startTimeInput.min = startTime;
                startTimeInput.max = endTime;
            }
            if (endTimeInput) {
                endTimeInput.min = startTime;
                endTimeInput.max = endTime;
            }
            
            // Store facility time range and max booking hours globally
            const maxBookingHours = facility.max_booking_hours || 1;
            window.currentFacilityTimeRange = { start: startTime, end: endTime };
            window.currentFacilityMaxBookingHours = maxBookingHours;
            
            // Handle multi-attendees feature
            const attendeesContainer = document.getElementById('attendeesFieldContainer');
            const attendeesList = document.getElementById('attendeesList');
            const addAttendeeBtn = document.getElementById('addAttendeeBtn');
            const enableMultiAttendees = facility.enable_multi_attendees || false;
            const maxAttendees = facility.max_attendees || facility.capacity || 1000;
            
            // Store facility multi-attendees settings globally
            window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
            window.currentFacilityMaxAttendees = maxAttendees;
            
            if (attendeesContainer && attendeesList) {
                if (enableMultiAttendees) {
                    // Show attendees field if multi-attendees is enabled
                    attendeesContainer.style.display = 'block';
                    if (addAttendeeBtn) {
                        addAttendeeBtn.style.display = 'inline-block';
                    }
                    // Add first attendee field if list is empty
                    if (attendeesList.children.length === 0) {
                        addAttendeeField();
                    }
                } else {
                    // Hide attendees field if multi-attendees is disabled
                    attendeesContainer.style.display = 'none';
                    if (addAttendeeBtn) {
                        addAttendeeBtn.style.display = 'none';
                    }
                    attendeesList.innerHTML = '';
                }
            }
        }
    } catch (error) {
        console.error('Error loading facility for time constraints:', error);
    }
}


// Global functions are already defined at the top of the script

// Bind form submit event
function bindBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        // Remove existing event listener if any
        const newForm = bookingForm.cloneNode(true);
        bookingForm.parentNode.replaceChild(newForm, bookingForm);
        
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const bookingId = this.dataset.bookingId; // Check if editing

    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (!submitBtn) {
        alert('Submit button not found');
        return;
    }
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    // Validate time slot selection
    if (!selectedTimeSlots || selectedTimeSlots.length === 0) {
        const errorDiv = document.getElementById('timeSlotError');
        if (errorDiv) {
            errorDiv.textContent = 'Please select at least one time slot from the timetable';
            errorDiv.style.display = 'block';
        }
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    // Get all selected slots for the same date (should be continuous)
    const facilitySelect = document.getElementById('bookingFacility');
    
    // Group slots by date
    const slotsByDate = {};
    selectedTimeSlots.forEach(slot => {
        if (!slotsByDate[slot.date]) {
            slotsByDate[slot.date] = [];
        }
        slotsByDate[slot.date].push(slot);
    });
    
    // For now, we'll use the first date's slots (can be extended for multi-day)
    const dates = Object.keys(slotsByDate);
    if (dates.length === 0) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select at least one time slot', 'warning');
        } else {
            alert('Please select at least one time slot');
        }
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    const date = dates[0];
    const dateSlots = slotsByDate[date].sort((a, b) => a.start.localeCompare(b.start));
    
    // Get facility ID and check max_booking_hours limit
    const facilityId = facilitySelect ? facilitySelect.value : null;
    if (!facilityId) {
        if (typeof showToast !== 'undefined') {
            showToast('Please select a facility', 'warning');
        } else {
            alert('Please select a facility');
        }
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    const purpose = document.getElementById('bookingPurpose').value;
    if (!purpose) {
        if (typeof showToast !== 'undefined') {
            showToast('Please enter a purpose for the booking', 'warning');
        } else {
            alert('Please enter a purpose for the booking');
        }
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        return;
    }
    
    // Check max_booking_hours limit (total hours from all selected slots)
    const maxBookingHours = window.currentFacilityMaxBookingHours || 1;
    try {
        const bookingsResult = await API.get('/bookings/user/my-bookings');
        if (bookingsResult.success) {
            const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
            const bookingsOnDate = userBookings.filter(b => {
                const bookingDate = b.booking_date || '';
                const bookingFacilityId = b.facility_id || b.facility?.id;
                return bookingDate === date && bookingFacilityId == facilityId && 
                       (b.status === 'pending' || b.status === 'approved');
            });
            
            // Calculate total hours from booking_slots to ensure accuracy
            // This matches the backend logic in BookingCapacityService
            let targetDate = date;
            if (targetDate && typeof targetDate === 'string') {
                targetDate = targetDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
            }
            
            const totalHours = bookingsOnDate.reduce((sum, b) => {
                // If booking has slots array, sum up duration_hours from slots
                if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                    const slotsHours = b.slots
                        .filter(slot => {
                            // Handle different date formats from API
                            let slotDate = slot.slot_date;
                            if (!slotDate) {
                                // If slot_date is missing, use booking_date as fallback
                                if (b.booking_date) {
                                    slotDate = typeof b.booking_date === 'string' 
                                        ? b.booking_date.split('T')[0].split(' ')[0]
                                        : b.booking_date;
                                } else {
                                    return false; // Skip if no date available
                                }
                            } else if (typeof slotDate === 'string') {
                                slotDate = slotDate.split('T')[0].split(' ')[0]; // Get YYYY-MM-DD format
                            } else if (slotDate && slotDate.format) {
                                // If it's a moment.js or similar object
                                slotDate = slotDate.format('YYYY-MM-DD');
                            } else if (slotDate instanceof Date) {
                                slotDate = slotDate.toISOString().split('T')[0];
                            }
                            return slotDate === targetDate; // Only count slots for this date
                        })
                        .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                    return sum + slotsHours;
                }
                // Fallback to duration_hours if slots not available (backward compatibility)
                return sum + (b.duration_hours || 1);
            }, 0);
            const newBookingHours = dateSlots.length; // Each slot is 1 hour
            
            if (totalHours + newBookingHours > maxBookingHours) {
                if (typeof showToast !== 'undefined') {
                    showToast(`You have reached the maximum booking limit for this facility on this date. Maximum allowed: ${maxBookingHours} hour(s).`, 'warning');
                } else {
                    alert(`You have reached the maximum booking limit for this facility on this date.\n\nMaximum allowed: ${maxBookingHours} hour(s)\nYour current bookings: ${totalHours} hour(s)\nAfter this booking: ${totalHours + newBookingHours} hour(s)`);
                }
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
        }
    } catch (error) {
        console.error('Error checking booking limit:', error);
    }
    
    // Create a separate booking for each selected time slot
    // This allows non-continuous time slots (e.g., 8-9 and 11-12)
    // Get attendees passports: if multi-attendees is enabled, collect all passport inputs; otherwise default to 1
    let expectedAttendees = 1; // Default to 1
    let attendeesPassports = []; // Array to store passport numbers
    const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
    
    if (enableMultiAttendees) {
        // Collect all passport inputs
        const passportInputs = document.querySelectorAll('.attendee-passport-input');
        passportInputs.forEach(input => {
            const passport = input.value.trim();
            if (passport) {
                attendeesPassports.push(passport);
            }
        });
        
        if (attendeesPassports.length === 0) {
            if (typeof showToast !== 'undefined') {
                showToast('Please enter at least one attendee passport number.', 'warning');
            } else {
                alert('Please enter at least one attendee passport number.');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
        expectedAttendees = attendeesPassports.length;
    } else {
        // If multi-attendees is disabled, expectedAttendees is already set to 1
        expectedAttendees = 1;
    }
    
    // Ensure expectedAttendees is always a valid integer
    expectedAttendees = parseInt(expectedAttendees) || 1;
    
    try {
        let successCount = 0;
        let errorCount = 0;
        const errors = [];
        
        // Ensure date is in YYYY-MM-DD format first (before the loop)
        let bookingDate = date;
        if (date instanceof Date) {
            // If date is a Date object, convert to YYYY-MM-DD
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            bookingDate = `${year}-${month}-${day}`;
        } else if (typeof date === 'string') {
            // If date is a string, ensure it's in YYYY-MM-DD format
            // Remove any time portion if present
            bookingDate = date.split('T')[0].split(' ')[0];
        }
        
        // Build time_slots array
        const timeSlots = [];
        dateSlots.forEach(slot => {
            // Ensure time format is correct (HH:mm or HH:mm:ss)
            const startTime = slot.start.includes(':') && slot.start.split(':').length === 3 
                ? slot.start 
                : `${slot.start}:00`;
            const endTime = slot.end.includes(':') && slot.end.split(':').length === 3 
                ? slot.end 
                : `${slot.end}:00`;
            
            timeSlots.push({
                date: bookingDate,
                start_time: `${bookingDate} ${startTime}`,
                end_time: `${bookingDate} ${endTime}`
            });
        });
        
        // Create single booking with multiple slots
        const data = {
            facility_id: parseInt(facilityId),
            purpose: purpose,
            expected_attendees: expectedAttendees,
            attendees_passports: enableMultiAttendees ? attendeesPassports : [],
            time_slots: timeSlots
        };
        
       
        
        try {
            const result = await API.post('/bookings', data);
          
            if (result.success) {
                successCount = timeSlots.length;
            } else {
                errorCount = timeSlots.length;
                // Show detailed error message including validation errors
                let errorMsg = result.error || result.data?.message || 'Unknown error';
                if (result.data?.errors) {
                    const validationErrors = Object.values(result.data.errors).flat().join(', ');
                    errorMsg = validationErrors || errorMsg;
                   
                }
              
                errors.push(errorMsg);
            }
        } catch (error) {
            errorCount = timeSlots.length;
            errors.push(error.message);
        }
        
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        
        if (successCount > 0) {
            // On create page - redirect to bookings index page
            if (errorCount === 0) {
                // Show success message briefly, then redirect
                if (typeof showToast !== 'undefined') {
                    showToast(`Successfully created ${successCount} booking(s)! Redirecting...`, 'success');
                    // Redirect after a short delay to show the toast
                    setTimeout(() => {
                        window.location.href = '/bookings';
                    }, 1500);
                } else {
                    alert(`Successfully created ${successCount} booking(s)!`);
                    window.location.href = '/bookings';
                }
            } else {
                // Some bookings failed - show warning but still redirect
                if (typeof showToast !== 'undefined') {
                    showToast(`Created ${successCount} booking(s), but ${errorCount} failed. Please check the details.`, 'warning');
                    setTimeout(() => {
                        window.location.href = '/bookings';
                    }, 2000);
                } else {
                    alert(`Created ${successCount} booking(s), but ${errorCount} failed:\n\n${errors.join('\n')}`);
                    window.location.href = '/bookings';
                }
            }
        } else {
            if (typeof showToast !== 'undefined') {
                showToast(`Failed to create bookings. Please check the details and try again.`, 'error');
            } else {
                alert(`Failed to create bookings:\n\n${errors.join('\n')}`);
            }
        }
    } catch (error) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
        if (typeof showToast !== 'undefined') {
            showToast('Error creating bookings: ' + error.message, 'error');
        } else {
            alert('Error creating bookings: ' + error.message);
        }
        console.error('Booking submission error:', error);
    }
        });
    }
}

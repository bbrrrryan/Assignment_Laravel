/**
 * Author: Low Kim Hong
 */

let facilities = [];

let facilityCurrentPage = 1;
let facilityHasMore = true;
let facilityLoading = false;
let allFacilities = [];

let selectedTimeSlots = [];
let bookedSlots = {};

window.validateTimeRange = function() {
    const startTimeInput = document.getElementById('bookingStartTime');
    const endTimeInput = document.getElementById('bookingEndTime');
    const startTimeError = document.getElementById('startTimeError');
    const endTimeError = document.getElementById('endTimeError');
    
    clearTimeValidationErrors();
    
    if (!startTimeInput || !endTimeInput || !startTimeInput.value || !endTimeInput.value) {
        return true; 
    }
    
    const facilityTimeRange = window.currentFacilityTimeRange || { start: '08:00', end: '20:00' };
    const minTime = facilityTimeRange.start || '08:00';
    const maxTime = facilityTimeRange.end || '20:00';
    
    let startTime = startTimeInput.value;
    let endTime = endTimeInput.value;
    
    if (startTime.includes(' ')) {
        startTime = startTime.split(' ')[1].substring(0, 5); 
    }
    if (endTime.includes(' ')) {
        endTime = endTime.split(' ')[1].substring(0, 5); 
    }
    
    if (startTime < minTime || startTime > maxTime) {
        if (startTimeError) {
            startTimeError.textContent = `Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            startTimeError.style.display = 'block';
        }
        startTimeInput.classList.add('is-invalid');
        startTimeInput.setCustomValidity(`Start time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    if (endTime < minTime || endTime > maxTime) {
        if (endTimeError) {
            endTimeError.textContent = `End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`;
            endTimeError.style.display = 'block';
        }
        endTimeInput.classList.add('is-invalid');
        endTimeInput.setCustomValidity(`End time must be between ${formatTime12(minTime)} and ${formatTime12(maxTime)}`);
        return false;
    }
    
    if (startTime >= endTime) {
        showTimeValidationError('End time must be after start time');
        return false;
    }
    
    return true;
};

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
    
    if (selectedDate <= today) {
        if (errorDiv) {
            errorDiv.textContent = 'You can only book from tomorrow onwards. Please select a future date.';
            errorDiv.style.display = 'block';
        }
        dateInput.classList.add('is-invalid');
        dateInput.setCustomValidity('You can only book from tomorrow onwards');
        return false;
    }
    
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
    dateInput.classList.remove('is-invalid');
    dateInput.setCustomValidity('');
    
    updateFacilitiesByDate();
    return true;
};


document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initCreateBooking();
});

async function initCreateBooking() {
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
    }
    
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
    
    window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
    window.currentFacilityMaxBookingHours = 1; 
    
    bindBookingForm();
    
    const facilityIdFromStorage = sessionStorage.getItem('selectedFacilityId');
    
    await loadFacilities();
    
    if (facilityIdFromStorage) {
        await preSelectFacilityFromStorage(facilityIdFromStorage);
        sessionStorage.removeItem('selectedFacilityId');
    }
}

async function preSelectFacilityFromStorage(facilityId) {
    const select = document.getElementById('bookingFacility');
    if (!select) {
        console.warn('bookingFacility select element not found');
        return;
    }
    
    await new Promise(resolve => setTimeout(resolve, 100));
    
    let option = select.querySelector(`option[value="${facilityId}"]`);
    
    if (option && !option.disabled) {
        select.value = facilityId;
        if (typeof handleFacilityChange === 'function') {
            handleFacilityChange(select);
        }
        return;
    }
    
    try {
        const result = await API.get(`/facilities/${facilityId}`);
        if (result.success && result.data) {
            const facility = result.data.data || result.data;
            
            if (facility.status === 'available') {
                if (!option) {
                    const optionElement = document.createElement('option');
                    optionElement.value = facility.id;
                    optionElement.textContent = `${facility.name} (${facility.code}) - ${facility.status}`;
                    const firstOption = select.querySelector('option[value=""]');
                    if (firstOption && firstOption.nextSibling) {
                        select.insertBefore(optionElement, firstOption.nextSibling);
                    } else {
                        select.appendChild(optionElement);
                    }
                }
                
                select.value = facilityId;
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
    if (facilityLoading) return; 
    
    const select = document.getElementById('bookingFacility');
    if (!select) {
        console.warn('bookingFacility select element not found, skipping loadFacilities');
        return;
    }
    
    facilityLoading = true;
    let url = `/facilities?per_page=50&page=${page}`;
    if (bookingDate) {
        url += `&booking_date=${bookingDate}`;
    }
    
    try {
        const result = await API.get(url);
        
        if (result.success) {
            const paginationData = result.data.data;
            const newFacilities = paginationData?.data || paginationData || [];
            
            if (append) {
                allFacilities = [...allFacilities, ...newFacilities];
            } else {
                allFacilities = newFacilities;
                facilityCurrentPage = 1;
            }
            
            facilities = allFacilities;
            
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
                const currentValue = select.value; 
                

                let optionsHTML = '<option value="">Select Facility</option>';
                optionsHTML += allFacilities.map(f => {
                    const isDisabled = f.status !== 'available';
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const selectedAttr = (currentValue == f.id) ? 'selected' : '';
                    return `<option value="${f.id}" ${disabledAttr} ${selectedAttr}>${f.name} (${f.code}) - ${f.status}</option>`;
                }).join('');
                
                if (facilityHasMore) {
                    optionsHTML += `<option value="__load_more__" disabled style="font-style: italic; color: #666;">--- Scroll to load more ---</option>`;
                }
                
                select.innerHTML = optionsHTML;
                
                if (currentValue) {
                    select.value = currentValue;
                }
                
                if (facilityHasMore && !select.dataset.scrollListenerAdded) {
                    select.dataset.scrollListenerAdded = 'true';
                    select.addEventListener('scroll', handleFacilitySelectScroll);
                    select.addEventListener('wheel', handleFacilitySelectScroll);
                    select.addEventListener('focus', function() {
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

function handleFacilitySelectScroll(e) {
    const select = e.target;
    const scrollTop = select.scrollTop;
    const scrollHeight = select.scrollHeight;
    const clientHeight = select.clientHeight;
    
    if (scrollHeight - scrollTop - clientHeight < 50 && facilityHasMore && !facilityLoading) {

        const bookingDate = document.getElementById('bookingDate')?.value || null;
        loadFacilities(bookingDate, facilityCurrentPage + 1, true);
    }
}

window.updateFacilitiesByDate = function() {
    const dateInput = document.getElementById('bookingDate');
    if (dateInput && dateInput.value) {
        loadFacilities(dateInput.value);
    }
};

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
        const dayOfWeek = dayNamesLower[dayIndex]; 
        const month = monthNames[date.getMonth()];
        const day = date.getDate();
        const year = date.getFullYear();
        
        const monthStr = String(date.getMonth() + 1).padStart(2, '0');
        const dayStr = String(day).padStart(2, '0');
        const dateStr = `${year}-${monthStr}-${dayStr}`;
        
        days.push({
            date: dateStr,
            display: `${dayName}, ${month} ${day}`,
            fullDate: `${month} ${day}, ${year}`,
            dayOfWeek: dayOfWeek 
        });
    }
    
    return days;
}

function generateTimeSlots(startTime = '08:00', endTime = '20:00') {
    const slots = [];
    
    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);
    
    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;
    const durationMinutes = 60; 
    
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

function formatTime12(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function formatTimeNoSeconds(date) {
    if (!date) return 'N/A';
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'N/A';
    
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

async function loadTimetable(facilityId) {
    console.log('Loading timetable for facility:', facilityId);
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    selectedTimeSlots = [];
    document.querySelectorAll('.timetable-slot.selected').forEach(s => {
        s.classList.remove('selected');
    });
    
    container.innerHTML = '<div class="timetable-loading"><i class="fas fa-spinner fa-spin me-2"></i>Loading timetable...</div>';
    
    try {
        if (!facilityId) {
            throw new Error('Facility ID is required');
        }
        const days = getNextThreeDays();
        
        let facilityCapacity = null;
        let facilityType = null;
        let facilityStartTime = '08:00';
        let facilityEndTime = '20:00';
        let maxBookingHours = 1;
        let facilityAvailableDays = null;
        
        try {
            const facilityResult = await API.get(`/facilities/${facilityId}`);
            if (facilityResult.success && facilityResult.data) {
                const facility = facilityResult.data.data || facilityResult.data;
                facilityCapacity = facility.capacity;
                facilityType = facility.type;
                maxBookingHours = facility.max_booking_hours || 1;
                
                if (facility.available_time && typeof facility.available_time === 'object') {
                    if (facility.available_time.start) {
                        facilityStartTime = facility.available_time.start;
                    }
                    if (facility.available_time.end) {
                        facilityEndTime = facility.available_time.end;
                    }
                }
                
                const enableMultiAttendees = facility.enable_multi_attendees || false;
                const maxAttendees = facility.max_attendees || facility.capacity || 1000;
                
                window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
                window.currentFacilityMaxAttendees = maxAttendees;
                window.currentFacilityEnableMultiAttendeesForTimetable = enableMultiAttendees;
                
                const attendeesContainer = document.getElementById('attendeesFieldContainer');
                const attendeesList = document.getElementById('attendeesList');
                const addAttendeeBtn = document.getElementById('addAttendeeBtn');
                if (attendeesContainer && attendeesList) {
                    if (enableMultiAttendees) {
                        attendeesContainer.style.display = 'block';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'inline-block';
                        }
                        attendeesList.innerHTML = '';
                        addAttendeeField();
                    } else {
                        attendeesContainer.style.display = 'none';
                        if (addAttendeeBtn) {
                            addAttendeeBtn.style.display = 'none';
                        }
                        attendeesList.innerHTML = '';
                    }
                }
                
                if (facility.available_day && Array.isArray(facility.available_day) && facility.available_day.length > 0) {
                    facilityAvailableDays = facility.available_day.map(day => day.toLowerCase());
                }
                
                window.currentFacilityTimeRange = {
                    start: facilityStartTime,
                    end: facilityEndTime
                };
                
                window.currentFacilityMaxBookingHours = maxBookingHours;
            }
        } catch (error) {
            console.error('Error loading facility info:', error);
        }
        
        const slots = generateTimeSlots(facilityStartTime, facilityEndTime);
        
        bookedSlots = {};
        const availabilityPromises = days.map(async (day) => {
            try {
                const result = await API.get(`/facilities/${facilityId}/availability?date=${day.date}`);
                console.log(`Availability result for ${day.date}:`, result);
                
                let bookings = [];
                if (result.success && result.data) {
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
                    if (booking.slots && booking.slots.length > 0) {
                        booking.slots.forEach(slot => {
                            let slotDate = slot.slot_date || day.date;
                            if (slotDate && typeof slotDate === 'string') {
                                if (slotDate.includes('T')) {
                                    slotDate = slotDate.split('T')[0];
                                } else if (slotDate.includes(' ')) {
                                    slotDate = slotDate.split(' ')[0];
                                }
                            }
                            
                            if (slotDate === day.date) {
                                let slotStartTime = slot.start_time || '';
                                let slotEndTime = slot.end_time || '';
                                
                                if (slotStartTime && typeof slotStartTime === 'string') {
                                    if (slotStartTime.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                        slotStartTime = slotStartTime.substring(0, 5); 
                                    } else if (slotStartTime.includes(' ')) {
                                        slotStartTime = slotStartTime.split(' ')[1]?.substring(0, 5);
                                    } else if (slotStartTime.includes('T')) {
                                        const timeMatch = slotStartTime.match(/T(\d{2}:\d{2})/);
                                        if (timeMatch) slotStartTime = timeMatch[1];
                                    }
                                }
                                
                                if (slotEndTime && typeof slotEndTime === 'string') {
                                    if (slotEndTime.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                        slotEndTime = slotEndTime.substring(0, 5); 
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
        
        await Promise.all(availabilityPromises);
        
        let userBookingsByDate = {};
        try {
            const bookingsResult = await API.get('/bookings/user/my-bookings');
            if (bookingsResult.success) {
                const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                console.log('User bookings loaded:', userBookings);
                console.log('Current facility ID:', facilityId);
                
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
        
                renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours, userBookingsByDate, facilityAvailableDays, enableMultiAttendees, facilityType);
    } catch (error) {
        console.error('Error loading timetable:', error);
        container.innerHTML = '<div class="timetable-no-slots">Error loading timetable. Please try again.</div>';
    }
}

function renderTimetable(days, slots, facilityId, facilityCapacity, maxBookingHours = 1, userBookingsByDate = {}, facilityAvailableDays = null, enableMultiAttendees = false, facilityType = null) {
    const container = document.getElementById('timetableContainer');
    if (!container) {
        console.error('Timetable container not found');
        return;
    }
    
    let html = '<div class="timetable-days">';
    
    days.forEach(day => {
        const dayBookedSlots = bookedSlots[day.date] || [];
        
        const isDayAvailable = !facilityAvailableDays || facilityAvailableDays.length === 0 || 
                                facilityAvailableDays.includes(day.dayOfWeek);
        
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
            const isDifferentDate = false;
            
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
                return;
            }
            
            let totalAttendees = 0;
            let hasOverlappingBookings = false;
            const slotStart = new Date(`${day.date} ${slot.start}:00`);
            const slotEnd = new Date(`${day.date} ${slot.end}:00`);
            
            const isFullCapacityType = facilityType && ['classroom', 'auditorium', 'laboratory'].includes(facilityType);
            const requiresFullCapacity = enableMultiAttendees || isFullCapacityType;
            
            dayBookedSlots.forEach(booking => {
                try {
                    const bookingStart = new Date(booking.start_time);
                    const bookingEnd = new Date(booking.end_time);
                    
                    if (isNaN(bookingStart.getTime()) || isNaN(bookingEnd.getTime())) {
                        return;
                    }
                    
                    if (slotStart < bookingEnd && slotEnd > bookingStart) {
                        hasOverlappingBookings = true;
                        if (requiresFullCapacity) {
                            totalAttendees = facilityCapacity;
                        } else {
                            totalAttendees += booking.expected_attendees || 1;
                        }
                    }
                } catch (error) {
                    console.error('Error processing booking:', booking, error);
                }
            });
            
            let isAvailable = true;
            if (requiresFullCapacity) {
                isAvailable = !hasOverlappingBookings;
            } else if (facilityCapacity !== null) {
                isAvailable = totalAttendees < facilityCapacity;
            } else {
                isAvailable = totalAttendees === 0;
            }
            
            const slotId = `slot-${day.date}-${slot.start}`;
            
            const isSelected = selectedTimeSlots.some(s => 
                s.date === day.date && s.start === slot.start && s.end === slot.end
            );
            
            const userBookingsOnDate = userBookingsByDate[day.date] || [];
            let isUserBooked = false;
            let userBookingStatus = null;
            
            if (userBookingsOnDate.length > 0) {
                console.log(`Checking ${userBookingsOnDate.length} user bookings for date ${day.date}, slot ${slot.start}-${slot.end}`);
            }
            
            userBookingsOnDate.forEach(userBooking => {
                try {
                    let bookingDateStr = day.date;
                    if (userBooking.booking_date) {
                        if (typeof userBooking.booking_date === 'string') {
                            bookingDateStr = userBooking.booking_date.split('T')[0]; 
                        } else if (userBooking.booking_date instanceof Date) {
                            bookingDateStr = userBooking.booking_date.toISOString().split('T')[0];
                        } else if (userBooking.booking_date.year) {
                            bookingDateStr = `${userBooking.booking_date.year}-${String(userBooking.booking_date.month).padStart(2, '0')}-${String(userBooking.booking_date.day).padStart(2, '0')}`;
                        }
                    }
                    
                    if (userBooking.slots && userBooking.slots.length > 0) {
                        userBooking.slots.forEach(bookingSlot => {
                            let slotDate = bookingSlot.slot_date || bookingDateStr;
                            if (slotDate && typeof slotDate === 'string') {
                                if (slotDate.includes('T')) {
                                    slotDate = slotDate.split('T')[0];
                                } else if (slotDate.includes(' ')) {
                                    slotDate = slotDate.split(' ')[0];
                                }
                            }
                            
                            if (slotDate !== day.date) {
                                return;
                            }
                            
                            let slotStartTime = null;
                            let slotEndTime = null;
                            
                            if (bookingSlot.start_time) {
                                let startTimeStr = String(bookingSlot.start_time);
                                if (startTimeStr.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                    slotStartTime = startTimeStr.substring(0, 5);
                                } else if (startTimeStr.includes(' ')) {
                                    slotStartTime = startTimeStr.split(' ')[1]?.substring(0, 5);
                                } else if (startTimeStr.includes('T')) {
                                    const timeMatch = startTimeStr.match(/T(\d{2}:\d{2})/);
                                    if (timeMatch) slotStartTime = timeMatch[1];
                                }
                            }
                            
                            if (bookingSlot.end_time) {
                                let endTimeStr = String(bookingSlot.end_time);
                                if (endTimeStr.match(/^\d{2}:\d{2}:\d{2}$/)) {
                                    slotEndTime = endTimeStr.substring(0, 5);
                                } else if (endTimeStr.includes(' ')) {
                                    slotEndTime = endTimeStr.split(' ')[1]?.substring(0, 5);
                                } else if (endTimeStr.includes('T')) {
                                    const timeMatch = endTimeStr.match(/T(\d{2}:\d{2})/);
                                    if (timeMatch) slotEndTime = timeMatch[1];
                                }
                            }
                            
                            if (slotStartTime && slotEndTime) {
                                if (slot.start === slotStartTime && slot.end === slotEndTime) {
                                    console.log('Found user booking slot match!', {
                                        slot: `${slot.start}-${slot.end}`,
                                        bookingSlot: `${slotStartTime}-${slotEndTime}`,
                                        status: userBooking.status
                                    });
                                    isUserBooked = true;
                                    userBookingStatus = userBooking.status;
                                    return;
                                }
                            }
                        });
                        
                        if (isUserBooked) {
                            return;
                        }
                    } else {
                        let userBookingStart, userBookingEnd;
                        
                        if (userBooking.start_time) {
                            let startTimeStr = '';
                            if (typeof userBooking.start_time === 'string') {
                                startTimeStr = userBooking.start_time;
                            } else {
                                startTimeStr = String(userBooking.start_time);
                            }
                            
                            let timePart = '';
                            if (startTimeStr.includes('T')) {
                                const timeMatch = startTimeStr.match(/T(\d{2}:\d{2}:\d{2})/);
                                if (timeMatch) {
                                    timePart = timeMatch[1].substring(0, 5);
                                }
                            } else if (startTimeStr.includes(' ')) {
                                const parts = startTimeStr.split(' ');
                                if (parts.length > 1) {
                                    timePart = parts[1].substring(0, 5);
                                }
                            }
                            
                            if (timePart) {
                                userBookingStart = new Date(`${bookingDateStr} ${timePart}:00`);
                            } else {
                                userBookingStart = new Date(userBooking.start_time);
                            }
                        } else {
                            return;
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
                                    timePart = timeMatch[1].substring(0, 5);
                                }
                            } else if (endTimeStr.includes(' ')) {
                                const parts = endTimeStr.split(' ');
                                if (parts.length > 1) {
                                    timePart = parts[1].substring(0, 5);
                                }
                            }
                            
                            if (timePart) {
                                userBookingEnd = new Date(`${bookingDateStr} ${timePart}:00`);
                            } else {
                                userBookingEnd = new Date(userBooking.end_time);
                            }
                        } else {
                            return;
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
                            return;
                        }
                    }
                } catch (error) {
                    console.error('Error checking user booking:', userBooking, error);
                }
            });
            
            const existingBookingHours = userBookingsOnDate.reduce((sum, b) => {
                if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                    let targetDate = day.date;
                    if (targetDate && typeof targetDate === 'string') {
                        targetDate = targetDate.split('T')[0].split(' ')[0];

                    }
                    
                    const slotsHours = b.slots
                        .filter(slot => {
                            let slotDate = slot.slot_date;
                            if (!slotDate) {
                                if (b.booking_date) {
                                    slotDate = typeof b.booking_date === 'string' 
                                        ? b.booking_date.split('T')[0].split(' ')[0]
                                        : b.booking_date;
                                } else {
                                    return false;
                                }
                            } else if (typeof slotDate === 'string') {
                                slotDate = slotDate.split('T')[0].split(' ')[0];
                            } else if (slotDate && slotDate.format) {
                                slotDate = slotDate.format('YYYY-MM-DD');
                            } else if (slotDate instanceof Date) {
                                slotDate = slotDate.toISOString().split('T')[0];
                            }
                            
                            return slotDate === targetDate;
                        })
                        .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                    return sum + slotsHours;
                }
                return sum + (b.duration_hours || 1);
            }, 0);
            const hasReachedLimit = existingBookingHours >= maxBookingHours;
            
            let slotClass = '';
            let isDisabled = false;
            
            if (isSelected) {
                slotClass = 'selected';
            } else if (isUserBooked) {
                if (userBookingStatus === 'pending') {
                    slotClass = 'user-booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as user-booked (pending)`);
                } else if (userBookingStatus === 'approved') {
                    slotClass = 'booked';
                    console.log(`Slot ${slot.start}-${slot.end} on ${day.date} marked as booked (approved)`);
                } else {
                    slotClass = 'user-booked';
                }
                isDisabled = true;
            } else if (isAvailable) {
                slotClass = 'available';
            } else {
                slotClass = 'booked';
            }
            
            if (hasReachedLimit && isAvailable && !isSelected && !isUserBooked && !isDifferentDate) {
                slotClass = 'disabled';
                isDisabled = true;
            } else if (!isAvailable && !isUserBooked && !isDifferentDate && !isSelected) {
                isDisabled = true;
            }
            
            let displayAttendees = totalAttendees;
            if (requiresFullCapacity && hasOverlappingBookings) {
                displayAttendees = facilityCapacity;
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

window.selectTimeSlot = async function(date, start, end, slotId) {
    const slot = document.getElementById(slotId);
    
    const isSelected = slot.classList.contains('selected');
    
    if (slot.classList.contains('booked') || slot.classList.contains('disabled') || slot.classList.contains('user-booked') || slot.classList.contains('unavailable')) {
        return;
    }
    
    const maxBookingHours = window.currentFacilityMaxBookingHours || 1;
    const facilitySelect = document.getElementById('bookingFacility');
    const facilityId = facilitySelect ? facilitySelect.value : null;
    
    const slotIndex = selectedTimeSlots.findIndex(s => 
        s.date === date && s.start === start && s.end === end
    );
    
    let newSelectedSlots = [];
    if (slotIndex >= 0) {
        selectedTimeSlots.splice(slotIndex, 1);
        
        slot.classList.remove('selected');
        slot.classList.add('available');
        
        newSelectedSlots = [...selectedTimeSlots];
    } else {
        const newSlot = { date, start, end };
        
        if (selectedTimeSlots.length > 0) {
            const existingSlotsOnOtherDates = selectedTimeSlots.filter(s => s.date !== date);
            
            if (existingSlotsOnOtherDates.length > 0) {
                const firstSelectedDate = existingSlotsOnOtherDates[0].date;
                const firstDateFormatted = new Date(firstSelectedDate).toLocaleDateString();
                const newDateFormatted = new Date(date).toLocaleDateString();
                
                if (typeof showToast !== 'undefined') {
                    showToast(`You can only select time slots on the same date. You have already selected slots on ${firstDateFormatted}. Please clear your current selection or select slots on the same date.`, 'warning');
                } else {
                    alert(`You can only select time slots on the same date.\n\nYou have already selected slots on: ${firstDateFormatted}\nYou are trying to select a slot on: ${newDateFormatted}\n\nPlease clear your current selection or select slots on the same date.`);
                }
                return;
            }
        }
        
        const sameDateSlots = selectedTimeSlots.filter(s => s.date === date);
        const totalSelectedSlots = sameDateSlots.length + 1;
        
        if (facilityId) {
            try {
                const bookingsResult = await API.get('/bookings/user/my-bookings');
                if (bookingsResult.success) {
                    const userBookings = bookingsResult.data.data?.data || bookingsResult.data.data || [];
                    
                    let targetDate = date;
                    if (targetDate && typeof targetDate === 'string') {
                        targetDate = targetDate.split('T')[0].split(' ')[0];
                    }
                    
                    const bookingsOnDate = userBookings.filter(b => {
                        let bookingDate = b.booking_date || '';
                        if (bookingDate && typeof bookingDate === 'string') {
                            bookingDate = bookingDate.split('T')[0].split(' ')[0];
                        } else if (bookingDate && bookingDate.format) {
                            bookingDate = bookingDate.format('YYYY-MM-DD');
                        } else if (bookingDate instanceof Date) {
                            bookingDate = bookingDate.toISOString().split('T')[0];
                        }
                        
                        const bookingFacilityId = b.facility_id || b.facility?.id;
                        return bookingDate === targetDate && bookingFacilityId == facilityId && 
                               (b.status === 'pending' || b.status === 'approved');
                    });
                    
                    const existingBookingHours = bookingsOnDate.reduce((sum, b) => {
                        if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                            const slotsHours = b.slots
                                .filter(slot => {
                                    let slotDate = slot.slot_date;
                                    if (!slotDate) {
                                        if (b.booking_date) {
                                            slotDate = typeof b.booking_date === 'string' 
                                                ? b.booking_date.split('T')[0].split(' ')[0]
                                                : b.booking_date;
                                        } else {
                                            return false;
                                        }
                                    } else if (typeof slotDate === 'string') {
                                        slotDate = slotDate.split('T')[0].split(' ')[0];
                                    } else if (slotDate && slotDate.format) {
                                        slotDate = slotDate.format('YYYY-MM-DD');
                                    } else if (slotDate instanceof Date) {
                                        slotDate = slotDate.toISOString().split('T')[0];
                                    }
                                    return slotDate === targetDate;
                                })
                                .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                            return sum + slotsHours;
                        }
                        return sum + (b.duration_hours || 1);
                    }, 0);
                    const selectedSlotsHours = totalSelectedSlots;
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
        
        selectedTimeSlots.push(newSlot);
        slot.classList.add('selected');
        newSelectedSlots = [...selectedTimeSlots];
    }
    
    if (newSelectedSlots.length > 0) {
        const sameDateSlots = newSelectedSlots.filter(s => s.date === date);
        if (sameDateSlots.length > 0) {
            sameDateSlots.sort((a, b) => a.start.localeCompare(b.start));
            
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
        const dateInput = document.getElementById('selectedBookingDate');
        const startInput = document.getElementById('bookingStartTime');
        const endInput = document.getElementById('bookingEndTime');
        
        if (dateInput) dateInput.value = '';
        if (startInput) startInput.value = '';
        if (endInput) endInput.value = '';
    }
    
    const errorDiv = document.getElementById('timeSlotError');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
};

function clearTimetable() {
    const container = document.getElementById('timetableContainer');
    if (container) {
        container.innerHTML = '<div class="timetable-no-slots">Please select a facility to view available time slots</div>';
    }
    selectedTimeSlots = [];
    document.querySelectorAll('.timetable-slot.selected').forEach(s => {
        s.classList.remove('selected');
    });
}

function validateStudentIdFormat(studentId) {
    if (!studentId || !studentId.trim()) return true;
    const trimmed = studentId.trim();
    const pattern = /^\d{2}WMR\d{5}$/;
    return pattern.test(trimmed);
}

function showPassportError(input, message) {
    const existingError = input.parentElement.parentElement.querySelector('.passport-error');
    if (existingError) {
        existingError.remove();
    }
    
    input.classList.remove('is-invalid');
    
    if (message) {
        input.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'passport-error text-danger';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        const inputGroup = input.closest('.input-group');
        if (inputGroup && inputGroup.parentElement) {
            inputGroup.parentElement.appendChild(errorDiv);
        }
    }
}

function validatePassportInput(input) {
    const value = input.value.trim();
    if (value && !validateStudentIdFormat(value)) {
        showPassportError(input, 'Invalid format. Must be YYWMR##### (e.g., 25WMR00001)');
        return false;
    } else {
        showPassportError(input, null);
        return true;
    }
}

window.addAttendeeField = function() {
    const attendeesList = document.getElementById('attendeesList');
    const maxAttendees = window.currentFacilityMaxAttendees || 1000;
    
    if (!attendeesList) return;
    
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
                   placeholder="Enter passport number (e.g., 25WMR00001)" 
                   required
                   data-index="${fieldIndex}">
            <button type="button" class="btn btn-outline-danger" onclick="removeAttendeeField(this)" ${currentCount === 0 ? 'disabled' : ''}>
                <i class="fas fa-times"></i> Remove
            </button>
        </div>
    `;
    
    attendeesList.appendChild(attendeeField);
    
    const input = attendeeField.querySelector('.attendee-passport-input');
    if (input) {
        input.setAttribute('data-validation-attached', 'true');
        input.addEventListener('blur', function() {
            validateAllAttendeePassports();
        });
        input.addEventListener('input', function() {
            validateAllAttendeePassports();
        });
    }
    
    updateAttendeeFieldNumbers();
    
    validateAllAttendeePassports();
}

window.removeAttendeeField = function(button) {
    const attendeeField = button.closest('.attendee-field');
    if (attendeeField) {
        attendeeField.remove();
        updateAttendeeFieldNumbers();
        validateAllAttendeePassports();
    }
}

function validateAllAttendeePassports() {
    const passportInputs = document.querySelectorAll('.attendee-passport-input');
    const passportValues = [];
    let hasErrors = false;
    
    passportInputs.forEach((input, index) => {
        const trimmedValue = input.value.trim();
        const upperValue = trimmedValue.toUpperCase();
        
        const existingError = input.parentElement.parentElement.querySelector('.passport-error');
        if (existingError) {
            existingError.remove();
        }
        input.classList.remove('is-invalid');
        
        if (trimmedValue) {
            if (!validateStudentIdFormat(trimmedValue)) {
                showPassportError(input, 'Invalid format. Must be YYWMR##### (e.g., 25WMR00001)');
                hasErrors = true;
            } else {
                passportValues.push({
                    value: upperValue,
                    original: trimmedValue,
                    input: input,
                    index: index
                });
            }
        }
    });
    
    const duplicates = new Set();
    const valueCounts = new Map();
    
    passportValues.forEach(item => {
        const count = valueCounts.get(item.value) || 0;
        valueCounts.set(item.value, count + 1);
        if (count > 0) {
            duplicates.add(item.value);
        }
    });
    
    passportValues.forEach(item => {
        if (duplicates.has(item.value)) {
            showPassportError(item.input, 'This passport number is already used. Each attendee must have a unique passport number.');
            hasErrors = true;
        }
    });
    
    return !hasErrors && duplicates.size === 0;
}

function checkDuplicatePassports() {
    return validateAllAttendeePassports();
}

function attachPassportValidation() {
    const passportInputs = document.querySelectorAll('.attendee-passport-input');
    passportInputs.forEach(input => {
        if (input.hasAttribute('data-validation-attached')) {
            return;
        }
        
        input.setAttribute('data-validation-attached', 'true');
        
        input.addEventListener('blur', function() {
            validateAllAttendeePassports();
        });
        input.addEventListener('input', function() {
            validateAllAttendeePassports();
        });
    });
}

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
        if (removeBtn) {
            removeBtn.disabled = fields.length <= 1;
        }
    });
    
    if (addAttendeeBtn) {
        addAttendeeBtn.disabled = fields.length >= maxAttendees;
    }
    
    attachPassportValidation();
}

window.handleFacilityChange = function(select) {
    console.log('Facility changed to:', select.value);
    if (select && select.value) {
        updateTimeInputConstraints(select.value);
        loadTimetable(select.value);
    } else {
        clearTimetable();
        window.currentFacilityTimeRange = { start: '08:00', end: '20:00' };
        
        const attendeesContainer = document.getElementById('attendeesFieldContainer');
        const attendeesInput = document.getElementById('bookingAttendees');
        if (attendeesContainer) {
            attendeesContainer.style.display = 'none';
        }
        if (attendeesInput) {
            attendeesInput.value = '1';
            attendeesInput.removeAttribute('required');
        }
        
        window.currentFacilityEnableMultiAttendees = false;
        window.currentFacilityMaxAttendees = null;
    }
};

async function updateTimeInputConstraints(facilityId) {
    try {
        const facilityResult = await API.get(`/facilities/${facilityId}`);
        if (facilityResult.success && facilityResult.data) {
            const facility = facilityResult.data.data || facilityResult.data;
            let startTime = '08:00';
            let endTime = '20:00';
            
            if (facility.available_time && typeof facility.available_time === 'object') {
                if (facility.available_time.start) {
                    startTime = facility.available_time.start;
                }
                if (facility.available_time.end) {
                    endTime = facility.available_time.end;
                }
            }
            
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
            

            const maxBookingHours = facility.max_booking_hours || 1;
            window.currentFacilityTimeRange = { start: startTime, end: endTime };
            window.currentFacilityMaxBookingHours = maxBookingHours;
            

            const attendeesContainer = document.getElementById('attendeesFieldContainer');
            const attendeesList = document.getElementById('attendeesList');
            const addAttendeeBtn = document.getElementById('addAttendeeBtn');
            const enableMultiAttendees = facility.enable_multi_attendees || false;
            const maxAttendees = facility.max_attendees || facility.capacity || 1000;
            

            window.currentFacilityEnableMultiAttendees = enableMultiAttendees;
            window.currentFacilityMaxAttendees = maxAttendees;
            
            if (attendeesContainer && attendeesList) {
                if (enableMultiAttendees) {
                    attendeesContainer.style.display = 'block';
                    if (addAttendeeBtn) {
                        addAttendeeBtn.style.display = 'inline-block';
                    }
                    if (attendeesList.children.length === 0) {
                        addAttendeeField();
                    } else {
                        attachPassportValidation();
                    }
                } else {
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




function bindBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {

        const newForm = bookingForm.cloneNode(true);
        bookingForm.parentNode.replaceChild(newForm, bookingForm);
        
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const bookingId = this.dataset.bookingId; 

    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (!submitBtn) {
        alert('Submit button not found');
        return;
    }
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';


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
    

    const facilitySelect = document.getElementById('bookingFacility');
    

    const slotsByDate = {};
    selectedTimeSlots.forEach(slot => {
        if (!slotsByDate[slot.date]) {
            slotsByDate[slot.date] = [];
        }
        slotsByDate[slot.date].push(slot);
    });
    
 
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
            
           
            let targetDate = date;
            if (targetDate && typeof targetDate === 'string') {
                targetDate = targetDate.split('T')[0].split(' ')[0]; 
            }
            
            const totalHours = bookingsOnDate.reduce((sum, b) => {
              
                if (b.slots && Array.isArray(b.slots) && b.slots.length > 0) {
                    const slotsHours = b.slots
                        .filter(slot => {
                           
                            let slotDate = slot.slot_date;
                            if (!slotDate) {
                               
                                if (b.booking_date) {
                                    slotDate = typeof b.booking_date === 'string' 
                                        ? b.booking_date.split('T')[0].split(' ')[0]
                                        : b.booking_date;
                                } else {
                                    return false; 
                                }
                            } else if (typeof slotDate === 'string') {
                                slotDate = slotDate.split('T')[0].split(' ')[0]; 
                            } else if (slotDate && slotDate.format) {
                               
                                slotDate = slotDate.format('YYYY-MM-DD');
                            } else if (slotDate instanceof Date) {
                                slotDate = slotDate.toISOString().split('T')[0];
                            }
                            return slotDate === targetDate; 
                        })
                        .reduce((slotSum, slot) => slotSum + (slot.duration_hours || 1), 0);
                    return sum + slotsHours;
                }
               
                return sum + (b.duration_hours || 1);
            }, 0);
            const newBookingHours = dateSlots.length; 
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
    
   
    let expectedAttendees = 1; 
    let attendeesPassports = []; 
    const enableMultiAttendees = window.currentFacilityEnableMultiAttendees || false;
    
    if (enableMultiAttendees) {
      
        if (!validateAllAttendeePassports()) {
            if (typeof showToast !== 'undefined') {
                showToast('Please fix the passport errors (invalid format or duplicates) before submitting.', 'warning');
            } else {
                alert('Please fix the passport errors (invalid format or duplicates) before submitting.');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }
        
       
        const passportInputs = document.querySelectorAll('.attendee-passport-input');
        passportInputs.forEach(input => {
            const passport = input.value.trim();
            if (passport && validateStudentIdFormat(passport)) {
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
       
        expectedAttendees = 1;
    }
    

    expectedAttendees = parseInt(expectedAttendees) || 1;
    
    try {
        let successCount = 0;
        let errorCount = 0;
        const errors = [];
        
        let bookingDate = date;
        if (date instanceof Date) {
          
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            bookingDate = `${year}-${month}-${day}`;
        } else if (typeof date === 'string') {
           
            bookingDate = date.split('T')[0].split(' ')[0];
        }
        
        const timeSlots = [];
        dateSlots.forEach(slot => {
           
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
              
                let errorMsg = result.error || result.data?.message || 'Unknown error';
                if (result.data?.errors) {
                   
                    if (result.data.errors['attendees_passports']) {
                        const arrayError = Array.isArray(result.data.errors['attendees_passports']) 
                            ? result.data.errors['attendees_passports'][0] 
                            : result.data.errors['attendees_passports'];
                        
                        const passportInputs = document.querySelectorAll('.attendee-passport-input');
                        passportInputs.forEach((input) => {
                            if (input.value.trim()) {
                                showPassportError(input, arrayError);
                            }
                        });
                        
                        if (typeof showToast !== 'undefined') {
                            showToast(arrayError, 'error');
                        }
                    }
                    
                  
                    const hasAttendeeErrors = Object.keys(result.data.errors).some(key => 
                        key.startsWith('attendees_passports.')
                    );
                    
                    if (hasAttendeeErrors) {
                        const passportInputs = document.querySelectorAll('.attendee-passport-input');
                        passportInputs.forEach((input, index) => {
                            const errorKey = `attendees_passports.${index}`;
                            const errorKeyWildcard = 'attendees_passports.*';
                            
                            if (result.data.errors[errorKey]) {
                                const errorMessage = Array.isArray(result.data.errors[errorKey]) 
                                    ? result.data.errors[errorKey][0] 
                                    : result.data.errors[errorKey];
                                showPassportError(input, errorMessage);
                            } 
                            else if (result.data.errors[errorKeyWildcard] && input.value.trim()) {
                                const errorMessage = Array.isArray(result.data.errors[errorKeyWildcard]) 
                                    ? result.data.errors[errorKeyWildcard][0] 
                                    : result.data.errors[errorKeyWildcard];
                                showPassportError(input, errorMessage);
                            }
                        });
                    }
                    
                    const validationErrors = [];
                    Object.keys(result.data.errors).forEach(key => {
                        const errorValue = result.data.errors[key];
                        if (Array.isArray(errorValue)) {
                            validationErrors.push(...errorValue);
                        } else {
                            validationErrors.push(errorValue);
                        }
                    });
                    
                    if (validationErrors.length > 0) {
                        errorMsg = validationErrors.join('. ');
                    }
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
            if (errorCount === 0) {
                if (typeof showToast !== 'undefined') {
                    showToast(`Successfully created ${successCount} booking(s)! Redirecting...`, 'success');
                    setTimeout(() => {
                        window.location.href = '/bookings';
                    }, 1500);
                } else {
                    alert(`Successfully created ${successCount} booking(s)!`);
                    window.location.href = '/bookings';
                }
            } else {
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

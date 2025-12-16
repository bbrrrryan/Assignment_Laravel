/**
 * Facility Form JavaScript
 * Handles form interactions for both create and edit facility pages
 */

// Toggle max attendees field based on enable_multi_attendees checkbox
window.toggleMaxAttendeesField = function() {
    const enableMultiAttendees = document.getElementById('enable_multi_attendees');
    const maxAttendeesContainer = document.getElementById('max_attendees_container');
    const maxAttendeesInput = document.getElementById('max_attendees');
    const maxAttendeesRequired = document.getElementById('max_attendees_required');
    const capacityInput = document.getElementById('capacity');
    
    if (!enableMultiAttendees || !maxAttendeesContainer || !maxAttendeesInput) {
        return;
    }
    
    if (enableMultiAttendees.checked) {
        maxAttendeesContainer.style.display = 'block';
        if (maxAttendeesRequired) {
            maxAttendeesRequired.style.display = 'inline';
        }
        maxAttendeesInput.setAttribute('required', 'required');
        if (capacityInput && capacityInput.value) {
            maxAttendeesInput.setAttribute('max', capacityInput.value);
        }
    } else {
        maxAttendeesContainer.style.display = 'none';
        if (maxAttendeesRequired) {
            maxAttendeesRequired.style.display = 'none';
        }
        maxAttendeesInput.removeAttribute('required');
        maxAttendeesInput.value = '';
    }
};

// Initialize form functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    }

    // Time validation - ensure end time is after start time
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (startTimeInput && endTimeInput) {
        function validateTimeRange() {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            
            if (startTime && endTime && startTime >= endTime) {
                endTimeInput.setCustomValidity('The end time must be later than the start time.');
            } else {
                endTimeInput.setCustomValidity('');
            }
        }
        
        startTimeInput.addEventListener('change', validateTimeRange);
        endTimeInput.addEventListener('change', validateTimeRange);
    }

    // Multi-attendees toggle initialization
    const enableMultiAttendees = document.getElementById('enable_multi_attendees');
    if (enableMultiAttendees) {
        enableMultiAttendees.addEventListener('change', window.toggleMaxAttendeesField);
        
        // Set initial state
        if (enableMultiAttendees.checked) {
            window.toggleMaxAttendeesField();
        }
        
        // Update max when capacity changes
        const capacityInput = document.getElementById('capacity');
        const maxAttendeesInput = document.getElementById('max_attendees');
        if (capacityInput && maxAttendeesInput) {
            capacityInput.addEventListener('change', function() {
                if (this.value && enableMultiAttendees.checked) {
                    maxAttendeesInput.setAttribute('max', this.value);
                }
            });
        }
    }
});


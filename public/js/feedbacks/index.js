let feedbacks = [];
let facilities = [];
let allBookings = []; // Store all bookings for filtering
let ratedBookingIds = []; // Store booking IDs that already have feedback
let selectedImageBase64 = null;
let paginationData = null;
let currentPage = 1;

function initFeedbacks() {
    // Update title and button visibility based on user role
    const titleElement = document.getElementById('feedbacksTitle');
    const submitBtn = document.getElementById('submitFeedbackBtn');

    if (API.isAdmin()) {
        if (titleElement) {
            titleElement.textContent = 'Feedback Management';
        }
        // Hide submit button for admin - admin can only reply, not submit
        if (submitBtn) {
            submitBtn.style.display = 'none';
        }
    } else {
        if (titleElement) {
            titleElement.textContent = 'My Feedbacks';
        }
        // Show submit button for students
        if (submitBtn) {
            submitBtn.style.display = 'block';
        }
    }

    loadFeedbacks();
    // Load bookings first, then extract facility types from bookings
    loadBookings();
}

async function loadFeedbacks(page = 1) {
    showLoading(document.getElementById('feedbacksList'));
    currentPage = page;

    // Build query parameters
    const params = new URLSearchParams();
    params.append('page', page);
    
    const search = document.getElementById('searchFilter')?.value.trim();
    const type = document.getElementById('typeFilter')?.value;
    const status = document.getElementById('statusFilter')?.value;
    
    if (search) params.append('search', search);
    if (type) params.append('type', type);
    if (status) params.append('status', status);

    // Use appropriate endpoint based on user role
    const endpoint = API.isAdmin() ? '/feedbacks' : '/feedbacks/user/my-feedbacks';
    const url = `${endpoint}?${params.toString()}`;
    const result = await API.get(url);

    if (result.success) {
        const responseData = result.data.data;
        feedbacks = responseData.data || [];
        paginationData = {
            current_page: responseData.current_page || 1,
            last_page: responseData.last_page || 1,
            per_page: responseData.per_page || 10,
            total: responseData.total || 0,
            from: responseData.from || 0,
            to: responseData.to || 0,
        };
        displayFeedbacks(feedbacks);
        displayPagination();
    } else {
        showError(document.getElementById('feedbacksList'), result.error || 'Failed to load feedbacks');
        console.error('Error loading feedbacks:', result);
    }
}

function loadFacilityTypes() {
    // Only load facility types for non-admin users (students/staff)
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return; // Admin doesn't need this filter
    }

    // Use already loaded bookings to extract facility types
    const select = document.getElementById('facilityTypeFilter');
    if (select && allBookings.length > 0) {
        // Extract unique facility types from bookings
        const seenTypes = new Set();
        const typeOptions = [];

        allBookings.forEach(booking => {
            const facilityType = booking.facility?.type;
            if (facilityType) {
                const key = String(facilityType).toLowerCase();
                if (!seenTypes.has(key)) {
                    seenTypes.add(key);
                    
                    // Capitalize first letter of each word
                    const label = String(facilityType)
                        .toLowerCase()
                        .split(' ')
                        .filter(w => w.length > 0)
                        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                        .join(' ');
                    
                    typeOptions.push(`<option value="${facilityType}">${label}</option>`);
                }
            }
        });

        select.innerHTML = '<option value="">All Facility Types</option>' + typeOptions.join('');
    }
}

async function loadBookings(facilityTypeFilter = null) {
    // Only load bookings for non-admin users (students/staff)
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return; // Admin doesn't need to select bookings
    }

    // Load user's feedbacks first to get rated booking IDs
    await loadRatedBookingIds();

    const result = await API.get('/bookings/user/my-bookings');

    if (result.success) {
        // Store all bookings for filtering
        allBookings = result.data.data?.data || result.data.data || [];
        
        // Filter out bookings that already have feedback
        allBookings = allBookings.filter(booking => !ratedBookingIds.includes(booking.id));
        
        // Filter bookings by facility type if filter is provided
        let filteredBookings = allBookings;
        if (facilityTypeFilter) {
            filteredBookings = allBookings.filter(booking => {
                const bookingFacilityType = booking.facility?.type;
                return bookingFacilityType && String(bookingFacilityType).toLowerCase() === String(facilityTypeFilter).toLowerCase();
            });
        }
        
        const select = document.getElementById('feedbackBooking');
        if (select) {
            const options = ['<option value="">None</option>'];
            
            filteredBookings.forEach(booking => {
                // Format: "Booking #123 - Facility Name - 2024-01-20"
                const facilityName = booking.facility?.name || 'Unknown Facility';
                const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
                const status = booking.status || 'unknown';
                
                const label = `Booking #${booking.id} - ${facilityName} - ${bookingDate} (${status})`;
                options.push(`<option value="${booking.id}">${label}</option>`);
            });

            select.innerHTML = options.join('');
        }
        
        // After loading bookings, populate facility type filter
        loadFacilityTypes();
    }
}

async function loadRatedBookingIds() {
    // Only load for non-admin users (students/staff)
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return;
    }

    try {
        const result = await API.get('/feedbacks/user/my-feedbacks');
        if (result.success) {
            const userFeedbacks = result.data.data?.data || result.data.data || [];
            // Extract booking IDs that already have feedback
            ratedBookingIds = userFeedbacks
                .filter(feedback => feedback.booking_id !== null && feedback.booking_id !== undefined)
                .map(feedback => feedback.booking_id);
        }
    } catch (error) {
        console.error('Error loading rated booking IDs:', error);
        ratedBookingIds = [];
    }
}

function filterBookingsByFacilityType() {
    const facilityTypeFilter = document.getElementById('facilityTypeFilter');
    const selectedType = facilityTypeFilter ? facilityTypeFilter.value : null;
    
    // Filter existing bookings without reloading from API
    // Note: allBookings already excludes rated bookings
    const select = document.getElementById('feedbackBooking');
    if (select && allBookings.length > 0) {
        let filteredBookings = allBookings;
        if (selectedType) {
            filteredBookings = allBookings.filter(booking => {
                const bookingFacilityType = booking.facility?.type;
                return bookingFacilityType && String(bookingFacilityType).toLowerCase() === String(selectedType).toLowerCase();
            });
        }
        
        const options = ['<option value="">None</option>'];
        
        filteredBookings.forEach(booking => {
            // Format: "Booking #123 - Facility Name - 2024-01-20"
            const facilityName = booking.facility?.name || 'Unknown Facility';
            const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
            const status = booking.status || 'unknown';
            
            const label = `Booking #${booking.id} - ${facilityName} - ${bookingDate} (${status})`;
            options.push(`<option value="${booking.id}">${label}</option>`);
        });

        select.innerHTML = options.join('');
    }
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const date = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    return `${date} ${time}`;
}

// Format type name (capitalize first letter)
function formatTypeName(type) {
    if (!type) return 'General';
    return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
}

// Format facility type name (capitalize first letter)
function formatFacilityType(type) {
    if (!type) return 'N/A';
    return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
}

function displayFeedbacks(feedbacksToShow) {
    const container = document.getElementById('feedbacksList');
    if (feedbacksToShow.length === 0) {
        container.innerHTML = '<div class="table-container"><table class="data-table"><tbody><tr><td colspan="8" class="text-center">No feedbacks found</td></tr></tbody></table></div>';
        return;
    }

    const isAdmin = typeof API !== 'undefined' && API.isAdmin();

    // Data is already sorted by backend (orderBy created_at desc)
    container.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    ${isAdmin ? '<th>User</th>' : ''}
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Rating</th>
                    ${!isAdmin ? '<th>Facility</th>' : ''}
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${feedbacksToShow.map(feedback => {
                    // Format status for display
                    const formatStatus = (status) => {
                        const statusMap = {
                            'pending': 'Pending',
                            'under_review': 'Under Review',
                            'resolved': 'Resolved',
                            'rejected': 'Rejected',
                            'blocked': 'Blocked'
                        };
                        return statusMap[status] || status;
                    };

                    // Get status badge class
                    const getStatusBadgeClass = (status) => {
                        if (status === 'resolved') return 'badge-success';
                        if (status === 'pending') return 'badge-warning';
                        if (status === 'under_review') return 'badge-info';
                        if (status === 'blocked' || status === 'rejected') return 'badge-danger';
                        return 'badge-secondary';
                    };

                    // Get type badge class
                    const getTypeBadgeClass = (type) => {
                        if (type === 'complaint') return 'badge-danger';
                        if (type === 'suggestion') return 'badge-info';
                        if (type === 'compliment') return 'badge-success';
                        return 'badge-secondary';
                    };

                    // Rating stars
                    const ratingStars = feedback.rating !== null && feedback.rating !== undefined 
                        ? Array.from({length: 5}, (_, i) => 
                            `<i class="fas fa-star ${i < feedback.rating ? 'text-warning' : 'text-muted'}" style="color: ${i < feedback.rating ? '#ffc107' : '#e0e0e0'}; font-size: 0.85rem;"></i>`
                          ).join('') + ` <span style="margin-left: 5px; font-weight: 600;">${feedback.rating}/5</span>`
                        : 'N/A';

                    return `
                    <tr>
                        ${isAdmin ? `<td>${feedback.user ? feedback.user.name : 'Unknown User'}</td>` : ''}
                        <td>${feedback.subject || 'No Subject'}</td>
                        <td>
                            <span class="badge ${getTypeBadgeClass(feedback.type)}">
                                ${formatTypeName(feedback.type)}
                            </span>
                        </td>
                        <td style="white-space: nowrap;">${ratingStars}</td>
                        ${!isAdmin ? `<td>${feedback.facility_name || 'N/A'}</td>` : ''}
                        <td>
                            <span class="badge ${getStatusBadgeClass(feedback.status)}">
                                ${formatStatus(feedback.status)}
                            </span>
                        </td>
                        <td>${formatDateTime(feedback.created_at)}</td>
                        <td class="actions">
                            <button class="btn-sm btn-info" onclick="viewFeedback(${feedback.id})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${isAdmin && feedback.status !== 'rejected' && feedback.status !== 'resolved' && !feedback.is_blocked ? `
                                <button class="btn-sm btn-success" onclick="replyToFeedback(${feedback.id})" title="Reply">
                                    <i class="fas fa-reply"></i>
                                </button>
                            ` : ''}
                            ${isAdmin && feedback.status !== 'resolved' && feedback.status !== 'rejected' && !feedback.is_blocked ? `
                                <button class="btn-sm btn-warning" onclick="rejectFeedback(${feedback.id})" title="Reject">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            ` : ''}
                            ${isAdmin && feedback.status !== 'rejected' && !feedback.is_blocked ? `
                                <button class="btn-sm btn-danger" onclick="blockFeedback(${feedback.id})" title="Block">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
                }).join('')}
            </tbody>
        </table>
    `;
}

// Make functions global
window.filterFeedbacks = function() {
    // Reload feedbacks with filters (server-side filtering)
    loadFeedbacks(1);
};

// Display pagination - Bootstrap 5 style (same as Facility Management)
function displayPagination() {
    if (!paginationData || paginationData.last_page <= 1) {
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
        return;
    }

    const container = document.getElementById('paginationContainer');
    if (!container) return;

    const { current_page, last_page } = paginationData;
    
    let paginationHTML = '<ul class="pagination">';

    // Previous button
    if (current_page > 1) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadFeedbacks(${current_page - 1}); return false;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        </li>`;
    } else {
        paginationHTML += `<li class="page-item disabled">
            <span class="page-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </span>
        </li>`;
    }

    // Page numbers
    const maxPagesToShow = 5;
    let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(last_page, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    if (startPage > 1) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadFeedbacks(1); return false;">1</a>
        </li>`;
        if (startPage > 2) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === current_page) {
            paginationHTML += `<li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
        } else {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadFeedbacks(${i}); return false;">${i}</a>
            </li>`;
        }
    }

    if (endPage < last_page) {
        if (endPage < last_page - 1) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadFeedbacks(${last_page}); return false;">${last_page}</a>
        </li>`;
    }

    // Next button
    if (current_page < last_page) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadFeedbacks(${current_page + 1}); return false;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </li>`;
    } else {
        paginationHTML += `<li class="page-item disabled">
            <span class="page-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        </li>`;
    }

    paginationHTML += '</ul>';
    container.innerHTML = paginationHTML;
}

window.showCreateModal = function() {
    // Facility types are already loaded during initialization
    // No need to reload them when opening the modal
    const form = document.getElementById('feedbackForm');
    if (form) {
        form.reset();
    }
    const modal = document.getElementById('feedbackModal');
    if (modal) {
        modal.style.display = 'block';
    }
};

window.closeModal = function() {
    const modal = document.getElementById('feedbackModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Reset image selection
    selectedImageBase64 = null;
    const imageInput = document.getElementById('feedbackImage');
    if (imageInput) {
        imageInput.value = '';
    }
    const imagePreview = document.getElementById('imagePreview');
    if (imagePreview) {
        imagePreview.style.display = 'none';
    }
    const previewImg = document.getElementById('previewImg');
    if (previewImg) {
        previewImg.src = '';
    }
};

window.viewFeedback = function(id) {
    const basePath = API.isAdmin() ? '/admin/feedbacks' : '/feedbacks';
    const currentHost = window.location.hostname;
    const baseUrl = `http://${currentHost}:8001`;
    window.location.href = `${baseUrl}${basePath}/${id}`;
};

// Admin function to reply to feedback
window.replyToFeedback = function(id) {
    // Find the feedback data
    const feedback = feedbacks.find(f => f.id == id);
    if (!feedback) {
        alert('Feedback not found');
        return;
    }

    // Display feedback info in modal
    const infoDiv = document.getElementById('replyFeedbackInfo');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <div class="reply-feedback-preview">
                <div class="preview-header">
                    <strong>Subject:</strong> ${feedback.subject || 'N/A'}
                </div>
                <div class="preview-type">
                    <span class="status-badge status-${feedback.type}">${formatTypeName(feedback.type)}</span>
                </div>
                <div class="preview-message">
                    <strong>Original Message:</strong>
                    <p>${feedback.message || 'No message'}</p>
                </div>
            </div>
        `;
    }

    // Reset form
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.reset();
        const replyMessage = document.getElementById('replyMessage');
        if (replyMessage) {
            replyMessage.value = '';
        }
        replyForm.dataset.feedbackId = id;
    }

    // Show modal
    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.style.display = 'block';
    }
};

window.closeReplyModal = function() {
    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.style.display = 'none';
    }
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.reset();
        delete replyForm.dataset.feedbackId;
        const submitBtn = replyForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Send Response';
        }
    }
    // Clear reply message
    const replyMessage = document.getElementById('replyMessage');
    if (replyMessage) {
        replyMessage.value = '';
    }
};

// Admin function to block feedback
window.blockFeedback = function(id) {
    // Find the feedback data
    const feedback = feedbacks.find(f => f.id == id);
    if (!feedback) {
        alert('Feedback not found');
        return;
    }

    // Display feedback info in modal
    const infoDiv = document.getElementById('blockFeedbackInfo');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <div class="reply-feedback-preview">
                <div class="preview-header">
                    <strong>Subject:</strong> ${feedback.subject || 'N/A'}
                </div>
                <div class="preview-type">
                    <span class="status-badge status-${feedback.type}">${formatTypeName(feedback.type)}</span>
                </div>
                <div class="preview-message">
                    <strong>Original Message:</strong>
                    <p>${feedback.message || 'No message'}</p>
                </div>
            </div>
        `;
    }

    // Reset form
    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.reset();
        const blockReason = document.getElementById('blockReason');
        if (blockReason) {
            blockReason.value = '';
        }
        blockForm.dataset.feedbackId = id;
    }

    // Show modal
    const blockModal = document.getElementById('blockModal');
    if (blockModal) {
        blockModal.style.display = 'block';
    }
};

window.closeBlockModal = function() {
    const blockModal = document.getElementById('blockModal');
    if (blockModal) {
        blockModal.style.display = 'none';
    }
    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.reset();
        delete blockForm.dataset.feedbackId;
    }
};

// Admin function to reject feedback
window.rejectFeedback = function(id) {
    if (!confirm('Are you sure you want to reject this feedback? This action cannot be undone.')) {
        return;
    }

    API.put(`/feedbacks/${id}/reject`, {}).then(result => {
        if (result.success) {
            loadFeedbacks(currentPage);
            if (typeof showToast !== 'undefined') {
                showToast('Feedback rejected successfully', 'success');
            } else {
                alert('Feedback rejected successfully');
            }
        } else {
            if (typeof showToast !== 'undefined') {
                showToast(result.error || 'Failed to reject feedback', 'error');
            } else {
                alert(result.error || 'Failed to reject feedback');
            }
        }
    }).catch(error => {
        console.error('Error rejecting feedback:', error);
        if (typeof showToast !== 'undefined') {
            showToast('An error occurred while rejecting the feedback', 'error');
        } else {
            alert('An error occurred while rejecting the feedback');
        }
    });
};

// Handle image upload and convert to base64
window.handleImageUpload = function(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    // Validate file type
    if (!file.type.match('image.*')) {
        if (typeof showToast === 'function') {
            showToast('Please select a valid image file', 'error');
        } else {
            alert('Please select a valid image file');
        }
        event.target.value = '';
        return;
    }

    // Validate file size (max 1MB to avoid database packet size issues)
    // Base64 encoding increases size by ~33%, so 1MB file becomes ~1.33MB base64
    if (file.size > 1 * 1024 * 1024) {
        if (typeof showToast === 'function') {
            showToast('Image size must be less than 1MB. Please compress your image before uploading.', 'error');
        } else {
            alert('Image size must be less than 1MB. Please compress your image before uploading.');
        }
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const base64 = e.target.result;
        
        // Double check base64 size (should be less than ~1.5MB)
        if (base64.length > 1500000) {
            if (typeof showToast === 'function') {
                showToast('Image is too large. Please use a smaller image (max 1MB).', 'error');
            } else {
                alert('Image is too large. Please use a smaller image (max 1MB).');
            }
            event.target.value = '';
            const imagePreview = document.getElementById('imagePreview');
            if (imagePreview) {
                imagePreview.style.display = 'none';
            }
            return;
        }

        selectedImageBase64 = base64;
        // Show preview
        const previewImg = document.getElementById('previewImg');
        const imagePreview = document.getElementById('imagePreview');
        if (previewImg) {
            previewImg.src = selectedImageBase64;
        }
        if (imagePreview) {
            imagePreview.style.display = 'block';
        }
    };
    reader.onerror = function() {
        if (typeof showToast === 'function') {
            showToast('Error reading image file', 'error');
        } else {
            alert('Error reading image file');
        }
    };
    reader.readAsDataURL(file);
};

// Remove selected image
window.removeImage = function() {
    selectedImageBase64 = null;
    const imageInput = document.getElementById('feedbackImage');
    if (imageInput) {
        imageInput.value = '';
    }
    const imagePreview = document.getElementById('imagePreview');
    if (imagePreview) {
        imagePreview.style.display = 'none';
    }
    const previewImg = document.getElementById('previewImg');
    if (previewImg) {
        previewImg.src = '';
    }
};

// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initFeedbacks();

    // Handle reply form submission
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        const newReplyForm = replyForm.cloneNode(true);
        replyForm.parentNode.replaceChild(newReplyForm, replyForm);
        
        newReplyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const feedbackId = this.dataset.feedbackId;
            if (!feedbackId) {
                alert('Feedback ID not found');
                return;
            }

            const responseInput = document.getElementById('replyMessage');
            const response = responseInput ? responseInput.value.trim() : '';

            if (!response) {
                alert('Please enter a response');
                return;
            }

            if (typeof API === 'undefined') {
                alert('API not loaded');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn) return;
            
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const result = await API.put(`/feedbacks/${feedbackId}/respond`, { response: response });

                if (result.success) {
                    closeReplyModal();
                    
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Response submitted successfully!', 'success');
                    } else {
                        alert('Response submitted successfully!');
                    }
                    
                    setTimeout(() => {
                        loadFeedbacks(currentPage);
                    }, 100);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.error || 'Error submitting response', 'error');
                    } else {
                        alert(result.error || 'Error submitting response');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error submitting reply:', error);
                if (typeof showToast === 'function') {
                    showToast('An error occurred while submitting the response', 'error');
                } else {
                    alert('An error occurred while submitting the response');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Close modal when clicking outside
    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplyModal();
            }
        });
    }

    // Handle block form submission
    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const feedbackId = this.dataset.feedbackId;
            const blockReasonInput = document.getElementById('blockReason');
            const reason = blockReasonInput ? blockReasonInput.value.trim() : '';

            if (!reason) {
                alert('Please enter a reason for blocking');
                return;
            }

            if (!confirm('Are you sure you want to block this feedback? This action cannot be easily undone.')) {
                return;
            }

            if (typeof API === 'undefined') {
                alert('API not loaded');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Blocking...';

            try {
                const result = await API.put(`/feedbacks/${feedbackId}/block`, { reason: reason });

                if (result.success) {
                    closeBlockModal();
                    loadFeedbacks(currentPage);
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Feedback blocked successfully!', 'success');
                    } else {
                        alert('Feedback blocked successfully!');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.error || 'Error blocking feedback', 'error');
                    } else {
                        alert(result.error || 'Error blocking feedback');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error blocking feedback:', error);
                if (typeof showToast === 'function') {
                    showToast('An error occurred while blocking the feedback', 'error');
                } else {
                    alert('An error occurred while blocking the feedback');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Close block modal when clicking outside
    const blockModal = document.getElementById('blockModal');
    if (blockModal) {
        blockModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBlockModal();
            }
        });
    }

    // Bind form submit event
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const bookingId = document.getElementById('feedbackBooking')?.value || null;
            let facilityId = null;
            
            // If booking is selected, get facility_id from the booking
            if (bookingId) {
                const selectedBooking = allBookings.find(booking => booking.id == bookingId);
                if (selectedBooking && selectedBooking.facility_id) {
                    facilityId = selectedBooking.facility_id;
                }
            }

            const data = {
                type: document.getElementById('feedbackType').value,
                subject: document.getElementById('feedbackSubject').value,
                message: document.getElementById('feedbackMessage').value,
                rating: parseInt(document.getElementById('feedbackRating').value),
                facility_id: facilityId,
                booking_id: bookingId,
                image: selectedImageBase64 || null
            };

            const result = await API.post('/feedbacks', data);

            if (result.success) {
                // If feedback is associated with a booking, add it to rated list
                if (data.booking_id) {
                    ratedBookingIds.push(parseInt(data.booking_id));
                    // Remove from allBookings if it exists
                    allBookings = allBookings.filter(booking => booking.id !== parseInt(data.booking_id));
                    // Update the booking dropdown
                    const select = document.getElementById('feedbackBooking');
                    if (select) {
                        const options = ['<option value="">None</option>'];
                        allBookings.forEach(booking => {
                            const facilityName = booking.facility?.name || 'Unknown Facility';
                            const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
                            const status = booking.status || 'unknown';
                            const label = `Booking #${booking.id} - ${facilityName} - ${bookingDate} (${status})`;
                            options.push(`<option value="${booking.id}">${label}</option>`);
                        });
                        select.innerHTML = options.join('');
                    }
                }
                
                // Reset image selection
                selectedImageBase64 = null;
                const imageInput = document.getElementById('feedbackImage');
                if (imageInput) {
                    imageInput.value = '';
                }
                const imagePreview = document.getElementById('imagePreview');
                if (imagePreview) {
                    imagePreview.style.display = 'none';
                }
                const previewImg = document.getElementById('previewImg');
                if (previewImg) {
                    previewImg.src = '';
                }
                
                closeModal();
                loadFeedbacks(1);
                if (typeof showToast === 'function') {
                    showToast('Feedback submitted successfully!', 'success');
                } else {
                    alert('Feedback submitted successfully!');
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.error || 'Error submitting feedback', 'error');
                } else {
                    alert(result.error || 'Error submitting feedback');
                }
            }
        });
    }
});


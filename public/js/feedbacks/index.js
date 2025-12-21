/**
 * Author: Boo Kai Jie
 */ 

let feedbacks = [];
let facilities = [];
let allBookings = [];
let ratedBookingIds = [];
let selectedImageBase64 = null;
let paginationData = null;
let currentPage = 1;

function initFeedbacks() {
    const titleElement = document.getElementById('feedbacksTitle');
    const submitBtn = document.getElementById('submitFeedbackBtn');

    if (API.isAdmin()) {
        if (titleElement) {
            titleElement.textContent = 'Feedback Management';
        }
        if (submitBtn) {
            submitBtn.style.display = 'none';
        }
    } else {
        if (titleElement) {
            titleElement.textContent = 'My Feedbacks';
        }
        if (submitBtn) {
            submitBtn.style.display = 'block';
        }
    }

    loadFeedbacks();
    loadBookings();
}

async function loadFeedbacks(page = 1) {
    showLoading(document.getElementById('feedbacksList'));
    currentPage = page;

    const params = new URLSearchParams();
    params.append('page', page);
    
    const search = document.getElementById('searchFilter')?.value.trim();
    const type = document.getElementById('typeFilter')?.value;
    const status = document.getElementById('statusFilter')?.value;
    
    if (search) params.append('search', search);
    if (type) params.append('type', type);
    if (status) params.append('status', status);

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
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return;
    }

    const select = document.getElementById('facilityTypeFilter');
    if (select && allBookings.length > 0) {
        const seenTypes = new Set();
        const typeOptions = [];

        allBookings.forEach(booking => {
            const facilityType = booking.facility?.type;
            if (facilityType) {
                const key = String(facilityType).toLowerCase();
                if (!seenTypes.has(key)) {
                    seenTypes.add(key);
                    
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
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return;
    }

    await loadRatedBookingIds();

    const result = await API.get('/bookings/user/my-bookings');

    if (result.success) {
        allBookings = result.data.data?.data || result.data.data || [];
        
        allBookings = allBookings.filter(booking => !ratedBookingIds.includes(booking.id));
        
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
                const facilityName = booking.facility?.name || 'Unknown Facility';
                const bookingDate = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString() : 'N/A';
                const status = booking.status || 'unknown';
                
                const label = `Booking #${booking.id} - ${facilityName} - ${bookingDate} (${status})`;
                options.push(`<option value="${booking.id}">${label}</option>`);
            });

            select.innerHTML = options.join('');
        }
        
        loadFacilityTypes();
    }
}

async function loadRatedBookingIds() {
    if (typeof API !== 'undefined' && API.isAdmin()) {
        return;
    }

    try {
        const result = await API.get('/feedbacks/user/my-feedbacks');
        if (result.success) {
            const userFeedbacks = result.data.data?.data || result.data.data || [];
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

function formatTypeName(type) {
    if (!type) return 'General';
    return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
}

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

                    const getStatusBadgeClass = (status) => {
                        if (status === 'resolved') return 'badge-success';
                        if (status === 'pending') return 'badge-warning';
                        if (status === 'under_review') return 'badge-info';
                        if (status === 'blocked' || status === 'rejected') return 'badge-danger';
                        return 'badge-secondary';
                    };

                    const getTypeBadgeClass = (type) => {
                        if (type === 'complaint') return 'badge-danger';
                        if (type === 'suggestion') return 'badge-info';
                        if (type === 'compliment') return 'badge-success';
                        return 'badge-secondary';
                    };

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
                        ${!isAdmin ? `<td>${feedback.facility_name ?? 'General'}</td>` : ''}
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

window.filterFeedbacks = function() {
    loadFeedbacks(1);
};

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

window.replyToFeedback = function(id) {
    const feedback = feedbacks.find(f => f.id == id);
    if (!feedback) {
        alert('Feedback not found');
        return;
    }

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

    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.reset();
        const replyMessage = document.getElementById('replyMessage');
        if (replyMessage) {
            replyMessage.value = '';
        }
        replyForm.dataset.feedbackId = id;
    }

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
    const replyMessage = document.getElementById('replyMessage');
    if (replyMessage) {
        replyMessage.value = '';
    }
};

window.blockFeedback = function(id) {
    const feedback = feedbacks.find(f => f.id == id);
    if (!feedback) {
        alert('Feedback not found');
        return;
    }

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

    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.reset();
        const blockReason = document.getElementById('blockReason');
        if (blockReason) {
            blockReason.value = '';
        }
        blockForm.dataset.feedbackId = id;
    }

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

window.handleImageUpload = function(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    if (!file.type.match('image.*')) {
        if (typeof showToast === 'function') {
            showToast('Please select a valid image file', 'error');
        } else {
            alert('Please select a valid image file');
        }
        event.target.value = '';
        return;
    }

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

document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initFeedbacks();

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

            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn) return;
            
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const result = await API.put(`/feedbacks/${feedbackId}/respond`, { response: response });

                if (result.success) {
                    closeReplyModal();
                    
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

    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplyModal();
            }
        });
    }

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

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Blocking...';

            try {
                const result = await API.put(`/feedbacks/${feedbackId}/block`, { reason: reason });

                if (result.success) {
                    closeBlockModal();
                    loadFeedbacks(currentPage);
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

    const blockModal = document.getElementById('blockModal');
    if (blockModal) {
        blockModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBlockModal();
            }
        });
    }

    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const bookingId = document.getElementById('feedbackBooking')?.value || null;
            let facilityId = null;
            
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
                if (data.booking_id) {
                    ratedBookingIds.push(parseInt(data.booking_id));
                    allBookings = allBookings.filter(booking => booking.id !== parseInt(data.booking_id));
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


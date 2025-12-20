let currentFeedback = null;
const feedbackId = window.feedbackId || null; // Should be set via data attribute in blade

// Helper function to switch port from 8001 to 8000
function switchToPort8000(url) {
    const currentPort = window.location.port;
    const hostname = window.location.hostname;
    const protocol = window.location.protocol;
    
    // Handle relative URLs starting with /
    if (url.startsWith('/')) {
        if (currentPort === '8001') {
            return `${protocol}//${hostname}:8000${url}`;
        }
        return url;
    }
    
    // Handle absolute URLs
    if (currentPort === '8001') {
        try {
            // Use the origin (protocol + hostname) instead of full current URL to avoid path contamination
            const baseUrl = `${protocol}//${hostname}:8000`;
            const urlObj = new URL(url, baseUrl);
            return urlObj.href;
        } catch (e) {
            // Fallback: simple replacement
            if (url.includes(':8001')) {
                return url.replace(':8001', ':8000');
            }
            return `${protocol}//${hostname}:8000${url}`;
        }
    }
    return url;
}

async function loadFeedbackDetails() {
    if (!feedbackId) {
        console.error('Feedback ID not found');
        const backUrl = switchToPort8000(API.isAdmin() ? '/admin/feedbacks' : '/feedbacks');
        document.getElementById('feedbackDetails').innerHTML = `
            <div class="error-message">
                <p>Error: Feedback ID not available</p>
                <a href="${backUrl}" class="btn-primary">Back to Feedbacks</a>
            </div>
        `;
        return;
    }

    const result = await API.get(`/feedbacks/${feedbackId}`);

    if (result.success) {
        currentFeedback = result.data.data;
        window.currentFeedback = currentFeedback;
        displayFeedbackDetails(currentFeedback);
    } else {
        const backUrl = switchToPort8000(API.isAdmin() ? '/admin/feedbacks' : '/feedbacks');
        document.getElementById('feedbackDetails').innerHTML = `
            <div class="error-message">
                <p>Error loading feedback details: ${result.error || 'Unknown error'}</p>
                <a href="${backUrl}" class="btn-primary">Back to Feedbacks</a>
            </div>
        `;
    }
}

function displayFeedbackDetails(feedback) {
    const container = document.getElementById('feedbackDetails');
    
    container.innerHTML = `
        <div class="details-card">
            <div class="details-section">
                <h2>Feedback Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">${feedback.subject || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="status-badge status-${feedback.type}">${feedback.type || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${feedback.status}">${formatStatus(feedback.status)}</span>
                </div>
                ${feedback.rating ? `
                <div class="detail-row">
                    <span class="detail-label">Rating:</span>
                    <span class="detail-value">${'★'.repeat(feedback.rating)} ${feedback.rating}/5</span>
                </div>
                ` : ''}
                <div class="detail-row">
                    <span class="detail-label">Submitted:</span>
                    <span class="detail-value">${formatDateTime(feedback.created_at)}</span>
                </div>
            </div>

            <div class="details-section">
                <h2>Message</h2>
                <div class="message-content">
                    ${feedback.message || 'No message'}
                </div>
                ${feedback.image ? `
                <div class="feedback-image-container" style="margin-top: 15px;">
                    <h3 style="font-size: 1rem; margin-bottom: 10px; color: #555;">Attached Image:</h3>
                    <div class="feedback-image-wrapper" style="position: relative; display: inline-block; max-width: 100%;">
                        <img src="${feedback.image}" alt="Feedback Image" class="feedback-image" style="max-width: 100%; max-height: 500px; border-radius: 8px; border: 2px solid #ddd; cursor: pointer; transition: all 0.3s ease;" onclick="viewFeedbackImage('${feedback.image}')">
                        <div class="image-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); border-radius: 8px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.3s ease; cursor: pointer;" onclick="viewFeedbackImage('${feedback.image}')">
                            <i class="fas fa-search-plus" style="color: white; font-size: 2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"></i>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>

            ${feedback.facility_type ? `
            <div class="details-section">
                <h2>Related Facility</h2>
                <div class="detail-row">
                    <span class="detail-label">Facility Type:</span>
                    <span class="detail-value">${formatFacilityType(feedback.facility_type)}</span>
                </div>
            </div>
            ` : ''}

            ${feedback.booking_id ? `
            <div class="details-section">
                <h2>Related Booking</h2>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#${feedback.booking_id}</span>
                </div>
                <div class="detail-row" style="margin-top: 10px;">
                    <button class="btn-primary" onclick="loadBookingDetailsForFeedback(${feedback.id}, ${feedback.booking_id})" id="viewBookingBtn">
                        <i class="fas fa-calendar-check"></i> View Booking Details
                    </button>
                </div>
                <div id="bookingDetailsContainer" style="margin-top: 15px; display: none;">
                    <div class="booking-details-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                        <p style="color: #666; margin: 0;"><i class="fas fa-spinner fa-spin"></i> Loading booking details...</p>
                    </div>
                </div>
            </div>
            ` : ''}

            ${feedback.status === 'pending' ? `
            <div class="details-section">
                <h2>Status Notice</h2>
                <div class="pending-notice">
                    <strong><i class="fas fa-clock"></i> This feedback is pending review</strong>
                    <p>Waiting for admin to review this feedback.</p>
                </div>
            </div>
            ` : ''}
            ${feedback.status === 'under_review' ? `
            <div class="details-section">
                <h2>Status Notice</h2>
                <div class="under-review-notice">
                    <strong><i class="fas fa-eye"></i> This feedback is under review</strong>
                    <p>Admin is currently reviewing this feedback.</p>
                    ${feedback.reviewer && feedback.reviewer.name ? `
                    <p style="margin-top: 8px; font-size: 0.9rem;">
                        <i class="fas fa-user"></i> Reviewed by: ${feedback.reviewer.name}
                    </p>
                    ` : feedback.reviewed_by ? `
                    <p style="margin-top: 8px; font-size: 0.9rem;">
                        <i class="fas fa-user"></i> Reviewed by: Admin
                    </p>
                    ` : ''}
                    ${feedback.reviewed_at ? `
                    <p style="margin-top: 5px; font-size: 0.9rem;">
                        <i class="fas fa-calendar"></i> Review started: ${formatDateTime(feedback.reviewed_at)}
                    </p>
                    ` : ''}
                </div>
            </div>
            ` : ''}
            ${feedback.status === 'blocked' || feedback.is_blocked ? `
            <div class="details-section">
                <h2>Status Notice</h2>
                <div class="blocked-notice">
                    <strong><i class="fas fa-ban"></i> This feedback has been blocked</strong>
                    <p>This feedback has been blocked and is not visible to other users.</p>
                    ${feedback.block_reason ? `
                    <p style="margin-top: 8px; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> <strong>Reason:</strong> ${feedback.block_reason}
                    </p>
                    ` : ''}
                    ${feedback.reviewed_at ? `
                    <p style="margin-top: 5px; font-size: 0.9rem;">
                        <i class="fas fa-calendar"></i> Blocked at: ${formatDateTime(feedback.reviewed_at)}
                    </p>
                    ` : ''}
                </div>
            </div>
            ` : ''}
            ${feedback.admin_response ? `
            <div class="details-section">
                <h2>Admin Response</h2>
                <div class="message-content admin-response">
                    ${feedback.admin_response}
                </div>
                ${feedback.reviewed_at ? `
                <div class="detail-row" style="margin-top: 15px;">
                    <span class="detail-label">Reviewed At:</span>
                    <span class="detail-value">${formatDateTime(feedback.reviewed_at)}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            ${typeof API !== 'undefined' && API.isAdmin() ? `
            <div class="details-section">
                <div class="feedback-action-buttons">
                    ${!feedback.admin_response && !feedback.is_blocked ? `
                        <button class="btn-action btn-success" onclick="replyToFeedback(${feedback.id})">
                            <i class="fas fa-reply"></i> Reply to Feedback
                        </button>
                    ` : ''}
                    ${!feedback.is_blocked ? `
                        <button class="btn-action btn-danger" onclick="blockFeedback(${feedback.id})">
                            <i class="fas fa-ban"></i> Block Feedback
                        </button>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
}

function formatTimeOnly(timeString) {
    if (!timeString) return 'N/A';
    // Handle both "YYYY-MM-DD HH:MM:SS" and "HH:MM:SS" formats
    const timePart = timeString.includes(' ') ? timeString.split(' ')[1] : 
                     timeString.includes('T') ? timeString.split('T')[1] : timeString;
    const time = timePart.split(':');
    if (time.length >= 2) {
        let hours = parseInt(time[0]);
        const minutes = time[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }
    return timePart;
}

function formatStatus(status) {
    if (!status) return 'N/A';
    const statusMap = {
        'pending': 'Pending',
        'under_review': 'Under Review',
        'resolved': 'Resolved',
        'rejected': 'Rejected',
        'blocked': 'Blocked'
    };
    return statusMap[status] || status;
}

// Format facility type name (capitalize first letter)
function formatFacilityType(type) {
    if (!type) return 'N/A';
    return type.charAt(0).toUpperCase() + type.slice(1).toLowerCase();
}

// Admin function to reply to feedback
window.replyToFeedback = function(id) {
    // Display feedback info in modal
    const infoDiv = document.getElementById('replyFeedbackInfo');
    const feedback = currentFeedback || {};
    
    if (infoDiv) {
        infoDiv.innerHTML = `
            <div class="reply-feedback-preview">
                <div class="preview-header">
                    <strong>Subject:</strong> ${feedback.subject || 'N/A'}
                </div>
                <div class="preview-type">
                    <span class="status-badge status-${feedback.type}">${feedback.type || 'N/A'}</span>
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
        replyForm.dataset.feedbackId = feedbackId;
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
    }
};

// Admin function to block feedback
window.blockFeedback = function(id) {
    const feedback = currentFeedback || {};
    
    // Display feedback info in modal
    const infoDiv = document.getElementById('blockFeedbackInfo');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <div class="reply-feedback-preview">
                <div class="preview-header">
                    <strong>Subject:</strong> ${feedback.subject || 'N/A'}
                </div>
                <div class="preview-type">
                    <span class="status-badge status-${feedback.type}">${feedback.type || 'N/A'}</span>
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
        blockForm.dataset.feedbackId = feedbackId;
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

// View feedback image in modal
window.viewFeedbackImage = function(imageSrc) {
    const modal = document.getElementById('imageViewModal');
    const modalImg = document.getElementById('imageViewImg');
    if (modal && modalImg) {
        modalImg.src = imageSrc;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

// Close image view modal
window.closeImageViewModal = function() {
    const modal = document.getElementById('imageViewModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

// Load booking details for feedback using Web Service
window.loadBookingDetailsForFeedback = async function(feedbackId, bookingId) {
    const container = document.getElementById('bookingDetailsContainer');
    const viewBtn = document.getElementById('viewBookingBtn');
    
    if (!container) return;
    
    // Show container
    container.style.display = 'block';
    
    // Disable button while loading
    if (viewBtn) {
        viewBtn.disabled = true;
        viewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    }
    
    try {
        // Call the new API endpoint that uses Booking Web Service
        const timestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');
        const result = await API.get(`/feedbacks/${feedbackId}/booking-details?timestamp=${encodeURIComponent(timestamp)}`);
        
        if (result.success && result.data) {
            // Response format from API.get():
            // result.data = { status: 'S', data: { feedback: {...}, booking: {...} }, timestamp: '...' }
            // So booking is at: result.data.data.booking
            const booking = result.data.data?.booking;
            
            if (booking) {
                
                container.innerHTML = `
                <div class="booking-details-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                    <h3 style="margin-top: 0; margin-bottom: 15px; color: #333;">
                        <i class="fas fa-calendar-check"></i> Booking Information
                    </h3>
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Booking ID:</span>
                        <span class="detail-value">#${booking.id}</span>
                    </div>
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Status:</span>
                        <span class="status-badge status-${booking.status}">${booking.status || 'N/A'}</span>
                    </div>
                    ${booking.facility_name ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Facility:</span>
                        <span class="detail-value">${booking.facility_name} ${booking.facility_code ? '(' + booking.facility_code + ')' : ''}</span>
                    </div>
                    ` : ''}
                    ${booking.booking_date ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Date:</span>
                        <span class="detail-value">${formatDateTime(booking.booking_date)}</span>
                    </div>
                    ` : ''}
                    ${booking.slots && Array.isArray(booking.slots) && booking.slots.length > 0 ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Time:</span>
                        <span class="detail-value">
                            ${booking.slots.map(slot => {
                                const startTime = formatTimeOnly(slot.start_time);
                                const endTime = formatTimeOnly(slot.end_time);
                                return `${startTime} - ${endTime}`;
                            }).join('<br>')}
                        </span>
                    </div>
                    ` : ''}
                    ${booking.duration_hours ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Duration:</span>
                        <span class="detail-value">${booking.duration_hours} hours</span>
                    </div>
                    ` : ''}
                    ${booking.purpose ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Purpose:</span>
                        <span class="detail-value">${booking.purpose}</span>
                    </div>
                    ` : ''}
                    ${booking.user_name ? `
                    <div class="detail-row" style="margin-bottom: 10px;">
                        <span class="detail-label" style="font-weight: 600;">Booked By:</span>
                        <span class="detail-value">${booking.user_name}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row" style="margin-top: 15px;">
                        <a href="${switchToPort8000(`/bookings/${booking.id}`)}" class="btn-primary" style="display: inline-block; text-decoration: none;">
                            <i class="fas fa-external-link-alt"></i> View Full Booking Details
                        </a>
                    </div>
                </div>
                `;
            } else {
                // Handle error response
                const errorMessage = result.error || result.data?.message || 'Failed to load booking details';
                console.error('API Error:', result);
                container.innerHTML = `
                    <div class="booking-details-card" style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffc107;">
                        <p style="color: #856404; margin: 0;">
                            <i class="fas fa-exclamation-triangle"></i> ${errorMessage}
                        </p>
                        ${result.data?.status === 'F' && result.data?.message === 'This feedback is not related to a booking' ? `
                        <p style="color: #856404; margin-top: 10px; font-size: 0.9rem;">
                            This feedback does not have an associated booking.
                        </p>
                        ` : ''}
                    </div>
                `;
            }
        } else {
            // API call failed
            const errorMessage = result.error || 'Failed to load booking details';
            console.error('API call failed:', result);
            container.innerHTML = `
                <div class="booking-details-card" style="background: #f8d7da; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">
                    <p style="color: #721c24; margin: 0;">
                        <i class="fas fa-exclamation-circle"></i> ${errorMessage}
                    </p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        container.innerHTML = `
            <div class="booking-details-card" style="background: #f8d7da; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">
                <p style="color: #721c24; margin: 0;">
                    <i class="fas fa-exclamation-circle"></i> Error loading booking details: ${error.message || 'Unknown error'}
                </p>
                <p style="color: #721c24; margin-top: 10px; font-size: 0.9rem;">
                    Please check the browser console for more details.
                </p>
            </div>
        `;
    } finally {
        // Re-enable button
        if (viewBtn) {
            viewBtn.disabled = false;
            viewBtn.innerHTML = '<i class="fas fa-calendar-check"></i> View Booking Details';
        }
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

    loadFeedbackDetails();
    
    // Handle reply form submission
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
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
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            try {
                const result = await API.put(`/feedbacks/${feedbackId}/respond`, { response: response });
                
                if (result.success) {
                    closeReplyModal();
                    // Reload feedback details to show the response
                    loadFeedbackDetails();
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Response submitted successfully!', 'success');
                    } else {
                        alert('Response submitted successfully!');
                    }
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
                    // Reload feedback details to show the blocked status
                    loadFeedbackDetails();
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

    // Close image view modal when clicking outside
    const imageModal = document.getElementById('imageViewModal');
    if (imageModal) {
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageViewModal();
            }
        });
    }
});


@extends('layouts.app')

@section('title', 'Feedback Details - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Feedback Details</h1>
        <a href="{{ route('feedbacks.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Feedbacks
        </a>
    </div>

    <div id="feedbackDetails" class="details-container">
        <p>Loading feedback details...</p>
    </div>
</div>

<!-- Reply Feedback Modal -->
<div id="replyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeReplyModal()">&times;</span>
        <h2>Reply to Feedback</h2>
        <div id="replyFeedbackInfo" class="reply-feedback-info"></div>
        <form id="replyForm">
            <div class="form-group">
                <label>Your Response *</label>
                <textarea id="replyMessage" required rows="6" placeholder="Enter your response to this feedback..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeReplyModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    Send Response
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Block Feedback Modal -->
<div id="blockModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeBlockModal()">&times;</span>
        <h2>Block Feedback</h2>
        <div id="blockFeedbackInfo" class="reply-feedback-info"></div>
        <form id="blockForm">
            <div class="form-group">
                <label>Reason for Blocking *</label>
                <textarea id="blockReason" required rows="4" placeholder="Enter the reason for blocking this feedback (e.g., spam, inappropriate content, false information...)"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeBlockModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    Block Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Image View Modal -->
<div id="imageViewModal" class="image-modal" style="display: none;">
    <span class="image-modal-close" onclick="closeImageViewModal()">&times;</span>
    <div class="image-modal-content">
        <img id="imageViewImg" src="" alt="Feedback Image">
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

    loadFeedbackDetails();
    
    // Handle reply form submission
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const feedbackId = this.dataset.feedbackId;
            const response = document.getElementById('replyMessage').value.trim();
            
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
            
            const feedbackId = this.dataset.feedbackId;
            const reason = document.getElementById('blockReason').value.trim();
            
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
});

let currentFeedback = null;

async function loadFeedbackDetails() {
    const feedbackId = {{ $id }};
    const result = await API.get(`/feedbacks/${feedbackId}`);

    if (result.success) {
        currentFeedback = result.data.data;
        window.currentFeedback = currentFeedback;
        displayFeedbackDetails(currentFeedback);
    } else {
        document.getElementById('feedbackDetails').innerHTML = `
            <div class="error-message">
                <p>Error loading feedback details: ${result.error || 'Unknown error'}</p>
                <a href="{{ route('feedbacks.index') }}" class="btn-primary">Back to Feedbacks</a>
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

            ${feedback.facility ? `
            <div class="details-section">
                <h2>Related Facility</h2>
                <div class="detail-row">
                    <span class="detail-label">Facility:</span>
                    <span class="detail-value">${feedback.facility.name || 'N/A'}</span>
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

// Admin function to reply to feedback
window.replyToFeedback = function(id) {
    // Get feedback data from the page
    const feedbackId = {{ $id }};
    
    // Display feedback info in modal
    const infoDiv = document.getElementById('replyFeedbackInfo');
    const feedback = currentFeedback || {};
    
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
    
    // Reset form
    document.getElementById('replyForm').reset();
    document.getElementById('replyMessage').value = '';
    
    // Store feedback ID for form submission
    document.getElementById('replyForm').dataset.feedbackId = feedbackId;
    
    // Show modal
    document.getElementById('replyModal').style.display = 'block';
};

window.closeReplyModal = function() {
    document.getElementById('replyModal').style.display = 'none';
    document.getElementById('replyForm').reset();
    delete document.getElementById('replyForm').dataset.feedbackId;
};

// Admin function to block feedback
window.blockFeedback = function(id) {
    // Get feedback data from the page
    const feedbackId = {{ $id }};
    const feedback = currentFeedback || {};
    
    // Display feedback info in modal
    const infoDiv = document.getElementById('blockFeedbackInfo');
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
    
    // Reset form
    document.getElementById('blockForm').reset();
    document.getElementById('blockReason').value = '';
    
    // Store feedback ID for form submission
    document.getElementById('blockForm').dataset.feedbackId = feedbackId;
    
    // Show modal
    document.getElementById('blockModal').style.display = 'block';
};

window.closeBlockModal = function() {
    document.getElementById('blockModal').style.display = 'none';
    document.getElementById('blockForm').reset();
    delete document.getElementById('blockForm').dataset.feedbackId;
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

// Close modal when clicking outside the image
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageViewModal');
    if (imageModal) {
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageViewModal();
            }
        });
    }
});
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

.message-content {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    line-height: 1.6;
    color: #333;
}

.message-content.admin-response {
    background: #e7f3ff;
    border-left: 4px solid #2196F3;
}

.error-message {
    text-align: center;
    padding: 40px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
}

/* Reply Modal Styles */
.reply-feedback-info {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #2196F3;
}

.reply-feedback-preview {
    font-size: 0.9rem;
}

.reply-feedback-preview .preview-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.reply-feedback-preview .preview-type {
    margin-bottom: 10px;
}

.reply-feedback-preview .preview-message {
    margin-top: 10px;
}

.reply-feedback-preview .preview-message strong {
    display: block;
    margin-bottom: 5px;
    color: #555;
}

.reply-feedback-preview .preview-message p {
    margin: 0;
    padding: 10px;
    background: white;
    border-radius: 4px;
    color: #333;
    line-height: 1.5;
}

#replyMessage {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    transition: border-color 0.3s;
}

#replyMessage:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.form-group textarea {
    min-height: 120px;
}

.pending-notice {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    color: #856404;
}

.pending-notice strong {
    display: block;
    margin-bottom: 8px;
    font-size: 1.1rem;
    color: #856404;
}

.pending-notice p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: #856404;
}

.under-review-notice {
    background: #d1ecf1;
    border: 2px solid #0dcaf0;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    color: #055160;
}

.under-review-notice strong {
    display: block;
    margin-bottom: 8px;
    font-size: 1.1rem;
    color: #055160;
}

.under-review-notice p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: #055160;
}

.blocked-notice {
    background: #dc3545;
    border: 2px solid #c82333;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    color: white;
}

.blocked-notice strong {
    display: block;
    margin-bottom: 8px;
    font-size: 1.1rem;
    color: white;
}

.blocked-notice p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.btn-danger:hover {
    background: #c82333;
}

/* Modal Styles (if not already defined) */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    overflow: auto;
}

.modal-content {
    background: #ffffff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #636e72;
    cursor: pointer;
}

.close:hover {
    color: #2d3436;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

/* Feedback Action Buttons */
.feedback-action-buttons {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 160px;
    justify-content: center;
}

.btn-action.btn-success {
    background: #10b981;
    color: white;
}

.btn-action.btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-action.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-action.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-action:active {
    transform: translateY(0);
}

/* Image View Modal Styles */
.image-modal {
    display: none;
    position: fixed;
    z-index: 3000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.image-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-modal-content img {
    max-width: 100%;
    max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    animation: zoomIn 0.3s ease;
}

@keyframes zoomIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.image-modal-close {
    position: absolute;
    top: 20px;
    right: 40px;
    color: #ffffff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 3001;
    transition: all 0.3s ease;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
}

.image-modal-close:hover {
    background: rgba(0, 0, 0, 0.8);
}

/* Feedback Image Wrapper Hover Effect */
.feedback-image-wrapper:hover .image-overlay {
    opacity: 1;
    background: rgba(0, 0, 0, 0.5);
}

.feedback-image-wrapper:hover .feedback-image {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
</style>
@endsection


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
                    alert('Response submitted successfully!');
                } else {
                    alert(result.error || 'Error submitting response');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error submitting reply:', error);
                alert('An error occurred while submitting the response');
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
                    alert('Feedback blocked successfully!');
                } else {
                    alert(result.error || 'Error blocking feedback');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error blocking feedback:', error);
                alert('An error occurred while blocking the feedback');
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
        window.currentFeedback = currentFeedback; // Make it globally available
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
                    <span class="status-badge status-${feedback.status}">${feedback.status || 'N/A'}</span>
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

            ${feedback.is_blocked ? `
            <div class="details-section">
                <h2>Blocked Notice</h2>
                <div class="blocked-notice">
                    <strong>This feedback has been blocked</strong>
                    ${feedback.block_reason ? `<p>Reason: ${feedback.block_reason}</p>` : ''}
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
                ${!feedback.admin_response && !feedback.is_blocked ? `
                    <button class="btn-primary" onclick="replyToFeedback(${feedback.id})">
                        Reply to Feedback
                    </button>
                ` : ''}
                ${!feedback.is_blocked ? `
                    <button class="btn-primary" onclick="blockFeedback(${feedback.id})" style="margin-left: 10px;">
                        Block Feedback
                    </button>
                ` : ''}
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
</style>
@endsection


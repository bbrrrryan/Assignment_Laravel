@extends('layouts.app')

@section('title', 'Feedback - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 id="feedbacksTitle">Feedback Management</h1>
        <button id="submitFeedbackBtn" class="btn-primary" onclick="showCreateModal()" style="display: none;">
            <i class="fas fa-plus"></i> Submit Feedback
        </button>
    </div>

    <div class="filters">
        <select id="typeFilter" onchange="filterFeedbacks()">
            <option value="">All Types</option>
            <option value="complaint">Complaint</option>
            <option value="suggestion">Suggestion</option>
            <option value="compliment">Compliment</option>
            <option value="general">General</option>
        </select>
        <select id="statusFilter" onchange="filterFeedbacks()">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="resolved">Resolved</option>
            <option value="rejected">Rejected</option>
            <option value="blocked">Blocked</option>
        </select>
    </div>

    <div id="feedbacksList" class="feedbacks-container">
        <p>Loading feedbacks...</p>
    </div>
</div>

<!-- Create Feedback Modal -->
<div id="feedbackModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Submit Feedback</h2>
        <form id="feedbackForm">
            <div class="form-group">
                <label>Type *</label>
                <select id="feedbackType" required>
                    <option value="complaint">Complaint</option>
                    <option value="suggestion">Suggestion</option>
                    <option value="compliment">Compliment</option>
                    <option value="general">General</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subject *</label>
                <input type="text" id="feedbackSubject" required>
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea id="feedbackMessage" required rows="5" placeholder="Describe your feedback, complaint, or suggestion..."></textarea>
            </div>
            <div class="form-group">
                <label>Related Facility (Optional)</label>
                <select id="feedbackFacility">
                    <option value="">None</option>
                </select>
                <small>Select a facility if this feedback is related to a specific facility</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Submit</button>
            </div>
        </form>
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
                <button type="submit" class="btn-success">
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
                <button type="submit" class="btn-danger">
                    Block Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initFeedbacks();
});

let feedbacks = [];
let facilities = [];

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
    loadFacilities();
}

async function loadFeedbacks() {
    showLoading(document.getElementById('feedbacksList'));

    // Use appropriate endpoint based on user role
    const endpoint = API.isAdmin() ? '/feedbacks' : '/feedbacks/user/my-feedbacks';
    const result = await API.get(endpoint);

    if (result.success) {
        feedbacks = result.data.data?.data || result.data.data || [];
        displayFeedbacks(feedbacks);
    } else {
        showError(document.getElementById('feedbacksList'), result.error || 'Failed to load feedbacks');
        console.error('Error loading feedbacks:', result);
    }
}

async function loadFacilities() {
    const result = await API.get('/facilities');

    if (result.success) {
        facilities = result.data.data?.data || result.data.data || [];
        const select = document.getElementById('feedbackFacility');
        select.innerHTML = '<option value="">None</option>' +
            facilities.map(f => `<option value="${f.id}">${f.name}</option>`).join('');
    }
}

function displayFeedbacks(feedbacksToShow) {
    const container = document.getElementById('feedbacksList');
    if (feedbacksToShow.length === 0) {
        container.innerHTML = '<p>No feedbacks found</p>';
        return;
    }

    const isAdmin = typeof API !== 'undefined' && API.isAdmin();

    // Sort by newest date first
    const sortedFeedbacks = [...feedbacksToShow].sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB - dateA;
    });

    container.innerHTML = sortedFeedbacks.map(feedback => {
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

        return `
        <div class="feedback-card">
            <div class="feedback-header">
                <div class="feedback-title-section">
                    <h3>${feedback.subject || 'No Subject'}</h3>
                    <div class="feedback-meta-badges">
                        <span class="feedback-type type-${feedback.type}">${feedback.type || 'general'}</span>
                        <span class="status-badge status-${feedback.status}">${formatStatus(feedback.status)}</span>
                    </div>
                </div>
            </div>
            <div class="feedback-content">
                <p class="${isAdmin ? 'feedback-message-admin' : 'feedback-message-user'}">${feedback.message || 'No message'}</p>
                ${!isAdmin && feedback.facility ? `
                    <div class="feedback-facility"><i class="fas fa-building"></i> ${feedback.facility.name}</div>
                ` : ''}
            </div>
${feedback.status === 'pending' ? `
<div class="status-notice pending-notice status-notice-fixed ${isAdmin ? 'status-notice-admin' : ''}">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Pending Review</strong>
                    <p>Waiting for admin to review this feedback.</p>
                </div>
            </div>
            ` : ''}
            ${feedback.status === 'under_review' ? `
            <div class="status-notice under-review-notice ${isAdmin ? 'status-notice-admin' : ''}">
                <i class="fas fa-eye"></i>
                <div>
                    <strong>Under Review</strong>
                    <p>Admin is currently reviewing this feedback.</p>
                </div>
            </div>
            ` : ''}
            ${feedback.status === 'blocked' || feedback.is_blocked ? `
            <div class="status-notice blocked-notice ${isAdmin ? 'status-notice-admin' : ''}">
                <i class="fas fa-ban"></i>
                <div>
                    <strong>Blocked</strong>
                    <p>This feedback has been blocked and is not visible to other users.</p>
                    ${feedback.block_reason ? `<p class="block-reason"><strong>Reason:</strong> ${feedback.block_reason}</p>` : ''}
                </div>
            </div>
            ` : ''}
            ${feedback.status === 'rejected' ? `
            <div class="status-notice rejected-notice ${isAdmin ? 'status-notice-admin' : ''}">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>Rejected</strong>
                    <p>This feedback has been rejected by admin.</p>
                </div>
            </div>
            ` : ''}
            ${isAdmin && feedback.status === 'resolved' ? `
            <div class="status-notice resolved-notice status-notice-admin">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Resolved</strong>
                    <p>This feedback has been resolved by admin.</p>
                    ${feedback.admin_response ? `<p class="resolved-response" style="margin-top: 8px; font-size: 0.85rem;"><strong>Response:</strong> ${feedback.admin_response}</p>` : ''}
                </div>
            </div>
            ` : ''}
            ${!isAdmin && feedback.admin_response ? `
            <div class="admin-response">
                <div class="admin-response-header">
                    <i class="fas fa-user-shield"></i>
                    <strong>Admin Response</strong>
                </div>
                <p>${feedback.admin_response}</p>
            </div>
            ` : ''}
            <div class="feedback-footer">
                <div class="feedback-meta">
                    <span class="feedback-user"><i class="fas fa-user"></i> ${feedback.user ? feedback.user.name : 'Unknown User'}</span>
                    <span class="feedback-time"><i class="fas fa-clock"></i> ${formatDateTime(feedback.created_at)}</span>
                </div>
                <div class="feedback-actions">
                    <button class="btn-sm btn-primary" onclick="viewFeedback(${feedback.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                </div>
            </div>
        </div>
    `;
    }).join('');
}

// Make functions global
window.filterFeedbacks = function() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = feedbacks.filter(f => {
        const matchType = !type || f.type === type;
        const matchStatus = !status || f.status === status;
        return matchType && matchStatus;
    });

    displayFeedbacks(filtered);
};

window.showCreateModal = function() {
    loadFacilities();
    document.getElementById('feedbackForm').reset();
    document.getElementById('feedbackModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('feedbackModal').style.display = 'none';
};

window.viewFeedback = function(id) {
    window.location.href = `/feedbacks/${id}`;
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
    infoDiv.innerHTML = `
        <div class="reply-feedback-preview">
            <div class="preview-header">
                <strong>Subject:</strong> ${feedback.subject || 'N/A'}
            </div>
            <div class="preview-type">
                <span class="status-badge status-${feedback.type}">${feedback.type}</span>
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
    document.getElementById('replyForm').dataset.feedbackId = id;

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
    // Find the feedback data
    const feedback = feedbacks.find(f => f.id == id);
    if (!feedback) {
        alert('Feedback not found');
        return;
    }

    // Display feedback info in modal
    const infoDiv = document.getElementById('blockFeedbackInfo');
    infoDiv.innerHTML = `
        <div class="reply-feedback-preview">
            <div class="preview-header">
                <strong>Subject:</strong> ${feedback.subject || 'N/A'}
            </div>
            <div class="preview-type">
                <span class="status-badge status-${feedback.type}">${feedback.type}</span>
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
    document.getElementById('blockForm').dataset.feedbackId = id;

    // Show modal
    document.getElementById('blockModal').style.display = 'block';
};

window.closeBlockModal = function() {
    document.getElementById('blockModal').style.display = 'none';
    document.getElementById('blockForm').reset();
    delete document.getElementById('blockForm').dataset.feedbackId;
};

// Handle reply form submission
document.addEventListener('DOMContentLoaded', function() {
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
                    if (typeof loadFeedbacks === 'function') {
                        loadFeedbacks();
                    }
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Response submitted successfully!', 'success');
                    } else {
                        alert('Response submitted successfully!');
                    }
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
                    if (typeof loadFeedbacks === 'function') {
                        loadFeedbacks();
                    }
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('Feedback blocked successfully!', 'success');
                    } else {
                        alert('Feedback blocked successfully!');
                    }
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

// Bind form submit event
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', async function(e) {
            e.preventDefault();

    const data = {
        type: document.getElementById('feedbackType').value,
        subject: document.getElementById('feedbackSubject').value,
        message: document.getElementById('feedbackMessage').value,
        facility_id: document.getElementById('feedbackFacility').value || null
    };

            const result = await API.post('/feedbacks', data);

            if (result.success) {
                window.closeModal();
                loadFeedbacks();
                alert('Feedback submitted successfully!');
            } else {
                alert(result.error || 'Error submitting feedback');
            }
        });
    }
});
</script>

<style>
/* Reply Modal Styles */
.reply-feedback-info {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #2196F3;
    width: 100%;
    box-sizing: border-box;
}

.reply-feedback-preview {
    font-size: 0.9rem;
    width: 100%;
}

.reply-feedback-preview .preview-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
    width: 100%;
    word-wrap: break-word;
}

.reply-feedback-preview .preview-type {
    margin-bottom: 10px;
    width: fit-content;
}

.reply-feedback-preview .preview-message {
    margin-top: 10px;
    width: 100%;
    box-sizing: border-box;
}

.reply-feedback-preview .preview-message strong {
    display: block;
    margin-bottom: 5px;
    color: #555;
    width: 100%;
}

.reply-feedback-preview .preview-message p {
    margin: 0;
    padding: 10px;
    background: white;
    border-radius: 4px;
    color: #333;
    line-height: 1.5;
    width: 100%;
    box-sizing: border-box;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
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
    box-sizing: border-box;
    max-width: 100%;
}

#replyMessage:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
}

.form-group textarea {
    min-height: 120px;
    max-width: 100%;
    box-sizing: border-box;
}

/* Status Notice Styles */
.status-notice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 14px;
    font-size: 0.85rem;
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
}

.status-notice i {
    font-size: 1rem;
    margin-top: 2px;
    flex-shrink: 0;
}

.status-notice strong {
    display: block;
    margin-bottom: 3px;
    font-size: 0.88rem;
    font-weight: 600;
    width: 100%;
    word-wrap: break-word;
}

.status-notice p {
    margin: 0;
    font-size: 0.82rem;
    line-height: 1.4;
    word-wrap: break-word;
    overflow-wrap: break-word;
    width: 100%;
    max-width: 100%;
}

.status-notice > div {
    flex: 1;
    min-width: 0;
    width: calc(100% - 26px);
}

/* Fixed message info box */
.message-info-fixed {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.pending-notice {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
    color: #92400e;
}

.pending-notice i {
    color: #f59e0b;
}

.under-review-notice {
    background: #dbeafe;
    border-left: 3px solid #3b82f6;
    color: #1e40af;
}

.under-review-notice i {
    color: #3b82f6;
}

.resolved-notice {
    background: #d1fae5;
    border-left: 3px solid #10b981;
    color: #065f46;
}

.resolved-notice i {
    color: #10b981;
}

.resolved-response {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid rgba(6, 95, 70, 0.2);
    font-size: 0.82rem;
    width: 100%;
    word-wrap: break-word;
}

.rejected-notice {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
    color: #991b1b;
}

.rejected-notice i {
    color: #ef4444;
}

.blocked-notice {
    background: #fee2e2;
    border-left: 3px solid #ef4444;
    color: #991b1b;
}

.blocked-notice i {
    color: #ef4444;
}

.block-reason {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid rgba(153, 27, 27, 0.2);
    font-size: 0.78rem;
    width: 100%;
    word-wrap: break-word;
}

.admin-response {
    background: #f0f9ff;
    border-left: 3px solid #0ea5e9;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
    width: 100%;
    box-sizing: border-box;
    max-width: 100%;
}

.admin-response-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    color: #0c4a6e;
    font-weight: 600;
    font-size: 0.88rem;
    width: 100%;
}

.admin-response-header i {
    color: #0ea5e9;
}

.admin-response p {
    margin: 0;
    color: #075985;
    font-size: 0.85rem;
    line-height: 1.5;
    width: 100%;
    word-wrap: break-word;
}

/* Feedback Card Styles - Fixed width constraints */
.feedback-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
    max-width: 100%;
}

.feedback-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    border-color: #d1d5db;
}

.feedback-header {
    margin-bottom: 16px;
    width: 100%;
}

.feedback-title-section h3 {
    margin: 0 0 10px 0;
    color: #111827;
    font-size: 1.15rem;
    font-weight: 600;
    line-height: 1.4;
    height: 3em; /* Fixed height for 2 lines */
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-wrap: break-word;
    width: 100%;
    max-width: 100%;
}

.feedback-meta-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    width: 100%;
}

.feedback-content {
    margin-bottom: 16px;
    width: 100%;
    box-sizing: border-box;
}

/* Fixed message boxes for both user and admin */
.feedback-message-user,
.feedback-message-admin {
    color: #374151 !important;
    line-height: 1.6;
    margin: 0 0 12px 0;
    padding: 12px 14px;
    background: #f9fafb;
    border-radius: 6px;
    font-size: 0.95rem;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    display: block !important;
    text-overflow: ellipsis;
    white-space: normal !important;
    overflow: hidden !important;
}

/* User side: fixed height message box */
.feedback-message-user {
    height: 80px !important;
    max-height: 80px !important;
    min-height: 80px !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 3 !important;
    -webkit-box-orient: vertical !important;
}

/* Admin side: auto height but fixed width */
.feedback-message-admin {
    min-height: 60px;
    max-height: none;
    height: auto;
}

.feedback-facility {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 8px;
    width: fit-content;
}

.feedback-facility i {
    color: #6366f1;
}

.feedback-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 12px;
    width: 100%;
}

.feedback-meta {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    width: fit-content;
    max-width: 100%;
}

.feedback-user,
.feedback-time {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.feedback-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    width: fit-content;
}

/* Status Badge Styles - Fixed width */
.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
    letter-spacing: 0.3px;
    width: fit-content;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-badge.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.status-under_review {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.status-resolved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.status-blocked {
    background: #fee2e2;
    color: #991b1b;
}

/* Feedback Type Badge - Fixed width */
.feedback-type {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
    letter-spacing: 0.3px;
    width: fit-content;
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.type-complaint {
    background: #fee2e2;
    color: #991b1b;
}

.type-suggestion {
    background: #dbeafe;
    color: #1e40af;
}

.type-compliment {
    background: #d1fae5;
    color: #065f46;
}

.type-general {
    background: #f3f4f6;
    color: #4b5563;
}

/* Button Styles */
.btn-sm {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-sm.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-sm.btn-primary:hover {
    background: #2563eb;
}

.btn-sm.btn-success {
    background: #10b981;
    color: white;
}

.btn-sm.btn-success:hover {
    background: #059669;
}

.btn-sm.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-sm.btn-warning:hover {
    background: #d97706;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    overflow: auto;
    animation: fadeIn 0.2s;
}

.modal.show {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: #ffffff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-content h2 {
    margin: 0 0 20px 0;
    color: #111827;
    font-size: 1.5rem;
    font-weight: 600;
    padding-right: 30px;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #6b7280;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close:hover {
    color: #111827;
}

.close:focus {
    outline: none;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #6b7280;
    font-size: 0.85rem;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn-primary,
.btn-secondary,
.btn-success,
.btn-danger {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:active {
    transform: translateY(0);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-danger:active {
    transform: translateY(0);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.btn-secondary:active {
    transform: translateY(0);
}

/* Reply Feedback Info Styles */
.reply-feedback-info {
    margin-bottom: 20px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 3px solid #3b82f6;
}

.reply-feedback-preview {
    font-size: 0.9rem;
}

.reply-feedback-preview .preview-header {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
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
    margin-bottom: 6px;
    color: #374151;
    font-size: 0.9rem;
}

.reply-feedback-preview .preview-message p {
    margin: 0;
    padding: 10px;
    background: white;
    border-radius: 6px;
    color: #374151;
    line-height: 1.6;
    font-size: 0.9rem;
}
</style>
@endsection


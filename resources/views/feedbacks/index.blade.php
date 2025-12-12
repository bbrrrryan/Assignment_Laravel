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
                <textarea id="feedbackMessage" required rows="5"></textarea>
            </div>
            <div class="form-group">
                <label>Rating (1-5)</label>
                <input type="number" id="feedbackRating" min="1" max="5">
            </div>
            <div class="form-group">
                <label>Related Facility (Optional)</label>
                <select id="feedbackFacility">
                    <option value="">None</option>
                </select>
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

    container.innerHTML = feedbacksToShow.map(feedback => `
        <div class="feedback-card">
            <div class="feedback-header">
                <div>
                    <h3>${feedback.subject}</h3>
                    <span class="feedback-type type-${feedback.type}">${feedback.type}</span>
                </div>
                <span class="status-badge status-${feedback.status}">${feedback.status}</span>
            </div>
            <p class="feedback-message">${feedback.message}</p>
            ${feedback.rating ? `<div class="feedback-rating">Rating: ${'★'.repeat(feedback.rating)}${'☆'.repeat(5 - feedback.rating)}</div>` : ''}
            ${feedback.facility ? `<p class="feedback-facility"><i class="fas fa-building"></i> ${feedback.facility.name}</p>` : ''}
            ${feedback.is_blocked ? `
            <div class="blocked-notice">
                <strong>This feedback has been blocked</strong>
                ${feedback.block_reason ? `<p>Reason: ${feedback.block_reason}</p>` : ''}
            </div>
            ` : ''}
            ${feedback.admin_response ? `<div class="admin-response">
                <strong>Admin Response:</strong>
                <p>${feedback.admin_response}</p>
            </div>` : ''}
            <div class="feedback-footer">
                <span class="feedback-time">${formatDateTime(feedback.created_at)}</span>
                <div class="feedback-actions">
                    <button class="btn-sm" onclick="viewFeedback(${feedback.id})">View</button>
                    ${typeof API !== 'undefined' && API.isAdmin() ? `
                        ${!feedback.admin_response && !feedback.is_blocked ? `
                            <button class="btn-sm btn-success" onclick="replyToFeedback(${feedback.id})">Reply</button>
                        ` : ''}
                        ${!feedback.is_blocked ? `
                            <button class="btn-sm btn-success" onclick="blockFeedback(${feedback.id})">Block</button>
                        ` : ''}
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
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
        rating: parseInt(document.getElementById('feedbackRating').value) || null,
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
</style>
@endsection


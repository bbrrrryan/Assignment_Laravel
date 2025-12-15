@extends('layouts.app')

@section('title', 'Feedback - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 id="feedbacksTitle">Feedback Management</h1>
            <p id="feedbacksSubtitle">Submit and manage your feedback, complaints, and suggestions</p>
        </div>
        <div>
            <button id="submitFeedbackBtn" class="btn-header-white" onclick="showCreateModal()" style="display: none;">
                <i class="fas fa-plus"></i> <span>Submit Feedback</span>
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <div class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchFilter" placeholder="Search by subject or message..." 
                           class="filter-input" onkeyup="filterFeedbacks()">
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <select id="typeFilter" class="filter-select" onchange="filterFeedbacks()">
                        <option value="">All Types</option>
                        <option value="complaint">Complaint</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="compliment">Compliment</option>
                        <option value="general">General</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select id="statusFilter" class="filter-select" onchange="filterFeedbacks()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="resolved">Resolved</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedbacks Table -->
    <div id="feedbacksList" class="table-container">
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
                <label>Rating (1-5) *</label>
                <select id="feedbackRating" required>
                    <option value="">Select Rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Fair</option>
                    <option value="3">3 - Good</option>
                    <option value="4">4 - Very Good</option>
                    <option value="5">5 - Excellent</option>
                </select>
                <small>Rate your experience from 1 to 5</small>
            </div>
            <div class="form-group">
                <label>Image (Optional)</label>
                <input type="file" id="feedbackImage" accept="image/*" onchange="handleImageUpload(event)">
                <small>Upload an image related to your feedback (JPG, PNG, GIF). Maximum size: 1MB</small>
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
                    <button type="button" onclick="removeImage()" style="margin-left: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
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

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    const d = new Date(dateTimeString);
    if (isNaN(d.getTime())) return 'N/A';
    
    const date = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const time = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    return `${date} ${time}`;
}

function displayFeedbacks(feedbacksToShow) {
    const container = document.getElementById('feedbacksList');
    if (feedbacksToShow.length === 0) {
        container.innerHTML = '<div class="table-container"><table class="data-table"><tbody><tr><td colspan="8" class="text-center">No feedbacks found</td></tr></tbody></table></div>';
        return;
    }

    const isAdmin = typeof API !== 'undefined' && API.isAdmin();

    // Sort by newest date first
    const sortedFeedbacks = [...feedbacksToShow].sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB - dateA;
    });

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
                ${sortedFeedbacks.map(feedback => {
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
                                ${feedback.type || 'general'}
                            </span>
                        </td>
                        <td style="white-space: nowrap;">${ratingStars}</td>
                        ${!isAdmin ? `<td>${feedback.facility ? feedback.facility.name : 'N/A'}</td>` : ''}
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
                            ${isAdmin && feedback.status !== 'resolved' && !feedback.is_blocked ? `
                                <button class="btn-sm btn-success" onclick="replyToFeedback(${feedback.id})" title="Reply">
                                    <i class="fas fa-reply"></i>
                                </button>
                            ` : ''}
                            ${isAdmin && !feedback.is_blocked ? `
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
    const search = document.getElementById('searchFilter').value.toLowerCase().trim();
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = feedbacks.filter(f => {
        // Search filter - check subject and message
        const matchSearch = !search || 
            (f.subject && f.subject.toLowerCase().includes(search)) ||
            (f.message && f.message.toLowerCase().includes(search));
        
        // Type filter
        const matchType = !type || f.type === type;
        
        // Status filter
        const matchStatus = !status || f.status === status;
        
        return matchSearch && matchType && matchStatus;
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

// Image handling variables
let selectedImageBase64 = null;

// Handle image upload and convert to base64
function handleImageUpload(event) {
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
            document.getElementById('imagePreview').style.display = 'none';
            return;
        }

        selectedImageBase64 = base64;
        // Show preview
        document.getElementById('previewImg').src = selectedImageBase64;
        document.getElementById('imagePreview').style.display = 'block';
    };
    reader.onerror = function() {
        if (typeof showToast === 'function') {
            showToast('Error reading image file', 'error');
        } else {
            alert('Error reading image file');
        }
    };
    reader.readAsDataURL(file);
}

// Remove selected image
function removeImage() {
    selectedImageBase64 = null;
    document.getElementById('feedbackImage').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('previewImg').src = '';
}

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
        rating: parseInt(document.getElementById('feedbackRating').value),
        facility_id: document.getElementById('feedbackFacility').value || null,
        image: selectedImageBase64 || null
    };

            const result = await API.post('/feedbacks', data);

            if (result.success) {
                // Reset image selection
                selectedImageBase64 = null;
                document.getElementById('feedbackImage').value = '';
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('previewImg').src = '';
                
                window.closeModal();
                loadFeedbacks();
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
</script>

<style>
/* Filters Section Styling */
.filters-section {
    margin-bottom: 30px;
}

.filters-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-input-wrapper,
.filter-select-wrapper {
    position: relative;
    flex: 1;
    min-width: 200px;
}

.filter-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 1;
    pointer-events: none;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-input::placeholder {
    color: #adb5bd;
}

.filter-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Table Container Enhancement */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    position: relative;
    overflow: visible;
}

.table-container .data-table {
    overflow: hidden;
    border-radius: 12px;
}

.data-table {
    margin: 0;
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.data-table th {
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
}

.data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
    color: #2d3436;
}

.data-table tbody tr:hover {
    background-color: #f8f9fa;
}

.data-table .actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    align-items: center;
}

.data-table .text-center {
    text-align: center;
    padding: 40px;
    color: #636e72;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
    letter-spacing: 0.3px;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
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

.btn-sm.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-sm.btn-info:hover {
    background: #138496;
}

.btn-sm.btn-success {
    background: #10b981;
    color: white;
}

.btn-sm.btn-success:hover {
    background: #059669;
}

.btn-sm.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-sm.btn-danger:hover {
    background: #dc2626;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-input-wrapper,
    .filter-select-wrapper {
        width: 100%;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }

    .data-table {
        font-size: 0.85rem;
    }

    .data-table th,
    .data-table td {
        padding: 10px;
    }

    .data-table .actions {
        flex-direction: column;
    }
}

/* Page Header Styles */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.page-header-content {
    padding: 10px 0;
}

.page-header-content h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.btn-header-white {
    background-color: #ffffff;
    color: #cb2d3e; 
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    cursor: pointer;
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.btn-header-white:active {
    transform: translateY(0);
}

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


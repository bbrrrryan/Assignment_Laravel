let feedbacks = [];
let facilities = [];
let selectedImageBase64 = null;

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
        if (select) {
            select.innerHTML = '<option value="">None</option>' +
                facilities.map(f => `<option value="${f.id}">${f.name}</option>`).join('');
        }
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
    if (infoDiv) {
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
                    <span class="status-badge status-${feedback.type}">${feedback.type}</span>
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
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const feedbackId = this.dataset.feedbackId;
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
                    loadFeedbacks();
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
                    loadFeedbacks();
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


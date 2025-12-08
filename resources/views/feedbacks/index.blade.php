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
            ${feedback.admin_response ? `<div class="admin-response">
                <strong>Admin Response:</strong>
                <p>${feedback.admin_response}</p>
            </div>` : ''}
            <div class="feedback-footer">
                <span class="feedback-time">${formatDateTime(feedback.created_at)}</span>
                <div class="feedback-actions">
                    <button class="btn-sm" onclick="viewFeedback(${feedback.id})">View</button>
                    ${typeof API !== 'undefined' && API.isAdmin() && !feedback.admin_response ? `
                        <button class="btn-sm btn-success" onclick="replyToFeedback(${feedback.id})">Reply</button>
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
window.replyToFeedback = async function(id) {
    const response = prompt('Enter your response to this feedback:');
    if (response === null || response.trim() === '') {
        return; // User cancelled or entered empty response
    }
    
    if (typeof API === 'undefined') {
        alert('API not loaded');
        return;
    }
    
    const result = await API.put(`/feedbacks/${id}/respond`, { response: response.trim() });
    
    if (result.success) {
        if (typeof loadFeedbacks === 'function') {
            loadFeedbacks();
        }
        alert('Response submitted successfully!');
    } else {
        alert(result.error || 'Error submitting response');
    }
};

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
@endsection


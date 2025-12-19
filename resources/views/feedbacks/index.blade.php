@extends('layouts.app')

@section('title', 'Feedback - TARUMT FMS')

@section('content')
<link rel="stylesheet" href="{{ asset('css/users/feedback/index.css') }}">
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
                <label>Filter for Related Booking</label>
                <select id="facilityTypeFilter" onchange="filterBookingsByFacilityType()">
                    <option value="">All Facility Types</option>
                </select>
                <small>Select a facility type to filter bookings</small>
            </div>
            <div class="form-group">
                <label>Related Booking (Optional)</label>
                <select id="feedbackBooking">
                    <option value="">None</option>
                </select>
                <small>Select a booking if this feedback is related to a specific booking</small>
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

<script src="{{ asset('js/feedbacks/index.js') }}"></script>

@endsection


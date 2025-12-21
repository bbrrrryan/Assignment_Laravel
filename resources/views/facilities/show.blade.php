/**
 * Author: Ng Jhun Hou
 */ 

@extends('layouts.app')

@section('title', 'Facility Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Facility Details</h1>
            <p>View facility information, book, and submit feedback</p>
        </div>
        <div>
            <a href="{{ route('facilities.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Facilities
            </a>
        </div>
    </div>

    <div id="facilityDetails" class="details-container">
        <p>Loading facility details...</p>
    </div>

    <div id="actionButtons" class="action-buttons-section" style="display: none;">
        <div class="action-buttons-card">
            <a href="#" id="addBookingBtn" class="btn-action btn-booking">
                <i class="fas fa-calendar-plus"></i>
                <span>Add Booking</span>
            </a>
            <button type="button" id="submitFeedbackBtn" class="btn-action btn-feedback" onclick="openFeedbackModal()">
                <i class="fas fa-comment-alt"></i>
                <span>Submit Feedback</span>
            </button>
        </div>
    </div>

    <div id="feedbacksSection" class="feedbacks-section" style="display: none;">
        <div class="feedbacks-card">
            <h2><i class="fas fa-comments"></i> Facility Feedbacks</h2>
            <div id="feedbacksList" class="feedbacks-list">
                <p>Loading feedbacks...</p>
            </div>
        </div>
    </div>
</div>

<div id="feedbackModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeFeedbackModal()">&times;</span>
        <h2>Submit Feedback</h2>
        <form id="feedbackForm">
            <input type="hidden" id="feedbackFacilityId">
            <div class="form-group">
                <label>Type *</label>
                <select id="feedbackType" required>
                    <option value="">Select Type</option>
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
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                <button type="submit" class="btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/facilities/index.css') }}">
<script src="{{ asset('js/facilities/index.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const facilityId = {{ $id }};
    if (typeof initFacilityShow === 'function') {
        initFacilityShow(facilityId);
    }
});
</script>
@endsection

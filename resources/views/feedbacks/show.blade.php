@extends('layouts.app')

@section('title', 'Feedback Details - TARUMT FMS')

@section('content')
<link rel="stylesheet" href="{{ asset('css/users/feedback/show.css') }}">
<div class="page-container">
    <div class="page-header">
        <h1>Feedback Details</h1>
        <a href="{{ route('feedbacks.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Feedbacks
        </a>
    </div>

    <div id="feedbackDetails" class="details-container" data-feedback-id="{{ $id }}">
        <p>Loading feedback details...</p>
    </div>
</div>

<script>
// Pass feedback ID to JavaScript
window.feedbackId = {{ $id }};
</script>

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

<script src="{{ asset('js/feedbacks/show.js') }}"></script>


@endsection


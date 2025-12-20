@extends('layouts.app')

@section('title', 'Feedback Management - TARUMT FMS')

@section('content')
<link rel="stylesheet" href="{{ asset('css/users/feedback/index.css') }}">
<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 id="feedbacksTitle">Feedback Management</h1>
            <p id="feedbacksSubtitle">Manage all feedbacks, complaints, and suggestions</p>
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
                        <option value="rejected">Rejected</option>
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

    <!-- Pagination -->
    <div id="paginationContainer" class="pagination-wrapper"></div>
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


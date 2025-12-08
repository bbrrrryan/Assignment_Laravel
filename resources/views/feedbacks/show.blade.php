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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadFeedbackDetails();
});

async function loadFeedbackDetails() {
    const feedbackId = {{ $id }};
    const result = await API.get(`/feedbacks/${feedbackId}`);

    if (result.success) {
        const feedback = result.data.data;
        displayFeedbackDetails(feedback);
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
        </div>
    `;
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
}
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
</style>
@endsection


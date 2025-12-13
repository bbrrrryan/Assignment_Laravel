@extends('layouts.app')

@section('title', 'Announcement Details - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Announcement Details</h1>
        <a href="{{ route('notifications.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div id="announcementDetails" class="details-container">
        <p>Loading announcement details...</p>
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

    loadAnnouncementDetails();
});

async function loadAnnouncementDetails() {
    const announcementId = {{ $id }};
    
    // Mark announcement as read when viewing details
    try {
        await API.put(`/announcements/${announcementId}/read`, {});
    } catch (error) {
        console.error('Error marking announcement as read:', error);
    }
    
    const result = await API.get(`/announcements/${announcementId}`);

    if (result.success) {
        const announcement = result.data.data;
        displayAnnouncementDetails(announcement);
    } else {
        document.getElementById('announcementDetails').innerHTML = `
            <div class="error-message">
                <p>Error loading announcement details: ${result.error || 'Unknown error'}</p>
                <a href="{{ route('notifications.index') }}" class="btn-primary">Back</a>
            </div>
        `;
    }
}

function displayAnnouncementDetails(announcement) {
    const container = document.getElementById('announcementDetails');
    
    container.innerHTML = `
        <div class="details-card">
            <div class="details-section">
                <h2>Announcement Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span class="detail-value">${announcement.title || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="status-badge status-${announcement.type}">${announcement.type || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority:</span>
                    <span class="detail-value">${announcement.priority || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${announcement.is_active ? 'active' : 'inactive'}">${announcement.is_active ? 'Active' : 'Inactive'}</span>
                </div>
                ${announcement.published_at ? `
                <div class="detail-row">
                    <span class="detail-label">Published:</span>
                    <span class="detail-value">${formatDateTime(announcement.published_at)}</span>
                </div>
                ` : ''}
                <div class="detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">${formatDateTime(announcement.created_at)}</span>
                </div>
            </div>

            <div class="details-section">
                <h2>Content</h2>
                <div class="message-content">
                    ${announcement.content || 'No content'}
                </div>
            </div>

            ${announcement.target_audience ? `
            <div class="details-section">
                <h2>Target Audience</h2>
                <div class="detail-row">
                    <span class="detail-label">Audience:</span>
                    <span class="detail-value">${announcement.target_audience}</span>
                </div>
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
    white-space: pre-wrap;
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


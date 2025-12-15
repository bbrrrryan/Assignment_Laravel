{{-- Author: Liew Zi Li (announcement show) --}}
@extends('layouts.app')

@section('title', 'Announcement Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Announcement Details</h1>
            <p>{{ $announcement->title }}</p>
        </div>
        <div>
            <a href="{{ route('admin.announcements.edit', $announcement->id) }}" class="btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <form id="deleteAnnouncementForm" action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-danger" onclick="showDeleteConfirmModal()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
            <a href="{{ route('admin.announcements.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="details-container">
        <div class="details-card">
            <div class="details-section">
                <h2>Basic Information</h2>
                <div class="detail-row">
                    <span class="detail-label">ID:</span>
                    <span class="detail-value">{{ $announcement->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span class="detail-value">{{ $announcement->title }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">
                        <span class="badge badge-{{ $announcement->type }}">{{ ucfirst($announcement->type) }}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority:</span>
                    <span class="detail-value">
                        <span class="badge badge-priority-{{ $announcement->priority }}">{{ ucfirst($announcement->priority) }}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge badge-{{ $announcement->is_active ? 'success' : 'secondary' }}">
                            {{ $announcement->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                </div>
                <div class="detail-row">
                    <span class="detail-label">Views:</span>
                    <span class="detail-value">{{ $announcement->views_count ?? 0 }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created By:</span>
                    <span class="detail-value">{{ $announcement->creator->name ?? 'System' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created At:</span>
                    <span class="detail-value" data-datetime="{{ $announcement->created_at->toIso8601String() }}"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Updated At:</span>
                    <span class="detail-value" data-datetime="{{ $announcement->updated_at->toIso8601String() }}"></span>
                </div>
                @if($announcement->published_at)
                <div class="detail-row">
                    <span class="detail-label">Published At:</span>
                    <span class="detail-value" data-datetime="{{ $announcement->published_at->toIso8601String() }}"></span>
                </div>
                @endif
                @if($announcement->expires_at)
                <div class="detail-row">
                    <span class="detail-label">Expires At:</span>
                    <span class="detail-value">{{ $announcement->expires_at->format('Y-m-d H:i:s') }}</span>
                </div>
                @endif
            </div>

            <div class="details-section">
                <h2>Content</h2>
                <div class="message-content">
                    {{ $announcement->content }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="confirm-modal" style="display: none;">
    <div class="confirm-modal-content">
        <div class="confirm-modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        </div>
        <div class="confirm-modal-body">
            <p>Are you sure you want to delete this announcement?</p>
            <p class="confirm-modal-warning"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn-confirm-cancel" onclick="closeDeleteConfirmModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn-confirm-delete" onclick="confirmDeleteAnnouncement()">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>

<style>
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

.page-header h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.page-header > div:last-child {
    display: flex;
    gap: 10px;
    align-items: center;
}

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

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-error {
    background: #f8d7da;
    color: #721c24;
}

.badge-reminder {
    background: #e2e3e5;
    color: #383d41;
}

.badge-priority-low {
    background: #e2e3e5;
    color: #383d41;
}

.badge-priority-medium {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-priority-high {
    background: #fff3cd;
    color: #856404;
}

.badge-priority-urgent {
    background: #f8d7da;
    color: #721c24;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.btn-primary {
    background: #cb2d3e;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #a01a2a;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}

/* Delete Confirmation Modal Styles */
.confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    animation: fadeIn 0.2s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.confirm-modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 450px;
    width: 90%;
    animation: slideDown 0.3s ease-out;
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

.confirm-modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
    background: #fff3cd;
    border-radius: 8px 8px 0 0;
}

.confirm-modal-header h3 {
    margin: 0;
    color: #856404;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.confirm-modal-header i {
    color: #ffc107;
}

.confirm-modal-body {
    padding: 25px;
}

.confirm-modal-body p {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 1rem;
    line-height: 1.5;
}

.confirm-modal-warning {
    color: #dc3545 !important;
    font-weight: 500;
}

.confirm-modal-footer {
    padding: 15px 25px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.btn-confirm-cancel {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-confirm-cancel:hover {
    background: #5a6268;
}

.btn-confirm-delete {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-confirm-delete:hover {
    background: #c82333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format all datetime fields using the same format as the list page
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Format all datetime elements
    document.querySelectorAll('[data-datetime]').forEach(function(element) {
        const datetime = element.getAttribute('data-datetime');
        if (datetime) {
            element.textContent = formatDateTime(datetime);
        }
    });
});

// Delete Confirmation Modal Functions
function showDeleteConfirmModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeDeleteConfirmModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function confirmDeleteAnnouncement() {
    const form = document.getElementById('deleteAnnouncementForm');
    if (form) {
        form.submit();
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal && event.target === modal) {
        closeDeleteConfirmModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteConfirmModal();
    }
});
</script>
@endsection


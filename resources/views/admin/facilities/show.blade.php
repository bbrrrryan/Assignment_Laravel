@extends('layouts.app')

@section('title', 'Facility Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Facility Details</h1>
            <p>{{ $facility->name }}</p>
        </div>
        <div>
            <a href="{{ route('admin.facilities.edit', $facility->id) }}" class="btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.facilities.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="details-container">
        <div class="details-card">
            <div class="details-section">
                <h2>Basic Information</h2>
                <div class="detail-row">
                    <span class="detail-label">ID:</span>
                    <span class="detail-value">{{ $facility->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $facility->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Code:</span>
                    <span class="detail-value">{{ $facility->code }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">{{ ucfirst($facility->type) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">{{ $facility->location }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Capacity:</span>
                    <span class="detail-value">{{ $facility->capacity }} people</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge badge-{{ $facility->status === 'available' ? 'success' : ($facility->status === 'maintenance' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($facility->status) }}
                        </span>
                    </span>
                </div>
                @if($facility->description)
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">{{ $facility->description }}</span>
                </div>
                @endif
                @if($facility->image_url)
                <div class="detail-row">
                    <span class="detail-label">Image:</span>
                    <span class="detail-value">
                        <a href="{{ $facility->image_url }}" target="_blank" class="btn-sm btn-info">
                            <i class="fas fa-external-link-alt"></i> View Image
                        </a>
                    </span>
                </div>
                @endif
            </div>

            <div class="details-section">
                <h2>Booking Settings</h2>
                <div class="detail-row">
                    <span class="detail-label">Requires Approval:</span>
                    <span class="detail-value">{{ $facility->requires_approval ? 'Yes' : 'No' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Advance Days:</span>
                    <span class="detail-value">{{ $facility->booking_advance_days ?? 30 }} days</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Max Booking Hours:</span>
                    <span class="detail-value">{{ $facility->max_booking_hours ?? 4 }} hours</span>
                </div>
            </div>

            <div class="details-section">
                <h2>Statistics</h2>
                <div class="detail-row">
                    <span class="detail-label">Total Bookings:</span>
                    <span class="detail-value">{{ $facility->bookings->count() }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created At:</span>
                    <span class="detail-value">{{ $facility->created_at->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Updated At:</span>
                    <span class="detail-value">{{ $facility->updated_at->format('Y-m-d H:i:s') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

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
</style>
@endsection

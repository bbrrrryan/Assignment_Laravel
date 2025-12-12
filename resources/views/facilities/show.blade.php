@extends('layouts.app')

@section('title', 'Facility Details - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Facility Details</h1>
        <a href="{{ route('facilities.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Facilities
        </a>
    </div>

    <div id="facilityDetails" class="details-container">
        <p>Loading facility details...</p>
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

    loadFacilityDetails();
});

async function loadFacilityDetails() {
    const facilityId = {{ $id }};
    const result = await API.get(`/facilities/${facilityId}`);

    if (result.success) {
        const facility = result.data.data;
        displayFacilityDetails(facility);
    } else {
        document.getElementById('facilityDetails').innerHTML = `
            <div class="error-message">
                <p>Error loading facility details: ${result.error || 'Unknown error'}</p>
                <a href="{{ route('facilities.index') }}" class="btn-primary">Back to Facilities</a>
            </div>
        `;
    }
}

function displayFacilityDetails(facility) {
    const container = document.getElementById('facilityDetails');
    
    container.innerHTML = `
        <div class="details-card">
            <div class="details-section">
                <h2>Facility Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${facility.name || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Code:</span>
                    <span class="detail-value">${facility.code || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value">${facility.type || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${facility.status}">${facility.status || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">${facility.location || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Capacity:</span>
                    <span class="detail-value">${facility.capacity || 'N/A'} people</span>
                </div>
                ${facility.description ? `
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">${facility.description}</span>
                </div>
                ` : ''}
            </div>

            ${facility.requires_approval !== undefined ? `
            <div class="details-section">
                <h2>Booking Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Requires Approval:</span>
                    <span class="detail-value">${facility.requires_approval ? 'Yes' : 'No'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Max Booking Hours:</span>
                    <span class="detail-value">${facility.max_booking_hours || 'N/A'} hours</span>
                </div>
                ${facility.available_day && facility.available_day.length > 0 && facility.available_time ? `
                <div class="detail-row">
                    <span class="detail-label">Available Days & Times:</span>
                    <span class="detail-value">
                        <div style="margin-bottom: 10px;">
                            <strong>Available Days:</strong>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                ${facility.available_day.map(dayKey => {
                                    const dayNames = {
                                        'monday': 'Monday',
                                        'tuesday': 'Tuesday',
                                        'wednesday': 'Wednesday',
                                        'thursday': 'Thursday',
                                        'friday': 'Friday',
                                        'saturday': 'Saturday',
                                        'sunday': 'Sunday'
                                    };
                                    return \`<span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em;">\${dayNames[dayKey] || dayKey.charAt(0).toUpperCase() + dayKey.slice(1)}</span>\`;
                                }).join('')}
                            </div>
                        </div>
                        <div>
                            <strong>Time Range:</strong>
                            <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em; margin-left: 8px;">
                                \${facility.available_time.start || 'N/A'} - \${facility.available_time.end || 'N/A'}
                            </span>
                        </div>
                    </span>
                </div>
                ` : ''}
            </div>
            ` : ''}
        </div>
    `;
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

.error-message {
    text-align: center;
    padding: 40px;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
}
</style>
@endsection


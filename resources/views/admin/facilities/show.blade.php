{{-- Author: [Ng Jhun Hou] --}}

@extends('layouts.app')

@section('title', 'Facility Details - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Facility Details</h1>
            <p>View facility information and statistics</p>
        </div>
        <div>
            @if(!empty($hasBookingsThisMonth) && $hasBookingsThisMonth)
                <a href="{{ route('admin.facilities.export-usage-csv', $facility->id) }}" class="btn-header-white" style="margin-right: 10px;">
                    <i class="fas fa-file-csv"></i> Export Usage Report (CSV)
                </a>
            @endif
            <a href="{{ route('admin.facilities.edit', $facility->id) }}" class="btn-header-white">
                <i class="fas fa-edit"></i> Edit Facility
            </a>
            <a href="{{ route('admin.facilities.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Facilities
            </a>
        </div>
    </div>

    <div class="details-section">
        <div class="details-card">
            <h2><i class="fas fa-building"></i> Facility Information</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>ID</label>
                    <p>{{ $facility->id }}</p>
                </div>
                <div class="detail-item">
                    <label>Name</label>
                    <p>{{ $facility->name }}</p>
                </div>
                <div class="detail-item">
                    <label>Code</label>
                    <p>{{ $facility->code }}</p>
                </div>
                <div class="detail-item">
                    <label>Type</label>
                    <p>
                        <span class="badge badge-info">
                            {{ ucfirst($facility->type) }}
                        </span>
                    </p>
                </div>
                <div class="detail-item">
                    <label>Location</label>
                    <p>{{ $facility->location }}</p>
                </div>
                <div class="detail-item">
                    <label>Capacity</label>
                    <p>{{ $facility->capacity }} people</p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p>
                        <span class="badge badge-{{ $facility->status === 'available' ? 'success' : ($facility->status === 'maintenance' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($facility->status) }}
                        </span>
                    </p>
                </div>
                @if($facility->description)
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <p>{{ $facility->description }}</p>
                </div>
                @endif
                @if($facility->equipment && !empty($facility->equipment))
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label><i class="fas fa-tools"></i> Equipment</label>
                    <p>
                        @if(is_array($facility->equipment))
                            <ul style="list-style: none; padding: 0; margin: 10px 0;">
                                @foreach($facility->equipment as $item)
                                    @if(!empty($item))
                                    <li style="padding: 8px 12px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #007bff; border-radius: 4px;">
                                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>{{ $item }}
                                    </li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            <span style="color: #6c757d;">{{ $facility->equipment }}</span>
                        @endif
                    </p>
                </div>
                @endif
                @if($facility->rules)
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label><i class="fas fa-clipboard-list"></i> Rules</label>
                    <p style="white-space: pre-line; padding: 10px 15px 35px 10px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ffc107; line-height: 1.6;">
                        {{ $facility->rules }}
                    </p>
                </div>
                @endif
                @if($facility->image_url)
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label>Image</label>
                    <p>
                        <img src="{{ $facility->image_url }}" alt="{{ $facility->name }}" 
                             class="img-thumbnail facility-image" style="max-width: 400px; max-height: 400px; cursor: pointer; border-radius: 8px;"
                             onclick="window.open('{{ $facility->image_url }}', '_blank')">
                        <br>
                        <a href="{{ $facility->image_url }}" target="_blank" style="display: inline-block; margin-top: 10px; color: #0066cc; text-decoration: none;">
                            <i class="fas fa-external-link-alt"></i> View Full Image
                        </a>
                    </p>
                </div>
                @endif
            </div>
        </div>

        <div class="details-card">
            <h2><i class="fas fa-calendar-alt"></i> Booking Settings</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>Max Booking Hours</label>
                    <p>{{ $facility->max_booking_hours ?? 4 }} hours</p>
                </div>
                @if($facility->enable_multi_attendees)
                <div class="detail-item">
                    <label>Multi-Attendees</label>
                    <p>Enabled</p>
                </div>
                @if($facility->max_attendees)
                <div class="detail-item">
                    <label>Max Attendees</label>
                    <p>{{ $facility->max_attendees }} people</p>
                </div>
                @endif
                @endif
                @if($facility->available_day && !empty($facility->available_day) && $facility->available_time)
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label>Available Days</label>
                    <p>
                        @php
                            $dayNames = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                         'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 
                                         'sunday' => 'Sunday'];
                        @endphp
                        @foreach($facility->available_day as $dayKey)
                            <span class="badge badge-info" style="margin-right: 5px; margin-bottom: 5px; display: inline-block;">
                                {{ $dayNames[$dayKey] ?? ucfirst($dayKey) }}
                            </span>
                        @endforeach
                    </p>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label>Time Range</label>
                    <p>
                        <span class="badge badge-success">
                            {{ $facility->available_time['start'] ?? 'N/A' }} - {{ $facility->available_time['end'] ?? 'N/A' }}
                        </span>
                    </p>
                </div>
                @else
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label>Available Days & Times</label>
                    <p style="color: #6c757d;">Not set</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="details-section">
        <div class="details-card" style="grid-column: 1 / -1;">
            <h2><i class="fas fa-chart-bar"></i> Statistics</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>Total Bookings</label>
                    <p>{{ $facility->bookings->count() }}</p>
                </div>
                <div class="detail-item">
                    <label>Created At</label>
                    <p>{{ $facility->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="detail-item">
                    <label>Updated At</label>
                    <p>{{ $facility->updated_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="detail-item">
                    <label>Created By</label>
                    <p>{{ $creatorInfo['name'] ?? 'N/A' }}</p>
                </div>
                <div class="detail-item">
                    <label>Updated By</label>
                    <p>{{ $updaterInfo['name'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/facilities.css') }}">
@endsection

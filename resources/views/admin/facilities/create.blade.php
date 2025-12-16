@extends('layouts.app')

@section('title', 'Add Facility - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Add Facility</h1>
            <p>Create a new facility record</p>
        </div>
        <div>
            <a href="{{ route('admin.facilities.index') }}" class="btn-header-white">
                <i class="fas fa-arrow-left"></i> Back to Facilities
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('admin.facilities.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="code">Code <span class="required">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}" required class="form-input" placeholder="e.g., LAB-101, GYM-A">
                    <small>Unique identifier, e.g., LAB-101, GYM-A</small>
                </div>

                <div class="form-group">
                    <label for="type">Type <span class="required">*</span></label>
                    <select id="type" name="type" required class="form-select">
                        <option value="">Select Type</option>
                        <option value="classroom" {{ old('type') === 'classroom' ? 'selected' : '' }}>Classroom</option>
                        <option value="laboratory" {{ old('type') === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                        <option value="sports" {{ old('type') === 'sports' ? 'selected' : '' }}>Sports</option>
                        <option value="auditorium" {{ old('type') === 'auditorium' ? 'selected' : '' }}>Auditorium</option>
                        <option value="library" {{ old('type') === 'library' ? 'selected' : '' }}>Library</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}" required class="form-input" placeholder="e.g., Main Building 3rd Floor">
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity <span class="required">*</span></label>
                    <input type="number" id="capacity" name="capacity" value="{{ old('capacity') }}" required min="1" class="form-input">
                    <small>Number of people the facility can accommodate</small>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required class="form-select">
                        <option value="available" {{ old('status', 'available') === 'available' ? 'selected' : '' }}>Available - Facility is ready for use</option>
                        <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance - Facility is under maintenance</option>
                        <option value="unavailable" {{ old('status') === 'unavailable' ? 'selected' : '' }}>Unavailable - Facility is not available</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-input">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="image">Facility Image</label>
                    <input type="file" id="image" name="image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="form-input" style="padding: 8px;">
                    <small>Upload an image (JPEG, PNG, JPG, GIF, WEBP). Max size: 2MB</small>
                    <div id="imagePreview" style="margin-top: 15px; display: none;">
                        <label style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: 600;">Image Preview:</label>
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 400px; max-height: 400px; border-radius: 8px;">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Booking Settings</h3>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="requires_approval" 
                               name="requires_approval" value="1" 
                               {{ old('requires_approval') ? 'checked' : '' }}
                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                        Requires Approval
                    </label>
                    <small>If checked, bookings for this facility require admin approval</small>
                </div>

                <div class="form-group">
                    <label for="max_booking_hours">Max Booking Hours</label>
                    <input type="number" id="max_booking_hours" 
                           name="max_booking_hours" value="{{ old('max_booking_hours', 4) }}" 
                           min="1" max="24" class="form-input">
                    <small>Maximum duration per booking (default: 4 hours)</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="enable_multi_attendees" 
                               name="enable_multi_attendees" value="1" 
                               {{ old('enable_multi_attendees') ? 'checked' : '' }}
                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                        Enable Multi-Attendees
                    </label>
                    <small>Allow multiple attendees to be specified for bookings</small>
                </div>

                <div class="form-group" id="max_attendees_container" style="display: {{ old('enable_multi_attendees') ? 'block' : 'none' }};">
                    <label for="max_attendees">Maximum Attendees <span class="required" id="max_attendees_required" style="display: {{ old('enable_multi_attendees') ? 'inline' : 'none' }};">*</span></label>
                    <input type="number" id="max_attendees" 
                           name="max_attendees" value="{{ old('max_attendees') }}" 
                           min="1" max="{{ old('capacity', 1000) }}" class="form-input">
                    <small>Maximum number of attendees allowed per booking (cannot exceed facility capacity)</small>
                </div>

                <div class="form-group">
                    <label>Available Days & Times</label>
                    <small style="display: block; margin-bottom: 15px;">Select the available days and set the time range</small>
                    
                    @php
                        $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 
                                 'sunday' => 'Sunday'];
                        $currentDays = old('available_day', []);
                        $currentTime = old('available_time', ['start' => '08:00', 'end' => '18:00']);
                        $startTime = $currentTime['start'] ?? '08:00';
                        $endTime = $currentTime['end'] ?? '18:00';
                    @endphp
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #495057;">Select Available Days:</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                            @foreach($days as $dayKey => $dayName)
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" 
                                       id="day_{{ $dayKey }}" 
                                       name="available_day[]" 
                                       value="{{ $dayKey }}" 
                                       {{ in_array($dayKey, $currentDays) ? 'checked' : '' }}
                                       style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                                <span>{{ $dayName }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label for="start_time" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Start Time:</label>
                            <input type="time" id="start_time" 
                                   name="available_time[start]" 
                                   value="{{ old('available_time.start', $startTime) }}" 
                                   required class="form-input">
                        </div>
                        <div>
                            <label for="end_time" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">End Time:</label>
                            <input type="time" id="end_time" 
                                   name="available_time[end]" 
                                   value="{{ old('available_time.end', $endTime) }}" 
                                   required class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.facilities.index') }}" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Facility
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/facilities.css') }}">
<script src="{{ asset('js/admin/facilities/form.js') }}"></script>
@endsection

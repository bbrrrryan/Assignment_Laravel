@extends('layouts.app')

@section('title', 'Add Facility - TARUMT FMS')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Add New Facility</h1>
            <p class="text-muted mb-0">Create a new facility record</p>
        </div>
        <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <!-- Error Messages -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:
            </h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-plus-circle me-2 text-primary"></i>Facility Information
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.facilities.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Basic Information Section -->
                <div class="mb-4">
                    <h5 class="text-primary mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ old('name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   value="{{ old('code') }}" required placeholder="e.g., LAB-101, GYM-A">
                            <small class="form-text text-muted">Unique identifier, e.g., LAB-101, GYM-A</small>
                        </div>

                        <div class="col-md-6">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="classroom" {{ old('type') === 'classroom' ? 'selected' : '' }}>Classroom</option>
                                <option value="laboratory" {{ old('type') === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                                <option value="sports" {{ old('type') === 'sports' ? 'selected' : '' }}>Sports</option>
                                <option value="auditorium" {{ old('type') === 'auditorium' ? 'selected' : '' }}>Auditorium</option>
                                <option value="library" {{ old('type') === 'library' ? 'selected' : '' }}>Library</option>
                                <option value="cafeteria" {{ old('type') === 'cafeteria' ? 'selected' : '' }}>Cafeteria</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="{{ old('location') }}" required placeholder="e.g., Main Building 3rd Floor">
                        </div>

                        <div class="col-md-6">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity" 
                                   value="{{ old('capacity') }}" required min="1">
                            <small class="form-text text-muted">Number of people it can accommodate</small>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" {{ old('status', 'available') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="unavailable" {{ old('status') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                                <option value="reserved" {{ old('status') === 'reserved' ? 'selected' : '' }}>Reserved</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label for="image" class="form-label">Facility Image</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <small class="form-text text-muted">Upload an image (JPEG, PNG, JPG, GIF, WEBP). Max size: 2MB</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Settings Section -->
                <div class="mb-4">
                    <h5 class="text-primary mb-3 border-bottom pb-2">
                        <i class="fas fa-calendar-alt me-2"></i>Booking Settings
                    </h5>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="requires_approval" 
                                       name="requires_approval" value="1" {{ old('requires_approval') ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_approval">
                                    Requires Approval
                                </label>
                                <small class="form-text text-muted d-block">If checked, bookings require admin approval</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="max_booking_hours" class="form-label">Max Booking Hours</label>
                            <input type="number" class="form-control" id="max_booking_hours" 
                                   name="max_booking_hours" value="{{ old('max_booking_hours', 4) }}" 
                                   min="1" max="24">
                            <small class="form-text text-muted">Maximum duration per booking (default: 4 hours)</small>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_multi_attendees" 
                                       name="enable_multi_attendees" value="1" {{ old('enable_multi_attendees') ? 'checked' : '' }}>
                                <label class="form-check-label" for="enable_multi_attendees">
                                    Enable Multi-Attendees
                                </label>
                                <small class="form-text text-muted d-block">Allow multiple attendees to be specified for bookings</small>
                            </div>
                        </div>

                        <div class="col-md-6" id="max_attendees_container" style="display: {{ old('enable_multi_attendees') ? 'block' : 'none' }};">
                            <label for="max_attendees" class="form-label">Maximum Attendees <span class="text-danger" id="max_attendees_required" style="display: {{ old('enable_multi_attendees') ? 'inline' : 'none' }};">*</span></label>
                            <input type="number" class="form-control" id="max_attendees" 
                                   name="max_attendees" value="{{ old('max_attendees') }}" 
                                   min="1" max="{{ old('capacity', 1000) }}">
                            <small class="form-text text-muted">Maximum number of attendees allowed per booking (cannot exceed facility capacity)</small>
                        </div>

                        <script>
                        // Define function immediately so it's available for onclick
                        window.toggleMaxAttendeesField = function() {
                            const enableMultiAttendees = document.getElementById('enable_multi_attendees');
                            const maxAttendeesContainer = document.getElementById('max_attendees_container');
                            const maxAttendeesInput = document.getElementById('max_attendees');
                            const maxAttendeesRequired = document.getElementById('max_attendees_required');
                            const capacityInput = document.getElementById('capacity');
                            
                            if (!enableMultiAttendees || !maxAttendeesContainer || !maxAttendeesInput) {
                                return;
                            }
                            
                            if (enableMultiAttendees.checked) {
                                maxAttendeesContainer.style.display = 'block';
                                if (maxAttendeesRequired) {
                                    maxAttendeesRequired.style.display = 'inline';
                                }
                                maxAttendeesInput.setAttribute('required', 'required');
                                if (capacityInput && capacityInput.value) {
                                    maxAttendeesInput.setAttribute('max', capacityInput.value);
                                }
                            } else {
                                maxAttendeesContainer.style.display = 'none';
                                if (maxAttendeesRequired) {
                                    maxAttendeesRequired.style.display = 'none';
                                }
                                maxAttendeesInput.removeAttribute('required');
                                maxAttendeesInput.value = '';
                            }
                        };
                        
                        // Bind event when DOM is ready
                        document.addEventListener('DOMContentLoaded', function() {
                            const enableMultiAttendees = document.getElementById('enable_multi_attendees');
                            if (enableMultiAttendees) {
                                enableMultiAttendees.addEventListener('change', window.toggleMaxAttendeesField);
                                
                                // Set initial state
                                if (enableMultiAttendees.checked) {
                                    window.toggleMaxAttendeesField();
                                }
                                
                                // Update max when capacity changes
                                const capacityInput = document.getElementById('capacity');
                                const maxAttendeesInput = document.getElementById('max_attendees');
                                if (capacityInput && maxAttendeesInput) {
                                    capacityInput.addEventListener('change', function() {
                                        if (this.value && enableMultiAttendees.checked) {
                                            maxAttendeesInput.setAttribute('max', this.value);
                                        }
                                    });
                                }
                            }
                        });
                        </script>

                        <div class="col-12">
                            <label class="form-label">Available Days & Times</label>
                            <small class="form-text text-muted d-block mb-3">Select the available days and set the time range</small>
                            
                            @php
                                $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                         'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 
                                         'sunday' => 'Sunday'];
                                $currentDays = old('available_day', []);
                                $currentTime = old('available_time', ['start' => '08:00', 'end' => '18:00']);
                                $startTime = $currentTime['start'] ?? '08:00';
                                $endTime = $currentTime['end'] ?? '18:00';
                            @endphp
                            
                            <!-- Available Days (Checkboxes) -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-2">Select Available Days</label>
                                <div class="row g-2">
                                    @foreach($days as $dayKey => $dayName)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="day_{{ $dayKey }}" 
                                                   name="available_day[]" 
                                                   value="{{ $dayKey }}" 
                                                   {{ in_array($dayKey, $currentDays) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_{{ $dayKey }}">
                                                {{ $dayName }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Available Time Range -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" 
                                           id="start_time" 
                                           name="available_time[start]" 
                                           value="{{ old('available_time.start', $startTime) }}" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" 
                                           id="end_time" 
                                           name="available_time[end]" 
                                           value="{{ old('available_time.end', $endTime) }}" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Facility
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Image preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
        }

        // Time validation - ensure end time is after start time
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        
        if (startTimeInput && endTimeInput) {
            function validateTimeRange() {
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;
                
                if (startTime && endTime && startTime >= endTime) {
                    endTimeInput.setCustomValidity('结束时间必须晚于开始时间');
                } else {
                    endTimeInput.setCustomValidity('');
                }
            }
            
            startTimeInput.addEventListener('change', validateTimeRange);
            endTimeInput.addEventListener('change', validateTimeRange);
        }
    });
</script>
@endpush
@endsection

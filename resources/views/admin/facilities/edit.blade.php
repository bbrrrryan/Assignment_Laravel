@extends('layouts.app')

@section('title', 'Edit Facility - TARUMT FMS')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Edit Facility</h1>
            <p class="text-muted mb-0">Update facility information</p>
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
                <i class="fas fa-edit me-2 text-primary"></i>Edit Facility Information
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.facilities.update', $facility->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Basic Information Section -->
                <div class="mb-4">
                    <h5 class="text-primary mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ old('name', $facility->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   value="{{ old('code', $facility->code) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="classroom" {{ old('type', $facility->type) === 'classroom' ? 'selected' : '' }}>Classroom</option>
                                <option value="laboratory" {{ old('type', $facility->type) === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                                <option value="sports" {{ old('type', $facility->type) === 'sports' ? 'selected' : '' }}>Sports</option>
                                <option value="auditorium" {{ old('type', $facility->type) === 'auditorium' ? 'selected' : '' }}>Auditorium</option>
                                <option value="library" {{ old('type', $facility->type) === 'library' ? 'selected' : '' }}>Library</option>
                                <option value="cafeteria" {{ old('type', $facility->type) === 'cafeteria' ? 'selected' : '' }}>Cafeteria</option>
                                <option value="other" {{ old('type', $facility->type) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="{{ old('location', $facility->location) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity" 
                                   value="{{ old('capacity', $facility->capacity) }}" required min="1">
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" {{ old('status', $facility->status) === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="maintenance" {{ old('status', $facility->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="unavailable" {{ old('status', $facility->status) === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                                <option value="reserved" {{ old('status', $facility->status) === 'reserved' ? 'selected' : '' }}>Reserved</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $facility->description) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label for="image" class="form-label">Facility Image</label>
                            @if($facility->image_url)
                                <div class="mb-2">
                                    <label class="form-label text-muted">Current Image:</label>
                                    <div>
                                        <img src="{{ $facility->image_url }}" alt="Current facility image" 
                                             class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                                    </div>
                                </div>
                            @endif
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <small class="form-text text-muted">Upload a new image to replace the current one (JPEG, PNG, JPG, GIF, WEBP). Max size: 2MB</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <label class="form-label text-muted">New Image Preview:</label>
                                <div>
                                    <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                                </div>
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
                                       name="requires_approval" value="1" {{ old('requires_approval', $facility->requires_approval) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_approval">
                                    Requires Approval
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="max_booking_hours" class="form-label">Max Booking Hours</label>
                            <input type="number" class="form-control" id="max_booking_hours" 
                                   name="max_booking_hours" value="{{ old('max_booking_hours', $facility->max_booking_hours ?? 4) }}" 
                                   min="1" max="24">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Available Days & Times</label>
                            <small class="form-text text-muted d-block mb-3">Select the available days and set the time range</small>
                            
                            @php
                                $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                         'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 
                                         'sunday' => 'Sunday'];
                                $currentDays = old('available_day') ?: ($facility->available_day ?? []);
                                $currentTime = old('available_time') ?: ($facility->available_time ?? ['start' => '08:00', 'end' => '18:00']);
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
                        <i class="fas fa-save me-2"></i> Update Facility
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Image preview
    document.getElementById('image').addEventListener('change', function(e) {
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

    // Time validation - ensure end time is after start time
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
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
</script>
@endpush
@endsection

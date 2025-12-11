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
            <form action="{{ route('admin.facilities.store') }}" method="POST">
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
                            <label for="image_url" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   value="{{ old('image_url') }}" placeholder="https://example.com/image.jpg">
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
                            <label for="booking_advance_days" class="form-label">Booking Advance Days</label>
                            <input type="number" class="form-control" id="booking_advance_days" 
                                   name="booking_advance_days" value="{{ old('booking_advance_days', 30) }}" 
                                   min="1" max="365">
                            <small class="form-text text-muted">How many days in advance can be booked (default: 30 days)</small>
                        </div>

                        <div class="col-md-6">
                            <label for="max_booking_hours" class="form-label">Max Booking Hours</label>
                            <input type="number" class="form-control" id="max_booking_hours" 
                                   name="max_booking_hours" value="{{ old('max_booking_hours', 4) }}" 
                                   min="1" max="24">
                            <small class="form-text text-muted">Maximum duration per booking (default: 4 hours)</small>
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
@endsection

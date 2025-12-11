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
            <form action="{{ route('admin.facilities.update', $facility->id) }}" method="POST">
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
                            <label for="image_url" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   value="{{ old('image_url', $facility->image_url) }}">
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
                            <label for="booking_advance_days" class="form-label">Booking Advance Days</label>
                            <input type="number" class="form-control" id="booking_advance_days" 
                                   name="booking_advance_days" value="{{ old('booking_advance_days', $facility->booking_advance_days ?? 30) }}" 
                                   min="1" max="365">
                        </div>

                        <div class="col-md-6">
                            <label for="max_booking_hours" class="form-label">Max Booking Hours</label>
                            <input type="number" class="form-control" id="max_booking_hours" 
                                   name="max_booking_hours" value="{{ old('max_booking_hours', $facility->max_booking_hours ?? 4) }}" 
                                   min="1" max="24">
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
@endsection

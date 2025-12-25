{{-- Author: [Ng Jhun Hou] --}}

@extends('layouts.app')

@section('title', 'Edit Facility - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Edit Facility</h1>
            <p>Update facility information</p>
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
        <form method="POST" action="{{ route('admin.facilities.update', $facility->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $facility->name) }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="code">Code <span class="required">*</span></label>
                    <input type="text" id="code" name="code" value="{{ old('code', $facility->code) }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="type">Type <span class="required">*</span></label>
                    <select id="type" name="type" required class="form-select">
                        <option value="">Select Type</option>
                        <option value="classroom" {{ old('type', $facility->type) === 'classroom' ? 'selected' : '' }}>Classroom</option>
                        <option value="laboratory" {{ old('type', $facility->type) === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                        <option value="sports" {{ old('type', $facility->type) === 'sports' ? 'selected' : '' }}>Sports</option>
                        <option value="auditorium" {{ old('type', $facility->type) === 'auditorium' ? 'selected' : '' }}>Auditorium</option>
                        <option value="library" {{ old('type', $facility->type) === 'library' ? 'selected' : '' }}>Library</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" value="{{ old('location', $facility->location) }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity <span class="required">*</span></label>
                    <input type="number" id="capacity" name="capacity" value="{{ old('capacity', $facility->capacity) }}" required min="1" class="form-input">
                    <small>Number of people the facility can accommodate</small>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required class="form-select">
                        <option value="available" {{ old('status', $facility->status) === 'available' ? 'selected' : '' }}>Available - Facility is ready for use</option>
                        <option value="maintenance" {{ old('status', $facility->status) === 'maintenance' ? 'selected' : '' }}>Maintenance - Facility is under maintenance</option>
                        <option value="unavailable" {{ old('status', $facility->status) === 'unavailable' ? 'selected' : '' }}>Unavailable - Facility is not available</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-input">{{ old('description', $facility->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="equipment">Equipment</label>
                    <small style="display: block; margin-bottom: 10px;">List equipment available in this facility (one per line or comma-separated)</small>
                    <div id="equipmentContainer">
                        @php
                            $oldEquipment = old('equipment');
                            $equipmentArray = [];
                            if ($oldEquipment) {
                                if (is_string($oldEquipment)) {
                                    $decoded = json_decode($oldEquipment, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $equipmentArray = $decoded;
                                    } else {
                                        $equipmentArray = array_filter(array_map('trim', preg_split('/[,\n\r]+/', $oldEquipment)));
                                    }
                                } elseif (is_array($oldEquipment)) {
                                    $equipmentArray = $oldEquipment;
                                }
                            } else {
                                $equipmentArray = $facility->equipment ?? [];
                            }
                        @endphp
                        @if(count($equipmentArray) > 0)
                            @foreach($equipmentArray as $index => $item)
                            <div class="equipment-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input type="text" name="equipment[]" value="{{ $item }}" class="form-input" placeholder="Equipment name">
                                <button type="button" class="btn-remove-equipment" onclick="removeEquipmentItem(this)" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        @else
                            <div class="equipment-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <input type="text" name="equipment[]" value="" class="form-input" placeholder="Equipment name">
                                <button type="button" class="btn-remove-equipment" onclick="removeEquipmentItem(this)" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                    <button type="button" onclick="addEquipmentItem()" style="margin-top: 10px; background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-plus"></i> Add Equipment
                    </button>
                    <input type="hidden" id="equipment_json" name="equipment_json">
                </div>

                <div class="form-group">
                    <label for="rules">Rules</label>
                    <textarea id="rules" name="rules" rows="5" class="form-input" placeholder="Enter facility rules and guidelines...">{{ old('rules', $facility->rules) }}</textarea>
                    <small>Enter the rules and guidelines for using this facility</small>
                </div>

                <div class="form-group">
                    <label for="image">Facility Image</label>
                    @if($facility->image_url)
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: 600;">Current Image:</label>
                            <img src="{{ $facility->image_url }}" alt="Current facility image" 
                                 class="img-thumbnail facility-image" style="max-width: 400px; max-height: 400px; cursor: pointer; border-radius: 8px;"
                                 onclick="window.open('{{ $facility->image_url }}', '_blank')">
                            <br>
                            <a href="{{ $facility->image_url }}" target="_blank" style="display: inline-block; margin-top: 10px; color: #0066cc; text-decoration: none;">
                                <i class="fas fa-external-link-alt"></i> View Full Image
                            </a>
                        </div>
                    @endif
                    <input type="file" id="image" name="image" 
                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="form-input" style="padding: 8px;">
                    <small>Upload a new image to replace the current one (JPEG, PNG, JPG, GIF, WEBP). Max size: 2MB</small>
                    <div id="imagePreview" style="margin-top: 15px; display: none;">
                        <label style="display: block; margin-bottom: 8px; color: #6c757d; font-weight: 600;">New Image Preview:</label>
                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 400px; max-height: 400px; border-radius: 8px;">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Booking Settings</h3>
                
                <div class="form-group">
                    <label for="max_booking_hours">Max Booking Hours</label>
                    <input type="number" id="max_booking_hours" 
                           name="max_booking_hours" value="{{ old('max_booking_hours', $facility->max_booking_hours ?? 4) }}" 
                           min="1" max="24" class="form-input">
                    <small>Maximum duration per booking (default: 4 hours)</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="enable_multi_attendees" 
                               name="enable_multi_attendees" value="1" 
                               {{ old('enable_multi_attendees', $facility->enable_multi_attendees ?? false) ? 'checked' : '' }}
                               style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                        Enable Multi-Attendees
                    </label>
                    <small>Allow multiple attendees to be specified for bookings</small>
                </div>

                <div class="form-group" id="max_attendees_container" style="display: {{ old('enable_multi_attendees', $facility->enable_multi_attendees ?? false) ? 'block' : 'none' }};">
                    <label for="max_attendees">Maximum Attendees <span class="required" id="max_attendees_required" style="display: {{ old('enable_multi_attendees', $facility->enable_multi_attendees ?? false) ? 'inline' : 'none' }};">*</span></label>
                    <input type="number" id="max_attendees" 
                           name="max_attendees" value="{{ old('max_attendees', $facility->max_attendees) }}" 
                           min="1" max="{{ old('capacity', $facility->capacity) }}" class="form-input">
                    <small>Maximum number of attendees allowed per booking (cannot exceed facility capacity)</small>
                </div>

                <div class="form-group">
                    <label>Available Days & Times</label>
                    <small style="display: block; margin-bottom: 15px;">Select the available days and set the time range</small>
                    
                    @php
                        $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 
                                 'sunday' => 'Sunday'];
                        $currentDays = old('available_day') ?: ($facility->available_day ?? []);
                        $currentTime = old('available_time') ?: ($facility->available_time ?? ['start' => '08:00', 'end' => '18:00']);
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
                    <i class="fas fa-save"></i> Update Facility
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/facilities.css') }}">
<script src="{{ asset('js/admin/facilities/form.js') }}"></script>
<script>
function addEquipmentItem() {
    const container = document.getElementById('equipmentContainer');
    const newItem = document.createElement('div');
    newItem.className = 'equipment-item';
    newItem.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px;';
    newItem.innerHTML = `
        <input type="text" name="equipment[]" value="" class="form-input" placeholder="Equipment name">
        <button type="button" class="btn-remove-equipment" onclick="removeEquipmentItem(this)" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newItem);
}

function removeEquipmentItem(button) {
    const container = document.getElementById('equipmentContainer');
    if (container.children.length > 1) {
        button.closest('.equipment-item').remove();
    } else {
        button.closest('.equipment-item').querySelector('input').value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const equipmentInputs = document.querySelectorAll('input[name="equipment[]"]');
            const equipmentArray = Array.from(equipmentInputs)
                .map(input => input.value.trim())
                .filter(value => value !== '');
            
            const equipmentJsonField = document.getElementById('equipment_json');
            if (equipmentJsonField) {
                equipmentJsonField.value = JSON.stringify(equipmentArray);
            }
        });
    }
});
</script>
@endsection

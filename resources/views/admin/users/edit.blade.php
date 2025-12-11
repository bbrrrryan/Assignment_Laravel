@extends('layouts.app')

@section('title', 'Edit User - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Edit User</h1>
            <p>Update user information</p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="form-card">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Full Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
        </div>

        <div class="form-group">
            <label for="email">Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-input">
            <small>Leave blank to keep current password. Minimum 6 characters if changing.</small>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input">
        </div>

        <div class="form-group">
            <label for="role_id">Role <span class="required">*</span></label>
            <select id="role_id" name="role_id" required class="form-select">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" 
                        {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                        {{ $role->display_name }} - {{ $role->description }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="text" id="phone_number" name="phone_number" 
                   value="{{ old('phone_number', $user->phone_number) }}" class="form-input">
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3" class="form-input">{{ old('address', $user->address) }}</textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="deactivated" {{ old('status', $user->status) === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Update User
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

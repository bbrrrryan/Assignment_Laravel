{{-- Author: Liew Zi Li (announcement edit) --}}
@extends('layouts.app')

@section('title', 'Edit Announcement - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>Edit Announcement</h1>
            <p>{{ $announcement->title }}</p>
        </div>
        <div>
            <a href="{{ route('admin.announcements.show', $announcement->id) }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-container">
        <div class="form-card">
            <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $announcement->title) }}" required>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" rows="4" required>{{ old('content', $announcement->content) }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            <option value="info" {{ old('type', $announcement->type) === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="warning" {{ old('type', $announcement->type) === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="success" {{ old('type', $announcement->type) === 'success' ? 'selected' : '' }}>Success</option>
                            <option value="error" {{ old('type', $announcement->type) === 'error' ? 'selected' : '' }}>Error</option>
                            <option value="reminder" {{ old('type', $announcement->type) === 'reminder' ? 'selected' : '' }}>Reminder</option>
                            <option value="general" {{ old('type', $announcement->type) === 'general' ? 'selected' : '' }}>General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="low" {{ old('priority', $announcement->priority) === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', $announcement->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $announcement->priority) === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority', $announcement->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="is_active">Status</label>
                        <select id="is_active" name="is_active">
                            <option value="1" {{ old('is_active', $announcement->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $announcement->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Update Announcement
                    </button>
                    <a href="{{ route('admin.announcements.show', $announcement->id) }}" class="btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.page-header h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-card {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #2c3e50;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.btn-primary {
    background: #cb2d3e;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #a01a2a;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.alert-error ul {
    margin: 0;
    padding-left: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
}
</style>
@endsection


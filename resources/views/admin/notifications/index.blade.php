{{-- Author: Admin Notification Management --}}
@extends('layouts.app')

@section('title', 'Notification Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Notification Management</h1>
            <p>View and manage all notifications in the system</p>
        </div>
        <div>
            <button class="btn-header-white" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Create Notification
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <form class="filters-form" id="notificationSearchForm">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="notificationSearchInput" placeholder="Search by title or content..." 
                           class="filter-input">
                    <button type="button" class="filter-clear-btn" id="notificationSearchClear" style="display: none;" onclick="clearNotificationSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <select id="notificationTypeFilter" class="filter-select" onchange="loadNotifications(1)">
                        <option value="">All Types</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                        <option value="reminder">Reminder</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <select id="notificationPriorityFilter" class="filter-select" onchange="loadNotifications(1)">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div id="notificationsList" class="table-container">
        <p>Loading...</p>
    </div>
</div>

<!-- Create/Edit Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create Notification</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="notificationForm">
                <input type="hidden" id="notificationId">
                
                <div class="form-group">
                    <label for="notificationTitle">Title <span class="required">*</span></label>
                    <input type="text" id="notificationTitle" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="notificationMessage">Message <span class="required">*</span></label>
                    <textarea id="notificationMessage" class="form-control" rows="5" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="notificationType">Type <span class="required">*</span></label>
                        <select id="notificationType" class="form-control" required>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notificationPriority">Priority</label>
                        <select id="notificationPriority" class="form-control">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notificationTargetAudience">Target Audience <span class="required">*</span></label>
                    <select id="notificationTargetAudience" class="form-control" required onchange="toggleTargetUsers()">
                        <option value="all">All Users</option>
                        <option value="students">Students Only</option>
                        <option value="staff">Staff Only</option>
                        <option value="admins">Admins Only</option>
                        <option value="specific">Specific Users</option>
                    </select>
                </div>
                
                <div class="form-group" id="targetUsersGroup" style="display: none;">
                    <label for="targetUserIds">Select Users</label>
                    <input type="text" id="targetUserIds" class="form-control" placeholder="Enter user IDs separated by commas">
                    <small class="form-text">Example: 1,2,3</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="notificationIsActive" checked>
                        Active
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="submitBtn">Create Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/notifications/index.css') }}">
<script src="{{ asset('js/admin/notifications/index.js') }}"></script>
@endsection


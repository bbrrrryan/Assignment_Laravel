{{-- Author: Liew Zi Li (user management) --}}
@extends('layouts.app')

@section('title', 'User Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>User Management</h1>
            <p>Manage all users in the system</p>
        </div>
        <div>
            <a href="{{ route('admin.users.export-csv') }}" class="btn-header-white">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning">
            <strong>Import Warnings:</strong>
            <ul>
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- CSV Upload Section -->
    <div class="csv-upload-section">
        <div class="csv-upload-card">
            <h3><i class="fas fa-file-upload"></i> Bulk Import Users (CSV)</h3>
            <form id="csvUploadForm" method="POST" action="{{ route('admin.users.upload-csv') }}" enctype="multipart/form-data" class="csv-form">
                @csrf
                <div class="csv-form-group">
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="csv-file-input" id="csvFileInput">
                    <small class="csv-help-text">
                        <strong>CSV Format:</strong> name, email, password, role, phone_number, address, status<br>
                        <strong>Example:</strong> John Doe, john@example.com, password123, student, 0123456789, Address, active
                    </small>
                </div>
                <button type="submit" class="btn-csv-upload" id="csvUploadBtn">
                    <i class="fas fa-upload"></i> Upload CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <form method="GET" action="{{ route('admin.users.index') }}" class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" name="search" id="userSearchInput" placeholder="Search by name or email..." 
                           value="{{ request('search') }}" class="filter-input">
                    <button type="button" class="filter-clear-btn" id="userSearchClear" style="display: none;" onclick="clearUserSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select name="status" id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <select name="role" id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
                
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="id">ID <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="name">Name <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="email">Email <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="role">Role <i class="sort-icon"></i></th>
                    <th class="sortable" data-sort="status">Status <i class="sort-icon"></i></th>
                    <th>Phone</th>
                    <th class="sortable" data-sort="created_at">Joined <i class="sort-icon"></i></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge badge-info">
                                {{ ucfirst($user->role ?? '-') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->phone_number ?? '-' }}</td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">no users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Loading Indicator -->
    <div id="searchLoading" style="display: none; text-align: center; padding: 20px;">
        <i class="fas fa-spinner fa-spin"></i> Searching now...
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper" id="paginationWrapper">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/users/index.css') }}">
<script>
    window.userUploadCsvRoute = '{{ route("admin.users.upload-csv") }}';
</script>
<script src="{{ asset('js/users/index.js') }}"></script>
@endsection
@extends('layouts.app')

@section('title', 'User Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1>User Management</h1>
            <p>Manage system users and their roles</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i> Add New User
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

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" action="{{ route('admin.users.index') }}" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by name or email..." 
                       value="{{ request('search') }}" class="form-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="deactivated" {{ request('status') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            
            <button type="submit" class="btn-secondary">
                <i class="fas fa-search"></i> Filter
            </button>
            
            @if(request()->hasAny(['search', 'status', 'role']))
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <!-- CSV Upload -->
    <div class="csv-upload-section">
        <form method="POST" action="{{ route('admin.users.upload-csv') }}" enctype="multipart/form-data" class="csv-form">
            @csrf
            <div class="form-group">
                <label>Bulk Upload Users (CSV)</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required>
                <small>Format: name,email,password,role,phone_number,address,status</small>
            </div>
            <button type="submit" class="btn-secondary">
                <i class="fas fa-upload"></i> Upload CSV
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
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
                            <span class="badge badge-{{ $user->status === 'active' ? 'success' : ($user->status === 'suspended' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->phone_number ?? '-' }}</td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" 
                                      style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        {{ $users->links() }}
    </div>
</div>
@endsection

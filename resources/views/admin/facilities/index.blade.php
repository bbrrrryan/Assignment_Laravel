/**
 * Author: Ng Jhun Hou
 */ 
@extends('layouts.app')

@section('title', 'Facility Management - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Facility Management</h1>
            <p>Manage all facilities in the system</p>
        </div>
        <div>
            <a href="{{ route('admin.facilities.create') }}" class="btn-header-white">
                <i class="fas fa-plus"></i> Add New Facility
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

    <!-- Search and Filters -->
    <div class="filters-section">
        <div class="filters-card">
            <form method="GET" action="{{ route('admin.facilities.index') }}" class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" name="search" placeholder="Search by name, code or location..." 
                           value="{{ request('search') }}" class="filter-input">
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <select name="type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="classroom" {{ request('type') === 'classroom' ? 'selected' : '' }}>Classroom</option>
                        <option value="laboratory" {{ request('type') === 'laboratory' ? 'selected' : '' }}>Laboratory</option>
                        <option value="sports" {{ request('type') === 'sports' ? 'selected' : '' }}>Sports</option>
                        <option value="auditorium" {{ request('type') === 'auditorium' ? 'selected' : '' }}>Auditorium</option>
                        <option value="library" {{ request('type') === 'library' ? 'selected' : '' }}>Library</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                        <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="unavailable" {{ request('status') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
                
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('admin.facilities.index') }}" class="btn-clear">
                        <i class="fas fa-times"></i>
                        <span>Clear</span>
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Facilities Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>
                        @php
                            $currentSortBy = request('sort_by', 'id');
                            $currentSortOrder = request('sort_order', 'asc');
                            $isIdSorted = ($currentSortBy === 'id');
                            $nextOrder = ($isIdSorted && $currentSortOrder === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.facilities.index', array_merge(request()->except(['sort_by', 'sort_order', 'page']), ['sort_by' => 'id', 'sort_order' => $nextOrder])) }}" class="sort-link">
                            ID
                            @if($isIdSorted)
                                <i class="fas fa-sort-{{ $currentSortOrder === 'asc' ? 'up' : 'down' }}"></i>
                            @else
                                <i class="fas fa-sort sort-inactive"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php
                            $isNameSorted = (request('sort_by') === 'name');
                            $nameNextOrder = ($isNameSorted && request('sort_order') === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.facilities.index', array_merge(request()->except(['sort_by', 'sort_order', 'page']), ['sort_by' => 'name', 'sort_order' => $nameNextOrder])) }}" class="sort-link">
                            Name
                            @if($isNameSorted)
                                <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                            @else
                                <i class="fas fa-sort sort-inactive"></i>
                            @endif
                        </a>
                    </th>
                    <th>Code</th>
                    <th>
                        @php
                            $isTypeSorted = (request('sort_by') === 'type');
                            $typeNextOrder = ($isTypeSorted && request('sort_order') === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.facilities.index', array_merge(request()->except(['sort_by', 'sort_order', 'page']), ['sort_by' => 'type', 'sort_order' => $typeNextOrder])) }}" class="sort-link">
                            Type
                            @if($isTypeSorted)
                                <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                            @else
                                <i class="fas fa-sort sort-inactive"></i>
                            @endif
                        </a>
                    </th>
                    <th>Location</th>
                    <th>
                        @php
                            $isCapacitySorted = (request('sort_by') === 'capacity');
                            $capacityNextOrder = ($isCapacitySorted && request('sort_order') === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.facilities.index', array_merge(request()->except(['sort_by', 'sort_order', 'page']), ['sort_by' => 'capacity', 'sort_order' => $capacityNextOrder])) }}" class="sort-link">
                            Capacity
                            @if($isCapacitySorted)
                                <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                            @else
                                <i class="fas fa-sort sort-inactive"></i>
                            @endif
                        </a>
                    </th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facilities as $facility)
                    <tr>
                        <td>{{ $facility->id }}</td>
                        <td>{{ $facility->name }}</td>
                        <td>{{ $facility->code }}</td>
                        <td>
                            <span class="badge badge-info">
                                {{ ucfirst($facility->type) }}
                            </span>
                        </td>
                        <td>{{ $facility->location }}</td>
                        <td>{{ $facility->capacity }} people</td>
                        <td>
                            <span class="badge badge-{{ $facility->status === 'available' ? 'success' : ($facility->status === 'maintenance' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($facility->status) }}
                            </span>
                        </td>
                        <td>{{ $facility->created_at->format('Y-m-d') }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.facilities.show', $facility->id) }}" class="btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.facilities.edit', $facility->id) }}" class="btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn-sm btn-danger" title="Delete" onclick="showDeleteModal({{ $facility->id }}, '{{ $facility->name }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No facilities found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper">
        {{ $facilities->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
            <span class="modal-close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this facility?</p>
            <p><strong>Facility Name:</strong> <span id="deleteFacilityName"></span></p>
        </div>
        <div class="modal-footer">
            <form id="deleteForm" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-danger" style="padding: 10px 20px !important;">
                    <i class="fas fa-trash"></i> Delete Facility
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Container Styling - Expanded Width */
.container {
    max-width: 95% !important;
    margin: 0 auto;
    padding: 0 20px;
}

/* Page Header Styling */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    width: 100%;
}

.page-header-content {
    padding: 10px 0;
}

.page-header-content h1 {
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

/* Filters Section Styling */
.filters-section {
    margin-bottom: 30px;
    width: 100%;
}

.filters-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    width: 100%;
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-input-wrapper,
.filter-select-wrapper {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.filter-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 1;
    pointer-events: none;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-input::placeholder {
    color: #adb5bd;
}

.filter-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Search Button Styling */
.btn-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #ffffff;
    color: #cb2d3e;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    white-space: nowrap;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    color: white;
}

.btn-search:active {
    transform: translateY(0);
}

.btn-search i {
    font-size: 0.9rem;
}
.btn-header-white {
    background-color: #ffffff;
    color: #cb2d3e; 
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

/* Clear Button Styling */
.btn-clear {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #ffffff;
    color: #6c757d;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-clear:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-clear:active {
    transform: translateY(0);
}

.btn-clear i {
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .container {
        max-width: 98%;
    }
}

@media (max-width: 1200px) {
    .data-table {
        min-width: 1000px;
    }
}

@media (max-width: 768px) {
    .container {
        max-width: 100%;
        padding: 0 15px;
    }
    
    .filters-form {
        flex-direction: column;
    }
    
    .filter-input-wrapper,
    .filter-select-wrapper {
        width: 100%;
        min-width: 100%;
    }
    
    .btn-search,
    .btn-clear {
        width: 100%;
        justify-content: center;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
    
    .page-header-content h1 {
        font-size: 1.8rem;
    }
    
    .table-container {
        overflow-x: auto;
    }
}

/* Table Container Enhancement */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid #e9ecef;
    width: 100%;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    min-width: 1200px;
}

.data-table th,
.data-table td {
    padding: 15px 20px;
}


.data-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.data-table th {
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

/* Custom Pagination Styling */
.pagination-wrapper {
    margin-top: 30px;
    display: flex;
    justify-content: center; 
    padding-bottom: 40px;
}

.pagination-wrapper p { display: none; }


.pagination {
    display: flex;
    gap: 8px;
    align-items: center;
    border-radius: 0;
    margin: 0;
}

.page-item {
    margin: 0;
    border: none;
}

.page-item .page-link {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 15px;
    font-size: 1.2rem;
    font-weight: 600;
    color: #6c757d;
    background-color: #ffffff;
    border: 1px solid #e9ecef; 
    border-radius: 8px !important; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}


.page-item:first-child .page-link,
.page-item:last-child .page-link {
    border-radius: 8px !important;
}


.page-item:not(.active):not(.disabled) .page-link:hover {
    background-color: #f8f9fa;
    color: #667eea; 
    transform: translateY(-2px); 
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.15);
    border-color: #667eea;
    z-index: 2;
    text-decoration: none;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
    transform: scale(1.05);
}

.page-item.disabled .page-link {
    background-color: #f1f3f5;
    color: #adb5bd;
    border-color: #e9ecef;
    opacity: 0.7;
    pointer-events: none;
    box-shadow: none;
}

/* Sort Link Styling */
.sort-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #495057;
    text-decoration: none;
    transition: color 0.3s ease;
}

.sort-link:hover {
    color: #cb2d3e;
    text-decoration: none;
}

.sort-link i {
    font-size: 0.75rem;
    transition: color 0.3s ease;
}

.sort-link:hover i {
    color: #cb2d3e;
}

.sort-inactive {
    color: #adb5bd;
    opacity: 0.5;
}

.sort-link .fa-sort-up,
.sort-link .fa-sort-down {
    color: #cb2d3e;
    opacity: 1;
}

.page-link svg {
    width: 16px;
    height: 16px;
}


@media (max-width: 576px) {
    .page-item .page-link {
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        font-size: 0.85rem;
    }
    .pagination {
        gap: 5px;
    }
}

/* Delete Modal Styling */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 2px solid #f1f3f5;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header h3 i {
    color: #dc3545;
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    color: #6c757d;
    cursor: pointer;
    transition: color 0.3s;
}

.modal-close:hover {
    color: #dc3545;
}

.modal-body {
    padding: 25px;
}

.modal-body p {
    margin: 0 0 15px 0;
    color: #495057;
    line-height: 1.6;
}

.modal-body p:last-child {
    margin-bottom: 0;
}

.modal-body strong {
    color: #2c3e50;
}

.text-warning {
    color: #856404;
    background-color: #fff3cd;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid #ffc107;
}

.text-warning i {
    margin-right: 8px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 2px solid #f1f3f5;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: #ffffff;
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    box-shadow: 0 4px 6px rgba(220, 53, 69, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(220, 53, 69, 0.4);
}
</style>

<script>
function showDeleteModal(facilityId, facilityName) {
    const modal = document.getElementById('deleteModal');
    const form = document.getElementById('deleteForm');
    const nameSpan = document.getElementById('deleteFacilityName');
    
    nameSpan.textContent = facilityName;
    form.action = '{{ route("admin.facilities.destroy", ":id") }}'.replace(':id', facilityId);
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
}
</script>
@endsection

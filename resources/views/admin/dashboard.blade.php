@extends('layouts.app')

@section('title', 'Admin Dashboard - TARUMT FMS')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    <!-- User Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-users"></i> User Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_users'] }}</h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['active_users'] }}</h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['inactive_users'] }}</h3>
                    <p>Inactive Users</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking & Scheduling Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-calendar-check"></i> Booking & Scheduling Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_bookings'] }}</h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['pending_bookings'] }}</h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['approved_bookings'] }}</h3>
                    <p>Approved Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ \App\Models\Booking::where('status', 'rejected')->count() }}</h3>
                    <p>Rejected Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ \App\Models\Booking::where('status', 'cancelled')->count() }}</h3>
                    <p>Cancelled Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ \App\Models\Booking::where('status', 'completed')->count() }}</h3>
                    <p>Completed Bookings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Reports & Statistics Section -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-chart-bar"></i> Booking Reports & Usage Statistics
        </h2>
        
        <!-- Date Range Filter -->
        <div class="reports-filter-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="reportStartDate" class="form-label">Start Date</label>
                    <input type="date" id="reportStartDate" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="reportEndDate" class="form-label">End Date</label>
                    <input type="date" id="reportEndDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="reportFacility" class="form-label">Facility (Optional)</label>
                    <select id="reportFacility" class="form-select">
                        <option value="">All Facilities</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-primary" id="generateReportsBtn" style="width: 100%;">
                            <i class="fas fa-sync"></i> Generate Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Tabs -->
        <ul class="nav nav-tabs" id="reportsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                    <i class="fas fa-file-alt"></i> Booking Reports
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab">
                    <i class="fas fa-chart-line"></i> Usage Statistics
                </button>
            </li>
        </ul>

        <div class="tab-content" id="reportsTabContent">
            <!-- Booking Reports Tab -->
            <div class="tab-pane fade show active" id="reports" role="tabpanel">
                <div class="reports-container" style="padding: 20px; background: white; border-radius: 0 0 8px 8px;">
                    <div id="bookingReportsContent">
                        <div class="text-center" style="padding: 40px;">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-3">Loading booking reports...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics Tab -->
            <div class="tab-pane fade" id="statistics" role="tabpanel">
                <div class="statistics-container" style="padding: 20px; background: white; border-radius: 0 0 8px 8px;">
                    <div id="usageStatisticsContent">
                        <div class="text-center" style="padding: 40px;">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-3">Loading usage statistics...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facility Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-building"></i> Facility Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_facilities'] }}</h3>
                    <p>Total Facilities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['active_facilities'] }}</h3>
                    <p>Available Facilities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['maintenance_facilities'] }}</h3>
                    <p>Under Maintenance</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-comment-dots"></i> Feedback Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_feedbacks'] }}</h3>
                    <p>Total Feedbacks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['pending_feedbacks'] }}</h3>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['blocked_feedbacks'] }}</h3>
                    <p>Blocked Feedbacks</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loyalty Management Module Stats -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-star"></i> Loyalty Management Module
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['total_loyalty_points']) }}</h3>
                    <p>Total Points Issued</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_rewards'] }}</h3>
                    <p>Total Rewards</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <h3>{{ $stats['total_certificates'] }}</h3>
                    <p>Certificates Issued</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="dashboard-content">
        <!-- Recent Bookings -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Bookings</h2>
                <a href="{{ route('bookings.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->user->name }}</td>
                                <td>{{ $booking->facility->name ?? '-' }}</td>
                                <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $booking->status === 'approved' ? 'success' : ($booking->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Feedbacks -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Feedbacks</h2>
                <a href="{{ route('feedbacks.index') }}" class="btn-primary">View All</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentFeedbacks as $feedback)
                            <tr>
                                <td>{{ $feedback->user->name ?? '-' }}</td>
                                <td>{{ Str::limit($feedback->subject ?? 'No subject', 30) }}</td>
                                <td>
                                    @if($feedback->rating)
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $feedback->rating)
                                                <i class="fas fa-star" style="color: #ffc107;"></i>
                                            @else
                                                <i class="far fa-star" style="color: #ddd;"></i>
                                            @endif
                                        @endfor
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($feedback->is_blocked)
                                        <span class="badge badge-danger">Blocked</span>
                                    @else
                                        <span class="badge badge-info">{{ ucfirst($feedback->status ?? 'pending') }}</span>
                                    @endif
                                </td>
                                <td>{{ $feedback->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No feedbacks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Booking Reports & Statistics JavaScript -->
<script>
// Load facilities for filter
async function loadFacilitiesForReport() {
    try {
        const result = await API.get('/facilities');
        if (result.success && result.data) {
            // Handle paginated response - facilities are in result.data.data.data
            let facilities = [];
            if (result.data.data) {
                // Check if it's a paginated response
                if (Array.isArray(result.data.data)) {
                    facilities = result.data.data;
                } else if (result.data.data.data && Array.isArray(result.data.data.data)) {
                    // Paginated response
                    facilities = result.data.data.data;
                } else if (result.data.data && Array.isArray(result.data)) {
                    facilities = result.data;
                }
            }
            
            const select = document.getElementById('reportFacility');
            if (select && Array.isArray(facilities)) {
                facilities.forEach(facility => {
                    const option = document.createElement('option');
                    option.value = facility.id;
                    option.textContent = facility.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        // Silently fail - facilities filter is optional
    }
}

// Load booking reports
async function loadBookingReports() {
    const startDate = document.getElementById('reportStartDate')?.value;
    const endDate = document.getElementById('reportEndDate')?.value;
    const facilityId = document.getElementById('reportFacility')?.value || '';
    const container = document.getElementById('bookingReportsContent');

    if (!startDate || !endDate) {
        showError('Please select both start and end dates');
        return;
    }

    // Show loading
    if (container) {
        container.innerHTML = '<div class="text-center" style="padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-3">Loading booking reports...</p></div>';
    }

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
    });
    if (facilityId) {
        params.append('facility_id', facilityId);
    }

    try {
        const result = await API.get(`/bookings/reports?${params.toString()}`);
        
        if (result.success) {
            if (result.data && result.data.data) {
                displayBookingReports(result.data.data);
            } else if (result.data) {
                displayBookingReports(result.data);
            } else {
                const errorMsg = 'No data received from server';
                if (container) {
                    container.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
                } else {
                    showError(errorMsg);
                }
            }
        } else {
            const errorMsg = result.error || result.data?.message || 'Failed to load booking reports';
            if (container) {
                container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
            } else {
                showError(errorMsg);
            }
        }
    } catch (error) {
        const errorMsg = 'Error loading booking reports: ' + (error.message || 'Unknown error');
        if (container) {
            container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
        } else {
            showError(errorMsg);
        }
    }
}

// Display booking reports
function displayBookingReports(data) {
    const container = document.getElementById('bookingReportsContent');
    if (!container) return;
    
    if (!data) {
        container.innerHTML = '<div class="alert alert-warning">No data received</div>';
        return;
    }
    
    if (!data.status_stats) {
        data.status_stats = {
            pending: 0,
            approved: 0,
            rejected: 0,
            cancelled: 0,
            completed: 0
        };
    }

    let html = `
        <div class="row g-4">
            <!-- Status Statistics -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Booking Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #fff3cd; border-radius: 8px;">
                                    <h3>${data.status_stats.pending || 0}</h3>
                                    <p class="mb-0">Pending</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #d1e7dd; border-radius: 8px;">
                                    <h3>${data.status_stats.approved || 0}</h3>
                                    <p class="mb-0">Approved</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #f8d7da; border-radius: 8px;">
                                    <h3>${data.status_stats.rejected || 0}</h3>
                                    <p class="mb-0">Rejected</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #d3d3d3; border-radius: 8px;">
                                    <h3>${data.status_stats.cancelled || 0}</h3>
                                    <p class="mb-0">Cancelled</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #cfe2ff; border-radius: 8px;">
                                    <h3>${data.status_stats.completed || 0}</h3>
                                    <p class="mb-0">Completed</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3" style="background: #e7f3ff; border-radius: 8px;">
                                    <h3>${data.total_hours_booked || 0}</h3>
                                    <p class="mb-0">Total Hours</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings by Date -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-day"></i> Bookings by Date</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Pending</th>
                                        <th>Approved</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (data.bookings_by_date && data.bookings_by_date.length > 0) {
        data.bookings_by_date.forEach(item => {
            html += `
                <tr>
                    <td>${new Date(item.date).toLocaleDateString()}</td>
                    <td>${item.pending || 0}</td>
                    <td>${item.approved || 0}</td>
                    <td><strong>${item.total || 0}</strong></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="4" class="text-center">No data available</td></tr>';
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings by Facility -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-building"></i> Bookings by Facility</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Facility</th>
                                        <th>Total Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (data.bookings_by_facility && data.bookings_by_facility.length > 0) {
        data.bookings_by_facility.forEach(item => {
            html += `
                <tr>
                    <td>${item.facility_name}</td>
                    <td><strong>${item.total_bookings || 0}</strong></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="2" class="text-center">No data available</td></tr>';
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

// Load usage statistics
async function loadUsageStatistics() {
    const startDate = document.getElementById('reportStartDate')?.value;
    const endDate = document.getElementById('reportEndDate')?.value;
    const facilityId = document.getElementById('reportFacility')?.value || '';
    const container = document.getElementById('usageStatisticsContent');

    if (!startDate || !endDate) {
        return; // Error already shown in loadBookingReports
    }

    // Show loading
    if (container) {
        container.innerHTML = '<div class="text-center" style="padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-3">Loading usage statistics...</p></div>';
    }

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
    });
    if (facilityId) {
        params.append('facility_id', facilityId);
    }

    try {
        const result = await API.get(`/bookings/usage-statistics?${params.toString()}`);
        
        if (result.success) {
            if (result.data && result.data.data) {
                displayUsageStatistics(result.data.data);
            } else if (result.data) {
                displayUsageStatistics(result.data);
            } else {
                const errorMsg = 'No data received from server';
                if (container) {
                    container.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
                } else {
                    showError(errorMsg);
                }
            }
        } else {
            const errorMsg = result.error || result.data?.message || 'Failed to load usage statistics';
            if (container) {
                container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
            } else {
                showError(errorMsg);
            }
        }
    } catch (error) {
        const errorMsg = 'Error loading usage statistics: ' + (error.message || 'Unknown error');
        if (container) {
            container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${errorMsg}</div>`;
        } else {
            showError(errorMsg);
        }
    }
}

// Display usage statistics
function displayUsageStatistics(data) {
    const container = document.getElementById('usageStatisticsContent');
    if (!container) return;

    let html = `
        <div class="row g-4">
            <!-- Facility Utilization -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-area"></i> Facility Utilization</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 500px; overflow-y: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Facility</th>
                                        <th>Hours Booked</th>
                                        <th>Possible Hours</th>
                                        <th>Utilization Rate</th>
                                        <th>Total Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (data.facility_utilization && data.facility_utilization.length > 0) {
        data.facility_utilization.forEach(item => {
            const utilizationColor = item.utilization_rate > 80 ? 'danger' : 
                                    item.utilization_rate > 60 ? 'warning' : 'success';
            html += `
                <tr>
                    <td><strong>${item.facility_name}</strong></td>
                    <td>${item.total_hours_booked || 0}</td>
                    <td>${item.total_possible_hours || 0}</td>
                    <td>
                        <span class="badge badge-${utilizationColor}">${item.utilization_rate || 0}%</span>
                    </td>
                    <td>${item.total_bookings || 0}</td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="5" class="text-center">No data available</td></tr>';
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Facilities -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-star"></i> Most Popular Facilities</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Facility</th>
                                        <th>Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (data.popular_facilities && data.popular_facilities.length > 0) {
        data.popular_facilities.forEach((item, index) => {
            html += `
                <tr>
                    <td>${index + 1}. ${item.facility_name}</td>
                    <td><strong>${item.booking_count || 0}</strong></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="2" class="text-center">No data available</td></tr>';
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Most Active Users</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (data.active_users && data.active_users.length > 0) {
        data.active_users.forEach((item, index) => {
            html += `
                <tr>
                    <td>${index + 1}. ${item.user_name}</td>
                    <td><strong>${item.booking_count || 0}</strong></td>
                </tr>
            `;
        });
    } else {
        html += '<tr><td colspan="2" class="text-center">No data available</td></tr>';
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Summary Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #e7f3ff; border-radius: 8px;">
                                    <h4>${data.average_booking_duration || 0}</h4>
                                    <p class="mb-0">Average Booking Duration (Hours)</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #fff3cd; border-radius: 8px;">
                                    <h4>${data.popular_facilities && data.popular_facilities.length > 0 ? data.popular_facilities[0].facility_name : 'N/A'}</h4>
                                    <p class="mb-0">Most Popular Facility</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3" style="background: #d1e7dd; border-radius: 8px;">
                                    <h4>${data.active_users && data.active_users.length > 0 ? data.active_users[0].user_name : 'N/A'}</h4>
                                    <p class="mb-0">Most Active User</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
}

// Helper function to show error
function showError(message) {
    if (typeof showToast !== 'undefined') {
        showToast(message, 'error');
    } else {
        alert(message);
    }
}

// Load reports when tab is clicked
document.addEventListener('DOMContentLoaded', function() {
    // Load facilities for filter dropdown
    loadFacilitiesForReport();
    
    // Automatically load reports on page load
    // Wait a bit for the page to fully render
    setTimeout(function() {
        loadBookingReports();
        loadUsageStatistics();
    }, 500);
    
    // Load reports when Generate button is clicked
    const generateBtn = document.getElementById('generateReportsBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            loadBookingReports();
            loadUsageStatistics();
        });
    }

});
</script>
@endsection

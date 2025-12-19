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

    <!-- Booking Reports & Analytics Section -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-chart-bar"></i> Booking Reports & Analytics
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

        <!-- Reports Content -->
        <div class="reports-container" style="padding: 20px; background: white; border-radius: 8px;">
            <div id="bookingReportsContent">
                <div class="text-center" style="padding: 40px;">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-3">Loading booking reports...</p>
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

    <!-- Facility Reports & Analytics -->
    <div class="module-section">
        <h2 class="module-title">
            <i class="fas fa-chart-pie"></i> Facility Reports & Analytics
        </h2>
        <div class="reports-container" style="padding: 20px; background: white; border-radius: 8px;">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-tags"></i> Facilities by Type</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="facilityTypeChart" style="max-height: 320px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-traffic-light"></i> Facilities by Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="facilityStatusChart" style="max-height: 320px;"></canvas>
                        </div>
                    </div>
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
                <div class="section-header-title">
                    <i class="fas fa-history"></i>
                    <h2>Recent Bookings</h2>
                </div>
                <a href="{{ route('bookings.index') }}" class="btn-primary btn-primary-outline">View All</a>
            </div>
            <div class="table-container">
                <table class="data-table data-table-compact">
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
                                    <span class="status-badge status-{{ $booking->status }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Feedbacks -->
        <div class="dashboard-section">
            <div class="section-header">
                <div class="section-header-title">
                    <i class="fas fa-comment-alt"></i>
                    <h2>Recent Feedbacks</h2>
                </div>
                <a href="{{ route('admin.feedbacks.index') }}" class="btn-primary btn-primary-outline">View All</a>
            </div>
            <div class="table-container">
                <table class="data-table data-table-compact">
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
                                        <span class="rating-stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $feedback->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
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
                                <td colspan="5" class="text-center text-muted">No feedbacks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Booking Reports & Statistics JavaScript -->
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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
            <!-- Status Distribution Pie Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Booking Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bookings by Facility Bar Chart -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-building"></i> Bookings by Facility</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="facilityChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
    
    // Render charts after HTML is inserted
    setTimeout(() => {
        renderStatusChart(data.status_stats);
        renderFacilityChart(data.bookings_by_facility || []);
    }, 100);
}

// Chart instances storage
let statusChart = null;
let facilityChart = null;
let facilityTypeChart = null;
let facilityStatusChart = null;

// Render Status Distribution Pie Chart
function renderStatusChart(statusStats) {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (statusChart) {
        statusChart.destroy();
    }
    
    statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed'],
            datasets: [{
                data: [
                    statusStats.pending || 0,
                    statusStats.approved || 0,
                    statusStats.rejected || 0,
                    statusStats.cancelled || 0,
                    statusStats.completed || 0
                ],
                backgroundColor: [
                    '#fff3cd',
                    '#d1e7dd',
                    '#f8d7da',
                    '#d3d3d3',
                    '#cfe2ff'
                ],
                borderColor: [
                    '#ffc107',
                    '#28a745',
                    '#dc3545',
                    '#6c757d',
                    '#007bff'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed || 0;
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// Render Bookings by Facility Bar Chart
function renderFacilityChart(bookingsByFacility) {
    const ctx = document.getElementById('facilityChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (facilityChart) {
        facilityChart.destroy();
    }
    
    // Sort by bookings count (descending) and take top 10
    const sorted = [...bookingsByFacility].sort((a, b) => (b.total_bookings || 0) - (a.total_bookings || 0)).slice(0, 10);
    
    const facilities = sorted.map(item => item.facility_name || 'Unknown');
    const bookings = sorted.map(item => item.total_bookings || 0);
    
    facilityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: facilities,
            datasets: [{
                label: 'Total Bookings',
                data: bookings,
                backgroundColor: 'rgba(0, 123, 255, 0.6)',
                borderColor: '#007bff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Load facility reports
async function loadFacilityReports() {
    try {
        const result = await API.get('/facilities/reports/summary');
        if (result.success && result.data && result.data.data) {
            const data = result.data.data;
            renderFacilityTypeChart(data.by_type || []);
            renderFacilityStatusChart(data.by_status || []);
        }
    } catch (error) {
        // Silent fail; facility charts are optional
        console.error('Error loading facility reports:', error);
    }
}

// Render Facilities by Type Bar Chart
function renderFacilityTypeChart(items) {
    const ctx = document.getElementById('facilityTypeChart');
    if (!ctx) return;

    if (facilityTypeChart) {
        facilityTypeChart.destroy();
    }

    const labels = items.map(i => (i.type || 'Unknown').charAt(0).toUpperCase() + (i.type || 'Unknown').slice(1));
    const values = items.map(i => i.total || 0);

    facilityTypeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Facilities',
                data: values,
                backgroundColor: 'rgba(163, 31, 55, 0.7)',
                borderColor: '#a31f37',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                    }
                }
            }
        }
    });
}

// Render Facilities by Status Doughnut Chart
function renderFacilityStatusChart(items) {
    const ctx = document.getElementById('facilityStatusChart');
    if (!ctx) return;

    if (facilityStatusChart) {
        facilityStatusChart.destroy();
    }

    const statusLabelMap = {
        available: 'Available',
        maintenance: 'Maintenance',
        unavailable: 'Unavailable',
        reserved: 'Reserved',
    };

    const labels = items.map(i => statusLabelMap[i.status] || (i.status || 'Unknown'));
    const values = items.map(i => i.total || 0);

    facilityStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#d4edda',
                    '#fff3cd',
                    '#f8d7da',
                    '#e2e3e5',
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
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
        loadFacilityReports();
    }, 500);
    
    // Load reports when Generate button is clicked
    const generateBtn = document.getElementById('generateReportsBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            loadBookingReports();
        });
    }

});
</script>
@endsection

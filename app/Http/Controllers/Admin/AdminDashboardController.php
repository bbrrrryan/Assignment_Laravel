<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Certificate;

class AdminDashboardController extends AdminBaseController
{
    public function index()
    {
        $stats['total_users'] = User::count();
        $stats['active_users'] = User::where('status', 'active')->count();
        $stats['inactive_users'] = User::where('status', 'inactive')->count();

        $stats['total_bookings'] = Booking::count();
        $stats['pending_bookings'] = Booking::where('status', 'pending')->count();
        $stats['approved_bookings'] = Booking::where('status', 'approved')->count();

        $stats['total_facilities'] = Facility::count();
        $stats['active_facilities'] = Facility::where('status', 'available')->count();
        $stats['maintenance_facilities'] = Facility::where('status', 'maintenance')->count();

        $stats['total_feedbacks'] = Feedback::count();
        $stats['pending_feedbacks'] = Feedback::where('status', 'pending')->count();
        $stats['blocked_feedbacks'] = Feedback::where('is_blocked', true)->count();

        $stats['total_loyalty_points'] = LoyaltyPoint::sum('points') ?? 0;
        $stats['total_rewards'] = Reward::count();
        $stats['total_certificates'] = Certificate::count();

        $recentBookings = Booking::with(['user', 'facility'])->latest()->limit(5)->get();
        $recentFeedbacks = Feedback::with('user')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'recentFeedbacks'));
    }

    public function getPendingItems(Request $request)
    {
        $limit = $request->get('limit', 10);

        $bookings = Booking::with(['user', 'facility'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'kind' => 'booking',
                    'title' => "Booking Request - {$booking->facility->name}",
                    'message' => "{$booking->user->name}: {$booking->booking_date->format('Y-m-d')} {$booking->start_time->format('H:i')} - {$booking->end_time->format('H:i')}",
                    'facility_name' => $booking->facility->name ?? 'Unknown',
                    'user_name' => $booking->user->name ?? 'Unknown',
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                    'created_at' => $booking->created_at->toIso8601String(),
                ];
            });

        $feedbacks = Feedback::with(['user', 'facility'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($feedback) {
                $facilityText = $feedback->facility && $feedback->facility->name !== 'N/A' 
                    ? " - {$feedback->facility->name}" 
                    : '';
                return [
                    'id' => $feedback->id,
                    'kind' => 'feedback',
                    'title' => "Feedback - {$feedback->subject}",
                    'message' => ($feedback->user->name ?? 'Unknown') . " ({$feedback->type})" . $facilityText,
                    'subject' => $feedback->subject,
                    'type' => $feedback->type,
                    'user_name' => $feedback->user->name ?? 'Unknown',
                    'facility_name' => $feedback->facility->name ?? 'N/A',
                    'created_at' => $feedback->created_at->toIso8601String(),
                ];
            });

        $combined = $bookings->concat($feedbacks);
        $sorted = $combined->sort(function ($a, $b) {
            $timestampA = strtotime($a['created_at']);
            $timestampB = strtotime($b['created_at']);
            return $timestampB <=> $timestampA;
        })->values();

        $bookingCount = Booking::where('status', 'pending')->count();
        $feedbackCount = Feedback::where('status', 'pending')->count();

        return response()->json([
            'status' => 'S',
            'message' => 'Pending items retrieved successfully',
            'data' => [
                'items' => $sorted->toArray(),
                'counts' => [
                    'bookings' => $bookingCount,
                    'feedbacks' => $feedbackCount,
                    'total' => $bookingCount + $feedbackCount,
                ],
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getBookingReports(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $facilityId = $request->input('facility_id');
        $status = $request->input('status');

        $query = Booking::with(['user', 'facility', 'slots'])
            ->whereBetween('booking_date', [$startDate, $endDate]);

        if ($facilityId) {
            $query->where('facility_id', $facilityId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $statusStats = [
            'pending' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->where('status', 'pending')->count(),
            'approved' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->where('status', 'approved')->count(),
            'rejected' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->where('status', 'rejected')->count(),
            'cancelled' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->where('status', 'cancelled')->count(),
            'completed' => Booking::whereBetween('booking_date', [$startDate, $endDate])
                ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
                ->where('status', 'completed')->count(),
        ];

        $bookingsByDate = Booking::selectRaw('DATE(booking_date) as date, COUNT(*) as count, status')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($group) {
                return [
                    'date' => $group->first()->date,
                    'pending' => $group->where('status', 'pending')->sum('count'),
                    'approved' => $group->where('status', 'approved')->sum('count'),
                    'rejected' => $group->where('status', 'rejected')->sum('count'),
                    'cancelled' => $group->where('status', 'cancelled')->sum('count'),
                    'completed' => $group->where('status', 'completed')->sum('count'),
                    'total' => $group->sum('count'),
                ];
            })
            ->values();

        $bookingsByFacility = Booking::selectRaw('facility_id, COUNT(*) as total_bookings')
            ->with('facility')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->groupBy('facility_id')
            ->orderByDesc('total_bookings')
            ->get()
            ->map(function ($item) {
                return [
                    'facility_id' => $item->facility_id,
                    'facility_name' => $item->facility->name ?? 'Unknown',
                    'total_bookings' => $item->total_bookings,
                ];
            });

        $totalHoursBooked = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->whereIn('status', ['approved', 'completed'])
            ->sum('duration_hours');

        $totalAttendees = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->whereIn('status', ['approved', 'completed'])
            ->sum('expected_attendees');

        return response()->json([
            'status' => 'S',
            'data' => [
                'status_stats' => $statusStats,
                'bookings_by_date' => $bookingsByDate,
                'bookings_by_facility' => $bookingsByFacility,
                'total_hours_booked' => $totalHoursBooked,
                'total_attendees' => $totalAttendees,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getUsageStatistics(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $facilityId = $request->input('facility_id');

        $facilities = Facility::when($facilityId, fn($q) => $q->where('id', $facilityId))->get();
        
        $facilityUtilization = $facilities->map(function ($facility) use ($startDate, $endDate) {
            $approvedBookings = Booking::where('facility_id', $facility->id)
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->whereIn('status', ['approved', 'completed'])
                ->get();

            $totalHoursBooked = $approvedBookings->sum('duration_hours');
            
            $days = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
            $totalPossibleHours = $days * 8;
            
            $utilizationRate = $totalPossibleHours > 0 
                ? round(($totalHoursBooked / $totalPossibleHours) * 100, 2) 
                : 0;

            $peakHours = BookingSlot::whereHas('booking', function($q) use ($facility, $startDate, $endDate) {
                    $q->where('facility_id', $facility->id)
                      ->whereBetween('booking_date', [$startDate, $endDate])
                      ->whereIn('status', ['approved', 'completed']);
                })
                ->selectRaw('SUBSTRING(start_time, 1, 2) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
                        'count' => $item->count,
                    ];
                });

            return [
                'facility_id' => $facility->id,
                'facility_name' => $facility->name,
                'total_hours_booked' => $totalHoursBooked,
                'total_possible_hours' => $totalPossibleHours,
                'utilization_rate' => $utilizationRate,
                'total_bookings' => $approvedBookings->count(),
                'peak_hours' => $peakHours,
            ];
        });

        $popularFacilities = Booking::selectRaw('facility_id, COUNT(*) as booking_count')
            ->with('facility')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'completed'])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->groupBy('facility_id')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'facility_id' => $item->facility_id,
                    'facility_name' => $item->facility->name ?? 'Unknown',
                    'booking_count' => $item->booking_count,
                ];
            });

        $weeklyTrends = Booking::selectRaw('YEARWEEK(booking_date) as week, COUNT(*) as count, status')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->groupBy('week', 'status')
            ->orderBy('week')
            ->get()
            ->groupBy('week')
            ->map(function ($group) {
                return [
                    'week' => $group->first()->week,
                    'pending' => $group->where('status', 'pending')->sum('count'),
                    'approved' => $group->where('status', 'approved')->sum('count'),
                    'rejected' => $group->where('status', 'rejected')->sum('count'),
                    'cancelled' => $group->where('status', 'cancelled')->sum('count'),
                    'completed' => $group->where('status', 'completed')->sum('count'),
                    'total' => $group->sum('count'),
                ];
            })
            ->values();

        $avgBookingDuration = Booking::whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->whereIn('status', ['approved', 'completed'])
            ->avg('duration_hours');

        $activeUsers = Booking::selectRaw('user_id, COUNT(*) as booking_count')
            ->with('user')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->whereIn('status', ['approved', 'completed'])
            ->groupBy('user_id')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user->name ?? 'Unknown',
                    'booking_count' => $item->booking_count,
                ];
            });

        return response()->json([
            'status' => 'S',
            'data' => [
                'facility_utilization' => $facilityUtilization,
                'popular_facilities' => $popularFacilities,
                'weekly_trends' => $weeklyTrends,
                'average_booking_duration' => round($avgBookingDuration ?? 0, 2),
                'active_users' => $activeUsers,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getFacilityReports(Request $request)
    {
        $byType = Facility::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderBy('type')
            ->get()
            ->map(function ($row) {
                return [
                    'type' => $row->type,
                    'total' => (int) $row->total,
                ];
            });

        $byStatus = Facility::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function ($row) {
                return [
                    'status' => $row->status,
                    'total' => (int) $row->total,
                ];
            });

        return response()->json([
            'status' => 'S',
            'data' => [
                'by_type' => $byType,
                'by_status' => $byStatus,
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}

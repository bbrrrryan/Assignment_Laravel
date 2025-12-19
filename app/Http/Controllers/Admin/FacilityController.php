<?php

namespace App\Http\Controllers\Admin;

use App\Models\Facility;
use App\Models\User;
use App\Factories\AnnouncementFactory;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacilityController extends AdminBaseController
{
    /**
     * Display a listing of facilities
     */
    public function index(Request $request)
    {
        $query = Facility::where('is_deleted', false);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id'); // Default sort by id
        $sortOrder = $request->get('sort_order', 'asc'); // Default ascending
        
        // Validate sort_by and sort_order
        $allowedSortFields = ['id', 'name', 'type', 'capacity'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $facilities = $query->paginate(15)->withQueryString();

        return view('admin.facilities.index', compact('facilities'));
    }

    /**
     * Show the form for creating a new facility
     */
    public function create()
    {
        return view('admin.facilities.create');
    }

    /**
     * Store a newly created facility
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:facilities,code',
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance,unavailable,reserved',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'enable_multi_attendees' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1|lte:capacity|required_if:enable_multi_attendees,1',
            'available_day' => 'nullable|array',
            'available_day.*' => 'nullable|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_time' => 'nullable|array',
            'available_time.start' => 'nullable|string|date_format:H:i',
            'available_time.end' => 'nullable|string|date_format:H:i|after:available_time.start',
            'equipment' => 'nullable|string',
            'equipment_json' => 'nullable|string',
            'equipment.*' => 'nullable|string|max:255',
            'rules' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/facilities');
            
            // Ensure directory exists
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Move uploaded file
            $image->move($destinationPath, $imageName);
            $validated['image_url'] = '/images/facilities/' . $imageName;
        }

        // Handle available_day - ensure it's an array or null
        if (isset($validated['available_day']) && is_array($validated['available_day'])) {
            $validated['available_day'] = !empty($validated['available_day']) ? array_values(array_filter($validated['available_day'])) : null;
        } else {
            $validated['available_day'] = null;
        }

        // Handle available_time - ensure it has start and end
        if (isset($validated['available_time']) && is_array($validated['available_time'])) {
            if (empty($validated['available_time']['start']) || empty($validated['available_time']['end'])) {
                $validated['available_time'] = null;
            }
        } else {
            $validated['available_time'] = null;
        }

        // Handle equipment - can be array, JSON string, or from hidden input
        if ($request->has('equipment_json') && !empty($request->equipment_json)) {
            $equipmentJson = json_decode($request->equipment_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($equipmentJson)) {
                $validated['equipment'] = array_filter($equipmentJson); // Remove empty values
            }
        } elseif (isset($validated['equipment'])) {
            if (is_string($validated['equipment'])) {
                $decoded = json_decode($validated['equipment'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validated['equipment'] = array_filter($decoded);
                } else {
                    $validated['equipment'] = null;
                }
            } elseif (is_array($validated['equipment'])) {
                $validated['equipment'] = array_filter($validated['equipment']); // Remove empty values
            }
        } else {
            $validated['equipment'] = null;
        }
        
        // Ensure equipment is null if empty array
        if (isset($validated['equipment']) && (empty($validated['equipment']) || (is_array($validated['equipment']) && count(array_filter($validated['equipment'])) === 0))) {
            $validated['equipment'] = null;
        }

        // Set default values
        $validated['status'] = $validated['status'] ?? 'available';
        $validated['max_booking_hours'] = $validated['max_booking_hours'] ?? 4;
        $validated['enable_multi_attendees'] = $validated['enable_multi_attendees'] ?? false;
        // If multi-attendees is disabled, set max_attendees to null
        if (!($validated['enable_multi_attendees'] ?? false)) {
            $validated['max_attendees'] = null;
        }

        // Set created_by and updated_by to current admin user
        $currentUserId = auth()->id();
        $validated['created_by'] = $currentUserId;
        $validated['updated_by'] = $currentUserId;

        // Create the facility
        $facility = Facility::create($validated);
        
        // Manually set timestamps to Malaysia timezone after creation using DB query
        $now = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        DB::table('facilities')
            ->where('id', $facility->id)
            ->update([
                'created_at' => $now,
                'updated_at' => $now
            ]);
        
        // Refresh the model to get updated timestamps
        $facility->refresh();

        // Create announcement for new facility
        $this->createFacilityAnnouncement($facility, 'new');

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility created successfully!');
    }

    /**
     * Display the specified facility
     */
    public function show(string $id)
    {
        $facility = Facility::where('is_deleted', false)
            ->with(['bookings', 'creator', 'updater'])
            ->findOrFail($id);

        // Check if facility has any bookings in the current month (for CSV export button)
        $hasBookingsThisMonth = $facility->bookings()
            ->whereBetween('booking_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->exists();

        return view('admin.facilities.show', compact('facility', 'hasBookingsThisMonth'));
    }

    /**
     * Show the form for editing the specified facility
     */
    public function edit(string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        return view('admin.facilities.edit', compact('facility'));
    }

    /**
     * Update the specified facility
     */
    public function update(Request $request, string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:facilities,code,' . $id,
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance,unavailable,reserved',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'enable_multi_attendees' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1|lte:capacity|required_if:enable_multi_attendees,1',
            'available_day' => 'nullable|array',
            'available_day.*' => 'nullable|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_time' => 'nullable|array',
            'available_time.start' => 'nullable|string|date_format:H:i',
            'available_time.end' => 'nullable|string|date_format:H:i|after:available_time.start',
            'equipment' => 'nullable|string',
            'equipment_json' => 'nullable|string',
            'equipment.*' => 'nullable|string|max:255',
            'rules' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($facility->image_url) {
                $oldImagePath = public_path($facility->image_url);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }

            // Upload new image
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/facilities');
            
            // Ensure directory exists
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Move uploaded file
            $image->move($destinationPath, $imageName);
            $validated['image_url'] = '/images/facilities/' . $imageName;
        }

        // Handle available_day - ensure it's an array or null
        if (isset($validated['available_day']) && is_array($validated['available_day'])) {
            $validated['available_day'] = !empty($validated['available_day']) ? array_values(array_filter($validated['available_day'])) : null;
        } else {
            $validated['available_day'] = null;
        }

        // Handle available_time - ensure it has start and end
        if (isset($validated['available_time']) && is_array($validated['available_time'])) {
            if (empty($validated['available_time']['start']) || empty($validated['available_time']['end'])) {
                $validated['available_time'] = null;
            }
        } else {
            $validated['available_time'] = null;
        }

        // Handle equipment - can be array, JSON string, or from hidden input
        if ($request->has('equipment_json') && !empty($request->equipment_json)) {
            $equipmentJson = json_decode($request->equipment_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($equipmentJson)) {
                $validated['equipment'] = array_filter($equipmentJson); // Remove empty values
            }
        } elseif (isset($validated['equipment'])) {
            if (is_string($validated['equipment'])) {
                $decoded = json_decode($validated['equipment'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validated['equipment'] = array_filter($decoded);
                } else {
                    $validated['equipment'] = null;
                }
            } elseif (is_array($validated['equipment'])) {
                $validated['equipment'] = array_filter($validated['equipment']); // Remove empty values
            }
        } else {
            $validated['equipment'] = null;
        }
        
        // Ensure equipment is null if empty array
        if (isset($validated['equipment']) && (empty($validated['equipment']) || (is_array($validated['equipment']) && count(array_filter($validated['equipment'])) === 0))) {
            $validated['equipment'] = null;
        }

        // Handle enable_multi_attendees and max_attendees
        $validated['enable_multi_attendees'] = $validated['enable_multi_attendees'] ?? false;
        // If multi-attendees is disabled, set max_attendees to null
        if (!($validated['enable_multi_attendees'] ?? false)) {
            $validated['max_attendees'] = null;
        }

        // Check if status is being changed
        $oldStatus = $facility->status;
        $newStatus = $validated['status'] ?? $oldStatus;

        // Set updated_by to current admin user
        $currentUserId = auth()->id();
        $validated['updated_by'] = $currentUserId;
        
        // Ensure created_by is set if it's null (shouldn't happen, but safety check)
        if (empty($facility->created_by)) {
            $validated['created_by'] = $currentUserId;
        }

        // Update the facility
        $facility->update($validated);
        
        // Manually set updated_at to Malaysia timezone after update using DB query
        $now = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        DB::table('facilities')
            ->where('id', $facility->id)
            ->update(['updated_at' => $now]);
        
        // Refresh the model to get updated timestamp
        $facility->refresh();

        // Create announcement if status changed to unavailable or maintenance
        if ($oldStatus !== $newStatus && in_array($newStatus, ['unavailable', 'maintenance'])) {
            $this->createFacilityAnnouncement($facility, $newStatus);
        }
        
        // Create announcement if status changed from unavailable or maintenance to available
        if ($oldStatus !== $newStatus && $newStatus === 'available' && in_array($oldStatus, ['unavailable', 'maintenance'])) {
            $this->createFacilityAnnouncement($facility, 'available');
        }

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility updated successfully!');
    }

    /**
     * Remove the specified facility (Soft delete)
     */
    public function destroy(string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $facility->update(['is_deleted' => true]);

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility deleted successfully!');
    }

    /**
     * Get facility utilization statistics
     */
    public function utilization(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        // Get utilization statistics using shared logic
        $stats = $this->calculateUtilizationStats($facility, $startDate, $endDate);

        // If this is an API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'status' => 'S', // IFA Standard
                'message' => 'Utilization statistics retrieved',
                'data' => [
                    'facility' => [
                        'id' => $stats['facility']->id,
                        'name' => $stats['facility']->name,
                        'capacity' => $stats['facility']->capacity,
                    ],
                    'period' => [
                        'start_date' => $stats['start_date'],
                        'end_date' => $stats['end_date'],
                    ],
                    'statistics' => [
                        'total_bookings' => $stats['total_bookings'],
                        'total_hours' => round($stats['total_hours'], 2),
                        'unique_users' => $stats['unique_users'],
                        'utilization_percentage' => round($stats['utilization_percentage'], 2),
                        'status_breakdown' => $stats['status_breakdown'],
                    ],
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
            ]);
        }

        // For web requests, you might want to return a view
        // For now, return JSON as well
        return response()->json([
            'status' => 'S',
            'data' => $stats,
        ]);
    }

    /**
     * Export facility bookings for a period as CSV
     */
    public function exportUtilizationCsv(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);

        // If no dates are provided, default to the current month
        $startInput = $request->get('start_date');
        $endInput = $request->get('end_date');

        if (!$startInput || !$endInput) {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        } else {
            $start = Carbon::parse($startInput)->startOfDay();
            $end = Carbon::parse($endInput)->endOfDay();
        }

        // Get all bookings for this facility within the date range (all statuses)
        $bookings = $facility->bookings()
            ->with('user')
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $filename = 'facility_bookings_' . $facility->id . '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings, $facility, $start, $end) {
            $handle = fopen('php://output', 'w');

            // CSV header (one row per booking)
            fputcsv($handle, [
                'booking_id',
                'facility_id',
                'facility_code',
                'facility_name',
                'user_id',
                'user_name',
                'user_email',
                'booking_date',
                'start_time',
                'end_time',
                'duration_hours',
                'expected_attendees',
                'status',
                'purpose',
                'created_at',
                'approved_at',
                'cancelled_at',
                'rejection_reason',
                'cancellation_reason',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $facility->id,
                    $facility->code,
                    $facility->name,
                    $booking->user_id,
                    optional($booking->user)->name,
                    optional($booking->user)->email,
                    $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                    $booking->start_time ? $booking->start_time->format('Y-m-d H:i:s') : null,
                    $booking->end_time ? $booking->end_time->format('Y-m-d H:i:s') : null,
                    $booking->duration_hours,
                    $booking->expected_attendees,
                    $booking->status,
                    $booking->purpose,
                    $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
                    $booking->approved_at ? $booking->approved_at->format('Y-m-d H:i:s') : null,
                    $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                    $booking->rejection_reason,
                    $booking->cancellation_reason,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Shared internal logic to calculate utilization statistics
     *
     * @param  \App\Models\Facility  $facility
     * @param  mixed  $startDate
     * @param  mixed  $endDate
     * @return array
     */
    private function calculateUtilizationStats(Facility $facility, $startDate, $endDate): array
    {
        // Normalize dates to Carbon instances for calculations,
        // but keep original values so callers can format them as needed.
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        // Get all approved bookings in the date range
        $bookings = $facility->bookings()
            ->whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get();

        $totalBookings = $bookings->count();
        $totalHours = $bookings->sum('duration_hours');
        $uniqueUsers = $bookings->pluck('user_id')->unique()->count();

        // Calculate utilization percentage (assuming facility is available 8 hours/day)
        $daysInRange = $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
        $maxPossibleHours = $daysInRange * 8; // Assuming 8 hours per day
        $utilizationPercentage = $maxPossibleHours > 0 ? ($totalHours / $maxPossibleHours) * 100 : 0;

        // Group by status for the same date range (all statuses)
        $statusBreakdown = $facility->bookings()
            ->whereBetween('booking_date', [$start, $end])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'facility' => $facility,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_bookings' => $totalBookings,
            'total_hours' => $totalHours,
            'unique_users' => $uniqueUsers,
            'utilization_percentage' => $utilizationPercentage,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /**
     * Create and publish announcement for facility events
     * 
     * @param Facility $facility
     * @param string $eventType 'new', 'unavailable', 'maintenance', or 'available'
     */
    private function createFacilityAnnouncement(Facility $facility, string $eventType)
    {
        try {
            $adminId = auth()->id();
            
            // Determine announcement details based on event type
            switch ($eventType) {
                case 'new':
                    $title = "New Facility Added: {$facility->name}";
                    $content = "\nWe are pleased to inform you that a new facility {$facility->name} (Code: {$facility->code}) is now available.";
                    $content .= "\n\nFacility Details:";
                    $content .= "\n- Type: " . ucfirst($facility->type);
                    $content .= "\n- Location: {$facility->location}";
                    $content .= "\n- Capacity: {$facility->capacity} people";
                    if ($facility->description) {
                        $content .= "\n- Description: {$facility->description}";
                    }
                    $content .= "\n\nYou can now start booking this facility.";
                    $type = 'success';
                    $priority = 'medium';
                    break;

                case 'unavailable':
                    $title = "Facility Temporarily Unavailable: {$facility->name}";
                    $content = "\nImportant Notice:\n The facility {$facility->name} (Code: {$facility->code}) is currently temporarily unavailable.";
                    $content .= "\n\nFacility Details:";
                    $content .= "\n- Location: {$facility->location}";
                    $content .= "\n- Status: Unavailable";
                    $content .= "\n\nWe are working to resolve this issue and will notify you when the facility becomes available again.";
                    $content .= "\nWe apologize for any inconvenience caused.";
                    $type = 'warning';
                    $priority = 'high';
                    break;

                case 'maintenance':
                    $title = "Facility Maintenance Notice: {$facility->name}";
                    $content = "\nImportant Notice:\n The facility {$facility->name} (Code: {$facility->code}) is currently under maintenance.";
                    $content .= "\n\nFacility Details:";
                    $content .= "\n- Location: {$facility->location}";
                    $content .= "\n- Status: Under Maintenance";
                    $content .= "\n\nDuring this period, the facility will be unavailable for booking. We will notify you promptly once maintenance is completed.";
                    $content .= "\nThank you for your understanding and cooperation.";
                    $type = 'warning';
                    $priority = 'high';
                    break;

                case 'available':
                    $title = "Facility Now Available: {$facility->name}";
                    $content = "\nGood News:\n The facility {$facility->name} (Code: {$facility->code}) is now available for booking again.";
                    $content .= "\n\nFacility Details:";
                    $content .= "\n- Type: " . ucfirst($facility->type);
                    $content .= "\n- Location: {$facility->location}";
                    $content .= "\n- Capacity: {$facility->capacity} people";
                    if ($facility->description) {
                        $content .= "\n- Description: {$facility->description}";
                    }
                    $content .= "\n- Status: Available";
                    $content .= "\n\nYou can now proceed to book this facility. We appreciate your patience.";
                    $type = 'success';
                    $priority = 'high';
                    break;

                default:
                    return; // Unknown event type, don't create announcement
            }

            // Create announcement using factory
            $announcement = AnnouncementFactory::makeAnnouncement(
                $type,
                $title,
                $content,
                'all', // Target all users
                $adminId,
                $priority,
                null, // No specific user IDs
                now(), // Publish immediately
                null, // No expiration
                true  // Active
            );

            // Publish announcement to all users
            $this->publishAnnouncementToAllUsers($announcement);

        } catch (\Exception $e) {
            // Log error but don't fail the facility operation
            \Log::error('Failed to create facility announcement', [
                'facility_id' => $facility->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish announcement to all active users
     * 
     * @param Announcement $announcement
     */
    private function publishAnnouncementToAllUsers(Announcement $announcement)
    {
        try {
            // Get all active users
            $targetUsers = User::where('status', 'active')->pluck('id')->toArray();

            if (empty($targetUsers)) {
                return;
            }

            // Attach announcement to all users
            $syncData = [];
            foreach ($targetUsers as $userId) {
                $syncData[$userId] = [
                    'is_read' => false,
                ];
            }

            $announcement->users()->sync($syncData);

            // Ensure published_at is set
            if (!$announcement->published_at) {
                $announcement->update(['published_at' => now()]);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to publish announcement to users', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


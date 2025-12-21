<?php
/**
 * Author: Ng Jhun Hou
 */
namespace App\Http\Controllers\Admin;

use App\Models\Facility;
use App\Models\User;
use App\Factories\AnnouncementFactory;
use App\Models\Announcement;
use App\Strategies\FacilityValidationContext;
use App\Services\UserWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FacilityController extends AdminBaseController
{
    protected $userWebService;

    public function __construct(UserWebService $userWebService)
    {
        $this->userWebService = $userWebService;
    }
    public function index(Request $request)
    {
        $query = Facility::where('is_deleted', false);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['id', 'name', 'type', 'capacity'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }
        
        $query->orderBy($sortBy, $sortOrder);

        $facilities = $query->paginate(15)->withQueryString();

        return view('admin.facilities.index', compact('facilities'));
    }

    public function create()
    {
        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $basicValidated = $request->validate([
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library,cafeteria,other',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $context = new FacilityValidationContext($basicValidated['type']);
        $data = $request->all();
        $validation = $context->validate($data);
        
        if (!$validation['valid']) {
            return back()->withErrors($validation['errors'])->withInput();
        }

        $defaults = $context->getDefaultValues();
        $validated = array_merge($defaults, $data);
        $validated = $context->processBeforeSave($validated);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/facilities');
            
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $image->move($destinationPath, $imageName);
            $validated['image_url'] = '/images/facilities/' . $imageName;
        }

        if (isset($validated['available_day']) && is_array($validated['available_day'])) {
            $validated['available_day'] = !empty($validated['available_day']) ? array_values(array_filter($validated['available_day'])) : null;
        } else {
            $validated['available_day'] = null;
        }

        if (isset($validated['available_time']) && is_array($validated['available_time'])) {
            if (empty($validated['available_time']['start']) || empty($validated['available_time']['end'])) {
                $validated['available_time'] = null;
            }
        } else {
            $validated['available_time'] = null;
        }

        if ($request->has('equipment_json') && !empty($request->equipment_json)) {
            $equipmentJson = json_decode($request->equipment_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($equipmentJson)) {
                $validated['equipment'] = array_filter($equipmentJson);
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
                $validated['equipment'] = array_filter($validated['equipment']);
            }
        } else {
            $validated['equipment'] = null;
        }
        
        if (isset($validated['equipment']) && (empty($validated['equipment']) || (is_array($validated['equipment']) && count(array_filter($validated['equipment'])) === 0))) {
            $validated['equipment'] = null;
        }

        $validated['status'] = $validated['status'] ?? 'available';

        $currentUserId = auth()->id();
        $validated['created_by'] = $currentUserId;
        $validated['updated_by'] = $currentUserId;

        $facility = Facility::create($validated);
        
        $now = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        DB::table('facilities')
            ->where('id', $facility->id)
            ->update([
                'created_at' => $now,
                'updated_at' => $now
            ]);
        
        $facility->refresh();

        $this->createFacilityAnnouncement($facility, 'new');

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility created successfully!');
    }

    public function show(string $id)
    {
        $facility = Facility::where('is_deleted', false)
            ->with(['bookings'])
            ->findOrFail($id);

        $hasBookingsThisMonth = $facility->bookings()
            ->whereBetween('booking_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->exists();

        $creatorInfo = null;
        $updaterInfo = null;

        if ($facility->created_by) {
            try {
                $creatorInfo = $this->userWebService->getUserInfo($facility->created_by);
            } catch (\Exception $e) {
                Log::error('Failed to fetch creator info via Web Service in facility show', [
                    'facility_id' => $id,
                    'created_by' => $facility->created_by,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($facility->updated_by) {
            try {
                $updaterInfo = $this->userWebService->getUserInfo($facility->updated_by);
            } catch (\Exception $e) {
                Log::error('Failed to fetch updater info via Web Service in facility show', [
                    'facility_id' => $id,
                    'updated_by' => $facility->updated_by,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.facilities.show', compact('facility', 'hasBookingsThisMonth', 'creatorInfo', 'updaterInfo'));
    }

    public function edit(string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        return view('admin.facilities.edit', compact('facility'));
    }

    public function update(Request $request, string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);

        $basicValidated = $request->validate([
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library,cafeteria,other',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $facilityType = $basicValidated['type'] ?? $facility->type;
        $context = new FacilityValidationContext($facilityType);
        $data = $request->all();
        $validation = $context->validate($data, $facility);
        
        if (!$validation['valid']) {
            return back()->withErrors($validation['errors'])->withInput();
        }

        $validated = $context->processBeforeSave($data);

        if ($request->hasFile('image')) {
            if ($facility->image_url) {
                $oldImagePath = public_path($facility->image_url);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('images/facilities');
            
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $image->move($destinationPath, $imageName);
            $validated['image_url'] = '/images/facilities/' . $imageName;
        }

        if (isset($validated['available_day']) && is_array($validated['available_day'])) {
            $validated['available_day'] = !empty($validated['available_day']) ? array_values(array_filter($validated['available_day'])) : null;
        } else {
            $validated['available_day'] = null;
        }

        if (isset($validated['available_time']) && is_array($validated['available_time'])) {
            if (empty($validated['available_time']['start']) || empty($validated['available_time']['end'])) {
                $validated['available_time'] = null;
            }
        } else {
            $validated['available_time'] = null;
        }

        if ($request->has('equipment_json') && !empty($request->equipment_json)) {
            $equipmentJson = json_decode($request->equipment_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($equipmentJson)) {
                $validated['equipment'] = array_filter($equipmentJson);
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
                $validated['equipment'] = array_filter($validated['equipment']);
            }
        } else {
            $validated['equipment'] = null;
        }
        
        if (isset($validated['equipment']) && (empty($validated['equipment']) || (is_array($validated['equipment']) && count(array_filter($validated['equipment'])) === 0))) {
            $validated['equipment'] = null;
        }

        $oldStatus = $facility->status;
        $newStatus = $validated['status'] ?? $oldStatus;

        $currentUserId = auth()->id();
        $validated['updated_by'] = $currentUserId;
        
        if (empty($facility->created_by)) {
            $validated['created_by'] = $currentUserId;
        }

        $facility->update($validated);
        
        $now = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        DB::table('facilities')
            ->where('id', $facility->id)
            ->update(['updated_at' => $now]);
        
        $facility->refresh();

        if ($oldStatus !== $newStatus && in_array($newStatus, ['unavailable', 'maintenance'])) {
            $this->createFacilityAnnouncement($facility, $newStatus);
        }
        
        if ($oldStatus !== $newStatus && $newStatus === 'available' && in_array($oldStatus, ['unavailable', 'maintenance'])) {
            $this->createFacilityAnnouncement($facility, 'available');
        }

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility updated successfully!');
    }

    public function destroy(string $id)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $facility->update(['is_deleted' => true]);

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility deleted successfully!');
    }

    public function utilization(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);
        
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $stats = $this->calculateUtilizationStats($facility, $startDate, $endDate);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'status' => 'S',
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
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }
        return response()->json([
            'status' => 'S',
            'data' => $stats,
        ]);
    }

    public function exportUtilizationCsv(string $id, Request $request)
    {
        $facility = Facility::where('is_deleted', false)->findOrFail($id);

        $startInput = $request->get('start_date');
        $endInput = $request->get('end_date');

        if (!$startInput || !$endInput) {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        } else {
            $start = Carbon::parse($startInput)->startOfDay();
            $end = Carbon::parse($endInput)->endOfDay();
        }

        $bookings = $facility->bookings()
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        $userIds = $bookings->pluck('user_id')->unique()->filter()->values()->toArray();

        $userInfoMap = [];
        if (!empty($userIds)) {
            try {
                $userInfoMap = $this->userWebService->getUsersInfo($userIds);
            } catch (\Exception $e) {
                Log::error('Failed to fetch users info via Web Service in CSV export', [
                    'facility_id' => $id,
                    'user_ids' => $userIds,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $filename = 'facility_bookings_' . $facility->id . '_' . $start->format('Ymd') . '_to_' . $end->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bookings, $facility, $start, $end, $userInfoMap) {
            $handle = fopen('php://output', 'w');

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
                $userInfo = $userInfoMap[$booking->user_id] ?? null;
                $userName = $userInfo['name'] ?? 'N/A';
                $userEmail = $userInfo['email'] ?? 'N/A';

                fputcsv($handle, [
                    $booking->id,
                    $facility->id,
                    $facility->code,
                    $facility->name,
                    $booking->user_id,
                    $userName,
                    $userEmail,
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

    private function calculateUtilizationStats(Facility $facility, $startDate, $endDate): array
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $bookings = $facility->bookings()
            ->whereBetween('booking_date', [$start, $end])
            ->where('status', 'approved')
            ->get();

        $totalBookings = $bookings->count();
        $totalHours = $bookings->sum('duration_hours');
        $uniqueUsers = $bookings->pluck('user_id')->unique()->count();

        $daysInRange = $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
        $maxPossibleHours = $daysInRange * 8;
        $utilizationPercentage = $maxPossibleHours > 0 ? ($totalHours / $maxPossibleHours) * 100 : 0;

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

    private function createFacilityAnnouncement(Facility $facility, string $eventType)
    {
        try {
            $adminId = auth()->id();
            
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
                    return;
            }

            $announcement = AnnouncementFactory::makeAnnouncement(
                $type,
                $title,
                $content,
                'all',
                $adminId,
                $priority,
                null,
                now(),
                null,
                true
            );

            $this->publishAnnouncementToAllUsers($announcement);

        } catch (\Exception $e) {
            \Log::error('Failed to create facility announcement', [
                'facility_id' => $facility->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    private function publishAnnouncementToAllUsers(Announcement $announcement)
    {
        try {
            $targetUsers = User::where('status', 'active')->pluck('id')->toArray();

            if (empty($targetUsers)) {
                return;
            }

            $syncData = [];
            foreach ($targetUsers as $userId) {
                $syncData[$userId] = [
                    'is_read' => false,
                ];
            }

            $announcement->users()->sync($syncData);

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


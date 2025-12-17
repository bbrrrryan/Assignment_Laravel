<?php

namespace App\Http\Controllers\Admin;

use App\Models\Facility;
use App\Models\User;
use App\Factories\AnnouncementFactory;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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

        // Order by latest
        $query->latest();

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
            'requires_approval' => 'nullable|boolean',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'enable_multi_attendees' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1|required_if:enable_multi_attendees,1',
            'available_day' => 'nullable|array',
            'available_day.*' => 'nullable|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_time' => 'nullable|array',
            'available_time.start' => 'nullable|string|date_format:H:i',
            'available_time.end' => 'nullable|string|date_format:H:i|after:available_time.start',
            'equipment' => 'nullable|string',
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

        if (isset($validated['equipment']) && is_string($validated['equipment'])) {
            $validated['equipment'] = json_decode($validated['equipment'], true);
        }

        // Set default values
        $validated['status'] = $validated['status'] ?? 'available';
        $validated['requires_approval'] = $validated['requires_approval'] ?? false;
        $validated['max_booking_hours'] = $validated['max_booking_hours'] ?? 4;
        $validated['enable_multi_attendees'] = $validated['enable_multi_attendees'] ?? false;
        // If multi-attendees is disabled, set max_attendees to null
        if (!($validated['enable_multi_attendees'] ?? false)) {
            $validated['max_attendees'] = null;
        }

        $facility = Facility::create($validated);

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
        $facility = Facility::where('is_deleted', false)->with('bookings')->findOrFail($id);

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
            'requires_approval' => 'nullable|boolean',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'enable_multi_attendees' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1|required_if:enable_multi_attendees,1',
            'available_day' => 'nullable|array',
            'available_day.*' => 'nullable|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'available_time' => 'nullable|array',
            'available_time.start' => 'nullable|string|date_format:H:i',
            'available_time.end' => 'nullable|string|date_format:H:i|after:available_time.start',
            'equipment' => 'nullable|string',
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

        if (isset($validated['equipment']) && is_string($validated['equipment'])) {
            $validated['equipment'] = json_decode($validated['equipment'], true);
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

        $facility->update($validated);

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


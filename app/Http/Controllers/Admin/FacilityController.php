<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * Display a listing of facilities
     */
    public function index(Request $request)
    {
        $query = Facility::query();

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
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library,cafeteria,other',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance,unavailable,reserved',
            'image_url' => 'nullable|url|max:255',
            'requires_approval' => 'nullable|boolean',
            'booking_advance_days' => 'nullable|integer|min:1|max:365',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'available_times' => 'nullable|string',
            'equipment' => 'nullable|string',
            'rules' => 'nullable|string',
        ]);

        // Handle JSON fields
        if (isset($validated['available_times']) && is_string($validated['available_times'])) {
            $validated['available_times'] = json_decode($validated['available_times'], true);
        }
        if (isset($validated['equipment']) && is_string($validated['equipment'])) {
            $validated['equipment'] = json_decode($validated['equipment'], true);
        }

        // Set default values
        $validated['status'] = $validated['status'] ?? 'available';
        $validated['requires_approval'] = $validated['requires_approval'] ?? false;
        $validated['booking_advance_days'] = $validated['booking_advance_days'] ?? 30;
        $validated['max_booking_hours'] = $validated['max_booking_hours'] ?? 4;

        Facility::create($validated);

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility created successfully!');
    }

    /**
     * Display the specified facility
     */
    public function show(string $id)
    {
        $facility = Facility::with('bookings')->findOrFail($id);
        return view('admin.facilities.show', compact('facility'));
    }

    /**
     * Show the form for editing the specified facility
     */
    public function edit(string $id)
    {
        $facility = Facility::findOrFail($id);
        return view('admin.facilities.edit', compact('facility'));
    }

    /**
     * Update the specified facility
     */
    public function update(Request $request, string $id)
    {
        $facility = Facility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:facilities,code,' . $id,
            'type' => 'required|in:classroom,laboratory,sports,auditorium,library,cafeteria,other',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'nullable|in:available,maintenance,unavailable,reserved',
            'image_url' => 'nullable|url|max:255',
            'requires_approval' => 'nullable|boolean',
            'booking_advance_days' => 'nullable|integer|min:1|max:365',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
            'available_times' => 'nullable|string',
            'equipment' => 'nullable|string',
            'rules' => 'nullable|string',
        ]);

        // Handle JSON fields
        if (isset($validated['available_times']) && is_string($validated['available_times'])) {
            $validated['available_times'] = json_decode($validated['available_times'], true);
        }
        if (isset($validated['equipment']) && is_string($validated['equipment'])) {
            $validated['equipment'] = json_decode($validated['equipment'], true);
        }

        $facility->update($validated);

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility updated successfully!');
    }

    /**
     * Remove the specified facility
     */
    public function destroy(string $id)
    {
        $facility = Facility::findOrFail($id);
        $facility->delete();

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility deleted successfully!');
    }
}


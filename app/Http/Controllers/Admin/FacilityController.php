<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'requires_approval' => 'nullable|boolean',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'requires_approval' => 'nullable|boolean',
            'max_booking_hours' => 'nullable|integer|min:1|max:24',
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
        
        // Delete associated image if exists
        if ($facility->image_url) {
            $imagePath = public_path($facility->image_url);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }
        
        $facility->delete();

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility deleted successfully!');
    }
}


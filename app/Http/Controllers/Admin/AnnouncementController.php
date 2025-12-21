<?php
/**
 * Author: Liew Zi Li
 */
namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends AdminBaseController
{
    public function index()
    {
        return view('admin.announcements.index');
    }

    public function show(string $id)
    {
        $announcement = Announcement::with('creator')->findOrFail($id);
        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,error,reminder,general',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'is_active' => 'nullable',
        ]);

        $validated['target_audience'] = 'all';
        
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : $announcement->is_active;

        $announcement->update($validated);

        return redirect()->route('admin.announcements.show', $id)
            ->with('success', 'Announcement updated successfully!');
    }

    public function destroy(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully!');
    }
}

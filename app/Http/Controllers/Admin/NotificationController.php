<?php

namespace App\Http\Controllers\Admin;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends AdminBaseController
{
    /**
     * Display admin notification management page
     */
    public function index()
    {
        return view('admin.notifications.index');
    }

    /**
     * Display the specified notification
     */
    public function show(string $id)
    {
        $notification = Notification::with('creator')->findOrFail($id);
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Show the form for editing the specified notification
     */
    public function edit(string $id)
    {
        $notification = Notification::findOrFail($id);
        return view('admin.notifications.edit', compact('notification'));
    }

    /**
     * Update the specified notification
     */
    public function update(Request $request, string $id)
    {
        $notification = Notification::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,warning,success,error,reminder',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'is_active' => 'nullable',
        ]);

        // Set target_audience to 'all' (notifications are sent to everyone)
        $validated['target_audience'] = 'all';
        
        // Convert is_active to boolean
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : $notification->is_active;

        $notification->update($validated);

        return redirect()->route('admin.notifications.show', $id)
            ->with('success', 'Notification updated successfully!');
    }

    /**
     * Remove the specified notification
     */
    public function destroy(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification deleted successfully!');
    }
}

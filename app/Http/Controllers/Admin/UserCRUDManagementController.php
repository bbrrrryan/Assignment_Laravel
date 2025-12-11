<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserCRUDManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,student,staff',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|in:active,suspended,deactivated',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'status' => $request->status ?? 'active',
        ]);

        // Log activity
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $currentUser->activityLogs()->create([
            'action' => 'create_user',
            'description' => "Created user: {$user->name}",
            'metadata' => ['user_id' => $user->id],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['activityLogs' => function($query) {
            $query->latest()->limit(20);
        }])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'role' => 'sometimes|required|in:admin,student,staff',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|in:active,suspended,deactivated',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = $request->only(['name', 'email', 'role', 'phone_number', 'address', 'status']);
        
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Log activity
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $currentUser->activityLogs()->create([
            'action' => 'update_user',
            'description' => "Updated user: {$user->name}",
            'metadata' => ['user_id' => $user->id, 'changes' => $updateData],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Log activity before deletion
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $currentUser->activityLogs()->create([
            'action' => 'delete_user',
            'description' => "Deleted user: {$user->name}",
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Upload CSV to create multiple users
     */
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $data = array_map('str_getcsv', file($path));
        $header = array_shift($data); // Remove header row

        // Expected CSV format: name,email,password,role,phone_number,address,status
        $expectedHeaders = ['name', 'email', 'password', 'role'];
        $headerMap = [];
        
        foreach ($expectedHeaders as $expected) {
            $index = array_search(strtolower(trim($expected)), array_map(function($h) {
                return strtolower(trim($h));
            }, $header));
            if ($index === false) {
                return back()->with('error', "CSV header missing required column: {$expected}");
            }
            $headerMap[$expected] = $index;
        }

        // Optional headers
        $optionalHeaders = ['phone_number', 'address', 'status'];
        foreach ($optionalHeaders as $optional) {
            $index = array_search(strtolower(trim($optional)), array_map(function($h) {
                return strtolower(trim($h));
            }, $header));
            if ($index !== false) {
                $headerMap[$optional] = $index;
            }
        }

        $created = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $rowIndex => $row) {
                if (count($row) < count($expectedHeaders)) {
                    $failed++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": Insufficient columns";
                    continue;
                }

                try {
                    $userData = [
                        'name' => trim($row[$headerMap['name']]),
                        'email' => trim($row[$headerMap['email']]),
                        'password' => Hash::make(trim($row[$headerMap['password']])),
                        'role' => strtolower(trim($row[$headerMap['role']])),
                        'phone_number' => isset($headerMap['phone_number']) ? trim($row[$headerMap['phone_number']]) : null,
                        'address' => isset($headerMap['address']) ? trim($row[$headerMap['address']]) : null,
                        'status' => isset($headerMap['status']) ? trim($row[$headerMap['status']]) : 'active',
                    ];

                    // Validate role - using simple if-else
                    $role = $userData['role'];
                    if ($role !== 'admin' && $role !== 'student' && $role !== 'staff') {
                        $userData['role'] = 'student'; // Default to student
                    }

                    // Validate user data
                    $validator = Validator::make($userData, [
                        'name' => 'required|string|max:255',
                        'email' => 'required|string|email|max:255|unique:users',
                        'password' => 'required',
                        'role' => 'required|in:admin,student,staff',
                        'phone_number' => 'nullable|string|max:20',
                        'address' => 'nullable|string|max:500',
                        'status' => 'nullable|in:active,suspended,deactivated',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    User::create($userData);
                    $created++;

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            // Log activity
            /** @var User $currentUser */
            $currentUser = auth()->user();
            $currentUser->activityLogs()->create([
                'action' => 'bulk_upload_users',
                'description' => "Uploaded CSV: {$created} users created, {$failed} failed",
                'metadata' => ['created' => $created, 'failed' => $failed],
            ]);

            $message = "CSV processed successfully. Created: {$created}, Failed: {$failed}";
            if ($failed > 0 && count($errors) > 0) {
                $message .= "\nErrors: " . implode("\n", array_slice($errors, 0, 10));
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'CSV processing failed: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort_by to prevent SQL injection
        $allowedSortFields = ['id', 'name', 'email', 'role', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        if (strtolower($sortOrder) !== 'asc' && strtolower($sortOrder) !== 'desc') {
            $sortOrder = 'desc';
        }
        
        $users = $query->orderBy($sortBy, $sortOrder)
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'S',
            'message' => 'Users retrieved successfully',
            'data' => $users,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,student,staff',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'status' => $request->status ?? 'active',
        ];
        
        if ($request->role === 'student') {
            $userData['studentid'] = User::generateStudentId();
        }
        
        $user = User::create($userData);

        // Log activity
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $currentUser->activityLogs()->create([
            'action' => 'create_user',
            'description' => "Created user: {$user->name}",
            'metadata' => ['user_id' => $user->id],
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'User created successfully',
            'data' => $user,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show(string $id)
    {
        $user = User::with(['activityLogs' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'status' => 'S',
            'message' => 'User retrieved successfully',
            'data' => $user,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|required|in:admin,student,staff',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $updateData = $request->only(['name', 'email', 'role', 'phone_number', 'address', 'status']);
        
        if ($request->has('password')) {
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

        return response()->json([
            'status' => 'S',
            'message' => 'User updated successfully',
            'data' => $user,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Log activity before deletion
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $currentUser->activityLogs()->create([
            'action' => 'delete_user',
            'description' => "Deleted user: {$user->name}",
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->delete();

        return response()->json([
            'status' => 'S',
            'message' => 'User deleted successfully',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get user activity logs
     */
    public function activityLogs(string $id, Request $request)
    {
        $user = User::findOrFail($id);
        
        $logs = $user->activityLogs()
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'S',
            'message' => 'Activity logs retrieved successfully',
            'data' => $logs,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
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
            $index = array_search($expected, array_map('strtolower', $header));
            if ($index === false) {
                return response()->json([
                    'status' => 'F',
                    'message' => "CSV header missing required column: {$expected}",
                    'expected_headers' => $expectedHeaders,
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                ], 422);
            }
            $headerMap[$expected] = $index;
        }

        // Optional headers
        $optionalHeaders = ['phone_number', 'address', 'status'];
        foreach ($optionalHeaders as $optional) {
            $index = array_search($optional, array_map('strtolower', $header));
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
                    $password = trim($row[$headerMap['password']]);
                    $phoneNumber = isset($headerMap['phone_number']) ? trim($row[$headerMap['phone_number']]) : null;
                    
                    // If password is empty, use phone_number as password, otherwise use default password
                    if (empty($password)) {
                        if (!empty($phoneNumber)) {
                            // Use phone_number as password
                            $password = $phoneNumber;
                        } else {
                            // Use default password if phone_number is also empty
                            $password = '123456';
                        }
                    }
                    
                    $userData = [
                        'name' => trim($row[$headerMap['name']]),
                        'email' => trim($row[$headerMap['email']]),
                        'password' => Hash::make($password),
                        'role' => strtolower(trim($row[$headerMap['role']])),
                        'phone_number' => $phoneNumber,
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
                        'phone_number' => 'nullable|string',
                        'address' => 'nullable|string',
                        'status' => 'nullable|in:active,inactive',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    if ($userData['role'] === 'student') {
                        $userData['studentid'] = User::generateStudentId();
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

            return response()->json([
                'status' => 'S',
                'message' => 'CSV processed successfully',
                'data' => [
                    'total_rows' => count($data),
                    'created' => $created,
                    'failed' => $failed,
                    'errors' => $errors,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'E',
                'message' => 'CSV processing failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 500);
        }
    }

    /**
     * Update user profile (for authenticated user)
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'password' => 'sometimes|required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'F',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ], 422);
        }

        $updateData = $request->only(['name', 'phone_number', 'address']);
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Log activity
        $user->activityLogs()->create([
            'action' => 'update_profile',
            'description' => 'Updated own profile',
        ]);

        return response()->json([
            'status' => 'S',
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get current user's activity logs
     * Limited to last 30 records only, paginated with 10 per page
     */
    public function myActivityLogs(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();
        
        // Get total count
        $totalLogs = $user->activityLogs()->count();
        $maxRecords = 30;
        $perPage = 10;
        
        // Calculate how many records to show (max 30)
        $totalToShow = min($totalLogs, $maxRecords);
        
        // Calculate offset based on page
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // If offset exceeds max records, adjust
        if ($offset >= $maxRecords) {
            $offset = 0;
            $page = 1;
        }
        
        // Get the records (latest first, limit to 30, then paginate)
        $logs = $user->activityLogs()
            ->latest()
            ->limit($maxRecords)
            ->skip($offset)
            ->take($perPage)
            ->get();
        
        // Create pagination response manually
        $lastPage = ceil($totalToShow / $perPage);
        
        return response()->json([
            'status' => 'S',
            'message' => 'Activity logs retrieved successfully',
            'data' => [
                'data' => $logs,
                'current_page' => (int)$page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $totalToShow,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $totalToShow),
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

}

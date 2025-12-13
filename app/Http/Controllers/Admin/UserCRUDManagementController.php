<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserCRUDManagementController extends AdminBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Role filter
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Order by latest
        $query->latest();

        // Paginate results
        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['activityLogs' => function($query) {
            $query->latest()->limit(10);
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
        $currentUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'role' => 'sometimes|required|in:admin,student,staff',
            'phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $updateData = $request->only(['name', 'email', 'phone_number', 'address']);
        
        // Only admin can change role
        if ($request->has('role')) {
            if ($currentUser->isAdmin()) {
                // Admin can change role
                $updateData['role'] = $request->role;
            } else {
                // Staff cannot change role
                return redirect()->back()
                    ->withErrors(['role' => 'Only admin can change user role'])
                    ->withInput();
            }
        }
        
        // Only admin can change status to inactive
        if ($request->has('status')) {
            if ($currentUser->isAdmin()) {
                // Admin can set status to active or inactive
                $updateData['status'] = $request->status;
            } else {
                // Staff can only set status to active, not inactive
                if ($request->status === 'inactive') {
                    return redirect()->back()
                        ->withErrors(['status' => 'Only admin can set user to inactive'])
                        ->withInput();
                }
                // Staff can set status to active
                $updateData['status'] = $request->status;
            }
        }
        
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated already');
    }

    /**
     * Export users to CSV
     */
    public function exportCsv()
    {
        $users = User::all();
        
        $filename = 'users_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, ['name', 'email', 'password', 'role', 'phone_number', 'address', 'status']);
            
            // Add user data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    '', // Password should be empty in export for security
                    $user->role,
                    $user->phone_number ?? '',
                    $user->address ?? '',
                    $user->status,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Upload CSV to create multiple users
     */
    public function uploadCsv(Request $request)
    {
        // Check if this is an AJAX request
        $isAjax = $request->ajax() || $request->wantsJson() || $request->expectsJson();
        
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        // Read file content and handle encoding
        $content = file_get_contents($path);
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Parse CSV lines
        $lines = str_getcsv($content, "\n");
        $data = [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $data[] = str_getcsv($line);
            }
        }
        
        // Remove header row
        if (empty($data)) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty or cannot read',
                    'error' => 'Empty file'
                ], 422);
            }
            return redirect()->route('admin.users.index')
                ->with('error', 'CSV file is empty or cannot read');
        }
        
        $header = array_shift($data);
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                // Skip if row is empty or has insufficient columns
                if (empty($row) || count($row) < 3) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Empty row or insufficient columns";
                    continue;
                }

                // Trim all values and map CSV columns: name, email, password, role, phone_number, address, status
                $userData = [
                    'name' => trim($row[0] ?? ''),
                    'email' => trim($row[1] ?? ''),
                    'password' => trim($row[2] ?? ''),
                    'role' => trim($row[3] ?? 'student'),
                    'phone_number' => isset($row[4]) ? trim($row[4]) : null,
                    'address' => isset($row[5]) ? trim($row[5]) : null,
                    'status' => isset($row[6]) ? trim($row[6]) : 'active',
                ];

                // Skip if all required fields are empty (empty row)
                if (empty($userData['name']) && empty($userData['email']) && empty($userData['password'])) {
                    continue; // Skip empty rows silently
                }

                // Validate required fields
                if (empty($userData['name'])) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Need to fill in name";
                    continue;
                }

                if (empty($userData['email'])) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Need to fill in email";
                    continue;
                }

                // If password is empty, use phone_number as password
                if (empty($userData['password'])) {
                    if (empty($userData['phone_number'])) {
                        $errorCount++;
                        $errors[] = "Row " . ($index + 2) . ": Need password or phone number";
                        continue;
                    } else {
                        // Use phone_number as password
                        $userData['password'] = $userData['phone_number'];
                    }
                }

                // Validate email format
                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Email format wrong ({$userData['email']})";
                    continue;
                }

                // Check if email already exists
                if (User::where('email', $userData['email'])->exists()) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": This email already got ({$userData['email']})";
                    continue;
                }

                // Validate role
                $userData['role'] = strtolower($userData['role']);
                if (!in_array($userData['role'], ['admin', 'student', 'staff'])) {
                    $userData['role'] = 'student'; // Default to student if invalid
                }

                // Validate status
                $userData['status'] = strtolower($userData['status']);
                if (!in_array($userData['status'], ['active', 'inactive'])) {
                    $userData['status'] = 'active'; // Default to active if invalid
                }

                // Validate password length (after using phone_number if needed)
                if (strlen($userData['password']) < 6) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Password need at least 6 characters";
                    continue;
                }

                // Clean up empty optional fields
                if (empty($userData['phone_number'])) {
                    $userData['phone_number'] = null;
                }
                if (empty($userData['address'])) {
                    $userData['address'] = null;
                }

                // Create user
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'phone_number' => $userData['phone_number'],
                    'address' => $userData['address'],
                    'status' => $userData['status'],
                ]);

                $successCount++;
            }

            DB::commit();

            // Return JSON response for API
            if ($isAjax) {
                $message = "Done! Created {$successCount} user";
                if ($successCount != 1) {
                    $message .= "s";
                }
                if ($errorCount > 0) {
                    $message .= ". Got {$errorCount} error";
                    if ($errorCount != 1) {
                        $message .= "s";
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'success_count' => $successCount,
                        'error_count' => $errorCount,
                        'errors' => $errors
                    ]
                ]);
            }

            // Fallback to redirect for non-AJAX requests
            $message = "Done! Created {$successCount} user";
            if ($successCount != 1) {
                $message .= "s";
            }
            if ($errorCount > 0) {
                $message .= ". Got {$errorCount} error";
                if ($errorCount != 1) {
                    $message .= "s";
                }
            }

            return redirect()->route('admin.users.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV Import Error: ' . $e->getMessage());
            
            // Return JSON response for API
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot upload CSV file. Something went wrong: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot upload CSV file. Something went wrong: ' . $e->getMessage());
        }
    }
}

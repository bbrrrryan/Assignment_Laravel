<?php
/**
 * Author: Liew Zi Li
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserCRUDManagementController extends AdminBaseController
{
    public function create()
    {
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Only admin can create staff user');
        }

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !$currentUser->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Only admin can create staff user');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $createData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff',
            'status' => 'active',
            'phone_number' => $request->phone_number ?: null,
            'address' => $request->address ?: null,
            'personal_id' => User::generateStaffId(),
        ];

        User::create($createData);

        return redirect()->route('admin.users.index')
            ->with('success', 'Staff user created successfully');
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $query->latest();

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(string $id)
    {
        $user = User::with(['activityLogs' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);
        
        return view('admin.users.show', compact('user'));
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $currentUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6|confirmed',
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
        
        if ($request->has('status')) {
            if ($currentUser->isAdmin()) {
                $updateData['status'] = $request->status;
            } else {
                if ($request->status === 'inactive') {
                    return redirect()->back()
                        ->withErrors(['status' => 'Only admin can set user to inactive'])
                        ->withInput();
                }
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
            
            fputcsv($file, ['name', 'email', 'password', 'role', 'phone_number', 'address', 'status']);
            
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->password,
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

    public function uploadCsv(Request $request)
    {
        $isAjax = $request->ajax() || $request->wantsJson() || $request->expectsJson();
        
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        $lines = str_getcsv($content, "\n");
        $data = [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $data[] = str_getcsv($line);
            }
        }
        
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
                if (empty($row) || count($row) < 3) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Empty row or insufficient columns";
                    continue;
                }

                $userData = [
                    'name' => trim($row[0] ?? ''),
                    'email' => trim($row[1] ?? ''),
                    'password' => trim($row[2] ?? ''),
                    'role' => trim($row[3] ?? 'student'),
                    'phone_number' => isset($row[4]) ? trim($row[4]) : null,
                    'address' => isset($row[5]) ? trim($row[5]) : null,
                    'status' => isset($row[6]) ? trim($row[6]) : 'active',
                ];

                if (empty($userData['name']) && empty($userData['email']) && empty($userData['password'])) {
                    continue;
                }

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

                if (empty($userData['password'])) {
                    if (!empty($userData['phone_number'])) {
                        $userData['password'] = $userData['phone_number'];
                    } else {
                        $userData['password'] = '123456';
                    }
                }

                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Email format wrong ({$userData['email']})";
                    continue;
                }

                if (User::where('email', $userData['email'])->exists()) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": This email already got ({$userData['email']})";
                    continue;
                }

                $userData['role'] = strtolower($userData['role']);
                if (!in_array($userData['role'], ['admin', 'student', 'staff'])) {
                    $userData['role'] = 'student';
                }

                $userData['status'] = strtolower($userData['status']);
                if (!in_array($userData['status'], ['active', 'inactive'])) {
                    $userData['status'] = 'active';
                }

                if (strlen($userData['password']) < 6) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": Password need at least 6 characters";
                    continue;
                }

                if (empty($userData['phone_number'])) {
                    $userData['phone_number'] = null;
                }
                if (empty($userData['address'])) {
                    $userData['address'] = null;
                }

                $rawPassword = $userData['password'];
                $hashedPassword = $rawPassword;
                if (!preg_match('/^\$2y\$\d{2}\$.{53}$/', $rawPassword)) {
                    $hashedPassword = Hash::make($rawPassword);
                }

                $createData = [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $hashedPassword,
                    'role' => $userData['role'],
                    'phone_number' => $userData['phone_number'],
                    'address' => $userData['address'],
                    'status' => $userData['status'],
                ];
                
                if ($userData['role'] === 'student') {
                    $createData['personal_id'] = User::generateStudentId();
                } elseif ($userData['role'] === 'staff') {
                    $createData['personal_id'] = User::generateStaffId();
                }
                
                User::create($createData);

                $successCount++;
            }

            DB::commit();

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

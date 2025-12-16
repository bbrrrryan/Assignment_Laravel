<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $roles,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function store(Request $request)
    {
        $role = Role::create($request->validate([
            'name' => 'required|unique:roles',
            'display_name' => 'required',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]));
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $role,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ], 201);
    }

    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $role,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $role->update($request->validate([
            'display_name' => 'sometimes|required',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]));
        return response()->json([
            'status' => 'S', // IFA Standard
            'data' => $role,
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }

    public function destroy(string $id)
    {
        Role::findOrFail($id)->delete();
        return response()->json([
            'status' => 'S', // IFA Standard
            'message' => 'Role deleted',
            'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard
        ]);
    }
}

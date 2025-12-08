<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['data' => $roles]);
    }

    public function store(Request $request)
    {
        $role = Role::create($request->validate([
            'name' => 'required|unique:roles',
            'display_name' => 'required',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]));
        return response()->json(['data' => $role], 201);
    }

    public function show(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json(['data' => $role]);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $role->update($request->validate([
            'display_name' => 'sometimes|required',
            'description' => 'nullable',
            'is_active' => 'boolean',
        ]));
        return response()->json(['data' => $role]);
    }

    public function destroy(string $id)
    {
        Role::findOrFail($id)->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}

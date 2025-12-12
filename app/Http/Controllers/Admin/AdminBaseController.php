<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminBaseController extends Controller
{
    /**
     * Constructor - Ensure only admin or staff can access
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Unauthenticated',
                    ], 401);
                }
                return redirect()->route('login');
            }

            $user = auth()->user();

            // Only allow admin and staff, block students
            if ($user->isStudent()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Unauthorized. Admin or Staff access required.',
                    ], 403);
                }
                return redirect()->route('home')
                    ->with('error', 'You do not have permission to access this page.');
            }

            return $next($request);
        });
    }
}


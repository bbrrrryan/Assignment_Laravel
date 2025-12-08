<?php
/**
 * Author: Liew Zi Li
 * Module: User Management Module
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Factories\UserFactory;
use App\Models\User;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }
    
    public function index()
    {
        $users = User::all(); 
        return view('users.index', compact('users'));
    }
    // ------------------

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $user = UserFactory::makeUser(
            $request->role,
            $request->name,
            $request->email,
            $request->password
        );

        if ($user) {
            return redirect()->back()->with('success', 'User created successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to create user.');
        }
    }

    // Authentication Views
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    // Dashboard
    public function dashboard()
    {
        return view('dashboard');
    }

    // Facilities
    public function facilities()
    {
        return view('facilities.index');
    }

    public function showFacility($id)
    {
        return view('facilities.show', compact('id'));
    }

    // Bookings
    public function bookings()
    {
        return view('bookings.index');
    }

    public function showBooking($id)
    {
        return view('bookings.show', compact('id'));
    }

    // Notifications
    public function notifications()
    {
        return view('notifications.index');
    }

    public function showNotification($id)
    {
        return view('notifications.show', compact('id'));
    }

    // Loyalty
    public function loyalty()
    {
        return view('loyalty.index');
    }

    // Feedback
    public function feedbacks()
    {
        return view('feedbacks.index');
    }

    public function showFeedback($id)
    {
        return view('feedbacks.show', compact('id'));
    }

    // Profile
    public function profile()
    {
        return view('profile.index');
    }
}
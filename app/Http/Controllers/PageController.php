<?php

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

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function showVerifyOtp(Request $request)
    {
        $email = $request->query('email');
        
        if (!$email) {
            return redirect()->route('register')
                ->with('toast_message', 'Please register first to verify your email.')
                ->with('toast_type', 'info');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('register')
                ->with('toast_message', 'Invalid email address.')
                ->with('toast_type', 'error');
        }
        
        return view('auth.verify-otp', ['email' => $email]);
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function facilities()
    {
        return view('facilities.index');
    }

    public function showFacility($id)
    {
        return view('facilities.show', compact('id'));
    }

    public function bookings()
    {
        return view('bookings.index');
    }

    public function createBooking()
    {
        return view('bookings.create');
    }

    public function showBooking($id)
    {
        return view('bookings.show', compact('id'));
    }
    
    public function adminBookings()
    {
        return view('admin.bookings.index');
    }

    public function notifications()
    {
        return view('notifications.index');
    }

    public function showNotification($id)
    {
        return view('notifications.show', compact('id'));
    }

    public function adminNotifications()
    {
        return view('admin.notifications.index');
    }

    public function showAnnouncement($id)
    {
        return view('announcements.show', compact('id'));
    }

    public function loyalty()
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return redirect()->route('admin.loyalty.index');
        }
        return view('loyalty.index');
    }

    public function adminLoyalty()
    {
        return view('admin.loyalty.index');
    }

    public function feedbacks()
    {
        return view('feedbacks.index');
    }

    public function showFeedback($id)
    {
        return view('feedbacks.show', compact('id'));
    }

    public function adminFeedbacks()
    {
        return view('admin.feedbacks.index');
    }

    public function adminShowFeedback($id)
    {
        return view('admin.feedbacks.show', compact('id'));
    }

    public function profile()
    {
        return view('profile.index');
    }
}

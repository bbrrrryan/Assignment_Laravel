<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TARUMT FMS')</title>
    
    <style>
        {!! file_get_contents(resource_path('css/app.css')) !!}
    </style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="{{ asset('js/api.js') }}"></script>
    <style>
        /* User Dropdown Styles */
        .user-dropdown {
            position: relative;
        }
        
        .user-button {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 15px;
            cursor: pointer;
            color: #2d3436;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .user-button:hover {
            border-color: #a31f37;
            background: #fff5f7;
        }
        
        .user-button i.fa-user-circle {
            font-size: 1.5rem;
            color: #a31f37;
        }
        
        .user-button i.fa-chevron-down {
            font-size: 0.8rem;
            color: #636e72;
            transition: transform 0.3s;
        }
        
        .user-dropdown.active .user-button i.fa-chevron-down {
            transform: rotate(180deg);
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }
        
        .user-dropdown.active .dropdown-menu {
            display: block;
        }
        
        .dropdown-menu a,
        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 20px;
            color: #2d3436;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.9rem;
            text-align: left;
            transition: background 0.2s;
        }
        
        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: #f8f9fa;
        }
        
        .dropdown-menu a i,
        .dropdown-menu button i {
            color: #a31f37;
            width: 20px;
        }
        
        .dropdown-logout {
            border-top: 1px solid #e0e0e0 !important;
            color: #dc3545 !important;
        }
        
        .dropdown-logout:hover {
            background: #fff5f5 !important;
        }
        
        .dropdown-logout i {
            color: #dc3545 !important;
        }
        
        /* Close dropdown when clicking outside */
        .dropdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
            display: none;
        }
        
        .user-dropdown.active ~ .dropdown-overlay,
        .user-dropdown.active .dropdown-overlay {
            display: block;
        }
    </style>
    <script>
        // Simple function to toggle user dropdown menu - using if-else only
        function toggleUserMenu() {
            var dropdown = document.querySelector('.user-dropdown');
            
            // Check if dropdown is active
            if (dropdown.classList.contains('active')) {
                // If active, remove it
                dropdown.classList.remove('active');
            } else {
                // If not active, add it
                dropdown.classList.add('active');
            }
        }
        
        // Close dropdown when clicking outside - using simple if-else
        document.addEventListener('click', function(event) {
            var dropdown = document.querySelector('.user-dropdown');
            
            // Check if click is inside dropdown
            var isClickInside = dropdown.contains(event.target);
            
            // Check if dropdown is active
            if (!isClickInside && dropdown.classList.contains('active')) {
                // If click is outside and dropdown is active, close it
                dropdown.classList.remove('active');
            }
        });
    </script>
    
    <!-- Toast Message System -->
    <script>
        // Simple Toast Message Function
        function showToast(message, type) {
            // type can be: 'success', 'error', 'warning', 'info'
            var container = document.getElementById('toastContainer');
            
            // Create toast element
            var toast = document.createElement('div');
            toast.className = 'toast-message toast-' + type;
            toast.innerHTML = '<span>' + message + '</span><button onclick="this.parentElement.remove()">&times;</button>';
            
            // Add to container
            container.appendChild(toast);
            
            // Auto remove after 4 seconds with fade out
            setTimeout(function() {
                if (toast.parentElement) {
                    toast.style.transition = 'all 0.3s ease-out';
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }
            }, 4000);
        }
    </script>
    
    <style>
        /* Toast Message Styles */
        #toastContainer {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .toast-message {
            min-width: 250px;
            max-width: 400px;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.4s ease-out;
            font-weight: 500;
            font-size: 14px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-80px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .toast-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-color: rgba(40, 167, 69, 0.2);
        }
        
        .toast-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-color: rgba(220, 53, 69, 0.2);
        }
        
        .toast-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-color: rgba(255, 193, 7, 0.2);
        }
        
        .toast-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border-color: rgba(23, 162, 184, 0.2);
        }
        
        .toast-message button {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            padding: 0;
            margin-left: 12px;
            opacity: 0.6;
            line-height: 1;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .toast-message button:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .toast-message span {
            flex: 1;
            padding: 0 5px;
            word-break: break-word;
        }
        
    </style>
    
    <script>
        // Show toast from session messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('toast_message'))
                showToast('{{ session('toast_message') }}', '{{ session('toast_type', 'success') }}');
            @endif
            
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
            
            // Store API token from session to localStorage for API calls
            @auth
                @if(session('api_token'))
                    // Save token to localStorage
                    localStorage.setItem('auth_token', '{{ session('api_token') }}');
                    
                    // Save user data to localStorage
                    localStorage.setItem('user', JSON.stringify({
                        id: {{ auth()->user()->id }},
                        name: '{{ auth()->user()->name }}',
                        email: '{{ auth()->user()->email }}',
                        role: '{{ auth()->user()->role }}',
                        role_id: {{ auth()->user()->role_id ?? 'null' }},
                        phone_number: '{{ auth()->user()->phone_number ?? '' }}',
                        address: '{{ auth()->user()->address ?? '' }}',
                        status: '{{ auth()->user()->status }}'
                    }));
                @endif
            @endauth
        });
    </script>
</head>
<body>

    <header>
        <div class="logo"><h1><a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">TARUMT FMS</a></h1></div>
        <nav>
            <ul>
                @auth
                    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                        <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('admin.users.index') }}">User Management</a></li>
                        <li><a href="{{ route('admin.facilities.index') }}">Facility Management</a></li>
                        <li><a href="{{ route('admin.notifications.index') }}">Notification Management</a></li>
                    @else
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                        <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
                        <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                        <li><a href="{{ route('loyalty.index') }}">Loyalty</a></li>
                        <li><a href="{{ route('feedbacks.index') }}">Feedback</a></li>
                    @endif
                    @if(!auth()->user()->isAdmin())
                        <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                    @endif
                @endauth
                @guest
                    <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                @endguest
                <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
                <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                <li><a href="{{ route('loyalty.index') }}">Loyalty</a></li>
                <li><a href="{{ route('feedbacks.index') }}">Feedback</a></li>
                @auth
                    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                        <li><a href="{{ route('admin.users.index') }}">User Management</a></li>
                        <li><a href="{{ route('admin.facilities.index') }}">Facility Management</a></li>
                    @endif
                @endauth
                @guest
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                @endguest
                <li id="authLinks">
                    @auth
                        <div class="user-dropdown">
                            <button class="user-button" onclick="toggleUserMenu()">
                                <i class="fas fa-user-circle"></i>
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="userDropdownMenu">
                                <a href="{{ route('profile.index') }}">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="dropdown-logout">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn-login">Sign In</a>
                    @endauth
                </li>
            </ul>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Toast Message Container -->
    <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000;"></div>

    <footer>
        &copy; 2025 TAR UMT Facilities Management System | Built by Group 5 F4
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
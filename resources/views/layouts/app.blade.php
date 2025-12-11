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
</head>
<body>

    <header>
        <div class="logo"><h1><a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">TARUMT FMS</a></h1></div>
        <nav>
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                @auth
                    @if(auth()->user()->isAdmin())
                        <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    @endif
                @endauth
                <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
                <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                <li><a href="{{ route('loyalty.index') }}">Loyalty</a></li>
                <li><a href="{{ route('feedbacks.index') }}">Feedback</a></li>
                @auth
                    @if(auth()->user()->isAdmin())
                        <li><a href="{{ route('admin.users.index') }}">User Management</a></li>
                    @endif
                @endauth
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
                                <a href="{{ route('settings.index') }}">
                                    <i class="fas fa-cog"></i> Settings
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

    <footer>
        &copy; 2025 TAR UMT Facilities Management System | Built by Liew Zi Li
    </footer>

</body>
</html>
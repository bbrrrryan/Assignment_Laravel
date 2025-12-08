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
    <script>
        // Check authentication status on page load
        document.addEventListener('DOMContentLoaded', function() {
            const token = API.getToken();
            const user = API.getUser();
            
            if (token && user) {
                const authLinks = document.getElementById('authLinks');
                if (authLinks) {
                    authLinks.innerHTML = `
                        <span style="color: #636e72; margin-right: 10px;">${user.name || 'User'}</span>
                        <a href="#" onclick="API.logout()" class="btn-login">Logout</a>
                    `;
                }
                
                // Hide Users link if not admin
                try {
                    const usersLink = document.querySelector('a[href="{{ route('users.index') }}"]');
                    if (usersLink) {
                        if (!API.isAdmin()) {
                            usersLink.parentElement.style.display = 'none';
                        } else {
                            usersLink.parentElement.style.display = '';
                        }
                    }
                } catch (error) {
                    console.warn('Error checking admin status:', error);
                }
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
                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
                <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                <li><a href="{{ route('loyalty.index') }}">Loyalty</a></li>
                <li><a href="{{ route('feedbacks.index') }}">Feedback</a></li>
                <li><a href="{{ route('users.index') }}">Users</a></li>
                <li><a href="{{ route('profile.index') }}">Profile</a></li>
                <li id="authLinks">
                    <a href="{{ route('login') }}" class="btn-login">Sign In</a>
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
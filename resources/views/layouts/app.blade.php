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
        
        /* Notification Bell Styles */
        .notification-dropdown {
            position: relative;
            margin-right: 15px;
        }
        
        .notification-button {
            position: relative;
            background: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 15px;
            cursor: pointer;
            color: #2d3436;
            font-size: 1.1rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-button:hover {
            border-color: #a31f37;
            background: #fff5f7;
        }
        
        .notification-button i {
            color: #a31f37;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .notification-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            min-width: 350px;
            max-width: 400px;
            max-height: 500px;
            z-index: 1001;
            overflow: hidden;
        }
        
        .notification-dropdown.active .notification-menu {
            display: block;
        }
        
        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #2d3436;
        }
        
        .view-all-link {
            color: #a31f37;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .view-all-link:hover {
            text-decoration: underline;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #f0f7ff;
            border-left: 3px solid #a31f37;
        }
        
        .notification-item-title {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .notification-item-message {
            color: #636e72;
            font-size: 0.85rem;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .notification-item-time {
            color: #95a5a6;
            font-size: 0.75rem;
        }
        
        .notification-loading,
        .notification-empty {
            padding: 30px 20px;
            text-align: center;
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        /* Auth Links Container */
        #authLinks {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Notification Icon Link */
        .notification-icon-link {
            position: relative;
            background: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 15px;
            color: #2d3436;
            font-size: 1.1rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .notification-icon-link:hover {
            border-color: #a31f37;
            background: #fff5f7;
        }
        
        .notification-icon-link i {
            color: #a31f37;
        }
        
        .notification-button .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .notification-item-actions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }

        .btn-approve, .btn-reject {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
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
        
        // Custom confirm dialog that returns a Promise
        function showConfirm(message, title) {
            return new Promise(function(resolve) {
                // Create modal overlay
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10001; display: flex; align-items: center; justify-content: center;';
                
                // Create modal dialog
                var modal = document.createElement('div');
                modal.style.cssText = 'background: white; border-radius: 8px; padding: 20px; max-width: 400px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);';
                
                modal.innerHTML = `
                    <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #333;">${title || 'Confirm'}</h3>
                    <p style="margin: 0 0 20px 0; color: #666; line-height: 1.5;">${message}</p>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button id="confirmCancel" style="padding: 8px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                        <button id="confirmOK" style="padding: 8px 20px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">OK</button>
                    </div>
                `;
                
                overlay.appendChild(modal);
                document.body.appendChild(overlay);
                
                // Handle button clicks
                document.getElementById('confirmOK').onclick = function() {
                    document.body.removeChild(overlay);
                    resolve(true);
                };
                
                document.getElementById('confirmCancel').onclick = function() {
                    document.body.removeChild(overlay);
                    resolve(false);
                };
                
                // Close on overlay click
                overlay.onclick = function(e) {
                    if (e.target === overlay) {
                        document.body.removeChild(overlay);
                        resolve(false);
                    }
                };
            });
        }
        
        // Custom prompt dialog that returns a Promise
        function showPrompt(message, title, defaultValue) {
            return new Promise(function(resolve) {
                // Create modal overlay
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10001; display: flex; align-items: center; justify-content: center;';
                
                // Create modal dialog
                var modal = document.createElement('div');
                modal.style.cssText = 'background: white; border-radius: 8px; padding: 20px; max-width: 400px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);';
                
                var inputId = 'promptInput_' + Date.now();
                modal.innerHTML = `
                    <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #333;">${title || 'Input'}</h3>
                    <p style="margin: 0 0 15px 0; color: #666; line-height: 1.5;">${message}</p>
                    <input type="text" id="${inputId}" value="${defaultValue || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; box-sizing: border-box; font-size: 14px;" autofocus>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button id="promptCancel" style="padding: 8px 20px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                        <button id="promptOK" style="padding: 8px 20px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer;">OK</button>
                    </div>
                `;
                
                overlay.appendChild(modal);
                document.body.appendChild(overlay);
                
                // Focus input and select text if default value exists
                var input = document.getElementById(inputId);
                input.focus();
                if (defaultValue) {
                    input.select();
                }
                
                // Handle Enter key
                input.onkeydown = function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('promptOK').click();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        document.getElementById('promptCancel').click();
                    }
                };
                
                // Handle button clicks
                document.getElementById('promptOK').onclick = function() {
                    var value = input.value;
                    document.body.removeChild(overlay);
                    resolve(value);
                };
                
                document.getElementById('promptCancel').onclick = function() {
                    document.body.removeChild(overlay);
                    resolve(null);
                };
                
                // Close on overlay click
                overlay.onclick = function(e) {
                    if (e.target === overlay) {
                        document.body.removeChild(overlay);
                        resolve(null);
                    }
                };
            });
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
                    
                    // Load notification count for all authenticated users
                    loadNotificationCount();
                    // Refresh notification count every 30 seconds
                    setInterval(loadNotificationCount, 30000);
                @endif
            @endauth
        });
        
        // Load unread notification count for notification icon badge
        async function loadNotificationCount() {
            if (typeof API === 'undefined') return;
            
            try {
                const isAdmin = API.isAdmin();
                const isStaff = API.isStaff();
                const isStudent = API.isStudent();
                
                if (isStudent) {
                    // For students, get unread announcements and notifications count
                    const result = await API.get('/notifications/user/unread-items?limit=0&only_unread=true');
                    if (result.success && result.data && result.data.counts) {
                        const totalCount = result.data.counts.total || 0;
                        const badge = document.getElementById('notificationNavBadge');
                        if (badge) {
                            if (totalCount > 0) {
                                badge.textContent = totalCount > 99 ? '99+' : totalCount;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                } else if (isAdmin || isStaff) {
                    // For admin/staff: get pending bookings count
                    const result = await API.get('/bookings/pending?limit=0');
                    let count = 0;
                    if (result && result.success !== false) {
                        // API returns: { success: true, data: { message: "...", data: { bookings: [], count: ... } } }
                        // Or directly: { success: true, data: { bookings: [], count: ... } }
                        if (result.data && result.data.data && result.data.data.count !== undefined) {
                            count = result.data.data.count;
                        } else if (result.data && result.data.count !== undefined) {
                            count = result.data.count;
                        } else if (result.data && result.data.bookings && Array.isArray(result.data.bookings)) {
                            count = result.data.bookings.length;
                        } else if (result.data && result.data.data && result.data.data.bookings && Array.isArray(result.data.data.bookings)) {
                            count = result.data.data.bookings.length;
                        }
                    }
                    
                    const badge = document.getElementById('notificationNavBadge');
                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading notification count:', error);
            }
        }

        // Toggle notification dropdown menu - make it global
        window.toggleNotificationMenu = function(event) {
            try {
                if (event) {
                    event.stopPropagation();
                    event.preventDefault();
                }
                
                const dropdown = document.getElementById('notificationDropdown');
                if (!dropdown) {
                    console.error('Notification dropdown not found!');
                    return;
                }
                
                if (dropdown.classList.contains('active')) {
                    dropdown.classList.remove('active');
                } else {
                    dropdown.classList.add('active');
                    if (typeof window.loadNotificationDropdownContent === 'function') {
                        window.loadNotificationDropdownContent();
                    } else {
                        console.error('loadNotificationDropdownContent function not found!');
                    }
                }
            } catch (error) {
                console.error('Error in toggleNotificationMenu:', error);
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Load notification dropdown content - make it global
        window.loadNotificationDropdownContent = async function() {
            if (typeof API === 'undefined') {
                console.error('API is not defined!');
                return;
            }
            
            const listContainer = document.getElementById('notificationList');
            const titleElement = document.getElementById('notificationMenuTitle');
            
            if (!listContainer) {
                console.error('Notification list container not found!');
                return;
            }
            
            listContainer.innerHTML = '<div class="notification-loading">Loading...</div>';
            
            try {
                const isAdmin = API.isAdmin();
                const isStaff = API.isStaff();
                const isStudent = API.isStudent();
                const viewAllLink = document.getElementById('viewAllLink');
                
                // Student: show Announcements & Notifications
                // Staff/Admin: show only pending bookings
                if (isStudent) {
                    // For students: show unread announcements and notifications
                    if (titleElement) {
                        titleElement.textContent = 'Announcements & Notifications';
                    }
                    if (viewAllLink) {
                        viewAllLink.href = '/notifications';
                    }
                    
                    // Only show unread items in bell dropdown
                    const result = await API.get('/notifications/user/unread-items?limit=10&only_unread=true');
                    console.log('Unread items API result (full):', JSON.stringify(result, null, 2));
                    
                    // Extract items from response
                    let items = [];
                    if (result && result.success) {
                        // Direct access to result.data.items
                        if (result.data && Array.isArray(result.data.items)) {
                            items = result.data.items;
                        } else if (result.data && result.data.data && Array.isArray(result.data.data.items)) {
                            items = result.data.data.items;
                        }
                        
                        console.log('Debug info:', result.data?.debug);
                        console.log('Items extracted:', items.length, 'items');
                        console.log('Items array:', items);
                        
                        if (result.data && result.data.counts) {
                            console.log('Announcements count:', result.data.counts.announcements || 0);
                            console.log('Notifications count:', result.data.counts.notifications || 0);
                        }
                    } else {
                        console.error('API call failed:', result);
                    }
                    
                    if (!items || items.length === 0) {
                        listContainer.innerHTML = '<div class="notification-empty">No items found</div>';
                        if (result && result.data && result.data.debug) {
                            console.log('Debug: All announcements:', result.data.debug.announcements_total);
                            console.log('Debug: Filtered announcements:', result.data.debug.announcements_filtered);
                            console.log('Debug: Combined count:', result.data.debug.combined_count);
                            console.log('Debug: Items count:', result.data.debug.items_count);
                        }
                        return;
                    }
                    
                    // Display items
                    listContainer.innerHTML = items.map(item => {
                        const icon = item.type === 'announcement' ? 'bullhorn' : 'bell';
                        const typeLabel = item.type === 'announcement' ? 'Announcement' : 'Notification';
                        const url = item.type === 'announcement' ? `/announcements/${item.id}` : `/notifications/${item.id}`;
                        const isRead = item.is_read === true || item.is_read === 1;
                        const itemClass = isRead ? 'notification-item' : 'notification-item unread';
                        
                        return `
                            <div class="${itemClass}" onclick="window.handleItemClick('${item.type}', ${item.id}, '${url}', ${isRead})">
                                <div class="notification-item-title">
                                    <i class="fas fa-${icon}"></i> ${typeLabel}: ${item.title}
                                </div>
                                <div class="notification-item-message">
                                    ${item.content ? (item.content.length > 100 ? item.content.substring(0, 100) + '...' : item.content) : 'No content'}
                                </div>
                                <div class="notification-item-time">${window.formatTimeAgo(item.created_at || item.pivot_created_at)}</div>
                            </div>
                        `;
                    }).join('');
                } else if (isAdmin || isStaff) {
                    // For admin/staff: ONLY show pending bookings (no announcements)
                    if (titleElement) {
                        titleElement.textContent = 'Pending Bookings';
                    }
                    if (viewAllLink) {
                        viewAllLink.href = '/bookings';
                    }
                    
                    // Admin/Staff bell only displays user booking requests
                    const result = await API.get('/bookings/pending?limit=10');
                    console.log('Pending bookings API result:', result);
                    
                    // Handle response - check both result.success and direct data access
                    // API.get returns: { success: true, data: { message: "...", data: { bookings: [], count: ... } } }
                    let bookings = [];
                    if (result && result.success !== false) {
                        if (result.data && result.data.data && result.data.data.bookings && Array.isArray(result.data.data.bookings)) {
                            bookings = result.data.data.bookings;
                        } else if (result.data && result.data.bookings && Array.isArray(result.data.bookings)) {
                            bookings = result.data.bookings;
                        } else if (result.data && Array.isArray(result.data)) {
                            bookings = result.data;
                        } else if (Array.isArray(result.bookings)) {
                            bookings = result.bookings;
                        }
                    }
                    
                    console.log('Bookings extracted:', bookings.length);
                    
                    if (!bookings || bookings.length === 0) {
                        listContainer.innerHTML = '<div class="notification-empty">No pending bookings</div>';
                        return;
                    }
                    
                    listContainer.innerHTML = bookings.map(booking => `
                        <div class="notification-item unread" onclick="window.handleBookingClick(${booking.id})">
                            <div class="notification-item-title">
                                <i class="fas fa-calendar-check"></i> ${booking.facility_name}
                            </div>
                            <div class="notification-item-message">
                                Booking ${booking.id} from ${booking.user_name}<br>
                                ${booking.booking_date} ${booking.start_time} - ${booking.end_time}
                            </div>
                            <div class="notification-item-time">${window.formatTimeAgo(booking.created_at)}</div>
                            <div class="notification-item-actions" onclick="event.stopPropagation()">
                                <button class="btn-approve" onclick="window.approveBooking(${booking.id}, event)" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-reject" onclick="window.rejectBooking(${booking.id}, event)" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading notification dropdown content:', error);
                listContainer.innerHTML = '<div class="notification-empty">Error loading content: ' + error.message + '</div>';
            }
        };

        // Handle item click (announcement or notification) - make it global
        window.handleItemClick = async function(type, id, url, isRead) {
            // Mark as read only if not already read
            if (!isRead) {
                try {
                    if (type === 'announcement') {
                        await API.put(`/announcements/${id}/read`, {});
                    } else {
                        await API.put(`/notifications/${id}/read`, {});
                    }
                    // Refresh badge count
                    loadNotificationCount();
                } catch (error) {
                    console.error('Error marking as read:', error);
                }
            }
            
            // Navigate to detail page
            window.location.href = url;
        };

        // Handle booking click (admin) - make it global
        window.handleBookingClick = function(bookingId) {
            window.location.href = `/bookings/${bookingId}`;
        };

        // Approve booking (admin) - make it global
        window.approveBooking = async function(bookingId, event) {
            if (event) {
                event.stopPropagation();
            }
            
            if (!confirm('Are you sure you want to approve this booking?')) {
                return;
            }
            
            try {
                const result = await API.put(`/bookings/${bookingId}/approve`, {});
                if (result.success) {
                    showToast('Booking approved successfully', 'success');
                    window.loadNotificationDropdownContent();
                    loadNotificationCount();
                } else {
                    showToast('Error approving booking: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error approving booking:', error);
                showToast('Error approving booking', 'error');
            }
        };

        // Reject booking (admin) - make it global
        window.rejectBooking = async function(bookingId, event) {
            if (event) {
                event.stopPropagation();
            }
            
            const reason = prompt('Please enter rejection reason:');
            if (!reason || reason.trim() === '') {
                return;
            }
            
            try {
                const result = await API.put(`/bookings/${bookingId}/reject`, { reason: reason.trim() });
                if (result.success) {
                    showToast('Booking rejected successfully', 'success');
                    window.loadNotificationDropdownContent();
                    loadNotificationCount();
                } else {
                    showToast('Error rejecting booking: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error rejecting booking:', error);
                showToast('Error rejecting booking', 'error');
            }
        };

        // Format time ago - make it global
        window.formatTimeAgo = function(dateString) {
            if (!dateString) return 'Unknown';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
        }
    </script>
</head>
<body>

    <header>
        <div class="logo"><h1><a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">TARUMT FMS</a></h1></div>
        <nav>
            <ul>
                @auth
                    @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                        {{-- Admin/Staff Navigation --}}
                        <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('admin.users.index') }}">User Management</a></li>
                        <li><a href="{{ route('admin.facilities.index') }}">Facility Management</a></li>
                        <li><a href="{{ route('admin.bookings.index') }}">Booking Management</a></li>
                        <li><a href="{{ route('admin.announcements.index') }}">Announcement Management</a></li>
                        <li><a href="{{ route('admin.loyalty.index') }}">Loyalty Management</a></li>
                        <li><a href="{{ route('feedbacks.index') }}">Feedback Management</a></li>
                    @else
                        {{-- Student Navigation --}}
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                        <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
                        <li><a href="{{ route('notifications.index') }}">Notifications</a></li>
                        <li><a href="{{ route('loyalty.index') }}">Loyalty</a></li>
                        <li><a href="{{ route('feedbacks.index') }}">Feedback</a></li>
                    @endif
                @endauth
                @guest
                    {{-- Guest Navigation --}}
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('facilities.index') }}">Facilities</a></li>
                @endguest
                <li id="authLinks">
                    @auth
                        <!-- Notification Dropdown - All authenticated users -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <button class="notification-button" onclick="window.toggleNotificationMenu(event)" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notificationNavBadge" style="display: none;">0</span>
                            </button>
                            <div class="notification-menu" id="notificationMenu">
                                <div class="notification-header">
                                    <h3 id="notificationMenuTitle">Announcements</h3>
                                    <a href="{{ route('notifications.index') }}" id="viewAllLink" class="view-all-link">View All</a>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <div class="notification-loading">Loading...</div>
                                </div>
                            </div>
                        </div>
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
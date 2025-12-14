// Global API Helper Functions
const API = {
    baseURL: '/api',
    
    // Get auth token from localStorage
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    // Get user from localStorage
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },
    
    // Check if user is admin
    isAdmin() {
        const user = this.getUser();
        if (!user) {
            console.log('🔍 isAdmin: No user found');
            return false;
        }
        
        console.log('🔍 isAdmin: Checking user data...');
        console.log('  - User ID:', user.id);
        console.log('  - User Email:', user.email);
        console.log('  - role_id:', user.role_id, typeof user.role_id);
        console.log('  - role:', user.role);
        console.log('  - role type:', typeof user.role);
        
        if (user.role && typeof user.role === 'object' && user.role !== null) {
            console.log('  - role.name:', user.role.name);
            console.log('  - role.id:', user.role.id);
        }
        
        // Check role_id first (most reliable)
        if (user.role_id === 1) {
            console.log('✅ isAdmin: Detected admin by role_id === 1');
            return true;
        }
        
        // Check if role is an object and has name property
        if (user.role && typeof user.role === 'object' && user.role !== null) {
            const roleName = user.role.name;
            console.log('  - Checking role name:', roleName);
            if (roleName === 'admin' || roleName === 'administrator') {
                console.log('✅ isAdmin: Detected admin by role name:', roleName);
                return true;
            }
        }
        
        // Check if role is a string (backward compatibility)
        if (typeof user.role === 'string') {
            const roleName = user.role.toLowerCase();
            console.log('  - Checking role string:', roleName);
            if (roleName === 'admin' || roleName === 'administrator') {
                console.log('✅ isAdmin: Detected admin by role string:', roleName);
                return true;
            }
        }
        
        console.log('❌ isAdmin: User is NOT admin');
        console.log('  - To become admin, set role_id = 1 in database');
        return false;
    },
    
    // Check if user is staff
    isStaff() {
        const user = this.getUser();
        if (!user) return false;
        
        if (typeof user.role === 'string') {
            return user.role.toLowerCase() === 'staff';
        }
        
        if (user.role && typeof user.role === 'object' && user.role !== null) {
            return user.role.name?.toLowerCase() === 'staff';
        }
        
        return false;
    },
    
    // Check if user is student
    isStudent() {
        const user = this.getUser();
        if (!user) return false;
        
        if (typeof user.role === 'string') {
            return user.role.toLowerCase() === 'student';
        }
        
        if (user.role && typeof user.role === 'object' && user.role !== null) {
            return user.role.name?.toLowerCase() === 'student';
        }
        
        return false;
    },
    
    isAdminOrStaff() {
        return this.isAdmin() || this.isStaff();
    },
    
    // Check if user is authenticated
    isAuthenticated() {
        return !!this.getToken();
    },
    
    // Make API request
    async request(endpoint, options = {}) {
        const token = this.getToken();
        const url = `${this.baseURL}${endpoint}`;
        
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        if (token) {
            defaultHeaders['Authorization'] = `Bearer ${token}`;
        }
        
        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...(options.headers || {})
            }
        };
        
        try {
            const response = await fetch(url, config);
            let data;
            
            // Try to parse JSON, but handle non-JSON responses
            try {
                const text = await response.text();
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                data = { message: 'Invalid response from server' };
            }
            
            if (!response.ok) {
                // Handle validation errors
                if (response.status === 422 && data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    return { success: false, error: errorMessages, data };
                }
                return { success: false, error: data.message || `Request failed (${response.status})`, data };
            }
            
            return { success: true, data };
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message || 'Network error. Please check your connection.' };
        }
    },
    
    // GET request
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },
    
    // POST request
    async post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },
    
    // PUT request
    async put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },
    
    // DELETE request
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },
    
    // Login
    async login(email, password) {
        const result = await this.post('/login', { email, password });
        if (result.success) {
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user', JSON.stringify(result.data.user));
        }
        return result;
    },
    
    // Register
    async register(userData) {
        const result = await this.post('/register', userData);
        if (result.success) {
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user', JSON.stringify(result.data.user));
        }
        return result;
    },
    
    // Logout
    logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    },
    
    // Redirect to login if not authenticated
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '/login';
            return false;
        }
        return true;
    }
};

// Show loading spinner
function showLoading(element) {
    if (element) {
        element.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

// Show error message
function showError(element, message) {
    if (element) {
        element.innerHTML = `<div class="error-message">${message}</div>`;
    }
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

// Format datetime
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString();
}


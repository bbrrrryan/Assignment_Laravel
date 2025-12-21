
const API = {
    baseURL: '/api',
    
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },
    
    isAdmin() {
        const user = this.getUser();
        if (!user) {
            return false;
        }
        
        if (user.role_id === 1) {
            return true;
        }
        
        if (user.role && typeof user.role === 'object' && user.role !== null) {
            const roleName = user.role.name;
            if (roleName === 'admin' || roleName === 'administrator') {
                return true;
            }
        }
        
        if (typeof user.role === 'string') {
            const roleName = user.role.toLowerCase();
            if (roleName === 'admin' || roleName === 'administrator') {
                return true;
            }
        }
        
        return false;
    },
    
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
    
    isAuthenticated() {
        return !!this.getToken();
    },
    
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
            
            try {
                const text = await response.text();
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                data = { message: 'Invalid response from server' };
            }
            
            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    return { success: false, error: errorMessages, data };
                }
                if (response.status === 400) {
                    return { success: false, error: data.message || 'Bad Request', data };
                }
                if (response.status === 503) {
                    return { success: false, error: data.message || 'Service Unavailable', data };
                }
                return { success: false, error: data.message || `Request failed (${response.status})`, data };
            }
            
            return { success: true, data };
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message || 'Network error. Please check your connection.' };
        }
    },
    
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },
    
    async post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },
    
    async put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },
    
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },
    
    async login(email, password) {
        const result = await this.post('/login', { email, password });
        if (result.success) {
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user', JSON.stringify(result.data.user));
        }
        return result;
    },
    
    async register(userData) {
        const result = await this.post('/register', userData);
        if (result.success) {
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user', JSON.stringify(result.data.user));
        }
        return result;
    },
    
    logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    },
    
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '/login';
            return false;
        }
        return true;
    }
};

function showLoading(element) {
    if (element) {
        element.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

function showError(element, message) {
    if (element) {
        element.innerHTML = `<div class="error-message">${message}</div>`;
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '-';
    
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '-';
    
    return d.toLocaleString();
}


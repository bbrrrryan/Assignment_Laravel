document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadProfile();
});

async function loadProfile() {
    const result = await API.get('/me');

    if (result.success) {
        const user = result.data.user;
        displayProfile(user);
    } else {
        alert('Error loading profile: ' + (result.error || 'Unknown error'));
    }
}

function displayProfile(user) {
    // Display header info
    document.getElementById('profileName').textContent = user.name || 'N/A';
    document.getElementById('profileEmail').textContent = user.email || 'N/A';
    
    const roleName = user.role ? (typeof user.role === 'object' ? user.role.name : user.role) : 'N/A';
    document.getElementById('profileRole').textContent = roleName.charAt(0).toUpperCase() + roleName.slice(1);
    document.getElementById('profileRole').className = 'role-badge role-' + roleName.toLowerCase();

    // Show studentid if user is a student
    const studentIdElement = document.getElementById('profileStudentId');
    if (user.role === 'student' && user.studentid) {
        studentIdElement.textContent = user.studentid;
        studentIdElement.className = 'role-badge role-student';
        studentIdElement.style.display = 'inline-block';
    } else {
        studentIdElement.style.display = 'none';
    }

    // Fill form fields
    document.getElementById('profileNameInput').value = user.name || '';
    document.getElementById('profileEmailInput').value = user.email || '';
    document.getElementById('profilePhoneInput').value = user.phone_number || '';
    document.getElementById('profileAddressInput').value = user.address || '';
    document.getElementById('profileRoleInput').value = roleName;
    document.getElementById('profileStatusInput').value = user.status || 'N/A';

    // Clear password fields
    document.getElementById('newPasswordInput').value = '';
    document.getElementById('confirmPasswordInput').value = '';
}

async function updateProfile() {
    const name = document.getElementById('profileNameInput').value;
    const phone = document.getElementById('profilePhoneInput').value;
    const address = document.getElementById('profileAddressInput').value;
    const newPassword = document.getElementById('newPasswordInput').value;
    const confirmPassword = document.getElementById('confirmPasswordInput').value;

    if (!name) {
        alert('Name is required');
        return;
    }

    if (newPassword && newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }

    const data = {
        name: name,
        phone_number: phone || null,
        address: address || null,
    };

    if (newPassword) {
        data.password = newPassword;
        data.password_confirmation = confirmPassword;
    }

    const result = await API.put('/users/profile/update', data);

    if (result.success) {
        if (typeof showToast === 'function') {
            showToast('Profile updated successfully!', 'success');
        } else {
            alert('Profile updated successfully!');
        }
        loadProfile();
        
        // Update localStorage user data
        const updatedUser = result.data.data;
        localStorage.setItem('user', JSON.stringify(updatedUser));
        
        // Update header name
        const headerName = document.querySelector('#authLinks span');
        if (headerName) {
            headerName.textContent = updatedUser.name || 'User';
        }
    } else {
        alert('Error updating profile: ' + (result.error || 'Unknown error'));
    }
}

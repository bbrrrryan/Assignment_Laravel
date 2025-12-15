// Load remembered email on page load
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.querySelector('input[name="remember"]');
    
    if (!emailInput) return;

    // Load saved email from localStorage
    const savedEmail = localStorage.getItem('remembered_email');
    if (savedEmail && !emailInput.value) {
        emailInput.value = savedEmail;
        // Auto-check remember me if email was saved
        if (rememberCheckbox) {
            rememberCheckbox.checked = true;
        }
    }

    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const errorMsg = document.getElementById('errorMessage');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        const emailInput = document.getElementById('email');
        const rememberCheckbox = document.querySelector('input[name="remember"]');
        
        // Save email to localStorage if remember me is checked
        if (rememberCheckbox && rememberCheckbox.checked) {
            localStorage.setItem('remembered_email', emailInput.value);
        } else {
            // Remove saved email if remember me is unchecked
            localStorage.removeItem('remembered_email');
        }
        
        if (submitBtn) {
            // Show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
        }
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
        
        // Submit form normally (will redirect based on role on server side)
        e.target.submit();
    });
});

/**
 * Author: Liew Zi Li
 */

document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.querySelector('input[name="remember"]');
    
    if (!emailInput) return;

    const savedEmail = localStorage.getItem('remembered_email');
    if (savedEmail && !emailInput.value) {
        emailInput.value = savedEmail;
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
        
        if (rememberCheckbox && rememberCheckbox.checked) {
            localStorage.setItem('remembered_email', emailInput.value);
        } else {
            localStorage.removeItem('remembered_email');
        }
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
        }
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
        
        e.target.submit();
    });
});

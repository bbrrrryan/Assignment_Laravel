/**
 * Author: Liew Zi Li
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const errorMsg = document.getElementById('errorMessage');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        
        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;
        
        if (password !== passwordConfirmation) {
            if (errorMsg) {
                errorMsg.textContent = 'Passwords do not match';
                errorMsg.style.display = 'block';
            }
            return;
        }
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating account...';
        }
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
        
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: password,
            password_confirmation: passwordConfirmation
        };
        
        if (typeof API === 'undefined' || typeof API.register !== 'function') {
            if (errorMsg) {
                errorMsg.textContent = 'API not available. Please refresh the page.';
                errorMsg.style.display = 'block';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            return;
        }
        
        const result = await API.register(formData);
        
        if (result.success) {
            if (result.data && result.data.redirect_to_otp) {
                if (typeof showToast === 'function') {
                    showToast('OTP already sent. Please check your email and verify.', 'info');
                }
                setTimeout(function() {
                    window.location.href = '/verify-otp?email=' + encodeURIComponent(result.data.email);
                }, 1500);
            } else {
                if (typeof showToast === 'function') {
                    showToast('Success register. Check email for OTP code', 'success');
                }
                setTimeout(function() {
                    window.location.href = '/verify-otp?email=' + encodeURIComponent(formData.email);
                }, 1500);
            }
        } else {
            const errorText = result.error || 'Registration failed. Please try again.';
            if (errorMsg) {
                errorMsg.textContent = errorText;
                errorMsg.style.display = 'block';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
});

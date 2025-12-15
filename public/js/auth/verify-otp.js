document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verifyOtpForm');
    const otpInput = document.getElementById('otp_code');
    const errorBox = document.getElementById('errorMessage');

    if (!form || !otpInput) return;

    form.addEventListener('submit', function(e) {
        const otpValue = otpInput.value.replace(/\D/g, ''); // Remove non-digits
        
        if (otpValue.length !== 6) {
            e.preventDefault();
            if (errorBox) {
                errorBox.textContent = 'Please enter 6-digit OTP code';
                errorBox.style.display = 'block';
            }
            return false;
        }
        
        otpInput.value = otpValue;
    });

    // Auto format OTP input
    otpInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    const resendLink = document.getElementById('resendOtpLink');
    if (resendLink) {
        resendLink.addEventListener('click', resendOtp);
    }
});

// Resend OTP function
async function resendOtp(event) {
    event.preventDefault();
    
    const emailInput = document.getElementById('email');
    if (!emailInput || !emailInput.value) {
        if (typeof showToast === 'function') {
            showToast('Please enter your email address first', 'error');
        } else {
            alert('Please enter your email address first');
        }
        return;
    }
    
    const email = emailInput.value;
    const resendLink = document.getElementById('resendOtpLink');
    const originalText = resendLink ? resendLink.textContent : '';
    
    if (resendLink) {
        resendLink.textContent = 'Sending...';
        resendLink.style.pointerEvents = 'none';
    }
    
    try {
        const response = await fetch('/api/resend-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            if (typeof showToast === 'function') {
                showToast('OTP code resent successfully. Please check your email.', 'success');
            } else {
                alert('OTP code resent successfully. Please check your email.');
            }
        } else {
            const msg = data.message || 'Failed to resend OTP. Please try again.';
            if (typeof showToast === 'function') {
                showToast(msg, 'error');
            } else {
                alert(msg);
            }
        }
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('Network error. Please try again.', 'error');
        } else {
            alert('Network error. Please try again.');
        }
    } finally {
        if (resendLink) {
            resendLink.textContent = originalText;
            resendLink.style.pointerEvents = 'auto';
        }
    }
}

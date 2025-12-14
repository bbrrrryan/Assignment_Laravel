{{-- Author: Liew Zi Li (auth register) --}}
@extends('layouts.app')

@section('title', 'Register - TARUMT FMS')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Sign up to get started</p>
        </div>

        <form id="registerForm" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-primary">Sign Up</button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorMsg = document.getElementById('errorMessage');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('password_confirmation').value;
    
    if (password !== passwordConfirmation) {
        errorMsg.textContent = 'Passwords do not match';
        errorMsg.style.display = 'block';
        return;
    }
    
    // Show loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating account...';
    errorMsg.style.display = 'none';
    
    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        password: password,
        password_confirmation: passwordConfirmation
    };
    
    const result = await API.register(formData);
    
    if (result.success) {
        // Check if need to redirect to OTP page (OTP already sent)
        if (result.data && result.data.redirect_to_otp) {
            showToast('OTP already sent. Please check your email and verify.', 'info');
            setTimeout(function() {
                window.location.href = '/verify-otp?email=' + encodeURIComponent(result.data.email);
            }, 1500);
        } else {
            // New registration, email sent successfully
            showToast('Success register. Check email for OTP code', 'success');
            // Redirect to OTP verification page
            setTimeout(function() {
                window.location.href = '/verify-otp?email=' + encodeURIComponent(formData.email);
            }, 1500);
        }
    } else {
        const errorText = result.error || 'Registration failed. Please try again.';
        errorMsg.textContent = errorText;
        errorMsg.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>
@endsection


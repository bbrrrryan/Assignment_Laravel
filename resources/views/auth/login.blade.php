@extends('layouts.app')

@section('title', 'Login - TARUMT FMS')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <form id="loginForm" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const errorMsg = document.getElementById('errorMessage');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Show loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in...';
    errorMsg.style.display = 'none';
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    const result = await API.login(email, password);
    
    if (result.success) {
        window.location.href = '/dashboard';
    } else {
        errorMsg.textContent = result.error || 'Login failed. Please check your credentials.';
        errorMsg.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>
@endsection


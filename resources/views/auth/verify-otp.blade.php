{{-- Author: Liew Zi Li (auth verify otp) --}}
@extends('layouts.app')

@section('title', 'Verify OTP - TARUMT FMS')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Verify Your Account</h2>
            <p>Enter the OTP code sent to your email</p>
        </div>

        <form id="verifyOtpForm" method="POST" action="{{ route('verify-otp.post') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', request('email')) }}" required autofocus>
                @error('email')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="otp_code">OTP Code</label>
                <input type="text" id="otp_code" name="otp_code" maxlength="6" pattern="[0-9]{6}" required 
                       placeholder="000000" style="text-align: center; letter-spacing: 5px; font-size: 24px; font-weight: bold;">
                @error('otp_code')
                    <span class="error-text">{{ $message }}</span>
                @enderror
                <small style="display: block; margin-top: 5px; color: #666;">Enter the 6-digit code from your email</small>
            </div>

            <button type="submit" class="btn-primary">Verify & Activate</button>
        </form>

        <div class="auth-footer">
            <p>Didn't receive the code? <a href="#" id="resendOtpLink" onclick="resendOtp(event)">Resend OTP</a></p>
            <p>Already verified? <a href="{{ route('login') }}">Sign in</a></p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>
</div>

<script>
document.getElementById('verifyOtpForm').addEventListener('submit', function(e) {
    const otpInput = document.getElementById('otp_code');
    const otpValue = otpInput.value.replace(/\D/g, ''); // Remove non-digits
    
    if (otpValue.length !== 6) {
        e.preventDefault();
        document.getElementById('errorMessage').textContent = 'Please enter 6-digit OTP code';
        document.getElementById('errorMessage').style.display = 'block';
        return false;
    }
    
    otpInput.value = otpValue;
});

// Auto format OTP input
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});

// Resend OTP function
async function resendOtp(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    if (!email) {
        showToast('Please enter your email address first', 'error');
        return;
    }
    
    const resendLink = document.getElementById('resendOtpLink');
    const originalText = resendLink.textContent;
    resendLink.textContent = 'Sending...';
    resendLink.style.pointerEvents = 'none';
    
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
            showToast('OTP code resent successfully. Please check your email.', 'success');
        } else {
            showToast(data.message || 'Failed to resend OTP. Please try again.', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    } finally {
        resendLink.textContent = originalText;
        resendLink.style.pointerEvents = 'auto';
    }
}
</script>
@endsection


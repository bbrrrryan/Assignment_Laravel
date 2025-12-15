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
            <p>Didn't receive the code? <a href="#" id="resendOtpLink">Resend OTP</a></p>
            <p>Already verified? <a href="{{ route('login') }}">Sign in</a></p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>
</div>

<script src="{{ asset('js/auth/verify-otp.js') }}"></script>
@endsection


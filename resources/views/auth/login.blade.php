{{-- Author: Liew Zi Li (auth login) --}}
@extends('layouts.app')

@section('title', 'Login - TARUMT FMS')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <form id="loginForm" method="POST" action="{{ route('login.post') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>
        </form>
        
        @if (session('error'))
            <div class="error-message" style="display: block; margin-top: 10px;">
                {{ session('error') }}
            </div>
        @endif

        <div class="auth-footer">
            <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
    <script src="{{ asset('js/auth/login.js') }}"></script>
    </div>
</div>
@endsection


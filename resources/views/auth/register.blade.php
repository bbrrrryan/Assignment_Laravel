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

<script src="{{ asset('js/auth/register.js') }}"></script>
@endsection


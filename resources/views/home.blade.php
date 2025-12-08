@extends('layouts.app')

@section('title', 'Home - TARUMT FMS')

@section('content')
    <section class="hero">
        <h2>Facilities Management <br>Redefined.</h2>
        <p>A high-performance digital hub designed to manage and optimize TAR UMT campus assets with absolute precision.</p>
    </section>

    <section class="feature-section">
        <div class="section-title">
            <h4>System Overview</h4>
            <h2>Core Functionalities</h2>
        </div>
        <div class="features">
            <div class="card">
                <i class="fas fa-lock"></i>
                <h3>Secure Access</h3>
                <p>Advanced authentication protocols to ensure only authorized personnel manage facility resources.</p>
            </div>

            <div class="card">
                <i class="fas fa-user-circle"></i>
                <h3>Personal Identity</h3>
                <p>Customize your profile and manage security settings easily within your dedicated user portal.</p>
            </div>

            <div class="card">
                <i class="fas fa-chart-line"></i>
                <h3>Activity Tracking</h3>
                <p>Full transparency on facility statuses and a detailed log of your account's historical actions.</p>
            </div>
        </div>
    </section>
@endsection
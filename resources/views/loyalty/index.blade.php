@extends('layouts.app')

@section('title', 'Loyalty Program - TARUMT FMS')

@section('content')
<link rel="stylesheet" href="{{ asset('css/users/loyalty/index.css') }}">
<div class="page-container">
    <div class="page-header">
        <h1>Loyalty Program</h1>
    </div>

    <div class="loyalty-dashboard">
        <div class="points-card">
            <h2>Your Points</h2>
            <div class="points-display">
                <span id="totalPoints" class="points-value">0</span>
                <p>Total Points</p>
            </div>
        </div>

        <div class="loyalty-tabs">
            <button class="tab-btn active" onclick="showTab('points')">Points History</button>
            <button class="tab-btn" onclick="showTab('my-rewards')">My Rewards</button>
            <button class="tab-btn" onclick="showTab('rewards')">Available Rewards</button>
            <button class="tab-btn" onclick="showTab('certificates')">Certificates</button>
        </div>

        <div id="loyaltyContent">
            <p>Loading...</p>
        </div>
    </div>
</div>

<script src="{{ asset('js/loyalty/index.js') }}"></script>


@endsection


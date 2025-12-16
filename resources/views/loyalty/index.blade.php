@extends('layouts.app')

@section('title', 'Loyalty Program - TARUMT FMS')

@section('content')
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
            <button class="tab-btn" onclick="showTab('rewards')">Available Rewards</button>
            <button class="tab-btn" onclick="showTab('certificates')">Certificates</button>
        </div>

        <div id="loyaltyContent">
            <p>Loading...</p>
        </div>
    </div>
</div>

<script src="{{ asset('js/loyalty/index.js') }}"></script>

<style>
.btn-primary.btn-disabled {
    background: #6c757d !important;
    color: white !important;
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-primary.btn-disabled:hover {
    background: #6c757d !important;
    opacity: 0.7;
}

button:disabled.btn-primary.btn-disabled {
    background: #6c757d !important;
    opacity: 0.7;
}

.reward-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    text-align: center;
}

.reward-card h3 {
    color: #2d3436;
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.reward-card p {
    color: #636e72;
    margin-bottom: 15px;
    min-height: 40px;
}

.reward-points {
    margin: 20px 0;
    font-size: 1.1rem;
    color: #a31f37;
}
</style>
@endsection


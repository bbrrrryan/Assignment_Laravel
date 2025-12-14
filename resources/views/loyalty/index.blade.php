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

<script>
// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    initLoyalty();
});

let currentTab = 'points';

function initLoyalty() {
    loadPoints();
    loadPointsHistory();
}

async function loadPoints() {
    const result = await API.get('/loyalty/points');
    
    if (result.success) {
        document.getElementById('totalPoints').textContent = result.data.total_points || 0;
    }
}

async function loadPointsHistory() {
    showLoading(document.getElementById('loyaltyContent'));
    
    const result = await API.get('/loyalty/points/history');
    
    if (result.success) {
        const history = result.data.data?.data || result.data.data || [];

        const container = document.getElementById('loyaltyContent');
        if (history.length === 0) {
            container.innerHTML = '<p>No points history</p>';
            return;
        }

        container.innerHTML = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Points</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    ${history.map(item => `
                        <tr>
                            <td>${formatDate(item.created_at)}</td>
                            <td>${item.action_type}</td>
                            <td class="${item.points > 0 ? 'text-success' : 'text-danger'}">${item.points > 0 ? '+' : ''}${item.points}</td>
                            <td>${item.description || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load points history');
    }
}

async function loadRewards() {
    showLoading(document.getElementById('loyaltyContent'));
    
    const result = await API.get('/loyalty/rewards');
    
    if (result.success) {
        const rewards = result.data.data || [];

        const container = document.getElementById('loyaltyContent');
        if (rewards.length === 0) {
            container.innerHTML = '<p>No rewards available</p>';
            return;
        }

        container.innerHTML = `
            <div class="rewards-grid">
                ${rewards.map(reward => `
                    <div class="reward-card">
                        <h3>${reward.name}</h3>
                        <p>${reward.description || ''}</p>
                        <div class="reward-points">
                            <strong>${reward.points_required} Points</strong>
                        </div>
                        <button class="btn-primary" onclick="redeemReward(${reward.id}, ${reward.points_required})" 
                                ${getTotalPoints() < reward.points_required ? 'disabled' : ''}>
                            Redeem
                        </button>
                </div>
            `).join('')}
            </div>
        `;
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load rewards');
    }
}

async function loadCertificates() {
    showLoading(document.getElementById('loyaltyContent'));
    
    const result = await API.get('/loyalty/certificates');
    
    if (result.success) {
        const certificates = result.data.data || [];

        const container = document.getElementById('loyaltyContent');
        if (certificates.length === 0) {
            container.innerHTML = '<p>No certificates earned</p>';
            return;
        }

        container.innerHTML = `
            <div class="certificates-grid">
                ${certificates.map(cert => `
                    <div class="certificate-card">
                        <i class="fas fa-certificate"></i>
                        <h3>${cert.title}</h3>
                        <p>${cert.certificate_type}</p>
                        <span class="cert-date">Issued: ${formatDate(cert.issued_date)}</span>
                </div>
            `).join('')}
            </div>
        `;
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load certificates');
    }
}

// Make functions global
window.showTab = function(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    if (tab === 'points') loadPointsHistory();
    else if (tab === 'rewards') loadRewards();
    else if (tab === 'certificates') loadCertificates();
};

function getTotalPoints() {
    return parseInt(document.getElementById('totalPoints').textContent) || 0;
}

window.redeemReward = async function(rewardId, pointsRequired) {
    if (!confirm(`Redeem this reward for ${pointsRequired} points?`)) return;

    const result = await API.post('/loyalty/rewards/redeem', { reward_id: rewardId });

    if (result.success) {
        alert('Reward redeemed successfully! Awaiting approval.');
        loadPoints();
        loadRewards();
    } else {
        alert(result.error || 'Error redeeming reward');
    }
};
</script>
@endsection


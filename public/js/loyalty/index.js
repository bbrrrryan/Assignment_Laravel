// Define getTotalPoints function early
function getTotalPoints() {
    const element = document.getElementById('totalPoints');
    return element ? parseInt(element.textContent) || 0 : 0;
}

// Define redeemReward function early to ensure it's available
window.redeemReward = async function(rewardId, pointsRequired) {
    console.log('redeemReward called with:', { rewardId, pointsRequired });
    
    const totalPoints = getTotalPoints();
    console.log('Total points:', totalPoints);
    
    // Check points before confirming
    if (totalPoints < pointsRequired) {
        const errorMsg = `Insufficient points. Required: ${pointsRequired}, Available: ${totalPoints}`;
        console.log('Insufficient points, showing toast:', errorMsg);
        console.log('showToast function exists:', typeof showToast === 'function');
        console.log('window.showToast exists:', typeof window.showToast === 'function');
        console.log('toastContainer exists:', !!document.getElementById('toastContainer'));
        
        if (typeof showToast === 'function') {
            try {
                showToast(errorMsg, 'error');
            } catch (e) {
                console.error('Error showing toast:', e);
                alert(errorMsg);
            }
        } else if (typeof window.showToast === 'function') {
            try {
                window.showToast(errorMsg, 'error');
            } catch (e) {
                console.error('Error showing toast:', e);
                alert(errorMsg);
            }
        } else {
            alert(errorMsg);
        }
        return;
    }
    
    if (!confirm(`Redeem this reward for ${pointsRequired} points?`)) {
        console.log('User cancelled redemption');
        return;
    }

    console.log('Proceeding with redemption...');
    
    try {
        const result = await API.post('/loyalty/rewards/redeem', { reward_id: rewardId });
        console.log('Redemption result:', result);

        if (result.success) {
            const successMsg = 'Reward redeemed successfully! Awaiting approval.';
            if (typeof showToast === 'function') {
                showToast(successMsg, 'success');
            } else if (typeof window.showToast === 'function') {
                window.showToast(successMsg, 'success');
            } else {
                alert(successMsg);
            }
            loadPoints();
            loadRewards();
        } else {
            // Show error message from API
            const errorMessage = result.error || result.message || result.data?.message || 'Error redeeming reward';
            console.log('Redemption error:', errorMessage);
            if (typeof showToast === 'function') {
                showToast(errorMessage, 'error');
            } else if (typeof window.showToast === 'function') {
                window.showToast(errorMessage, 'error');
            } else {
                alert(errorMessage);
            }
        }
    } catch (error) {
        console.error('Error redeeming reward:', error);
        const errorMsg = error.message || 'An error occurred while redeeming the reward';
        if (typeof showToast === 'function') {
            showToast(errorMsg, 'error');
        } else if (typeof window.showToast === 'function') {
            window.showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
    }
};

let currentTab = 'points';

function initLoyalty() {
    loadPoints();
    loadPointsHistory();
}

async function loadPoints() {
    const result = await API.get('/loyalty/points');
    
    if (result.success) {
        // API returns: { status, data: { total_points }, timestamp }
        const totalPoints = (result.data && result.data.data && typeof result.data.data.total_points !== 'undefined')
            ? result.data.data.total_points
            : 0;
        document.getElementById('totalPoints').textContent = totalPoints;
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
                    ${history.map(item => {
                        const formattedAction = formatActionType(item.action_type);
                        const formattedDescription = formatDescription(item.action_type, item.description);
                        return `
                        <tr>
                            <td>${formatDate(item.created_at)}</td>
                            <td>${formattedAction}</td>
                            <td class="${item.points > 0 ? 'text-success' : 'text-danger'}">${item.points > 0 ? '+' : ''}${item.points}</td>
                            <td>${formattedDescription}</td>
                        </tr>
                    `;
                    }).join('')}
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
                ${rewards.map(reward => {
                    const totalPoints = getTotalPoints();
                    const hasEnoughPoints = totalPoints >= reward.points_required;
                    const isOutOfStock = reward.stock_quantity !== null && reward.stock_quantity <= 0;
                    const isDisabled = !hasEnoughPoints || isOutOfStock;
                    
                    const buttonClass = isDisabled ? 'btn-primary btn-disabled' : 'btn-primary';
                    const buttonId = `redeemBtn_${reward.id}`;
                    
                    return `
                    <div class="reward-card">
                        <h3>${reward.name}</h3>
                        <p>${reward.description || ''}</p>
                        <div class="reward-points">
                            <strong>${reward.points_required} Points</strong>
                        </div>
                        ${isOutOfStock ? '<p style="color: #dc3545; font-size: 0.9rem; margin: 10px 0;"><i class="fas fa-exclamation-circle"></i> Out of Stock</p>' : ''}
                        ${!hasEnoughPoints && !isOutOfStock ? '<p style="color: #ff9800; font-size: 0.9rem; margin: 10px 0;"><i class="fas fa-info-circle"></i> Insufficient Points</p>' : ''}
                        <button id="${buttonId}" class="${buttonClass}" 
                                ${isDisabled ? 'disabled' : ''} 
                                data-reward-id="${reward.id}"
                                data-points-required="${reward.points_required}"
                                style="${isDisabled ? 'cursor: not-allowed; opacity: 0.6;' : 'cursor: pointer;'}">
                            Redeem
                        </button>
                </div>
            `;
                }).join('')}
            </div>
        `;
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load rewards');
    }
}

async function loadMyRewards() {
    showLoading(document.getElementById('loyaltyContent'));
    
    const result = await API.get('/loyalty/rewards/my');
    
    if (result.success) {
        const rewards = result.data.data || [];
        const container = document.getElementById('loyaltyContent');
        
        if (rewards.length === 0) {
            container.innerHTML = '<p>No rewards redeemed yet</p>';
            return;
        }

        container.innerHTML = `
            <div class="rewards-grid">
                ${rewards.map(reward => {
                    const status = (reward.status || '').toLowerCase();
                    let statusLabel = 'Pending';
                    let statusClass = 'badge-warning';
                    
                    if (status === 'approved' || status === 'redeemed') {
                        statusLabel = 'Approved';
                        statusClass = 'badge-success';
                    } else if (status === 'cancelled') {
                        statusLabel = 'Cancelled';
                        statusClass = 'badge-secondary';
                    }

                    const typeLabel = reward.reward_type
                        ? reward.reward_type.charAt(0).toUpperCase() + reward.reward_type.slice(1)
                        : 'Reward';

                    return `
                    <div class="reward-card">
                        <h3>${reward.name}</h3>
                        <p>${reward.description || ''}</p>
                        <div class="reward-points">
                            <strong>${reward.points_used || reward.points_required} Points Used</strong>
                        </div>
                        <p style="margin: 5px 0; color: #636e72;">
                            <strong>Type:</strong> ${typeLabel}
                        </p>
                        <p style="margin: 5px 0; color: #636e72;">
                            <strong>Redeemed At:</strong> ${reward.redeemed_at ? formatDate(reward.redeemed_at) : 'N/A'}
                        </p>
                        <span class="badge ${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>
                `;
                }).join('')}
            </div>
        `;
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load my rewards');
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
                        <h3>${cert.title}</h3>
                        <p>${cert.description || 'Certificate'}</p>
                        <span class="cert-date">Issued: ${formatDate(cert.issued_date)}</span>
                        <div style="margin-top: 10px;">
                            <a href="/certificates/${cert.id}/download" class="btn-primary" target="_blank">
                                Download PDF
                            </a>
                        </div>
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
    else if (tab === 'my-rewards') loadMyRewards();
    else if (tab === 'rewards') loadRewards();
    else if (tab === 'certificates') loadCertificates();
};

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatActionType(actionType) {
    if (!actionType) return '-';
    
    const actionMap = {
        'redemption_refund': 'Redemption refund',
        'reward_redemption': 'Reward redemption'
    };
    
    // If we have a mapping, use it
    if (actionMap[actionType]) {
        return actionMap[actionType];
    }
    
    // Otherwise, format by replacing underscores with spaces and capitalizing
    return actionType
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDescription(actionType, description) {
    // For redemption_refund, use specific description
    if (actionType === 'redemption_refund') {
        return 'Refunded for rejected redemption';
    }
    
    // For other types, use the provided description or default
    return description || '-';
}

// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    console.log('Initializing loyalty...');
    
    // Set up event delegation for redeem buttons (only once)
    const rewardsContainer = document.getElementById('loyaltyContent');
    if (rewardsContainer) {
        // Remove any existing event listeners by cloning the element
        const newContainer = rewardsContainer.cloneNode(true);
        rewardsContainer.parentNode.replaceChild(newContainer, rewardsContainer);
        
        // Add event delegation to the container
        document.getElementById('loyaltyContent').addEventListener('click', function(e) {
            const button = e.target.closest('button[data-reward-id]');
            if (button && !button.disabled) {
                e.preventDefault();
                e.stopPropagation();
                const rewardId = parseInt(button.getAttribute('data-reward-id'));
                const pointsRequired = parseInt(button.getAttribute('data-points-required'));
                console.log('Redeem button clicked for reward:', rewardId, 'Points required:', pointsRequired);
                console.log('redeemReward function exists:', typeof window.redeemReward === 'function');
                if (typeof window.redeemReward === 'function') {
                    window.redeemReward(rewardId, pointsRequired);
                } else {
                    console.error('redeemReward function not found!');
                    alert('Error: Redeem function not available. Please refresh the page.');
                }
            }
        });
    }
    
    initLoyalty();
    console.log('redeemReward function available:', typeof window.redeemReward === 'function');
});


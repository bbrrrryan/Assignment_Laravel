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

let allRewards = [];

async function loadRewards() {
    showLoading(document.getElementById('loyaltyContent'));
    
    const result = await API.get('/loyalty/rewards');
    
    if (result.success) {
        allRewards = result.data.data || [];

        const container = document.getElementById('loyaltyContent');
        if (allRewards.length === 0) {
            container.innerHTML = '<p>No rewards available</p>';
            return;
        }

        renderRewardsSection(allRewards);
    } else {
        showError(document.getElementById('loyaltyContent'), result.error || 'Failed to load rewards');
    }
}

function renderRewardsSection(rewards) {
    const container = document.getElementById('loyaltyContent');
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" 
                               id="rewardSearchInput" 
                               class="filter-input" 
                               placeholder="Search by name, type, or description..." 
                               value=""
                               oninput="filterRewards()"
                               onkeyup="filterRewards()">
                        <button type="button" 
                                class="filter-clear-btn" 
                                id="rewardSearchClear" 
                                style="display: none;" 
                                onclick="clearRewardSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="rewardsTableContainer" style="overflow-x: auto;">
        </div>
    `;
    
    updateRewardsTable(rewards);
    
    const searchInput = document.getElementById('rewardSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const clearBtn = document.getElementById('rewardSearchClear');
            if (clearBtn) {
                clearBtn.style.display = this.value.length > 0 ? 'flex' : 'none';
            }
        });
    }
}

function updateRewardsTable(rewards) {
    const tableContainer = document.getElementById('rewardsTableContainer');
    if (!tableContainer) {
        renderRewardsSection(rewards);
        return;
    }
    
    const totalPoints = getTotalPoints();
    
    if (rewards.length === 0) {
        tableContainer.innerHTML = '<p>No rewards found matching your search.</p>';
        return;
    }

    tableContainer.innerHTML = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reward Name</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Points Required</th>
                    <th>Stock Status</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${rewards.map(reward => {
                    const hasEnoughPoints = totalPoints >= reward.points_required;
                    const isOutOfStock = reward.stock_quantity !== null && reward.stock_quantity <= 0;
                    const isDisabled = !hasEnoughPoints || isOutOfStock;
                    
                    const typeLabel = reward.reward_type 
                        ? reward.reward_type.charAt(0).toUpperCase() + reward.reward_type.slice(1)
                        : 'Reward';
                    
                    let stockStatus = '';
                    let availabilityStatus = '';
                    let availabilityClass = '';
                    
                    if (reward.stock_quantity === null) {
                        stockStatus = 'Unlimited';
                        availabilityStatus = 'Available';
                        availabilityClass = 'badge-success';
                    } else {
                        stockStatus = `${reward.stock_quantity} available`;
                        if (reward.stock_quantity <= 0) {
                            availabilityStatus = 'Out of Stock';
                            availabilityClass = 'badge-secondary';
                        } else if (reward.stock_quantity <= 5) {
                            availabilityStatus = 'Low Stock';
                            availabilityClass = 'badge-warning';
                        } else {
                            availabilityStatus = 'Available';
                            availabilityClass = 'badge-success';
                        }
                    }
                    
                    if (!hasEnoughPoints && !isOutOfStock) {
                        availabilityStatus = 'Insufficient Points';
                        availabilityClass = 'badge-warning';
                    }
                    
                    const description = reward.description || 'No description';
                    const truncatedDescription = description.length > 80 
                        ? description.substring(0, 80) + '...' 
                        : description;
                    
                    return `
                    <tr>
                        <td><strong>${reward.name}</strong></td>
                        <td>${typeLabel}</td>
                        <td title="${description}">${truncatedDescription}</td>
                        <td style="color: #a31f37; font-weight: bold;">${reward.points_required}</td>
                        <td>${stockStatus}</td>
                        <td><span class="badge ${availabilityClass}">${availabilityStatus}</span></td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button onclick="viewRewardDetail(${reward.id})" 
                                        class="btn-secondary" 
                                        style="padding: 6px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button onclick="window.redeemReward(${reward.id}, ${reward.points_required})" 
                                        class="btn-primary" 
                                        ${isDisabled ? 'disabled' : ''} 
                                        data-reward-id="${reward.id}"
                                        data-points-required="${reward.points_required}"
                                        style="padding: 6px 12px; font-size: 0.85rem; ${isDisabled ? 'cursor: not-allowed; opacity: 0.6;' : 'cursor: pointer;'}">
                                    <i class="fas fa-gift"></i> Redeem
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('')}
            </tbody>
        </table>
    `;
}


window.filterRewards = function() {
    const searchInput = document.getElementById('rewardSearchInput');
    if (!searchInput) return;
    
    const clearBtn = document.getElementById('rewardSearchClear');
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (clearBtn) {
        clearBtn.style.display = searchInput.value.length > 0 ? 'flex' : 'none';
    }
    
    if (!searchTerm) {
        updateRewardsTable(allRewards);
        return;
    }
    
    const filtered = allRewards.filter(reward => {
        const name = (reward.name || '').toLowerCase();
        const description = (reward.description || '').toLowerCase();
        const type = (reward.reward_type || '').toLowerCase();
        const points = String(reward.points_required || '');
        
        return name.includes(searchTerm) || 
               description.includes(searchTerm) || 
               type.includes(searchTerm) ||
               points.includes(searchTerm);
    });
    
    updateRewardsTable(filtered);
};

window.clearRewardSearch = function() {
    const searchInput = document.getElementById('rewardSearchInput');
    const clearBtn = document.getElementById('rewardSearchClear');
    
    if (searchInput) {
        searchInput.value = '';
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
        updateRewardsTable(allRewards);
        searchInput.focus();
    }
};

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
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reward Name</th>
                        <th>Type</th>
                        <th>Points Used</th>
                        <th>Redeemed Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
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
                        <tr>
                            <td>${reward.name}</td>
                            <td>${typeLabel}</td>
                            <td>${reward.points_used || reward.points_required}</td>
                            <td>${reward.redeemed_at ? formatDate(reward.redeemed_at) : 'N/A'}</td>
                            <td><span class="badge ${statusClass}">${statusLabel}</span></td>
                        </tr>
                    `;
                    }).join('')}
                </tbody>
            </table>
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

// View reward detail function
window.viewRewardDetail = async function(rewardId) {
    try {
        const result = await API.get(`/loyalty/rewards/${rewardId}`);
        
        if (result.success) {
            const reward = result.data.data;
            showRewardModal(reward);
        } else {
            const errorMsg = result.error || result.message || 'Failed to load reward details';
            if (typeof showToast === 'function') {
                showToast(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error loading reward details:', error);
        const errorMsg = error.message || 'An error occurred while loading reward details';
        if (typeof showToast === 'function') {
            showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
    }
};

function showRewardModal(reward) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('rewardDetailModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'rewardDetailModal';
        modal.className = 'modal';
        modal.style.display = 'none';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                <span class="close" onclick="closeRewardModal()">&times;</span>
                <div id="rewardDetailContent"></div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRewardModal();
            }
        });
    }
    
    // Populate modal content
    const content = document.getElementById('rewardDetailContent');
    const rewardType = reward.reward_type ? reward.reward_type.charAt(0).toUpperCase() + reward.reward_type.slice(1) : 'Reward';
    const stockInfo = reward.stock_quantity !== null 
        ? `<p><strong>Stock:</strong> ${reward.stock_quantity} available</p>`
        : '<p><strong>Stock:</strong> Unlimited</p>';
    
    content.innerHTML = `
        <h2 style="margin-top: 0;">${reward.name}</h2>
        ${reward.image_url ? `
            <div style="margin: 20px 0; text-align: center;">
                <img src="${reward.image_url}" alt="${reward.name}" 
                     style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            </div>
        ` : ''}
        <div style="margin: 20px 0;">
            <p><strong>Type:</strong> ${rewardType}</p>
            <p><strong>Points Required:</strong> <span style="color: #007bff; font-size: 1.2em; font-weight: bold;">${reward.points_required}</span></p>
            ${stockInfo}
            ${reward.description ? `<p><strong>Description:</strong></p><p style="line-height: 1.6; color: #555;">${reward.description}</p>` : ''}
        </div>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <button onclick="redeemRewardFromModal(${reward.id}, ${reward.points_required})" 
                    class="btn-primary" 
                    style="width: 100%; padding: 12px; font-size: 1.1em;">
                <i class="fas fa-gift"></i> Redeem This Reward
            </button>
        </div>
    `;
    
    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeRewardModal() {
    const modal = document.getElementById('rewardDetailModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

window.redeemRewardFromModal = function(rewardId, pointsRequired) {
    closeRewardModal();
    window.redeemReward(rewardId, pointsRequired);
};

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


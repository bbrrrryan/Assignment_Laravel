document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth() || !API.isAdminOrStaff()) {
        window.location.href = '/';
        return;
    }

    initAdminLoyalty();
});

let currentAdminTab = 'rules';
let currentEditingRuleId = null;
let currentEditingRewardId = null;
let currentSearchData = {
    rules: [],
    rewards: [],
    redemptions: [],
    points: [],
    certificates: [],
};
let searchTimeout = null;

function initAdminLoyalty() {
    loadAdminTab('rules');
    // Don't setup form handler here - it will be set up when modal is first opened
    // Note: Modals can only be closed via close button (X) or Cancel button, not by clicking outside
}

// Update header based on current tab
function updateLoyaltyHeader(title, subtitle, buttonText, buttonAction) {
    const titleEl = document.getElementById('loyaltyPageTitle');
    const subtitleEl = document.getElementById('loyaltyPageSubtitle');
    const buttonEl = document.getElementById('loyaltyHeaderBtn');
    const buttonTextEl = document.getElementById('loyaltyHeaderBtnText');
    
    if (titleEl) titleEl.textContent = title;
    if (subtitleEl) subtitleEl.textContent = subtitle;
    
    if (buttonText && buttonAction) {
        if (buttonTextEl) buttonTextEl.textContent = buttonText;
        if (buttonEl) {
            buttonEl.setAttribute('onclick', buttonAction + '()');
            buttonEl.style.display = 'block';
        }
    } else {
        if (buttonEl) buttonEl.style.display = 'none';
    }
}

// Handle header button click
function handleHeaderButtonClick() {
    // This will be handled by the onclick attribute set in updateLoyaltyHeader
}

let ruleFormHandlerSetup = false;
let rewardFormHandlerSetup = false;

function setupRuleFormHandler() {
    // Only setup once
    if (ruleFormHandlerSetup) return;
    
    const ruleForm = document.getElementById('ruleForm');
    if (!ruleForm) {
        console.warn('Rule form not found, will retry when modal opens');
        return;
    }
    
    ruleFormHandlerSetup = true;
    
    ruleForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const data = {
            action_type: document.getElementById('ruleActionType').value.trim(),
            name: document.getElementById('ruleName').value.trim(),
            description: document.getElementById('ruleDescription').value.trim() || null,
            points: parseInt(document.getElementById('rulePoints').value),
            is_active: document.getElementById('ruleIsActive').checked,
        };
        
        if (!data.action_type || !data.name || isNaN(data.points) || data.points < 0) {
            if (typeof showToast !== 'undefined') {
                showToast('Please fill in all required fields correctly', 'warning');
            } else {
                alert('Please fill in all required fields correctly');
            }
            return;
        }
        
        // Save the editing state before async operations
        const isEditing = !!currentEditingRuleId;
        const editingId = currentEditingRuleId;
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        try {
            let result;
            if (isEditing) {
                // Update existing rule
                console.log('Updating rule:', editingId, data);
                result = await API.put(`/loyalty/rules/${editingId}`, data);
            } else {
                // Create new rule
                console.log('Creating rule:', data);
                result = await API.post('/loyalty/rules', data);
            }
            
            console.log('API Result:', result);
            
            if (result.success) {
                closeRuleModal();
                loadRulesManagement();
                if (typeof showToast !== 'undefined') {
                    showToast(isEditing ? 'Rule updated successfully!' : 'Rule created successfully!', 'success');
                } else {
                    alert(isEditing ? 'Rule updated successfully!' : 'Rule created successfully!');
                }
            } else {
                console.error('API Error:', result);
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Failed to save rule', 'error');
                } else {
                    alert(result.error || 'Failed to save rule');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error saving rule:', error);
            if (typeof showToast !== 'undefined') {
                showToast('An error occurred while saving the rule: ' + error.message, 'error');
            } else {
                alert('An error occurred while saving the rule: ' + error.message);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Note: Modal cannot be closed by clicking outside - only via close button (X) or cancel button
}

window.showAdminTab = function(tab, clickedElement) {
    currentAdminTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    if (clickedElement) {
        clickedElement.classList.add('active');
    } else if (event && event.target) {
        event.target.classList.add('active');
    }
    loadAdminTab(tab);
};

function loadAdminTab(tab) {
    const container = document.getElementById('adminLoyaltyContent');
    
    switch(tab) {
        case 'rules':
            loadRulesManagement();
            break;
        case 'rewards':
            loadRewardsManagement();
            break;
        case 'redemptions':
            loadRedemptionsManagement();
            break;
        case 'points':
            loadPointsManagement();
            break;
        case 'certificates':
            loadCertificatesManagement();
            break;
        case 'reports':
            loadReports();
            break;
    }
}

// Rules Management
async function loadRulesManagement() {
    const container = document.getElementById('adminLoyaltyContent');
    if (!container) {
        console.error('adminLoyaltyContent container not found');
        return;
    }
    
    showLoading(container);
    
    try {
        const result = await API.get('/loyalty/rules');
        
        if (result.success) {
            const rules = result.data.data || [];
            currentSearchData.rules = rules;
            displayRulesManagement(rules);
        } else {
            showError(container, result.error || 'Failed to load rules');
            console.error('Failed to load rules:', result);
        }
    } catch (error) {
        console.error('Error loading rules:', error);
        showError(container, 'An error occurred while loading rules: ' + error.message);
    }
}

function displayRulesManagement(rules) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Store data for search
    currentSearchData.rules = rules;
    
    // Update header
    updateLoyaltyHeader('Point Earning Rules', 'Manage and configure point earning rules', 'Create Rule', 'showCreateRuleModal');
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="rulesSearchInput" class="filter-input" placeholder="Search by name, action type, or description..." oninput="searchRules(this.value)">
                        <button type="button" class="filter-clear-btn" id="rulesSearchClear" style="display: none;" onclick="clearRulesSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action Type</th>
                        <th>Points</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="width: 100px; min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rules.length === 0 ? `
                        <tr>
                            <td colspan="6" class="text-center">No rules found. Create your first rule!</td>
                        </tr>
                    ` : rules.map(rule => `
                        <tr>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><strong>${rule.name}</strong></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">${rule.action_type}</code></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><strong>${rule.points}</strong></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; max-width: 300px; word-wrap: break-word; line-height: 1.4;">${rule.description || 'N/A'}</td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap;">
                                <span class="badge ${rule.is_active ? 'badge-success' : 'badge-secondary'}" style="display: inline-block; vertical-align: bottom;">
                                    ${rule.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap; width: 100px; min-width: 100px; text-align: center;">
                                <button class="btn-sm btn-primary" onclick="editRule(${rule.id})" title="Edit" style="white-space: nowrap !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; margin: 0; vertical-align: bottom;">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Rewards Management
async function loadRewardsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/rewards/all');
    
    if (result.success) {
        const rewards = result.data.data || [];
        currentSearchData.rewards = rewards;
        displayRewardsManagement(rewards);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load rewards');
    }
}

function displayRewardsManagement(rewards) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Store data for search
    currentSearchData.rewards = rewards;
    
    // Update header
    updateLoyaltyHeader('Rewards Management', 'Manage available rewards for redemption', 'Create Reward', 'showCreateRewardModal');
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="rewardsSearchInput" class="filter-input" placeholder="Search by name, type, or description..." oninput="searchRewards(this.value)">
                        <button type="button" class="filter-clear-btn" id="rewardsSearchClear" style="display: none;" onclick="clearRewardsSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Points Required</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rewards.length === 0 ? `
                        <tr>
                            <td colspan="7" class="text-center">No rewards found. Create your first reward!</td>
                        </tr>
                    ` : rewards.map(reward => `
                        <tr>
                            <td><strong>${reward.name}</strong></td>
                            <td><span class="badge badge-info">${reward.reward_type}</span></td>
                            <td><strong>${reward.points_required}</strong></td>
                            <td>${reward.stock_quantity !== null ? reward.stock_quantity : '<span style="color: #10b981;">Unlimited</span>'}</td>
                            <td>${reward.description || 'N/A'}</td>
                            <td>
                                <span class="badge ${reward.is_active ? 'badge-success' : 'badge-secondary'}">
                                    ${reward.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap; text-align: center;">
                                <button class="btn-sm btn-primary btn-square" onclick="editReward(${reward.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-sm btn-danger btn-square" onclick="deleteReward(${reward.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Redemptions Management
async function loadRedemptionsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/redemptions');
    
    if (result.success) {
        const redemptions = result.data.data?.data || result.data.data || [];
        // Always store fresh data from server
        currentSearchData.redemptions = redemptions;
        displayRedemptionsManagement(redemptions);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load redemptions');
    }
}

function displayRedemptionsManagement(redemptions) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Check if this is initial load (container has no filters section yet)
    const hasFilters = container.querySelector('.filters-section');
    
    // Update header
    updateLoyaltyHeader('Redemption Approvals', 'Review and approve reward redemptions', '', '');
    document.getElementById('loyaltyHeaderBtn').style.display = 'none';
    
    // If filters section doesn't exist, create it
    if (!hasFilters) {
        container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="redemptionsSearchInput" class="filter-input" placeholder="Search by reward name, user name, or email..." oninput="searchRedemptions(this.value)">
                        <button type="button" class="filter-clear-btn" id="redemptionsSearchClear" style="display: none;" onclick="clearRedemptionsSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="filter-select-wrapper">
                        <div class="filter-icon">
                            <i class="fas fa-filter"></i>
                        </div>
                        <select id="redemptionStatusFilter" class="filter-select" onchange="filterRedemptions()">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            ${generateRedemptionsTableHTML(redemptions)}
        </div>
    `;
    } else {
        // Filters already exist, just update the table
        const tableContainer = container.querySelector('.table-container');
        if (tableContainer) {
            tableContainer.innerHTML = generateRedemptionsTableHTML(redemptions).replace('<div class="table-container">', '').replace('</div>', '').trim();
        }
    }
}

// Points Management
async function loadPointsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/points/all');
    
    if (result.success) {
        const users = result.data.data || [];
        currentSearchData.points = users;
        displayPointsManagement(users);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load points data');
    }
}

function displayPointsManagement(users) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Store data for search
    currentSearchData.points = users;
    
    // Update header
    updateLoyaltyHeader('Student Points Tracking', 'View and manage student loyalty points', '', '');
    document.getElementById('loyaltyHeaderBtn').style.display = 'none';
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="pointsSearchInput" class="filter-input" placeholder="Search by student name or email..." oninput="searchPoints(this.value)">
                        <button type="button" class="filter-clear-btn" id="pointsSearchClear" style="display: none;" onclick="clearPointsSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Points</th>
                        <th>Transactions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.length === 0 ? `
                        <tr>
                            <td colspan="5" class="text-center">No users found</td>
                        </tr>
                    ` : users.map(user => `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><strong>${user.total_points || 0}</strong></td>
                            <td>${user.points_count || 0}</td>
                            <td class="actions">
                                <button class="btn-sm btn-info btn-square" onclick="viewUserPoints(${user.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-sm btn-primary btn-square" onclick="awardPointsToUser(${user.id})" title="Award Points">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn-sm btn-danger btn-square" onclick="deductPointsFromUser(${user.id})" title="Deduct Points">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Certificates Management
async function loadCertificatesManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/certificates/all');
    
    if (result.success) {
        const certificates = result.data.data || [];
        currentSearchData.certificates = certificates;
        displayCertificatesManagement(certificates);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load certificates');
    }
}

function displayCertificatesManagement(certificates) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Store data for search
    currentSearchData.certificates = certificates;
    
    // Update header
    updateLoyaltyHeader('Certificates Management', 'Issue and manage student certificates', 'Issue Certificate', 'showIssueCertificateModal');
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
                    <div class="filter-input-wrapper" style="flex: 1;">
                        <div class="filter-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="certificatesSearchInput" class="filter-input" placeholder="Search by title, student name, email, or certificate number..." oninput="searchCertificates(this.value)">
                        <button type="button" class="filter-clear-btn" id="certificatesSearchClear" style="display: none;" onclick="clearCertificatesSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Certificate Number</th>
                        <th>Issued Date</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${certificates.length === 0 ? `
                        <tr>
                            <td colspan="7" class="text-center">No certificates issued</td>
                        </tr>
                    ` : certificates.map(cert => `
                        <tr>
                            <td><strong>${cert.title}</strong></td>
                            <td>${cert.user?.name || 'N/A'}</td>
                            <td>${cert.user?.email || 'N/A'}</td>
                            <td><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">${cert.certificate_number}</code></td>
                            <td>${formatDateTime(cert.issued_date)}</td>
                            <td>${cert.description || 'N/A'}</td>
                            <td>
                                <span class="badge ${cert.status === 'active' ? 'badge-success' : (cert.status === 'expired' ? 'badge-danger' : 'badge-secondary')}">
                                    ${cert.status.charAt(0).toUpperCase() + cert.status.slice(1)}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Reports
async function loadReports() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const participationResult = await API.get('/loyalty/reports/participation');
    const distributionResult = await API.get('/loyalty/reports/points-distribution');
    const rewardsStatsResult = await API.get('/loyalty/reports/rewards-stats');
    
    if (participationResult.success && distributionResult.success && rewardsStatsResult.success) {
        displayReports({
            participation: participationResult.data.data,
            distribution: distributionResult.data.data,
            rewardsStats: rewardsStatsResult.data.data,
        });
    } else {
        showError(document.getElementById('adminLoyaltyContent'), 'Failed to load reports');
    }
}

function displayReports(data) {
    const container = document.getElementById('adminLoyaltyContent');
    const participation = data.participation || {};
    const rewardsStats = data.rewardsStats || {};
    
    // Update header
    updateLoyaltyHeader('Loyalty Program Reports', 'View statistics and analytics', '', '');
    document.getElementById('loyaltyHeaderBtn').style.display = 'none';
    
    container.innerHTML = `
        <div class="reports-grid">
            <div class="report-card">
                <h3>Participation Overview</h3>
                <div class="report-stats">
                    <div class="stat-item">
                        <strong>Total Students:</strong> ${participation.total_users || 0}
                    </div>
                    <div class="stat-item">
                        <strong>Active Participants:</strong> ${participation.active_users || 0}
                    </div>
                    <div class="stat-item">
                        <strong>Total Points Awarded:</strong> ${participation.total_points_awarded || 0}
                    </div>
                </div>
            </div>
            
            <div class="report-card">
                <h3>Rewards Statistics</h3>
                <div class="report-stats">
                    <div class="stat-item">
                        <strong>Total Rewards:</strong> ${rewardsStats.total_rewards || 0}
                    </div>
                    <div class="stat-item">
                        <strong>Active Rewards:</strong> ${rewardsStats.active_rewards || 0}
                    </div>
                    <div class="stat-item">
                        <strong>Total Redemptions:</strong> ${rewardsStats.total_redemptions || 0}
                    </div>
                    <div class="stat-item">
                        <strong>Pending Approvals:</strong> ${rewardsStats.pending_redemptions || 0}
                    </div>
                </div>
            </div>
        </div>
        
        ${participation.top_earners && participation.top_earners.length > 0 ? `
        <div class="report-section">
            <h3>Top Point Earners</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Points</th>
                    </tr>
                </thead>
                <tbody>
                    ${participation.top_earners.map((user, index) => `
                        <tr>
                            <td>#${index + 1}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><strong>${user.total_points || 0}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ` : ''}
        
        ${rewardsStats.popular_rewards && rewardsStats.popular_rewards.length > 0 ? `
        <div class="report-section">
            <h3>Most Popular Rewards</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reward Name</th>
                        <th>Redemptions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rewardsStats.popular_rewards.map(reward => `
                        <tr>
                            <td>${reward.name}</td>
                            <td>${reward.redemption_count}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ` : ''}
    `;
}

// Helper functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
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

function showLoading(element) {
    if (element) {
        element.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #a31f37;"></i><p style="margin-top: 20px;">Loading...</p></div>';
    }
}

function showError(element, message) {
    if (element) {
        element.innerHTML = `<div class="error-message" style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px; margin: 20px 0;"><p><strong>Error:</strong> ${message}</p></div>`;
    }
}

// Action functions
window.showCreateRuleModal = function() {
    console.log('showCreateRuleModal called');
    currentEditingRuleId = null;
    
    // Setup form handler if not already done
    setupRuleFormHandler();
    
    const modal = document.getElementById('ruleModal');
    const title = document.getElementById('ruleModalTitle');
    const form = document.getElementById('ruleForm');
    const actionType = document.getElementById('ruleActionType');
    const isActive = document.getElementById('ruleIsActive');
    
    if (!modal) {
        console.error('Rule modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    if (title) title.textContent = 'Create Point Rule';
    if (form) form.reset();
    if (isActive) isActive.checked = true;
    if (actionType) {
        actionType.disabled = false;
        actionType.value = '';
    }
    
    modal.style.display = 'block';
    modal.classList.add('show');
    console.log('Modal should be visible now');
};

window.editRule = async function(id) {
    console.log('editRule called with id:', id);
    currentEditingRuleId = id;
    
    // Setup form handler if not already done
    setupRuleFormHandler();
    
    const modal = document.getElementById('ruleModal');
    const title = document.getElementById('ruleModalTitle');
    
    if (!modal) {
        console.error('Rule modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    if (title) title.textContent = 'Edit Point Rule';
    
    // Show loading state
    modal.style.display = 'block';
    modal.classList.add('show');
    
    try {
        // Load rule data
        const result = await API.get('/loyalty/rules');
        
        if (result.success) {
            const rules = result.data.data || [];
            const rule = rules.find(r => r.id == id);
            
            if (rule) {
                const actionType = document.getElementById('ruleActionType');
                const name = document.getElementById('ruleName');
                const description = document.getElementById('ruleDescription');
                const points = document.getElementById('rulePoints');
                const isActive = document.getElementById('ruleIsActive');
                
                if (actionType) {
                    actionType.value = rule.action_type;
                    actionType.disabled = true;
                }
                if (name) name.value = rule.name;
                if (description) description.value = rule.description || '';
                if (points) points.value = rule.points;
                if (isActive) isActive.checked = rule.is_active;
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast('Rule not found', 'error');
                } else {
                    alert('Rule not found');
                }
                closeRuleModal();
            }
        } else {
            if (typeof showToast !== 'undefined') {
                showToast('Failed to load rule data: ' + (result.error || 'Unknown error'), 'error');
            } else {
                alert('Failed to load rule data: ' + (result.error || 'Unknown error'));
            }
            closeRuleModal();
        }
    } catch (error) {
        console.error('Error loading rule:', error);
        if (typeof showToast !== 'undefined') {
            showToast('An error occurred while loading the rule: ' + error.message, 'error');
        } else {
            alert('An error occurred while loading the rule: ' + error.message);
        }
        closeRuleModal();
    }
};

window.closeRuleModal = function() {
    const modal = document.getElementById('ruleModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    const form = document.getElementById('ruleForm');
    if (form) {
        form.reset();
        // Reset submit button state - important to restore button functionality
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Rule';
        }
    }
    const actionTypeInput = document.getElementById('ruleActionType');
    if (actionTypeInput) {
        actionTypeInput.disabled = false;
    }
    currentEditingRuleId = null;
};

// Custom confirmation dialog
let pendingDeleteId = null;

function showDeleteConfirmModal(message, onConfirm) {
    const modal = document.getElementById('deleteConfirmModal');
    const messageEl = document.getElementById('deleteConfirmMessage');
    
    if (!modal || !messageEl) {
        console.error('Delete confirm modal elements not found');
        if (confirm(message)) {
            onConfirm();
        }
        return;
    }
    
    // Store the callback globally so onclick can access it
    window._deleteConfirmCallback = onConfirm;
    
    messageEl.textContent = message;
    modal.style.display = 'block';
    modal.classList.add('show');
}

window.handleConfirmDelete = function() {
    console.log('handleConfirmDelete called');
    if (window._deleteConfirmCallback) {
        const callback = window._deleteConfirmCallback;
        window._deleteConfirmCallback = null;
        closeDeleteConfirmModal();
        // Call callback after a small delay to ensure modal is closed
        setTimeout(() => {
            callback();
        }, 100);
    } else {
        console.error('Delete confirm callback not found');
    }
}

function closeDeleteConfirmModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    // Don't reset pendingDeleteId here - it will be reset after deletion completes
    window._deleteConfirmCallback = null;
}

window.deleteRule = function(id) {
    console.log('deleteRule called with id:', id);
    pendingDeleteId = id;
    
    const deleteCallback = function() {
        console.log('Delete callback executed, pendingDeleteId:', pendingDeleteId);
        const idToDelete = pendingDeleteId; // Save the ID before it might be cleared
        if (idToDelete) {
            API.delete(`/loyalty/rules/${idToDelete}`).then(result => {
                console.log('Delete API result:', result);
                pendingDeleteId = null; // Clear after successful deletion
                if (result.success) {
                    loadRulesManagement();
                    if (typeof showToast !== 'undefined') {
                        showToast('Rule deleted successfully', 'success');
                    } else {
                        alert('Rule deleted successfully');
                    }
                } else {
                    if (typeof showToast !== 'undefined') {
                        showToast(result.error || 'Failed to delete rule', 'error');
                    } else {
                        alert(result.error || 'Failed to delete rule');
                    }
                }
            }).catch(error => {
                console.error('Error deleting rule:', error);
                pendingDeleteId = null; // Clear on error too
                if (typeof showToast !== 'undefined') {
                    showToast('An error occurred while deleting the rule', 'error');
                } else {
                    alert('An error occurred while deleting the rule');
                }
            });
        } else {
            console.error('pendingDeleteId is null');
        }
    };
    
    showDeleteConfirmModal('Are you sure you want to delete this rule? This action cannot be undone.', deleteCallback);
};

function setupRewardFormHandler() {
    if (rewardFormHandlerSetup) return;
    const rewardForm = document.getElementById('rewardForm');
    if (!rewardForm) {
        console.warn('Reward form not found, will retry when modal opens');
        return;
    }
    rewardFormHandlerSetup = true;
    rewardForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = {
            name: document.getElementById('rewardName').value.trim(),
            description: document.getElementById('rewardDescription').value.trim() || null,
            points_required: parseInt(document.getElementById('rewardPointsRequired').value),
            reward_type: document.getElementById('rewardType').value,
            image_url: rewardSelectedImageBase64 || null,
            stock_quantity: document.getElementById('rewardStockQuantity').value.trim() ? 
                parseInt(document.getElementById('rewardStockQuantity').value) : null,
            is_active: document.getElementById('rewardIsActive').checked,
        };
        if (!data.name || !data.reward_type || isNaN(data.points_required) || data.points_required < 1) {
            showToast('Please fill in all required fields correctly', 'warning');
            return;
        }
        const isEditing = !!currentEditingRewardId;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        try {
            let result;
            if (isEditing) {
                result = await API.put(`/loyalty/rewards/${currentEditingRewardId}`, data);
            } else {
                result = await API.post('/loyalty/rewards', data);
            }
            if (result.success) {
                closeRewardModal();
                loadRewardsManagement();
                showToast(isEditing ? 'Reward updated successfully!' : 'Reward created successfully!', 'success');
            } else {
                showToast(result.error || 'Failed to save reward', 'error');
            }
        } catch (error) {
            console.error('Error saving reward:', error);
            showToast('An error occurred while saving the reward: ' + error.message, 'error');
        } finally {
            resetRewardFormButtonState(submitBtn, originalText);
        }
    });
}

function resetRewardFormButtonState(button, originalText) {
    if (button) {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

// Reward image handling variables
let rewardSelectedImageBase64 = null;

// Handle reward image upload and convert to base64
window.handleRewardImageUpload = function(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    // Validate file type
    if (!file.type.match('image.*')) {
        if (typeof showToast === 'function') {
            showToast('Please select a valid image file', 'error');
        } else {
            alert('Please select a valid image file');
        }
        event.target.value = '';
        return;
    }

    // Validate file size (max 1MB to avoid database packet size issues)
    if (file.size > 1 * 1024 * 1024) {
        if (typeof showToast === 'function') {
            showToast('Image size must be less than 1MB. Please compress your image before uploading.', 'error');
        } else {
            alert('Image size must be less than 1MB. Please compress your image before uploading.');
        }
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const base64 = e.target.result;
        
        // Double check base64 size (should be less than ~1.5MB)
        if (base64.length > 1500000) {
            if (typeof showToast === 'function') {
                showToast('Image is too large. Please use a smaller image (max 1MB).', 'error');
            } else {
                alert('Image is too large. Please use a smaller image (max 1MB).');
            }
            event.target.value = '';
            document.getElementById('rewardImagePreview').style.display = 'none';
            return;
        }

        rewardSelectedImageBase64 = base64;
        // Show preview
        document.getElementById('rewardPreviewImg').src = rewardSelectedImageBase64;
        document.getElementById('rewardImagePreview').style.display = 'block';
    };
    reader.onerror = function() {
        if (typeof showToast === 'function') {
            showToast('Error reading image file', 'error');
        } else {
            alert('Error reading image file');
        }
    };
    reader.readAsDataURL(file);
}

// Remove selected reward image
window.removeRewardImage = function() {
    rewardSelectedImageBase64 = null;
    document.getElementById('rewardImage').value = '';
    document.getElementById('rewardImagePreview').style.display = 'none';
    document.getElementById('rewardPreviewImg').src = '';
}

window.showCreateRewardModal = function() {
    console.log('showCreateRewardModal called');
    currentEditingRewardId = null;
    
    // Setup form handler if not already done
    setupRewardFormHandler();
    
    const modal = document.getElementById('rewardModal');
    const title = document.getElementById('rewardModalTitle');
    const form = document.getElementById('rewardForm');
    const isActive = document.getElementById('rewardIsActive');
    
    if (!modal) {
        console.error('Reward modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    if (title) title.textContent = 'Create Reward';
    if (form) form.reset();
    if (isActive) isActive.checked = true;
    
    modal.style.display = 'block';
    modal.classList.add('show');
    console.log('Reward modal should be visible now');
};

window.editReward = async function(id) {
    console.log('editReward called with id:', id);
    currentEditingRewardId = id;
    
    // Setup form handler if not already done
    setupRewardFormHandler();
    
    const modal = document.getElementById('rewardModal');
    const title = document.getElementById('rewardModalTitle');
    
    if (!modal) {
        console.error('Reward modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    if (title) title.textContent = 'Edit Reward';
    
    // Show loading state
    modal.style.display = 'block';
    modal.classList.add('show');
    
    try {
        // Load reward data
        const result = await API.get('/loyalty/rewards/all');
        
        if (result.success && result.data && result.data.data) {
            const reward = result.data.data.find(r => r.id === id);
            if (!reward) {
                showToast('Reward not found', 'error');
                closeRewardModal();
                return;
            }
            
            // Populate form
            document.getElementById('rewardName').value = reward.name || '';
            document.getElementById('rewardDescription').value = reward.description || '';
            document.getElementById('rewardPointsRequired').value = reward.points_required || '';
            document.getElementById('rewardType').value = reward.reward_type || '';
            
            // Handle image - if it's a base64 string, show preview; if it's a URL, we can't preview it
            if (reward.image_url) {
                if (reward.image_url.startsWith('data:image/')) {
                    // It's a base64 image
                    rewardSelectedImageBase64 = reward.image_url;
                    document.getElementById('rewardPreviewImg').src = reward.image_url;
                    document.getElementById('rewardImagePreview').style.display = 'block';
                } else {
                    // It's a URL, clear the image input
                    rewardSelectedImageBase64 = null;
                    document.getElementById('rewardImagePreview').style.display = 'none';
                }
            } else {
                rewardSelectedImageBase64 = null;
                document.getElementById('rewardImagePreview').style.display = 'none';
            }
            document.getElementById('rewardImage').value = '';
            
            document.getElementById('rewardStockQuantity').value = reward.stock_quantity !== null ? reward.stock_quantity : '';
            document.getElementById('rewardIsActive').checked = reward.is_active !== false;
        } else {
            showToast('Failed to load reward data', 'error');
            closeRewardModal();
        }
    } catch (error) {
        console.error('Error loading reward:', error);
        showToast('An error occurred while loading the reward', 'error');
        closeRewardModal();
    }
};

window.closeRewardModal = function() {
    const modal = document.getElementById('rewardModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    const form = document.getElementById('rewardForm');
    if (form) {
        form.reset();
    }
    // Reset image selection
    rewardSelectedImageBase64 = null;
    const imageInput = document.getElementById('rewardImage');
    if (imageInput) {
        imageInput.value = '';
    }
    const imagePreview = document.getElementById('rewardImagePreview');
    if (imagePreview) {
        imagePreview.style.display = 'none';
    }
    const previewImg = document.getElementById('rewardPreviewImg');
    if (previewImg) {
        previewImg.src = '';
    }
    currentEditingRewardId = null;
};

window.deleteReward = function(id) {
    console.log('deleteReward called with id:', id);
    pendingDeleteId = id;
    
    const deleteCallback = function() {
        console.log('Delete reward callback executed, pendingDeleteId:', pendingDeleteId);
        const idToDelete = pendingDeleteId; // Save the ID before it might be cleared
        if (idToDelete) {
            API.delete(`/loyalty/rewards/${idToDelete}`).then(result => {
                console.log('Delete reward API result:', result);
                pendingDeleteId = null; // Clear after successful deletion
                if (result.success) {
                    loadRewardsManagement();
                    if (typeof showToast !== 'undefined') {
                        showToast('Reward deleted successfully', 'success');
                    } else {
                        alert('Reward deleted successfully');
                    }
                } else {
                    if (typeof showToast !== 'undefined') {
                        showToast(result.error || 'Failed to delete reward', 'error');
                    } else {
                        alert(result.error || 'Failed to delete reward');
                    }
                }
            }).catch(error => {
                console.error('Error deleting reward:', error);
                pendingDeleteId = null; // Clear on error too
                if (typeof showToast !== 'undefined') {
                    showToast('An error occurred while deleting the reward', 'error');
                } else {
                    alert('An error occurred while deleting the reward');
                }
            });
        } else {
            console.error('pendingDeleteId is null');
        }
    };
    
    showDeleteConfirmModal('Are you sure you want to delete this reward? This action cannot be undone.', deleteCallback);
};

window.approveRedemption = async function(id) {
    if (!confirm('Approve this redemption?')) return;
    
    const result = await API.put(`/loyalty/redemptions/${id}/approve`, {});
    
    if (result.success) {
        loadRedemptionsManagement();
        alert('Redemption approved successfully');
    } else {
        alert(result.error || 'Failed to approve redemption');
    }
};

window.rejectRedemption = async function(id) {
    if (!confirm('Reject this redemption? Points will be refunded.')) return;
    
    const result = await API.put(`/loyalty/redemptions/${id}/reject`, {});
    
    if (result.success) {
        loadRedemptionsManagement();
        alert('Redemption rejected and points refunded');
    } else {
        alert(result.error || 'Failed to reject redemption');
    }
};

window.viewUserPoints = async function(userId) {
    const modal = document.getElementById('viewUserPointsModal');
    const content = document.getElementById('viewUserPointsContent');
    
    if (!modal || !content) {
        console.error('View user points modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    // Show modal with loading state
    modal.style.display = 'block';
    modal.classList.add('show');
    
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #a31f37;"></i>
            <p style="margin-top: 20px;">Loading user points...</p>
        </div>
    `;
    
    try {
        const result = await API.get(`/loyalty/points/user/${userId}`);
        
        console.log('User points API response:', result);
        
        if (result.success && result.data) {
            // Handle both response structures: { data: {...} } or direct {...}
            const data = result.data.data || result.data;
            console.log('Parsed data:', data);
            
            const user = data.user || {};
            const totalPoints = data.total_points || 0;
            const history = data.points_history?.data || data.points_history || [];
            
            content.innerHTML = `
                <div style="margin-bottom: 30px;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #333;">${user.name || 'N/A'}</h3>
                        <p style="margin: 5px 0; color: #666;"><strong>Email:</strong> ${user.email || 'N/A'}</p>
                        <p style="margin: 5px 0; color: #666;"><strong>Role:</strong> ${user.role || 'N/A'}</p>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #e0e0e0;">
                            <p style="margin: 0; font-size: 1.5rem; color: #a31f37;">
                                <strong>Total Points: ${totalPoints}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 15px; color: #333;">Points History</h3>
                    ${history.length === 0 ? '<p style="text-align: center; padding: 20px; color: #666;">No points history found</p>' : ''}
                    ${history.length > 0 ? `
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="data-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action Type</th>
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
                                            <td>${formatDateTime(item.created_at)}</td>
                                            <td>${formattedAction}</td>
                                            <td class="${item.points > 0 ? 'text-success' : 'text-danger'}" style="font-weight: bold;">
                                                ${item.points > 0 ? '+' : ''}${item.points}
                                            </td>
                                            <td>${formattedDescription}</td>
                                        </tr>
                                    `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : ''}
                </div>
                
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeViewUserPointsModal()">Close</button>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="error-message" style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px; margin: 20px 0;">
                    <p><strong>Error:</strong> ${result.error || 'Failed to load user points details'}</p>
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeViewUserPointsModal()">Close</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading user points:', error);
        content.innerHTML = `
            <div class="error-message" style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px; margin: 20px 0;">
                <p><strong>Error:</strong> An error occurred while loading user points: ${error.message}</p>
            </div>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn-secondary" onclick="closeViewUserPointsModal()">Close</button>
            </div>
        `;
    }
};

window.closeViewUserPointsModal = function() {
    const modal = document.getElementById('viewUserPointsModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
};

// Award Points Modal Functions
let awardPointsFormHandlerSetup = false;

function setupAwardPointsFormHandler() {
    if (awardPointsFormHandlerSetup) return;
    
    const form = document.getElementById('awardPointsForm');
    if (!form) {
        console.warn('Award points form not found');
        return;
    }
    
    awardPointsFormHandlerSetup = true;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('awardPointsUserId').value;
        const points = parseInt(document.getElementById('awardPointsAmount').value);
        const actionType = document.getElementById('awardPointsActionType').value.trim();
        const description = document.getElementById('awardPointsDescription').value.trim() || null;
        
        if (!userId || isNaN(points) || points < 1 || !actionType) {
            if (typeof showToast !== 'undefined') {
                showToast('Please fill in all required fields correctly', 'warning');
            } else {
                alert('Please fill in all required fields correctly');
            }
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Awarding...';
        
        try {
            const result = await API.post('/loyalty/points/award', {
                user_id: userId,
                points: points,
                action_type: actionType,
                description: description
            });
            
            if (result.success) {
                closeAwardPointsModal();
                loadPointsManagement(); // Refresh the points tracking table
                if (typeof showToast !== 'undefined') {
                    showToast('Points awarded successfully!', 'success');
                } else {
                    alert('Points awarded successfully!');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Failed to award points', 'error');
                } else {
                    alert(result.error || 'Failed to award points');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error awarding points:', error);
            if (typeof showToast !== 'undefined') {
                showToast('An error occurred while awarding points: ' + error.message, 'error');
            } else {
                alert('An error occurred while awarding points: ' + error.message);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

async function loadUsersForAward() {
    const select = document.getElementById('awardPointsUserId');
    if (!select) return;
    
    select.innerHTML = '<option value="">Loading users...</option>';
    select.disabled = true;
    
    try {
        const result = await API.get('/users?role=student');
        
        // API.js returns {success: true, data: serverResponse}
        // Server returns {status: 'S', data: {data: [...], ...}}
        let users = null;
        
        if (result.success && result.data) {
            // Check if server response has status 'S' and paginated data
            if (result.data.status === 'S' && result.data.data) {
                // Handle paginated response: result.data.data.data
                if (result.data.data.data && Array.isArray(result.data.data.data)) {
                    users = result.data.data.data;
                } else if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                }
            } else if (result.data.data) {
                // Handle direct data response
                if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                } else if (Array.isArray(result.data)) {
                    users = result.data;
                }
            } else if (Array.isArray(result.data)) {
                users = result.data;
            }
        }
        
        if (users && users.length > 0) {
            select.innerHTML = '<option value="">Select a student...</option>';
            
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.name} (${user.email})`;
                select.appendChild(option);
            });
            
            select.disabled = false;
            return users; // Return users array for use in awardPointsToUser
        } else {
            select.innerHTML = '<option value="">No students found</option>';
            console.error('No users found in response:', result);
            return null;
        }
    } catch (error) {
        console.error('Error loading users:', error);
        select.innerHTML = '<option value="">Error loading users</option>';
        return null;
    }
}

window.showAwardPointsModal = async function() {
    // Setup form handler if not already done
    setupAwardPointsFormHandler();
    
    const modal = document.getElementById('awardPointsModal');
    const form = document.getElementById('awardPointsForm');
    
    if (!modal || !form) {
        console.error('Award points modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    // Reset form
    form.reset();
    const select = document.getElementById('awardPointsUserId');
    if (select) {
        select.innerHTML = '<option value="">Loading users...</option>';
        select.disabled = false; // Enable select when opening modal manually
    }
    
    // Load users
    await loadUsersForAward();
    
    // Show modal
    modal.style.display = 'block';
    modal.classList.add('show');
};

window.awardPointsToUser = async function(userId) {
    // Setup form handler if not already done
    setupAwardPointsFormHandler();
    
    const modal = document.getElementById('awardPointsModal');
    const form = document.getElementById('awardPointsForm');
    
    if (!modal || !form) {
        console.error('Award points modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    // Show modal first (with loading state)
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Reset form
    form.reset();
    
    // Load users and wait for completion
    const users = await loadUsersForAward();
    
    // Set the user ID after users are loaded
    const select = document.getElementById('awardPointsUserId');
    if (select && userId) {
        // Check if the user ID exists in the loaded users
        const userExists = users && users.some(user => user.id == userId);
        if (userExists) {
            select.value = userId;
            // Disable the select box since user is already selected
            select.disabled = true;
        } else {
            console.warn(`User ID ${userId} not found in loaded users`);
        }
    }
};

window.closeAwardPointsModal = function() {
    const modal = document.getElementById('awardPointsModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    const form = document.getElementById('awardPointsForm');
    if (form) {
        form.reset();
        // Reset submit button state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Award Points';
        }
        // Re-enable select box when closing modal
        const select = document.getElementById('awardPointsUserId');
        if (select) {
            select.disabled = false;
        }
    }
}

// Deduct Points Modal Functions
let deductPointsFormHandlerSetup = false;

function setupDeductPointsFormHandler() {
    if (deductPointsFormHandlerSetup) return;
    
    const form = document.getElementById('deductPointsForm');
    if (!form) {
        console.warn('Deduct points form not found');
        return;
    }
    
    deductPointsFormHandlerSetup = true;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('deductPointsUserId').value;
        const points = parseInt(document.getElementById('deductPointsAmount').value);
        const actionType = document.getElementById('deductPointsActionType').value.trim();
        const description = document.getElementById('deductPointsDescription').value.trim() || null;
        
        if (!userId || isNaN(points) || points < 1 || !actionType) {
            if (typeof showToast !== 'undefined') {
                showToast('Please fill in all required fields correctly', 'warning');
            } else {
                alert('Please fill in all required fields correctly');
            }
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deducting...';
        
        try {
            const data = {
                user_id: userId,
                points: points,
                action_type: actionType,
                description: description
            };
            
            const result = await API.post('/loyalty/points/deduct', data);
            
            if (result.success) {
                closeDeductPointsModal();
                loadPointsManagement(); // Refresh the points tracking table
                if (typeof showToast !== 'undefined') {
                    showToast('Points deducted successfully!', 'success');
                } else {
                    alert('Points deducted successfully!');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Failed to deduct points', 'error');
                } else {
                    alert(result.error || 'Failed to deduct points');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error deducting points:', error);
            if (typeof showToast !== 'undefined') {
                showToast('An error occurred while deducting points: ' + error.message, 'error');
            } else {
                alert('An error occurred while deducting points: ' + error.message);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

async function loadUsersForDeduct() {
    const select = document.getElementById('deductPointsUserId');
    if (!select) return;
    
    select.innerHTML = '<option value="">Loading users...</option>';
    select.disabled = true;
    
    try {
        const result = await API.get('/users?role=student');
        
        // API.js returns {success: true, data: serverResponse}
        // Server returns {status: 'S', data: {data: [...], ...}}
        let users = null;
        
        if (result.success && result.data) {
            // Check if server response has status 'S' and paginated data
            if (result.data.status === 'S' && result.data.data) {
                // Handle paginated response: result.data.data.data
                if (result.data.data.data && Array.isArray(result.data.data.data)) {
                    users = result.data.data.data;
                } else if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                }
            } else if (result.data.data) {
                // Handle direct data response
                if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                } else if (Array.isArray(result.data)) {
                    users = result.data;
                }
            } else if (Array.isArray(result.data)) {
                users = result.data;
            }
        }
        
        if (users && users.length > 0) {
            select.innerHTML = '<option value="">Select a student...</option>';
            
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.name} (${user.email})${user.personal_id ? ' - ' + user.personal_id : ''}`;
                select.appendChild(option);
            });
            
            select.disabled = false;
            return users; // Return users array for use in deductPointsFromUser
        } else {
            select.innerHTML = '<option value="">No students found</option>';
            console.error('No users found in response:', result);
            return null;
        }
    } catch (error) {
        console.error('Error loading users:', error);
        select.innerHTML = '<option value="">Error loading users</option>';
        return null;
    }
}

window.showDeductPointsModal = async function() {
    setupDeductPointsFormHandler();
    
    const modal = document.getElementById('deductPointsModal');
    const form = document.getElementById('deductPointsForm');
    
    if (!modal || !form) {
        console.error('Deduct points modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    // Reset form
    form.reset();
    const select = document.getElementById('deductPointsUserId');
    if (select) {
        select.innerHTML = '<option value="">Loading users...</option>';
        select.disabled = false;
    }
    
    // Load users
    await loadUsersForDeduct();
    
    // Show modal
    modal.style.display = 'block';
    modal.classList.add('show');
}

window.deductPointsFromUser = async function(userId) {
    setupDeductPointsFormHandler();
    
    const modal = document.getElementById('deductPointsModal');
    const form = document.getElementById('deductPointsForm');
    
    if (!modal || !form) {
        console.error('Deduct points modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    // Show modal first (with loading state)
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Reset form
    form.reset();
    
    // Load users and wait for completion
    const users = await loadUsersForDeduct();
    
    // Set the user ID if provided
    const select = document.getElementById('deductPointsUserId');
    if (select && userId) {
        // Check if the user ID exists in the loaded users
        const userExists = users && users.some(user => user.id == userId);
        if (userExists) {
            select.value = userId;
            // Disable the select box since user is already selected
            select.disabled = true;
        } else {
            console.warn(`User ID ${userId} not found in loaded users`);
        }
    }
}

window.closeDeductPointsModal = function() {
    const modal = document.getElementById('deductPointsModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    const form = document.getElementById('deductPointsForm');
    if (form) {
        form.reset();
        // Reset submit button state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Deduct Points';
        }
        // Re-enable select box when closing modal
        const select = document.getElementById('deductPointsUserId');
        if (select) {
            select.disabled = false;
        }
    }
};

// Issue Certificate Modal Functions
let issueCertificateFormHandlerSetup = false;

function setupIssueCertificateFormHandler() {
    if (issueCertificateFormHandlerSetup) return;
    
    const form = document.getElementById('issueCertificateForm');
    if (!form) {
        console.warn('Issue certificate form not found');
        return;
    }
    
    issueCertificateFormHandlerSetup = true;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('issueCertificateUserId').value;
        const title = document.getElementById('issueCertificateTitle').value.trim();
        const description = document.getElementById('issueCertificateDescription').value.trim() || null;
        const rewardId = document.getElementById('issueCertificateRewardId').value || null;
        const issuedDate = document.getElementById('issueCertificateIssuedDate').value || null;
        
        if (!userId || !title) {
            if (typeof showToast !== 'undefined') {
                showToast('Please fill in all required fields correctly', 'warning');
            } else {
                alert('Please fill in all required fields correctly');
            }
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Issuing...';
        
        try {
            const data = {
                user_id: userId,
                title: title,
                description: description,
                reward_id: rewardId,
                issued_date: issuedDate
            };
            
            const result = await API.post('/loyalty/certificates/issue', data);
            
            if (result.success) {
                closeIssueCertificateModal();
                loadCertificatesManagement(); // Refresh the certificates table
                if (typeof showToast !== 'undefined') {
                    showToast('Certificate issued successfully!', 'success');
                } else {
                    alert('Certificate issued successfully!');
                }
            } else {
                if (typeof showToast !== 'undefined') {
                    showToast(result.error || 'Failed to issue certificate', 'error');
                } else {
                    alert(result.error || 'Failed to issue certificate');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error issuing certificate:', error);
            if (typeof showToast !== 'undefined') {
                showToast('An error occurred while issuing certificate: ' + error.message, 'error');
            } else {
                alert('An error occurred while issuing certificate: ' + error.message);
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

async function loadUsersForCertificate() {
    const select = document.getElementById('issueCertificateUserId');
    if (!select) return;
    
    select.innerHTML = '<option value="">Loading users...</option>';
    select.disabled = true;
    
    try {
        const result = await API.get('/users?role=student');
        
        let users = null;
        
        if (result.success && result.data) {
            if (result.data.status === 'S' && result.data.data) {
                if (result.data.data.data && Array.isArray(result.data.data.data)) {
                    users = result.data.data.data;
                } else if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                }
            } else if (result.data.data) {
                if (Array.isArray(result.data.data)) {
                    users = result.data.data;
                } else if (Array.isArray(result.data)) {
                    users = result.data;
                }
            } else if (Array.isArray(result.data)) {
                users = result.data;
            }
        }
        
        if (users && users.length > 0) {
            select.innerHTML = '<option value="">Select a student...</option>';
            
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.name} (${user.email})`;
                select.appendChild(option);
            });
            
            select.disabled = false;
        } else {
            select.innerHTML = '<option value="">No students found</option>';
            console.error('No users found in response:', result);
        }
    } catch (error) {
        console.error('Error loading users:', error);
        select.innerHTML = '<option value="">Error loading users</option>';
    }
}

async function loadRewardsForCertificate() {
    const select = document.getElementById('issueCertificateRewardId');
    if (!select) return;
    
    try {
        const result = await API.get('/loyalty/rewards/all');
        
        let rewards = null;
        
        if (result.success && result.data) {
            if (result.data.data && Array.isArray(result.data.data)) {
                rewards = result.data.data;
            } else if (Array.isArray(result.data)) {
                rewards = result.data;
            }
        }
        
        if (rewards && rewards.length > 0) {
            select.innerHTML = '<option value="">No reward</option>';
            
            rewards.forEach(reward => {
                const option = document.createElement('option');
                option.value = reward.id;
                option.textContent = `${reward.name} (${reward.points_required} points)`;
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">No rewards available</option>';
        }
    } catch (error) {
        console.error('Error loading rewards:', error);
        select.innerHTML = '<option value="">Error loading rewards</option>';
    }
}

window.showIssueCertificateModal = async function() {
    const modal = document.getElementById('issueCertificateModal');
    if (!modal) {
        console.error('Issue certificate modal not found!');
        return;
    }
    
    // Reset form
    const form = document.getElementById('issueCertificateForm');
    if (form) {
        form.reset();
    }
    
    // Show modal
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Setup form handler if not already done
    setupIssueCertificateFormHandler();
    
    // Load users and rewards
    await Promise.all([
        loadUsersForCertificate(),
        loadRewardsForCertificate()
    ]);
    
    // Set today's date as default for issued date
    const issuedDateInput = document.getElementById('issueCertificateIssuedDate');
    if (issuedDateInput) {
        const today = new Date().toISOString().split('T')[0];
        issuedDateInput.value = today;
    }
};

window.closeIssueCertificateModal = function() {
    const modal = document.getElementById('issueCertificateModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    
    // Reset form
    const form = document.getElementById('issueCertificateForm');
    if (form) {
        form.reset();
    }
};

window.filterRedemptions = function() {
    const status = document.getElementById('redemptionStatusFilter')?.value || '';
    const searchTerm = document.getElementById('redemptionsSearchInput')?.value?.trim().toLowerCase() || '';
    
    // Always start from original data stored in currentSearchData
    if (!currentSearchData.redemptions || currentSearchData.redemptions.length === 0) {
        // If no original data, reload from server
        loadRedemptionsManagement();
        return;
    }
    
    let filtered = [...(currentSearchData.redemptions || [])];
    
    // Apply status filter
    if (status) {
        filtered = filtered.filter(r => r.status === status);
    }
    
    // Apply search filter
    if (searchTerm) {
        filtered = filtered.filter(r => 
            (r.reward_name && r.reward_name.toLowerCase().includes(searchTerm)) ||
            (r.user_name && r.user_name.toLowerCase().includes(searchTerm)) ||
            (r.user_email && r.user_email.toLowerCase().includes(searchTerm))
        );
    }
    
    // Only update the table HTML, preserve filters
    const container = document.getElementById('adminLoyaltyContent');
    const tableContainer = container.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.innerHTML = generateRedemptionsTableHTML(filtered).trim();
    } else {
        // If table container doesn't exist, re-render everything
        displayRedemptionsManagement(filtered);
    }
};

// Search functions
function debounceSearch(func, wait) {
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(searchTimeout);
            func(...args);
        };
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(later, wait);
    };
}

window.searchRules = debounceSearch(function(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const searchClearBtn = document.getElementById('rulesSearchClear');
    
    if (searchClearBtn) {
        searchClearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
    
    const filtered = currentSearchData.rules.filter(rule =>
        (rule.name && rule.name.toLowerCase().includes(searchLower)) ||
        (rule.action_type && rule.action_type.toLowerCase().includes(searchLower)) ||
        (rule.description && rule.description.toLowerCase().includes(searchLower))
    );
    
    // Re-render the table with filtered data
    const container = document.getElementById('adminLoyaltyContent');
    const searchSection = container.querySelector('.filters-section');
    const tableHTML = generateRulesTableHTML(filtered);
    const tableContainer = container.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.outerHTML = tableHTML;
    }
}, 300);

window.clearRulesSearch = function() {
    const searchInput = document.getElementById('rulesSearchInput');
    const searchClearBtn = document.getElementById('rulesSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        searchRules('');
    }
};

function generateRulesTableHTML(rules) {
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action Type</th>
                        <th>Points</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="width: 100px; min-width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rules.length === 0 ? `
                        <tr>
                            <td colspan="6" class="text-center">No rules found. Create your first rule!</td>
                        </tr>
                    ` : rules.map(rule => `
                        <tr>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><strong>${rule.name}</strong></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">${rule.action_type}</code></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px;"><strong>${rule.points}</strong></td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; max-width: 300px; word-wrap: break-word; line-height: 1.4;">${rule.description || 'N/A'}</td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap;">
                                <span class="badge ${rule.is_active ? 'badge-success' : 'badge-secondary'}" style="display: inline-block; vertical-align: bottom;">
                                    ${rule.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap; width: 100px; min-width: 100px; text-align: center;">
                                <button class="btn-sm btn-primary" onclick="editRule(${rule.id})" title="Edit" style="white-space: nowrap !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; margin: 0; vertical-align: bottom;">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

window.searchRewards = debounceSearch(function(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const searchClearBtn = document.getElementById('rewardsSearchClear');
    
    if (searchClearBtn) {
        searchClearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
    
    const filtered = currentSearchData.rewards.filter(reward =>
        (reward.name && reward.name.toLowerCase().includes(searchLower)) ||
        (reward.reward_type && reward.reward_type.toLowerCase().includes(searchLower)) ||
        (reward.description && reward.description.toLowerCase().includes(searchLower))
    );
    
    const container = document.getElementById('adminLoyaltyContent');
    const tableHTML = generateRewardsTableHTML(filtered);
    const tableContainer = container.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.outerHTML = tableHTML;
    }
}, 300);

window.clearRewardsSearch = function() {
    const searchInput = document.getElementById('rewardsSearchInput');
    const searchClearBtn = document.getElementById('rewardsSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        searchRewards('');
    }
};

function generateRewardsTableHTML(rewards) {
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Points Required</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rewards.length === 0 ? `
                        <tr>
                            <td colspan="7" class="text-center">No rewards found. Create your first reward!</td>
                        </tr>
                    ` : rewards.map(reward => `
                        <tr>
                            <td><strong>${reward.name}</strong></td>
                            <td><span class="badge badge-info">${reward.reward_type}</span></td>
                            <td><strong>${reward.points_required}</strong></td>
                            <td>${reward.stock_quantity !== null ? reward.stock_quantity : '<span style="color: #10b981;">Unlimited</span>'}</td>
                            <td>${reward.description || 'N/A'}</td>
                            <td>
                                <span class="badge ${reward.is_active ? 'badge-success' : 'badge-secondary'}">
                                    ${reward.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap; text-align: center;">
                                <button class="btn-sm btn-primary btn-square" onclick="editReward(${reward.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-sm btn-danger btn-square" onclick="deleteReward(${reward.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

window.searchRedemptions = debounceSearch(function(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const searchClearBtn = document.getElementById('redemptionsSearchClear');
    
    if (searchClearBtn) {
        searchClearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
    
    // Use the same filtering logic as filterRedemptions
    window.filterRedemptions();
}, 300);

window.clearRedemptionsSearch = function() {
    const searchInput = document.getElementById('redemptionsSearchInput');
    const searchClearBtn = document.getElementById('redemptionsSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        searchRedemptions('');
    }
};

function generateRedemptionsTableHTML(redemptions) {
    return `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reward</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Points Used</th>
                    <th>Redeemed At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${redemptions.length === 0 ? `
                    <tr>
                        <td colspan="7" class="text-center">No redemptions found</td>
                    </tr>
                ` : redemptions.map(redemption => `
                    <tr>
                        <td>
                            <strong>${redemption.reward_name}</strong>
                            ${redemption.reward_description ? `<br><small style="color: #6b7280;">${redemption.reward_description}</small>` : ''}
                        </td>
                        <td>${redemption.user_name}</td>
                        <td>${redemption.user_email}</td>
                        <td><strong>${redemption.points_used}</strong></td>
                        <td>${formatDateTime(redemption.redeemed_at || redemption.created_at)}</td>
                        <td>
                            <span class="badge ${redemption.status === 'approved' ? 'badge-success' : (redemption.status === 'pending' ? 'badge-warning' : 'badge-danger')}">
                                ${redemption.status.charAt(0).toUpperCase() + redemption.status.slice(1)}
                            </span>
                        </td>
                        <td style="vertical-align: bottom; padding-bottom: 15px; white-space: nowrap; width: 100px; min-width: 100px; text-align: center;">
                            ${redemption.status === 'pending' ? `
                                <button class="btn-sm btn-success btn-square" onclick="approveRedemption(${redemption.id})" title="Approve" style="margin-right: 5px;">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-sm btn-danger btn-square" onclick="rejectRedemption(${redemption.id})" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : '-'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

window.searchPoints = debounceSearch(function(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const searchClearBtn = document.getElementById('pointsSearchClear');
    
    if (searchClearBtn) {
        searchClearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
    
    const filtered = currentSearchData.points.filter(user =>
        (user.name && user.name.toLowerCase().includes(searchLower)) ||
        (user.email && user.email.toLowerCase().includes(searchLower))
    );
    
    const container = document.getElementById('adminLoyaltyContent');
    const tableHTML = generatePointsTableHTML(filtered);
    const tableContainer = container.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.outerHTML = tableHTML;
    }
}, 300);

window.clearPointsSearch = function() {
    const searchInput = document.getElementById('pointsSearchInput');
    const searchClearBtn = document.getElementById('pointsSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        searchPoints('');
    }
};

function generatePointsTableHTML(users) {
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Points</th>
                        <th>Transactions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.length === 0 ? `
                        <tr>
                            <td colspan="5" class="text-center">No users found</td>
                        </tr>
                    ` : users.map(user => `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><strong>${user.total_points || 0}</strong></td>
                            <td>${user.points_count || 0}</td>
                            <td class="actions">
                                <button class="btn-sm btn-info btn-square" onclick="viewUserPoints(${user.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-sm btn-primary btn-square" onclick="awardPointsToUser(${user.id})" title="Award Points">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn-sm btn-danger btn-square" onclick="deductPointsFromUser(${user.id})" title="Deduct Points">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

window.searchCertificates = debounceSearch(function(searchTerm) {
    const searchLower = searchTerm.toLowerCase();
    const searchClearBtn = document.getElementById('certificatesSearchClear');
    
    if (searchClearBtn) {
        searchClearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
    
    const filtered = currentSearchData.certificates.filter(cert =>
        (cert.title && cert.title.toLowerCase().includes(searchLower)) ||
        (cert.user && cert.user.name && cert.user.name.toLowerCase().includes(searchLower)) ||
        (cert.user && cert.user.email && cert.user.email.toLowerCase().includes(searchLower)) ||
        (cert.certificate_number && cert.certificate_number.toLowerCase().includes(searchLower))
    );
    
    const container = document.getElementById('adminLoyaltyContent');
    const tableHTML = generateCertificatesTableHTML(filtered);
    const tableContainer = container.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.outerHTML = tableHTML;
    }
}, 300);

window.clearCertificatesSearch = function() {
    const searchInput = document.getElementById('certificatesSearchInput');
    const searchClearBtn = document.getElementById('certificatesSearchClear');
    if (searchInput) {
        searchInput.value = '';
        if (searchClearBtn) {
            searchClearBtn.style.display = 'none';
        }
        searchCertificates('');
    }
};

function generateCertificatesTableHTML(certificates) {
    return `
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Certificate Number</th>
                        <th>Issued Date</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${certificates.length === 0 ? `
                        <tr>
                            <td colspan="7" class="text-center">No certificates issued</td>
                        </tr>
                    ` : certificates.map(cert => `
                        <tr>
                            <td><strong>${cert.title}</strong></td>
                            <td>${cert.user?.name || 'N/A'}</td>
                            <td>${cert.user?.email || 'N/A'}</td>
                            <td><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">${cert.certificate_number}</code></td>
                            <td>${formatDateTime(cert.issued_date)}</td>
                            <td>${cert.description || 'N/A'}</td>
                            <td>
                                <span class="badge ${cert.status === 'active' ? 'badge-success' : (cert.status === 'expired' ? 'badge-danger' : 'badge-secondary')}">
                                    ${cert.status.charAt(0).toUpperCase() + cert.status.slice(1)}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

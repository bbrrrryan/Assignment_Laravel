@extends('layouts.app')

@section('title', 'Loyalty Management - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Loyalty Management</h1>
    </div>

    <div class="loyalty-admin-tabs">
        <button class="tab-btn active" onclick="showAdminTab('rules', this)">Point Rules</button>
        <button class="tab-btn" onclick="showAdminTab('rewards', this)">Rewards</button>
        <button class="tab-btn" onclick="showAdminTab('redemptions', this)">Redemptions</button>
        <button class="tab-btn" onclick="showAdminTab('points', this)">Points Tracking</button>
        <button class="tab-btn" onclick="showAdminTab('certificates', this)">Certificates</button>
        <button class="tab-btn" onclick="showAdminTab('reports', this)">Reports</button>
    </div>

    <div id="adminLoyaltyContent">
        <p>Loading...</p>
    </div>
</div>

<!-- Create/Edit Rule Modal -->
<div id="ruleModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeRuleModal()">&times;</span>
        <h2 id="ruleModalTitle">Create Point Rule</h2>
        <form id="ruleForm">
            <div class="form-group">
                <label>Action Type *</label>
                <input type="text" id="ruleActionType" required 
                       placeholder="e.g., facility_booking, feedback_submission, event_attendance">
                <small>Unique identifier for this action (lowercase, use underscores)</small>
            </div>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" id="ruleName" required 
                       placeholder="e.g., Facility Booking, Feedback Submission">
                <small>Display name for this rule</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="ruleDescription" rows="3" 
                          placeholder="Optional description of this rule"></textarea>
            </div>
            <div class="form-group">
                <label>Points Awarded *</label>
                <input type="number" id="rulePoints" required min="0" 
                       placeholder="e.g., 10">
                <small>Number of points to award for this action</small>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="ruleIsActive" checked> Active
                </label>
                <small>Only active rules will award points</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeRuleModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Rule</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close" onclick="closeDeleteConfirmModal()">&times;</span>
        <h2>Confirm Delete</h2>
        <div style="padding: 20px 0;">
            <p id="deleteConfirmMessage">Are you sure you want to delete this rule? This action cannot be undone.</p>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="closeDeleteConfirmModal()">Cancel</button>
            <button type="button" class="btn-danger" id="confirmDeleteBtn" onclick="handleConfirmDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Modals will be added here -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth() || !API.isAdmin()) {
        window.location.href = '/';
        return;
    }

    initAdminLoyalty();
});

let currentAdminTab = 'rules';
let currentEditingRuleId = null;

function initAdminLoyalty() {
    loadAdminTab('rules');
    // Don't setup form handler here - it will be set up when modal is first opened
}

let ruleFormHandlerSetup = false;

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
    
    // Close modal when clicking outside
    const ruleModal = document.getElementById('ruleModal');
    if (ruleModal) {
        ruleModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRuleModal();
            }
        });
    }
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
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Point Earning Rules</h2>
            <button class="btn-primary" onclick="showCreateRuleModal()">
                <i class="fas fa-plus"></i> Create Rule
            </button>
        </div>
        <div class="rules-list">
            ${rules.length === 0 ? '<p>No rules found. Create your first rule!</p>' : ''}
            ${rules.map(rule => `
                <div class="rule-card">
                    <div class="rule-header">
                        <h3>${rule.name}</h3>
                        <span class="status-badge ${rule.is_active ? 'status-active' : 'status-inactive'}">
                            ${rule.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                    <div class="rule-details">
                        <p><strong>Action Type:</strong> ${rule.action_type}</p>
                        <p><strong>Points:</strong> ${rule.points}</p>
                        ${rule.description ? `<p><strong>Description:</strong> ${rule.description}</p>` : ''}
                    </div>
                    <div class="rule-actions">
                        <button class="btn-sm btn-primary" onclick="editRule(${rule.id})">Edit</button>
                        <button class="btn-sm btn-danger" onclick="deleteRule(${rule.id})">Delete</button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Rewards Management
async function loadRewardsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/rewards/all');
    
    if (result.success) {
        const rewards = result.data.data || [];
        displayRewardsManagement(rewards);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load rewards');
    }
}

function displayRewardsManagement(rewards) {
    const container = document.getElementById('adminLoyaltyContent');
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Rewards Management</h2>
            <button class="btn-primary" onclick="showCreateRewardModal()">
                <i class="fas fa-plus"></i> Create Reward
            </button>
        </div>
        <div class="rewards-admin-list">
            ${rewards.length === 0 ? '<p>No rewards found. Create your first reward!</p>' : ''}
            ${rewards.map(reward => `
                <div class="reward-admin-card">
                    <div class="reward-admin-header">
                        <h3>${reward.name}</h3>
                        <span class="status-badge ${reward.is_active ? 'status-active' : 'status-inactive'}">
                            ${reward.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                    <div class="reward-admin-details">
                        <p><strong>Type:</strong> ${reward.reward_type}</p>
                        <p><strong>Points Required:</strong> ${reward.points_required}</p>
                        ${reward.stock_quantity !== null ? `<p><strong>Stock:</strong> ${reward.stock_quantity}</p>` : '<p><strong>Stock:</strong> Unlimited</p>'}
                        ${reward.description ? `<p><strong>Description:</strong> ${reward.description}</p>` : ''}
                    </div>
                    <div class="reward-admin-actions">
                        <button class="btn-sm btn-primary" onclick="editReward(${reward.id})">Edit</button>
                        <button class="btn-sm btn-danger" onclick="deleteReward(${reward.id})">Delete</button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

// Redemptions Management
async function loadRedemptionsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/redemptions');
    
    if (result.success) {
        const redemptions = result.data.data?.data || result.data.data || [];
        displayRedemptionsManagement(redemptions);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load redemptions');
    }
}

function displayRedemptionsManagement(redemptions) {
    const container = document.getElementById('adminLoyaltyContent');
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Redemption Approvals</h2>
            <div class="filters">
                <select id="redemptionStatusFilter" onchange="filterRedemptions()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <div class="redemptions-list">
            ${redemptions.length === 0 ? '<p>No redemptions found</p>' : ''}
            ${redemptions.map(redemption => `
                <div class="redemption-card">
                    <div class="redemption-header">
                        <div>
                            <h3>${redemption.reward_name}</h3>
                            <p><strong>User:</strong> ${redemption.user_name} (${redemption.user_email})</p>
                        </div>
                        <span class="status-badge status-${redemption.status}">${redemption.status}</span>
                    </div>
                    <div class="redemption-details">
                        <p><strong>Points Used:</strong> ${redemption.points_used}</p>
                        <p><strong>Redeemed At:</strong> ${formatDateTime(redemption.redeemed_at || redemption.created_at)}</p>
                        ${redemption.reward_description ? `<p><strong>Description:</strong> ${redemption.reward_description}</p>` : ''}
                    </div>
                    ${redemption.status === 'pending' ? `
                    <div class="redemption-actions">
                        <button class="btn-sm btn-success" onclick="approveRedemption(${redemption.id})">Approve</button>
                        <button class="btn-sm btn-danger" onclick="rejectRedemption(${redemption.id})">Reject</button>
                    </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    `;
}

// Points Management
async function loadPointsManagement() {
    showLoading(document.getElementById('adminLoyaltyContent'));
    
    const result = await API.get('/loyalty/points/all');
    
    if (result.success) {
        const users = result.data.data || [];
        displayPointsManagement(users);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load points data');
    }
}

function displayPointsManagement(users) {
    const container = document.getElementById('adminLoyaltyContent');
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Student Points Tracking</h2>
            <button class="btn-primary" onclick="showAwardPointsModal()">
                <i class="fas fa-plus"></i> Award Points
            </button>
        </div>
        <div class="points-tracking-table">
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
                    ${users.map(user => `
                        <tr>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td><strong>${user.total_points || 0}</strong></td>
                            <td>${user.points_count || 0}</td>
                            <td>
                                <button class="btn-sm" onclick="viewUserPoints(${user.id})">View Details</button>
                                <button class="btn-sm btn-primary" onclick="awardPointsToUser(${user.id})">Award Points</button>
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
        displayCertificatesManagement(certificates);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load certificates');
    }
}

function displayCertificatesManagement(certificates) {
    const container = document.getElementById('adminLoyaltyContent');
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Certificates Management</h2>
            <button class="btn-primary" onclick="showIssueCertificateModal()">
                <i class="fas fa-plus"></i> Issue Certificate
            </button>
        </div>
        <div class="certificates-admin-list">
            ${certificates.length === 0 ? '<p>No certificates issued</p>' : ''}
            ${certificates.map(cert => `
                <div class="certificate-admin-card">
                    <div class="certificate-admin-header">
                        <h3>${cert.title}</h3>
                        <span class="status-badge status-${cert.status}">${cert.status}</span>
                    </div>
                    <div class="certificate-admin-details">
                        <p><strong>Student:</strong> ${cert.user?.name || 'N/A'} (${cert.user?.email || 'N/A'})</p>
                        <p><strong>Certificate Number:</strong> ${cert.certificate_number}</p>
                        <p><strong>Issued Date:</strong> ${formatDateTime(cert.issued_date)}</p>
                        ${cert.description ? `<p><strong>Description:</strong> ${cert.description}</p>` : ''}
                    </div>
                </div>
            `).join('')}
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
    
    container.innerHTML = `
        <div class="admin-section-header">
            <h2>Loyalty Program Reports</h2>
        </div>
        
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

function handleConfirmDelete() {
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

window.editReward = function(id) {
    alert('Edit reward functionality - to be implemented');
};

window.deleteReward = function(id) {
    if (confirm('Are you sure you want to delete this reward?')) {
        API.delete(`/loyalty/rewards/${id}`).then(result => {
            if (result.success) {
                loadRewardsManagement();
                alert('Reward deleted successfully');
            } else {
                alert(result.error || 'Failed to delete reward');
            }
        });
    }
};

window.showCreateRewardModal = function() {
    alert('Create reward modal - to be implemented');
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

window.viewUserPoints = function(userId) {
    alert('View user points details - to be implemented');
};

window.awardPointsToUser = function(userId) {
    alert('Award points modal - to be implemented');
};

window.showAwardPointsModal = function() {
    alert('Award points modal - to be implemented');
};

window.showIssueCertificateModal = function() {
    alert('Issue certificate modal - to be implemented');
};

window.filterRedemptions = function() {
    const status = document.getElementById('redemptionStatusFilter').value;
    // Reload with filter
    loadRedemptionsManagement();
};
</script>

<style>
.loyalty-admin-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-size: 1rem;
    transition: all 0.3s;
}

.tab-btn:hover {
    background: #f5f5f5;
}

.tab-btn.active {
    border-bottom-color: #a31f37;
    color: #a31f37;
    font-weight: 600;
}

.admin-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-section-header h2 {
    margin: 0;
}

.rules-list, .rewards-admin-list, .redemptions-list, .certificates-admin-list {
    display: grid;
    gap: 20px;
}

.rule-card, .reward-admin-card, .redemption-card, .certificate-admin-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rule-header, .reward-admin-header, .redemption-header, .certificate-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.rule-details, .reward-admin-details, .redemption-details, .certificate-admin-details {
    margin-bottom: 15px;
}

.rule-actions, .reward-admin-actions, .redemption-actions {
    display: flex;
    gap: 10px;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.report-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-stats {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stat-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.report-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 20px;
}

/* Modal Styles - Override if needed */
#ruleModal.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    overflow: auto;
}

#ruleModal.modal.show {
    display: block;
}

#ruleModal .modal-content {
    background: #ffffff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #636e72;
    cursor: pointer;
}

.close:hover {
    color: #2d3436;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}

.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #a31f37;
    box-shadow: 0 0 0 3px rgba(163, 31, 55, 0.1);
}

.form-group input[type="checkbox"] {
    margin-right: 8px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.85rem;
}

.form-group input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}
</style>
@endsection


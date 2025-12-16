@extends('layouts.app')

@section('title', 'Loyalty Management - TARUMT FMS')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/loyalty/index.css') }}">
<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 id="loyaltyPageTitle">Loyalty Management</h1>
            <p id="loyaltyPageSubtitle">Manage loyalty program rules, rewards, and points</p>
        </div>
        <div>
            <button id="loyaltyHeaderBtn" class="btn-header-white" onclick="handleHeaderButtonClick()" style="display: none;">
                <i class="fas fa-plus"></i> <span id="loyaltyHeaderBtnText">Create</span>
            </button>
        </div>
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

<!-- Create/Edit Reward Modal -->
<div id="rewardModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeRewardModal()">&times;</span>
        <h2 id="rewardModalTitle">Create Reward</h2>
        <form id="rewardForm">
            <div class="form-group">
                <label>Reward Name *</label>
                <input type="text" id="rewardName" required 
                       placeholder="e.g., Gold Certificate, VIP Badge">
                <small>Display name for this reward</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="rewardDescription" rows="3" 
                          placeholder="Optional description of this reward"></textarea>
            </div>
            <div class="form-group">
                <label>Points Required *</label>
                <input type="number" id="rewardPointsRequired" required min="1" 
                       placeholder="e.g., 100">
                <small>Minimum points needed to redeem this reward</small>
            </div>
            <div class="form-group">
                <label>Reward Type *</label>
                <select id="rewardType" required>
                    <option value="">Select a type</option>
                    <option value="certificate">Certificate</option>
                    <option value="badge">Badge</option>
                    <option value="privilege">Privilege</option>
                    <option value="physical">Physical Item</option>
                </select>
                <small>Type of reward being offered</small>
            </div>
            <div class="form-group">
                <label>Image (Optional)</label>
                <input type="file" id="rewardImage" accept="image/*" onchange="handleRewardImageUpload(event)">
                <small>Upload an image for this reward (JPG, PNG, GIF). Maximum size: 1MB</small>
                <div id="rewardImagePreview" style="margin-top: 10px; display: none;">
                    <img id="rewardPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
                    <button type="button" onclick="removeRewardImage()" style="margin-left: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" id="rewardStockQuantity" min="0" 
                       placeholder="Leave empty for unlimited">
                <small>Leave empty for unlimited stock, or enter a number for limited quantity</small>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="rewardIsActive" checked> Active
                </label>
                <small>Only active rewards can be redeemed</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeRewardModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Reward</button>
            </div>
        </form>
    </div>
</div>

<!-- Award Points Modal -->
<div id="awardPointsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAwardPointsModal()">&times;</span>
        <h2>Award Points</h2>
        <form id="awardPointsForm">
            <div class="form-group">
                <label>Select Student *</label>
                <select id="awardPointsUserId" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Points *</label>
                <input type="number" id="awardPointsAmount" required min="1" 
                       placeholder="e.g., 10, 50, 100">
                <small>Number of points to award (minimum: 1)</small>
            </div>
            <div class="form-group">
                <label>Action Type *</label>
                <input type="text" id="awardPointsActionType" required 
                       placeholder="e.g., manual_award, event_participation, bonus">
                <small>Type of action that triggered this award</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="awardPointsDescription" rows="3" 
                          placeholder="Optional description for this point award"></textarea>
                <small>Provide details about why these points are being awarded</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeAwardPointsModal()">Cancel</button>
                <button type="submit" class="btn-primary">Award Points</button>
            </div>
        </form>
    </div>
</div>

<!-- Deduct Points Modal -->
<div id="deductPointsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeDeductPointsModal()">&times;</span>
        <h2>Deduct Points</h2>
        <form id="deductPointsForm">
            <div class="form-group">
                <label>Select Student *</label>
                <select id="deductPointsUserId" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Points to Deduct *</label>
                <input type="number" id="deductPointsAmount" required min="1" 
                       placeholder="e.g., 10, 20, 50">
                <small>Number of points to deduct (minimum: 1)</small>
            </div>
            <div class="form-group">
                <label>Action Type *</label>
                <input type="text" id="deductPointsActionType" required 
                       placeholder="e.g., rule_violation, penalty, adjustment">
                <small>Type of action that triggered this deduction</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="deductPointsDescription" rows="3" 
                          placeholder="Optional description for this point deduction"></textarea>
                <small>Provide details about why these points are being deducted</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeDeductPointsModal()">Cancel</button>
                <button type="submit" class="btn-danger">Deduct Points</button>
            </div>
        </form>
    </div>
</div>

<!-- View User Points Modal -->
<div id="viewUserPointsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeViewUserPointsModal()">&times;</span>
        <h2>User Points Details</h2>
        <div id="viewUserPointsContent">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #a31f37;"></i>
                <p style="margin-top: 20px;">Loading...</p>
            </div>
        </div>
    </div>
</div>

<div id="issueCertificateModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeIssueCertificateModal()">&times;</span>
        <h2>Issue Certificate</h2>
        <form id="issueCertificateForm">
            <div class="form-group">
                <label>Select Student *</label>
                <select id="issueCertificateUserId" required>
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Certificate Title *</label>
                <input type="text" id="issueCertificateTitle" required 
                       placeholder="e.g., Gold Certificate, VIP Badge, Achievement Award">
                <small>Display name for this certificate</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="issueCertificateDescription" rows="3" 
                          placeholder="Optional description for this certificate"></textarea>
                <small>Provide details about this certificate</small>
            </div>
            <div class="form-group">
                <label>Related Reward (Optional)</label>
                <select id="issueCertificateRewardId">
                    <option value="">No reward</option>
                </select>
                <small>Link this certificate to a specific reward (optional)</small>
            </div>
            <div class="form-group">
                <label>Issued Date</label>
                <input type="date" id="issueCertificateIssuedDate">
                <small>Leave empty to use today's date</small>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeIssueCertificateModal()">Cancel</button>
                <button type="submit" class="btn-primary">Issue Certificate</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/admin/loyalty/index.js') }}"></script>

<script>
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
    
    // Update header
    updateLoyaltyHeader('Point Earning Rules', 'Manage and configure point earning rules', 'Create Rule', 'showCreateRuleModal');
    
    container.innerHTML = `
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
        displayRewardsManagement(rewards);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load rewards');
    }
}

function displayRewardsManagement(rewards) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Update header
    updateLoyaltyHeader('Rewards Management', 'Manage available rewards for redemption', 'Create Reward', 'showCreateRewardModal');
    
    container.innerHTML = `
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
        displayRedemptionsManagement(redemptions);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load redemptions');
    }
}

function displayRedemptionsManagement(redemptions) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Update header
    updateLoyaltyHeader('Redemption Approvals', 'Review and approve reward redemptions', '', '');
    document.getElementById('loyaltyHeaderBtn').style.display = 'none';
    
    container.innerHTML = `
        <div class="filters-section" style="margin-bottom: 20px;">
            <div class="filters-card">
                <div class="filters-form">
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
    
    // Update header
    updateLoyaltyHeader('Student Points Tracking', 'View and manage student loyalty points', '', '');
    document.getElementById('loyaltyHeaderBtn').style.display = 'none';
    
    container.innerHTML = `
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
        displayCertificatesManagement(certificates);
    } else {
        showError(document.getElementById('adminLoyaltyContent'), result.error || 'Failed to load certificates');
    }
}

function displayCertificatesManagement(certificates) {
    const container = document.getElementById('adminLoyaltyContent');
    
    // Update header
    updateLoyaltyHeader('Certificates Management', 'Issue and manage student certificates', 'Issue Certificate', 'showIssueCertificateModal');
    
    container.innerHTML = `
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
function handleRewardImageUpload(event) {
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
function removeRewardImage() {
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

}
    font-size: 2.2rem;
    color: #ffffff;
    margin: 0 0 8px 0;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.page-header-content p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    margin: 0;
    font-weight: 400;
}

.btn-header-white {
    background-color: #ffffff;
    color: #cb2d3e; 
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    cursor: pointer;
}

.btn-header-white:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: #a01a2a;
}

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
#ruleModal.modal, #rewardModal.modal, #deleteConfirmModal.modal, #awardPointsModal.modal, #deductPointsModal.modal, #viewUserPointsModal.modal, #issueCertificateModal.modal {
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

#ruleModal.modal.show, #rewardModal.modal.show, #deleteConfirmModal.modal.show, #awardPointsModal.modal.show, #deductPointsModal.modal.show, #viewUserPointsModal.modal.show, #issueCertificateModal.modal.show {
    display: block;
}

#ruleModal .modal-content, #rewardModal .modal-content, #deleteConfirmModal .modal-content, #awardPointsModal .modal-content, #deductPointsModal .modal-content, #viewUserPointsModal .modal-content, #issueCertificateModal .modal-content {
    background: #ffffff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

#viewUserPointsModal .modal-content {
    max-width: 800px;
}

.text-success {
    color: #28a745;
}

.text-danger {
    color: #dc3545;
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

/* Table Container Enhancement */
.table-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    position: relative;
    overflow: visible;
}

.table-container .data-table {
    overflow: hidden;
    border-radius: 12px;
}

.data-table {
    margin: 0;
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}

.data-table tr {
    vertical-align: bottom;
}

.data-table thead {
    background: #f8f9fa;
}

.data-table th {
    font-weight: 600;
    color: #2d3436;
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
}

.data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f2f6;
    color: #2d3436;
    vertical-align: bottom;
}

/* Ensure Status and Actions columns align at bottom - same row */
.data-table tbody tr td:nth-child(5),
.data-table tbody tr td:nth-child(6) {
    vertical-align: bottom !important;
    padding-bottom: 15px !important;
    white-space: nowrap;
}

.data-table tbody tr {
    vertical-align: bottom;
}

.data-table tbody tr:hover {
    background-color: #f8f9fa;
}

.data-table .actions {
    display: flex !important;
    gap: 5px;
    flex-wrap: nowrap !important;
    align-items: center;
    justify-content: center;
    white-space: nowrap !important;
    min-width: 80px;
    width: 100px;
    vertical-align: bottom;
}

.data-table .actions button {
    margin: 0 !important;
    vertical-align: bottom;
}

.data-table .text-center {
    text-align: center;
    padding: 40px;
    color: #636e72;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
    letter-spacing: 0.3px;
    vertical-align: middle;
    line-height: 1.5;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.badge-secondary {
    background: #f3f4f6;
    color: #4b5563;
}

/* Button Styles */
.btn-sm {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    flex-shrink: 0;
    margin: 0;
    vertical-align: middle;
    line-height: 1;
}

.btn-sm.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-sm.btn-info:hover {
    background: #138496;
}

.btn-sm.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-sm.btn-primary:hover {
    background: #2563eb;
}

.btn-sm.btn-success {
    background: #10b981;
    color: white;
}

.btn-sm.btn-success:hover {
    background: #059669;
}

.btn-sm.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-sm.btn-danger:hover {
    background: #dc2626;
}

/* Square buttons for Rewards table */
.btn-sm.btn-square {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    border-radius: 4px;
}

.btn-sm.btn-square i {
    font-size: 0.85rem;
    margin: 0;
}

/* Filters Section Styling */
.filters-section {
    margin-bottom: 30px;
}

.filters-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-input-wrapper,
.filter-select-wrapper {
    position: relative;
    flex: 1;
    min-width: 200px;
}

.filter-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 1;
    pointer-events: none;
}

.filter-input,
.filter-select {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #495057;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-input::placeholder {
    color: #adb5bd;
}

.filter-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236c757d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-input-wrapper,
    .filter-select-wrapper {
        width: 100%;
    }
    
    .data-table {
        font-size: 0.85rem;
    }

    .data-table th,
    .data-table td {
        padding: 10px;
    }

    .data-table .actions {
        flex-direction: column;
    }
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
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: border-color 0.3s;
    background-color: white;
}

.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #a31f37;
    box-shadow: 0 0 0 3px rgba(163, 31, 55, 0.1);
}

.form-group select:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
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

@endsection


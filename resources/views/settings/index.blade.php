@extends('layouts.app')

@section('title', 'Settings - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Settings</h1>
        <p>Manage your account preferences and notification settings</p>
    </div>

    <div class="settings-container">
        <div class="settings-card">
            <div class="settings-content">
                <!-- Notification Preferences -->
                <div class="settings-group">
                    <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                    <p class="settings-description">Choose which notifications you want to receive</p>
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label class="switch-label">
                                <span>Email Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="setting-email-notifications">
                                    <span class="slider"></span>
                                </label>
                            </label>
                            <p class="setting-description">Receive notifications via email</p>
                        </div>
                        <div class="setting-item">
                            <label class="switch-label">
                                <span>System Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="setting-system-notifications">
                                    <span class="slider"></span>
                                </label>
                            </label>
                            <p class="setting-description">Receive in-app system notifications</p>
                        </div>
                        <div class="setting-item">
                            <label class="switch-label">
                                <span>Booking Reminders</span>
                                <label class="switch">
                                    <input type="checkbox" id="setting-booking-reminders">
                                    <span class="slider"></span>
                                </label>
                            </label>
                            <p class="setting-description">Get reminded about upcoming bookings</p>
                        </div>
                        <div class="setting-item">
                            <label class="switch-label">
                                <span>Facility Maintenance Alerts</span>
                                <label class="switch">
                                    <input type="checkbox" id="setting-facility-maintenance">
                                    <span class="slider"></span>
                                </label>
                            </label>
                            <p class="setting-description">Be notified about facility maintenance</p>
                        </div>
                        <div class="setting-item">
                            <label class="switch-label">
                                <span>Loyalty Rewards Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="setting-loyalty-rewards">
                                    <span class="slider"></span>
                                </label>
                            </label>
                            <p class="setting-description">Get notified about new rewards and points</p>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button class="btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <button class="btn-secondary" onclick="resetSettings()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadSettings();
});

// Settings Functions
async function loadSettings() {
    const result = await API.get('/users/profile/settings');
    
    if (result.success) {
        const settings = result.data.data || {};
        displaySettings(settings);
    } else {
        console.error('Error loading settings:', result.error);
        alert('Error loading settings: ' + (result.error || 'Unknown error'));
    }
}

function displaySettings(settings) {
    const notifications = settings.notifications || {};
    
    // Set notification checkboxes
    document.getElementById('setting-email-notifications').checked = notifications.email !== false;
    document.getElementById('setting-system-notifications').checked = notifications.system !== false;
    document.getElementById('setting-booking-reminders').checked = notifications.booking_reminders !== false;
    document.getElementById('setting-facility-maintenance').checked = notifications.facility_maintenance !== false;
    document.getElementById('setting-loyalty-rewards').checked = notifications.loyalty_rewards !== false;
}

async function saveSettings() {
    const settings = {
        notifications: {
            email: document.getElementById('setting-email-notifications').checked,
            system: document.getElementById('setting-system-notifications').checked,
            booking_reminders: document.getElementById('setting-booking-reminders').checked,
            facility_maintenance: document.getElementById('setting-facility-maintenance').checked,
            loyalty_rewards: document.getElementById('setting-loyalty-rewards').checked,
        }
    };
    
    const result = await API.put('/users/profile/settings', settings);
    
    if (result.success) {
        alert('Settings saved successfully!');
    } else {
        alert('Error saving settings: ' + (result.error || 'Unknown error'));
    }
}

function resetSettings() {
    // Reset to default settings (all notifications enabled)
    document.getElementById('setting-email-notifications').checked = true;
    document.getElementById('setting-system-notifications').checked = true;
    document.getElementById('setting-booking-reminders').checked = true;
    document.getElementById('setting-facility-maintenance').checked = true;
    document.getElementById('setting-loyalty-rewards').checked = true;
    
    // Save the default settings
    saveSettings();
}
</script>

<style>
.settings-container {
    max-width: 1000px;
    margin: 0 auto;
}

.settings-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.settings-content {
    padding: 30px;
}

.settings-group {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.settings-group:last-of-type {
    border-bottom: none;
}

.settings-group h3 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 1.4em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-group h3 i {
    color: #667eea;
}

.settings-description {
    color: #666;
    margin-bottom: 20px;
    font-size: 0.9em;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.setting-item {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.setting-item label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 0.95em;
}

.setting-description {
    color: #666;
    font-size: 0.85em;
    margin-top: 5px;
    margin-bottom: 0;
}

.form-control {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
    margin-top: 8px;
}

/* Toggle Switch Styles */
.switch-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    cursor: pointer;
    margin-bottom: 5px;
}

.switch-label span {
    font-weight: 500;
    color: #333;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #667eea;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

input:disabled + .slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.settings-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

/* Dark Theme Styles */
.dark-theme {
    background-color: #1a1a1a;
    color: #e0e0e0;
}

.dark-theme .settings-card {
    background: #2d2d2d;
    color: #e0e0e0;
}

.dark-theme .settings-group {
    border-bottom-color: #444;
}

.dark-theme .settings-group h3 {
    color: #e0e0e0;
}

.dark-theme .settings-description {
    color: #bbb;
}

.dark-theme .setting-item {
    background: #3d3d3d;
}

.dark-theme .setting-item label,
.dark-theme .switch-label span {
    color: #e0e0e0;
}

.dark-theme .setting-description {
    color: #bbb;
}

.dark-theme .form-control {
    background: #4d4d4d;
    color: #e0e0e0;
    border-color: #555;
}

.dark-theme .settings-actions {
    border-top-color: #444;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-actions {
        flex-direction: column;
    }
}
</style>
@endsection


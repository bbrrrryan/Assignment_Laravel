@extends('layouts.app')

@section('title', 'Facilities - TARUMT FMS')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1>Facilities Management</h1>
        <div id="adminActions" style="display: none;">
            <button class="btn-primary" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Add Facility
            </button>
        </div>
    </div>

    <div class="filters">
        <input type="text" id="searchInput" placeholder="Search facilities..." onkeyup="filterFacilities()">
        <select id="typeFilter" onchange="filterFacilities()">
            <option value="">All Types</option>
            <option value="laboratory">Laboratory</option>
            <option value="classroom">Classroom</option>
            <option value="sports">Sports</option>
            <option value="auditorium">Auditorium</option>
        </select>
        <select id="statusFilter" onchange="filterFacilities()">
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="maintenance">Maintenance</option>
            <option value="unavailable">Unavailable</option>
        </select>
    </div>

    <div id="facilitiesList" class="facilities-grid">
        <p>Loading facilities...</p>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="facilityModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Add Facility</h2>
        <form id="facilityForm">
            <input type="hidden" id="facilityId">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" id="facilityName" required>
            </div>
            <div class="form-group">
                <label>Code *</label>
                <input type="text" id="facilityCode" required>
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select id="facilityType" required>
                    <option value="laboratory">Laboratory</option>
                    <option value="classroom">Classroom</option>
                    <option value="sports">Sports</option>
                    <option value="auditorium">Auditorium</option>
                </select>
            </div>
            <div class="form-group">
                <label>Location *</label>
                <input type="text" id="facilityLocation" required>
            </div>
            <div class="form-group">
                <label>Capacity *</label>
                <input type="number" id="facilityCapacity" required min="1">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="facilityStatus">
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="facilityDescription" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Wait for DOM and API to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if API is loaded
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    // Check authentication
    if (!API.requireAuth()) return;

    initFacilities();
});

let facilities = [];

function initFacilities() {
    // Show admin actions if user is admin
    if (API.isAdmin()) {
        const adminActions = document.getElementById('adminActions');
        if (adminActions) {
            adminActions.style.display = 'block';
        }
    } else {
        // Hide edit/delete buttons for non-admin users
        // This will be handled in displayFacilities function
    }
    
    // Bind form submit event
    const facilityForm = document.getElementById('facilityForm');
    if (facilityForm) {
        facilityForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('facilityId').value;
            const isEdit = !!id;

            const data = {
                name: document.getElementById('facilityName').value,
                code: document.getElementById('facilityCode').value,
                type: document.getElementById('facilityType').value,
                location: document.getElementById('facilityLocation').value,
                capacity: parseInt(document.getElementById('facilityCapacity').value),
                status: document.getElementById('facilityStatus').value,
                description: document.getElementById('facilityDescription').value
            };

            const result = isEdit 
                ? await API.put(`/facilities/${id}`, data)
                : await API.post('/facilities', data);

            if (result.success) {
                window.closeModal();
                loadFacilities();
                alert('Facility saved successfully!');
            } else {
                alert(result.error || 'Error saving facility');
            }
        });
    }
    
    loadFacilities();
}

async function loadFacilities() {
    showLoading(document.getElementById('facilitiesList'));
    
    const result = await API.get('/facilities');
    
    if (result.success) {
        facilities = result.data.data?.data || result.data.data || [];
        displayFacilities(facilities);
    } else {
        showError(document.getElementById('facilitiesList'), result.error || 'Failed to load facilities');
    }
}

function displayFacilities(facilitiesToShow) {
    const container = document.getElementById('facilitiesList');
    if (facilitiesToShow.length === 0) {
        container.innerHTML = '<p>No facilities found</p>';
        return;
    }

    container.innerHTML = facilitiesToShow.map(facility => `
        <div class="facility-card">
            <div class="facility-header">
                <h3>${facility.name}</h3>
                <span class="status-badge status-${facility.status}">${facility.status}</span>
            </div>
            <div class="facility-body">
                <p><i class="fas fa-code"></i> ${facility.code}</p>
                <p><i class="fas fa-map-marker-alt"></i> ${facility.location}</p>
                <p><i class="fas fa-users"></i> Capacity: ${facility.capacity}</p>
                <p><i class="fas fa-tag"></i> ${facility.type}</p>
            </div>
            <div class="facility-actions">
                <button class="btn-sm" onclick="viewFacility(${facility.id})">View</button>
                ${typeof API !== 'undefined' && API.isAdmin() ? `
                    <button class="btn-sm" onclick="editFacility(${facility.id})">Edit</button>
                    <button class="btn-sm btn-danger" onclick="deleteFacility(${facility.id})">Delete</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}


// Make functions global so onclick can access them
window.showCreateModal = function() {
    document.getElementById('modalTitle').textContent = 'Add Facility';
    document.getElementById('facilityForm').reset();
    document.getElementById('facilityId').value = '';
    document.getElementById('facilityModal').style.display = 'block';
};

window.editFacility = function(id) {
    const facility = facilities.find(f => f.id === id);
    if (!facility) return;

    document.getElementById('modalTitle').textContent = 'Edit Facility';
    document.getElementById('facilityId').value = facility.id;
    document.getElementById('facilityName').value = facility.name;
    document.getElementById('facilityCode').value = facility.code;
    document.getElementById('facilityType').value = facility.type;
    document.getElementById('facilityLocation').value = facility.location;
    document.getElementById('facilityCapacity').value = facility.capacity;
    document.getElementById('facilityStatus').value = facility.status;
    document.getElementById('facilityDescription').value = facility.description || '';
    document.getElementById('facilityModal').style.display = 'block';
};

window.closeModal = function() {
    document.getElementById('facilityModal').style.display = 'none';
};

window.filterFacilities = function() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;

    const filtered = facilities.filter(f => {
        const matchSearch = !search || f.name.toLowerCase().includes(search) || f.code.toLowerCase().includes(search);
        const matchType = !type || f.type === type;
        const matchStatus = !status || f.status === status;
        return matchSearch && matchType && matchStatus;
    });

    displayFacilities(filtered);
};

window.viewFacility = function(id) {
    window.location.href = `/facilities/${id}`;
};

window.deleteFacility = async function(id) {
    if (!confirm('Are you sure you want to delete this facility?')) return;

    const result = await API.delete(`/facilities/${id}`);
    
    if (result.success) {
        loadFacilities();
        alert('Facility deleted successfully!');
    } else {
        alert(result.error || 'Error deleting facility');
    }
};
</script>
@endsection


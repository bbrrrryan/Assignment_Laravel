// Facilities Page JavaScript (Index and Show combined)
// ============================================
// INDEX PAGE FUNCTIONS
// ============================================

// Wait for DOM and API to be ready (for index page)
document.addEventListener('DOMContentLoaded', function() {
    // Check if API is loaded
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    // Check authentication
    if (!API.requireAuth()) return;

    // Only initialize index page if we're on the index page
    if (document.getElementById('facilitiesList')) {
        initFacilities();
    }
});

// Index page variables
let facilities = [];
let filteredFacilities = [];
let currentPage = 1;
let totalPages = 1;
let paginationData = null;

function initFacilities() {
    // Populate type filter based on user role
    populateTypeFilter();
    
    // Load facilities
    loadFacilities();
}

function populateTypeFilter() {
    const typeFilter = document.getElementById('typeFilter');
    if (!typeFilter) return;

    const user = API.getUser();
    if (!user) return;

    // Clear existing options except "All Types"
    typeFilter.innerHTML = '<option value="">All Types</option>';

    // Check if user is student
    if (API.isStudent && API.isStudent()) {
        // Students can only see sports and library
        typeFilter.innerHTML += `
            <option value="sports">Sports</option>
            <option value="library">Library</option>
        `;
    } else if (API.isStaff && API.isStaff()) {
        // Staff can see all types
        typeFilter.innerHTML += `
            <option value="classroom">Classroom</option>
            <option value="laboratory">Laboratory</option>
            <option value="sports">Sports</option>
            <option value="auditorium">Auditorium</option>
            <option value="library">Library</option>
        `;
    } else {
        // Default: show all types (for admin or unknown role)
        typeFilter.innerHTML += `
            <option value="classroom">Classroom</option>
            <option value="laboratory">Laboratory</option>
            <option value="sports">Sports</option>
            <option value="auditorium">Auditorium</option>
            <option value="library">Library</option>
        `;
    }
}

async function loadFacilities(page = 1) {
    const container = document.getElementById('facilitiesList');
    if (!container) return;

    showLoading(container);
    
    try {
        // Build query parameters
        const params = new URLSearchParams();
        params.append('page', page);
        
        const search = document.getElementById('searchInput')?.value || '';
        const type = document.getElementById('typeFilter')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';
        
        if (search) params.append('search', search);
        if (type) params.append('type', type);
        if (status) params.append('status', status);
        
        const result = await API.get(`/facilities?${params.toString()}`);
        
        if (result.success) {
            const data = result.data.data || result.data;
            facilities = data.data || [];
            currentPage = data.current_page || 1;
            totalPages = data.last_page || 1;
            paginationData = data;
            
            // Filter out unavailable and deleted facilities (should already be filtered by API, but double-check)
            facilities = facilities.filter(f => 
                f.status !== 'unavailable' && 
                !f.is_deleted
            );
            
            displayFacilities(facilities);
            displayPagination();
        } else {
            showError(container, result.error || 'Failed to load facilities');
        }
    } catch (error) {
        console.error('Error loading facilities:', error);
        showError(container, 'An error occurred while loading facilities');
    }
}

function displayFacilities(facilitiesToShow) {
    const container = document.getElementById('facilitiesList');
    if (!container) return;

    if (facilitiesToShow.length === 0) {
        container.innerHTML = '<p>No facilities found</p>';
        // Remove pagination if no results
        const existingPagination = document.getElementById('paginationWrapper');
        if (existingPagination) {
            existingPagination.remove();
        }
        return;
    }

    container.innerHTML = facilitiesToShow.map(facility => {
        const imageUrl = facility.image_url || '';
        const description = facility.description || '';
        
        return `
            <div class="facility-card">
                ${imageUrl ? `<img src="${imageUrl}" alt="${facility.name}" class="facility-image" onerror="this.style.display='none'">` : ''}
                <div class="facility-header">
                    <h3>${escapeHtml(facility.name)}</h3>
                    <span class="status-badge status-${facility.status}">${facility.status}</span>
                </div>
                <div class="facility-body">
                    <div class="facility-info">
                        <i class="fas fa-code"></i>
                        <span><strong>Code:</strong> ${escapeHtml(facility.code)}</span>
                    </div>
                    <div class="facility-info">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong>Location:</strong> ${escapeHtml(facility.location)}</span>
                    </div>
                    <div class="facility-info">
                        <i class="fas fa-users"></i>
                        <span><strong>Capacity:</strong> ${facility.capacity} people</span>
                    </div>
                    <div class="facility-info">
                        <i class="fas fa-tag"></i>
                        <span><strong>Type:</strong> ${capitalizeFirst(facility.type)}</span>
                    </div>
                    ${description ? `<div class="facility-description">${escapeHtml(description)}</div>` : ''}
                </div>
                <div class="facility-actions">
                    <a href="/facilities/${facility.id}" class="btn-view">
                        <i class="fas fa-eye"></i>
                        View Details
                    </a>
                </div>
            </div>
        `;
    }).join('');
}

function filterFacilities() {
    // Reset to page 1 when filtering
    currentPage = 1;
    loadFacilities(1);
}

function clearFilters() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');

    if (searchInput) searchInput.value = '';
    if (typeFilter) typeFilter.value = '';
    if (statusFilter) statusFilter.value = '';

    currentPage = 1;
    loadFacilities(1);
}

function displayPagination() {
    const container = document.getElementById('facilitiesList');
    if (!container || !paginationData) return;

    // Remove existing pagination if any
    const existingPagination = document.getElementById('paginationWrapper');
    if (existingPagination) {
        existingPagination.remove();
    }

    // Don't show pagination if only one page
    if (totalPages <= 1) return;

    // Create pagination wrapper
    const paginationWrapper = document.createElement('div');
    paginationWrapper.id = 'paginationWrapper';
    paginationWrapper.className = 'pagination-wrapper';
    
    let paginationHTML = '<div class="pagination">';
    
    // Previous button
    if (currentPage > 1) {
        paginationHTML += `
            <span class="page-item">
                <a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${currentPage - 1});">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </a>
            </span>
        `;
    } else {
        paginationHTML += `
            <span class="page-item disabled">
                <span class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </span>
            </span>
        `;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `
            <span class="page-item">
                <a href="#" class="page-link" onclick="event.preventDefault(); goToPage(1);">1</a>
            </span>
        `;
        if (startPage > 2) {
            paginationHTML += `
                <span class="page-item disabled">
                    <span class="page-link">...</span>
                </span>
            `;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHTML += `
                <span class="page-item active">
                    <span class="page-link">${i}</span>
                </span>
            `;
        } else {
            paginationHTML += `
                <span class="page-item">
                    <a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${i});">${i}</a>
                </span>
            `;
        }
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `
                <span class="page-item disabled">
                    <span class="page-link">...</span>
                </span>
            `;
        }
        paginationHTML += `
            <span class="page-item">
                <a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${totalPages});">${totalPages}</a>
            </span>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        paginationHTML += `
            <span class="page-item">
                <a href="#" class="page-link" onclick="event.preventDefault(); goToPage(${currentPage + 1});">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </a>
            </span>
        `;
    } else {
        paginationHTML += `
            <span class="page-item disabled">
                <span class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </span>
            </span>
        `;
    }
    
    paginationHTML += '</div>';
    
    paginationWrapper.innerHTML = paginationHTML;
    container.parentNode.appendChild(paginationWrapper);
}

function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    loadFacilities(page);
    
    // Scroll to top of facilities list
    const container = document.getElementById('facilitiesList');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function showLoading(container) {
    if (container) {
        container.innerHTML = '<p>Loading facilities...</p>';
    }
}

// ============================================
// SHOW PAGE FUNCTIONS
// ============================================

// Show page variables
let showFacilityId = null;
let feedbackImageBase64 = null;

function initFacilityShow(facilityId) {
    if (typeof API === 'undefined') {
        console.error('API.js not loaded!');
        alert('Error: API functions not loaded. Please refresh the page.');
        return;
    }

    if (!API.requireAuth()) return;

    loadFacilityDetails(facilityId);
    loadFacilityFeedbacks(facilityId);
    setupFeedbackForm(facilityId);
}

function loadFacilityDetails(id) {
    showFacilityId = id;
    const container = document.getElementById('facilityDetails');
    if (!container) return;
    
    API.get(`/facilities/${id}`)
        .then(result => {
            if (result.success) {
                const facility = result.data.data;
                displayFacilityDetails(facility);
                
                // Show action buttons
                const actionButtons = document.getElementById('actionButtons');
                if (actionButtons) {
                    actionButtons.style.display = 'block';
                }
                
                // Set booking link and hide if maintenance
                const addBookingBtn = document.getElementById('addBookingBtn');
                if (addBookingBtn) {
                    if (facility.status === 'maintenance') {
                        // Hide Add Booking button if facility is under maintenance
                        addBookingBtn.style.display = 'none';
                    } else {
                        addBookingBtn.style.display = 'inline-flex';
                        // Store facility_id in sessionStorage and navigate to create booking page
                        addBookingBtn.onclick = function(e) {
                            e.preventDefault();
                            // Store facility_id in sessionStorage for API-based approach
                            sessionStorage.setItem('selectedFacilityId', id);
                            window.location.href = '/bookings/create';
                        };
                    }
                }
            } else {
                showErrorForShow(container, result.error || 'Failed to load facility details');
            }
        })
        .catch(error => {
            console.error('Error loading facility:', error);
            showErrorForShow(container, 'An error occurred while loading facility details');
        });
}

function displayFacilityDetails(facility) {
    const container = document.getElementById('facilityDetails');
    if (!container) return;
    
    const imageHtml = facility.image_url ? `
        <div class="detail-item" style="grid-column: 1 / -1;">
            <label>Image</label>
            <p>
                <img src="${facility.image_url}" alt="${escapeHtml(facility.name)}" 
                     class="facility-image" 
                     style="max-width: 400px; max-height: 400px; cursor: pointer; border-radius: 8px;"
                     onclick="window.open('${facility.image_url}', '_blank')">
                <br>
                <a href="${facility.image_url}" target="_blank" style="display: inline-block; margin-top: 10px; color: #0066cc; text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> View Full Image
                </a>
            </p>
        </div>
    ` : '';
    
    const availableDaysHtml = facility.available_day && facility.available_day.length > 0 ? `
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
            ${facility.available_day.map(dayKey => {
                const dayNames = {
                    'monday': 'Monday',
                    'tuesday': 'Tuesday',
                    'wednesday': 'Wednesday',
                    'thursday': 'Thursday',
                    'friday': 'Friday',
                    'saturday': 'Saturday',
                    'sunday': 'Sunday'
                };
                return `<span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em;">${dayNames[dayKey] || dayKey.charAt(0).toUpperCase() + dayKey.slice(1)}</span>`;
            }).join('')}
        </div>
    ` : '';
    
    const availableTimeHtml = facility.available_time ? `
        <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em; margin-left: 8px;">
            ${facility.available_time.start || 'N/A'} - ${facility.available_time.end || 'N/A'}
        </span>
    ` : '';
    
    const equipmentHtml = facility.equipment && Array.isArray(facility.equipment) && facility.equipment.length > 0 ? `
        <div class="detail-item" style="grid-column: 1 / -1;">
            <label>Equipment</label>
            <p>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                    ${facility.equipment.map(item => 
                        `<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.9em;">${escapeHtml(item)}</span>`
                    ).join('')}
                </div>
            </p>
        </div>
    ` : '';
    
    container.innerHTML = `
        <div class="details-card">
            <div class="details-section">
                <h2><i class="fas fa-building"></i> Facility Information</h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Name</label>
                        <p>${escapeHtml(facility.name || 'N/A')}</p>
                    </div>
                    <div class="detail-item">
                        <label>Code</label>
                        <p>${escapeHtml(facility.code || 'N/A')}</p>
                    </div>
                    <div class="detail-item">
                        <label>Type</label>
                        <p><span class="badge badge-info">${capitalizeFirst(facility.type || 'N/A')}</span></p>
                    </div>
                    <div class="detail-item">
                        <label>Location</label>
                        <p>${escapeHtml(facility.location || 'N/A')}</p>
                    </div>
                    <div class="detail-item">
                        <label>Capacity</label>
                        <p>${facility.capacity || 'N/A'} people</p>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <p><span class="status-badge status-${facility.status}">${facility.status || 'N/A'}</span></p>
                    </div>
                    ${facility.description ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Description</label>
                        <p>${escapeHtml(facility.description)}</p>
                    </div>
                    ` : ''}
                    ${imageHtml}
                </div>
            </div>

            <div class="details-section">
                <h2><i class="fas fa-calendar-alt"></i> Booking Settings</h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Max Booking Hours</label>
                        <p>${facility.max_booking_hours || 4} hours</p>
                    </div>
                    ${facility.enable_multi_attendees ? `
                    <div class="detail-item">
                        <label>Multi-Attendees</label>
                        <p>Enabled${facility.max_attendees ? ` (Max: ${facility.max_attendees})` : ''}</p>
                    </div>
                    ` : ''}
                    ${availableDaysHtml || availableTimeHtml ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Available Days & Times</label>
                        <p>
                            ${availableDaysHtml ? `<div style="margin-bottom: 10px;"><strong>Available Days:</strong>${availableDaysHtml}</div>` : ''}
                            ${availableTimeHtml ? `<div><strong>Time Range:</strong>${availableTimeHtml}</div>` : ''}
                        </p>
                    </div>
                    ` : ''}
                    ${equipmentHtml}
                    ${facility.rules ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Rules</label>
                        <div class="rules-list">
                            ${formatRulesAsBulletPoints(facility.rules)}
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

async function loadFacilityFeedbacks(id) {
    const container = document.getElementById('feedbacksList');
    const section = document.getElementById('feedbacksSection');
    
    if (!container || !section) return;
    
    try {
        const result = await API.get(`/facilities/${id}/feedbacks?limit=10`);
        
        if (result.success && result.data.status === 'S') {
            const feedbacks = result.data.data?.feedbacks || [];
            section.style.display = 'block';
            
            if (feedbacks.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #6c757d; padding: 20px;">No feedbacks yet. Be the first to submit feedback!</p>';
            } else {
                displayFacilityFeedbacks(feedbacks);
            }
        } else {
            container.innerHTML = '<p style="color: #dc3545;">Failed to load feedbacks</p>';
        }
    } catch (error) {
        console.error('Error loading feedbacks:', error);
        container.innerHTML = '<p style="color: #dc3545;">An error occurred while loading feedbacks</p>';
    }
}

function displayFacilityFeedbacks(feedbacks) {
    const container = document.getElementById('feedbacksList');
    if (!container) return;
    
    container.innerHTML = feedbacks.map(feedback => {
        const user = feedback.user || {};
        const userName = user.name || 'Anonymous';
        const userEmail = user.email || '';
        const date = new Date(feedback.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const stars = '★'.repeat(feedback.rating || 0) + '☆'.repeat(5 - (feedback.rating || 0));
        
        const imageHtml = feedback.image ? `
            <div class="feedback-image">
                <img src="${feedback.image}" alt="Feedback image" onclick="window.open('${feedback.image}', '_blank')">
            </div>
        ` : '';
        
        return `
            <div class="feedback-item">
                <div class="feedback-header">
                    <div class="feedback-user">
                        <div class="feedback-user-info">
                            <h4>${escapeHtml(userName)}</h4>
                            <p>${escapeHtml(userEmail)}</p>
                        </div>
                    </div>
                    <div class="feedback-rating">
                        <span class="stars">${stars}</span>
                        <span class="rating-number">${feedback.rating}/5</span>
                    </div>
                </div>
                <div class="feedback-body">
                    <div class="feedback-subject">${escapeHtml(feedback.subject || '')}</div>
                    <div class="feedback-message">${escapeHtml(feedback.message || '')}</div>
                    ${imageHtml}
                </div>
                <div class="feedback-footer">
                    <span class="feedback-type ${feedback.type}">${capitalizeFirst(feedback.type || 'general')}</span>
                    <span>${date}</span>
                </div>
            </div>
        `;
    }).join('');
}

function setupFeedbackForm(id) {
    const form = document.getElementById('feedbackForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            facility_id: id,
            type: document.getElementById('feedbackType').value,
            subject: document.getElementById('feedbackSubject').value,
            message: document.getElementById('feedbackMessage').value,
            rating: parseInt(document.getElementById('feedbackRating').value),
            image: feedbackImageBase64 || null
        };
        
        try {
            const result = await API.post('/feedbacks', formData);
            
            if (result.success) {
                if (typeof showToast === 'function') {
                    showToast('Feedback submitted successfully!', 'success');
                } else {
                    alert('Feedback submitted successfully!');
                }
                closeFeedbackModal();
                loadFacilityFeedbacks(id);
                form.reset();
                feedbackImageBase64 = null;
                const preview = document.getElementById('imagePreview');
                if (preview) preview.style.display = 'none';
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.error || 'Failed to submit feedback', 'error');
                } else {
                    alert(result.error || 'Failed to submit feedback');
                }
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            if (typeof showToast === 'function') {
                showToast('An error occurred while submitting feedback', 'error');
            } else {
                alert('An error occurred while submitting feedback');
            }
        }
    });
}

function openFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    const facilityIdInput = document.getElementById('feedbackFacilityId');
    
    if (modal) {
        modal.style.display = 'block';
        if (facilityIdInput) {
            facilityIdInput.value = showFacilityId;
        }
    }
}

function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Check file size (1MB max)
    if (file.size > 1024 * 1024) {
        alert('Image size must be less than 1MB');
        event.target.value = '';
        return;
    }
    
    // Check file type
    if (!file.type.match('image.*')) {
        alert('Please select an image file');
        event.target.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        feedbackImageBase64 = e.target.result;
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        
        if (preview && previewImg) {
            previewImg.src = feedbackImageBase64;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    feedbackImageBase64 = null;
    const fileInput = document.getElementById('feedbackImage');
    const preview = document.getElementById('imagePreview');
    
    if (fileInput) fileInput.value = '';
    if (preview) preview.style.display = 'none';
}

// ============================================
// SHARED UTILITY FUNCTIONS
// ============================================

function showError(container, message) {
    if (container) {
        container.innerHTML = `<p style="color: #dc3545;">${escapeHtml(message)}</p>`;
    }
}

function showErrorForShow(container, message) {
    if (container) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                <p style="color: #856404; margin-bottom: 20px;">${escapeHtml(message)}</p>
                <a href="/facilities" class="btn-primary" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: white; border-radius: 6px; background: #cb2d3e;">
                    Back to Facilities
                </a>
            </div>
        `;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatRulesAsBulletPoints(rules) {
    if (!rules) return '';
    
    // Split rules by newlines, semicolons, or commas
    let rulesArray = [];
    
    // First try splitting by newlines
    if (rules.includes('\n')) {
        rulesArray = rules.split('\n').map(r => r.trim()).filter(r => r.length > 0);
    }
    // Then try splitting by semicolons
    else if (rules.includes(';')) {
        rulesArray = rules.split(';').map(r => r.trim()).filter(r => r.length > 0);
    }
    // Then try splitting by commas (but only if there are multiple commas)
    else if (rules.split(',').length > 2) {
        rulesArray = rules.split(',').map(r => r.trim()).filter(r => r.length > 0);
    }
    // Otherwise, treat as a single rule
    else {
        rulesArray = [rules.trim()];
    }
    
    // Format as bullet points
    return rulesArray.map(rule => 
        `<div class="rule-item">
            <i class="fas fa-circle" style="font-size: 0.5rem; color: #cb2d3e; margin-right: 10px; margin-top: 8px;"></i>
            <span>${escapeHtml(rule)}</span>
        </div>`
    ).join('');
}

// ============================================
// GLOBAL FUNCTION EXPORTS
// ============================================

// Make functions global for onclick handlers
window.filterFacilities = filterFacilities;
window.clearFilters = clearFilters;
window.goToPage = goToPage;
window.openFeedbackModal = openFeedbackModal;
window.closeFeedbackModal = closeFeedbackModal;
window.handleImageUpload = handleImageUpload;
window.removeImage = removeImage;
window.initFacilityShow = initFacilityShow;

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('feedbackModal');
    if (event.target === modal) {
        closeFeedbackModal();
    }
}

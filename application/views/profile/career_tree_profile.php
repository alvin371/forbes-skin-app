<!-- Career Tree Profile Widget -->
<div class="career-tree-profile-widget">
    <div class="career-tree-profile-content">
        <!-- Header Section -->
        <div class="career-tree-profile-current">
            <div class="career-tree-profile-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="career-tree-profile-info">
                <h4><?= $user_position['name'] ?? 'Position Not Assigned' ?></h4>
                <div class="career-tree-profile-meta">
                    <?= $user_position['department'] ?? 'No Department' ?> • <?= $user_position['level_name'] ?? 'No Level' ?>
                    <?php if (isset($user_position['time_in_role'])): ?>
                        • <?= $user_position['time_in_role'] ?> in role
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quest Progress Notification -->
        <?php if (isset($career_notifications) && !empty($career_notifications)): ?>
            <?php foreach($career_notifications as $notification): ?>
                <div class="alert alert-<?= $notification['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $notification['icon'] ?>"></i>
                    <strong><?= $notification['title'] ?>:</strong> <?= $notification['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Career Tree Visualization (Simplified) -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-route"></i>
                    Your Career Path
                </h6>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary profile-tree-layout active" data-layout="tree">
                        <i class="fas fa-sitemap"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary profile-tree-layout" data-layout="radial">
                        <i class="fas fa-sun"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="profileCareerTreeContainer" class="profile-career-tree-container">
                    <!-- User-specific career tree will be rendered here -->
                </div>
            </div>
        </div>
        
        <!-- Career Statistics -->
        <div class="row mt-3" id="profileCareerStats">
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-trophy text-white"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0" id="availablePathsCount">0</h6>
                        <small class="text-muted">Available Paths</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0" id="readyPathsCount">0</h6>
                        <small class="text-muted">Ready to Apply</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Career Recommendations -->
        <div class="career-recommendations mt-3" id="careerRecommendations" style="display: none;">
            <h6 class="text-primary mb-3">
                <i class="fas fa-lightbulb"></i>
                Career Recommendations
            </h6>
            <div id="recommendationsList">
                <!-- Recommendations will be populated here -->
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="career-tree-widget-actions mt-3">
            <a href="<?= base_url() ?>profile/career_paths_full" class="btn btn-primary btn-sm">
                <i class="fas fa-route"></i> View Full Career Paths
            </a>
            <?php if (isset($available_quests) && !empty($available_quests)): ?>
                <a href="<?= base_url() ?>profile/available_quests" class="btn btn-success btn-sm">
                    <i class="fas fa-rocket"></i> View Available Quests
                </a>
            <?php else: ?>
                <a href="<?= base_url() ?>profile/quest_history" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-history"></i> Quest History
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Career Readiness Modal -->
<div class="modal fade" id="careerReadinessModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line"></i>
                    Career Readiness Assessment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="readinessContent">
                    <!-- Readiness details will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewQuestRequirements">
                    <i class="fas fa-tasks"></i> View Quest Requirements
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile-specific career tree styles */
.career-tree-profile-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.career-tree-profile-widget::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    z-index: 1;
}

.career-tree-profile-content {
    position: relative;
    z-index: 2;
}

.career-tree-profile-current {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.career-tree-profile-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    font-size: 22px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.career-tree-profile-info h4 {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 600;
}

.career-tree-profile-meta {
    font-size: 14px;
    opacity: 0.9;
}

.profile-career-tree-container {
    min-height: 350px;
    background: #f8f9fa;
    border-radius: 8px;
    position: relative;
}

.career-tree-profile-widget .card {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.95);
}

.career-tree-profile-widget .card-header {
    background: rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
}

.career-tree-profile-widget .card-body {
    color: #333;
}

.profile-tree-layout.active {
    background-color: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    color: white;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.career-recommendations {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 16px;
    backdrop-filter: blur(10px);
}

.recommendation-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    border-left: 4px solid #28a745;
    transition: all 0.2s ease;
}

.recommendation-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(4px);
}

.recommendation-item:last-child {
    margin-bottom: 0;
}

.recommendation-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.recommendation-details {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 6px;
}

.readiness-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.readiness-badge.ready {
    background: rgba(40, 167, 69, 0.3);
    border: 1px solid rgba(40, 167, 69, 0.5);
    color: #ffffff;
}

.readiness-badge.mostly-ready {
    background: rgba(255, 193, 7, 0.3);
    border: 1px solid rgba(255, 193, 7, 0.5);
    color: #ffffff;
}

.readiness-badge.partially-ready {
    background: rgba(255, 152, 0, 0.3);
    border: 1px solid rgba(255, 152, 0, 0.5);
    color: #ffffff;
}

.readiness-badge.not-ready {
    background: rgba(220, 53, 69, 0.3);
    border: 1px solid rgba(220, 53, 69, 0.5);
    color: #ffffff;
}

/* Responsive design */
@media (max-width: 768px) {
    .career-tree-profile-current {
        flex-direction: column;
        text-align: center;
    }
    
    .career-tree-profile-icon {
        margin-right: 0;
        margin-bottom: 12px;
    }
    
    .profile-career-tree-container {
        min-height: 250px;
    }
    
    #profileCareerStats .col-6 {
        margin-bottom: 15px;
    }
}

/* Loading and error states */
.profile-tree-loading, .profile-tree-error {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    flex-direction: column;
    gap: 12px;
    color: #666;
}

.profile-tree-error {
    color: #dc3545;
}

.profile-tree-loading i, .profile-tree-error i {
    font-size: 32px;
    opacity: 0.6;
}

/* Animation for recommendations */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.recommendation-item {
    animation: slideInUp 0.3s ease-out;
}

.recommendation-item:nth-child(2) {
    animation-delay: 0.1s;
}

.recommendation-item:nth-child(3) {
    animation-delay: 0.2s;
}

.recommendation-item:nth-child(4) {
    animation-delay: 0.3s;
}
</style>

<script>
$(document).ready(function() {
    let profileCareerTreeViz = null;
    let userTreeData = null;
    let userContext = null;
    let selectedLayout = 'tree';
    
    // Initialize profile career tree
    function initializeProfileCareerTree() {
        // Create visualization instance with profile-specific config
        profileCareerTreeViz = new CareerTreeVisualization('profileCareerTreeContainer', {
            width: 800,
            height: 350,
            layout: selectedLayout,
            enableTooltip: true,
            enableZoom: true,
            enablePan: true,
            nodeRadius: 8,
            fontSize: 11,
            nodeColors: {
                junior: '#52c41a',
                intermediate: '#1890ff', 
                senior: '#722ed1',
                executive: '#fa8c16',
                leadership: '#eb2f96'
            }
        });
        
        // Load user-specific tree data
        loadUserCareerTreeData();
        
        // Setup event listeners
        setupProfileEventListeners();
    }
    
    // Load user career tree data
    function loadUserCareerTreeData() {
        $.ajax({
            url: '<?= base_url() ?>position/get_user_career_tree_data',
            method: 'GET',
            dataType: 'json',
            beforeSend: function() {
                showProfileLoading();
            },
            success: function(response) {
                if (response.success) {
                    userTreeData = response.data;
                    userContext = response.user_context;
                    
                    // Load tree data
                    profileCareerTreeViz.loadData(userTreeData);
                    
                    // Update statistics and recommendations
                    updateProfileStatistics();
                    updateCareerRecommendations();
                    
                } else {
                    showProfileError('Failed to load your career path: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Profile career tree load error:', error);
                if (xhr.status === 401) {
                    showProfileError('Please log in to view your career path.');
                } else {
                    showProfileError('Failed to load career path data. Please try again.');
                }
            },
            complete: function() {
                hideProfileLoading();
            }
        });
    }
    
    // Setup profile-specific event listeners
    function setupProfileEventListeners() {
        // Layout change buttons
        $('.profile-tree-layout').on('click', function(e) {
            e.preventDefault();
            
            const newLayout = $(this).data('layout');
            if (newLayout !== selectedLayout) {
                selectedLayout = newLayout;
                
                // Update active state
                $('.profile-tree-layout').removeClass('active');
                $(this).addClass('active');
                
                // Recreate visualization with new layout
                if (profileCareerTreeViz) {
                    profileCareerTreeViz.destroy();
                }
                
                profileCareerTreeViz = new CareerTreeVisualization('profileCareerTreeContainer', {
                    width: 800,
                    height: 350,
                    layout: selectedLayout,
                    enableTooltip: true,
                    enableZoom: true,
                    enablePan: true,
                    nodeRadius: 8,
                    fontSize: 11
                });
                
                if (userTreeData) {
                    profileCareerTreeViz.loadData(userTreeData);
                }
            }
        });
        
        // Listen for node clicks to show readiness details
        document.addEventListener('careerTree:nodeClick', function(e) {
            const nodeData = e.detail.node.data;
            if (nodeData.career_readiness) {
                showReadinessModal(nodeData);
            }
        });
        
        // View quest requirements button
        $('#viewQuestRequirements').on('click', function() {
            window.location.href = '<?= base_url() ?>profile/quest_requirements';
        });
        
        // Recommendation item clicks
        $(document).on('click', '.recommendation-item', function() {
            const positionId = $(this).data('position-id');
            showReadinessDetails(positionId);
        });
    }
    
    // Update profile statistics
    function updateProfileStatistics() {
        if (!userContext) return;
        
        const availablePaths = userContext.available_paths || 0;
        const recommendations = userContext.recommendations || [];
        const readyPaths = recommendations.filter(r => r.readiness_status === 'ready' || r.readiness_status === 'mostly_ready').length;
        
        $('#availablePathsCount').text(availablePaths);
        $('#readyPathsCount').text(readyPaths);
    }
    
    // Update career recommendations
    function updateCareerRecommendations() {
        if (!userContext || !userContext.recommendations || userContext.recommendations.length === 0) {
            $('#careerRecommendations').hide();
            return;
        }
        
        const recommendations = userContext.recommendations;
        let html = '';
        
        recommendations.slice(0, 3).forEach(rec => {
            const readinessClass = rec.readiness_status.replace('_', '-');
            const readinessText = rec.readiness_percentage + '% Ready';
            
            html += `
                <div class="recommendation-item" data-position-id="${rec.target_position || ''}" style="cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="recommendation-title">${rec.target_position}</div>
                            <div class="recommendation-details">
                                ${rec.department} • ${rec.level || 'N/A'}
                                ${rec.estimated_months ? ` • ${rec.estimated_months} months` : ''}
                            </div>
                            <div class="recommendation-next-steps">
                                <small><strong>Next steps:</strong> ${rec.next_steps.slice(0, 2).join(', ')}</small>
                            </div>
                        </div>
                        <div>
                            <span class="readiness-badge ${readinessClass}">${readinessText}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#recommendationsList').html(html);
        $('#careerRecommendations').slideDown();
    }
    
    // Show career readiness modal
    function showReadinessModal(nodeData) {
        if (!nodeData.career_readiness) return;
        
        const readiness = nodeData.career_readiness;
        const requirements = nodeData.quest_requirements || [];
        const hasQuest = nodeData.has_promotion_quest || false;

        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary">Position: ${nodeData.name}</h6>
                    <p class="mb-2">${nodeData.department} • ${nodeData.level_name}</p>
                </div>
                <div class="col-md-6 text-end">
                    ${readiness.percentage > 0 ? `
                        <h3 class="text-success">${readiness.percentage}%</h3>
                        <span class="badge bg-${getReadinessBadgeColor(readiness.status)}">${getReadinessStatusText(readiness.status)}</span>
                    ` : `
                        <span class="badge bg-secondary">No Quest Available</span>
                    `}
                </div>
            </div>

            ${readiness.percentage > 0 ? `
                <div class="progress mb-4" style="height: 10px;">
                    <div class="progress-bar bg-${getReadinessBadgeColor(readiness.status)}"
                         style="width: ${readiness.percentage}%"></div>
                </div>
            ` : ''}
        `;

        if (hasQuest && requirements.length > 0) {
            html += `
                <h6 class="mb-3"><i class="bi bi-list-check"></i> Quest Requirements</h6>
                <div class="row mb-4">
            `;

            requirements.forEach(req => {
                html += `
                    <div class="col-md-4 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-${getRequirementIcon(req.type)} text-primary me-2"></i>
                            <span>${req.count} ${req.type.replace('_', ' ')}</span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
        } else if (!hasQuest) {
            html += `
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    No promotion quest available for this position yet.
                </div>
            `;
        }
        
        if (readiness.next_steps && readiness.next_steps.length > 0) {
            html += `
                <h6 class="mb-3">Next Steps</h6>
                <ul class="list-unstyled">
            `;
            
            readiness.next_steps.forEach(step => {
                html += `<li><i class="fas fa-arrow-right text-primary me-2"></i>${step}</li>`;
            });
            
            html += '</ul>';
        }
        
        $('#readinessContent').html(html);
        $('#careerReadinessModal').modal('show');
    }
    
    // Helper functions for readiness display
    function getReadinessBadgeColor(status) {
        const colors = {
            'ready': 'success',
            'mostly_ready': 'warning',
            'partially_ready': 'info',
            'not_ready': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function getReadinessStatusText(status) {
        const texts = {
            'ready': 'Ready to Apply',
            'mostly_ready': 'Almost Ready',
            'partially_ready': 'In Progress',
            'not_ready': 'Not Ready'
        };
        return texts[status] || 'Unknown';
    }
    
    function getRequirementIcon(type) {
        const icons = {
            'main_quest': 'trophy',
            'side_quest': 'tasks',
            'points': 'star'
        };
        return icons[type] || 'check';
    }
    
    // Show loading state
    function showProfileLoading() {
        $('#profileCareerTreeContainer').html(`
            <div class="profile-tree-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div>Loading your career path...</div>
            </div>
        `);
    }
    
    // Hide loading state
    function hideProfileLoading() {
        // Loading will be hidden when tree renders
    }
    
    // Show error state
    function showProfileError(message) {
        $('#profileCareerTreeContainer').html(`
            <div class="profile-tree-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>${message}</div>
                <button class="btn btn-outline-danger btn-sm mt-2" onclick="loadUserCareerTreeData()">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        `);
    }
    
    // Initialize on page load
    initializeProfileCareerTree();
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (profileCareerTreeViz) {
            profileCareerTreeViz.destroy();
        }
    });
    
    // Make loadUserCareerTreeData globally available for error retry
    window.loadUserCareerTreeData = loadUserCareerTreeData;
});
</script>
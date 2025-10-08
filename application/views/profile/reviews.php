<div class="container-fluid py-3">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h3 class="text-primary fw-500">COMMUNITY REVIEWS</h3>
            <p class="text-muted mb-0">Explore approved book and film reviews from the community</p>
        </div>
        <div class="col-lg-4 text-end">
            <a href="<?= base_url() ?>profile" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="card mb-4 card-hover-effect">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">
                    <i class="bi bi-collection me-2"></i>Community Reviews
                </h5>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (!empty($all_reviews)): ?>
                        <span class="badge bg-primary"><?= count($all_reviews) ?> Reviews</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Explore approved book and film reviews from the community. Only approved submissions are displayed.
            </p>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="search-reviews" 
                               placeholder="Search by title, reviewer, or content..." 
                               style="border-left: none; box-shadow: none;">
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <select class="form-select" id="filter-type">
                        <option value="all">All Reviews</option>
                        <option value="film">Films Only</option>
                        <option value="book">Books Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sort-reviews">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title">Title A-Z</option>
                        <option value="reviewer">Reviewer A-Z</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Grid -->
    <div class="reviews-container">
        <?php if (!empty($all_reviews)): ?>
            <div class="row" id="reviews-grid">
                <?php foreach ($all_reviews as $review): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4 review-item" 
                         data-title="<?= strtolower(htmlspecialchars($review['submission_title'])) ?>"
                         data-reviewer="<?= strtolower(htmlspecialchars($review['username'] ?? $review['full_name'] ?? 'Unknown')) ?>"
                         data-type="<?= strpos(strtolower($review['quest_title']), 'film') !== false ? 'film' : 'book' ?>"
                         data-date="<?= strtotime($review['approved_at'] ?? $review['submitted_at']) ?>">
                        <div class="review-card" data-submission-id="<?= $review['id'] ?>" style="cursor: pointer;">
                            <div class="card h-100 review-item-card">
                                <div class="card-img-wrapper">
                                    <?php
                                    $image_url = 'https://placehold.co/400x200/f0f0f0/666666?text=No+Image';
                                    
                                    if (!empty($review['submission_image'])) {
                                        $upload_path = FCPATH . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                        if (file_exists($upload_path)) {
                                            $image_url = base_url() . 'assets/uploads/side_quest_user_images/' . $review['submission_image'];
                                        }
                                    }
                                    ?>
                                    <img src="<?= $image_url ?>" class="card-img-top" alt="<?= htmlspecialchars($review['submission_title']) ?>"
                                        onerror="this.src='https://placehold.co/400x200/f0f0f0/666666?text=No+Image'">
                                    <div class="card-img-overlay-bottom">
                                        <h6 class="text-white mb-1"><?= htmlspecialchars($review['submission_title']) ?></h6>
                                        <small class="text-white-50"><?= htmlspecialchars($review['quest_title']) ?></small>
                                    </div>
                                    <div class="card-status-badge">
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($review['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                $status_text = 'Pending';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                $status_text = 'Approved';
                                                break;
                                            case 'denied':
                                                $status_class = 'bg-danger';
                                                $status_text = 'Denied';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <!-- Review Title -->
                                    <h6 class="card-title mb-3 text-dark fw-medium">
                                        <?= htmlspecialchars($review['submission_title']) ?>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="reviewer-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-person-circle text-primary me-2"></i>
                                                <small class="text-primary fw-medium">
                                                    <?= htmlspecialchars($review['full_name'] ?? $review['username'] ?? 'Unknown User') ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($review['approved_at'] ?? $review['submitted_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="view-icon">
                                            <i class="bi bi-eye text-primary" title="View Review Details"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-collection" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h4 class="text-muted mb-3">No Approved Reviews Yet</h4>
                    <p class="text-muted mb-4">
                        Be the first to share your book and film reviews with the community!<br>
                        Complete film or book quests to add your reviews here.
                    </p>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <span class="badge bg-primary py-2 px-3">
                            <i class="bi bi-camera-reels me-1"></i>🎬 Film Reviews
                        </span>
                        <span class="badge bg-success py-2 px-3">
                            <i class="bi bi-book me-1"></i>📚 Book Reviews
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- No Results Message (Hidden by default) -->
    <div class="text-center py-5 d-none" id="no-results">
        <i class="bi bi-search" style="font-size: 3rem; color: #ccc;"></i>
        <h5 class="text-muted mt-3">No Reviews Found</h5>
        <p class="text-muted">Try adjusting your search terms or filters.</p>
    </div>
</div>

<!-- Enhanced Review Detail Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="bi bi-collection me-2"></i>Community Review
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading content will be inserted here by JavaScript -->
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading review details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const originalReviews = $('#reviews-grid .review-item').clone();
    
    // Search functionality
    $('#search-reviews').on('input', function() {
        filterReviews();
    });
    
    // Filter and sort functionality
    $('#filter-type, #sort-reviews').on('change', function() {
        filterReviews();
    });
    
    function filterReviews() {
        const searchTerm = $('#search-reviews').val().toLowerCase();
        const filterType = $('#filter-type').val();
        const sortBy = $('#sort-reviews').val();
        
        let visibleItems = originalReviews.filter(function() {
            const $item = $(this);
            const title = $item.data('title');
            const reviewer = $item.data('reviewer');
            const type = $item.data('type');
            
            // Search filter
            const matchesSearch = searchTerm === '' || 
                                title.includes(searchTerm) || 
                                reviewer.includes(searchTerm);
            
            // Type filter
            const matchesType = filterType === 'all' || type === filterType;
            
            return matchesSearch && matchesType;
        });
        
        // Sort items
        visibleItems.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);
            
            switch(sortBy) {
                case 'newest':
                    return $b.data('date') - $a.data('date');
                case 'oldest':
                    return $a.data('date') - $b.data('date');
                case 'title':
                    return $a.data('title').localeCompare($b.data('title'));
                case 'reviewer':
                    return $a.data('reviewer').localeCompare($b.data('reviewer'));
                default:
                    return $b.data('date') - $a.data('date');
            }
        });
        
        // Update display
        $('#reviews-grid').empty().append(visibleItems);
        
        // Show/hide no results message
        if (visibleItems.length === 0) {
            $('#no-results').removeClass('d-none');
            $('.reviews-container .row').addClass('d-none');
        } else {
            $('#no-results').addClass('d-none');
            $('.reviews-container .row').removeClass('d-none');
        }
    }
    
    // Review card click handler
    $(document).on('click', '.review-card', function(e) {
        const submissionId = $(this).data('submission-id');
        
        // Show loading state
        $('#reviewModal .modal-body').html(`
            <div class="text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading review details...</p>
            </div>
        `);
        $('#reviewModal').modal('show');
        
        // AJAX call to get full review details
        $.ajax({
            url: '<?= base_url() ?>profile/get_community_review_detail',
            method: 'POST',
            data: { submission_id: submissionId },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.error) {
                        $('#reviewModal .modal-body').html(`
                            <div class="text-center py-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                <h6 class="text-muted mt-3">Error loading review</h6>
                                <p class="text-muted">${data.error}</p>
                            </div>
                        `);
                        return;
                    }
                    
                    populateReviewModal(data);
                    
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showModalError('Error parsing review data');
                }
            },
            error: function() {
                showModalError('Connection error. Please try again.');
            }
        });
    });
    
    function populateReviewModal(data) {
        const modalHTML = `
            <!-- Image Section -->
            <div class="review-image-container text-center mb-4">
                <img id="review-modal-image" 
                     src="${data.submission_image ? '<?= base_url() ?>assets/uploads/side_quest_user_images/' + data.submission_image : 'https://placehold.co/800x400/f0f0f0/666666?text=No+Image'}"
                     class="img-fluid rounded" 
                     style="max-height: 400px; object-fit: cover; width: 100%;"
                     onerror="this.src='https://placehold.co/800x400/f0f0f0/666666?text=No+Image'"
                     alt="Review Image">
            </div>
            
            <!-- Review Content -->
            <div class="review-content">
                <h4 class="mb-2">${data.submission_title || 'No Title'}</h4>
                <p class="text-muted mb-3">
                    <i class="bi bi-tag me-1"></i>${data.quest_title || ''}
                </p>
                
                <!-- Reviewer and Meta Info -->
                <div class="reviewer-meta-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-circle text-primary me-2" style="font-size: 1.25rem;"></i>
                                <div>
                                    <strong class="text-primary">${data.full_name || data.username || 'Unknown Reviewer'}</strong>
                                    ${data.username && data.full_name ? `<br><small class="text-muted">@${data.username}</small>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-success mb-2">
                                <i class="bi bi-check-circle me-1"></i>Approved
                            </span>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                ${data.approved_at_formatted || data.submitted_at_formatted || ''}
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Review Content -->
                <div class="review-text-content">
                    <h6 class="mb-3">
                        <i class="bi bi-chat-text me-2"></i>Review Content:
                    </h6>
                    <div class="review-text p-3 bg-light rounded" 
                         style="max-height: 400px; overflow-y: auto; line-height: 1.6;">
                        ${data.hasil_html && data.hasil_html.trim() !== '' ? data.hasil_html : (data.hasil || 'No content').replace(/\n/g, '<br>')}
                    </div>
                </div>
                
                ${data.hr_notes && data.hr_notes.trim() !== '' ? `
                <div class="mt-4">
                    <h6 class="mb-2">
                        <i class="bi bi-person-badge me-2"></i>Admin Notes:
                    </h6>
                    <div class="alert alert-info">${data.hr_notes}</div>
                </div>
                ` : ''}
            </div>
        `;
        
        $('#reviewModal .modal-body').html(modalHTML);
    }
    
    function showModalError(message) {
        $('#reviewModal .modal-body').html(`
            <div class="text-center py-5">
                <i class="bi bi-wifi-off text-danger" style="font-size: 3rem;"></i>
                <h6 class="text-muted mt-3">Error</h6>
                <p class="text-muted">${message}</p>
            </div>
        `);
    }
});
</script>

<!-- Custom CSS for Reviews Page -->
<style>
.reviewer-info .text-primary {
    font-weight: 600;
}

.reviewer-meta-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #1890ff;
}

.review-item-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.review-item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #1890ff;
}

.card-img-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.review-item-card:hover .card-img-wrapper img {
    transform: scale(1.05);
}

.empty-state {
    max-width: 500px;
    margin: 0 auto;
}

.empty-state .badge {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .card-img-wrapper {
        height: 180px;
    }
    
    .reviewer-info {
        text-align: center;
    }
    
    .view-icon {
        text-align: center;
        margin-top: 0.5rem;
    }
}
</style>
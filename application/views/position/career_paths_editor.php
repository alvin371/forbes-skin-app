<!-- Career Paths Editor Component -->
<style>
    .career-paths-editor {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
    }

    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .editor-title {
        color: #1890ff;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .add-path-btn {
        background: linear-gradient(135deg, #1890ff, #096dd9);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .add-path-btn:hover {
        background: linear-gradient(135deg, #096dd9, #0050b3);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
    }

    .paths-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 20px;
    }

    .career-path-item {
        background: white;
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        padding: 20px;
        position: relative;
        transition: all 0.3s ease;
    }

    .career-path-item:hover {
        border-color: #1890ff;
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.1);
    }

    .path-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }

    .path-number {
        color: #1890ff;
        font-size: 16px;
        font-weight: 600;
    }

    .remove-path-btn {
        background: #ff4d4f;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .remove-path-btn:hover {
        background: #cf1322;
        transform: scale(1.05);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-label {
        color: #262626;
        font-size: 14px;
        font-weight: 500;
    }

    .required-mark {
        color: #ff4d4f;
    }

    .form-control, .form-select {
        padding: 8px 12px;
        border: 1px solid #d9d9d9;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #1890ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
        outline: none;
    }

    .requirements-section {
        background: #fafafa;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
        padding: 16px;
        margin-top: 16px;
    }

    .requirements-title {
        color: #262626;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .help-text {
        color: #8c8c8c;
        font-size: 12px;
        margin-top: 4px;
    }

    .path-type-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 4px;
    }

    .path-type-vertical_technical {
        background: #e6f7ff;
        color: #0050b3;
    }

    .path-type-lateral_management {
        background: #f0f5ff;
        color: #1d39c4;
    }

    .path-type-diagonal_hybrid {
        background: #fff7e6;
        color: #ad6800;
    }

    .json-preview-section {
        background: #262626;
        color: #fafafa;
        border-radius: 8px;
        padding: 16px;
        margin-top: 20px;
        max-height: 400px;
        overflow-y: auto;
    }

    .json-preview-header {
        color: #1890ff;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .json-preview-code {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #8c8c8c;
    }

    .empty-state i {
        font-size: 48px;
        color: #d9d9d9;
        margin-bottom: 16px;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    .validation-error {
        color: #ff4d4f;
        font-size: 13px;
        margin-top: 4px;
        display: none;
    }

    .path-type-description {
        margin-top: 8px;
        padding: 8px 12px;
        background: #f0f5ff;
        border-left: 3px solid #1890ff;
        border-radius: 4px;
        font-size: 13px;
        color: #595959;
    }
</style>

<div class="career-paths-editor">
    <div class="editor-header">
        <h5 class="editor-title">
            <i class="bi bi-signpost-split"></i> Career Advancement Paths
        </h5>
        <button type="button" class="add-path-btn" id="addPathBtn">
            <i class="bi bi-plus-circle me-1"></i> Add Path
        </button>
    </div>

    <div class="paths-container" id="pathsContainer">
        <!-- Paths will be dynamically added here -->
        <div class="empty-state" id="emptyState">
            <i class="bi bi-diagram-3"></i>
            <p>No career paths defined yet. Click "Add Path" to create advancement opportunities for this position.</p>
        </div>
    </div>

    <!-- JSON Preview (Optional - can be toggled) -->
    <div class="json-preview-section" id="jsonPreview" style="display: none;">
        <div class="json-preview-header">
            <i class="bi bi-code-square"></i> JSON Preview
        </div>
        <div class="json-preview-code" id="jsonPreviewCode">
            <!-- JSON will be displayed here -->
        </div>
    </div>
</div>

<!-- Path Template (Hidden) -->
<template id="pathTemplate">
    <div class="career-path-item" data-path-index="">
        <div class="path-header">
            <div class="path-number">
                <i class="bi bi-arrow-up-right-circle"></i> Career Path #<span class="path-index-display"></span>
            </div>
            <button type="button" class="remove-path-btn">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>

        <!-- Position Selection -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">
                    Target Position <span class="required-mark">*</span>
                </label>
                <select class="form-select position-select" name="" required>
                    <option value="">Select target position...</option>
                    <?php if (isset($available_positions)): ?>
                        <?php foreach ($available_positions as $pos): ?>
                            <option value="<?= $pos['id'] ?>" data-name="<?= htmlspecialchars($pos['name']) ?>" data-dept="<?= htmlspecialchars($pos['department'] ?? '') ?>">
                                <?= $pos['name'] ?>
                                <?php if (!empty($pos['department'])): ?>
                                    (<?= $pos['department'] ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <input type="hidden" class="position-name-hidden" name="">
                <div class="validation-error">Please select a target position</div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Path Type <span class="required-mark">*</span>
                </label>
                <select class="form-select path-type-select" name="" required>
                    <option value="">Select path type...</option>
                    <option value="vertical_technical">Vertical (Technical Advancement)</option>
                    <option value="lateral_management">Lateral (Management Track)</option>
                    <option value="diagonal_hybrid">Diagonal (Cross-functional)</option>
                </select>
                <div class="path-type-description" style="display: none;"></div>
            </div>
        </div>
    </div>
</template>

<script>
// Wrap in document ready to ensure DOM is loaded
$(document).ready(function() {
    let pathCounter = 0;
    const pathsContainer = document.getElementById('pathsContainer');
    const emptyState = document.getElementById('emptyState');
    const addPathBtn = document.getElementById('addPathBtn');
    const pathTemplate = document.getElementById('pathTemplate');
    const jsonPreview = document.getElementById('jsonPreview');
    const jsonPreviewCode = document.getElementById('jsonPreviewCode');

    // Check if elements exist
    if (!pathsContainer || !addPathBtn || !pathTemplate) {
        console.error('Career paths editor elements not found');
        return;
    }

    // Path type descriptions
    const pathTypeDescriptions = {
        'vertical_technical': 'Advancement within the same department with increased technical responsibilities',
        'lateral_management': 'Movement to management role, possibly in different department',
        'diagonal_hybrid': 'Cross-functional advancement combining technical and management aspects'
    };

    // Initialize with existing paths if editing
    <?php if (isset($existing_career_paths) && !empty($existing_career_paths)): ?>
    const existingPaths = <?= json_encode($existing_career_paths) ?>;
    existingPaths.forEach(path => {
        addPath(path);
    });
    <?php endif; ?>

    // Add path button handler - using both jQuery and vanilla JS for compatibility
    $('#addPathBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Add Path button clicked');
        addPath();
    });

    function addPath(existingData = null) {
        console.log('Adding new career path...');
        pathCounter++;

        // Clone template
        const pathItem = pathTemplate.content.cloneNode(true);

        // Get the actual div element from the DocumentFragment
        const pathDiv = pathItem.querySelector('.career-path-item');

        if (!pathDiv) {
            console.error('Could not find .career-path-item in template');
            return;
        }

        // Set data index
        pathDiv.setAttribute('data-path-index', pathCounter);

        const pathIndexDisplay = pathDiv.querySelector('.path-index-display');
        if (pathIndexDisplay) {
            pathIndexDisplay.textContent = pathCounter;
        }

        // Set field names with counter - simplified to only position and type
        const positionSelect = pathDiv.querySelector('.position-select');
        const positionNameHidden = pathDiv.querySelector('.position-name-hidden');
        const pathTypeSelect = pathDiv.querySelector('.path-type-select');

        console.log('Form elements found:', {
            positionSelect: !!positionSelect,
            positionNameHidden: !!positionNameHidden,
            pathTypeSelect: !!pathTypeSelect
        });

        // Set names with safety checks
        if (positionSelect) positionSelect.name = `career_paths[${pathCounter}][position_id]`;
        if (positionNameHidden) positionNameHidden.name = `career_paths[${pathCounter}][position_name]`;
        if (pathTypeSelect) pathTypeSelect.name = `career_paths[${pathCounter}][path_type]`;

        // Populate with existing data if provided
        if (existingData) {
            if (positionSelect) positionSelect.value = existingData.position_id || '';
            if (positionNameHidden) positionNameHidden.value = existingData.position_name || '';
            if (pathTypeSelect) pathTypeSelect.value = existingData.path_type || '';

            // Trigger path type description if value exists
            if (existingData.path_type) {
                setTimeout(() => {
                    const desc = pathDiv.querySelector('.path-type-description');
                    if (desc) {
                        desc.textContent = pathTypeDescriptions[existingData.path_type] || '';
                        desc.style.display = 'block';
                    }
                }, 0);
            }
        }

        // Position select change handler
        if (positionSelect) {
            positionSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value && positionNameHidden) {
                    positionNameHidden.value = selectedOption.getAttribute('data-name');
                } else if (positionNameHidden) {
                    positionNameHidden.value = '';
                }
                updateJsonPreview();
            });
        }

        // Path type change handler
        if (pathTypeSelect) {
            pathTypeSelect.addEventListener('change', function() {
                const description = pathDiv.querySelector('.path-type-description');
                if (description) {
                    if (this.value && pathTypeDescriptions[this.value]) {
                        description.textContent = pathTypeDescriptions[this.value];
                        description.style.display = 'block';
                    } else {
                        description.style.display = 'none';
                    }
                }
                updateJsonPreview();
            });
        }

        // Update JSON preview on any input change
        pathDiv.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', updateJsonPreview);
        });

        // Remove button handler
        pathDiv.querySelector('.remove-path-btn').addEventListener('click', function() {
            pathDiv.remove();
            checkEmptyState();
            updateJsonPreview();
        });

        // Add to container
        pathsContainer.appendChild(pathDiv);
        checkEmptyState();
        updateJsonPreview();
    }

    function checkEmptyState() {
        const paths = pathsContainer.querySelectorAll('.career-path-item');
        if (paths.length === 0) {
            emptyState.style.display = 'block';
            jsonPreview.style.display = 'none';
        } else {
            emptyState.style.display = 'none';
            jsonPreview.style.display = 'block';
        }
    }

    function updateJsonPreview() {
        const paths = [];
        pathsContainer.querySelectorAll('.career-path-item').forEach(pathDiv => {
            const positionSelect = pathDiv.querySelector('.position-select');
            const positionNameHidden = pathDiv.querySelector('.position-name-hidden');
            const pathTypeSelect = pathDiv.querySelector('.path-type-select');

            const positionId = positionSelect ? positionSelect.value : '';
            const positionName = positionNameHidden ? positionNameHidden.value : '';
            const pathType = pathTypeSelect ? pathTypeSelect.value : '';

            if (positionId && positionName) {
                const pathData = {
                    position_id: parseInt(positionId),
                    position_name: positionName,
                    path_type: pathType || 'vertical_technical'
                };
                paths.push(pathData);
            }
        });

        const jsonData = paths.length > 0 ? { next_roles: paths } : null;
        if (jsonPreviewCode) {
            jsonPreviewCode.textContent = JSON.stringify(jsonData, null, 2);
        }
    }

    // Initial state check
    checkEmptyState();
});
</script>

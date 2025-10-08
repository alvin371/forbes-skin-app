/**
 * Node Arrangement Editor
 * Provides interactive editing capabilities for dendrogram node positioning
 * Allows users to customize organizational hierarchy through drag-and-drop and sortable lists
 */

class ArrangementEditor {
    constructor(dendrogramInstance, options = {}) {
        this.dendrogram = dendrogramInstance;
        this.isEditMode = false;
        this.currentArrangement = null;
        this.originalData = null;
        this.hasUnsavedChanges = false;

        // Configuration
        this.config = {
            enableDragDrop: options.enableDragDrop !== false,
            enableSortableLists: options.enableSortableLists !== false,
            autoSave: options.autoSave || false,
            userId: options.userId || null,
            departmentLocked: options.departmentLocked || false,
            ...options
        };

        // Internal state
        this.draggedNode = null;
        this.dropZones = [];
        this.validationRules = new Map();
        this.undoStack = [];
        this.redoStack = [];

        this.init();
    }

    init() {
        this.createArrangementInterface();
        this.setupEventListeners();
        this.loadValidationRules();
        console.log('ArrangementEditor initialized');
    }

    /**
     * Create the arrangement editing interface
     */
    createArrangementInterface() {
        // Create arrangement editor panel
        const editorPanel = `
            <div id="arrangementEditor" class="arrangement-editor" style="display: none;">
                <div class="arrangement-header">
                    <h5>
                        <i class="fas fa-edit"></i>
                        Node Arrangement Editor
                    </h5>
                    <div class="arrangement-actions">
                        <button class="btn btn-sm btn-outline-secondary" id="undoArrangement" disabled>
                            <i class="fas fa-undo"></i> Undo
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="redoArrangement" disabled>
                            <i class="fas fa-redo"></i> Redo
                        </button>
                        <button class="btn btn-sm btn-outline-info" id="previewArrangement">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button class="btn btn-sm btn-success" id="saveArrangement">
                            <i class="fas fa-save"></i> Save
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="cancelArrangement">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="arrangement-content">
                    <div class="arrangement-tabs">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="drag-drop-tab" data-bs-toggle="tab" href="#dragDropPanel">
                                    <i class="fas fa-mouse"></i> Drag & Drop
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="list-edit-tab" data-bs-toggle="tab" href="#listEditPanel">
                                    <i class="fas fa-list"></i> List Editor
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="templates-tab" data-bs-toggle="tab" href="#templatesPanel">
                                    <i class="fas fa-layer-group"></i> Templates
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <!-- Drag & Drop Panel -->
                        <div class="tab-pane fade show active" id="dragDropPanel">
                            <div class="drag-drop-controls">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enableAdvancedDrag">
                                    <label class="form-check-label" for="enableAdvancedDrag">
                                        Enhanced Drag Mode
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="showDropZones">
                                    <label class="form-check-label" for="showDropZones">
                                        Show Drop Zones
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="snapToGrid">
                                    <label class="form-check-label" for="snapToGrid">
                                        Snap to Grid
                                    </label>
                                </div>
                            </div>
                            <div class="drag-instructions">
                                <p><i class="fas fa-info-circle"></i>
                                Drag nodes to reorder them within departments. Hold Shift while dragging to move between departments.</p>
                            </div>
                        </div>

                        <!-- List Editor Panel -->
                        <div class="tab-pane fade" id="listEditPanel">
                            <div class="list-editor-container">
                                <div id="departmentLists">
                                    <!-- Sortable lists will be populated here -->
                                </div>
                            </div>
                        </div>

                        <!-- Templates Panel -->
                        <div class="tab-pane fade" id="templatesPanel">
                            <div class="templates-container">
                                <div class="template-options">
                                    <button class="btn btn-outline-primary template-btn" data-template="alphabetical">
                                        <i class="fas fa-sort-alpha-down"></i> Alphabetical
                                    </button>
                                    <button class="btn btn-outline-primary template-btn" data-template="seniority">
                                        <i class="fas fa-star"></i> By Seniority
                                    </button>
                                    <button class="btn btn-outline-primary template-btn" data-template="department-size">
                                        <i class="fas fa-users"></i> By Department Size
                                    </button>
                                    <button class="btn btn-outline-primary template-btn" data-template="custom">
                                        <i class="fas fa-cog"></i> Custom Order
                                    </button>
                                </div>
                                <div class="template-preview">
                                    <h6>Template Preview</h6>
                                    <div id="templatePreview">
                                        Select a template to see preview
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert the editor panel
        $(this.dendrogram.container.node()).append(editorPanel);

        // Create edit mode toggle button
        const toggleButton = `
            <button class="btn btn-primary" id="toggleEditMode">
                <i class="fas fa-edit"></i> Edit Arrangement
            </button>
        `;

        // Add to export controls
        $('.export-controls').prepend(toggleButton);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Edit mode toggle
        $(document).on('click', '#toggleEditMode', () => {
            this.toggleEditMode();
        });

        // Arrangement actions
        $(document).on('click', '#saveArrangement', () => {
            this.saveArrangement();
        });

        $(document).on('click', '#cancelArrangement', () => {
            this.cancelEditing();
        });

        $(document).on('click', '#undoArrangement', () => {
            this.undo();
        });

        $(document).on('click', '#redoArrangement', () => {
            this.redo();
        });

        $(document).on('click', '#previewArrangement', () => {
            this.previewArrangement();
        });

        // Template buttons
        $(document).on('click', '.template-btn', (e) => {
            const template = $(e.currentTarget).data('template');
            this.applyTemplate(template);
        });

        // Drag controls
        $(document).on('change', '#enableAdvancedDrag', (e) => {
            this.toggleAdvancedDrag(e.target.checked);
        });

        $(document).on('change', '#showDropZones', (e) => {
            this.toggleDropZones(e.target.checked);
        });

        $(document).on('change', '#snapToGrid', (e) => {
            this.toggleSnapToGrid(e.target.checked);
        });

        // Tab switching
        $(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', (e) => {
            const target = $(e.target).attr('href');
            if (target === '#listEditPanel') {
                this.populateSortableLists();
            }
        });

        // Window beforeunload to warn about unsaved changes
        $(window).on('beforeunload', () => {
            if (this.hasUnsavedChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    }

    /**
     * Toggle edit mode
     */
    toggleEditMode() {
        if (this.isEditMode) {
            this.exitEditMode();
        } else {
            this.enterEditMode();
        }
    }

    /**
     * Enter edit mode
     */
    enterEditMode() {
        if (!this.dendrogram || !this.dendrogram.root) {
            alert('Please load the dendrogram first');
            return;
        }

        console.log('Entering edit mode');
        this.isEditMode = true;
        this.hasUnsavedChanges = false;

        // Store original data
        this.originalData = JSON.parse(JSON.stringify(this.dendrogram.data || {}));

        // Update UI
        $('#toggleEditMode').html('<i class="fas fa-eye"></i> View Mode');
        $('#arrangementEditor').slideDown();

        // Enable enhanced drag if configured
        if (this.config.enableDragDrop) {
            this.enableAdvancedDragMode();
        }

        // Add visual indicators
        this.addEditModeIndicators();

        // Initialize undo/redo
        this.undoStack = [];
        this.redoStack = [];
        this.updateUndoRedoButtons();

        // Save initial state
        this.saveState('Initial state');
    }

    /**
     * Exit edit mode
     */
    exitEditMode() {
        if (this.hasUnsavedChanges) {
            if (!confirm('You have unsaved changes. Do you want to discard them?')) {
                return;
            }
            this.restoreOriginalData();
        }

        console.log('Exiting edit mode');
        this.isEditMode = false;
        this.hasUnsavedChanges = false;

        // Update UI
        $('#toggleEditMode').html('<i class="fas fa-edit"></i> Edit Arrangement');
        $('#arrangementEditor').slideUp();

        // Disable enhanced drag
        this.disableAdvancedDragMode();

        // Remove visual indicators
        this.removeEditModeIndicators();

        // Clear state
        this.undoStack = [];
        this.redoStack = [];
    }

    /**
     * Enable advanced drag mode
     */
    enableAdvancedDragMode() {
        if (!this.dendrogram || !this.dendrogram.g) return;

        // Update dendrogram config
        this.dendrogram.config.enableDrag = true;

        // Add enhanced drag behavior
        this.dendrogram.g.selectAll('.node')
            .call(d3.drag()
                .on('start', (event, d) => this.onDragStart(event, d))
                .on('drag', (event, d) => this.onDrag(event, d))
                .on('end', (event, d) => this.onDragEnd(event, d))
            );

        console.log('Advanced drag mode enabled');
    }

    /**
     * Disable advanced drag mode
     */
    disableAdvancedDragMode() {
        if (!this.dendrogram || !this.dendrogram.g) return;

        // Remove drag behavior
        this.dendrogram.g.selectAll('.node')
            .on('.drag', null);

        // Update dendrogram config
        this.dendrogram.config.enableDrag = false;

        console.log('Advanced drag mode disabled');
    }

    /**
     * Handle drag start
     */
    onDragStart(event, d) {
        if (!d.data.isPosition) return; // Only allow dragging positions

        this.draggedNode = d;

        // Add dragging class
        d3.select(event.sourceEvent.target.closest('.node'))
            .classed('dragging', true);

        // Show drop zones if enabled
        if ($('#showDropZones').is(':checked')) {
            this.showDropZones(d);
        }

        // Create ghost/preview element
        this.createDragPreview(event, d);

        console.log('Drag start:', d.data.name);
    }

    /**
     * Handle drag
     */
    onDrag(event, d) {
        if (!this.draggedNode) return;

        // Update position
        d.x = event.x;
        d.y = event.y;

        // Update transform
        d3.select(event.sourceEvent.target.closest('.node'))
            .attr('transform', `translate(${d.x},${d.y})`);

        // Update drag preview
        this.updateDragPreview(event);

        // Highlight valid drop zones
        this.highlightDropZones(event, d);
    }

    /**
     * Handle drag end
     */
    onDragEnd(event, d) {
        if (!this.draggedNode) return;

        // Remove dragging class
        d3.select(event.sourceEvent.target.closest('.node'))
            .classed('dragging', false);

        // Hide drop zones
        this.hideDropZones();

        // Remove drag preview
        this.removeDragPreview();

        // Check for valid drop
        const dropTarget = this.getDropTarget(event);
        if (dropTarget) {
            this.performDrop(this.draggedNode, dropTarget);
        } else {
            // Snap back to original position if no valid drop
            this.snapBackToPosition(d);
        }

        this.draggedNode = null;
        console.log('Drag end');
    }

    /**
     * Create drag preview element
     */
    createDragPreview(event, d) {
        const preview = d3.select('body')
            .append('div')
            .attr('class', 'drag-preview')
            .style('position', 'absolute')
            .style('background', 'rgba(24, 144, 255, 0.9)')
            .style('color', 'white')
            .style('padding', '8px 12px')
            .style('border-radius', '4px')
            .style('pointer-events', 'none')
            .style('z-index', '9999')
            .style('font-size', '12px')
            .text(d.data.name);

        this.updateDragPreview(event);
    }

    /**
     * Update drag preview position
     */
    updateDragPreview(event) {
        d3.select('.drag-preview')
            .style('left', (event.sourceEvent.pageX + 10) + 'px')
            .style('top', (event.sourceEvent.pageY - 10) + 'px');
    }

    /**
     * Remove drag preview
     */
    removeDragPreview() {
        d3.select('.drag-preview').remove();
    }

    /**
     * Show drop zones
     */
    showDropZones(draggedNode) {
        // Implementation depends on specific requirements
        // This would highlight valid drop areas
        console.log('Showing drop zones for:', draggedNode.data.name);
    }

    /**
     * Hide drop zones
     */
    hideDropZones() {
        d3.selectAll('.drop-zone').remove();
        d3.selectAll('.drop-highlight').classed('drop-highlight', false);
    }

    /**
     * Populate sortable lists
     */
    populateSortableLists() {
        if (!this.dendrogram || !this.dendrogram.data) return;

        const container = $('#departmentLists');
        container.empty();

        // Get department data
        const departments = this.getDepartmentData();

        departments.forEach(dept => {
            const listHtml = `
                <div class="department-list-container">
                    <h6 class="department-title">
                        <i class="fas fa-folder"></i> ${dept.name}
                        <span class="badge badge-secondary">${dept.positions.length}</span>
                    </h6>
                    <ul class="sortable-list" data-department="${dept.name}">
                        ${dept.positions.map(pos => `
                            <li class="sortable-item" data-position-id="${pos.id}" data-order="${pos.position_order}">
                                <div class="position-info">
                                    <div class="position-name">${pos.name}</div>
                                    <div class="position-details">
                                        Order: <input type="number" class="order-input" value="${pos.position_order}" min="0" max="9999">
                                        | Level: ${pos.level_name}
                                    </div>
                                </div>
                                <div class="sortable-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
            container.append(listHtml);
        });

        // Initialize sortable
        this.initializeSortable();
    }

    /**
     * Initialize sortable lists
     */
    initializeSortable() {
        $('.sortable-list').sortable({
            handle: '.sortable-handle',
            placeholder: 'sortable-placeholder',
            tolerance: 'pointer',
            update: (event, ui) => {
                this.onSortableUpdate(event, ui);
            },
            start: (event, ui) => {
                ui.placeholder.height(ui.item.height());
            }
        });

        // Handle direct order input changes
        $(document).on('input', '.order-input', (e) => {
            this.onOrderInputChange(e);
        });
    }

    /**
     * Handle sortable list updates
     */
    onSortableUpdate(event, ui) {
        const department = $(event.target).data('department');
        const positions = [];

        $(event.target).find('.sortable-item').each((index, item) => {
            const positionId = $(item).data('position-id');
            const newOrder = $(item).data('order');
            positions.push({
                id: positionId,
                order: newOrder,
                index: index
            });
        });

        this.updatePositionOrders(department, positions);
        this.markAsChanged();
    }

    /**
     * Handle direct order input changes
     */
    onOrderInputChange(e) {
        const input = $(e.target);
        const newOrder = parseInt(input.val());
        const item = input.closest('.sortable-item');
        const positionId = item.data('position-id');

        if (newOrder >= 0 && newOrder <= 9999) {
            item.data('order', newOrder);
            this.updateSinglePositionOrder(positionId, newOrder);
            this.markAsChanged();
        }
    }

    /**
     * Apply arrangement template
     */
    applyTemplate(templateName) {
        console.log('Applying template:', templateName);

        if (!confirm(`Apply ${templateName} template? This will rearrange all nodes.`)) {
            return;
        }

        this.saveState(`Before applying ${templateName} template`);

        switch (templateName) {
            case 'alphabetical':
                this.applyAlphabeticalTemplate();
                break;
            case 'seniority':
                this.applySeniorityTemplate();
                break;
            case 'department-size':
                this.applyDepartmentSizeTemplate();
                break;
            case 'custom':
                this.showCustomOrderDialog();
                break;
        }

        this.markAsChanged();
        this.refreshDendrogram();
    }

    /**
     * Apply alphabetical template
     */
    applyAlphabeticalTemplate() {
        const departments = this.getDepartmentData();

        departments.forEach(dept => {
            dept.positions.sort((a, b) => a.name.localeCompare(b.name));
            dept.positions.forEach((pos, index) => {
                pos.position_order = (dept.baseOrder || 100) + index;
            });
        });

        this.updateDendrogramData(departments);
    }

    /**
     * Apply seniority template
     */
    applySeniorityTemplate() {
        const departments = this.getDepartmentData();

        departments.forEach(dept => {
            dept.positions.sort((a, b) => {
                // Sort by level_order first, then by position_order
                const levelDiff = (b.level_order || 0) - (a.level_order || 0);
                if (levelDiff !== 0) return levelDiff;
                return (b.position_order || 0) - (a.position_order || 0);
            });

            dept.positions.forEach((pos, index) => {
                pos.position_order = (dept.baseOrder || 100) + (dept.positions.length - index - 1);
            });
        });

        this.updateDendrogramData(departments);
    }

    /**
     * Save arrangement
     */
    saveArrangement() {
        if (!this.hasUnsavedChanges) {
            alert('No changes to save');
            return;
        }

        console.log('Saving arrangement...');

        const arrangementData = this.getCurrentArrangementData();

        $.ajax({
            url: '<?= base_url() ?>position/save_custom_arrangement',
            method: 'POST',
            data: {
                arrangement_data: JSON.stringify(arrangementData),
                user_id: this.config.userId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.hasUnsavedChanges = false;
                    this.showSuccessMessage('Arrangement saved successfully');
                    this.exitEditMode();
                } else {
                    this.showErrorMessage('Failed to save arrangement: ' + response.message);
                }
            },
            error: () => {
                this.showErrorMessage('Error saving arrangement');
            }
        });
    }

    /**
     * Cancel editing
     */
    cancelEditing() {
        if (this.hasUnsavedChanges) {
            if (!confirm('Discard unsaved changes?')) {
                return;
            }
        }

        this.restoreOriginalData();
        this.exitEditMode();
    }

    /**
     * Undo last action
     */
    undo() {
        if (this.undoStack.length > 1) {
            const currentState = this.undoStack.pop();
            this.redoStack.push(currentState);

            const previousState = this.undoStack[this.undoStack.length - 1];
            this.restoreState(previousState);

            this.updateUndoRedoButtons();
            this.refreshDendrogram();
        }
    }

    /**
     * Redo last undone action
     */
    redo() {
        if (this.redoStack.length > 0) {
            const state = this.redoStack.pop();
            this.undoStack.push(state);

            this.restoreState(state);
            this.updateUndoRedoButtons();
            this.refreshDendrogram();
        }
    }

    /**
     * Save current state for undo/redo
     */
    saveState(description) {
        const state = {
            data: JSON.parse(JSON.stringify(this.getCurrentArrangementData())),
            timestamp: Date.now(),
            description: description
        };

        this.undoStack.push(state);

        // Limit undo stack size
        if (this.undoStack.length > 20) {
            this.undoStack.shift();
        }

        // Clear redo stack when new action is performed
        this.redoStack = [];

        this.updateUndoRedoButtons();
    }

    /**
     * Update undo/redo button states
     */
    updateUndoRedoButtons() {
        $('#undoArrangement').prop('disabled', this.undoStack.length <= 1);
        $('#redoArrangement').prop('disabled', this.redoStack.length === 0);
    }

    /**
     * Mark as changed
     */
    markAsChanged() {
        this.hasUnsavedChanges = true;

        // Update save button
        $('#saveArrangement').removeClass('btn-success').addClass('btn-warning')
            .html('<i class="fas fa-save"></i> Save*');
    }

    /**
     * Helper methods
     */
    getDepartmentData() {
        // Extract department data from current dendrogram
        if (!this.dendrogram || !this.dendrogram.root) return [];

        const departments = [];
        this.dendrogram.root.children?.forEach(dept => {
            if (dept.data.isDepartment) {
                const positions = dept.children?.map(pos => pos.data.data) || [];
                departments.push({
                    name: dept.data.name,
                    positions: positions,
                    baseOrder: Math.max(...positions.map(p => p.position_order || 0), 100)
                });
            }
        });

        return departments;
    }

    getCurrentArrangementData() {
        return this.getDepartmentData();
    }

    restoreOriginalData() {
        if (this.originalData && this.dendrogram) {
            this.dendrogram.loadData(this.originalData);
        }
    }

    refreshDendrogram() {
        if (this.dendrogram) {
            this.dendrogram.render();
        }
    }

    showSuccessMessage(message) {
        // Use SweetAlert2 if available, otherwise alert
        if (typeof Swal !== 'undefined') {
            Swal.fire('Success', message, 'success');
        } else {
            alert(message);
        }
    }

    showErrorMessage(message) {
        // Use SweetAlert2 if available, otherwise alert
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', message, 'error');
        } else {
            alert(message);
        }
    }

    // Validation and constraint methods
    loadValidationRules() {
        // Define arrangement validation rules
        this.validationRules.set('department_lock', {
            validate: (node, newParent) => {
                if (this.config.departmentLocked) {
                    return node.data.department === newParent.data.name;
                }
                return true;
            },
            message: 'Positions cannot be moved between departments'
        });

        this.validationRules.set('hierarchy_maintain', {
            validate: (node, newParent) => {
                return newParent.data.isDepartment || newParent.data.type === 'root';
            },
            message: 'Positions can only be placed under departments'
        });
    }

    validateMove(node, newParent) {
        for (let [name, rule] of this.validationRules) {
            if (!rule.validate(node, newParent)) {
                return { valid: false, message: rule.message };
            }
        }
        return { valid: true };
    }

    // Additional helper methods for visual feedback
    addEditModeIndicators() {
        // Add visual indicators that we're in edit mode
        if (this.dendrogram && this.dendrogram.g) {
            this.dendrogram.g.classed('edit-mode', true);
        }
    }

    removeEditModeIndicators() {
        if (this.dendrogram && this.dendrogram.g) {
            this.dendrogram.g.classed('edit-mode', false);
        }
    }
}

// Make available globally
window.ArrangementEditor = ArrangementEditor;
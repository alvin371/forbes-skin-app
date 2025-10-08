/**
 * Career Tree Visualization Component
 * D3.js-based interactive career progression tree for BH Skin Application
 * 
 * @author Claude Code
 * @version 1.0.0
 * @requires d3.js v7+, jQuery 3.6+, Bootstrap 5
 */

class CareerTreeVisualization {
    /**
     * Initialize Career Tree Visualization
     * @param {string} containerId - DOM element ID for the visualization container
     * @param {Object} options - Configuration options
     */
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = d3.select(`#${containerId}`);
        
        // Default configuration
        this.config = {
            width: options.width || 1200,
            height: options.height || 800,
            margin: options.margin || { top: 20, right: 90, bottom: 30, left: 90 },
            nodeRadius: options.nodeRadius || 8,
            fontSize: options.fontSize || 12,
            linkColor: options.linkColor || '#999',
            nodeColors: options.nodeColors || {
                junior: '#52c41a',      // Green
                intermediate: '#1890ff', // Blue
                senior: '#722ed1',      // Purple
                executive: '#fa8c16',   // Orange
                leadership: '#eb2f96'   // Pink
            },
            animationDuration: options.animationDuration || 750,
            zoomExtent: options.zoomExtent || [0.1, 3],
            enableTooltip: options.enableTooltip !== false,
            enableZoom: options.enableZoom !== false,
            enablePan: options.enablePan !== false,
            layout: options.layout || 'tree' // 'tree', 'cluster', 'radial'
        };

        // Internal state
        this.data = null;
        this.root = null;
        this.svg = null;
        this.g = null;
        this.tree = null;
        this.diagonal = null;
        this.zoom = null;
        this.tooltip = null;
        
        // Node tracking
        this.nodeId = 0;
        this.selectedNode = null;
        this.highlightedPath = [];
        
        // Visibility tracking
        this.needsRender = false;

        // Performance optimization
        this.renderingCache = new Map();
        this.isRendering = false;
        this.requestId = null;
        this.visibilityObserver = null;
        this.resizeObserver = null;
        
        // Virtual rendering for large datasets
        this.virtualRenderingEnabled = options.enableVirtualRendering !== false;
        this.visibleNodes = new Set();
        this.renderBatchSize = options.renderBatchSize || 50;
        
        this.init();
    }

    /**
     * Initialize the visualization
     */
    init() {
        this.createSVG();
        this.setupZoom();
        this.setupTooltip();
        this.setupEventListeners();
        this.setupPerformanceOptimizations();
    }

    /**
     * Create SVG container and groups
     */
    createSVG() {
        // Clear existing content
        this.container.selectAll("*").remove();
        
        // Create main SVG
        this.svg = this.container
            .append("svg")
            .attr("width", "100%")
            .attr("height", this.config.height)
            .attr("viewBox", `0 0 ${this.config.width} ${this.config.height}`)
            .attr("preserveAspectRatio", "xMidYMid meet")
            .classed("career-tree-svg", true);

        // Create main group for transformations
        this.g = this.svg
            .append("g")
            .attr("class", "career-tree-container");

        // Create groups for different elements
        this.g.append("g").attr("class", "links");
        this.g.append("g").attr("class", "nodes");
        
        // Don't add loading indicator automatically - let CareerTreeManager handle loading states
        console.log('CareerTreeVisualization: SVG groups created, ready for data');
    }

    /**
     * Setup zoom and pan functionality
     */
    setupZoom() {
        if (!this.config.enableZoom && !this.config.enablePan) return;

        this.zoom = d3.zoom()
            .scaleExtent(this.config.zoomExtent)
            .on("zoom", (event) => {
                // Validate transform values to prevent NaN errors
                const transform = event.transform;
                const x = isNaN(transform.x) ? 0 : transform.x;
                const y = isNaN(transform.y) ? 0 : transform.y;
                const k = isNaN(transform.k) ? 1 : transform.k;
                
                this.g.attr("transform", `translate(${x},${y}) scale(${k})`);
            });

        if (this.config.enableZoom || this.config.enablePan) {
            this.svg.call(this.zoom);
        }

        // Add zoom controls
        this.addZoomControls();
    }

    /**
     * Add zoom control buttons
     */
    addZoomControls() {
        const controls = this.container
            .append("div")
            .attr("class", "career-tree-controls")
            .style("position", "absolute")
            .style("top", "10px")
            .style("right", "10px")
            .style("z-index", "1000");

        // Zoom in button
        controls.append("button")
            .attr("class", "btn btn-sm btn-outline-primary me-1")
            .attr("title", "Zoom In")
            .html('<i class="fas fa-plus"></i>')
            .on("click", () => this.zoomIn());

        // Zoom out button
        controls.append("button")
            .attr("class", "btn btn-sm btn-outline-primary me-1")
            .attr("title", "Zoom Out")
            .html('<i class="fas fa-minus"></i>')
            .on("click", () => this.zoomOut());

        // Reset zoom button
        controls.append("button")
            .attr("class", "btn btn-sm btn-outline-secondary me-1")
            .attr("title", "Reset View")
            .html('<i class="fas fa-expand-arrows-alt"></i>')
            .on("click", () => this.resetZoom());

        // Fullscreen button
        controls.append("button")
            .attr("class", "btn btn-sm btn-outline-info")
            .attr("title", "Fullscreen")
            .html('<i class="fas fa-expand"></i>')
            .on("click", () => this.toggleFullscreen());
    }

    /**
     * Setup tooltip for node information
     */
    setupTooltip() {
        if (!this.config.enableTooltip) return;

        this.tooltip = d3.select("body").append("div")
            .attr("class", "career-tree-tooltip")
            .style("opacity", 0)
            .style("position", "absolute")
            .style("background", "rgba(0, 0, 0, 0.8)")
            .style("color", "white")
            .style("padding", "10px")
            .style("border-radius", "5px")
            .style("font-size", "12px")
            .style("pointer-events", "none")
            .style("z-index", "1001");
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Window resize handler
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        // Escape key to clear selection
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.clearSelection();
            }
        });
    }

    /**
     * Setup performance optimizations
     */
    setupPerformanceOptimizations() {
        // Setup Intersection Observer for visibility-based rendering
        if (this.virtualRenderingEnabled && 'IntersectionObserver' in window) {
            this.visibilityObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const nodeId = entry.target.getAttribute('data-node-id');
                    if (entry.isIntersecting) {
                        this.visibleNodes.add(nodeId);
                    } else {
                        this.visibleNodes.delete(nodeId);
                    }
                });
            }, {
                root: this.svg.node(),
                rootMargin: '50px',
                threshold: 0.1
            });
        }

        // Setup ResizeObserver for responsive handling
        if ('ResizeObserver' in window) {
            this.resizeObserver = new ResizeObserver(this.debounce((entries) => {
                for (const entry of entries) {
                    if (entry.target.id === this.containerId) {
                        this.handleResize();
                        break;
                    }
                }
            }, 250));
            
            this.resizeObserver.observe(document.getElementById(this.containerId));
        }

        // Setup memory cleanup
        this.setupMemoryManagement();
    }

    /**
     * Setup memory management for large datasets
     */
    setupMemoryManagement() {
        // Cleanup rendering cache periodically
        setInterval(() => {
            if (this.renderingCache.size > 100) {
                // Keep only the most recent 50 entries
                const entries = Array.from(this.renderingCache.entries());
                const toKeep = entries.slice(-50);
                this.renderingCache.clear();
                toKeep.forEach(([key, value]) => {
                    this.renderingCache.set(key, value);
                });
            }
        }, 30000); // Every 30 seconds

        // Monitor memory usage if available
        if ('memory' in performance) {
            const memoryCheck = () => {
                const memInfo = performance.memory;
                const memUsage = memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit;
                
                if (memUsage > 0.8) {
                    console.warn('CareerTreeVisualization: High memory usage detected, clearing caches');
                    this.renderingCache.clear();
                }
            };
            
            setInterval(memoryCheck, 60000); // Every minute
        }
    }

    /**
     * Optimized rendering with batching and caching
     */
    renderWithOptimization() {
        if (this.requestId) {
            cancelAnimationFrame(this.requestId);
        }

        this.requestId = requestAnimationFrame(() => {
            this.render();
            this.requestId = null;
        });
    }

    /**
     * Load and render career tree data
     * @param {Object} data - Career tree data
     */
    loadData(data) {
        if (this.isRendering) {
            console.warn('CareerTreeVisualization: Rendering in progress, ignoring new data');
            return;
        }

        console.log('CareerTreeVisualization: Starting to load data', data);
        this.isRendering = true;
        this.data = data;
        
        try {
            // Process data and create hierarchy
            this.processData();
            
            // Hide loading and render with optimization
            this.hideLoading();
            console.log('CareerTreeVisualization: Loading indicator hidden');
            
            this.renderWithOptimization();
            console.log('CareerTreeVisualization: Rendering initiated');
            
        } catch (error) {
            console.error('CareerTreeVisualization: Error during data loading', error);
            this.hideLoading(); // Ensure loading is hidden even on error
            this.showError('Failed to render career tree: ' + error.message);
        } finally {
            this.isRendering = false;
        }
    }

    /**
     * Process raw data into D3 hierarchy
     */
    processData() {
        if (!this.data) {
            console.error('CareerTreeVisualization: No data provided');
            return;
        }

        // Validate data structure
        if (!this.data.children || !Array.isArray(this.data.children)) {
            console.error('CareerTreeVisualization: Invalid data structure - missing children array');
            // Provide fallback data structure
            this.data = {
                name: 'No Data',
                type: 'root',
                children: []
            };
        }

        // Convert flat data to hierarchical structure
        this.root = d3.hierarchy(this.data);
        
        // Create tree layout
        const treeWidth = this.config.width - this.config.margin.left - this.config.margin.right;
        const treeHeight = this.config.height - this.config.margin.top - this.config.margin.bottom;
        
        if (this.config.layout === 'radial') {
            this.tree = d3.tree()
                .size([2 * Math.PI, Math.min(treeWidth, treeHeight) / 2]);
        } else {
            this.tree = d3.tree()
                .size([treeHeight, treeWidth]);
        }

        // Assign positions
        this.tree(this.root);

        // Custom positioning logic based on position_order
        this.adjustPositionsByOrder();

        // Transform for radial layout
        if (this.config.layout === 'radial') {
            this.root.descendants().forEach(d => {
                const angle = d.x;
                const radius = d.y;
                d.x = radius * Math.cos(angle - Math.PI / 2);
                d.y = radius * Math.sin(angle - Math.PI / 2);
            });
        }

        // Validate and fix coordinates to prevent NaN errors
        this.root.descendants().forEach(d => {
            // Fix any NaN coordinates with default values
            if (isNaN(d.x)) d.x = 0;
            if (isNaN(d.y)) d.y = 0;
            
            // Assign unique IDs to nodes
            d.id = ++this.nodeId;
        });
    }

    /**
     * Adjust node positions for career progression visualization
     * Creates clear vertical career paths within each department
     */
    adjustPositionsByOrder() {
        if (this.config.layout === 'radial') {
            // Skip custom positioning for radial layout
            return;
        }

        // The tree structure is already linear (career progression chains)
        // We just need to ensure proper spacing for career paths
        
        // Get all department nodes (children of root)
        const departments = this.root.children || [];
        
        departments.forEach(department => {
            // Each department should have one career progression chain
            if (department.children && department.children.length > 0) {
                // The first child is the entry-level position
                const careerPath = department.children[0];
                
                // Traverse the career progression chain and adjust spacing
                this.adjustCareerPathSpacing(careerPath, department.x);
            }
        });
    }
    
    /**
     * Adjust spacing for a career progression path
     */
    adjustCareerPathSpacing(startPosition, departmentX) {
        const baseSpacing = 100; // Base spacing between career levels
        let currentPosition = startPosition;
        let level = 0;
        
        while (currentPosition) {
            // Position nodes in a vertical career progression
            currentPosition.x = departmentX + (level * baseSpacing);
            
            // Move to next position in career path
            currentPosition = currentPosition.children && currentPosition.children.length > 0 
                ? currentPosition.children[0] 
                : null;
            level++;
        }
    }

    /**
     * Main render method
     */
    render() {
        if (!this.root) {
            console.warn('CareerTreeVisualization: No data to render');
            return;
        }

        // Check if container is visible and has dimensions
        const containerElement = document.getElementById(this.containerId);
        if (!containerElement || containerElement.offsetWidth === 0 || containerElement.offsetHeight === 0) {
            console.warn('CareerTreeVisualization: Container not visible or has zero dimensions, deferring render');
            // Store that we need to render when container becomes visible
            this.needsRender = true;
            return;
        }

        console.log('CareerTreeVisualization: Starting render process');

        // Set container transform
        this.g.attr("transform", 
            `translate(${this.config.margin.left},${this.config.margin.top})`);

        // Render links and nodes
        this.renderLinks();
        this.renderNodes();

        // Setup keyboard navigation for accessibility
        this.setupKeyboardNavigation();

        // Initial zoom to fit
        this.zoomToFit();
        
        this.needsRender = false;
        console.log('CareerTreeVisualization: Render complete');
        
        // Notify that rendering is complete
        this.emit('renderComplete', { success: true });
    }

    /**
     * Render tree links/connections
     */
    renderLinks() {
        const links = this.root.links();

        // Create link generator
        const linkGenerator = this.config.layout === 'radial' 
            ? d3.linkRadial()
                .angle(d => d.x)
                .radius(d => d.y)
            : d3.linkHorizontal()
                .x(d => d.y)
                .y(d => d.x);

        // Select and bind data
        const link = this.g.select(".links")
            .selectAll(".link")
            .data(links, d => d.target.id);

        // Enter selection
        const linkEnter = link.enter()
            .append("path")
            .attr("class", d => {
                // Different classes for different connection types
                if (d.source.data.type === 'root' && d.target.data.type === 'department') {
                    return "link department-link";
                } else if (d.source.data.type === 'department' && d.target.data.type === 'position') {
                    return "link position-link";
                }
                return "link";
            })
            .attr("fill", "none")
            .attr("stroke", d => {
                // Different colors for different connection types
                if (d.source.data.type === 'root' && d.target.data.type === 'department') {
                    return "#1890ff"; // Blue for department connections
                } else if (d.source.data.type === 'department' && d.target.data.type === 'position') {
                    return "#52c41a"; // Green for position connections
                }
                return this.config.linkColor;
            })
            .attr("stroke-width", d => {
                // Thicker lines for department connections
                if (d.source.data.type === 'root' && d.target.data.type === 'department') {
                    return 3;
                } else if (d.source.data.type === 'department' && d.target.data.type === 'position') {
                    return 2;
                }
                return 2;
            })
            .attr("stroke-dasharray", d => {
                // Solid lines for departments, dashed for special cases
                if (d.source.data.type === 'department' && d.target.data.type === 'position') {
                    const positionOrder = d.target.data.position_order || 0;
                    // Dashed line for position_order 0 to highlight entry-level positions
                    return positionOrder === 0 ? "4,2" : "none";
                }
                return "none";
            })
            .attr("opacity", 0);

        // Update selection
        const linkUpdate = linkEnter.merge(link);
        
        linkUpdate.transition()
            .duration(this.config.animationDuration)
            .attr("d", linkGenerator)
            .attr("opacity", 1);

        // Exit selection
        link.exit()
            .transition()
            .duration(this.config.animationDuration)
            .attr("opacity", 0)
            .remove();
    }

    /**
     * Render tree nodes
     */
    renderNodes() {
        const nodes = this.root.descendants();

        // Select and bind data
        const node = this.g.select(".nodes")
            .selectAll(".node")
            .data(nodes, d => d.id);

        // Enter selection
        const nodeEnter = node.enter()
            .append("g")
            .attr("class", "node")
            .attr("transform", d => {
                // Validate coordinates before applying transform
                const x = isNaN(d.x) ? 0 : d.x;
                const y = isNaN(d.y) ? 0 : d.y;
                return `translate(${y},${x})`;
            })
            .style("opacity", 0)
            .style("cursor", "pointer")
            .on("click", (event, d) => this.handleNodeClick(event, d))
            .on("mouseover", (event, d) => this.handleNodeMouseOver(event, d))
            .on("mouseout", (event, d) => this.handleNodeMouseOut(event, d));

        // Add circles for nodes
        nodeEnter.append("circle")
            .attr("r", this.config.nodeRadius)
            .attr("fill", d => this.getNodeColor(d.data))
            .attr("stroke", "#fff")
            .attr("stroke-width", 2)
            .attr("role", "button")
            .attr("tabindex", "0")
            .attr("aria-label", d => this.generateAriaLabel(d.data));

        // Add text labels
        nodeEnter.append("text")
            .attr("dy", "0.31em")
            .attr("x", d => d.children ? -this.config.nodeRadius - 6 : this.config.nodeRadius + 6)
            .attr("text-anchor", d => d.children ? "end" : "start")
            .text(d => d.data.name)
            .attr("font-size", this.config.fontSize)
            .attr("font-family", "Arial, sans-serif")
            .attr("fill", "#333");

        // Add department labels for positions (not as nodes)
        nodeEnter.filter(d => d.data.department_label && d.data.type !== 'ceo')
            .append("text")
            .attr("class", "department-label")
            .attr("dy", "-2em")
            .attr("x", 0)
            .attr("text-anchor", "middle")
            .attr("font-size", "11px")
            .attr("font-family", "Arial, sans-serif")
            .attr("font-weight", "bold")
            .attr("fill", "#1890ff")
            .text(d => d.data.department_label);

        // Add badges for additional info
        this.addNodeBadges(nodeEnter);

        // Update selection
        const nodeUpdate = nodeEnter.merge(node);
        
        nodeUpdate.transition()
            .duration(this.config.animationDuration)
            .attr("transform", d => {
                // Validate coordinates before applying transform
                const x = isNaN(d.x) ? 0 : d.x;
                const y = isNaN(d.y) ? 0 : d.y;
                return `translate(${y},${x})`;
            })
            .style("opacity", 1);

        // Update circles
        nodeUpdate.select("circle")
            .transition()
            .duration(this.config.animationDuration)
            .attr("fill", d => this.getNodeColor(d.data))
            .attr("r", this.config.nodeRadius);

        // Exit selection
        node.exit()
            .transition()
            .duration(this.config.animationDuration)
            .style("opacity", 0)
            .remove();
    }

    /**
     * Add badges to nodes for additional information
     */
    addNodeBadges(nodeEnter) {
        // Level badge for position nodes
        nodeEnter.filter(d => d.data.type === 'position' && d.data.level_name && d.data.level_name !== 'No Level')
            .append("circle")
            .attr("class", "level-badge")
            .attr("r", 8)
            .attr("cx", -this.config.nodeRadius - 10)
            .attr("cy", -this.config.nodeRadius - 10)
            .attr("fill", "#1890ff")
            .attr("stroke", "#fff")
            .attr("stroke-width", 1);

        nodeEnter.filter(d => d.data.type === 'position' && d.data.level_name && d.data.level_name !== 'No Level')
            .append("text")
            .attr("class", "level-badge-text")
            .attr("x", -this.config.nodeRadius - 10)
            .attr("y", -this.config.nodeRadius - 10)
            .attr("text-anchor", "middle")
            .attr("dy", "0.31em")
            .attr("font-size", "8px")
            .attr("font-weight", "bold")
            .attr("fill", "#fff")
            .text(d => {
                // Abbreviate level names
                const level = d.data.level_name;
                if (level.includes('Junior')) return 'J';
                if (level.includes('Intermediate')) return 'I';
                if (level.includes('Senior')) return 'S';
                return level.charAt(0).toUpperCase();
            });

        // Employee count badge
        nodeEnter.filter(d => d.data.employee_count > 0)
            .append("circle")
            .attr("class", "employee-badge")
            .attr("r", 8)
            .attr("cx", this.config.nodeRadius + 5)
            .attr("cy", -this.config.nodeRadius - 5)
            .attr("fill", "#52c41a")
            .attr("stroke", "#fff")
            .attr("stroke-width", 1);

        nodeEnter.filter(d => d.data.employee_count > 0)
            .append("text")
            .attr("class", "employee-count")
            .attr("x", this.config.nodeRadius + 5)
            .attr("y", -this.config.nodeRadius - 5)
            .attr("dy", "0.31em")
            .attr("text-anchor", "middle")
            .attr("font-size", "10px")
            .attr("fill", "#fff")
            .text(d => d.data.employee_count);

        // Leadership badge
        nodeEnter.filter(d => d.data.is_leadership)
            .append("path")
            .attr("class", "leadership-badge")
            .attr("d", "M-4,-8 L4,-8 L6,-4 L4,0 L-4,0 L-6,-4 Z")
            .attr("transform", `translate(${-this.config.nodeRadius - 8}, ${-this.config.nodeRadius - 8})`)
            .attr("fill", "#faad14")
            .attr("stroke", "#fff")
            .attr("stroke-width", 1);
    }

    /**
     * Get node color based on level and type
     */
    getNodeColor(nodeData) {
        // CEO gets special color
        if (nodeData.type === 'ceo') {
            return '#722ed1'; // Purple for CEO
        }
        
        // Position groups get different color
        if (nodeData.type === 'position_group') {
            return '#fa8c16'; // Orange for position groups
        }
        
        if (nodeData.is_leadership) {
            return this.config.nodeColors.leadership;
        }
        
        const level = nodeData.level_name?.toLowerCase();
        return this.config.nodeColors[level] || this.config.nodeColors.intermediate;
    }

    /**
     * Handle node click events
     */
    handleNodeClick(event, d) {
        event.stopPropagation();
        
        // Toggle selection
        if (this.selectedNode === d) {
            this.clearSelection();
        } else {
            this.selectNode(d);
        }

        // Emit custom event
        this.emit('nodeClick', { node: d, event: event });
    }

    /**
     * Handle node mouseover events
     */
    handleNodeMouseOver(event, d) {
        if (!this.config.enableTooltip || !this.tooltip) return;

        const tooltipContent = this.generateTooltipContent(d.data);
        
        this.tooltip.transition()
            .duration(200)
            .style("opacity", .9);
            
        this.tooltip.html(tooltipContent)
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 28) + "px");

        // Highlight node
        d3.select(event.currentTarget)
            .select("circle")
            .transition()
            .duration(150)
            .attr("r", this.config.nodeRadius * 1.3)
            .attr("stroke-width", 3);
    }

    /**
     * Handle node mouseout events
     */
    handleNodeMouseOut(event, d) {
        if (!this.config.enableTooltip || !this.tooltip) return;

        this.tooltip.transition()
            .duration(500)
            .style("opacity", 0);

        // Reset node highlight
        if (this.selectedNode !== d) {
            d3.select(event.currentTarget)
                .select("circle")
                .transition()
                .duration(150)
                .attr("r", this.config.nodeRadius)
                .attr("stroke-width", 2);
        }
    }

    /**
     * Generate tooltip content for a node
     */
    generateTooltipContent(nodeData) {        
        return `
            <div class="tooltip-header">
                <strong>${nodeData.name}</strong>
                ${nodeData.type === 'position' ? '<span style="font-size: 10px; color: #ccc;"> (Position)</span>' : ''}
                ${nodeData.type === 'department' ? '<span style="font-size: 10px; color: #ccc;"> (Department)</span>' : ''}
            </div>
            <div class="tooltip-body">
                <div><strong>Level:</strong> ${nodeData.level_name || 'N/A'}</div>
                <div><strong>Department:</strong> ${nodeData.department || 'N/A'}</div>
                <div><strong>Employees:</strong> ${nodeData.employee_count || 0}</div>
                ${nodeData.is_leadership ? '<div style="color: #faad14;"><i class="fas fa-crown"></i> Leadership Position</div>' : ''}
                ${nodeData.type === 'position' ? '<div style="color: #52c41a; font-size: 11px; margin-top: 5px;"><i class="fas fa-arrow-up"></i> Career Progression Path</div>' : ''}
            </div>
        `;
    }

    /**
     * Select a node and highlight career paths
     */
    selectNode(node) {
        // Clear previous selection
        this.clearSelection();
        
        this.selectedNode = node;
        
        // Highlight selected node
        this.g.selectAll(".node")
            .filter(d => d === node)
            .select("circle")
            .transition()
            .duration(300)
            .attr("r", this.config.nodeRadius * 1.5)
            .attr("stroke", "#ff4d4f")
            .attr("stroke-width", 4);

        // Highlight career paths
        this.highlightCareerPaths(node);

        // Emit event
        this.emit('nodeSelect', { node: node });
    }

    /**
     * Clear node selection and highlights
     */
    clearSelection() {
        if (!this.selectedNode) return;

        // Reset node appearance
        this.g.selectAll(".node circle")
            .transition()
            .duration(300)
            .attr("r", this.config.nodeRadius)
            .attr("stroke", "#fff")
            .attr("stroke-width", 2);

        // Reset link highlights
        this.g.selectAll(".link")
            .transition()
            .duration(300)
            .attr("stroke", this.config.linkColor)
            .attr("stroke-width", 2)
            .attr("opacity", 1);

        this.selectedNode = null;
        this.highlightedPath = [];

        // Emit event
        this.emit('nodeDeselect');
    }

    /**
     * Highlight career progression paths from selected node
     */
    highlightCareerPaths(node) {
        // Get path from root to selected node
        const pathToRoot = node.ancestors();
        const pathFromRoot = pathToRoot.reverse();
        
        // Get all descendant paths
        const descendants = node.descendants();
        
        // Combine paths
        this.highlightedPath = [...pathFromRoot, ...descendants.slice(1)];
        
        // Fade non-highlighted elements
        this.g.selectAll(".node")
            .transition()
            .duration(300)
            .style("opacity", d => this.highlightedPath.includes(d) ? 1 : 0.3);
            
        this.g.selectAll(".link")
            .transition()
            .duration(300)
            .style("opacity", d => {
                return this.highlightedPath.includes(d.source) && 
                       this.highlightedPath.includes(d.target) ? 1 : 0.2;
            })
            .attr("stroke", d => {
                return this.highlightedPath.includes(d.source) && 
                       this.highlightedPath.includes(d.target) ? "#1890ff" : this.config.linkColor;
            })
            .attr("stroke-width", d => {
                return this.highlightedPath.includes(d.source) && 
                       this.highlightedPath.includes(d.target) ? 3 : 2;
            });
    }

    /**
     * Zoom control methods
     */
    zoomIn() {
        this.svg.transition()
            .duration(300)
            .call(this.zoom.scaleBy, 1.5);
    }

    zoomOut() {
        this.svg.transition()
            .duration(300)
            .call(this.zoom.scaleBy, 1 / 1.5);
    }

    resetZoom() {
        this.svg.transition()
            .duration(750)
            .call(this.zoom.transform, d3.zoomIdentity);
        this.zoomToFit();
    }

    zoomToFit() {
        if (!this.root) return;

        try {
            const bounds = this.g.node().getBBox();
            const parent = this.svg.node().getBoundingClientRect();
            
            // Validate dimensions to prevent NaN
            const fullWidth = isNaN(parent.width) || parent.width <= 0 ? this.config.width : parent.width;
            const fullHeight = isNaN(parent.height) || parent.height <= 0 ? this.config.height : parent.height;
            
            const width = isNaN(bounds.width) || bounds.width <= 0 ? 1 : bounds.width;
            const height = isNaN(bounds.height) || bounds.height <= 0 ? 1 : bounds.height;
            
            const midX = isNaN(bounds.x) ? 0 : bounds.x + width / 2;
            const midY = isNaN(bounds.y) ? 0 : bounds.y + height / 2;
            
            const scale = 0.85 / Math.max(width / fullWidth, height / fullHeight);
            const translateX = fullWidth / 2 - scale * midX;
            const translateY = fullHeight / 2 - scale * midY;
            
            // Validate final values
            const finalScale = isNaN(scale) || scale <= 0 ? 1 : scale;
            const finalX = isNaN(translateX) ? 0 : translateX;
            const finalY = isNaN(translateY) ? 0 : translateY;
            
            this.svg.transition()
                .duration(750)
                .call(this.zoom.transform, d3.zoomIdentity.translate(finalX, finalY).scale(finalScale));
        } catch (error) {
            console.warn('CareerTreeVisualization: Error in zoomToFit, using default transform:', error);
            // Fallback to identity transform
            this.svg.transition()
                .duration(750)
                .call(this.zoom.transform, d3.zoomIdentity);
        }
    }

    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {
        const container = document.getElementById(this.containerId);
        
        if (!document.fullscreenElement) {
            container.requestFullscreen?.() || 
            container.webkitRequestFullscreen?.() || 
            container.mozRequestFullScreen?.();
        } else {
            document.exitFullscreen?.() || 
            document.webkitExitFullscreen?.() || 
            document.mozCancelFullScreen?.();
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        const containerRect = document.getElementById(this.containerId).getBoundingClientRect();
        this.config.width = containerRect.width;
        
        this.svg.attr("viewBox", `0 0 ${this.config.width} ${this.config.height}`);
        
        if (this.root) {
            this.processData();
            this.render();
        }
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        const loading = this.container.select(".loading-indicator");
        if (loading.empty()) {
            this.container.append("div")
                .attr("class", "loading-indicator")
                .style("position", "absolute")
                .style("top", "50%")
                .style("left", "50%")
                .style("transform", "translate(-50%, -50%)")
                .style("text-align", "center")
                .html(`
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading Career Tree...</div>
                `);
        }
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        this.container.select(".loading-indicator").remove();
    }

    /**
     * Show error message
     */
    showError(message) {
        this.container.selectAll("*").remove();
        this.container.append("div")
            .attr("class", "error-indicator")
            .style("position", "absolute")
            .style("top", "50%")
            .style("left", "50%")
            .style("transform", "translate(-50%, -50%)")
            .style("text-align", "center")
            .style("color", "#dc3545")
            .html(`
                <div style="font-size: 48px; margin-bottom: 16px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div style="font-size: 16px; font-weight: 600;">${message}</div>
                <button class="btn btn-outline-danger btn-sm mt-3" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Reload Page
                </button>
            `);
    }

    /**
     * Check if render was deferred and trigger it if container is now visible
     */
    checkDeferredRender() {
        if (this.needsRender) {
            const containerElement = document.getElementById(this.containerId);
            if (containerElement && containerElement.offsetWidth > 0 && containerElement.offsetHeight > 0) {
                console.log('CareerTreeVisualization: Container now visible, triggering deferred render');
                this.render();
            }
        }
    }

    /**
     * Event emitter for custom events
     */
    emit(eventName, data = {}) {
        const event = new CustomEvent(`careerTree:${eventName}`, {
            detail: { ...data, instance: this }
        });
        document.dispatchEvent(event);
    }

    /**
     * Utility: Debounce function calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Export visualization as PNG
     */
    exportAsPNG(filename = 'career-tree.png') {
        const svgNode = this.svg.node();
        const svgData = new XMLSerializer().serializeToString(svgNode);
        
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const img = new Image();
        
        canvas.width = this.config.width;
        canvas.height = this.config.height;
        
        img.onload = () => {
            context.drawImage(img, 0, 0);
            const pngFile = canvas.toDataURL('image/png');
            
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = pngFile;
            downloadLink.click();
        };
        
        img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
    }

    /**
     * Generate accessible aria-label for nodes
     */
    generateAriaLabel(nodeData) {
        let label = `${nodeData.name}`;
        
        if (nodeData.type === 'position') {
            label += ' position';
        } else if (nodeData.type === 'department') {
            label += ' department';
        }
        
        if (nodeData.department && nodeData.type !== 'department') {
            label += ` in ${nodeData.department} department`;
        }
        
        if (nodeData.level_name && nodeData.level_name !== 'No Level') {
            label += `, ${nodeData.level_name} level`;
        }
        
        if (nodeData.employee_count > 0) {
            label += `, ${nodeData.employee_count} employees`;
        }
        
        if (nodeData.is_leadership) {
            label += ', leadership position';
        }
        
        if (nodeData.outgoing_paths > 0) {
            label += `, ${nodeData.outgoing_paths} career progression paths available`;
        }
        
        label += '. Press Enter or Space to select, Escape to deselect.';
        
        return label;
    }

    /**
     * Setup keyboard navigation for accessibility
     */
    setupKeyboardNavigation() {
        // Add keyboard event listeners
        this.g.selectAll('.node')
            .on('keydown', (event, d) => {
                event.preventDefault();
                
                switch (event.key) {
                    case 'Enter':
                    case ' ':
                        this.handleNodeClick(event, d);
                        break;
                    case 'Escape':
                        this.clearSelection();
                        break;
                    case 'ArrowUp':
                        this.focusParentNode(d);
                        break;
                    case 'ArrowDown':
                        this.focusChildNode(d);
                        break;
                    case 'ArrowLeft':
                        this.focusSiblingNode(d, -1);
                        break;
                    case 'ArrowRight':
                        this.focusSiblingNode(d, 1);
                        break;
                }
            });
    }

    /**
     * Focus navigation helpers
     */
    focusParentNode(currentNode) {
        if (currentNode.parent) {
            this.focusNode(currentNode.parent);
        }
    }

    focusChildNode(currentNode) {
        if (currentNode.children && currentNode.children.length > 0) {
            this.focusNode(currentNode.children[0]);
        }
    }

    focusSiblingNode(currentNode, direction) {
        if (!currentNode.parent) return;
        
        const siblings = currentNode.parent.children;
        const currentIndex = siblings.indexOf(currentNode);
        const newIndex = currentIndex + direction;
        
        if (newIndex >= 0 && newIndex < siblings.length) {
            this.focusNode(siblings[newIndex]);
        }
    }

    focusNode(node) {
        this.g.selectAll('.node')
            .filter(d => d === node)
            .select('circle')
            .node()
            ?.focus();
    }

    /**
     * Destroy the visualization and clean up resources
     */
    destroy() {
        // Cancel any pending animation frames
        if (this.requestId) {
            cancelAnimationFrame(this.requestId);
        }

        // Disconnect observers
        if (this.visibilityObserver) {
            this.visibilityObserver.disconnect();
        }
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }

        // Remove event listeners
        window.removeEventListener('resize', this.handleResize);
        
        // Clear DOM elements
        this.container.selectAll("*").remove();
        if (this.tooltip) {
            this.tooltip.remove();
        }
        
        // Clear references and caches
        this.data = null;
        this.root = null;
        this.selectedNode = null;
        this.highlightedPath = [];
        this.renderingCache.clear();
        this.visibleNodes.clear();
    }
}

// Make available globally
window.CareerTreeVisualization = CareerTreeVisualization;
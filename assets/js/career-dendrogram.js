/**
 * Career Dendrogram Visualization
 * Interactive hierarchical clustering dendrogram for career progression
 * Uses D3.js v7 for modern visualization
 */

class CareerDendrogram {
    constructor(containerId, options = {}) {
        this.container = d3.select(`#${containerId}`);
        this.containerId = containerId;

        // Default configuration
        this.config = {
            width: options.width || 1200,
            height: options.height || 800,
            margin: { top: 50, right: 120, bottom: 50, left: 120 },
            nodeRadius: options.nodeRadius || 8,
            fontSize: options.fontSize || 12,
            linkDistance: options.linkDistance || 100,
            enableZoom: options.enableZoom !== false,
            enableDrag: options.enableDrag || false,
            enableTooltip: options.enableTooltip !== false,
            layout: options.layout || 'tree', // tree, cluster, radial
            colorScheme: options.colorScheme || 'modern',
            animationDuration: options.animationDuration || 750
        };

        // Internal properties
        this.svg = null;
        this.g = null;
        this.zoom = null;
        this.root = null;
        this.treeLayout = null;
        this.diagonal = null;
        this.tooltip = null;
        this.selectedNode = null;
        this.pathsVisible = false;

        // Color schemes
        this.colorSchemes = {
            modern: {
                ceo: '#722ed1',      // Purple
                executive: '#1890ff', // Blue
                manager: '#52c41a',   // Green
                specialist: '#fa8c16', // Orange
                background: '#fafafa',
                link: '#d9d9d9',
                text: '#262626',
                highlight: '#ff4d4f'
            },
            corporate: {
                ceo: '#2c3e50',
                executive: '#3498db',
                manager: '#27ae60',
                specialist: '#f39c12',
                background: '#ecf0f1',
                link: '#bdc3c7',
                text: '#2c3e50',
                highlight: '#e74c3c'
            }
        };

        this.colors = this.colorSchemes[this.config.colorScheme];

        this.init();
    }

    init() {
        this.createSVG();
        this.setupZoom();
        this.setupTooltip();
        this.createDiagonal();

        console.log('CareerDendrogram initialized successfully');
    }

    createSVG() {
        // Clear existing content
        this.container.selectAll("*").remove();

        // Create main SVG
        this.svg = this.container
            .append("svg")
            .attr("width", this.config.width)
            .attr("height", this.config.height)
            .attr("class", "career-dendrogram-svg")
            .style("background-color", this.colors.background);

        // Create main group for zoom/pan
        this.g = this.svg
            .append("g")
            .attr("class", "dendrogram-container");

        // Add pattern definitions for enhanced styling
        const defs = this.svg.append("defs");

        // Gradient for nodes
        const gradient = defs.append("linearGradient")
            .attr("id", "nodeGradient")
            .attr("x1", "0%")
            .attr("y1", "0%")
            .attr("x2", "100%")
            .attr("y2", "100%");

        gradient.append("stop")
            .attr("offset", "0%")
            .attr("stop-color", "#ffffff")
            .attr("stop-opacity", 0.8);

        gradient.append("stop")
            .attr("offset", "100%")
            .attr("stop-color", "#f0f0f0")
            .attr("stop-opacity", 1);

        // Shadow filter
        const shadow = defs.append("filter")
            .attr("id", "dropshadow")
            .attr("x", "-50%")
            .attr("y", "-50%")
            .attr("width", "200%")
            .attr("height", "200%");

        shadow.append("feDropShadow")
            .attr("dx", 2)
            .attr("dy", 2)
            .attr("stdDeviation", 3)
            .attr("flood-color", "#000000")
            .attr("flood-opacity", 0.3);
    }

    setupZoom() {
        if (!this.config.enableZoom) return;

        this.zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on("zoom", (event) => {
                this.g.attr("transform", event.transform);
            });

        this.svg.call(this.zoom);

        // Add zoom controls
        const zoomControls = this.container
            .append("div")
            .attr("class", "zoom-controls")
            .style("position", "absolute")
            .style("top", "10px")
            .style("right", "10px")
            .style("z-index", "1000");

        zoomControls.append("button")
            .attr("class", "btn btn-sm btn-outline-secondary")
            .style("margin", "2px")
            .html('<i class="fas fa-plus"></i>')
            .on("click", () => this.zoomIn());

        zoomControls.append("button")
            .attr("class", "btn btn-sm btn-outline-secondary")
            .style("margin", "2px")
            .html('<i class="fas fa-minus"></i>')
            .on("click", () => this.zoomOut());

        zoomControls.append("button")
            .attr("class", "btn btn-sm btn-outline-secondary")
            .style("margin", "2px")
            .html('<i class="fas fa-home"></i>')
            .on("click", () => this.resetZoom());
    }

    setupTooltip() {
        if (!this.config.enableTooltip) return;

        this.tooltip = d3.select("body")
            .append("div")
            .attr("class", "dendrogram-tooltip")
            .style("opacity", 0)
            .style("position", "absolute")
            .style("background", "rgba(0, 0, 0, 0.9)")
            .style("color", "white")
            .style("padding", "12px")
            .style("border-radius", "6px")
            .style("font-size", "12px")
            .style("pointer-events", "none")
            .style("z-index", "9999")
            .style("box-shadow", "0 4px 12px rgba(0,0,0,0.3)");
    }

    createDiagonal() {
        this.diagonal = d3.linkVertical()
            .x(d => d.x)
            .y(d => d.y);
    }

    loadData(data) {
        try {
            console.log('Loading dendrogram data:', data);

            // Transform flat data into hierarchical structure
            const hierarchicalData = this.transformToHierarchy(data);
            console.log('Hierarchical data created:', hierarchicalData);

            // Create root node
            this.root = d3.hierarchy(hierarchicalData);

            // Calculate positions using selected layout
            this.updateLayout();

            // Render the dendrogram
            this.render();

            return true;
        } catch (error) {
            console.error('Error loading dendrogram data:', error);
            this.showError('Failed to load dendrogram data: ' + error.message);
            return false;
        }
    }

    transformToHierarchy(flatData) {
        if (!Array.isArray(flatData) || flatData.length === 0) {
            throw new Error('Invalid data: expected non-empty array');
        }

        // Sort by position_order (highest first for CEO at top)
        const sorted = [...flatData].sort((a, b) =>
            (b.position_order || 0) - (a.position_order || 0)
        );

        // Create hierarchical clustering based on position_order ranges
        const hierarchy = {
            name: "Organization",
            children: [],
            type: "root"
        };

        // Define hierarchy levels based on position_order
        const levels = [
            { name: "CEO Level", min: 1000, max: 9999, type: "ceo" },
            { name: "Executive Level", min: 500, max: 999, type: "executive" },
            { name: "Management Level", min: 100, max: 499, type: "manager" },
            { name: "Specialist Level", min: 1, max: 99, type: "specialist" }
        ];

        // Group positions by level
        levels.forEach(level => {
            const levelPositions = sorted.filter(pos => {
                const order = pos.position_order || 0;
                return order >= level.min && order <= level.max;
            });

            if (levelPositions.length > 0) {
                const levelNode = {
                    name: level.name,
                    type: level.type,
                    children: [],
                    isLevel: true,
                    count: levelPositions.length
                };

                // Group by department within level
                const deptGroups = {};
                levelPositions.forEach(pos => {
                    const dept = pos.department || 'No Department';
                    if (!deptGroups[dept]) {
                        deptGroups[dept] = [];
                    }
                    deptGroups[dept].push({
                        name: pos.name,
                        type: level.type,
                        data: pos,
                        department: dept,
                        level_name: pos.level_name,
                        position_order: pos.position_order,
                        employee_count: pos.employee_count || 0,
                        outgoing_paths: pos.outgoing_paths || 0,
                        incoming_paths: pos.incoming_paths || 0,
                        isPosition: true
                    });
                });

                // Add department nodes if multiple departments
                if (Object.keys(deptGroups).length > 1) {
                    Object.entries(deptGroups).forEach(([deptName, positions]) => {
                        levelNode.children.push({
                            name: deptName,
                            type: level.type + "_dept",
                            children: positions,
                            isDepartment: true,
                            count: positions.length
                        });
                    });
                } else {
                    // Single department, add positions directly
                    levelNode.children = Object.values(deptGroups)[0];
                }

                hierarchy.children.push(levelNode);
            }
        });

        return hierarchy;
    }

    updateLayout() {
        const layoutWidth = this.config.width - this.config.margin.left - this.config.margin.right;
        const layoutHeight = this.config.height - this.config.margin.top - this.config.margin.bottom;

        switch (this.config.layout) {
            case 'cluster':
                this.treeLayout = d3.cluster()
                    .size([layoutWidth, layoutHeight]);
                break;
            case 'radial':
                this.treeLayout = d3.cluster()
                    .size([2 * Math.PI, Math.min(layoutWidth, layoutHeight) / 2]);
                break;
            default: // tree
                this.treeLayout = d3.tree()
                    .size([layoutWidth, layoutHeight]);
                break;
        }

        // Calculate node positions
        this.treeLayout(this.root);

        // For radial layout, convert to cartesian coordinates
        if (this.config.layout === 'radial') {
            this.root.each(d => {
                const angle = d.x;
                const radius = d.y;
                d.x = radius * Math.cos(angle - Math.PI / 2);
                d.y = radius * Math.sin(angle - Math.PI / 2);
            });
        }
    }

    render() {
        // Clear previous content
        this.g.selectAll("*").remove();

        // Set transform for the main group
        this.g.attr("transform",
            `translate(${this.config.margin.left},${this.config.margin.top})`);

        // Get nodes and links
        const nodes = this.root.descendants();
        const links = this.root.links();

        // Create links
        const link = this.g.selectAll(".link")
            .data(links)
            .enter().append("path")
            .attr("class", "link")
            .attr("fill", "none")
            .attr("stroke", this.colors.link)
            .attr("stroke-width", d => {
                if (d.target.data.isPosition) return 2;
                if (d.target.data.isDepartment) return 3;
                return 4;
            })
            .attr("stroke-opacity", 0.6)
            .attr("d", this.diagonal);

        // Create node groups
        const node = this.g.selectAll(".node")
            .data(nodes)
            .enter().append("g")
            .attr("class", d => `node ${d.data.type || 'default'}`)
            .attr("transform", d => `translate(${d.x},${d.y})`)
            .style("cursor", "pointer");

        // Add node circles/rectangles
        node.each((d, i, nodes) => {
            const nodeElement = d3.select(nodes[i]);

            if (d.data.isLevel) {
                // Level nodes as rounded rectangles
                nodeElement.append("rect")
                    .attr("width", 120)
                    .attr("height", 30)
                    .attr("x", -60)
                    .attr("y", -15)
                    .attr("rx", 15)
                    .attr("fill", this.getNodeColor(d.data.type))
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 2)
                    .style("filter", "url(#dropshadow)")
                    .style("opacity", 0.9);
            } else if (d.data.isDepartment) {
                // Department nodes as hexagons
                nodeElement.append("polygon")
                    .attr("points", this.hexagonPoints(20))
                    .attr("fill", this.getNodeColor(d.data.type))
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 2)
                    .style("filter", "url(#dropshadow)")
                    .style("opacity", 0.8);
            } else if (d.data.isPosition) {
                // Position nodes as circles
                nodeElement.append("circle")
                    .attr("r", this.config.nodeRadius)
                    .attr("fill", this.getNodeColor(d.data.type))
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 2)
                    .style("filter", "url(#dropshadow)");
            } else {
                // Root node
                nodeElement.append("circle")
                    .attr("r", 15)
                    .attr("fill", "#262626")
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 3)
                    .style("filter", "url(#dropshadow)");
            }
        });

        // Add text labels
        node.append("text")
            .attr("dy", d => {
                if (d.data.isLevel) return 5;
                if (d.data.isPosition) return 25;
                return 5;
            })
            .attr("x", d => {
                if (d.data.isLevel) return 0;
                return 0;
            })
            .style("text-anchor", "middle")
            .style("font-size", d => {
                if (d.data.isLevel) return "11px";
                if (d.data.isDepartment) return "10px";
                return `${this.config.fontSize}px`;
            })
            .style("font-weight", d => d.data.isLevel ? "bold" : "normal")
            .style("fill", this.colors.text)
            .text(d => {
                if (d.data.isLevel && d.data.count) {
                    return `${d.data.name} (${d.data.count})`;
                }
                return d.data.name;
            });

        // Add position details for position nodes
        node.filter(d => d.data.isPosition)
            .append("text")
            .attr("dy", 40)
            .attr("x", 0)
            .style("text-anchor", "middle")
            .style("font-size", "9px")
            .style("fill", "#666666")
            .text(d => {
                const data = d.data.data;
                return `Order: ${data.position_order || 0} | Employees: ${data.employee_count || 0}`;
            });

        // Add event handlers
        this.addEventHandlers(node);

        // Center the dendrogram
        setTimeout(() => this.centerDendrogram(), 100);
    }

    addEventHandlers(node) {
        const self = this;

        node.on("click", function(event, d) {
            event.stopPropagation();
            self.selectNode(d, this);
        });

        if (this.config.enableTooltip) {
            node.on("mouseover", function(event, d) {
                self.showTooltip(event, d);
                d3.select(this).select("circle, rect, polygon")
                    .transition()
                    .duration(200)
                    .attr("stroke-width", 4)
                    .style("filter", "url(#dropshadow) brightness(1.1)");
            })
            .on("mousemove", function(event, d) {
                self.moveTooltip(event);
            })
            .on("mouseout", function(event, d) {
                self.hideTooltip();
                d3.select(this).select("circle, rect, polygon")
                    .transition()
                    .duration(200)
                    .attr("stroke-width", d.data.isLevel ? 2 : 2)
                    .style("filter", "url(#dropshadow)");
            });
        }

        if (this.config.enableDrag) {
            const drag = d3.drag()
                .on("start", function(event, d) {
                    d3.select(this).raise();
                })
                .on("drag", function(event, d) {
                    d.x = event.x;
                    d.y = event.y;
                    d3.select(this).attr("transform", `translate(${d.x},${d.y})`);
                });

            node.call(drag);
        }
    }

    selectNode(node, element) {
        // Remove previous selection
        this.g.selectAll(".node").classed("selected", false);
        this.g.selectAll(".link").classed("highlighted", false);

        // Add selection to current node
        d3.select(element).classed("selected", true);
        this.selectedNode = node;

        // Highlight path to root
        this.highlightPath(node);

        // Trigger selection event
        this.onNodeSelected(node);
    }

    highlightPath(node) {
        const pathNodes = [];
        let current = node;
        while (current) {
            pathNodes.push(current);
            current = current.parent;
        }

        // Highlight links in path
        this.g.selectAll(".link")
            .classed("highlighted", d => {
                return pathNodes.includes(d.source) && pathNodes.includes(d.target);
            });
    }

    showTooltip(event, d) {
        if (!this.tooltip) return;

        let content = `<strong>${d.data.name}</strong>`;

        if (d.data.isPosition && d.data.data) {
            const data = d.data.data;
            content += `<br><strong>Department:</strong> ${data.department || 'N/A'}`;
            content += `<br><strong>Level:</strong> ${data.level_name || 'N/A'}`;
            content += `<br><strong>Order:</strong> ${data.position_order || 0}`;
            content += `<br><strong>Employees:</strong> ${data.employee_count || 0}`;
            content += `<br><strong>Career Paths:</strong> ${data.outgoing_paths || 0} out, ${data.incoming_paths || 0} in`;
        } else if (d.data.isLevel) {
            content += `<br><strong>Positions:</strong> ${d.data.count || 0}`;
        } else if (d.data.isDepartment) {
            content += `<br><strong>Positions:</strong> ${d.data.count || 0}`;
        }

        this.tooltip.html(content)
            .style("opacity", 1)
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 10) + "px");
    }

    moveTooltip(event) {
        if (!this.tooltip) return;

        this.tooltip
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 10) + "px");
    }

    hideTooltip() {
        if (!this.tooltip) return;

        this.tooltip.style("opacity", 0);
    }

    getNodeColor(type) {
        switch (type) {
            case 'ceo': return this.colors.ceo;
            case 'executive': return this.colors.executive;
            case 'manager': return this.colors.manager;
            case 'specialist': return this.colors.specialist;
            default: return this.colors.executive;
        }
    }

    hexagonPoints(radius) {
        const points = [];
        for (let i = 0; i < 6; i++) {
            const angle = (i * Math.PI) / 3;
            const x = radius * Math.cos(angle);
            const y = radius * Math.sin(angle);
            points.push(`${x},${y}`);
        }
        return points.join(' ');
    }

    centerDendrogram() {
        if (!this.root) return;

        const bounds = this.g.node().getBBox();
        const fullWidth = this.config.width;
        const fullHeight = this.config.height;
        const width = bounds.width;
        const height = bounds.height;
        const midX = bounds.x + width / 2;
        const midY = bounds.y + height / 2;

        const scale = 0.8 / Math.max(width / fullWidth, height / fullHeight);
        const translate = [fullWidth / 2 - scale * midX, fullHeight / 2 - scale * midY];

        this.svg.transition()
            .duration(this.config.animationDuration)
            .call(this.zoom.transform, d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale));
    }

    // Zoom control methods
    zoomIn() {
        this.svg.transition().duration(300).call(
            this.zoom.scaleBy, 1.5
        );
    }

    zoomOut() {
        this.svg.transition().duration(300).call(
            this.zoom.scaleBy, 1 / 1.5
        );
    }

    resetZoom() {
        this.centerDendrogram();
    }

    // Layout switching
    setLayout(layout) {
        if (['tree', 'cluster', 'radial'].includes(layout)) {
            this.config.layout = layout;
            if (this.root) {
                this.updateLayout();
                this.render();
            }
        }
    }

    // Export functionality
    exportAsPNG(filename = 'career-dendrogram.png') {
        const svgElement = this.svg.node();
        const svgData = new XMLSerializer().serializeToString(svgElement);

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const img = new Image();

        img.onload = () => {
            canvas.width = img.width;
            canvas.height = img.height;
            context.drawImage(img, 0, 0);

            const link = document.createElement('a');
            link.download = filename;
            link.href = canvas.toDataURL();
            link.click();
        };

        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    }

    // Event callbacks (can be overridden)
    onNodeSelected(node) {
        console.log('Node selected:', node);

        // Dispatch custom event
        const event = new CustomEvent('nodeSelected', {
            detail: { node: node, data: node.data }
        });
        document.dispatchEvent(event);
    }

    // Error handling
    showError(message) {
        this.container.html(`
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <h6>Dendrogram Error</h6>
                <p>${message}</p>
                <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Reload
                </button>
            </div>
        `);
    }

    // Cleanup
    destroy() {
        if (this.tooltip) {
            this.tooltip.remove();
        }
        this.container.selectAll("*").remove();
        console.log('CareerDendrogram destroyed');
    }
}

// Make available globally
window.CareerDendrogram = CareerDendrogram;
/**
 * Organization Chart Visualization
 * Interactive hierarchical organization chart using D3.js v7
 * Displays company position structure with parent-child relationships
 */

class OrgChartVisualization {
    constructor(containerId, options = {}) {
        this.container = d3.select(`#${containerId}`);
        this.containerId = containerId;

        // Configuration
        this.config = {
            width: options.width || 1400,
            height: options.height || 900,
            margin: { top: 40, right: 90, bottom: 40, left: 90 },
            nodeSize: { width: 180, height: 60 },
            nodeRadius: options.nodeRadius || 10,
            fontSize: options.fontSize || 13,
            linkDistance: options.linkDistance || 180,
            enableZoom: options.enableZoom !== false,
            enableTooltip: options.enableTooltip !== false,
            layout: options.layout || 'vertical', // vertical, horizontal, radial
            colorMode: options.colorMode || 'department', // department, level, hiring
            animationDuration: options.animationDuration || 750
        };

        // Internal state
        this.svg = null;
        this.g = null;
        this.zoom = null;
        this.root = null;
        this.treeLayout = null;
        this.tooltip = null;
        this.selectedNode = null;
        this.i = 0; // For unique IDs

        // Color schemes
        this.colorSchemes = {
            department: {
                'Executive': '#722ed1',     // Purple
                'HR Management': '#1890ff', // Blue
                'Marketing': '#52c41a',     // Green
                'Operations': '#fa8c16',    // Orange
                'Finance': '#eb2f96',       // Pink
                'IT': '#13c2c2',            // Cyan
                'Sales': '#faad14',         // Gold
                'No Department': '#8c8c8c', // Gray
                'default': '#bfbfbf'
            },
            level: {
                'Senior': '#722ed1',        // Purple
                'Intermediate': '#1890ff',  // Blue
                'Junior': '#52c41a',        // Green
                'No Level': '#8c8c8c',
                'default': '#bfbfbf'
            },
            hiring: {
                'hiring': '#52c41a',        // Green (hiring)
                'not_hiring': '#8c8c8c',    // Gray (not hiring)
                'leadership': '#722ed1',    // Purple (leadership)
                'default': '#bfbfbf'
            }
        };

        this.init();
    }

    init() {
        this.createSVG();
        this.setupZoom();
        this.setupTooltip();
        console.log('OrgChartVisualization initialized successfully');
    }

    createSVG() {
        // Clear existing content
        this.container.selectAll("*").remove();

        // Create main SVG
        this.svg = this.container
            .append("svg")
            .attr("width", this.config.width)
            .attr("height", this.config.height)
            .attr("class", "org-chart-svg")
            .style("background-color", "#fafafa");

        // Add gradient definitions
        const defs = this.svg.append("defs");

        // Node gradient
        const nodeGradient = defs.append("linearGradient")
            .attr("id", "nodeGradient")
            .attr("x1", "0%")
            .attr("y1", "0%")
            .attr("x2", "0%")
            .attr("y2", "100%");

        nodeGradient.append("stop")
            .attr("offset", "0%")
            .attr("stop-color", "#ffffff")
            .attr("stop-opacity", 1);

        nodeGradient.append("stop")
            .attr("offset", "100%")
            .attr("stop-color", "#f5f5f5")
            .attr("stop-opacity", 1);

        // Drop shadow filter
        const filter = defs.append("filter")
            .attr("id", "dropShadow")
            .attr("height", "130%");

        filter.append("feGaussianBlur")
            .attr("in", "SourceAlpha")
            .attr("stdDeviation", 3);

        filter.append("feOffset")
            .attr("dx", 0)
            .attr("dy", 2)
            .attr("result", "offsetblur");

        filter.append("feComponentTransfer")
            .append("feFuncA")
            .attr("type", "linear")
            .attr("slope", 0.2);

        const feMerge = filter.append("feMerge");
        feMerge.append("feMergeNode");
        feMerge.append("feMergeNode")
            .attr("in", "SourceGraphic");

        // Create main group for zoom/pan
        this.g = this.svg
            .append("g")
            .attr("class", "org-chart-container")
            .attr("transform", `translate(${this.config.margin.left},${this.config.margin.top})`);
    }

    setupZoom() {
        if (!this.config.enableZoom) return;

        this.zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on("zoom", (event) => {
                this.g.attr("transform", event.transform);
            });

        this.svg.call(this.zoom);
    }

    setupTooltip() {
        if (!this.config.enableTooltip) return;

        this.tooltip = d3.select("body")
            .append("div")
            .attr("class", "org-chart-tooltip")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background-color", "rgba(0, 0, 0, 0.85)")
            .style("color", "#fff")
            .style("padding", "12px 16px")
            .style("border-radius", "6px")
            .style("font-size", "13px")
            .style("box-shadow", "0 4px 12px rgba(0,0,0,0.15)")
            .style("z-index", "10000")
            .style("max-width", "300px")
            .style("pointer-events", "none");
    }

    loadData(data) {
        try {
            if (!data || !data.name) {
                console.error('Invalid data structure');
                return false;
            }

            // Create hierarchical structure
            this.root = d3.hierarchy(data, d => d.children);

            // Assign unique IDs
            this.root.descendants().forEach((d, i) => {
                d.id = d.id || i;
                d.data._children = d.children;
            });

            // Initial collapsed state for large trees
            if (this.root.descendants().length > 20) {
                this.root.children.forEach(this.collapseNode.bind(this));
            }

            this.update(this.root);
            this.centerChart();

            return true;
        } catch (error) {
            console.error('Error loading data:', error);
            return false;
        }
    }

    update(source) {
        // Create tree layout based on selected layout type
        const treeWidth = this.config.width - this.config.margin.left - this.config.margin.right;
        const treeHeight = this.config.height - this.config.margin.top - this.config.margin.bottom;

        if (this.config.layout === 'horizontal') {
            this.treeLayout = d3.tree().size([treeHeight, treeWidth]);
        } else if (this.config.layout === 'radial') {
            this.treeLayout = d3.tree()
                .size([2 * Math.PI, Math.min(treeWidth, treeHeight) / 2 - 100])
                .separation((a, b) => (a.parent == b.parent ? 1 : 2) / a.depth);
        } else {
            // Vertical layout (default)
            this.treeLayout = d3.tree().size([treeWidth, treeHeight]);
        }

        // Compute tree layout
        const treeData = this.treeLayout(this.root);
        const nodes = treeData.descendants();
        const links = treeData.links();

        // Normalize vertical spacing for vertical/horizontal layouts
        if (this.config.layout !== 'radial') {
            nodes.forEach(d => {
                if (this.config.layout === 'vertical') {
                    d.y = d.depth * this.config.linkDistance;
                } else {
                    d.x = d.depth * this.config.linkDistance;
                }
            });
        }

        // Update nodes
        this.updateNodes(nodes, source);

        // Update links
        this.updateLinks(links, source);
    }

    updateNodes(nodes, source) {
        const self = this;

        // Bind data
        const node = this.g.selectAll('g.node')
            .data(nodes, d => d.id || (d.id = ++this.i));

        // Enter new nodes
        const nodeEnter = node.enter().append('g')
            .attr('class', 'node')
            .attr('transform', d => this.getNodeTransform(source))
            .on('click', (event, d) => this.toggleNode(event, d))
            .on('mouseover', (event, d) => {
                this.showTooltip(event, d);
                // Highlight on hover
                d3.select(event.currentTarget).select('rect.node-bg')
                    .transition().duration(200)
                    .style('stroke-width', 4)
                    .style('opacity', 1);
                d3.select(event.currentTarget).select('rect.node-accent')
                    .transition().duration(200)
                    .attr('height', 10);
            })
            .on('mouseout', (event) => {
                this.hideTooltip();
                // Remove highlight
                d3.select(event.currentTarget).select('rect.node-bg')
                    .transition().duration(200)
                    .style('stroke-width', 3)
                    .style('opacity', 0.95);
                d3.select(event.currentTarget).select('rect.node-accent')
                    .transition().duration(200)
                    .attr('height', 8);
            })
            .style('cursor', 'pointer');

        // Add gradient background rectangle
        nodeEnter.append('rect')
            .attr('class', 'node-bg')
            .attr('width', this.config.nodeSize.width)
            .attr('height', this.config.nodeSize.height)
            .attr('x', -this.config.nodeSize.width / 2)
            .attr('y', -this.config.nodeSize.height / 2)
            .attr('rx', this.config.nodeRadius)
            .attr('ry', this.config.nodeRadius)
            .style('fill', 'url(#nodeGradient)')
            .style('stroke', d => this.getNodeStrokeColor(d))
            .style('stroke-width', 3)
            .style('filter', 'url(#dropShadow)')
            .style('opacity', 0.95);

        // Add colored accent bar at top
        nodeEnter.append('rect')
            .attr('class', 'node-accent')
            .attr('width', this.config.nodeSize.width)
            .attr('height', 8)
            .attr('x', -this.config.nodeSize.width / 2)
            .attr('y', -this.config.nodeSize.height / 2)
            .attr('rx', this.config.nodeRadius)
            .style('fill', d => this.getNodeColor(d))
            .style('opacity', 0.9);

        // Add position name text
        nodeEnter.append('text')
            .attr('class', 'node-title')
            .attr('dy', '-1.2em')
            .attr('text-anchor', 'middle')
            .style('fill', '#262626')
            .style('font-size', (this.config.fontSize + 1) + 'px')
            .style('font-weight', '700')
            .style('pointer-events', 'none')
            .text(d => this.truncateText(d.data.name, 20));

        // Add quest level badge
        nodeEnter.append('text')
            .attr('class', 'node-level')
            .attr('dy', '0.2em')
            .attr('text-anchor', 'middle')
            .style('fill', '#1890ff')
            .style('font-size', (this.config.fontSize - 2) + 'px')
            .style('font-weight', '600')
            .style('pointer-events', 'none')
            .text(d => {
                const level = d.data.level_name;
                if (level && level !== '-' && level !== 'No Level') {
                    return `📊 ${level}`;
                }
                return '';
            });

        // Add department text
        nodeEnter.append('text')
            .attr('class', 'node-dept')
            .attr('dy', '1.5em')
            .attr('text-anchor', 'middle')
            .style('fill', '#8c8c8c')
            .style('font-size', (this.config.fontSize - 2) + 'px')
            .style('font-weight', '500')
            .style('pointer-events', 'none')
            .style('font-style', 'italic')
            .text(d => this.truncateText(d.data.department || '', 18));

        // Add subordinate count indicator
        nodeEnter.filter(d => d.data.subordinate_count > 0)
            .append('circle')
            .attr('cx', this.config.nodeSize.width / 2 - 15)
            .attr('cy', -this.config.nodeSize.height / 2 + 15)
            .attr('r', 12)
            .style('fill', '#1890ff')
            .style('stroke', '#fff')
            .style('stroke-width', 2);

        nodeEnter.filter(d => d.data.subordinate_count > 0)
            .append('text')
            .attr('x', this.config.nodeSize.width / 2 - 15)
            .attr('y', -this.config.nodeSize.height / 2 + 15)
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .style('fill', '#fff')
            .style('font-size', '10px')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none')
            .text(d => d.data.subordinate_count);

        // Add expand/collapse indicator
        nodeEnter.filter(d => d._children || d.children)
            .append('circle')
            .attr('cy', this.config.nodeSize.height / 2 + 15)
            .attr('r', 10)
            .style('fill', '#52c41a')
            .style('stroke', '#fff')
            .style('stroke-width', 2);

        nodeEnter.filter(d => d._children || d.children)
            .append('text')
            .attr('y', this.config.nodeSize.height / 2 + 15)
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .style('fill', '#fff')
            .style('font-size', '12px')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none')
            .text(d => d._children ? '+' : '−');

        // Transition to new position
        const nodeUpdate = nodeEnter.merge(node);

        nodeUpdate.transition()
            .duration(this.config.animationDuration)
            .attr('transform', d => this.getNodeTransform(d));

        // Update background rectangle
        nodeUpdate.select('rect.node-bg')
            .style('stroke', d => this.getNodeStrokeColor(d));

        // Update colored accent bar
        nodeUpdate.select('rect.node-accent')
            .style('fill', d => this.getNodeColor(d));

        // Update text content
        nodeUpdate.select('text.node-title')
            .text(d => this.truncateText(d.data.name, 20));

        nodeUpdate.select('text.node-level')
            .text(d => {
                const level = d.data.level_name;
                if (level && level !== '-' && level !== 'No Level') {
                    return `📊 ${level}`;
                }
                return '';
            });

        nodeUpdate.select('text.node-dept')
            .text(d => this.truncateText(d.data.department || '', 18));

        // Update expand/collapse indicator
        nodeUpdate.selectAll('circle').filter(function() {
            return d3.select(this).style('fill') === 'rgb(82, 196, 26)';
        }).each(function(d) {
            d3.select(this.nextSibling).text(d._children ? '+' : '−');
        });

        // Exit old nodes
        const nodeExit = node.exit().transition()
            .duration(this.config.animationDuration)
            .attr('transform', d => this.getNodeTransform(source))
            .remove();

        nodeExit.select('rect')
            .attr('width', 0)
            .attr('height', 0);

        nodeExit.select('text')
            .style('fill-opacity', 0);
    }

    updateLinks(links, source) {
        const self = this;

        // Bind data
        const link = this.g.selectAll('path.link')
            .data(links, d => d.target.id);

        // Enter new links
        const linkEnter = link.enter().insert('path', 'g')
            .attr('class', 'link')
            .attr('d', d => this.getLinkPath(source, source))
            .style('fill', 'none')
            .style('stroke', '#d9d9d9')
            .style('stroke-width', 2)
            .style('opacity', 0.6);

        // Transition to new position
        linkEnter.merge(link).transition()
            .duration(this.config.animationDuration)
            .attr('d', d => this.getLinkPath(d.source, d.target))
            .style('opacity', 1);

        // Exit old links
        link.exit().transition()
            .duration(this.config.animationDuration)
            .attr('d', d => this.getLinkPath(source, source))
            .style('opacity', 0)
            .remove();
    }

    getNodeTransform(d) {
        if (this.config.layout === 'radial') {
            const angle = d.x;
            const radius = d.y;
            return `translate(${radius * Math.cos(angle - Math.PI / 2) + this.config.width / 2},${radius * Math.sin(angle - Math.PI / 2) + this.config.height / 2})`;
        } else if (this.config.layout === 'horizontal') {
            return `translate(${d.y},${d.x})`;
        } else {
            return `translate(${d.x},${d.y})`;
        }
    }

    getLinkPath(source, target) {
        if (this.config.layout === 'radial') {
            return d3.linkRadial()
                .angle(d => d.x)
                .radius(d => d.y)
                ({ source, target });
        } else if (this.config.layout === 'horizontal') {
            return d3.linkHorizontal()
                .x(d => d.y)
                .y(d => d.x)
                ({ source, target });
        } else {
            return d3.linkVertical()
                .x(d => d.x)
                .y(d => d.y)
                ({ source, target });
        }
    }

    getNodeColor(d) {
        const colorScheme = this.colorSchemes[this.config.colorMode];

        if (this.config.colorMode === 'department') {
            return colorScheme[d.data.department] || colorScheme.default;
        } else if (this.config.colorMode === 'level') {
            return colorScheme[d.data.level_name] || colorScheme.default;
        } else if (this.config.colorMode === 'hiring') {
            if (d.data.is_leadership) return colorScheme.leadership;
            return d.data.is_hiring ? colorScheme.hiring : colorScheme.not_hiring;
        }

        return colorScheme.default || '#bfbfbf';
    }

    getNodeStrokeColor(d) {
        if (this.selectedNode && this.selectedNode.id === d.id) {
            return '#ff4d4f';
        }
        return this.getNodeColor(d);
    }

    toggleNode(event, d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else {
            d.children = d._children;
            d._children = null;
        }

        this.selectedNode = d;
        this.update(d);

        // Dispatch event for external listeners
        this.dispatchNodeEvent('nodeClicked', d);
    }

    collapseNode(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
            d._children.forEach(child => this.collapseNode(child));
        }
    }

    expandNode(d) {
        if (d._children) {
            d.children = d._children;
            d._children = null;
        }
        if (d.children) {
            d.children.forEach(child => this.expandNode(child));
        }
    }

    showTooltip(event, d) {
        if (!this.tooltip) return;

        const html = `
            <div style="font-weight: 600; margin-bottom: 8px; font-size: 14px;">${d.data.name}</div>
            <div style="margin-bottom: 4px;"><strong>Department:</strong> ${d.data.department}</div>
            <div style="margin-bottom: 4px;"><strong>Level:</strong> ${d.data.level_name}</div>
            ${d.data.subordinate_count ? `<div style="margin-bottom: 4px;"><strong>Subordinates:</strong> ${d.data.subordinate_count}</div>` : ''}
            ${d.data.is_leadership ? '<div style="color: #faad14;">⭐ Leadership Position</div>' : ''}
            ${d.data.is_hiring ? '<div style="color: #52c41a;">✓ Currently Hiring</div>' : ''}
        `;

        this.tooltip
            .style("visibility", "visible")
            .html(html);
    }

    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.style("visibility", "hidden");
        }
    }

    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    setLayout(layoutType) {
        this.config.layout = layoutType;
        if (this.root) {
            this.update(this.root);
            this.centerChart();
        }
    }

    setColorMode(colorMode) {
        this.config.colorMode = colorMode;
        if (this.root) {
            this.update(this.root);
        }
    }

    centerChart() {
        if (!this.svg || !this.g) return;

        const bounds = this.g.node().getBBox();
        const fullWidth = this.config.width;
        const fullHeight = this.config.height;
        const width = bounds.width;
        const height = bounds.height;
        const midX = bounds.x + width / 2;
        const midY = bounds.y + height / 2;

        if (width === 0 || height === 0) return;

        const scale = 0.9 / Math.max(width / fullWidth, height / fullHeight);
        const translate = [fullWidth / 2 - scale * midX, fullHeight / 2 - scale * midY];

        this.svg.transition()
            .duration(this.config.animationDuration)
            .call(this.zoom.transform, d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale));
    }

    expandAll() {
        if (this.root) {
            this.expandNode(this.root);
            this.update(this.root);
        }
    }

    collapseAll() {
        if (this.root && this.root.children) {
            this.root.children.forEach(child => this.collapseNode(child));
            this.update(this.root);
        }
    }

    exportAsPNG(filename) {
        const svgElement = this.svg.node();
        const svgData = new XMLSerializer().serializeToString(svgElement);
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        const img = new Image();

        canvas.width = this.config.width;
        canvas.height = this.config.height;

        img.onload = () => {
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);

            canvas.toBlob(blob => {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename || 'org-chart.png';
                link.click();
                URL.revokeObjectURL(url);
            });
        };

        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    }

    dispatchNodeEvent(eventName, node) {
        const event = new CustomEvent(eventName, {
            detail: {
                node: node,
                data: node.data
            }
        });
        document.dispatchEvent(event);
    }

    destroy() {
        if (this.tooltip) {
            this.tooltip.remove();
        }
        this.container.selectAll("*").remove();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OrgChartVisualization;
}

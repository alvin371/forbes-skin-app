/**
 * Career Path Clustering Algorithm
 * Implements hierarchical clustering for career progression paths
 * Based on position hierarchy, department similarity, and level progression
 */

class CareerClustering {
    constructor(options = {}) {
        this.config = {
            // Clustering weights
            positionOrderWeight: options.positionOrderWeight || 0.6,
            departmentWeight: options.departmentWeight || 0.2,
            levelWeight: options.levelWeight || 0.2,

            // Distance calculation parameters
            maxPositionOrder: options.maxPositionOrder || 10000,
            departmentSimilarityThreshold: options.departmentSimilarityThreshold || 0.8,

            // Path generation parameters
            maxPathLength: options.maxPathLength || 5,
            minPathDistance: options.minPathDistance || 0.1,
            maxPathDistance: options.maxPathDistance || 0.9,

            // Clustering parameters
            linkageMethod: options.linkageMethod || 'ward', // ward, single, complete, average
            distanceMetric: options.distanceMetric || 'euclidean',

            // Career progression rules
            allowLateralMoves: options.allowLateralMoves !== false,
            allowSkipLevel: options.allowSkipLevel || false,
            allowDepartmentChange: options.allowDepartmentChange !== false
        };

        this.positions = [];
        this.clusters = [];
        this.distanceMatrix = [];
        this.careerPaths = [];
    }

    /**
     * Load position data and perform initial clustering
     */
    loadPositions(positions) {
        if (!Array.isArray(positions) || positions.length === 0) {
            throw new Error('Invalid positions data: expected non-empty array');
        }

        this.positions = positions.map((pos, index) => ({
            id: pos.id || index,
            name: pos.name || 'Unknown Position',
            department: pos.department || 'No Department',
            level_name: pos.level_name || 'No Level',
            position_order: parseInt(pos.position_order) || 0,
            level_id: pos.level_id || null,
            employee_count: parseInt(pos.employee_count) || 0,
            outgoing_paths: parseInt(pos.outgoing_paths) || 0,
            incoming_paths: parseInt(pos.incoming_paths) || 0,
            is_leadership: pos.is_leadership || false,
            original: pos
        }));

        console.log(`Loaded ${this.positions.length} positions for clustering`);

        // Normalize position data for clustering
        this.normalizeData();

        // Calculate distance matrix
        this.calculateDistanceMatrix();

        // Perform hierarchical clustering
        this.performClustering();

        // Generate career paths
        this.generateCareerPaths();

        return {
            positions: this.positions,
            clusters: this.clusters,
            paths: this.careerPaths,
            distanceMatrix: this.distanceMatrix
        };
    }

    /**
     * Normalize position data for clustering calculations
     */
    normalizeData() {
        // Calculate normalization parameters
        const orderValues = this.positions.map(p => p.position_order);
        const maxOrder = Math.max(...orderValues);
        const minOrder = Math.min(...orderValues);

        // Normalize each position
        this.positions.forEach(pos => {
            // Normalized position order (0-1, higher order = higher value)
            pos.normalizedOrder = maxOrder > minOrder ?
                (pos.position_order - minOrder) / (maxOrder - minOrder) : 0.5;

            // Department similarity vector (using department name similarity)
            pos.departmentVector = this.getDepartmentVector(pos.department);

            // Level hierarchy value (higher level = higher value)
            pos.levelValue = this.getLevelValue(pos.level_name, pos.position_order);

            // Leadership bonus
            pos.leadershipValue = pos.is_leadership ? 1 : 0;
        });

        console.log('Position data normalized for clustering');
    }

    /**
     * Generate department similarity vector
     */
    getDepartmentVector(department) {
        const departments = [...new Set(this.positions.map(p => p.department))];
        const vector = new Array(departments.length).fill(0);
        const index = departments.indexOf(department);
        if (index >= 0) {
            vector[index] = 1;
        }
        return vector;
    }

    /**
     * Calculate level hierarchy value
     */
    getLevelValue(levelName, positionOrder) {
        // Define level hierarchy based on common patterns
        const levelHierarchy = {
            'Senior': 0.9,
            'Intermediate': 0.6,
            'Junior': 0.3,
            'Lead': 0.8,
            'Manager': 0.7,
            'Director': 0.85,
            'VP': 0.95,
            'C-Level': 1.0,
            'CEO': 1.0
        };

        // Check for level keywords
        for (const [level, value] of Object.entries(levelHierarchy)) {
            if (levelName.toLowerCase().includes(level.toLowerCase())) {
                return value;
            }
        }

        // Fallback to position order based estimation
        if (positionOrder >= 1000) return 1.0; // CEO level
        if (positionOrder >= 500) return 0.8;  // Executive level
        if (positionOrder >= 100) return 0.6;  // Manager level
        return 0.4; // Specialist level
    }

    /**
     * Calculate distance matrix between all positions
     */
    calculateDistanceMatrix() {
        const n = this.positions.length;
        this.distanceMatrix = Array(n).fill(null).map(() => Array(n).fill(0));

        for (let i = 0; i < n; i++) {
            for (let j = i + 1; j < n; j++) {
                const distance = this.calculateDistance(this.positions[i], this.positions[j]);
                this.distanceMatrix[i][j] = distance;
                this.distanceMatrix[j][i] = distance;
            }
        }

        console.log('Distance matrix calculated');
    }

    /**
     * Calculate distance between two positions
     */
    calculateDistance(pos1, pos2) {
        // Position order distance (normalized)
        const orderDistance = Math.abs(pos1.normalizedOrder - pos2.normalizedOrder);

        // Department similarity (cosine similarity)
        const deptSimilarity = this.cosineSimilarity(pos1.departmentVector, pos2.departmentVector);
        const deptDistance = 1 - deptSimilarity;

        // Level distance
        const levelDistance = Math.abs(pos1.levelValue - pos2.levelValue);

        // Leadership distance
        const leadershipDistance = Math.abs(pos1.leadershipValue - pos2.leadershipValue);

        // Weighted euclidean distance
        const distance = Math.sqrt(
            Math.pow(orderDistance * this.config.positionOrderWeight, 2) +
            Math.pow(deptDistance * this.config.departmentWeight, 2) +
            Math.pow(levelDistance * this.config.levelWeight, 2) +
            Math.pow(leadershipDistance * 0.1, 2)
        );

        return distance;
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    cosineSimilarity(vecA, vecB) {
        if (vecA.length !== vecB.length) return 0;

        let dotProduct = 0;
        let normA = 0;
        let normB = 0;

        for (let i = 0; i < vecA.length; i++) {
            dotProduct += vecA[i] * vecB[i];
            normA += vecA[i] * vecA[i];
            normB += vecB[i] * vecB[i];
        }

        if (normA === 0 || normB === 0) return 0;

        return dotProduct / (Math.sqrt(normA) * Math.sqrt(normB));
    }

    /**
     * Perform hierarchical clustering using specified linkage method
     */
    performClustering() {
        const n = this.positions.length;

        // Initialize clusters (each position is its own cluster)
        this.clusters = this.positions.map((pos, index) => ({
            id: index,
            positions: [index],
            centroid: this.calculateCentroid([index]),
            level: 0,
            children: null,
            parent: null
        }));

        // Track cluster merge history for dendrogram
        const mergeHistory = [];
        let nextClusterId = n;

        // Perform agglomerative clustering
        while (this.clusters.length > 1) {
            // Find closest cluster pair
            let minDistance = Infinity;
            let mergeI = -1;
            let mergeJ = -1;

            for (let i = 0; i < this.clusters.length; i++) {
                for (let j = i + 1; j < this.clusters.length; j++) {
                    const distance = this.calculateClusterDistance(this.clusters[i], this.clusters[j]);
                    if (distance < minDistance) {
                        minDistance = distance;
                        mergeI = i;
                        mergeJ = j;
                    }
                }
            }

            // Merge closest clusters
            const clusterI = this.clusters[mergeI];
            const clusterJ = this.clusters[mergeJ];

            const mergedCluster = {
                id: nextClusterId++,
                positions: [...clusterI.positions, ...clusterJ.positions],
                centroid: this.calculateCentroid([...clusterI.positions, ...clusterJ.positions]),
                level: Math.max(clusterI.level, clusterJ.level) + 1,
                children: [clusterI, clusterJ],
                parent: null,
                distance: minDistance
            };

            // Update parent references
            clusterI.parent = mergedCluster;
            clusterJ.parent = mergedCluster;

            // Record merge for dendrogram
            mergeHistory.push({
                clusterId: mergedCluster.id,
                leftChild: clusterI.id,
                rightChild: clusterJ.id,
                distance: minDistance,
                level: mergedCluster.level
            });

            // Remove merged clusters and add new cluster
            this.clusters.splice(Math.max(mergeI, mergeJ), 1);
            this.clusters.splice(Math.min(mergeI, mergeJ), 1);
            this.clusters.push(mergedCluster);
        }

        console.log(`Hierarchical clustering completed. Root cluster contains ${this.clusters[0].positions.length} positions`);

        return {
            rootCluster: this.clusters[0],
            mergeHistory: mergeHistory
        };
    }

    /**
     * Calculate distance between two clusters based on linkage method
     */
    calculateClusterDistance(clusterA, clusterB) {
        switch (this.config.linkageMethod) {
            case 'single':
                return this.singleLinkage(clusterA, clusterB);
            case 'complete':
                return this.completeLinkage(clusterA, clusterB);
            case 'average':
                return this.averageLinkage(clusterA, clusterB);
            case 'ward':
            default:
                return this.wardLinkage(clusterA, clusterB);
        }
    }

    singleLinkage(clusterA, clusterB) {
        let minDistance = Infinity;
        for (const posA of clusterA.positions) {
            for (const posB of clusterB.positions) {
                const distance = this.distanceMatrix[posA][posB];
                minDistance = Math.min(minDistance, distance);
            }
        }
        return minDistance;
    }

    completeLinkage(clusterA, clusterB) {
        let maxDistance = 0;
        for (const posA of clusterA.positions) {
            for (const posB of clusterB.positions) {
                const distance = this.distanceMatrix[posA][posB];
                maxDistance = Math.max(maxDistance, distance);
            }
        }
        return maxDistance;
    }

    averageLinkage(clusterA, clusterB) {
        let totalDistance = 0;
        let count = 0;
        for (const posA of clusterA.positions) {
            for (const posB of clusterB.positions) {
                totalDistance += this.distanceMatrix[posA][posB];
                count++;
            }
        }
        return totalDistance / count;
    }

    wardLinkage(clusterA, clusterB) {
        // Ward's method: minimize within-cluster sum of squares
        const mergedPositions = [...clusterA.positions, ...clusterB.positions];
        const mergedCentroid = this.calculateCentroid(mergedPositions);

        let mergedVariance = 0;
        for (const posIndex of mergedPositions) {
            const pos = this.positions[posIndex];
            mergedVariance += this.calculateVariance(pos, mergedCentroid);
        }

        let clusterAVariance = 0;
        for (const posIndex of clusterA.positions) {
            const pos = this.positions[posIndex];
            clusterAVariance += this.calculateVariance(pos, clusterA.centroid);
        }

        let clusterBVariance = 0;
        for (const posIndex of clusterB.positions) {
            const pos = this.positions[posIndex];
            clusterBVariance += this.calculateVariance(pos, clusterB.centroid);
        }

        return mergedVariance - clusterAVariance - clusterBVariance;
    }

    /**
     * Calculate cluster centroid
     */
    calculateCentroid(positionIndices) {
        if (positionIndices.length === 0) return null;

        const centroid = {
            normalizedOrder: 0,
            levelValue: 0,
            leadershipValue: 0,
            departmentVector: new Array(this.positions[0].departmentVector.length).fill(0)
        };

        for (const index of positionIndices) {
            const pos = this.positions[index];
            centroid.normalizedOrder += pos.normalizedOrder;
            centroid.levelValue += pos.levelValue;
            centroid.leadershipValue += pos.leadershipValue;

            for (let i = 0; i < pos.departmentVector.length; i++) {
                centroid.departmentVector[i] += pos.departmentVector[i];
            }
        }

        const count = positionIndices.length;
        centroid.normalizedOrder /= count;
        centroid.levelValue /= count;
        centroid.leadershipValue /= count;

        for (let i = 0; i < centroid.departmentVector.length; i++) {
            centroid.departmentVector[i] /= count;
        }

        return centroid;
    }

    /**
     * Calculate variance from centroid
     */
    calculateVariance(position, centroid) {
        if (!centroid) return 0;

        const orderVar = Math.pow(position.normalizedOrder - centroid.normalizedOrder, 2);
        const levelVar = Math.pow(position.levelValue - centroid.levelValue, 2);
        const leadershipVar = Math.pow(position.leadershipValue - centroid.leadershipValue, 2);

        return orderVar + levelVar + leadershipVar;
    }

    /**
     * Generate optimal career progression paths
     */
    generateCareerPaths() {
        this.careerPaths = [];

        for (let i = 0; i < this.positions.length; i++) {
            const sourcePosition = this.positions[i];
            const paths = this.findCareerPaths(i);

            if (paths.length > 0) {
                this.careerPaths.push({
                    sourcePosition: sourcePosition,
                    sourceIndex: i,
                    paths: paths
                });
            }
        }

        console.log(`Generated ${this.careerPaths.length} career progression paths`);
        return this.careerPaths;
    }

    /**
     * Find career paths from a given position
     */
    findCareerPaths(sourceIndex) {
        const paths = [];
        const sourcePos = this.positions[sourceIndex];

        // Find potential target positions
        const candidates = this.findCareerCandidates(sourceIndex);

        // Generate direct paths
        for (const candidate of candidates) {
            const path = this.generatePath(sourceIndex, candidate.index);
            if (path && this.isValidCareerPath(path)) {
                paths.push(path);
            }
        }

        // Generate multi-step paths (up to maxPathLength)
        if (this.config.maxPathLength > 2) {
            const multiStepPaths = this.generateMultiStepPaths(sourceIndex);
            paths.push(...multiStepPaths);
        }

        // Sort paths by preference score
        paths.sort((a, b) => b.score - a.score);

        // Return top paths (limit to prevent overwhelming)
        return paths.slice(0, 10);
    }

    /**
     * Find career advancement candidates for a position
     */
    findCareerCandidates(sourceIndex) {
        const sourcePos = this.positions[sourceIndex];
        const candidates = [];

        for (let i = 0; i < this.positions.length; i++) {
            if (i === sourceIndex) continue;

            const targetPos = this.positions[i];
            const distance = this.distanceMatrix[sourceIndex][i];

            // Check if this is a valid career progression
            if (this.isValidProgression(sourcePos, targetPos, distance)) {
                const score = this.calculateProgressionScore(sourcePos, targetPos, distance);
                candidates.push({
                    index: i,
                    position: targetPos,
                    distance: distance,
                    score: score
                });
            }
        }

        // Sort by score (best candidates first)
        candidates.sort((a, b) => b.score - a.score);

        return candidates.slice(0, 20); // Limit candidates
    }

    /**
     * Check if progression from source to target is valid
     */
    isValidProgression(sourcePos, targetPos, distance) {
        // Distance must be within reasonable range
        if (distance < this.config.minPathDistance || distance > this.config.maxPathDistance) {
            return false;
        }

        // Check level progression rules
        if (targetPos.levelValue < sourcePos.levelValue) {
            // Downward movement - only allowed for lateral moves
            if (!this.config.allowLateralMoves) return false;
            if (targetPos.levelValue < sourcePos.levelValue - 0.1) return false;
        }

        // Check department change rules
        if (sourcePos.department !== targetPos.department) {
            if (!this.config.allowDepartmentChange) return false;
        }

        // Check skip level rules
        const levelDiff = targetPos.levelValue - sourcePos.levelValue;
        if (levelDiff > 0.3 && !this.config.allowSkipLevel) {
            return false;
        }

        return true;
    }

    /**
     * Calculate progression score (higher is better)
     */
    calculateProgressionScore(sourcePos, targetPos, distance) {
        let score = 1.0;

        // Prefer upward progression
        const levelDiff = targetPos.levelValue - sourcePos.levelValue;
        if (levelDiff > 0) {
            score += levelDiff * 2; // Bonus for advancement
        } else if (levelDiff < 0) {
            score -= Math.abs(levelDiff) * 0.5; // Penalty for regression
        }

        // Prefer similar departments (but not penalty for change)
        if (sourcePos.department === targetPos.department) {
            score += 0.3;
        }

        // Prefer leadership progression for leadership positions
        if (sourcePos.is_leadership && targetPos.is_leadership) {
            score += 0.2;
        } else if (!sourcePos.is_leadership && targetPos.is_leadership) {
            score += 0.4; // Bonus for becoming leader
        }

        // Distance penalty (closer positions are preferred)
        score -= distance * 0.5;

        // Position order progression bonus
        if (targetPos.position_order > sourcePos.position_order) {
            const orderBonus = (targetPos.position_order - sourcePos.position_order) / 1000;
            score += Math.min(orderBonus, 1.0);
        }

        return Math.max(score, 0.1); // Ensure positive score
    }

    /**
     * Generate a direct career path between two positions
     */
    generatePath(sourceIndex, targetIndex) {
        const sourcePos = this.positions[sourceIndex];
        const targetPos = this.positions[targetIndex];
        const distance = this.distanceMatrix[sourceIndex][targetIndex];

        return {
            type: 'direct',
            steps: [sourceIndex, targetIndex],
            positions: [sourcePos, targetPos],
            distance: distance,
            score: this.calculateProgressionScore(sourcePos, targetPos, distance),
            difficulty: this.calculatePathDifficulty(sourcePos, targetPos),
            estimatedMonths: this.estimateTimeframe(sourcePos, targetPos),
            requirements: this.generateRequirements(sourcePos, targetPos)
        };
    }

    /**
     * Generate multi-step career paths
     */
    generateMultiStepPaths(sourceIndex) {
        const paths = [];

        // Use breadth-first search to find multi-step paths
        const queue = [{
            steps: [sourceIndex],
            distance: 0,
            visited: new Set([sourceIndex])
        }];

        while (queue.length > 0 && paths.length < 20) {
            const current = queue.shift();

            if (current.steps.length >= this.config.maxPathLength) continue;

            const lastIndex = current.steps[current.steps.length - 1];
            const candidates = this.findCareerCandidates(lastIndex);

            for (const candidate of candidates.slice(0, 5)) { // Limit branching
                if (current.visited.has(candidate.index)) continue;

                const newSteps = [...current.steps, candidate.index];
                const newDistance = current.distance + candidate.distance;
                const newVisited = new Set([...current.visited, candidate.index]);

                // If this is a complete path (length > 2), add it
                if (newSteps.length >= 3) {
                    const path = {
                        type: 'multi-step',
                        steps: newSteps,
                        positions: newSteps.map(i => this.positions[i]),
                        distance: newDistance,
                        score: this.calculateMultiStepScore(newSteps),
                        difficulty: this.calculateMultiStepDifficulty(newSteps),
                        estimatedMonths: this.estimateMultiStepTimeframe(newSteps),
                        requirements: this.generateMultiStepRequirements(newSteps)
                    };

                    if (this.isValidCareerPath(path)) {
                        paths.push(path);
                    }
                }

                // Continue searching if not at max length
                if (newSteps.length < this.config.maxPathLength) {
                    queue.push({
                        steps: newSteps,
                        distance: newDistance,
                        visited: newVisited
                    });
                }
            }
        }

        return paths;
    }

    /**
     * Calculate multi-step path score
     */
    calculateMultiStepScore(steps) {
        let totalScore = 0;
        let stepPenalty = 0.1; // Each additional step reduces score

        for (let i = 0; i < steps.length - 1; i++) {
            const sourcePos = this.positions[steps[i]];
            const targetPos = this.positions[steps[i + 1]];
            const distance = this.distanceMatrix[steps[i]][steps[i + 1]];

            const stepScore = this.calculateProgressionScore(sourcePos, targetPos, distance);
            totalScore += stepScore - (i * stepPenalty);
        }

        // Bonus for reaching high-level positions
        const finalPos = this.positions[steps[steps.length - 1]];
        if (finalPos.levelValue > 0.8) {
            totalScore += 1.0;
        }

        return totalScore / steps.length; // Average score per step
    }

    /**
     * Calculate path difficulty (1-10 scale)
     */
    calculatePathDifficulty(sourcePos, targetPos) {
        let difficulty = 1;

        // Level jump difficulty
        const levelDiff = targetPos.levelValue - sourcePos.levelValue;
        difficulty += Math.max(0, levelDiff * 10);

        // Department change difficulty
        if (sourcePos.department !== targetPos.department) {
            difficulty += 2;
        }

        // Leadership transition difficulty
        if (!sourcePos.is_leadership && targetPos.is_leadership) {
            difficulty += 3;
        }

        // Position order jump difficulty
        const orderRatio = targetPos.position_order / Math.max(sourcePos.position_order, 1);
        if (orderRatio > 2) {
            difficulty += Math.min(orderRatio - 1, 5);
        }

        return Math.min(Math.max(difficulty, 1), 10);
    }

    /**
     * Calculate multi-step path difficulty
     */
    calculateMultiStepDifficulty(steps) {
        let totalDifficulty = 0;

        for (let i = 0; i < steps.length - 1; i++) {
            const sourcePos = this.positions[steps[i]];
            const targetPos = this.positions[steps[i + 1]];
            totalDifficulty += this.calculatePathDifficulty(sourcePos, targetPos);
        }

        // Additional difficulty for multi-step paths
        totalDifficulty += (steps.length - 2) * 1.5;

        return Math.min(totalDifficulty / (steps.length - 1), 10);
    }

    /**
     * Estimate timeframe for career progression
     */
    estimateTimeframe(sourcePos, targetPos) {
        const baseTime = 12; // Base 12 months
        const levelDiff = targetPos.levelValue - sourcePos.levelValue;
        const difficulty = this.calculatePathDifficulty(sourcePos, targetPos);

        let months = baseTime + (levelDiff * 18) + (difficulty * 3);

        // Department change adds time
        if (sourcePos.department !== targetPos.department) {
            months += 6;
        }

        return Math.max(Math.round(months), 6);
    }

    /**
     * Estimate multi-step timeframe
     */
    estimateMultiStepTimeframe(steps) {
        let totalMonths = 0;

        for (let i = 0; i < steps.length - 1; i++) {
            const sourcePos = this.positions[steps[i]];
            const targetPos = this.positions[steps[i + 1]];
            totalMonths += this.estimateTimeframe(sourcePos, targetPos);
        }

        return totalMonths;
    }

    /**
     * Generate career progression requirements
     */
    generateRequirements(sourcePos, targetPos) {
        const requirements = [];

        // Level-based requirements
        const levelDiff = targetPos.levelValue - sourcePos.levelValue;
        if (levelDiff > 0.2) {
            requirements.push("Demonstrate leadership skills and experience");
            requirements.push("Complete relevant training and certifications");
        }

        // Department change requirements
        if (sourcePos.department !== targetPos.department) {
            requirements.push(`Gain experience in ${targetPos.department} department`);
            requirements.push("Cross-functional project participation");
        }

        // Leadership requirements
        if (!sourcePos.is_leadership && targetPos.is_leadership) {
            requirements.push("Management training completion");
            requirements.push("Team leadership experience");
            requirements.push("Performance evaluation excellence");
        }

        // Position order jump requirements
        const orderRatio = targetPos.position_order / Math.max(sourcePos.position_order, 1);
        if (orderRatio > 2) {
            requirements.push("Exceptional performance record");
            requirements.push("Strategic thinking demonstration");
            requirements.push("Stakeholder management skills");
        }

        return requirements.length > 0 ? requirements : ["Meet standard performance criteria"];
    }

    /**
     * Generate multi-step requirements
     */
    generateMultiStepRequirements(steps) {
        const allRequirements = new Set();

        for (let i = 0; i < steps.length - 1; i++) {
            const stepRequirements = this.generateRequirements(
                this.positions[steps[i]],
                this.positions[steps[i + 1]]
            );
            stepRequirements.forEach(req => allRequirements.add(req));
        }

        // Add multi-step specific requirements
        allRequirements.add("Long-term career planning and commitment");
        allRequirements.add("Continuous learning and skill development");

        return Array.from(allRequirements);
    }

    /**
     * Validate if a career path is acceptable
     */
    isValidCareerPath(path) {
        if (!path || !path.steps || path.steps.length < 2) return false;
        if (path.score < 0.2) return false; // Minimum score threshold
        if (path.difficulty > 9) return false; // Maximum difficulty threshold

        return true;
    }

    /**
     * Get clustering statistics
     */
    getClusteringStats() {
        return {
            totalPositions: this.positions.length,
            totalPaths: this.careerPaths.reduce((sum, p) => sum + p.paths.length, 0),
            averageDistance: this.calculateAverageDistance(),
            clusterCount: this.clusters.length,
            departmentCount: new Set(this.positions.map(p => p.department)).size,
            levelCount: new Set(this.positions.map(p => p.level_name)).size
        };
    }

    /**
     * Calculate average distance between all positions
     */
    calculateAverageDistance() {
        let total = 0;
        let count = 0;

        for (let i = 0; i < this.positions.length; i++) {
            for (let j = i + 1; j < this.positions.length; j++) {
                total += this.distanceMatrix[i][j];
                count++;
            }
        }

        return count > 0 ? total / count : 0;
    }

    /**
     * Export clustering results
     */
    exportResults() {
        return {
            positions: this.positions,
            clusters: this.clusters,
            careerPaths: this.careerPaths,
            distanceMatrix: this.distanceMatrix,
            stats: this.getClusteringStats(),
            config: this.config
        };
    }
}

// Make available globally
window.CareerClustering = CareerClustering;
<style>
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fafafa;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
    }

    .card-body {
        padding: 24px;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
    }

    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    .btn {
        border-radius: 2px;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-secondary {
        background-color: #f5f5f5;
        border-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    .btn-secondary:hover {
        background-color: #e6f7ff;
        border-color: #40a9ff;
        color: #40a9ff;
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
        margin-bottom: 4px;
    }

    .info-value {
        color: rgba(0, 0, 0, 0.65);
    }
</style>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-lg-8">
            <!-- Module Details Card -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php if ($data['icon']): ?>
                                <i class="<?= htmlspecialchars($data['icon']) ?> me-2"></i>
                            <?php else: ?>
                                <i class="bi bi-gear me-2"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($data['display_name']) ?>
                        </h5>
                        <div>
                            <a href="<?= base_url() ?>/modules/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <a href="<?= base_url() ?>/modules" class="btn btn-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Module ID</div>
                                <div class="info-value"><?= $data['id'] ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Module Name</div>
                                <div class="info-value">
                                    <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px;">
                                        <?= htmlspecialchars($data['name']) ?>
                                    </code>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Display Name</div>
                                <div class="info-value"><?= htmlspecialchars($data['display_name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Controller</div>
                                <div class="info-value">
                                    <?php if ($data['controller']): ?>
                                        <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px;">
                                            <?= htmlspecialchars($data['controller']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="text-muted">No controller (parent module)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Parent Module</div>
                                <div class="info-value">
                                    <?php if ($data['parent_name']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($data['parent_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Root Module</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Sort Order</div>
                                <div class="info-value">
                                    <span class="badge bg-secondary"><?= $data['sort_order'] ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <?php if ($data['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Icon</div>
                                <div class="info-value">
                                    <?php if ($data['icon']): ?>
                                        <i class="<?= htmlspecialchars($data['icon']) ?> me-2" style="font-size: 20px;"></i>
                                        <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px;">
                                            <?= htmlspecialchars($data['icon']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="text-muted">No icon</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Child Modules Card -->
            <?php if (!empty($children)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-diagram-3 me-2"></i>Child Modules
                        <span class="badge bg-info ms-2"><?= count($children) ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Display Name</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                <tr>
                                    <td>
                                        <code style="background-color: #f6f8fa; padding: 2px 6px; border-radius: 3px;">
                                            <?= htmlspecialchars($child['name']) ?>
                                        </code>
                                    </td>
                                    <td><?= htmlspecialchars($child['display_name']) ?></td>
                                    <td>
                                        <?php if ($child['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url() ?>/modules/detail?id=<?= $child['id'] ?>" 
                                           class="me-2" style="color: #1890ff; text-decoration: none;" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url() ?>/modules/edit_page?id=<?= $child['id'] ?>" 
                                           style="color: #1890ff; text-decoration: none;" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Role Permissions Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Role Permissions
                        <span class="badge bg-secondary ms-2"><?= count($permissions) ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($permissions)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th class="text-center">V</th>
                                        <th class="text-center">C</th>
                                        <th class="text-center">E</th>
                                        <th class="text-center">D</th>
                                        <th class="text-center">A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permissions as $perm): ?>
                                    <tr>
                                        <td>
                                            <small><?= htmlspecialchars($perm['role_name']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($perm['can_view']): ?>
                                                <i class="bi bi-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($perm['can_create']): ?>
                                                <i class="bi bi-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($perm['can_edit']): ?>
                                                <i class="bi bi-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($perm['can_delete']): ?>
                                                <i class="bi bi-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($perm['can_approve']): ?>
                                                <i class="bi bi-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <strong>Legend:</strong> V=View, C=Create, E=Edit, D=Delete, A=Approve
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-shield-x text-muted" style="font-size: 48px;"></i>
                            <p class="text-muted mt-2">No role permissions assigned</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Module Statistics Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="mb-1 text-primary"><?= count($children) ?></h4>
                                <small class="text-muted">Child Modules</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="mb-1 text-success"><?= count($permissions) ?></h4>
                                <small class="text-muted">Role Permissions</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
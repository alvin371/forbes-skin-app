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
        padding: 20px;
    }

    .info-item {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        width: 150px;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
    }

    .info-value {
        flex: 1;
        color: rgba(0, 0, 0, 0.65);
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
        padding: 12px 8px;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
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
</style>

<div class="container-fluid py-3">
    <div class="row">
        <!-- Role Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-label">Role Name:</div>
                        <div class="info-value">
                            <code><?= htmlspecialchars($data['name']) ?></code>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Display Name:</div>
                        <div class="info-value">
                            <strong><?= htmlspecialchars($data['display_name']) ?></strong>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <?php if ($data['is_active']): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Description:</div>
                        <div class="info-value">
                            <?= !empty($data['description']) ? nl2br(htmlspecialchars($data['description'])) : '<em class="text-muted">No description provided</em>' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Created:</div>
                        <div class="info-value">
                            <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Updated:</div>
                        <div class="info-value">
                            <?= date('d M Y H:i', strtotime($data['updated_at'])) ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= base_url() ?>/roles" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                        <a href="<?= base_url() ?>/roles/edit_page?id=<?= $data['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit Role
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Users -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Assigned Users (<?= count($users) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-people" style="font-size: 48px; color: #d9d9d9;"></i>
                            <p class="text-muted mt-2">No users assigned to this role</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Assigned Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($user['username']) ?></code>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="text-muted">
                                                    <?= date('d M Y', strtotime($user['assigned_at'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Role Permissions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Role Permissions (<?= count($permissions) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($permissions)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-shield-exclamation" style="font-size: 48px; color: #d9d9d9;"></i>
                            <p class="text-muted mt-2">No permissions assigned to this role</p>
                            <a href="<?= base_url() ?>/modules/permission_matrix" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-1"></i>Assign Permissions
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Module</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Create</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                        <th class="text-center">Approve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permissions as $index => $permission): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($permission['display_name']) ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($permission['can_view']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($permission['can_create']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($permission['can_edit']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($permission['can_delete']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($permission['can_approve']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
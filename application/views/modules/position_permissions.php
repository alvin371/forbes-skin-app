<div class="row mb-3">
    <div class="col">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="bi bi-briefcase fs-2 text-primary"></i>
            </div>
            <div>
                <h5 class="mb-0"><?= htmlspecialchars($position['position_name']) ?></h5>
                <small class="text-muted">
                    <i class="bi bi-award me-1"></i><?= htmlspecialchars($position['quest_level_name']) ?> Level
                </small>
            </div>
        </div>
    </div>
    <div class="col-auto">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="checkAll()">
                <i class="bi bi-check-all me-1"></i>Check All
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="uncheckAll()">
                <i class="bi bi-x-lg me-1"></i>Uncheck All
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Module</th>
                        <th class="text-center" style="width: 15%;">
                            <i class="bi bi-eye text-primary"></i><br>
                            <small>View</small>
                        </th>
                        <th class="text-center" style="width: 15%;">
                            <i class="bi bi-plus-circle text-success"></i><br>
                            <small>Create</small>
                        </th>
                        <th class="text-center" style="width: 15%;">
                            <i class="bi bi-pencil-square text-warning"></i><br>
                            <small>Edit</small>
                        </th>
                        <th class="text-center" style="width: 15%;">
                            <i class="bi bi-trash text-danger"></i><br>
                            <small>Delete</small>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grouped_permissions = [];
                    foreach ($permissions as $perm) {
                        if ($perm['parent_id'] === null) {
                            $grouped_permissions[$perm['module_id']] = $perm;
                            $grouped_permissions[$perm['module_id']]['children'] = [];
                        }
                    }
                    
                    foreach ($permissions as $perm) {
                        if ($perm['parent_id'] !== null) {
                            if (isset($grouped_permissions[$perm['parent_id']])) {
                                $grouped_permissions[$perm['parent_id']]['children'][] = $perm;
                            }
                        }
                    }
                    
                    foreach ($grouped_permissions as $parent):
                    ?>
                        <!-- Parent Module -->
                        <tr class="table-light">
                            <td>
                                <strong>
                                    <?php if (!empty($parent['children'])): ?>
                                        <i class="bi bi-chevron-down me-1" data-bs-toggle="collapse" data-bs-target="#children-<?= $parent['module_id'] ?>" style="cursor: pointer;"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($parent['display_name']) ?>
                                </strong>
                            </td>
                            <td class="text-center">
                                <div class="checkbox-wrapper-13">
                                    <input type="checkbox" id="view_<?= $parent['module_id'] ?>" 
                                           data-module="<?= $parent['module_id'] ?>" data-action="can_view"
                                           <?= $parent['can_view'] ? 'checked' : '' ?>>
                                    <label for="view_<?= $parent['module_id'] ?>"></label>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="checkbox-wrapper-13">
                                    <input type="checkbox" id="create_<?= $parent['module_id'] ?>" 
                                           data-module="<?= $parent['module_id'] ?>" data-action="can_create"
                                           <?= $parent['can_create'] ? 'checked' : '' ?>>
                                    <label for="create_<?= $parent['module_id'] ?>"></label>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="checkbox-wrapper-13">
                                    <input type="checkbox" id="edit_<?= $parent['module_id'] ?>" 
                                           data-module="<?= $parent['module_id'] ?>" data-action="can_edit"
                                           <?= $parent['can_edit'] ? 'checked' : '' ?>>
                                    <label for="edit_<?= $parent['module_id'] ?>"></label>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="checkbox-wrapper-13">
                                    <input type="checkbox" id="delete_<?= $parent['module_id'] ?>" 
                                           data-module="<?= $parent['module_id'] ?>" data-action="can_delete"
                                           <?= $parent['can_delete'] ? 'checked' : '' ?>>
                                    <label for="delete_<?= $parent['module_id'] ?>"></label>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Child Modules -->
                        <?php if (!empty($parent['children'])): ?>
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="collapse show" id="children-<?= $parent['module_id'] ?>">
                                        <table class="table table-sm mb-0">
                                            <?php foreach ($parent['children'] as $child): ?>
                                                <tr>
                                                    <td style="width: 40%; padding-left: 2rem;">
                                                        <i class="bi bi-arrow-return-right me-2 text-muted"></i>
                                                        <?= htmlspecialchars($child['display_name']) ?>
                                                    </td>
                                                    <td class="text-center" style="width: 15%;">
                                                        <div class="checkbox-wrapper-13">
                                                            <input type="checkbox" id="view_<?= $child['module_id'] ?>" 
                                                                   data-module="<?= $child['module_id'] ?>" data-action="can_view"
                                                                   <?= $child['can_view'] ? 'checked' : '' ?>>
                                                            <label for="view_<?= $child['module_id'] ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center" style="width: 15%;">
                                                        <div class="checkbox-wrapper-13">
                                                            <input type="checkbox" id="create_<?= $child['module_id'] ?>" 
                                                                   data-module="<?= $child['module_id'] ?>" data-action="can_create"
                                                                   <?= $child['can_create'] ? 'checked' : '' ?>>
                                                            <label for="create_<?= $child['module_id'] ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center" style="width: 15%;">
                                                        <div class="checkbox-wrapper-13">
                                                            <input type="checkbox" id="edit_<?= $child['module_id'] ?>" 
                                                                   data-module="<?= $child['module_id'] ?>" data-action="can_edit"
                                                                   <?= $child['can_edit'] ? 'checked' : '' ?>>
                                                            <label for="edit_<?= $child['module_id'] ?>"></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center" style="width: 15%;">
                                                        <div class="checkbox-wrapper-13">
                                                            <input type="checkbox" id="delete_<?= $child['module_id'] ?>" 
                                                                   data-module="<?= $child['module_id'] ?>" data-action="can_delete"
                                                                   <?= $child['can_delete'] ? 'checked' : '' ?>>
                                                            <label for="delete_<?= $child['module_id'] ?>"></label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function checkAll() {
    $('#positionPermissionsContent input[type="checkbox"]').prop('checked', true);
}

function uncheckAll() {
    $('#positionPermissionsContent input[type="checkbox"]').prop('checked', false);
}

// Toggle collapse icons
$(document).on('click', '[data-bs-toggle="collapse"]', function() {
    let icon = $(this).find('i');
    icon.toggleClass('bi-chevron-down bi-chevron-right');
});

// Auto-check view when other permissions are checked
$(document).on('change', 'input[data-action="can_create"], input[data-action="can_edit"], input[data-action="can_delete"]', function() {
    if ($(this).is(':checked')) {
        let moduleId = $(this).data('module');
        $('#view_' + moduleId).prop('checked', true);
    }
});

// Prevent unchecking view if other permissions are checked
$(document).on('change', 'input[data-action="can_view"]', function() {
    if (!$(this).is(':checked')) {
        let moduleId = $(this).data('module');
        // Uncheck all other permissions for this module
        $('input[data-module="' + moduleId + '"]').not(this).prop('checked', false);
    }
});
</script>
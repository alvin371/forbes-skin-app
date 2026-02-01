-- Add Approval Modules for Navigation
-- Run this SQL to add approval_routes and approval_inbox modules to the database

-- Insert approval_routes module
INSERT INTO modules (name, display_name, controller, icon, parent_id, sort_order, is_active, created_at, updated_at)
SELECT 'approval_routes', 'Approval Routes', 'admin/ApprovalRoutesController', 'bi-diagram-3', NULL, 100, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE name = 'approval_routes');

-- Insert approval_inbox module
INSERT INTO modules (name, display_name, controller, icon, parent_id, sort_order, is_active, created_at, updated_at)
SELECT 'approval_inbox', 'Approval Inbox', 'approvals/ApprovalInboxController', 'bi-inbox', NULL, 101, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE name = 'approval_inbox');

-- Grant permissions to admin/HR roles (adjust role_id as needed)
-- Get module IDs
SET @approval_routes_id = (SELECT id FROM modules WHERE name = 'approval_routes');
SET @approval_inbox_id = (SELECT id FROM modules WHERE name = 'approval_inbox');

-- Get admin role ID (assuming role name is 'Admin' or 'Super Admin')
SET @admin_role_id = (SELECT id FROM roles WHERE name IN ('Admin', 'Super Admin', 'admin', 'super_admin') LIMIT 1);
SET @hr_role_id = (SELECT id FROM roles WHERE name IN ('HR', 'Head of HR', 'HR Admin', 'hr') LIMIT 1);

-- Grant full access to admin role for approval_routes
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @admin_role_id, @approval_routes_id, 1, 1, 1, 1, 1, NOW(), NOW()
WHERE @admin_role_id IS NOT NULL AND @approval_routes_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @admin_role_id AND module_id = @approval_routes_id);

-- Grant full access to HR role for approval_routes
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @hr_role_id, @approval_routes_id, 1, 1, 1, 1, 1, NOW(), NOW()
WHERE @hr_role_id IS NOT NULL AND @approval_routes_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @hr_role_id AND module_id = @approval_routes_id);

-- Grant approval_inbox access to all roles that have leave_approvals access
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT DISTINCT rp.role_id, @approval_inbox_id, 1, 0, 1, 0, 1, NOW(), NOW()
FROM role_permissions rp
INNER JOIN modules m ON rp.module_id = m.id
WHERE m.name = 'leave_approvals' AND rp.can_view = 1
AND @approval_inbox_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = rp.role_id AND module_id = @approval_inbox_id);

-- If no leave_approvals permissions exist, grant to admin and HR roles
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @admin_role_id, @approval_inbox_id, 1, 0, 1, 0, 1, NOW(), NOW()
WHERE @admin_role_id IS NOT NULL AND @approval_inbox_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @admin_role_id AND module_id = @approval_inbox_id);

INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @hr_role_id, @approval_inbox_id, 1, 0, 1, 0, 1, NOW(), NOW()
WHERE @hr_role_id IS NOT NULL AND @approval_inbox_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @hr_role_id AND module_id = @approval_inbox_id);

-- Verification
SELECT m.name, m.display_name, m.controller, COUNT(rp.id) as permission_count
FROM modules m
LEFT JOIN role_permissions rp ON m.id = rp.module_id
WHERE m.name IN ('approval_routes', 'approval_inbox')
GROUP BY m.id, m.name, m.display_name, m.controller;

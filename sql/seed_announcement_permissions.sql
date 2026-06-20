-- Seed Announcement module + role permissions
-- Run after the announcements migration. The `modules` row is normally created
-- automatically by sidebar_registry_sync() (triggered when an admin opens the
-- Roles/Modules page); this script also inserts it defensively so the seed can be
-- run standalone. Grants full CRUD to admin/super_admin (+ HR) roles.

-- Insert announcement module (idempotent)
INSERT INTO modules (name, display_name, controller, icon, parent_id, sort_order, is_active, created_at, updated_at)
SELECT 'announcement', 'ANNOUNCEMENTS', 'announcement', 'bi-megaphone', NULL, 505, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE name = 'announcement');

-- Resolve ids
SET @announcement_id = (SELECT id FROM modules WHERE name = 'announcement');
SET @admin_role_id = (SELECT id FROM roles WHERE name IN ('Admin', 'Super Admin', 'admin', 'super_admin') LIMIT 1);
SET @hr_role_id = (SELECT id FROM roles WHERE name IN ('HR', 'Head of HR', 'HR Admin', 'hr') LIMIT 1);

-- Grant full CRUD to admin role
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @admin_role_id, @announcement_id, 1, 1, 1, 1, 0, NOW(), NOW()
WHERE @admin_role_id IS NOT NULL AND @announcement_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @admin_role_id AND module_id = @announcement_id);

-- Grant full CRUD to HR role
INSERT INTO role_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, created_at, updated_at)
SELECT @hr_role_id, @announcement_id, 1, 1, 1, 1, 0, NOW(), NOW()
WHERE @hr_role_id IS NOT NULL AND @announcement_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = @hr_role_id AND module_id = @announcement_id);

-- Verification
SELECT m.name, m.display_name, m.controller, COUNT(rp.id) as permission_count
FROM modules m
LEFT JOIN role_permissions rp ON m.id = rp.module_id
WHERE m.name = 'announcement'
GROUP BY m.id, m.name, m.display_name, m.controller;

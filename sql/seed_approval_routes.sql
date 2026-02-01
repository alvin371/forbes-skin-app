-- Sample Approval Routes for Dynamic Leave Approval System
-- Run this SQL after the migration to create initial routes

-- ============================================
-- Route 1: Finance/Warehouse Route (2 Steps)
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('FINANCE_WAREHOUSE', 'Rute Finance/Warehouse', 'Rute approval untuk departemen Finance dan Warehouse', 1, CURDATE(), 1, 1);
SET @route_id_1 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_1, 'department', 'Finance,Warehouse', 'in');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_1, 1, 'role', 'Head of Operation', 'Head of Operation'),
    (@route_id_1, 2, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 2: Marketing Route (2 Steps)
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('MARKETING', 'Rute Marketing', 'Rute approval untuk departemen Marketing', 1, CURDATE(), 1, 1);
SET @route_id_2 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_2, 'department', 'Marketing', 'eq');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_2, 1, 'role', 'Head of Marketing', 'Head of Marketing'),
    (@route_id_2, 2, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 3: HR Short Leave (<= 3 days) - 1 Step
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('HR_SHORT', 'Rute HR Cuti Singkat', 'Rute approval HR untuk cuti <= 3 hari', 1, CURDATE(), 1, 1);
SET @route_id_3 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES
    (@route_id_3, 'department', 'HR', 'eq'),
    (@route_id_3, 'leave_duration', '3', 'lte');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES (@route_id_3, 1, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 4: HR Long Leave (> 3 days) - 3 Steps
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('HR_LONG', 'Rute HR Cuti Panjang', 'Rute approval HR untuk cuti > 3 hari', 1, CURDATE(), 1, 1);
SET @route_id_4 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES
    (@route_id_4, 'department', 'HR', 'eq'),
    (@route_id_4, 'leave_duration', '3', 'gt');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_4, 1, 'dynamic', 'direct_manager', 'Atasan Langsung'),
    (@route_id_4, 2, 'role', 'Head of HR', 'Head of HR'),
    (@route_id_4, 3, 'role', 'Director', 'Director');

-- ============================================
-- Route 5: Operations Route (2 Steps)
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('OPERATIONS', 'Rute Operations', 'Rute approval untuk departemen Operations', 1, CURDATE(), 1, 1);
SET @route_id_5 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_5, 'department', 'Operations', 'eq');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_5, 1, 'dynamic', 'direct_manager', 'Atasan Langsung'),
    (@route_id_5, 2, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 6: IT Department Route (2 Steps)
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('IT_DEPT', 'Rute IT', 'Rute approval untuk departemen IT', 1, CURDATE(), 1, 1);
SET @route_id_6 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_6, 'department', 'IT', 'eq');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_6, 1, 'role', 'Head of IT', 'Head of IT'),
    (@route_id_6, 2, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 7: Default/Fallback Route (2 Steps)
-- No scopes = matches anyone not matched by other routes
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('DEFAULT', 'Rute Default', 'Rute default untuk semua departemen yang tidak memiliki rute khusus', 1, CURDATE(), 1, 1);
SET @route_id_7 = LAST_INSERT_ID();

-- No scopes for default route (lowest priority, matches everyone)

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_7, 1, 'dynamic', 'direct_manager', 'Atasan Langsung'),
    (@route_id_7, 2, 'role', 'Head of HR', 'HR Final Approval');

-- ============================================
-- Route 8: Sick Leave Route (Any Department) - 1 Step
-- Higher priority for sick leave type
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('SICK_LEAVE', 'Rute Cuti Sakit', 'Rute khusus untuk cuti sakit (semua departemen)', 1, CURDATE(), 1, 1);
SET @route_id_8 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_8, 'leave_type', 'Sakit', 'eq');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES (@route_id_8, 1, 'role', 'Head of HR', 'Head of HR');

-- ============================================
-- Route 9: Maternity/Paternity Leave (3 Steps)
-- Special approval for long-term leave
-- ============================================
INSERT INTO approval_route_versions (route_code, name, description, version, effective_from, is_active, created_by)
VALUES ('MATERNITY_PATERNITY', 'Rute Cuti Melahirkan/Ayah', 'Rute khusus untuk cuti melahirkan atau cuti ayah', 1, CURDATE(), 1, 1);
SET @route_id_9 = LAST_INSERT_ID();

INSERT INTO approval_route_scopes (route_version_id, scope_type, scope_value, operator)
VALUES (@route_id_9, 'leave_type', 'Melahirkan,Cuti Ayah', 'in');

INSERT INTO approval_route_steps (route_version_id, step_no, approver_type, approver_value, step_name)
VALUES
    (@route_id_9, 1, 'dynamic', 'direct_manager', 'Atasan Langsung'),
    (@route_id_9, 2, 'role', 'Head of HR', 'Head of HR'),
    (@route_id_9, 3, 'role', 'Director', 'Director');

-- ============================================
-- Verification Query
-- ============================================
SELECT
    rv.id,
    rv.route_code,
    rv.name,
    rv.version,
    (SELECT COUNT(*) FROM approval_route_scopes WHERE route_version_id = rv.id) as scope_count,
    (SELECT COUNT(*) FROM approval_route_steps WHERE route_version_id = rv.id) as step_count
FROM approval_route_versions rv
WHERE rv.is_active = 1
ORDER BY rv.id;

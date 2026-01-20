<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Performance Model
 * Handles all database operations for Performance Appraisal system
 */
class Performance_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // ============================================
    // ROLE HELPER METHODS
    // ============================================

    /**
     * Get all active roles for dropdown
     */
    public function get_all_roles()
    {
        $this->db->select('id, name, display_name');
        $this->db->from('roles');
        $this->db->where('is_active', 1);
        $this->db->order_by('display_name', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Get employee's primary role (first assigned role)
     */
    public function get_employee_primary_role($employee_id)
    {
        $this->db->select('r.id, r.name, r.display_name');
        $this->db->from('user_roles ur');
        $this->db->join('roles r', 'ur.role_id = r.id');
        $this->db->where('ur.user_id', $employee_id);
        $this->db->where('r.is_active', 1);
        $this->db->limit(1);

        return $this->db->get()->row_array();
    }

    // ============================================
    // TEMPLATE OPERATIONS
    // ============================================

    /**
     * Get all templates with optional filtering
     */
    public function get_templates($filters = [])
    {
        $this->db->select('
            pt.*,
            r.display_name as role_display_name,
            COUNT(pti.id) as item_count,
            SUM(pti.weight) as total_weight,
            (SELECT COUNT(*) FROM performance_submissions ps WHERE ps.template_id = pt.id) as submission_count
        ');
        $this->db->from('performance_templates pt');
        $this->db->join('performance_template_items pti', 'pt.id = pti.template_id', 'left');
        $this->db->join('roles r', 'pt.role_id = r.id', 'left');

        if (isset($filters['period_year'])) {
            $this->db->where('pt.period_year', $filters['period_year']);
        }

        // Filter by role_id (new) or department (legacy)
        if (isset($filters['role_id']) && $filters['role_id'] !== '' && $filters['role_id'] !== null) {
            $this->db->where('pt.role_id', $filters['role_id']);
        } elseif (isset($filters['department']) && $filters['department'] !== '') {
            $this->db->where('pt.department', $filters['department']);
        }

        if (isset($filters['is_active'])) {
            $this->db->where('pt.is_active', $filters['is_active']);
        }

        $this->db->group_by('pt.id');
        $this->db->order_by('pt.period_year', 'DESC');
        $this->db->order_by('pt.created_at', 'DESC');

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get single template by ID with items
     */
    public function get_template_by_id($template_id, $include_items = true)
    {
        $this->db->select('
            pt.*,
            COUNT(pti.id) as item_count,
            SUM(pti.weight) as total_weight
        ');
        $this->db->from('performance_templates pt');
        $this->db->join('performance_template_items pti', 'pt.id = pti.template_id', 'left');
        $this->db->where('pt.id', $template_id);
        $this->db->group_by('pt.id');

        $query = $this->db->get();
        $template = $query->row_array();

        if ($template && $include_items) {
            $template['items'] = $this->get_template_items($template_id);
        }

        return $template;
    }

    /**
     * Get active template for employee based on their role
     * @param int $period_year
     * @param int|null $employee_role_id - The employee's primary role ID
     */
    public function get_active_template_for_employee($period_year, $employee_role_id = null)
    {
        $this->db->select('pt.*, r.display_name as role_display_name');
        $this->db->from('performance_templates pt');
        $this->db->join('roles r', 'pt.role_id = r.id', 'left');
        $this->db->where('pt.is_active', 1);
        $this->db->where('pt.period_year', $period_year);

        // Match role or global template (role_id = NULL means all roles)
        if ($employee_role_id) {
            $this->db->group_start();
            $this->db->where('pt.role_id', $employee_role_id);
            $this->db->or_where('pt.role_id IS NULL');
            $this->db->group_end();

            // Prioritize specific role over global template
            $order_expr = "CASE WHEN pt.role_id = " . $this->db->escape($employee_role_id) . " THEN 0 WHEN pt.role_id IS NULL THEN 1 ELSE 2 END";
            $this->db->order_by($order_expr, 'ASC', FALSE);
        } else {
            // No role assigned - prefer global templates but allow any active template
            $this->db->order_by('CASE WHEN pt.role_id IS NULL THEN 0 ELSE 1 END', 'ASC', FALSE);
        }

        $this->db->limit(1);

        $query = $this->db->get();
        $template = $query->row_array();

        if ($template) {
            $template['items'] = $this->get_template_items($template['id']);
        }

        return $template;
    }

    /**
     * Create new template
     */
    public function create_template($data)
    {
        $template_data = [
            'name' => $data['name'],
            'period_year' => $data['period_year'],
            'role_id' => isset($data['role_id']) && $data['role_id'] !== '' ? $data['role_id'] : null,
            'department' => null, // Deprecated, kept for backward compatibility
            'is_active' => $data['is_active'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('performance_templates', $template_data);
        return $this->db->insert_id();
    }

    /**
     * Update template
     */
    public function update_template($template_id, $data)
    {
        // If activating, validate total weight = 100
        if (isset($data['is_active']) && $data['is_active'] == 1) {
            $template = $this->get_template_by_id($template_id);

            if (!$template || $template['item_count'] == 0) {
                return ['success' => false, 'error' => 'Template must have at least 1 item before activation'];
            }

            if ($template['total_weight'] != 100) {
                return ['success' => false, 'error' => 'Total weight must be exactly 100% before activation (current: ' . $template['total_weight'] . '%)'];
            }
        }

        $update_data = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (isset($data['name'])) $update_data['name'] = $data['name'];
        if (isset($data['period_year'])) $update_data['period_year'] = $data['period_year'];
        if (array_key_exists('role_id', $data)) {
            $update_data['role_id'] = ($data['role_id'] === '' || $data['role_id'] === null) ? null : $data['role_id'];
        }
        if (isset($data['department'])) $update_data['department'] = $data['department'];
        if (isset($data['is_active'])) $update_data['is_active'] = $data['is_active'];

        $this->db->where('id', $template_id);
        $this->db->update('performance_templates', $update_data);

        return ['success' => true];
    }

    /**
     * Delete template (only if no submissions exist)
     */
    public function delete_template($template_id)
    {
        // Check for submissions
        $this->db->where('template_id', $template_id);
        $count = $this->db->count_all_results('performance_submissions');

        if ($count > 0) {
            return ['success' => false, 'error' => 'Cannot delete template with existing submissions'];
        }

        $this->db->where('id', $template_id);
        $this->db->delete('performance_templates');

        return ['success' => true];
    }

    // ============================================
    // TEMPLATE ITEMS OPERATIONS
    // ============================================

    /**
     * Get all items for a template
     */
    public function get_template_items($template_id)
    {
        $this->db->where('template_id', $template_id);
        $this->db->order_by('order_no', 'ASC');
        $query = $this->db->get('performance_template_items');
        return $query->result_array();
    }

    /**
     * Get single template item
     */
    public function get_template_item($item_id)
    {
        $this->db->where('id', $item_id);
        $query = $this->db->get('performance_template_items');
        return $query->row_array();
    }

    /**
     * Create template item
     */
    public function create_template_item($data)
    {
        $item_data = [
            'template_id' => $data['template_id'],
            'order_no' => $data['order_no'] ?? 0,
            'objective' => $data['objective'],
            'kpi' => $data['kpi'],
            'target_value' => $data['target_value'],
            'unit' => $data['unit'],
            'weight' => $data['weight'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Validate weight >= 0
        if ($item_data['weight'] < 0) {
            return ['success' => false, 'error' => 'Weight must be >= 0'];
        }

        // Validate target_value > 0
        if ($item_data['target_value'] <= 0) {
            return ['success' => false, 'error' => 'Target value must be > 0'];
        }

        $this->db->insert('performance_template_items', $item_data);
        $item_id = $this->db->insert_id();

        return ['success' => true, 'id' => $item_id];
    }

    /**
     * Update template item
     */
    public function update_template_item($item_id, $data)
    {
        $update_data = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (isset($data['order_no'])) $update_data['order_no'] = $data['order_no'];
        if (isset($data['objective'])) $update_data['objective'] = $data['objective'];
        if (isset($data['kpi'])) $update_data['kpi'] = $data['kpi'];

        if (isset($data['target_value'])) {
            if ($data['target_value'] <= 0) {
                return ['success' => false, 'error' => 'Target value must be > 0'];
            }
            $update_data['target_value'] = $data['target_value'];
        }

        if (isset($data['unit'])) $update_data['unit'] = $data['unit'];

        if (isset($data['weight'])) {
            if ($data['weight'] < 0) {
                return ['success' => false, 'error' => 'Weight must be >= 0'];
            }
            $update_data['weight'] = $data['weight'];
        }

        $this->db->where('id', $item_id);
        $this->db->update('performance_template_items', $update_data);

        return ['success' => true];
    }

    /**
     * Delete template item
     */
    public function delete_template_item($item_id)
    {
        $this->db->where('id', $item_id);
        $this->db->delete('performance_template_items');
        return ['success' => true];
    }

    /**
     * Reorder template items
     */
    public function reorder_template_items($items_order)
    {
        $this->db->trans_start();

        foreach ($items_order as $item) {
            $this->db->where('id', $item['id']);
            $this->db->update('performance_template_items', [
                'order_no' => $item['order_no'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->db->trans_complete();

        return ['success' => $this->db->trans_status()];
    }

    // ============================================
    // SUBMISSION OPERATIONS
    // ============================================

    /**
     * Get submissions with optional filtering
     */
    public function get_submissions($filters = [])
    {
        $this->db->select('
            ps.*,
            pt.name as template_name,
            pt.role_id as template_role_id,
            r.display_name as template_role_name,
            COUNT(psi.id) as item_count
        ');
        $this->db->from('performance_submissions ps');
        $this->db->join('performance_templates pt', 'ps.template_id = pt.id', 'left');
        $this->db->join('roles r', 'pt.role_id = r.id', 'left');
        $this->db->join('performance_submission_items psi', 'ps.id = psi.submission_id', 'left');

        if (isset($filters['employee_id'])) {
            $this->db->where('ps.employee_id', $filters['employee_id']);
        }

        if (isset($filters['template_id'])) {
            $this->db->where('ps.template_id', $filters['template_id']);
        }

        if (isset($filters['period_year'])) {
            $this->db->where('ps.period_year', $filters['period_year']);
        }

        if (isset($filters['status'])) {
            $this->db->where('ps.status', $filters['status']);
        }

        // Filter by employee's role at submission time
        if (isset($filters['role_id']) && $filters['role_id'] !== '' && $filters['role_id'] !== null) {
            $this->db->where('ps.employee_role_id', $filters['role_id']);
        }

        // Legacy: filter by department
        if (isset($filters['department'])) {
            $this->db->where('pt.department', $filters['department']);
        }

        $this->db->group_by('ps.id');
        $this->db->order_by('ps.created_at', 'DESC');

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get single submission by ID with items
     */
    public function get_submission_by_id($submission_id, $include_items = true)
    {
        $this->db->select('ps.*, pt.name as template_name');
        $this->db->from('performance_submissions ps');
        $this->db->join('performance_templates pt', 'ps.template_id = pt.id', 'left');
        $this->db->where('ps.id', $submission_id);

        $query = $this->db->get();
        $submission = $query->row_array();

        if ($submission && $include_items) {
            $submission['items'] = $this->get_submission_items($submission_id);
        }

        return $submission;
    }

    /**
     * Get submission items with template item details
     */
    public function get_submission_items($submission_id)
    {
        $this->db->select('
            psi.*,
            pti.objective,
            pti.kpi,
            pti.target_value,
            pti.unit,
            pti.weight,
            pti.order_no
        ');
        $this->db->from('performance_submission_items psi');
        $this->db->join('performance_template_items pti', 'psi.template_item_id = pti.id', 'left');
        $this->db->where('psi.submission_id', $submission_id);
        $this->db->order_by('pti.order_no', 'ASC');

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Create submission with items and calculate scores
     */
    public function create_submission($data)
    {
        $this->db->trans_start();

        try {
            // Check for duplicate submission
            $this->db->where([
                'employee_id' => $data['employee_id'],
                'template_id' => $data['template_id'],
                'period_year' => $data['period_year']
            ]);
            $existing = $this->db->get('performance_submissions')->row_array();

            if ($existing) {
                $this->db->trans_rollback();
                return ['success' => false, 'error' => 'Submission already exists for this employee, template, and period'];
            }

            // Create submission record
            $submission_data = [
                'template_id' => $data['template_id'],
                'employee_id' => $data['employee_id'],
                'employee_name' => $data['employee_snapshot']['name'] ?? null,
                'employee_nik' => $data['employee_snapshot']['nik'] ?? null,
                'employee_department' => $data['employee_snapshot']['department'] ?? null,
                'employee_position' => $data['employee_snapshot']['position'] ?? null,
                'employee_role_id' => $data['employee_snapshot']['role_id'] ?? null,
                'employee_role_name' => $data['employee_snapshot']['role_name'] ?? null,
                'period_year' => $data['period_year'],
                'status' => $data['status'] ?? 'SUBMITTED',
                'total_score' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('performance_submissions', $submission_data);
            $submission_id = $this->db->insert_id();

            // Process items and calculate scores
            $total_score = 0;

            foreach ($data['items'] as $item) {
                // Get template item for target and weight
                $template_item = $this->get_template_item($item['template_item_id']);

                if (!$template_item) {
                    throw new Exception("Template item not found: " . $item['template_item_id']);
                }

                if ((int) $template_item['template_id'] !== (int) $data['template_id']) {
                    throw new Exception("Template item does not belong to the selected template.");
                }

                // Validate actual_value >= 0
                if ($item['actual_value'] < 0) {
                    throw new Exception("Actual value must be >= 0");
                }

                // Validate target_value > 0 (prevent division by zero)
                if ($template_item['target_value'] == 0) {
                    throw new Exception("Target value cannot be 0 for item: " . $template_item['objective']);
                }

                // Calculate scores
                $score_ratio = $item['actual_value'] / $template_item['target_value'];
                $final_score = $template_item['weight'] * $score_ratio;

                // Create submission item
                $item_data = [
                    'submission_id' => $submission_id,
                    'template_item_id' => $item['template_item_id'],
                    'actual_value' => $item['actual_value'],
                    'score_ratio' => $score_ratio,
                    'final_score' => $final_score,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->db->insert('performance_submission_items', $item_data);

                $total_score += $final_score;
            }

            // Update submission with total score
            $this->db->where('id', $submission_id);
            $this->db->update('performance_submissions', [
                'total_score' => $total_score,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                return ['success' => false, 'error' => 'Transaction failed'];
            }

            return [
                'success' => true,
                'id' => $submission_id,
                'total_score' => $total_score
            ];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

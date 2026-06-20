<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AnnouncementModel
 *
 * Data access for the announcements table. All list/detail reads exclude
 * soft-deleted rows (deleted_at IS NULL). Filters/sorts mirror the list-page UI
 * and the migration indexes. User input is bound via the query builder.
 */
class AnnouncementModel extends CI_Model
{
    protected $table = 'announcements';

    /** Whitelisted sort keys -> ORDER BY fragment (pinned-first is always prepended). */
    private function sort_map()
    {
        return [
            'newest'       => 'created_at DESC',
            'oldest'       => 'created_at ASC',
            'title'        => 'title ASC',
            'category'     => 'category ASC',
            'priority'     => "FIELD(priority,'URGENT','HIGH','MEDIUM','LOW') ASC",
            'publish'      => 'publish_start_at DESC',
        ];
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Apply shared list filters to the active query builder.
     */
    private function apply_filters(array $f)
    {
        $this->db->where('deleted_at IS NULL', null, false);

        if (!empty($f['search'])) {
            $this->db->group_start()
                ->like('title', $f['search'])
                ->or_like('content', $f['search'])
                ->group_end();
        }
        if (!empty($f['category'])) {
            $this->db->where('category', $f['category']);
        }
        if (!empty($f['subcategory'])) {
            $this->db->where('subcategory', $f['subcategory']);
        }
        if (!empty($f['status'])) {
            $this->db->where('status', $f['status']);
        }
        if (!empty($f['priority'])) {
            $this->db->where('priority', $f['priority']);
        }
        if (isset($f['is_pinned']) && $f['is_pinned'] !== '' && $f['is_pinned'] !== null) {
            $this->db->where('is_pinned', (int) $f['is_pinned']);
        }
        if (!empty($f['publish_start_at'])) {
            $this->db->where('publish_start_at >=', $f['publish_start_at']);
        }
        if (!empty($f['publish_end_at'])) {
            $this->db->where('publish_start_at <=', $f['publish_end_at']);
        }
    }

    /**
     * Paginated, filtered, sorted list.
     */
    public function search(array $filters, $sort = 'newest', $page = 1, $limit = 10)
    {
        $this->apply_filters($filters);

        $map = $this->sort_map();
        $order = isset($map[$sort]) ? $map[$sort] : $map['newest'];
        $this->db->order_by('is_pinned DESC, ' . $order, '', false);

        $page = max(1, (int) $page);
        $limit = max(1, (int) $limit);
        $this->db->limit($limit, ($page - 1) * $limit);

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Total rows for the same filters (pagination).
     */
    public function count(array $filters)
    {
        $this->apply_filters($filters);
        return (int) $this->db->count_all_results($this->table);
    }

    /**
     * Single non-deleted row.
     */
    public function get_by_id($id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row_array();
    }

    public function insert(array $data)
    {
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        return $this->db->where('id', (int) $id)->update($this->table, $data);
    }

    /**
     * Soft delete: stamp deleted_at + updated_by, keep the row.
     */
    public function soft_delete($id, $userId)
    {
        return $this->db->where('id', (int) $id)->update($this->table, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => (int) $userId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Active employee user ids for publish broadcast.
     */
    public function active_employee_ids()
    {
        $rows = $this->db
            ->select('id')
            ->where('status', 'Aktif')
            ->get('user')
            ->result_array();
        return array_map(static function ($r) {
            return (int) $r['id'];
        }, $rows);
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

/**
 * Announcement
 *
 * HR Management announcement CRUD (web dashboard). Extends BaseController, so the
 * per-method permission guard runs automatically via the controller_module_map
 * ('announcement') + method_action_map (index/item/detail=view, create_page/store=create,
 * edit_page/update=edit, remove/delete=delete).
 *
 * The list page loads rows over AJAX (item()) for search/filter/sort/pagination.
 * Publishing an announcement broadcasts an in-app + FCM notification to all active
 * employees (AnnouncementNotificationService), and the mobile detail deep-link is
 * served by Api_hrms::announcement_detail.
 */
class Announcement extends BaseController
{
    private $per_page = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('AnnouncementModel');
        $this->load->library('template');
        $this->load->helper('announcement_categories');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'remove' => 'delete',
        ]);
    }

    /**
     * Read list filters from the request (shared by index + item).
     */
    private function request_filters()
    {
        return [
            'search'           => trim($this->input->get('search') ?? ''),
            'category'         => $this->input->get('category') ?? '',
            'subcategory'      => $this->input->get('subcategory') ?? '',
            'status'           => $this->input->get('status') ?? '',
            'priority'         => $this->input->get('priority') ?? '',
            'is_pinned'        => $this->input->get('is_pinned'),
            'publish_start_at' => $this->input->get('publish_start_at') ?? '',
            'publish_end_at'   => $this->input->get('publish_end_at') ?? '',
        ];
    }

    public function index()
    {
        $data['user'] = $this->user_data;
        $data['title'] = 'Announcements - ' . $this->template->title();
        $data['per_page'] = $this->per_page;
        $data['categories'] = announcement_categories();
        $data['statuses'] = announcement_statuses();
        $data['priorities'] = announcement_priorities();
        $data['permissions'] = $this->get_permission_data('announcement');

        $data['content'] = $this->load->view('announcement/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /**
     * JSON list endpoint powering the AJAX table (search/filter/sort/pagination).
     */
    public function item()
    {
        $filters = $this->request_filters();
        $sort = $this->input->get('sort_by') ?: 'newest';
        $current_page = max(1, (int) ($this->input->get('page') ?? 1));

        $total = $this->AnnouncementModel->count($filters);
        $rows = $this->AnnouncementModel->search($filters, $sort, $current_page, $this->per_page);
        $perms = $this->get_permission_data('announcement');

        $data = array_map(function ($r) {
            return [
                'id'               => (int) $r['id'],
                'title'            => $r['title'],
                'category'         => $r['category'],
                'subcategory'      => $r['subcategory'],
                'status'           => $r['status'],
                'priority'         => $r['priority'],
                'is_pinned'        => (int) $r['is_pinned'],
                'publish_start_at' => $r['publish_start_at'],
                'publish_end_at'   => $r['publish_end_at'],
                'created_at'       => $r['created_at'],
            ];
        }, $rows);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'    => true,
                'data'       => $data,
                'page'       => $current_page,
                'perPage'    => $this->per_page,
                'total'      => $total,
                'totalPages' => (int) ceil($total / $this->per_page),
                'start'      => ($current_page - 1) * $this->per_page,
                'can'        => [
                    'edit'   => (bool) $perms['can_edit'],
                    'delete' => (bool) $perms['can_delete'],
                ],
            ]));
    }

    public function create_page()
    {
        $data['user'] = $this->user_data;
        $data['data'] = [];
        $data['mode'] = 'create';
        $data['title'] = 'Tambah Announcement - ' . $this->template->title();
        $data['categories'] = announcement_categories();
        $data['statuses'] = announcement_statuses();
        $data['priorities'] = announcement_priorities();
        $data['content'] = $this->load->view('announcement/create_page', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function store()
    {
        $dt = $this->input->post('dt');
        $errors = $this->validate($dt);
        if (!empty($errors)) {
            echo $this->template->alert_danger(implode(' ', $errors));
            return;
        }

        $now = date('Y-m-d H:i:s');
        $payload = $this->build_payload($dt);
        $payload['created_by'] = (int) $this->user_id;
        $payload['created_at'] = $now;

        $id = $this->AnnouncementModel->insert($payload);
        if (!$id) {
            echo $this->template->alert_danger('Tambah data tidak berhasil!');
            return;
        }

        if ($payload['status'] === 'PUBLISHED') {
            $this->broadcast($id, $payload);
        }

        echo $this->template->alert_success('Tambah data berhasil!');
    }

    public function edit_page($id = null)
    {
        $id = (int) ($id ?: $this->input->get('id'));
        $row = $this->AnnouncementModel->get_by_id($id);
        if (!$row) {
            redirect(base_url() . 'announcement');
            return;
        }

        $data['user'] = $this->user_data;
        $data['data'] = $row;
        $data['mode'] = 'edit';
        $data['title'] = 'Edit Announcement - ' . $this->template->title();
        $data['categories'] = announcement_categories();
        $data['statuses'] = announcement_statuses();
        $data['priorities'] = announcement_priorities();
        $data['content'] = $this->load->view('announcement/edit_page', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function update()
    {
        $id = (int) $this->input->post('id');
        $existing = $this->AnnouncementModel->get_by_id($id);
        if (!$existing) {
            echo $this->template->alert_danger('Data tidak ditemukan!');
            return;
        }

        $dt = $this->input->post('dt');
        $errors = $this->validate($dt);
        if (!empty($errors)) {
            echo $this->template->alert_danger(implode(' ', $errors));
            return;
        }

        $payload = $this->build_payload($dt);
        $payload['updated_by'] = (int) $this->user_id;
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->AnnouncementModel->update($id, $payload)) {
            echo $this->template->alert_danger('Update data tidak berhasil!');
            return;
        }

        // Broadcast only on the transition into PUBLISHED (avoids re-notifying on every edit).
        if ($payload['status'] === 'PUBLISHED' && $existing['status'] !== 'PUBLISHED') {
            $payload['id'] = $id;
            $this->broadcast($id, $payload);
        }

        echo $this->template->alert_success('Update data berhasil!');
    }

    public function detail($id = null)
    {
        $id = (int) ($id ?: $this->input->get('id'));
        $row = $this->AnnouncementModel->get_by_id($id);
        if (!$row) {
            redirect(base_url() . 'announcement');
            return;
        }

        $data['user'] = $this->user_data;
        $data['data'] = $row;
        $data['permissions'] = $this->get_permission_data('announcement');
        $data['title'] = 'Detail Announcement - ' . $this->template->title();
        $data['content'] = $this->load->view('announcement/detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function delete()
    {
        $id = (int) $this->input->post('id');
        $row = $this->AnnouncementModel->get_by_id($id);
        if (!$row) {
            echo $this->template->alert_danger('Data tidak ditemukan!');
            return;
        }

        if ($this->AnnouncementModel->soft_delete($id, $this->user_id)) {
            echo $this->template->alert_success('Hapus data berhasil!');
        } else {
            echo $this->template->alert_danger('Hapus data tidak berhasil!');
        }
    }

    // ----- helpers -----

    /**
     * Server-side validation. Returns array of error messages (empty = ok).
     */
    private function validate($dt)
    {
        $errors = [];
        if (empty($dt) || !is_array($dt)) {
            return ['Data tidak valid.'];
        }

        if (empty(trim($dt['title'] ?? ''))) {
            $errors[] = 'Judul wajib diisi.';
        }
        if (trim(strip_tags($dt['content'] ?? '')) === '') {
            $errors[] = 'Konten wajib diisi.';
        }

        $category = $dt['category'] ?? '';
        $subcategory = $dt['subcategory'] ?? '';
        if (empty($category)) {
            $errors[] = 'Kategori wajib dipilih.';
        }
        if (empty($subcategory)) {
            $errors[] = 'Subkategori wajib dipilih.';
        }
        if ($category && $subcategory && !announcement_subcategory_valid($category, $subcategory)) {
            $errors[] = 'Subkategori tidak sesuai dengan kategori.';
        }

        $status = $dt['status'] ?? '';
        if (!in_array($status, announcement_statuses(), true)) {
            $errors[] = 'Status tidak valid.';
        }
        $priority = $dt['priority'] ?? '';
        if (!in_array($priority, announcement_priorities(), true)) {
            $errors[] = 'Prioritas tidak valid.';
        }

        $start = $this->normalize_datetime($dt['publish_start_at'] ?? '');
        $end = $this->normalize_datetime($dt['publish_end_at'] ?? '');
        if ($start && $end && strtotime($end) < strtotime($start)) {
            $errors[] = 'Tanggal akhir publikasi tidak boleh sebelum tanggal mulai.';
        }

        return $errors;
    }

    /**
     * Map posted dt[] into a DB column payload (sanitized, normalized).
     */
    private function build_payload($dt)
    {
        return [
            'title'            => trim($dt['title']),
            'content'          => $this->sanitize_html($dt['content']),
            'category'         => $dt['category'],
            'subcategory'      => $dt['subcategory'],
            'status'           => $dt['status'],
            'priority'         => $dt['priority'],
            'publish_start_at' => $this->normalize_datetime($dt['publish_start_at'] ?? '') ?: null,
            'publish_end_at'   => $this->normalize_datetime($dt['publish_end_at'] ?? '') ?: null,
            'is_pinned'        => !empty($dt['is_pinned']) ? 1 : 0,
        ];
    }

    private function broadcast($id, $payload)
    {
        try {
            $this->load->library('AnnouncementNotificationService');
            $this->announcementnotificationservice->broadcastPublished([
                'id'       => (int) $id,
                'title'    => $payload['title'] ?? null,
                'category' => $payload['category'] ?? null,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Announcement broadcast failed: ' . $e->getMessage());
        }
    }

    /**
     * Accepts 'Y-m-d\TH:i' (datetime-local) or 'Y-m-d H:i(:s)' -> 'Y-m-d H:i:s'.
     */
    private function normalize_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $ts = strtotime(str_replace('T', ' ', $value));
        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }

    /**
     * Light hardening of TinyMCE HTML: drop <script>/<iframe>/<style> blocks and
     * inline on* event handlers + javascript: URLs. Project XSS filtering is off,
     * so do not render untrusted markup without this.
     */
    private function sanitize_html($html)
    {
        $html = (string) $html;
        $html = preg_replace('#<\s*(script|iframe|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>]*\2#i', '$1="#"', $html);
        return $html;
    }
}

<style>
    /* Select2 Styling */
    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 32px;
        user-select: none;
        -webkit-user-select: none;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: rgba(0, 0, 0, 0.85);
        line-height: 32px;
        padding-left: 11px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 0px;
        right: 1px;
        width: 20px;
    }

    .select2 {
        height: 32px !important;
        min-width: 100% !important;
        margin-bottom: 8px;
    }

    /* Ant Design-like Table Styling */
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
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 12px 8px !important;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    .btn-outline-secondary {
        color: rgba(0, 0, 0, 0.65);
        border-color: #d9d9d9;
        background: #fff;
    }

    .btn-outline-secondary:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .search-form .input-group {
        border-radius: 2px;
        display: flex;
        justify-content: space-between;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Status Badges */
    .badge-primary {
        background-color: #1890ff;
        color: white;
    }
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
    }
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>
<style>
    :root {
        --primary-color: #3b82f6;
        --secondary-color: #6b7280;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-bg: #f8fafc;
        --border-color: #e5e7eb;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        overflow: hidden;
        background: white;
    }

    .card-header {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem 2rem;
    }

    .card-header h5 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .card-header h5 i {
        color: var(--primary-color);
        margin-right: 0.75rem;
    }

    .card-body {
        padding: 2rem;
    }

    .info-group {
        margin-bottom: 2rem;
    }

    .info-group:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-value {
        font-size: 1rem;
        color: #1f2937;
        margin: 0 0 1rem 0;
        line-height: 1.5;
    }

    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #374151;
    }

    .contact-item i {
        color: var(--primary-color);
        margin-right: 0.75rem;
        width: 16px;
    }

    .status-badges {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bg-info {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .bg-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .bg-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .bg-light {
        background-color: #f3f4f6;
        color: #4b5563;
        border: 1px solid var(--border-color);
    }

    .demographics {
        color: #4b5563;
        line-height: 1.6;
    }

    .work-preference {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        color: #4b5563;
    }

    .salary {
        font-weight: 600;
        color: var(--success-color);
        font-size: 1.125rem;
    }

    .keyword-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .keyword-tag {
        background: #f0f9ff;
        color: #0369a1;
        padding: 0.375rem 0.75rem;
        border-radius: 16px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid #bae6fd;
    }

    .hr-notes-section {
        background: linear-gradient(135deg, #fafbfc 0%, #f3f4f6 100%);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
    }

    .timestamp {
        font-size: 0.875rem;
        color: var(--secondary-color);
        font-weight: 500;
    }

    .section-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        margin: 1.5rem 0;
    }

    .empty-state {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .card-header {
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }
        
        .status-badges {
            justify-content: flex-start;
        }
    }

    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }
    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }
    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }
    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }
    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }
    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }
    .badge-default {
        background-color: #e0e0e0;
        color: #666;
    }

</style>

<div class="container-fluid py-3">
    <?php 
        $data['user'] = $_SESSION['user'];
        
        if (in_array($data['user']['role'], array('1', '2', '3'))) {
            $this->load->view('recruitment/menu');
        }
    ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Recruitment Management</h5>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <div class="dropdown" style="margin-right: 10px;">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="border: 1px solid #d9d9d9; border-radius: 2px; color: rgba(0,0,0,0.65); background-color: #fff; height: 32px; padding: 4px 11px;">
                                    <span style="margin-right: 8px;"><?= $keyword_category ?? 'Nama' ?></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="position: absolute; background-color: white; min-width: 160px; z-index: 9999; border-radius: 2px; box-shadow: 0 3px 6px -4px rgba(0,0,0,0.12), 0 6px 16px 0 rgba(0,0,0,0.08), 0 9px 28px 8px rgba(0,0,0,0.05); padding: 4px 0; margin-top: 2px; left: 0;">
                                    <?php
                                    $arr = array('Nama', 'Posisi');
                                    foreach ($arr as $k => $val) {
                                        $active = (($keyword_category ?? 'Nama') == $val) ? 'background-color: #e6f7ff; color: #1890ff;' : '';
                                    ?>
                                        <li><a class="dropdown-item" href="<?= base_url() ?>/recruitment?keyword_category=<?= $val ?><?= !empty($_GET['keyword']) ? '&keyword=' . $_GET['keyword'] : '' ?><?= !empty($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?>"
                                                style="padding: 5px 12px; font-size: 14px; line-height: 22px; <?= $active ?>">
                                                <?= $val ?>
                                            </a></li>
                                    <?php }  ?>
                                </ul>
                            </div>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?? 'Nama' ?>">
                            <input type="text" name="keyword" class="form-control" placeholder="Search..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <select name="status_filter" class="form-select" style="max-width: 150px; margin-right: 10px;">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= ($status_filter ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="review" <?= ($status_filter ?? '') == 'review' ? 'selected' : '' ?>>Review</option>
                                <option value="interview_hr" <?= ($status_filter ?? '') == 'interview' ? 'selected' : '' ?>>Interview HR</option>
                                <option value="interview_user" <?= ($status_filter ?? '') == 'interview_user' ? 'selected' : '' ?>>Interview User</option>
                                <option value="selected" <?= ($status_filter ?? '') == 'selected' ? 'selected' : '' ?>>Selected</option>
                                <option value="rejected" <?= ($status_filter ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="pertimbangan" <?= ($status_filter ?? '') == 'pertimbangan' ? 'selected' : '' ?>>Pertimbangan</option>
                            </select>
                            <select name="status_testcase" class="form-select" style="max-width: 200px; margin-right: 10px;">
                                <option value="">Semua Status Testcase</option>
                                <option value="pending" <?= ($status_testcase ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="shared_testcase_1" <?= ($status_testcase ?? '') == 'shared_testcase_1' ? 'selected' : '' ?>>Shared Testcase</option>
                                <option value="done_testcase_1" <?= ($status_testcase ?? '') == 'done_testcase_1' ? 'selected' : '' ?>>Done Testcase</option>
                            </select>
                            <button class="btn btn-primary" type="submit"
                                style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-start">#</th>
                            <th class="text-start">Tanggal Apply</th>
                            <th class="text-start">Nama Lengkap</th>
                            <th class="text-start">Posisi Dilamar</th>
                            <th class="text-start">Status Recruitment</th>
                            <th class="text-start">Status Approval</th>
                            <th class="text-start">Status Test Case</th>
                            <th class="text-start">Notes HR</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">

                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <?= $pagination ?>
                </div>
                <div>
                    <?php
                    $per_page_options = [10, 20, 50, 100, 500];
                    $limit = $_GET['limit'] ?? 10;
                    if (!in_array($limit, $per_page_options)) {
                        $limit = 10;
                    }

                    $query_params = $_GET;
                    unset($query_params['limit']);
                    ?>

                    <form method="GET" action="">
                        <?php foreach ($query_params as $key => $value): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endforeach; ?>

                        <select class="form-control select2" name="limit" id="limit"
                            onchange="this.form.submit()">
                            <?php foreach ($per_page_options as $option): ?>
                                <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                                    <?= $option ?> / Halaman
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Notes Editor -->
<div id="notesEditor" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Notes HR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea id="notesTextarea" class="form-control" rows="5" style="height: 20vh;"></textarea>
                <input type="hidden" id="notesApplicantId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveNotesBtn">Simpan</button>
            </div>
        </div>
    </div>
</div>
<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-body p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-pills nav-justified bg-light p-3 mb-0 border-bottom" id="detailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-info-tab" data-bs-toggle="pill" data-bs-target="#personal-info" type="button" role="tab">
                            <i class="bi bi-person-circle me-1"></i>Informasi Pribadi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="resume-tab" data-bs-toggle="pill" data-bs-target="#resume" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1"></i>Resume/CV
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="pill" data-bs-target="#history" type="button" role="tab">
                            <i class="bi bi-clock-history me-1"></i>Riwayat Lamaran
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content p-4" id="detailTabContent">
                    <!-- Personal Info Tab -->
                    <div class="tab-pane fade show active" id="personal-info" role="tabpanel">
                        <div id="personal-info-content">
                            <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume Tab -->
                    <div class="tab-pane fade" id="resume" role="tabpanel">
                        <div id="resume-content">
                            <div class="text-center p-4">
                                <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">Klik tab ini setelah memilih pelamar untuk melihat CV/Resume</p>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div class="tab-pane fade" id="history" role="tabpanel">
                        <div id="history-content">
                            <div class="text-center p-4">
                                <i class="bi bi-clock-history" style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="text-muted mt-2">Klik tab ini setelah memilih pelamar untuk melihat riwayat lamaran</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    .badge-testcase_1 {
        background-color: #d7bdf2;
        color: #4b0082;
    }

    .badge-interview_hr {
        background-color: #ffe7a0;
        color: #7a5900;
    }

    .badge-interview_user {
        background-color: #cde8ff;
        color: #0d6efd;
    }

    .badge-selected {
        background-color: #2e7d32;
        color: white;
    }

    .badge-rejected {
        background-color: #b71c1c;
        color: white;
    }

    .badge-pertimbangan {
        background-color: #f9c998;
        color: #8b4513;
    }

    .modal {
        z-index: 99999;
    }
    
    .editable-notes {
        transition: background-color 0.2s;
    }
    
    .editable-notes:hover {
        background-color: #f5f5f5;
    }

    .clickable-row:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .nav-pills .nav-link {
        color: #495057;
        border-radius: 0.5rem;
        margin: 0 0.25rem;
        font-weight: 500;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
    }

    .nav-pills .nav-link:hover:not(.active) {
        background-color: #e9ecef;
    }

    .tab-content {
        min-height: 500px;
    }

    .modal-xl {
        max-width: 1200px;
    }

    .iframe-container {
        position: relative;
        width: 100%;
        height: 700px;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .iframe-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
</style>

<script>
    /**
     * Loads recruitment data via AJAX
     */
    function loadRecruitmentData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/recruitment/item<?= $param ?>",
            beforeSend: function() {
                $('#tbody').append('<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#tbody tr:last').remove(); // Remove loading indicator
                $('#tbody').append(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody').append('<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    $(document).ready(function() {
        $(document).on('click', '.clickable-row', function(e) {
            if ($(e.target).closest('.editable-status, .editable-notes, .btn').length > 0) {
                return;
            }
            
            const id = $(this).data('id');
            openDetailModal(id);
        });

        $(document).on('click', '.btn-detail', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            openDetailModal(id);
        });

        function openDetailModal(id) {
            $('#personal-info-content').html(`
                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
            
            $('#resume-content').html(`
                <div class="text-center p-4">
                    <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            `);

            $('#history-content').html(`
                <div class="text-center p-4">
                    <i class="bi bi-clock-history" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            `);

            $('#detailModal').modal('show');

            loadPersonalInfo(id);
        }

        function loadPersonalInfo(id) {
            $.ajax({
                url: '<?= base_url() ?>recruitment/get_detail_data',
                method: 'GET',
                data: { id: id },
                success: function(response) {
                    if (response.status === 'success') {
                        const data = response.data;
                        const history = response.history || [];
                        
                        $('#detailModalLabel').html(`
                            <i class="bi bi-person-lines-fill me-2"></i>Detail Pelamar - ${data.nama_lengkap}
                        `);
                        
                        $('#personal-info-content').html(generatePersonalInfoHTML(data));
                        
                        loadResumeContent(data.cv_portofolio);
                        loadHistoryContent(data.tag, history);
                        
                    } else {
                        $('#personal-info-content').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Gagal memuat data: ${response.message}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#personal-info-content').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terjadi kesalahan saat memuat data
                        </div>
                    `);
                }
            });
        }

        function formatWaNumber(number) {
            number = number.replace(/\D/g, '');
            if (number.startsWith('620')) {
                number = '62' + number.substring(3);
            }
            else if (number.startsWith('0')) {
                number = '62' + number.substring(1);
            }
            return number;
        }



        function generatePersonalInfoHTML(data) {
            const waNumber = formatWaNumber(data.no_handphone);

            return `
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Informasi Dasar</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Nama Lengkap</div>
                                            <div class="info-value fw-semibold">${data.nama_lengkap}</div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Posisi Dilamar</div>
                                            <div class="info-value">${data.posisi_dilamar}</div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Kontak</div>
                                            <div class="contact-info">
                                                <div class="contact-item">
                                                    <i class="bi bi-envelope-fill"></i>
                                                    <span>${data.email}</span>
                                                </div>
                                                <div class="contact-item d-flex align-items-center">
                                                    <i class="bi bi-telephone-fill"></i>
                                                    <a href="https://wa.me/${waNumber}" target="_blank" 
                                                    style="text-decoration: none; color: #28a745; margin-right:8px;">
                                                        ${waNumber}
                                                    </a>
                                                    <i class="bi bi-copy copy-icon" data-number="${waNumber}" 
                                                    title="Copy nomor" style="cursor:pointer; color:#6c757d;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-group">
                                            <div class="info-label">Demografi</div>
                                            <div class="demographics">
                                                <div>Usia: ${data.usia} tahun</div>
                                                <div>Domisili: ${data.domisili}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-group">
                                            <div class="info-label">Dokumen Pendukung</div>
                                            <div class="info-value">
                                                ${data.link_drive_dokumen ? `
                                                    <a href="${data.link_drive_dokumen}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-google me-1"></i> Lihat Dokumen Pendukung
                                                    </a>
                                                ` : `
                                                    <span class="empty-state">Tidak tersedia</span>
                                                `}
                                            </div>
                                        </div>
                                        
                                        <div class="info-group">
                                            <div class="info-label">Sosial Media</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                ${renderSocialIcon('instagram', 'fab fa-instagram', data.instagram, 'instagram.com', 'https://instagram.com/')}
                                                ${renderSocialIcon('facebook', 'fab fa-facebook-f', data.facebook, 'facebook.com', 'https://facebook.com/')}
                                                ${renderSocialIcon('linkedin', 'fab fa-linkedin-in', data.linkedin, 'linkedin.com', 'https://linkedin.com/in/')}
                                                ${renderSocialIcon('tiktok', 'fab fa-tiktok', data.tiktok, 'tiktok.com', 'https://tiktok.com/@')}
                                            </div>
                                        </div>
                                        
                                        <div class="info-group">
                                            <div class="info-label">Sumber Info Lowongan</div>
                                            <div class="info-value">
                                                ${data.sumber_info_loker ? data.sumber_info_loker : '<span class="empty-state">Tidak tersedia</span>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Info -->
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i>Informasi Profesional</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Pengalaman Terakhir</div>
                                    <div class="info-value">${data.pengalaman_terakhir}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Perusahaan Terakhir</div>
                                    <div class="info-value">${data.perusahaan_terakhir}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Gaji & Benefit Terakhir</div>
                                    <div class="info-value">${data.gaji_benefit_terakhir}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Ekspektasi Gaji</div>
                                    <div class="salary">Rp ${data.ekspektasi_sallary}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Work Preference -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Preferensi Kerja</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Mode Kerja</div>
                                    <div class="info-value">${data.is_wfo ? '🏢 Work From Office' : '🏠 Work From Home'}</div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Tanggal Bisa Join</div>
                                    <div class="info-value">${data.tanggal_bisa_join}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-copy-check me-2"></i>Status Aplikasi</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-group">
                                    <div class="info-label">Status Recruitment</div>
                                    <div class="status-badges">
                                        ${getRecruitmentStatusBadge(data.status_recruitment)}
                                    </div>
                                </div>
                                <div class="info-group">
                                    <div class="info-label">Status Approval</div>
                                    <div class="status-badges">
                                        ${getApprovalStatusBadge(data.status_approval)}
                                    </div>
                                </div>
                                ${data.link_testcase ? `
                                <div class="info-group">
                                    <div class="info-label">Link Testcase</div>
                                    <div class="info-value">
                                        <a href="${data.link_testcase}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-link-45deg me-1"></i>Lihat Testcase
                                        </a>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Personal Insights -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Pandangan Pribadi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Alasan Join</div>
                                            <div class="info-value">${data.alasan_join}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Visi Misi</div>
                                            <div class="info-value">${data.visi_misi}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">3 Kata Deskripsi Diri</div>
                                            <div class="keyword-tags">
                                                ${data.tiga_kata_diri.split(', ').map(word => `
                                                    <span class="keyword-tag">${word.trim()}</span>
                                                `).join('')}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="info-group">
                                            <div class="info-label">Pantun</div>
                                            <div class="info-value fst-italic">${data.pantun}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HR Notes -->
                    ${data.notes_hr ? `
                    <div class="col-12">
                        <div class="hr-notes-section">
                            <div class="info-group">
                                <div class="info-label"><i class="bi bi-sticky me-1"></i>Catatan HR</div>
                                <div class="info-value">${data.notes_hr}</div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }

        function renderSocialIcon(platform, iconClass, input, expectedDomain, urlPrefix) {
            if (!input) return '';
            
            input = input.trim();
            input = input.startsWith('@') ? input.substring(1) : input;
            
            let url = input;
            if (!new RegExp(`^(https?:\\/\\/)?(www\\.)?${expectedDomain.replace('.', '\\.')}`).test(input)) {
                url = urlPrefix + input;
            } else if (!input.startsWith('http')) {
                url = 'https://' + input;
            }
            
            return `
                <a href="${url}" target="_blank" class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;" title="${platform.charAt(0).toUpperCase() + platform.slice(1)}">
                    <i class="${iconClass}"></i>
                </a>
            `;
        }

        function loadResumeContent(cvUrl) {
            if (cvUrl) {
                let embedUrl = cvUrl;
                
                if (cvUrl.includes('drive.google.com')) {
                    let fileId;
                    const fileIdMatch = cvUrl.match(/\/d\/([a-zA-Z0-9-_]+)/);
                    const openIdMatch = cvUrl.match(/[?&]id=([a-zA-Z0-9-_]+)/);
                    
                    if (fileIdMatch) {
                        fileId = fileIdMatch[1];
                        embedUrl = `https://drive.google.com/file/d/${fileId}/preview`;
                    } else if (openIdMatch) {
                        fileId = openIdMatch[1];
                        embedUrl = `https://drive.google.com/file/d/${fileId}/preview`;
                    } else {
                        embedUrl = cvUrl;
                    }
                }

                $('#resume-content').html(`
                    <div class="iframe-container">
                        <div class="loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <iframe src="${embedUrl}" 
                                onload="$(this).parent().find('.loading-overlay').hide();"
                                onerror="$(this).parent().html('<div class=\"alert alert-warning\"><i class=\"bi bi-exclamation-triangle me-2\"></i>Tidak dapat memuat CV. <a href=\"${cvUrl}\" target=\"_blank\">Buka di tab baru</a></div>');">
                        </iframe>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="${cvUrl}" target="_blank" class="btn btn-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Tab Baru
                        </a>
                    </div>
                `);
            } else {
                $('#resume-content').html(`
                    <div class="text-center p-4">
                        <i class="bi bi-file-earmark-x" style="font-size: 3rem; color: var(--secondary-color);"></i>
                        <p class="empty-state mt-2">CV/Resume tidak tersedia</p>
                    </div>
                `);
            }
        }

        function loadHistoryContent(tag, history) {
            if (tag === 'Already Apply' && history && history.length > 0) {
                let historyHtml = `
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-info-circle me-2"></i>
                        Kandidat ini pernah melamar sebelumnya. Berikut adalah riwayat aplikasi dengan nama yang sama.
                    </div>
                    <div class="row">
                `;
                
                history.forEach((hist, index) => {
                    historyHtml += `
                        <div class="col-md-6">
                            <div class="card border-left-primary">
                                <div class="card-body p-3" style="border-left: 4px solid var(--primary-color);">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1 fw-semibold">${hist.posisi_dilamar}</h6>
                                        <small class="text-muted">#${hist.id}</small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="status-badges">
                                            ${getRecruitmentStatusBadge(hist.status_recruitment)}
                                            ${getApprovalStatusBadge(hist.status_approval)}
                                        </div>
                                    </div>
                                    
                                    <div class="small text-muted mb-2">
                                        <div><i class="bi bi-calendar-event me-1"></i>Apply: ${hist.formatted_created_at}</div>
                                        ${hist.formatted_updated_at ? `<div><i class="bi bi-arrow-repeat me-1"></i>Update: ${hist.formatted_updated_at}</div>` : ''}
                                    </div>
                                    
                                    <div class="small mb-2">
                                        <div class="text-muted">Ekspektasi Gaji:</div>
                                        <div class="fw-semibold text-success">Rp ${hist.ekspektasi_sallary}</div>
                                    </div>
                                    
                                    ${hist.notes_hr ? `
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <small class="text-muted d-block">Catatan HR:</small>
                                        <small>${hist.notes_hr}</small>
                                    </div>
                                    ` : ''}
                                    
                                    ${hist.link_testcase ? `
                                    <div class="mt-2">
                                        <a href="${hist.link_testcase}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye me-1"></i>Lihat Testcase
                                        </a>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                historyHtml += `
                    </div>
                `;
                
                $('#history-content').html(historyHtml);
            } else {
                $('#history-content').html(`
                    <div class="text-center p-4">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: var(--secondary-color);"></i>
                        <p class="empty-state mt-2">
                            ${tag === 'Already Apply' ? 'Tidak ada riwayat lamaran sebelumnya' : 'Kandidat baru - belum ada riwayat lamaran'}
                        </p>
                    </div>
                `);
            }
        }

        function getRecruitmentStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning"><i class="bi bi-hourglass me-1"></i>Pending</span>',
                'testcase_1': '<span class="badge badge-testcase_1"><i class="bi bi-file-earmark-text me-1"></i>Testcase 1</span>',
                'interview_hr': '<span class="badge badge-interview_hr"><i class="bi bi-people me-1"></i>Interview HR</span>',
                'interview_user': '<span class="badge badge-interview_user"><i class="bi bi-person-check me-1"></i>Interview User</span>',
                'selected': '<span class="badge badge-selected"><i class="bi bi-check-circle me-1"></i>Selected</span>',
                'rejected': '<span class="badge badge-rejected"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
                'pertimbangan': '<span class="badge badge-pertimbangan"><i class="bi bi-question-circle me-1"></i>Pertimbangan</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Pending</span>';
        }

        function getApprovalStatusBadge(status) {
            if (!status) return '<span class="badge bg-secondary">Pending</span>';
            
            const badges = {
                'approved': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>',
                'rejected': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Pending</span>';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        $(document).on("click", ".copy-icon", function () {
            let $icon = $(this);
            let number = $icon.data("number");

            navigator.clipboard.writeText(number).then(() => {
                $icon.removeClass("bi-copy").addClass("bi-check-circle");
                $icon.css("color", "#28a745");

                setTimeout(() => {
                    $icon.removeClass("bi-check-circle").addClass("bi-copy");
                    $icon.css("color", "#6c757d");
                }, 1500);
            });
        });


        $(document).on('click', '.editable-status', function(e) {
            if ($(e.target).hasClass('btn-cancel-status') || $(e.target).hasClass('status-select')) {
                return;
            }
            
            $('.edit-mode').addClass('d-none');
            $('.display-mode').removeClass('d-none');
            
            $(this).find('.display-mode').addClass('d-none');
            $(this).find('.edit-mode').removeClass('d-none');
        });
        
        $(document).on('click', '.btn-cancel-status', function(e) {
            e.stopPropagation();
            $(this).closest('.edit-mode').addClass('d-none');
            $(this).closest('.editable-status').find('.display-mode').removeClass('d-none');
        });
        
        $(document).on('change', '.editable-status select', function () {
            const cell = $(this).closest('.editable-status');
            const id = cell.data('id');
            const field = cell.data('field');
            const newValue = $(this).val();
            const linkInput = cell.find('.testcase-link-input');

            if (newValue === 'done_testcase_1') {
                linkInput.removeClass('d-none');

                linkInput.off('change').on('change', function () {
                    const linkTestcase = $(this).val();

                    sendStatusUpdate(id, field, newValue, linkTestcase);
                });

            } else {
                linkInput.addClass('d-none');
                sendStatusUpdate(id, field, newValue, null);
            }
        });

        function sendStatusUpdate(id, field, value, link_testcase = null) {
            $.ajax({
                url: '<?= base_url() ?>recruitment/update_status',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    field: field,
                    value: value,
                    link_testcase: link_testcase
                },
                success: function (response) {
                    if (response.status === 'success') {
                        loadRecruitmentData();
                    } else {
                        alert('Gagal memperbarui status: ' + response.message);
                    }
                },
                error: function () {
                    alert('Gagal memperbarui status. Silakan coba lagi.');
                }
            });
        }

        $(document).on('click', '.editable-notes', function() {
            const id = $(this).data('id');
            const currentNotes = $(this).text().trim();
            
            $('#notesApplicantId').val(id);
            $('#notesTextarea').val(currentNotes === 'Klik untuk menambahkan notes' ? '' : currentNotes);
            
            $('#notesEditor').modal('show');
        });

        $('#saveNotesBtn').click(function() {
            const id = $('#notesApplicantId').val();
            const notes = $('#notesTextarea').val();
            
            $.ajax({
                url: '<?= base_url() ?>recruitment/update_notes',
                method: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    notes: notes
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#notesEditor').modal('hide');
                        loadRecruitmentData();
                    } else {
                        alert('Gagal menyimpan notes: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal menyimpan notes. Silakan coba lagi.');
                }
            });
        });

        if (!$('#modal-custom-css').length) {
            $('head').append(`
                <style id="modal-custom-css">
                    .info-label {
                        font-size: 0.75rem;
                        font-weight: 600;
                        color: #6c757d;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin-bottom: 0.25rem;
                    }
                    
                    .info-value {
                        font-size: 0.95rem;
                        color: #333;
                        margin-bottom: 0;
                        line-height: 1.4;
                    }
                    
                    .badge-testcase_1 {
                        background-color: #d7bdf2;
                        color: #4b0082;
                    }
                    
                    .badge-interview_hr {
                        background-color: #ffe7a0;
                        color: #7a5900;
                    }
                    
                    .badge-interview_user {
                        background-color: #cde8ff;
                        color: #0d6efd;
                    }
                    
                    .badge-selected {
                        background-color: #2e7d32;
                        color: white;
                    }
                    
                    .badge-rejected {
                        background-color: #b71c1c;
                        color: white;
                    }
                    
                    .badge-pertimbangan {
                        background-color: #f9c998;
                        color: #8b4513;
                    }
                </style>
            `);
        }
    });
    loadRecruitmentData();
</script>

<style>
    /* Bootstrap dropdown fix */
    .dropdown-menu {
        z-index: 9999 !important;
    }

    /* Ensure dropdown is visible */
    .dropdown-menu.show {
        display: block !important;
    }
</style>

<script>
    // Initialize Bootstrap dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        // Force initialize Bootstrap dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
</script>
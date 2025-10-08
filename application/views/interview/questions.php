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

    /* Form Controls */
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

    /* Status Badges */
    .badge-success {
        background-color: #52c41a;
        color: white;
    }
    .badge-warning {
        background-color: #faad14;
        color: white;
    }
    .badge-info {
        background-color: #13c2c2;
        color: white;
    }
    .badge-danger {
        background-color: #f5222d;
        color: white;
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
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Interview Questions Management</h5>
                <a href="<?= base_url() ?>interview/add_question" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Question
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="" class="search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <input type="text" name="keyword" class="form-control" placeholder="Search questions..." value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <select name="position_filter" class="form-select" style="max-width: 150px; margin-right: 10px;">
                                <option value="">All Positions</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= $pos['position'] ?>" <?= ($position_filter ?? '') == $pos['position'] ? 'selected' : '' ?>><?= $pos['position'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category_filter" class="form-select" style="max-width: 200px; margin-right: 10px;">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($category_filter ?? '') == $cat['id'] ? 'selected' : '' ?>><?= $cat['category_name'] ?></option>
                                <?php endforeach; ?>
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
                            <th class="text-start">Position</th>
                            <th class="text-start">Category</th>
                            <th class="text-start">Criteria</th>
                            <th class="text-start">Type</th>
                            <th class="text-start">Score Range</th>
                            <th class="text-start">Sort Order</th>
                            <th class="text-start">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">

                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Loads questions data via AJAX
     */
    function loadQuestionsData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/interview/questions_item<?= $param ?>",
            beforeSend: function() {
                $('#tbody').html('<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#tbody').html(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#tbody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    // Initialize the page
    $(document).ready(function() {
        loadQuestionsData();
    });

    // Handle delete question
    function deleteQuestion(id) {
        if (confirm('Are you sure you want to delete this question?')) {
            $.ajax({
                url: '<?= base_url() ?>interview/delete_question',
                method: 'POST',
                dataType: 'json',
                data: { id: id },
                success: function(response) {
                    if (response.status === 'success') {
                        loadQuestionsData();
                    } else {
                        alert('Failed to delete question: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Failed to delete question. Please try again.');
                }
            });
        }
    }
</script>
<?php
if (empty($data)) {
    echo '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        $tag_color_class = match($value['tag']) {
            'New Applied' => 'badge-success',
            'Already Apply' => 'badge-danger',
            default => 'badge-default',
        };


        $recruitment_badge_colors = [
            'testcase_1' => 'badge-testcase_1',
            'interview_hr' => 'badge-interview_hr',
            'interview_user' => 'badge-interview_user',
            'selected' => 'badge-selected',
            'rejected' => 'badge-rejected',
            'pertimbangan' => 'badge-pertimbangan'
        ];

        $status = $value['status_recruitment'] ?? null;

        $recruitment_status_color = $status !== null 
            ? ($recruitment_badge_colors[$status] ?? 'badge-default') 
            : 'badge-default';

        $label_map = [
            'testcase_1' => 'Testcase 1',
            'interview_hr' => 'Interview HR',
            'interview_user' => 'Interview User',
            'selected' => 'Selected',
            'rejected' => 'Rejected',
            'pertimbangan' => 'Pertimbangan'
        ];

        $label = $status !== null 
            ? ($label_map[$status] ?? ucfirst($status)) 
            : 'Pending';

        $approval_status_color = match($value['status_approval']) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-default'
        };

        $testcase_label_map = [
            'shared_testcase_1' => 'Shared Testcase 1',
            'done_testcase_1' => 'Done Testcase 1',
            '' => 'Pending'
        ];        
        $testcase_label = isset($testcase_label_map[$value['status_testcase']]) ? $testcase_label_map[$value['status_testcase']] : 'Pending';
        $testcase_status_color = match($value['status_testcase']) {
            'shared_testcase_1' => 'badge-primary',
            'done_testcase_1' => 'badge-success',
            default => 'badge-default'
        };

?>
        <tr>
            <td><?= $num ?></td>
            <td class="clickable-row" data-id="<?= $value['id'] ?>"><?= date('d M Y', strtotime($value['created_at'])) ?></td>
            <td class="clickable-row" data-id="<?= $value['id'] ?>" style="cursor: pointer;">
                <span class="badge <?= $tag_color_class ?>">
                    <strong><?= $value['nama_lengkap'] ?></strong>
                </span>
            </td>


            <td class="clickable-row" data-id="<?= $value['id'] ?>"><?= $value['posisi_dilamar'] ?></td>
            
            <!-- Kolom Status Recruitment dengan edit on click -->
            <td class="editable-status" data-field="status_recruitment" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $recruitment_badge_colors[$value['status_recruitment']] ?? 'badge-default' ?>">
                    <?= $label ?>
                </span>

                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm status-select" style="height: calc(1.5em + .5rem + 2px);">
                            <option value="pending" <?= $value['status_recruitment'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="testcase_1" <?= $value['status_recruitment'] == 'testcase_1' ? 'selected' : '' ?>>Testcase 1</option>
                            <option value="interview_hr" <?= $value['status_recruitment'] == 'interview_hr' ? 'selected' : '' ?>>Interview HR</option>
                            <option value="interview_user" <?= $value['status_recruitment'] == 'interview_user' ? 'selected' : '' ?>>Interview User</option>
                            <option value="selected" <?= $value['status_recruitment'] == 'selected' ? 'selected' : '' ?>>Selected</option>
                            <option value="rejected" <?= $value['status_recruitment'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="pertimbangan" <?= $value['status_recruitment'] == 'pertimbangan' ? 'selected' : '' ?>>Pertimbangan</option>
                        </select>
                        <button class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center btn-cancel-status" style="height: calc(1.5em + .5rem + 2px);" title="Cancel">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>

            </td>

            <!-- Kolom Approval Status -->
            <td class="editable-status" data-field="status_approval" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $approval_status_color ?>">
                        <?= $value['status_approval'] ? ucfirst($value['status_approval']) : 'Pending' ?>
                    </span>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="height: 28px; line-height: 1;">
                            <option value="" <?= empty($value['status_approval']) ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $value['status_approval'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $value['status_approval'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button class="btn btn-sm btn-outline-danger p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>

            <!-- Kolom Test Case Status -->
            <td class="editable-status" data-field="status_testcase" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $testcase_status_color ?>"><?= $testcase_label ?></span>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm status-select" style="height: 28px; line-height: 1;">
                                <option value="" <?= empty($value['status_testcase']) ? 'selected' : '' ?>>Pending</option>
                                <option value="shared_testcase_1" <?= $value['status_testcase'] == 'shared_testcase_1' ? 'selected' : '' ?>>Shared Testcase 1</option>
                                <option value="done_testcase_1" <?= $value['status_testcase'] == 'done_testcase_1' ? 'selected' : '' ?>>Done Testcase 1</option>
                            </select>

                            <button class="btn btn-sm btn-outline-danger p-0 d-flex align-items-center justify-content-center"
                                style="width: 28px; height: 28px;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>

                        <!-- Floating input di bawah select -->
                        <input type="text" class="form-control form-control-sm testcase-link-input d-none"
                            placeholder="Masukkan link testcase..." style="height: 28px; width: 100%;" />
                    </div>
                </div>


            </td>
            
            <td class="editable-notes" style="cursor: pointer;" data-id="<?= $value['id'] ?>">
                <?= !empty($value['notes_hr']) ? $value['notes_hr'] : '<span class="text-muted">Klik untuk menambahkan notes</span>' ?>
            </td>
        </tr>
<?php
    }
}
?>

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
</style>

<script>
$(document).ready(function() {
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

    // Handle simpan notes
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
});

function getCurrentPage() {
    let params = new URLSearchParams(window.location.search);
    return params.get('page') || 1; 
}

function loadRecruitmentData(page = null) {
    let currentPage = page || getCurrentPage();
    let baseUrl = "<?= base_url() ?>/recruitment/item<?= $param ?>";
    let separator = baseUrl.includes('?') ? '&' : '?';
    
    $.ajax({
        type: 'GET',
        url: baseUrl + separator + "page=" + currentPage,
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


</script>
<?php
if (empty($data)) {
    echo '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>';
} else {
    foreach ($data as $key => $value) {
        $num = $start + $key + 1;
        
        // Format interview datetime
        $interview_datetime = !empty($value['interview_datetime']) ? date('d M Y H:i', strtotime($value['interview_datetime'])) : '-';
        $interview_datetime_value = !empty($value['interview_datetime']) ? date('Y-m-d\TH:i', strtotime($value['interview_datetime'])) : '';
        
        // Status Recruitment Badge
        $recruitment_badge_colors = [
            'interview_hr' => 'badge-primary',
            'interview_user' => 'badge-info',
        ];
        
        $recruitment_status = $value['interview_status'] ?? 'pending';
        $recruitment_status_color = $recruitment_badge_colors[$recruitment_status] ?? 'badge-default';
        
        $recruitment_status_labels = [
            'interview_hr' => 'Interview HR',
            'interview_user' => 'Interview User',
        ];
        
        $recruitment_status_label = $recruitment_status_labels[$recruitment_status] ?? ucfirst($recruitment_status);
        
        // Approval Status Badge
        $approval_status = $value['interview_approval'] ?? '';
        $approval_status_color = match($approval_status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-default'
        };
        $approval_status_label = !empty($approval_status) ? ucfirst($approval_status) : 'Pending';
        
        // Notes content
        $notes_content = !empty($value['notes']) ? $value['notes'] : '<span class="text-muted">Klik untuk menambahkan notes</span>';
        
        // Interviewer
        $interviewer = !empty($value['interviewer']) ? htmlspecialchars($value['interviewer']) : '-';
?>
        <tr>
            <td><?= $num ?></td>
            
            <!-- Interview Datetime Column - Editable -->
            <td class="editable-datetime" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <?= $interview_datetime ?>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <input type="datetime-local" class="form-control form-control-sm datetime-input" 
                               value="<?= $interview_datetime_value ?>" style="height: 28px;">
                        <button class="btn btn-sm btn-outline-danger btn-cancel-datetime p-0 d-flex align-items-center justify-content-center" 
                                style="width: 28px; height: 28px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>
            
            <!-- Interviewer Column - Editable -->
            <td class="editable-interviewer" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <?= $interviewer ?>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control form-control-sm interviewer-input" 
                               value="<?= $interviewer ?>" style="height: 28px; width: 200px;">
                        <button class="btn btn-sm btn-outline-danger btn-cancel-interviewer p-0 d-flex align-items-center justify-content-center" 
                                style="width: 28px; height: 28px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>

            <!-- Interview Link Column -->
            <td>
                <?php if (!empty($value['interview_link'])): ?>
                    <a href="<?= htmlspecialchars($value['interview_link']) ?>" target="_blank" class="text-decoration-none">
                        <?= htmlspecialchars($value['interview_link']) ?>
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm btn-primary btn-generate-link" data-id="<?= $value['id'] ?>">
                        Buat Link
                    </button>
                <?php endif; ?>
            </td>

            
            <td class="clickable-row" data-id="<?= $value['id_job_applications'] ?>"><?= htmlspecialchars($value['nama_lengkap']) ?></td>
            <td class="clickable-row" data-id="<?= $value['id_job_applications'] ?>"><?= htmlspecialchars($value['posisi_dilamar']) ?></td>
            
            <!-- Status Recruitment Column -->
            <td data-field="status_recruitment" data-id="<?= $value['id'] ?>">
                <span class="badge <?= $recruitment_status_color ?>">
                    <?= $recruitment_status_label ?>
                </span>
            </td>
            
            <!-- Approval Status Column -->
            <td class="editable-status" data-field="interview_approval" data-applicant-id="<?= $value['id_job_applications'] ?>" data-id="<?= $value['id'] ?>">
                <div class="display-mode">
                    <span class="badge <?= $approval_status_color ?>">
                        <?= $approval_status_label ?>
                    </span>
                </div>
                <div class="edit-mode d-none">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="height: 28px; line-height: 1;">
                            <option value="" <?= empty($approval_status) ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $approval_status == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $approval_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button class="btn btn-sm btn-outline-danger p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </td>
            
            <!-- Interview Notes Column -->
            <td class="editable-notes" style="cursor: pointer;" data-id="<?= $value['id'] ?>">
                <?= $notes_content ?>
            </td>
            
            <!-- Action Column -->
            <td class="text-end">
                <div class="btn-group" role="group">
                    <a href="<?= base_url() ?>interview/summary/<?= $value['id'] ?>" 
                    class="btn btn-sm btn-info" 
                    title="Input Summary">
                        Input Summary
                    </a>
                </div>
            </td>
        </tr>
<?php
    }
}
?>

<style>
    .editable-date, .editable-status, .editable-notes {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .editable-date:hover, .editable-status:hover, .editable-notes:hover {
        background-color: #f5f5f5;
    }
    
    .badge-default {
        background-color: #d9d9d9;
        color: rgba(0, 0, 0, 0.65);
    }
    
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
    
    .badge-info {
        background-color: #13c2c2;
        color: white;
    }
    
    .date-input {
        width: 120px;
    }

    .btn-summary {
        color: white;
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    
    .btn-summary:hover {
        background-color: #138496;
        border-color: #117a8b;
    }
</style>

<script>
$(document).ready(function() {
    // Handle date field clicks
    $(document).on('click', '.editable-datetime', function(e) {
        if ($(e.target).hasClass('btn-cancel-datetime') || $(e.target).hasClass('datetime-input')) {
            return;
        }
        
        $('.edit-mode').addClass('d-none');
        $('.display-mode').removeClass('d-none');
        
        $(this).find('.display-mode').addClass('d-none');
        $(this).find('.edit-mode').removeClass('d-none');
    });
    
    // Handle cancel datetime button
    $(document).on('click', '.btn-cancel-datetime', function(e) {
        e.stopPropagation();
        $(this).closest('.edit-mode').addClass('d-none');
        $(this).closest('.editable-datetime').find('.display-mode').removeClass('d-none');
    });
    
    // Handle datetime input changes
    $(document).on('change', '.datetime-input', function() {
        const cell = $(this).closest('.editable-datetime');
        const id = cell.data('id');
        const newDatetime = $(this).val();
        
        if (!newDatetime) return;
        
        $.ajax({
            url: '<?= base_url() ?>interview/update_datetime',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                datetime: newDatetime
            },
            success: function(response) {
                if (response.status === 'success') {
                    loadInterviewData();
                } else {
                    alert('Failed to update datetime: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Failed to update datetime. Please try again.');
            }
        });
    });
    
    // Handle interviewer field clicks
    $(document).on('click', '.editable-interviewer', function(e) {
        if ($(e.target).hasClass('btn-cancel-interviewer') || $(e.target).hasClass('interviewer-input')) {
            return;
        }
        
        $('.edit-mode').addClass('d-none');
        $('.display-mode').removeClass('d-none');
        
        $(this).find('.display-mode').addClass('d-none');
        $(this).find('.edit-mode').removeClass('d-none');
    });
    
    // Handle cancel interviewer button
    $(document).on('click', '.btn-cancel-interviewer', function(e) {
        e.stopPropagation();
        $(this).closest('.edit-mode').addClass('d-none');
        $(this).closest('.editable-interviewer').find('.display-mode').removeClass('d-none');
    });
    
    // Handle interviewer input changes
    $(document).on('blur', '.interviewer-input', function() {
        const cell = $(this).closest('.editable-interviewer');
        const id = cell.data('id');
        const newInterviewer = $(this).val();
        
        $.ajax({
            url: '<?= base_url() ?>interview/update_interviewer',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                interviewer: newInterviewer
            },
            success: function(response) {
                if (response.status === 'success') {
                    loadInterviewData();
                } else {
                    alert('Failed to update interviewer: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Failed to update interviewer. Please try again.');
            }
        });
    });
    
    // Handle status field clicks
    $(document).on('click', '.editable-status', function(e) {
        // Prevent event bubbling if clicking child elements
        if ($(e.target).hasClass('btn-cancel-status') || $(e.target).hasClass('status-select')) {
            return;
        }
        
        // Close all active edit modes
        $('.edit-mode').addClass('d-none');
        $('.display-mode').removeClass('d-none');
        
        // Open edit mode for clicked column
        $(this).find('.display-mode').addClass('d-none');
        $(this).find('.edit-mode').removeClass('d-none');
    });
    
    $(document).on('click', '.btn-cancel-status', function(e) {
        e.stopPropagation();
        $(this).closest('.edit-mode').addClass('d-none');
        $(this).closest('.editable-status').find('.display-mode').removeClass('d-none');
    });
    
    $(document).on('change', '.editable-status select', function() {
        const cell = $(this).closest('.editable-status');
        const id = cell.data('id');
        const applicantId = cell.data('applicant-id');
        const field = cell.data('field');
        const newValue = $(this).val();
        
        $.ajax({
            url: '<?= base_url() ?>interview/update_status',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                applicant_id: applicantId,
                field: field,
                value: newValue
            },
            success: function(response) {
                if (response.status === 'success') {
                    loadInterviewData();
                }
            },
            error: function(xhr) {
                alert('Failed to update status. Please try again.');
            }
        });
    });
    
    $(document).on('click', '.editable-notes', function() {
        const id = $(this).data('id');
        const currentNotes = $(this).text().trim();
        
        $('#notesApplicantId').val(id);
        $('#notesTextarea').val(currentNotes === 'Klik untuk menambahkan notes' ? '' : currentNotes);
        
        // Show modal
        $('#notesEditor').modal('show');
    });
    
    // Handle save notes button
    $('#saveNotesBtn').click(function() {
        const id = $('#notesApplicantId').val();
        const notes = $('#notesTextarea').val();
        
        $.ajax({
            url: '<?= base_url() ?>interview/update_notes',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                notes: notes
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#notesEditor').modal('hide');
                    loadInterviewData();
                } else {
                    alert('Failed to save notes: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Failed to save notes. Please try again.');
            }
        });
    });
    
    // Handle schedule interview button
    $(document).on('click', '.btn-schedule', function() {
        const id = $(this).data('id');
        // You can implement a modal or redirect to schedule page
        window.location.href = '<?= base_url() ?>interview/schedule?id=' + id;
    });

    $(document).on('click', '.btn-summary', function() {
        const id = $(this).data('id');
        $('#summaryApplicantId').val(id);
        
        // Load existing summary if available
        $.ajax({
            url: '<?= base_url() ?>interview/get_summary',
            method: 'POST',
            dataType: 'json',
            data: { id: id },
            success: function(response) {
                if (response.status === 'success') {
                    $('#summaryLink').val(response.summary || '');
                }
            },
            error: function(xhr) {
                console.error('Error loading summary');
            }
        });
        
        // Show modal
        $('#summaryModal').modal('show');
    });

    $(document).on('click', '.btn-generate-link', function () {
        const $btn = $(this);
        const id = $btn.data('id');

        if ($btn.prop('disabled')) return;

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Membuat...');

        $.ajax({
            url: '<?= base_url() ?>googlemeet/generate_link',
            method: 'POST',
            dataType: 'json',
            data: { id: id },
            success: function (response) {
                if (response.status === 'redirect') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Login Google Dibutuhkan',
                        text: response.message || 'Silakan login terlebih dahulu.',
                        confirmButtonText: 'Login Sekarang'
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                    return;
                }

                if (response.status === 'success') {
                    const link = response.link || '#';

                    navigator.clipboard.writeText(link).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Link berhasil dibuat dan tersalin di clipboard.',
                            footer: `<a href="${link}" target="_blank">Klik untuk membuka link</a>`,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                        loadInterviewData();
                    }).catch(() => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Berhasil!',
                            text: 'Link berhasil dibuat, namun gagal menyalin ke clipboard.',
                            footer: `<a href="${link}" target="_blank">Klik untuk membuka link</a>`,
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                        loadInterviewData();
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Gagal membuat link Google Meet.'
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal membuat link. Periksa koneksi dan coba lagi.'
                });
            },
            complete: function () {
                $btn.prop('disabled', false).html('Buat Link');
            }
        });
    });



    
    // Handle save summary button
    $('#saveSummaryBtn').click(function() {
        const id = $('#summaryApplicantId').val();
        const summary = $('#summaryLink').val();
        
        if (!summary) {
            alert('Please enter a valid Drive link');
            return;
        }
        
        $.ajax({
            url: '<?= base_url() ?>interview/save_summary',
            method: 'POST',
            dataType: 'json',
            data: {
                id: id,
                summary: summary
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#summaryModal').modal('hide');
                } else {
                    alert('Failed to save summary: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Failed to save summary. Please try again.');
            }
        });
    });
});

function loadInterviewData() {
    $.ajax({
        type: 'GET',
        url: "<?= base_url() ?>/interview/item<?= $param ?>",
        beforeSend: function() {
            $('#tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(data) {
            $('#tbody').html(data);
        },
        error: function(xhr, status, error) {
            console.error('Error loading data:', error);
            $('#tbody').html('<tr><td colspan="8" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}
</script>
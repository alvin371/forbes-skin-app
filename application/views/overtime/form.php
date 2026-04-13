<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Ajukan Lembur</h3>
        <small style="color: rgba(0,0,0,0.45);">Lengkapi data pengajuan lembur sesuai kebutuhan</small>
    </div>

    <div class="card-body" style="padding: 24px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>" enctype="multipart/form-data" id="overtimeForm">
            <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">

            <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
                <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-info-circle"></i> Informasi Lembur
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Tipe Lembur <span style="color: #ff4d4f;">*</span></label>
                            <select name="overtime_type_id" id="overtimeType" class="form-select" style="border-radius: 2px; height: 32px; font-size: 14px;" required>
                                <option value="">-- Pilih Tipe --</option>
                                <?php foreach ($overtime_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"
                                            data-requires-attachment="<?php echo (int) $type['requires_attachment']; ?>"
                                            data-description="<?php echo htmlspecialchars($type['description'] ?? ''); ?>"
                                            <?php echo ((string) $request['overtime_type_id'] === (string) $type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="typeDescription" style="color: rgba(0,0,0,0.45);"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Tanggal Lembur <span style="color: #ff4d4f;">*</span></label>
                            <input type="date" name="overtime_date" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($request['overtime_date']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Jam Mulai <span style="color: #ff4d4f;">*</span></label>
                            <input type="time" name="start_time" id="startTime" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($request['start_time']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Jam Selesai <span style="color: #ff4d4f;">*</span></label>
                            <input type="time" name="end_time" id="endTime" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($request['end_time']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Durasi (Jam)</label>
                            <input type="text" name="duration_hours" id="durationHours" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;"
                                   value="<?php echo htmlspecialchars($request['duration_hours']); ?>" readonly>
                            <small style="color: rgba(0,0,0,0.45);">Otomatis dihitung berdasarkan jam mulai & selesai.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0;">
                <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-chat-left-text"></i> Alasan Lembur
                </h5>
                <div class="mb-3">
                    <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">Alasan <span style="color: #ff4d4f;">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" style="border-radius: 2px; font-size: 14px;" required><?php echo htmlspecialchars($request['reason']); ?></textarea>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h5 style="margin-bottom: 16px; font-size: 14px; font-weight: 500; color: rgba(0,0,0,0.85);">
                    <i class="bi bi-paperclip"></i> Lampiran (Jika Dibutuhkan)
                </h5>
                <div class="mb-3">
                    <label class="form-label" style="font-size: 14px; color: rgba(0,0,0,0.85);">
                        Lampiran <span id="attachmentRequired" style="color: #ff4d4f; display: none;">*</span>
                    </label>
                    <input type="file" name="attachment" id="attachment" class="form-control" style="border-radius: 2px; height: 32px; font-size: 14px;" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="color: rgba(0,0,0,0.45);">Format: PDF, JPG, PNG. Maks 5MB.</small>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f0f0f0;">
                <a href="<?php echo site_url('overtime'); ?>" class="btn btn-secondary" style="border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; border-radius: 2px; height: 32px; padding: 4px 15px; font-size: 14px;">
                    <i class="bi bi-check"></i> Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateDuration() {
    var start = document.getElementById('startTime').value;
    var end = document.getElementById('endTime').value;
    if (!start || !end) {
        document.getElementById('durationHours').value = '';
        return;
    }

    var startParts = start.split(':');
    var endParts = end.split(':');
    if (startParts.length < 2 || endParts.length < 2) {
        document.getElementById('durationHours').value = '';
        return;
    }

    var startMinutes = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
    var endMinutes = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);
    if (endMinutes <= startMinutes) {
        endMinutes += 24 * 60;
    }
    var diffMinutes = endMinutes - startMinutes;
    var hours = diffMinutes / 60;
    document.getElementById('durationHours').value = hours.toFixed(2);
}

function updateAttachmentRequirement() {
    var select = document.getElementById('overtimeType');
    var selected = select.options[select.selectedIndex];
    var requiresAttachment = selected && selected.getAttribute('data-requires-attachment') === '1';
    var description = selected ? selected.getAttribute('data-description') : '';
    var attachmentRequired = document.getElementById('attachmentRequired');
    var attachmentInput = document.getElementById('attachment');
    var descriptionEl = document.getElementById('typeDescription');

    if (descriptionEl) {
        descriptionEl.textContent = description || '';
    }

    if (requiresAttachment) {
        attachmentRequired.style.display = 'inline';
        attachmentInput.setAttribute('required', 'required');
    } else {
        attachmentRequired.style.display = 'none';
        attachmentInput.removeAttribute('required');
    }
}

document.getElementById('startTime').addEventListener('change', calculateDuration);
document.getElementById('endTime').addEventListener('change', calculateDuration);
document.getElementById('overtimeType').addEventListener('change', updateAttachmentRequirement);

calculateDuration();
updateAttachmentRequirement();
</script>

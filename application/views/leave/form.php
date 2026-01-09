<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Submit Leave Request</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f; margin-bottom: 16px;">
                <i class="bi bi-exclamation-circle"></i>
                <?php foreach ($errors as $error): ?>
                    <div style="margin-top: 4px;"><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo $form_action; ?>" enctype="multipart/form-data">
            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
            <?php endif; ?>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                    Leave Type <span style="color: #ff4d4f;">*</span>
                </label>
                <select name="leave_type_id" class="form-select" required style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;">
                    <option value="">Select leave type</option>
                    <?php foreach ($leave_types as $leaveType): ?>
                        <option value="<?php echo (int) $leaveType['id']; ?>" <?php echo ((int) $request['leave_type_id'] === (int) $leaveType['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($leaveType['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                            Start Date <span style="color: #ff4d4f;">*</span>
                        </label>
                        <input type="text" id="leave-start-date" name="start_date" class="form-control js-leave-date" value="<?php echo htmlspecialchars($request['start_date']); ?>" required autocomplete="off" placeholder="YYYY-MM-DD" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                            End Date <span style="color: #ff4d4f;">*</span>
                        </label>
                        <input type="text" id="leave-end-date" name="end_date" class="form-control js-leave-date" value="<?php echo htmlspecialchars($request['end_date']); ?>" required autocomplete="off" placeholder="YYYY-MM-DD" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Reason</label>
                <textarea name="reason" rows="4" class="form-control" style="padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px; width: 100%;"><?php echo htmlspecialchars($request['reason']); ?></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">
                    Attachment <span style="font-size: 12px; color: rgba(0,0,0,0.45);">(PDF/JPG/PNG, max 2MB)</span>
                </label>
                <input type="file" name="attachment" class="form-control" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; margin-right: 8px;">
                    <i class="bi bi-check-circle"></i> Submit
                </button>
                <a href="<?php echo site_url('leave'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    .flatpickr-calendar,
    .flatpickr-months,
    .flatpickr-innerContainer,
    .flatpickr-rContainer {
        background: #fff !important;
        opacity: 1 !important;
    }

    .flatpickr-day.holiday-day {
        background: #fff1f0 !important;
        border-color: #ffa39e !important;
        color: #cf1322 !important;
    }

    .flatpickr-day.holiday-day:hover {
        background: #ffccc7 !important;
        color: #a8071a !important;
    }
</style>

<script>
    (function () {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        var holidays = <?php echo json_encode($holidays ?? array(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        var holidayLookup = {};
        var holidayDates = holidays.map(function (holiday) {
            holidayLookup[holiday.date] = holiday.name || '';
            return holiday.date;
        });

        function attachLeaveDatepicker(selector, options) {
            var input = document.querySelector(selector);
            if (!input) {
                return;
            }

            return flatpickr(input, Object.assign({
                dateFormat: 'Y-m-d',
                allowInput: false,
                minDate: 'today',
                disable: holidayDates,
                onDayCreate: function (dObj, dStr, fp, dayElem) {
                    var date = dayElem.dateObj.getFullYear() + '-' +
                        String(dayElem.dateObj.getMonth() + 1).padStart(2, '0') + '-' +
                        String(dayElem.dateObj.getDate()).padStart(2, '0');
                    if (holidayLookup[date]) {
                        dayElem.classList.add('holiday-day');
                        dayElem.setAttribute('title', holidayLookup[date]);
                    }
                }
            }, options || {}));
        }

        var endPicker = attachLeaveDatepicker('#leave-end-date');
        attachLeaveDatepicker('#leave-start-date', {
            onChange: function (selectedDates) {
                if (!endPicker) {
                    return;
                }

                if (selectedDates && selectedDates.length) {
                    endPicker.set('minDate', selectedDates[0]);
                    if (endPicker.selectedDates.length && endPicker.selectedDates[0] < selectedDates[0]) {
                        endPicker.setDate(selectedDates[0], true);
                    }
                } else {
                    endPicker.set('minDate', 'today');
                }
            }
        });
    })();
</script>

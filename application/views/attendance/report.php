<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; height: 56px; display: flex; align-items: center;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Attendance Report</h3>
    </div>
    <div class="card-body" style="padding: 16px;">
        <form method="get" action="<?php echo site_url('attendance/report'); ?>" style="margin-bottom: 16px;">
            <div class="row g-2">
                <div class="col-md-3">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">Month</label>
                    <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                </div>
                <?php if (!empty($is_admin_hr) && !empty($target_user)): ?>
                <div class="col-md-3">
                    <label style="font-size: 14px; color: rgba(0,0,0,0.85); margin-bottom: 4px; display: block;">User</label>
                    <select name="user_id" class="form-select" style="height: 32px; padding: 4px 11px; border: 1px solid #d9d9d9; border-radius: 2px; font-size: 14px;">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>" <?php echo ($selected_user_id ?? 0) == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px;">
                        <i class="bi bi-search"></i> Apply
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($report['error'])): ?>
            <div class="alert alert-danger" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #fff2f0; border: 1px solid #ffccc7; color: #ff4d4f;">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($report['error']); ?>
            </div>
        <?php else: ?>
            <?php if (!empty($is_admin_hr) && empty($_GET['user_id'])): ?>
                <div class="table-responsive">
                    <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">User</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Present</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Late</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Early Checkout</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Absent</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Leave</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Schedule</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php $summaryData = $summaries[$user['id']] ?? null; ?>
                                <?php
                                    $summaryHasSpecial = !empty($summaryData['special_schedule']);
                                    $displayStart = $summaryData['start_time'] ?? '08:00';
                                    $displayEnd   = $summaryData['end_time']   ?? '17:00';
                                    $inputStart   = $summaryHasSpecial ? $displayStart : '08:00';
                                    $inputEnd     = $summaryHasSpecial ? $displayEnd   : '17:00';
                                ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['present_days'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['late_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['early_checkout_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['absent_count'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo $summaryData['leave_days'] ?? '-'; ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);" class="schedule-cell" data-user-id="<?php echo (int) $user['id']; ?>">
                                        <!-- display view -->
                                        <div class="schedule-display">
                                            <?php if ($summaryHasSpecial): ?>
                                                <span class="schedule-badge" style="background-color: #fff1f0; color: #cf1322; border: 1px solid #ffa39e; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-right: 4px;">Special</span>
                                            <?php endif; ?>
                                            <span class="schedule-times" style="font-size: 13px;"><?php echo htmlspecialchars($displayStart . ' – ' . $displayEnd); ?></span>
                                            <button type="button" class="schedule-edit-btn" title="Edit schedule" style="background: none; border: none; cursor: pointer; color: #1890ff; padding: 0 4px; font-size: 14px; vertical-align: middle;">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        <!-- inline edit form (hidden by default) -->
                                        <div class="schedule-form" style="display:none; margin-top: 6px;">
                                            <div style="display:flex; align-items:center; gap:4px; flex-wrap:wrap; margin-bottom:4px;">
                                                <label style="font-size:12px; color:rgba(0,0,0,0.65);">In:</label>
                                                <input type="time" class="schedule-start-input" value="<?php echo htmlspecialchars($inputStart); ?>" style="height:28px; padding:2px 6px; border:1px solid #d9d9d9; border-radius:2px; font-size:12px; width:90px;">
                                                <label style="font-size:12px; color:rgba(0,0,0,0.65);">Out:</label>
                                                <input type="time" class="schedule-end-input" value="<?php echo htmlspecialchars($inputEnd); ?>" style="height:28px; padding:2px 6px; border:1px solid #d9d9d9; border-radius:2px; font-size:12px; width:90px;">
                                            </div>
                                            <div class="schedule-thresholds" style="font-size:11px; color:rgba(0,0,0,0.45); margin-bottom:4px;"></div>
                                            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                                <button type="button" class="schedule-save-btn" style="height:26px; padding:0 10px; background-color:#1890ff; border:none; border-radius:2px; color:#fff; font-size:12px; cursor:pointer;">Save</button>
                                                <button type="button" class="schedule-reset-btn" style="height:26px; padding:0 10px; background-color:#fff; border:1px solid #d9d9d9; border-radius:2px; color:rgba(0,0,0,0.65); font-size:12px; cursor:pointer;">Reset to Default</button>
                                                <button type="button" class="schedule-cancel-btn" style="height:26px; padding:0 10px; background-color:#fff; border:1px solid #d9d9d9; border-radius:2px; color:rgba(0,0,0,0.65); font-size:12px; cursor:pointer;">Cancel</button>
                                            </div>
                                            <div class="schedule-error" style="font-size:11px; color:#ff4d4f; margin-top:3px;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px;">
                                        <a href="<?php echo site_url('attendance/report?month=' . urlencode($month) . '&user_id=' . (int) $user['id']); ?>" style="color: #1890ff; font-size: 16px;" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php if (!empty($target_user)): ?>
                    <div style="margin-bottom: 16px; padding: 12px; background-color: #fafafa; border-radius: 2px; border: 1px solid #d9d9d9;">
                        <strong style="font-size: 14px; color: rgba(0,0,0,0.85);">User:</strong> <span style="font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($target_user['full_name'] ?? ''); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($report['summary']['special_schedule'])): ?>
                    <div style="margin-bottom: 16px; padding: 12px; background-color: #fff1f0; border-radius: 2px; border: 1px solid #ffa39e; color: #a8071a; display: flex; align-items: center; gap: 8px;">
                        <span style="background-color: #cf1322; color: #fff; padding: 2px 8px; border-radius: 2px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px;">Special Schedule</span>
                        <span style="font-size: 14px;">
                            <?php echo htmlspecialchars(($report['summary']['start_time'] ?? '-') . ' - ' . ($report['summary']['end_time'] ?? '-')); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($report['summary'])): ?>
                    <div class="row g-2" style="margin-bottom: 16px;">
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Present</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['present_days']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Late</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['late_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Early Checkout</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['early_checkout_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Absent</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['absent_count']; ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="padding: 12px; border: 1px solid #d9d9d9; border-radius: 2px; background-color: #fafafa;">
                                <div style="font-size: 12px; color: rgba(0,0,0,0.65); margin-bottom: 4px;">Leave</div>
                                <div style="font-size: 20px; font-weight: 500; color: rgba(0,0,0,0.85);"><?php echo $report['summary']['leave_days']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <a href="<?php echo site_url('attendance/report/pdf?month=' . urlencode($month) . (!empty($target_user['id']) ? '&user_id=' . (int) $target_user['id'] : '')); ?>" target="_blank" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table" style="border: 1px solid #f0f0f0; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background-color: #fafafa;">
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Date</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Status</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">First In</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Last Out</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Late</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Early Checkout</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Late Reason</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Early Reason</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Proof</th>
                                <th style="padding: 12px 8px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['daily'] as $row): ?>
                                <?php
                                    $isFlagged = !empty($row['late']) || !empty($row['early_checkout']);
                                    $rowStyle = $isFlagged ? 'background-color: #fff1f0;' : '';
                                    $notesValue = $row['notes'] ?? array();
                                    if (is_array($notesValue)) {
                                        $notesText = implode(' ', $notesValue);
                                    } else {
                                        $notesText = (string) $notesValue;
                                    }
                                    $lateReason = trim((string) ($row['late_reason'] ?? ''));
                                    $lateAttachmentPath = trim((string) ($row['late_attachment_path'] ?? ''));
                                    $earlyReason = trim((string) ($row['early_checkout_reason'] ?? ''));
                                    $earlyAttachmentPath = trim((string) ($row['early_checkout_attachment_path'] ?? ''));
                                    $firstInProofPath = trim((string) ($row['first_in_proof_path'] ?? ''));
                                    $lastOutProofPath = trim((string) ($row['last_out_proof_path'] ?? ''));
                                ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s; <?php echo $rowStyle; ?>">
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                        <?php if (!empty($row['holiday_name'])): ?>
                                            <span style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-left: 4px;">
                                                <?php echo htmlspecialchars($row['holiday_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['out_of_town'])): ?>
                                            <span style="background-color: #fff7e6; color: #d46b08; border: 1px solid #ffd591; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-left: 4px;">
                                                Dinas Luar Kota
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['first_in'] ?? '-'); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($row['last_out'] ?? '-'); ?></td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($row['late']): ?>
                                            <span style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">Yes</span>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($row['early_checkout']): ?>
                                            <span style="background-color: #fffbe6; color: #faad14; border: 1px solid #ffe58f; padding: 0 8px; height: 22px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px;">Yes</span>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($lateReason !== '' || $lateAttachmentPath !== ''): ?>
                                            <?php if ($lateReason !== ''): ?>
                                                <div><?php echo htmlspecialchars($lateReason); ?></div>
                                            <?php endif; ?>
                                            <?php if ($lateAttachmentPath !== ''): ?>
                                                <div style="margin-top: 4px;">
                                                    <a href="<?php echo htmlspecialchars($lateAttachmentPath); ?>" target="_blank" rel="noopener" style="color: #1890ff; text-decoration: none;">View attachment</a>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($earlyReason !== '' || $earlyAttachmentPath !== ''): ?>
                                            <?php if ($earlyReason !== ''): ?>
                                                <div><?php echo htmlspecialchars($earlyReason); ?></div>
                                            <?php endif; ?>
                                            <?php if ($earlyAttachmentPath !== ''): ?>
                                                <div style="margin-top: 4px;">
                                                    <a href="<?php echo htmlspecialchars($earlyAttachmentPath); ?>" target="_blank" rel="noopener" style="color: #1890ff; text-decoration: none;">View attachment</a>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($firstInProofPath !== '' || $lastOutProofPath !== ''): ?>
                                            <?php if ($firstInProofPath !== ''): ?>
                                                <div>
                                                    <span style="background-color: #fff7e6; color: #d46b08; border: 1px solid #ffd591; padding: 0 8px; height: 20px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-right: 6px;">IN</span>
                                                    <a href="<?php echo htmlspecialchars($firstInProofPath); ?>" target="_blank" rel="noopener" style="color: #1890ff; text-decoration: none;">View photo</a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($lastOutProofPath !== ''): ?>
                                                <div style="margin-top: 4px;">
                                                    <span style="background-color: #fff7e6; color: #d46b08; border: 1px solid #ffd591; padding: 0 8px; height: 20px; display: inline-flex; align-items: center; border-radius: 2px; font-size: 12px; margin-right: 6px;">OUT</span>
                                                    <a href="<?php echo htmlspecialchars($lastOutProofPath); ?>" target="_blank" rel="noopener" style="color: #1890ff; text-decoration: none;">View photo</a>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 8px; font-size: 14px; color: rgba(0,0,0,0.65);">
                                        <?php if ($notesText !== ''): ?>
                                            <?php echo htmlspecialchars($notesText); ?>
                                        <?php else: ?>
                                            <span style="color: rgba(0,0,0,0.45);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($is_admin_hr) && empty($_GET['user_id'])): ?>
<script>
(function () {
    var setScheduleUrl = "<?php echo site_url('attendance/report/set-schedule'); ?>";

    function addMinutes(hhmm, mins) {
        var parts = hhmm.split(':');
        var total = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10) + mins;
        total = ((total % 1440) + 1440) % 1440;
        var h = String(Math.floor(total / 60)).padStart(2, '0');
        var m = String(total % 60).padStart(2, '0');
        return h + ':' + m;
    }

    function updateThresholds(cell) {
        var startVal = cell.querySelector('.schedule-start-input').value;
        var endVal   = cell.querySelector('.schedule-end-input').value;
        var threshEl = cell.querySelector('.schedule-thresholds');
        var parts = [];
        if (startVal) parts.push('Late after ' + addMinutes(startVal, 15));
        if (endVal)   parts.push('Early before ' + addMinutes(endVal, -15));
        threshEl.textContent = parts.join(' | ');
    }

    document.querySelectorAll('.schedule-cell').forEach(function (cell) {
        var userId     = cell.getAttribute('data-user-id');
        var display    = cell.querySelector('.schedule-display');
        var form       = cell.querySelector('.schedule-form');
        var editBtn    = cell.querySelector('.schedule-edit-btn');
        var startInput = cell.querySelector('.schedule-start-input');
        var endInput   = cell.querySelector('.schedule-end-input');
        var saveBtn    = cell.querySelector('.schedule-save-btn');
        var resetBtn   = cell.querySelector('.schedule-reset-btn');
        var cancelBtn  = cell.querySelector('.schedule-cancel-btn');
        var errorEl    = cell.querySelector('.schedule-error');

        updateThresholds(cell);

        startInput.addEventListener('input', function () { updateThresholds(cell); });
        endInput.addEventListener('input',   function () { updateThresholds(cell); });

        editBtn.addEventListener('click', function () {
            display.style.display = 'none';
            form.style.display    = 'block';
            errorEl.textContent   = '';
        });

        cancelBtn.addEventListener('click', function () {
            display.style.display = '';
            form.style.display    = 'none';
            errorEl.textContent   = '';
        });

        function applyResult(data) {
            var isSpecial = data.is_special;
            var start     = data.start_time;
            var end       = data.end_time;

            var badge = cell.querySelector('.schedule-badge');
            var times = cell.querySelector('.schedule-times');

            if (isSpecial) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'schedule-badge';
                    badge.style.cssText = 'background-color:#fff1f0;color:#cf1322;border:1px solid #ffa39e;padding:0 8px;height:22px;display:inline-flex;align-items:center;border-radius:2px;font-size:12px;margin-right:4px;';
                    badge.textContent = 'Special';
                    display.insertBefore(badge, times);
                }
            } else {
                if (badge) { badge.remove(); }
            }

            times.textContent = start + ' \u2013 ' + end;
            startInput.value  = start;
            endInput.value    = end;
            updateThresholds(cell);

            display.style.display = '';
            form.style.display    = 'none';
            errorEl.textContent   = '';
        }

        function postSchedule(startTime, endTime) {
            saveBtn.disabled  = true;
            resetBtn.disabled = true;
            errorEl.textContent = '';

            fetch(setScheduleUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ user_id: parseInt(userId, 10), start_time: startTime, end_time: endTime })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'ok') {
                    applyResult(data);
                } else {
                    errorEl.textContent = data.message || 'Failed to save.';
                }
            })
            .catch(function () {
                errorEl.textContent = 'Network error. Please try again.';
            })
            .finally(function () {
                saveBtn.disabled  = false;
                resetBtn.disabled = false;
            });
        }

        saveBtn.addEventListener('click', function () {
            postSchedule(startInput.value, endInput.value);
        });

        resetBtn.addEventListener('click', function () {
            postSchedule('', '');
        });
    });
})();
</script>
<?php endif; ?>

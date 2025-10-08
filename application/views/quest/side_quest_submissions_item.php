<?php
$k = $start;
if (empty($data)) {
    echo '<tr><td colspan="8" class="text-center" style="padding: 60px 20px; color: rgba(0,0,0,0.45);"><div><i class="bi bi-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i><div style="font-size: 16px; margin-bottom: 8px;">No submissions found</div><div style="font-size: 14px;">Side quest submissions will appear here once submitted by employees.</div></div></td></tr>';
} else {
    foreach ($data as $v) {
        $status_config = [
            'pending' => ['color' => '#faad14', 'bg' => '#fffbe6', 'border' => '#ffe58f', 'icon' => 'hourglass-split', 'text' => '🟡 Pending'],
            'approved' => ['color' => '#52c41a', 'bg' => '#f6ffed', 'border' => '#b7eb8f', 'icon' => 'check-circle', 'text' => '✅ Approved'],
            'denied' => ['color' => '#ff4d4f', 'bg' => '#fff2f0', 'border' => '#ffccc7', 'icon' => 'x-circle', 'text' => '❌ Denied']
        ];
        
        $status = $status_config[$v['status']] ?? $status_config['pending'];
        
        // Use standardized hr_score for consistent scoring across all views
        $hr_score = $v['display_score'] ?? $v['hr_score'] ?? 0;
?>
        <tr style="border-bottom: 1px solid #f0f0f0;">
            <td class="text-start" style="padding: 16px; color: rgba(0,0,0,0.85); font-weight: 500;"><?= $k + 1 ?></td>
            <td class="text-start" style="padding: 16px;">
                <div style="margin-bottom: 4px;">
                    <strong style="color: rgba(0,0,0,0.85); font-size: 14px;"><?= $v['quest_title'] ?></strong>
                </div>
                <div style="color: rgba(0,0,0,0.65); font-size: 12px; line-height: 1.4;">
                    <?= strlen($v['quest_description']) > 80 ? substr($v['quest_description'], 0, 80) . '...' : $v['quest_description'] ?>
                </div>
            </td>
            <td class="text-start" style="padding: 16px;">
                <div style="margin-bottom: 4px;">
                    <strong style="color: rgba(0,0,0,0.85); font-size: 14px;"><?= $v['full_name'] ?></strong>
                </div>
                <div style="color: rgba(0,0,0,0.65); font-size: 12px;"><?= $v['email'] ?></div>
                <div style="margin-top: 6px;">
                    <span style="background-color: #f0f5ff; color: #1890ff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">
                        <?= $v['position_name'] ?> - <?= $v['user_level'] ?>
                    </span>
                </div>
            </td>
            <td class="text-center" style="padding: 16px;">
                <?php if (!empty($v['hasil'])): ?>
                    <button type="button" class="btn btn-sm btn-lihat-hasil" 
                            data-hasil="<?= htmlspecialchars($v['hasil']) ?>" 
                            data-employee="<?= htmlspecialchars($v['full_name']) ?>" 
                            data-quest="<?= htmlspecialchars($v['quest_title']) ?>"
                            style="background-color: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 500;">
                        <i class="bi bi-file-text me-1"></i>Lihat Hasil Kerja
                    </button>
                <?php else: ?>
                    <div style="color: rgba(0,0,0,0.45); font-style: italic; font-size: 12px;">
                        <i class="bi bi-dash"></i> Tidak ada hasil kerja
                    </div>
                <?php endif; ?>
            </td>
            <td class="text-center" style="padding: 16px;">
                <?php if ($v['status'] == 'approved' && $hr_score > 0): ?>
                    <div style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; padding: 8px 12px; border-radius: 16px; display: inline-block;">
                        <i class="bi bi-award-fill me-1"></i>
                        <strong style="font-size: 16px;"><?= $hr_score ?></strong>
                        <small style="font-size: 11px; opacity: 0.8;"> poin</small>
                    </div>
                    <div style="color: rgba(0,0,0,0.45); font-size: 11px; margin-top: 4px;">
                        <?= $hr_score == 100 ? 'Review Video' : 'Approval Standar' ?>
                    </div>
                <?php else: ?>
                    <div style="color: rgba(0,0,0,0.45); font-size: 13px;">
                        <i class="bi bi-clock me-1"></i>
                        <?php if ($v['status'] == 'pending'): ?>
                            <span>Menunggu Review</span>
                        <?php elseif ($v['status'] == 'approved'): ?>
                            <span>Tidak Ada Skor</span>
                        <?php else: ?>
                            <span>Ditolak</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
            <td class="text-center" style="padding: 16px;">
                <span style="background-color: <?= $status['bg'] ?>; color: <?= $status['color'] ?>; border: 1px solid <?= $status['border'] ?>; padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 500;">
                    <?= $status['text'] ?>
                </span>
            </td>
            <td class="text-start" style="padding: 16px;">
                <div style="color: rgba(0,0,0,0.65); font-size: 13px;">
                    <i class="bi bi-calendar me-1"></i><?= date('d M Y', strtotime($v['submitted_at'])) ?>
                </div>
                <div style="color: rgba(0,0,0,0.45); font-size: 12px;">
                    <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($v['submitted_at'])) ?>
                </div>
            </td>
            <td class="text-end" style="padding: 16px;">
                <?php if ($v['status'] == 'pending'): ?>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm" 
                                onclick="showApprovalModal('<?= $v['id'] ?>')" title="Approve Submission"
                                style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; border-radius: 6px 0 0 6px; padding: 6px 12px;">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button type="button" class="btn btn-sm" 
                                onclick="showDenialModal('<?= $v['id'] ?>')" title="Deny Submission"
                                style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; border-radius: 0 6px 6px 0; padding: 6px 12px;">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div style="text-align: center;">
                        <div style="color: <?= $status['color'] ?>; font-size: 12px; font-weight: 500; margin-bottom: 2px;">
                            <i class="bi bi-<?= $status['icon'] ?> me-1"></i><?= ucfirst($v['status']) ?>
                        </div>
                        <?php if ($v['approved_at']): ?>
                            <div style="color: rgba(0,0,0,0.45); font-size: 11px;">
                                <?= date('d M Y', strtotime($v['approved_at'])) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($v['hr_notes'])): ?>
                            <button type="button" class="btn btn-sm mt-1 btn-lihat-notes" 
                                    data-notes="<?= htmlspecialchars($v['hr_notes']) ?>" 
                                    data-employee="<?= htmlspecialchars($v['full_name']) ?>" 
                                    data-quest="<?= htmlspecialchars($v['quest_title']) ?>" 
                                    data-status="<?= $v['status'] ?>"
                                    style="background-color: #f0f5ff; color: #1890ff; border: 1px solid #adc6ff; border-radius: 6px; padding: 2px 8px; font-size: 11px;">
                                <i class="bi bi-chat-text"></i> Notes
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
<?php 
        $k += 1;
    } // end foreach
} // end if ?>
<?php
$search = isset($search) ? (string) $search : '';
$wifi_filter = isset($wifi_filter) ? (string) $wifi_filter : '';
$current_page = isset($current_page) ? (int) $current_page : 1;
$per_page = isset($per_page) ? (int) $per_page : 10;
$total_rows = isset($total_rows) ? (int) $total_rows : 0;
$start_row = $total_rows > 0 ? (($current_page - 1) * $per_page) + 1 : 0;
$end_row = min($total_rows, $current_page * $per_page);
?>

<style>
    .office-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: end;
        margin-bottom: 16px;
        padding: 14px;
        border: 1px solid #f0f0f0;
        border-radius: 10px;
        background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
    }

    .office-field {
        flex: 1 1 220px;
        min-width: 220px;
    }

    .office-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.75);
    }

    .office-field input,
    .office-field select {
        width: 100%;
        height: 38px;
        border: 1px solid #d9d9d9;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 14px;
        background-color: #fff;
    }

    .office-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .office-table-shell {
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        overflow: hidden;
        background-color: #fff;
    }

    .office-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
    }

    .office-table-wrap::-webkit-scrollbar {
        display: none;
    }

    .office-table {
        min-width: 980px;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .office-table th,
    .office-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .office-name-cell {
        max-width: 280px;
        min-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .office-wifi-badge,
    .office-status-badge {
        padding: 0 10px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
    }

    .office-scroll-hint {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 12px 0;
        font-size: 12px;
        color: rgba(0, 0, 0, 0.45);
    }

    .office-bottom-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        margin: 8px 12px 0;
        padding-bottom: 6px;
        scrollbar-color: #b6c6d8 #edf2f7;
        scrollbar-width: thin;
    }

    .office-bottom-scroll::-webkit-scrollbar {
        height: 10px;
    }

    .office-bottom-scroll::-webkit-scrollbar-track {
        background: #edf2f7;
        border-radius: 999px;
    }

    .office-bottom-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #94a3b8 0%, #64748b 100%);
        border-radius: 999px;
    }

    .office-bottom-scroll-inner {
        height: 1px;
    }

    .office-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    @media (max-width: 768px) {
        .office-name-cell {
            max-width: 220px;
            min-width: 220px;
        }
    }
</style>

<div class="card" style="border-radius: 2px; border: 1px solid #f0f0f0; box-shadow: 0 2px 8px rgba(0,0,0,0.09);">
    <div class="card-header" style="background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 16px; min-height: 56px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 500; color: rgba(0,0,0,0.85);">Office Configuration</h3>
        <a href="<?php echo site_url('admin/offices/create'); ?>" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 32px; padding: 4px 15px; border-radius: 2px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
            <i class="bi bi-plus"></i> Create Office
        </a>
    </div>
    <div class="card-body" style="padding: 16px;">
        <?php if ($this->session->flashdata('message')): ?>
            <div class="alert alert-success" style="padding: 8px 15px; border-radius: 2px; font-size: 14px; background-color: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; margin-bottom: 16px;">
                <i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('message'); ?>
            </div>
        <?php endif; ?>

        <form method="get" action="<?php echo site_url('admin/offices'); ?>" class="office-tools">
            <div class="office-field">
                <label for="office-search">Search</label>
                <input id="office-search" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, SSID, or BSSID">
            </div>
            <div class="office-field" style="max-width: 260px;">
                <label for="office-wifi-filter">Wi-Fi Status</label>
                <select id="office-wifi-filter" name="wifi_filter">
                    <option value="" <?php echo $wifi_filter === '' ? 'selected' : ''; ?>>All Wi-Fi</option>
                    <option value="active" <?php echo $wifi_filter === 'active' ? 'selected' : ''; ?>>Wi-Fi Active</option>
                    <option value="inactive" <?php echo $wifi_filter === 'inactive' ? 'selected' : ''; ?>>Wi-Fi Inactive</option>
                </select>
            </div>
            <div class="office-field" style="flex: 0 0 auto; min-width: auto;">
                <label>Rows</label>
                <div style="height: 38px; display: flex; align-items: center; padding: 0 12px; border: 1px solid #d9d9d9; border-radius: 8px; background: #fafafa; font-size: 14px; color: rgba(0,0,0,0.65);">
                    10 per page
                </div>
            </div>
            <div class="office-actions">
                <button type="submit" class="btn btn-primary" style="background-color: #1890ff; border-color: #1890ff; height: 38px; padding: 6px 16px; border-radius: 8px; font-size: 14px;">
                    <i class="bi bi-search"></i> Apply
                </button>
                <a href="<?php echo site_url('admin/offices'); ?>" class="btn btn-outline-secondary" style="color: rgba(0,0,0,0.65); border-color: #d9d9d9; background: #fff; height: 38px; padding: 6px 16px; border-radius: 8px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>

        <div style="margin-bottom: 12px; font-size: 13px; color: rgba(0,0,0,0.55);">
            Showing <?php echo $start_row; ?>-<?php echo $end_row; ?> of <?php echo $total_rows; ?> offices. Wi-Fi active rows are shown first.
        </div>

        <div class="office-table-shell">
            <div id="office-table-wrap" class="office-table-wrap">
                <table class="table office-table">
                    <thead>
                        <tr style="background-color: #fafafa;">
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">ID</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Name</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Wi-Fi</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Lat</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Lng</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Radius (m)</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Min Accuracy (m)</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Office Active</th>
                            <th style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; font-weight: 500; color: rgba(0,0,0,0.85); font-size: 14px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($offices)): ?>
                            <tr>
                                <td colspan="9" style="padding: 24px; text-align: center; font-size: 14px; color: rgba(0,0,0,0.45);">No offices matched your filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($offices as $office): ?>
                                <?php $wifiActive = (int) ($office['wifi_active'] ?? 0) === 1; ?>
                                <tr style="border-bottom: 1px solid #f0f0f0; transition: background-color 0.3s;">
                                    <td style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo (int) $office['id']; ?></td>
                                    <td class="office-name-cell" style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.75);" title="<?php echo htmlspecialchars($office['name']); ?>">
                                        <?php echo htmlspecialchars($office['name']); ?>
                                    </td>
                                    <td style="padding: 12px 10px; font-size: 14px;">
                                        <?php if ($wifiActive): ?>
                                            <span class="office-wifi-badge" style="background-color: #f0fdf4; color: #15803d; border: 1px solid #86efac;">
                                                <i class="bi bi-wifi" style="margin-right: 6px;"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="office-wifi-badge" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fdba74;">
                                                <i class="bi bi-wifi-off" style="margin-right: 6px;"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($office['lat']); ?></td>
                                    <td style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($office['lng']); ?></td>
                                    <td style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($office['radius_m']); ?></td>
                                    <td style="padding: 12px 10px; font-size: 14px; color: rgba(0,0,0,0.65);"><?php echo htmlspecialchars($office['min_accuracy_m']); ?></td>
                                    <td style="padding: 12px 10px; font-size: 14px;">
                                        <?php if ((int) $office['is_active'] === 1): ?>
                                            <span class="office-status-badge" style="background-color: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f;">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="office-status-badge" style="background-color: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7;">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 10px; font-size: 14px;">
                                        <a href="<?php echo site_url('admin/offices/' . $office['id'] . '/edit'); ?>" style="color: #1890ff; margin-right: 12px; font-size: 16px;" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/duplicate'); ?>" style="display:inline; margin-right: 12px;" onsubmit="return confirm('Duplicate this office?');">
                                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                            <?php endif; ?>
                                            <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #722ed1; font-size: 16px;" title="Duplicate">
                                                <i class="bi bi-files"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/delete'); ?>" style="display:inline; margin-right: 12px;" onsubmit="return confirm('Delete this office?');">
                                            <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                            <?php endif; ?>
                                            <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #ff4d4f; font-size: 16px;" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php if ((int) $office['is_active'] !== 1): ?>
                                            <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/activate'); ?>" style="display:inline;">
                                                <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                                    <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                                <?php endif; ?>
                                                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #52c41a; font-size: 16px;" title="Activate">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="<?php echo site_url('admin/offices/' . $office['id'] . '/deactivate'); ?>" style="display:inline;">
                                                <?php if (!empty($csrf_name) && !empty($csrf_hash)): ?>
                                                    <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                                                <?php endif; ?>
                                                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #faad14; font-size: 16px;" title="Deactivate">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="office-scroll-hint">
                <span>Horizontal scroll</span>
                <span>Use the bar below to move long rows sideways.</span>
            </div>
            <div id="office-bottom-scroll" class="office-bottom-scroll">
                <div id="office-bottom-scroll-inner" class="office-bottom-scroll-inner"></div>
            </div>
        </div>

        <div class="office-footer">
            <div style="font-size: 13px; color: rgba(0,0,0,0.55);">
                Ordered by Wi-Fi active first, then office active.
            </div>
            <div>
                <?php if ($total_rows > 0): ?>
                    <?php echo $pagination; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var tableWrap = document.getElementById('office-table-wrap');
        var bottomScroll = document.getElementById('office-bottom-scroll');
        var bottomScrollInner = document.getElementById('office-bottom-scroll-inner');

        if (!tableWrap || !bottomScroll || !bottomScrollInner) {
            return;
        }

        var syncingFromTable = false;
        var syncingFromBottom = false;

        function syncWidth() {
            var table = tableWrap.querySelector('table');
            if (!table) {
                return;
            }
            bottomScrollInner.style.width = table.scrollWidth + 'px';
            bottomScroll.style.display = table.scrollWidth > tableWrap.clientWidth ? 'block' : 'none';
        }

        tableWrap.addEventListener('scroll', function () {
            if (syncingFromBottom) {
                return;
            }
            syncingFromTable = true;
            bottomScroll.scrollLeft = tableWrap.scrollLeft;
            syncingFromTable = false;
        });

        bottomScroll.addEventListener('scroll', function () {
            if (syncingFromTable) {
                return;
            }
            syncingFromBottom = true;
            tableWrap.scrollLeft = bottomScroll.scrollLeft;
            syncingFromBottom = false;
        });

        window.addEventListener('resize', syncWidth);
        syncWidth();
    })();
</script>

<?php
function separator_only($angka) {
    $number = doubleval($angka);
    return number_format(round($number), 0, ',', '.');
}
?>
<?php foreach ($data as $v): 
    if ($v['platform'] == "Tiktok") {
        $v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
    } else if ($v['platform'] == "Instagram") {
        $v['img'] = base_url() . '/assets/img/icon/icon-ig.png';
    } else if ($v['platform'] == "Youtube") {
        $v['img'] = base_url() . '/assets/img/icon/icon-youtube.png';
    } else if ($v['platform'] == "Facebook") {
        $v['img'] = base_url() . '/assets/img/icon/icon-fb.png';
    } else if ($v['platform'] == "Twitter") {
        $v['img'] = base_url() . '/assets/img/icon/icon-twitter.png';
    } else {
        $v['img'] = base_url() . '/assets/img/icon/icon-no.png';
    }
    if ($v['nama_creator'] == "") {
        $v['nama_creator'] = "-";
    }
    if ($v['posting_at']) {
        $v['posting_at'] = DATE("d/m/Y", strtotime($v['posting_at']));
    } else {
        $v['posting_at'] = '-';
    }
    if ($v['rencana_at']) {
        $v['rencana_at'] = DATE("d/m/Y", strtotime($v['rencana_at']));
    } else {
        $v['rencana_at'] = '-';
    }
    if ($v['barang_dikirim_at']) {
        $v['barang_dikirim_at'] = DATE("d/m/Y", strtotime($v['barang_dikirim_at']));
    } else {
        $v['barang_dikirim_at'] = '-';
    }
    if ($v['sync_at']) {
        $v['sync_at'] = DATE("d/m/Y", strtotime($v['sync_at']));
    } else {
        $v['sync_at'] = '-';
    }
    if ($v['created_at']) {
        $v['created_at'] = DATE("d/m/Y", strtotime($v['created_at']));
    } else {
        $v['created_at'] = '-';
    }
    if ($v['link_brief']) {
        $v['link_brief'] = '<a href="' . $v['link_brief'] . '" target="_blank">' . $v['link_brief'] . '</a>';
    } else {
        $v['link_brief'] = '-';
    }
    if ($v['link_mou']) {
        $v['link_mou'] = '<a href="' . $v['link_mou'] . '" target="_blank">' . $v['link_mou'] . '</a>';
    } else {
        $v['link_mou'] = '-';
    }
    if ($v['link_upload']) {
        $v['img'] = '<a href="' . $v['link_upload'] . '" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="' . $v['img'] . '"></a>';
    } else {
        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img'] . '">';
    }
    
    $bg = '#e6e6e6';
    $clr = '#000';
    if (in_array($v['status_endorse'], array('Review'))) {
        $bg = '#e6e6e6';
    } else if (in_array($v['status_endorse'], array('Hold'))) {
        $bg = '#ffe599';
    } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
        $bg = '#f6b26b';
    } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
        $bg = '#ffd0d0';
    } else if (in_array($v['status_endorse'], array('Draft Content'))) {
        $bg = '#d4edbc';
    } else if (in_array($v['status_endorse'], array('Posted Content'))) {
        $bg = '#7bd3ea';
    } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
        $bg = '#ea7b7b';
    }

    if (in_array($v['status_payment'], array('DP'))) {
        $bg_payment = '#60bb55';
        $clr_payment = '#000';
    } else if (in_array($v['status_payment'], array('FP'))) {
        $bg_payment = '#1255cc';
        $clr_payment = '#000';
    } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
        $bg_payment = '#e6e6e6';
        $clr_payment = '#000';
    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
        $bg_payment = '#C2FFC7';
        $clr_payment = '#000';
    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
        $bg_payment = '#A5BFCC';
        $clr_payment = '#000';
    } else if (in_array($v['status_payment'], array(' '))) {
        $bg_payment = '#fff';
        $clr_payment = '#fff';
    } else if (in_array($v['pengajuan_status_payment'], array(' '))) {
        $bg_payment = '#fff';
        $clr_payment = '#fff';
    }

    if (empty($v['desc'])) {
        $v['desc'] = '-';
    }

    $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
    $v['type'] = $creator['type'];
    $v['tipe_kontak'] = $creator['tipe_kontak'];
    $v['url'] = $creator['url'];

    if ($v['type'] == "Tiktok") {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-tiktok.png';
    } else if ($v['type'] == "Instagram") {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-ig.png';
    } else if ($v['type'] == "Youtube") {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-youtube.png';
    } else if ($v['type'] == "Facebook") {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-fb.png';
    } else if ($v['type'] == "Twitter") {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-twitter.png';
    } else {
        $v['img_creator_1'] = base_url() . '/assets/img/icon/icon-no.png';
    }

    if ($v['url']) {
        $v['img_creator_1'] = '<a href="' . $v['url'] . '" target="_blank"><img style="width:30px;border-radius:40px;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '"></a>';
    } else {
        $v['img_creator_1'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0 me-1" src="' . $v['img_creator_1'] . '">';
    }

    if ($v['tipe_kontak'] == "WA") {
        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-wa.png';
    } else if ($v['tipe_kontak'] == "IG") {
        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-ig.png';
    } else if ($v['tipe_kontak'] == "Email") {
        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-email.png';
    } else if ($v['tipe_kontak'] == "HP") {
        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-phone.png';
    } else {
        $v['img_creator_2'] = base_url() . '/assets/img/icon/icon-no.png';
    }

    if ($v['contact']) {
        $v['img_creator_2'] = '<a href="' . $v['contact'] . '" target="_blank"><img style="width:30px;border-radius:40px;" class="mt-0" src="' . $v['img_creator_2'] . '"></a>';
    } else {
        $v['img_creator_2'] = '<img style="width:30px;border-radius:40px; filter: grayscale(100%)!important;" class="mt-0" src="' . $v['img_creator_2'] . '">';
    }
    $creator = $this->mymodel->selectDataOne('influencer', array('id' => $v['influencer']));
    $v['nama_creator'] = $v['nama_creator'] ?: "-";
    $v['pic'] = $v['pic'] ?: "-";
    $v['desc'] = $v['desc'] ?: "-";
    
    // Status background color
    $bg = '#e6e6e6';
    $clr = '#000';
    if (in_array($v['status_endorse'], array('Hold'))) {
        $bg = '#ffe599';
    } else if (in_array($v['status_endorse'], array('Acc', 'Pengajuan Payment'))) {
        $bg = '#f6b26b';
    } else if (in_array($v['status_endorse'], array('Barang Dikirim'))) {
        $bg = '#ffd0d0';
    } else if (in_array($v['status_endorse'], array('Draft Content'))) {
        $bg = '#d4edbc';
    } else if (in_array($v['status_endorse'], array('Posted Content'))) {
        $bg = '#7bd3ea';
    } else if (in_array($v['status_endorse'], array('Reject', 'Problem'))) {
        $bg = '#ea7b7b';
    }
    
    // Payment status
    $bg_payment = '#fff';
    $clr_payment = '#000';
    if (in_array($v['status_payment'], array('DP'))) {
        $bg_payment = '#60bb55';
    } else if (in_array($v['status_payment'], array('FP'))) {
        $bg_payment = '#8CCDEB';
    } else if (in_array($v['status_payment'], array('Pengajuan Payment'))) {
        $bg_payment = '#e6e6e6';
    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment DP'))) {
        $bg_payment = '#C2FFC7';
    } else if (in_array($v['pengajuan_status_payment'], array('Pengajuan Payment FP'))) {
        $bg_payment = '#A5BFCC';
    }
?>
<tr>
    <!-- <td><?= $k + 1 ?></td> -->
    <td>
        <div class="checkbox-wrapper-13 d-inline">
            <input class="checkItem" style="" type="checkbox" value="<?= $v['id'] ?>" data-id="<?= $k ?>" name="list_id" form="form-action">
        </div>
    </td>
    <td class="text-start clickable-row" data-id = "<?= $v['endorse_id'] ?>">
        <div>
            <span class="fw-bold" style="font-size: 16px;"><?= $v['nama_creator'] ?></span>
            <i class="bi bi-eye text-blue ms-1" id="eye-list-<?= $v['nama_creator'] ?>"></i>
        </div>

        <div class="d-flex align-items-center mt-1">
            <?= $v['img_creator_1'] ?><br>
            <?= $v['img_creator_2'] ?>
        </div>
    </td>
    <td class="clickable-row" data-id = "<?= $v['endorse_id'] ?>"><?= $v['pic'] ? $v['pic'] : '-' ?></td>
    <td class="clickable-row" data-id = "<?= $v['endorse_id'] ?>"><?= $v['campaign_name'] ?: '-' ?></td>
    <td class="clickable-row" data-id = "<?= $v['endorse_id'] ?>"><?= $v['spv'] ? $v['spv'] : '-' ?></td>
    <td class="clickable-row" data-id = "<?= $v['endorse_id'] ?>"><?= separator_only($v['total_cost']) ?></td>
    <td class="clickable-row" data-id = "<?= $v['endorse_id'] ?>"><?= $v['rencana_at'] ?></td>
    <td class="editable-status" data-field="status_endorse" data-id="<?= $v['endorse_id'] ?>" style="width: 200px">
        <div class="edit-mode">
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm status-select" style="height: calc(1.5em + .5rem + 2px);">
                    <option value="Review" <?= $value['status_endorse'] == 'Review' ? 'selected' : '' ?>>Review</option>
                    <option value="Hold" <?= $value['status_endorse'] == 'Hold' ? 'selected' : '' ?>>Hold</option>
                    <option value="Acc" <?= $value['status_endorse'] == 'Acc' ? 'selected' : '' ?>>Acc</option>
                    <option value="Draft Content" <?= $value['status_endorse'] == 'Draft Content' ? 'selected' : '' ?>>Draft Content</option>
                    <option value="Posted Content" <?= $value['status_endorse'] == 'Posted Content' ? 'selected' : '' ?>>Posted Content</option>
                    <option value="Reject" <?= $value['status_endorse'] == 'Reject' ? 'selected' : '' ?>>Reject</option>
                    <option value="Problem" <?= $value['status_endorse'] == 'Problem' ? 'selected' : '' ?>>Problem</option>
                </select>
            </div>
        </div>

    </td>
    <td style="white-space: normal; word-wrap: break-word; min-width: 300px;">
        <?= $v['desc'] ?>
    </td>

</tr>
<?php $k += 1; ?>
<?php endforeach; ?>

<script type="text/javascript">
    $('input[name="list_id"]').change(function() {
        get_id();
    });

    $(document).ready(function() {
        $('[id^="eye-list-"]').each(function() {
            const eyeIcon = this;
            const creatorName = eyeIcon.id.replace('eye-list-', '');
            
            tippy(eyeIcon, {
                content: '<div class="p-2"><div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Memuat data...</div></div>',
                allowHTML: true,
                interactive: true,
                placement: 'right',
                theme: 'light',
                maxWidth: 700,
                onShow(instance) {
                    $.ajax({
                        url: '<?= base_url() ?>endorse/get_influencer_data_all',
                        type: 'POST',
                        data: { nama_creator: creatorName },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                const tooltipContent = `
                                    <div class="report-card p-2" style="width: 100%; max-width: 600px; line-height: 1.5; font-size: 13px;">
                                        <div style="display: flex; flex-direction: row; align-items: flex-start; gap: 10px;">
                                            
                                            <!-- Internal Section -->
                                            <div style="flex: 1; min-width: 150px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Internal</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er || 0}</span>
                                                </div>
                                            </div>

                                            <!-- Eksternal Section -->
                                            <div style="flex: 1; min-width: 150px; border-left: 1px solid #ccc; padding-left: 15px;">
                                                <div class="text-secondary fw-bold" style="margin-bottom: 5px;">Eksternal</div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">Avg. Views</span>
                                                    <span style="white-space: nowrap;">${data.avg_views_2 || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span class="text-secondary">CPM</span>
                                                    <span style="white-space: nowrap;">${data.cpm_2 || 0}</span>
                                                </div>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span class="text-secondary">ER</span>
                                                    <span style="white-space: nowrap;">${data.er_2 || 0}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    `;



                                instance.setContent(tooltipContent);
                            } else {
                                throw new Error("Data tidak valid");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            instance.setContent(`
                                <div class="p-2 text-danger">
                                    Gagal memuat data influencer
                                    <div class="small mt-2">${error}</div>
                                </div>
                            `);
                        }
                    });
                }
            });
        });
    });
</script>
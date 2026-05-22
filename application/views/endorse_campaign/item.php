<?php
$k = $start;
function separator_only($angka)
{
    $number = $angka;
    return number_format(round($number), 0, ',', '.');
}
function date_format_indo($date)
{
    $month = array(
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    if ($date) {
        $date = DATE('d', strtotime($date)) . ' ' . $month[intval(DATE('m', strtotime($date)))] . ' ' . DATE('Y', strtotime($date));
    } else {
        $date = '-';
    }
    return $date;
}
foreach ($data as $v) {

    if ($v['desc'] == "") {
        $v['desc'] = '-';
    }
?>
    <div class="card mb-3" style="padding-bottom:0px">
        <div class="row">
            <div class="col-lg-8">
                <p class="mt-0 mb-0"><a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse?id_campaign=<?= $v['id'] ?>">#<?= $k + 1 ?></a></p>
                <p class="mt-0 mb-1"><a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse?id_campaign=<?= $v['id'] ?>"><?= $v['title'] ?></a></p>
                <p class="mb-1 text-black">SPV : <?= $v['spv'] ? $v['spv'] : '-' ?></p>
                <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>
                <p class="mb-1 text-black">Produk : <?= $v['product_text'] ? $v['product_text'] : '-' ?></p>
                <p class="mb-1 text-black">Keterangan : <?= $v['desc'] ?></p>

                <?php if (!empty($v['media_file']) && !empty($v['media_type'])) { ?>
                    <div class="mt-2">
                        <strong>Media:</strong>
                        <?php if ($v['media_type'] == 'image') { ?>
                            <a href="<?= base_url() ?>assets/img/endorse_campaign/<?= $v['media_file'] ?>" target="_blank">
                                <img src="<?= base_url() ?>assets/img/endorse_campaign/<?= $v['media_file'] ?>" alt="Campaign Media" style="max-width: 150px; max-height: 100px; display:block; margin-top:5px; border-radius:5px;">
                            </a>
                        <?php } elseif ($v['media_type'] == 'video') { ?>
                            <video controls style="max-width: 200px; max-height: 120px; display:block; margin-top:5px; border-radius:5px;">
                                <source src="<?= base_url() ?>assets/img/endorse_campaign/<?= $v['media_file'] ?>" type="video/<?= pathinfo($v['media_file'], PATHINFO_EXTENSION) == 'mov' ? 'quicktime' : 'mp4' ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            <div class="col-lg-4 text-lg-end text-start">
                <?php
                $refresh = isset($card_refresh_meta[$v['id']]) ? $card_refresh_meta[$v['id']] : [];
                $pending_count = intval($refresh['pending_count'] ?? 0);
                $processing_count = intval($refresh['processing_count'] ?? 0);
                $active_refresh_count = $pending_count + $processing_count;
                $last_child_sync_at = $refresh['last_child_sync_at'] ?? null;
                ?>
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
                <a href="#!" onclick="refreshCampaign('<?= $v['id'] ?>')"
                   class="btn btn-outline-secondary mt-0 ms-1 mb-2"
                   id="refresh-btn-<?= $v['id'] ?>">
                   <i class="bi bi-arrow-clockwise fs-16"></i> Refresh
                </a>
                <small class="text-muted d-block mt-0" id="sync-time-<?= $v['id'] ?>">
                    <?php if ($active_refresh_count > 0) { ?>
                        Refresh queue: <?= $pending_count ?> pending, <?= $processing_count ?> processing
                    <?php } else { ?>
                        Update konten: <?= $last_child_sync_at ? date('d/m/Y H:i', strtotime($last_child_sync_at)) : 'Belum' ?>
                    <?php } ?>
                </small>
                <?php if ($active_refresh_count > 0) { ?>
                    <small class="d-block mt-1">
                        <a href="<?= base_url() ?>endorse/queue?id_campaign=<?= $v['id'] ?>" class="text-decoration-none">Lihat antrian refresh</a>
                    </small>
                <?php } ?>
            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12 pb-3">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Jumlah Influencer : <?= separator_only($v['count_influencer']) ?></p>
                        <p class="mb-1 text-black">Jumlah Endorse : <?= separator_only($v['count_endorse']) ?></p>
                        <p class="mb-1 text-black">Tgl Mulai : <?= date_format_indo($v['start_at']) ?></p>
                        <p class="mb-1 text-black">Tgl Selesai : <?= date_format_indo($v['until_at']) ?></p>
                        <p class="mb-1 text-black">Status : <?= $v['status'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Likes : <?= separator_only($v['likes']) ?></p>
                        <p class="mb-1 text-black">Comments : <?= separator_only($v['comment']) ?></p>
                        <p class="mb-1 text-black">Save & Share : <?= separator_only($v['share_save']) ?></p>
                        <p class="mb-1 text-black fw-600">Views : <?= separator_only($v['views']) ?></p>
                        <p class="mb-1 text-black fw-600">CPM : <?= separator_only($v['cpm']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <?php
                        $stats = isset($card_stats[$v['id']]) ? $card_stats[$v['id']] : [];
                        $a = isset($stats['total_pengajuan']) ? intval($stats['total_pengajuan']) : 0;
                        $b = isset($stats['posted_count'])    ? intval($stats['posted_count'])    : 0;
                        $c = isset($stats['reject_count'])    ? intval($stats['reject_count'])    : 0;
                        ?>
                        <p class="mb-1 text-black">Total Pengajuan Post : <?= separator_only($a) ?></p>
                        <p class="mb-1 text-black">Posted : <?= separator_only($b) ?></p>
                        <p class="mb-1 text-black">Rejected : <?= separator_only($c) ?></p>
                        <p class="mb-1 text-black">Summary : <?= separator_only($b) ?>/<?= separator_only($a - $c) ?></p>
                    </div>

                </div>
            </div>
        </div>
    </div>


<?php $k += 1;
} ?>

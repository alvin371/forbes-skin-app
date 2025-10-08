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
            </div>
            <div class="col-lg-4 text-lg-end text-start">
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
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
                        $id = $v['id'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'");
                        $a = $dat[0]['count'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'
                        AND status_endorse = 'Posted Content'
                        ");
                        $b = $dat[0]['count'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'
                        AND status_endorse = 'Reject'
                        ");
                        $c = $dat[0]['count'];

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
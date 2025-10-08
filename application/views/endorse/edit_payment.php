<div class="form-message"></div>
<form action="<?= base_url() ?>payment/store" method="POST" id="form-modal" enctype="multipart/form-data">
    <input type="hidden" name="dt[id]" value="<?= $campaign['id'] ?>">
    <input type="hidden" name="dt[id_campaign]" value="<?= $campaign['id_campaign'] ?>">
    <input type="hidden" name="id_campaign" value="<?= $campaign['id'] ?>">
    <input type="hidden" name="status_payment_existing" value="<?= $campaign['status_payment'] ?>">
    <input type="hidden" name="dt[brand]" value="<?= $campaign['brand'] ?>">
    <style>
        .select2-container--open .select2-dropdown {
            z-index: 10000;
        }
    </style>
    <div class="row">
        <!-- Kolom Kiri -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="">Nama Creator</label>
                <input type="text" class="form-control" name="dt[nama_creator]" value="<?= $campaign['nama_creator'] ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="">PIC</label>
                <input type="text" class="form-control" name="dt[pic]" value="<?= $campaign['pic'] ?>" readonly>
            </div>
            <!-- <div class="mb-3">
                <label for="">Kampanye</label>
                <textarea class="form-control campaign" name="dt[title]" rows="2" readonly><?= $campaign['title'] ?></textarea>
            </div> -->
            <div class="mb-3">
                <label for="">Link MOU</label>
                <?php if ($campaign['link_mou']) { ?>
                    <a href="<?= $campaign['link_mou'] ?>" target="_blank"><i>Buka Link</i></a>
                <?php } ?>
            </div>
            <div class="mb-3">
                <label for="">Total Cost</label>
                <input type="text" class="form-control" value="<?= 'Rp ' . number_format($campaign['total_cost'], 0, ',', '.') ?>" readonly>
                <input type="hidden" name="dt[total_cost]" value="<?= $campaign['total_cost'] ?>">
            </div>
            <div class="mb-3">
                <label for="">Nominal Pengajuan</label>
                <input type="text" class="form-control" name="dt[nominal_pengajuan]" value="<?= 'Rp ' . number_format($campaign['nominal_pengajuan'], 0, ',', '.') ?> " readonly>
                <input type="hidden" class="form-control" name="dt[nominal_pengajuan]" value="<?= $campaign['nominal_pengajuan'] ?> ">
                <input type="hidden" class="form-control" name="dt[nominal_dibayarkan]" value="<?= $campaign['nominal_dibayarkan'] ?> ">
            </div>
            <!-- <div class="mb-3">
                <label for="">No Rekening</label>
                <input type="text" class="form-control" value="<?= $influencer['bank'] ?> - <?= $influencer['no_rekening']  ?>" readonly>
            </div> -->
            <!-- <div class="mb-3">
                <label for="">Keterangan</label>
                <textarea class="form-control" name="dt[desc]" rows="4" style="width: 100%; resize: vertical; min-height: 100px;" readonly><?= $campaign['desc'] ?></textarea>
            </div> -->
        </div>

        <!-- Kolom Kanan -->
        <div class="col-md-6">
            <!-- <div class="mb-3">
                <label for="">Status Pengajuan</label>
                <input type="text" class="form-control" name="dt[pengajuan_status_payment]" value="<?= $campaign['pengajuan_status_payment'] ?> " readonly>
            </div> -->
            <input type="hidden" class="form-control" name="dt[pengajuan_status_payment]" value="<?= $campaign['pengajuan_status_payment'] ?> ">
            <div class="mb-3">
                <label for="">Tanggal Pembayaran</label>
                <input type="date" class="form-control" name="dt[tgl_tf]" value="<?= !empty($campaign['tgl_tf']) ? $campaign['tgl_tf'] : date('Y-m-d') ?>">
            </div>
            <div class="mb-3">
                <label for="">Status Payment</label>
                <?php 
                $status_payment = $campaign['status_payment'];

                if (empty($status_payment) && !empty($campaign['pengajuan_status_payment'])) {
                    if (stripos($campaign['pengajuan_status_payment'], 'DP') !== false) {
                        $status_payment = 'DP';
                    } elseif (stripos($campaign['pengajuan_status_payment'], 'FP') !== false) {
                        $status_payment = 'FP';
                    } else {
                        $status_payment = 'Pengajuan Payment';
                    }
                }

                ?>
                <select type="text" class="form-control" name="dt[status_payment]" id="status_payment">
                    <?php
                    $options = ["Pengajuan Payment", "DP", "FP"];
                    foreach ($options as $option) {
                        $selected = ($status_payment == $option) ? 'selected' : '';
                        echo "<option value=\"$option\" $selected>$option</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="">Link Telegram</label>
                <?php if ($campaign['link_telegram']) { ?>
                    <a href="<?= $campaign['link_telegram'] ?>" target="_blank"><i>Buka Link</i></a>
                <?php } ?>
                <input type="url" class="form-control" name="dt[link_telegram]" value="<?= $campaign['link_telegram'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="">Bukti Transfer</label>
                <?php if ($campaign['bukti_tf']) { ?>
                    <a href="<?= base_url() ?>assets/img/transfer/<?= $campaign['bukti_tf'] . '?token=' . DATE("Ymdhis", strtotime($campaign['updated_at'])) ?>" target="_blank"><i>Buka Gambar</i></a>
                <?php } ?>
                <input type="file" class="form-control" name="file" accept="image/png, image/jpeg, image/jpg">
            </div>
            <div class="mb-3">
                <label for="">Keterangan Payment</label>
                <textarea class="form-control" name="dt[keterangan_payment]" rows="4" style="width: 100%; resize: vertical; min-height: 100px;"><?= $campaign['keterangan_payment'] ?></textarea>
            </div>
        </div>
    </div>

    <!-- Tombol Submit -->
    <div class="row">
        <div class="col-md-12 mt-3 text-end">
            <button type="submit" class="btn btn-primary btn-send">Simpan Data</button>
        </div>
    </div>

    <!-- Payment Logs -->
    <div class="row">
        <div class="col-md-12 mb-3 mt-3">
            <p class="fw-600 mb-1">PAYMENT LOGS</p>
            <hr>
            <?php if (!empty($all_logs)) { ?>
                <?php foreach ($all_logs as $index => $log) { ?>
                    <p class="mb-0">
                        #<?= $index + 1 ?> <br>
                        <b><?= $log['code'] ?? $log['created_by'] ?></b> 
                        <?php if ($log['log_type'] == 'pengajuan') { ?>
                            mengajukan <?= $log['status_payment'] ?> <br>
                            Nominal Pengajuan: <b>Rp <?= number_format($log['nominal_dibayarkan'], 0, ',', '.') ?></b> <br>
                            <?php if (!empty($log['note'])) { ?>
                                <i class="text-danger">Rollback</i><br>
                            <?php } ?>
                        <?php } else { ?>
                            mengubah status payment menjadi <?= $log['status_payment'] ?> <br>
                            Nominal Dibayarkan: <b>Rp <?= number_format($log['nominal_dibayarkan'], 0, ',', '.') ?></b> <br>
                            <?php if (!empty($log['link_tele'])) { ?>
                                <a href="<?= $log['link_tele'] ?>" target="_blank">Bukti Transfer</a><br>
                            <?php } ?>
                        <?php } ?>
                        Tanggal: <?= date('d M Y H:i', strtotime($log['created_at'])) ?><br>
                        <hr>
                    </p>
                <?php } ?>
            <?php } else { ?>
                <i>Belum ada logs.</i>
            <?php } ?>
        </div>
    </div>
</form>
<script type="text/javascript">
    $("#form-modal").submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr("action"),
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").prop("disabled", true).text("Processing...");
            },
            success: function(response) {
                if (response.includes("success")) {
                    Swal.fire({
                        title: "Success!",
                        text: "Data updated successfully!",
                        icon: "success",
                        confirmButtonText: "OK",
                        customClass: {
                            confirmButton: 'swal-blue-button'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: "Error!",
                        text: response,
                        icon: "error",
                        confirmButtonText: "OK",
                        customClass: {
                            confirmButton: 'swal-blue-button'
                        }
                    });
                }
                $(".btn-send").prop("disabled", false).text("Simpan Data");
            },
            error: function(xhr) {
                Swal.fire({
                    title: "Request Failed!",
                    text: "Error: " + xhr.statusText,
                    icon: "error",
                    confirmButtonText: "OK"
                });
                $(".btn-send").prop("disabled", false).text("Simpan Data");
            },
        });
    });
</script>
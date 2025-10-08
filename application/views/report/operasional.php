<style>
    .custom-card {
        height: 100%;
    }

    tbody:before {
        line-height: 0em;
        content: ".";
        color: white;
        display: block;
    }
</style>
<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];

if ($_GET['start_date'] == "") {
    $start_date = DATE("Y-m-01");
    
} else {
    $start_date = $_GET['start_date'];
}
if ($_GET['until_date'] == "") {
    $until_date = DATE("Y-m-d");
} else {
    $until_date = $_GET['until_date'];
}

if ($_GET['start_year'] == "") {
    $start_year = DATE("Y");
} else {
    $start_year = $_GET['start_year'];
}

if ($_GET['until_year'] == "") {
    $until_year = DATE("Y");
} else {
    $until_year = $_GET['until_year'];
}

if ($_GET['start_month'] == "") {
    $start_month = "1";
} else {
    $start_month = $_GET['start_month'];
}

if ($_GET['until_month'] == "") {
    $until_month = DATE("m");
} else {
    $until_month = $_GET['until_month'];
}

if ($_GET['start_week'] == "") {
    $start_week = "1";
} else {
    $start_week = $_GET['start_week'];
}

if ($_GET['until_week'] == "") {
    $until_week = DATE("W", strtotime(DATE('Y-m-d')));
} else {
    $until_week = $_GET['until_week'];
}



$type = $_GET['type'];

if ($_GET['type'] == "Yearly") {
    $chart_title = $start_year . ' - ' . $until_year;
} else if ($_GET['type'] == "Monthly") {
    $chart_title = 'Month ' . $start_month . ' - ' . $until_month . ' ' . $start_year;
} else if ($_GET['type'] == "Weekly") {
    $chart_title = 'Week ' . $start_week . ' - ' . $until_week . ' ' . $start_year;
} else {
    $type = "Daily";
    $chart_title = DATE('d M Y', strtotime($start_date)) . ' - ' . DATE('d M Y', strtotime($until_date));
}
?>
<div class="form-message"></div>
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">REPORT</h3>
        </div>
        <?php $this->load->view('report/menu') ?>
        <div class="col-lg-12 mb-3">

            <form action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control select2" name="brand" id="brand">
                                    <option value="">Brand</option>
                                    <?php foreach ($brands as $val) :
                                        $text = "";
                                        if ($_GET["brand"] == $val["code"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["code"] ?>"><?= $val["code"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control select2" name="channel">
                                    <option value="">Channel</option>
                                    <?php foreach ($channel_2 as $val) :
                                        $text = "";
                                        if ($_GET["channel"] == $val["name"]) {
                                            $text = "selected";
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val["name"] ?>"><?= $val["name"] ?></option>
                                    <?php
                                    endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="tanggal" placeholder="Pilih rentang tanggal...">
                        <input type="hidden" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? $start_date ?>">
                        <input type="hidden" name="until_date" id="end_date" value="<?= $_GET['until_date'] ?? $until_date ?>">
                        <input type="hidden" name="p" value="<?= $_GET['p'] ?? 'operasional' ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                    </div>
                    <script>
                        get_filter();
                        function get_filter() {
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>/ajax/get-filter',
                                data: {
                                    start_date: "<?= $_GET['start_date'] ?? $start_date ?>",
                                    until_date: "<?= $_GET['until_date'] ?? $until_date ?>",
                                },
                                success: function(response) {
                                    $("#tanggal").after(response.html); 
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error loading filter:", error);
                                }
                            });
                        }
                    </script>
                </div>
            </form>
        </div>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <h5 class="mb-0">Marketplace Order</h5>
                        <hr class="mb-2 mt-2">
                        <div id="report-1"><?php $this->load->view('loading_v2', true) ?> </div>
                        <script>
                            $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=1&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&start_year=<?= $start_year ?>&until_year=<?= $until_year ?>&start_month=<?= $start_month ?>&until_month=<?= $until_month ?>&start_week=<?= $start_week ?>&until_week=<?= $until_week ?>',
                                success: function(html) {
                                    $("#report-1").html(html.html);
                                }
                            });
                        </script>
                    </div>
                </div>
                <div class="col-lg-12 mb-3">
                    <div class="card custom-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0">Stok Produk</h5>

                            <div class="d-flex align-items-center gap-3">
                                <select class="form-select w-auto" name="jenis" id="jenis">
                                    <?php
                                    $currentJenis = $_GET['jenis'] ?? 'produk_jual'; 
                                    $jenisOptions = [
                                        'produk_jual' => 'Produk Jual',
                                        'produk_operasional' => 'Produk Operasional',
                                        '' => 'Semua Produk',
                                    ];

                                    foreach ($jenisOptions as $value => $label) {
                                        $selected = ($currentJenis === $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>

                                <select class="form-select w-auto" name="status" id="status">
                                    <?php
                                    $currentStatus = $_GET['status'] ?? 'Aktif'; 
                                    $statusOptions = [
                                        'Aktif' => 'Aktif',
                                        'Tidak Aktif' => 'Tidak Aktif',
                                        '' => 'Semua',
                                    ];

                                    foreach ($statusOptions as $value => $label) {
                                        $selected = ($currentStatus === $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <hr class="mb-2 mt-2">
                        <div id="report-11"><?php $this->load->view('loading_v2', true) ?> </div>
                        
                        <script>
                            // --- helper: hancurkan tippy lama di container agar tidak dobel ---
                            function destroyTippyIn(container) {
                                container.querySelectorAll('.tippy-return').forEach(el => {
                                if (el._tippy) el._tippy.destroy();
                                });
                            }

                            // --- helper: init tippy untuk elemen yang baru saja dirender ---
                            function initReturnTippy(container) {
                                const els = container.querySelectorAll('.tippy-return');
                                if (!els.length) return;

                                tippy(els, {
                                    content: 'Loading…',
                                    allowHTML: true,
                                    placement: 'top',
                                    theme: 'light-border',
                                    interactive: false,
                                    onShow(instance) {
                                    const el = instance.reference;

                                    // Kunci cache berdasar filter supaya tooltip update saat tanggal berubah
                                    const pid = el.getAttribute('data-product-id');
                                    const sd  = el.getAttribute('data-start-date') || '';
                                    const ud  = el.getAttribute('data-until-date') || '';
                                    const cacheKey = `${pid}|${sd}|${ud}`;

                                    if (el.dataset.loaded === '1' && el.dataset.cacheKey === cacheKey) return;

                                    const qs  = new URLSearchParams({ product_id: pid, start_date: sd, until_date: ud });
                                    const url = '<?= base_url("ajax/return_conditions") ?>' + '?' + qs.toString();

                                    instance.setContent('Loading…');

                                    fetch(url, { credentials: 'same-origin' })
                                        .then(r => r.json())
                                        .then(resp => {
                                        if (!resp || resp.ok !== true || !resp.data) {
                                            throw new Error(resp && resp.error ? resp.error : 'Gagal mengambil data');
                                        }
                                        const d  = resp.data;
                                        const nf = new Intl.NumberFormat('id-ID');

                                        // d.good_conditions: jumlah retur GOOD (ada IN & OUT)
                                        // d.bad_conditions : jumlah retur BAD  (ada OUT, tidak ada IN)
                                        // d.total_in_qty_return / d.total_out_qty_return opsional untuk insight
                                        const good = d.good_conditions?.total_qty ?? 0;
                                        const bad  = d.bad_conditions?.total_qty ?? 0;

                                        const html = `
                                            <div style="min-width:240px">
                                            <div><strong>Return Conditions</strong></div>
                                            <div class="mt-1" style="margin-top:6px">
                                                <div>Good   : <strong>${nf.format(good)}</strong></div>
                                                <div>Bad    : <strong>${nf.format(bad)}</strong></div>
                                                <hr style="margin:6px 0">
                                            </div>
                                            </div>
                                        `;
                                        instance.setContent(html);
                                        el.dataset.loaded   = '1';
                                        el.dataset.cacheKey = cacheKey;
                                        })
                                        .catch(err => {
                                        console.error(err);
                                        instance.setContent('<em>Gagal memuat data RETURN</em>');
                                        // jangan set loaded biar bisa retry di hover berikutnya
                                        });
                                    }
                                });
                            }


                            function loadReportData() {
                                const jenis  = $('#jenis').val();
                                const status = $('#status').val();

                                $("#report-11").html(<?= json_encode($this->load->view("loading_v2", true, true)) ?>);

                                $.ajax({
                                dataType: "json",
                                url: '<?= base_url() ?>ajax/get-report?code=11&brand=<?= $_GET['brand'] ?>&channel=<?= $_GET['channel'] ?>&type=<?= $type ?>&start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&jenis=' + jenis + '&status=' + status,
                                success: function(html) {
                                    // bersihkan tippy lama di container ini
                                    destroyTippyIn(document.getElementById('report-11'));

                                    // render HTML baru
                                    $("#report-11").html(html.html);

                                    // init tippy SETELAH HTML masuk
                                    initReturnTippy(document.getElementById('report-11'));
                                },
                                error: function() {
                                    $("#report-11").html('<div class="alert alert-danger">Error loading data</div>');
                                }
                                });
                            }

                            $(document).ready(function () {
                                loadReportData();
                                $('#jenis, #status').change(loadReportData);

                                // Tidak perlu init tippy di DOMContentLoaded lagi,
                                // karena elemen dibuat via AJAX. Inisialisasi dilakukan di success AJAX.
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
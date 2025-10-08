<?php
$start_date = $_GET['start_date'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$until_date = $_GET['until_date'] ?? '';
$until_time = $_GET['until_time'] ?? '';

if (empty($start_date)) {
    $start_date = date("d/m/Y", strtotime("-31 days"));
    $start_time = '00:00';
} else {
    $start_date = date("d/m/Y", strtotime(str_replace('/', '-', $start_date)));
}

if (empty($until_date)) {
    $until_date = date("d/m/Y");
    $until_time = date("H:i");
} else {
    $until_date = date("d/m/Y", strtotime(str_replace('/', '-', $until_date)));
}
?>

<style>
    .sortable {
        cursor: pointer;
        position: relative;
        user-select: none;
    }

    .sortable:hover {
        background-color: #f8f9fa;
    }

    .sortable i {
        font-size: 0.8em;
        margin-left: 5px;
        opacity: 0.5;
    }

    .sortable.asc i, .sortable.desc i {
        opacity: 1;
    }

    .sortable.asc i {
        transform: rotate(180deg);
    }
</style>

<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600 mb-3">STOK PRODUK</h3>
            <div class="card">
                <p class="mb-1">Name : <?= $data['name'] ?></p>
                <p class="mb-1">SKU : <?= $data['sku'] ?></p>
                <p class="mb-1">Brand : <?= $data['brand'] ?></p>
                <!--<table class="table table-bordered">-->
                <!--    <tr>-->
                <!--        <th colspan="2">IN</th>-->
                <!--        <th colspan="2">OUT</th>-->
                <!--    </tr>-->
                <!--    <tr>-->
                <!--        <th>-->
                <!--            Stok-->
                <!--        </th>-->
                <!--        <th>-->
                <!--            Order-->
                <!--        </th>-->
                <!--        <th>-->
                <!--            Stok-->
                <!--        </th>-->
                <!--        <th>-->
                <!--            Order-->
                <!--        </th>-->
                <!--    </tr>-->
                <!--    <tr>-->
                <!--        <td><?= $template->separator_only($data['stock_in']) ?></td>-->
                <!--        <td><?= $template->separator_only($data['stock_in_pos']) ?></td>-->
                <!--        <td><?= $template->separator_only($data['stock_out']) ?></td>-->
                <!--        <td><?= $template->separator_only($data['stock_out_pos']) ?></td>-->
                <!--    </tr>-->
                <!--    <tr>-->
                <!--        <td colspan="2"><?= $template->separator_only($data['stock_in'] + $data['stock_in_pos']) ?></td>-->
                <!--        <td colspan="2"><?= $template->separator_only($data['stock_out'] + $data['stock_out_pos']) ?></td>-->
                <!--    </tr>-->
                <!--    <tr>-->
                <!--        <td colspan="4"><?= $template->separator_only($data['stock']) ?></td>-->
                <!--    </tr>-->
                <!--</table>-->
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <form action="">
            <input type="hidden" name="id" value="<?= $_GET['id'] ?>">
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="card">
                        <h3 class="mb-0 text-notif">Filter Data</h3>
                        <hr>
                        <div class="row">
                            <!-- <div class="col-md-1">
                                    <label for="">Brand</label>
                                    <select class="form-control select2" name="brand" id="brand">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($brands as $val) :
                                            $text = '';
                                            if ($_GET['brand'] == $val['code']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['code'] ?>"><?= $val['code'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div> -->
                            <div class="col-md-2">
                                <label for="">Toko</label>
                                <select class="form-control select2" name="shop_id">
                                    <option value="">-</option>
                                    <?php
                                    foreach ($store as $val) :
                                        $text = '';
                                        if ($_GET['shop_id'] == $val['id']) {
                                            $text = 'selected';
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val['id'] ?>"><?= $val['opt'] ?> <?= ucwords(strtolower($val['marketplace'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="">Channel</label>
                                <select class="form-control select2" name="marketplace">
                                    <option value="">-</option>
                                    <?php
                                    foreach ($marketplace as $val) :
                                        $text = '';
                                        if ($_GET['marketplace'] == $val['name']) {
                                            $text = 'selected';
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val['name'] ?>"><?= $val['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="">Brand</label>
                                <select class="form-control select2" name="brand">
                                    <option value="">-</option>
                                    <?php
                                    foreach ($brands as $val) :
                                        $text = '';
                                        if ($_GET['brand'] == $val['opt']) {
                                            $text = 'selected';
                                        }
                                    ?>
                                        <option <?= $text ?> value="<?= $val['opt'] ?>"><?= $val['opt'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="">Tanggal Mulai</label>
                                <div class="d-flex">
                                    <!-- Input Tanggal dengan Flatpickr -->
                                    <input type="text" id="start_date" name="start_date" class="form-control" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($start_date)); ?>" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
                                    <!-- Input Waktu (H:i) -->
                                    <input type="time" id="start_time" name="start_time" class="form-control" value="<?= date('H:i', strtotime($start_time)); ?>" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label for="">Tanggal Selesai</label>
                                <div class="d-flex">
                                    <!-- Input Tanggal dengan Flatpickr -->
                                    <input type="text" id="until_date" name="until_date" class="form-control" placeholder="dd/mm/yyyy" value="<?= date('d/m/Y', strtotime($until_date)); ?>" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
                                    <!-- Input Waktu (H:i) -->
                                    <input type="time" id="until_time" name="until_time" class="form-control" value="<?= date('H:i', strtotime($until_time)); ?>" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="">Status Order</label>
                                    <select class="form-control select2" name="order_status">
                                        <option value="">-</option>
                                        <?php
                                        foreach ($order_status as $val) :
                                            $text = '';
                                            if ($_GET['order_status'] == $val['opt']) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val['opt'] ?>"><?= $val['opt'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="">TF/COD</label>
                                    <select class="form-control select2" name="payment_type">
                                        <option value="">-</option>
                                        <?php
                                        $arr = array();
                                        $arr[] = "TF";
                                        $arr[] = "COD";
                                        foreach ($arr as $val) :
                                            $text = '';
                                            if ($_GET['payment_type'] == $val) {
                                                $text = 'selected';
                                            }
                                        ?>
                                            <option <?= $text ?> value="<?= $val ?>"><?= $val ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 text-lg-end text-start">
                                    <!-- <label for="">&nbsp;</label> -->
                                    <button style="margin-top:25px" class="btn btn-edit-active w-100" type="submit">Cari Data</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-12 d-none">
                    <div class="row">
                        <div class="col-md-2">
                            <!-- <label for="">&nbsp;</label> -->
                            <button class="btn mb-2 btn-primary w-100 form-control" type="submit">FILTER DATA</button>
                        </div>
                        <div class="col-md-2">
                            <!-- <label for="">&nbsp;</label> -->
                            <a href="<?= base_url() ?>stock/create?start_date=<?= $start_date ?>&until_date=<?= $until_date ?>&brand=<?= $brand ?>&marketplace=<?= $_GET['marketplace'] ?>&cs=<?= $_GET['cs'] ?>&keyword=<?= $_GET['keyword'] ?>" class="btn mb-2 btn-primary w-100 form-control">TAMBAH DATA</a>
                        </div>
                        <div class="col-md-2">
                            <!-- <label for="">&nbsp;</label> -->
                            <a href="#!" onclick="import_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">IMPORT DATA</a>
                        </div>
                        <div class="col-md-2">
                            <!-- <label for="">&nbsp;</label> -->
                            <a href="#!" onclick="sync_data('<?= $start_date ?>','<?= $until_date ?>')" class="btn mb-2 btn-primary w-100 form-control">SYNC DATA</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?= $notif ?>
        <div class="form-message"></div>
        <div class="table-responsive" id="table-item">
            <table class="table table-bordered" id="tbody">
                <thead>
                    <tr class="bg-primary text-white">
                        <th class="text-start" style="max-width:0px!important">#</th>
                        <th class="text-start sortable">TGL</th>
                        <th class="text-start">TIPE</th>
                        <th class="text-start">ID TRX</th>
                        <th class="text-start">NO RESI</th>
                        <th class="text-end sortable">QTY</th>
                        <th class="text-end sortable">BALANCE</th>
                        <th class="text-start">KET</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <div>
            <?= $pagination ?>
        </div>
        <div>
            <?php
            $per_page_options = [10, 20, 50, 100, 500];
            $limit = $_GET['limit'] ?? 10;
            if (!in_array($limit, $per_page_options)) {
                $limit = 10;
            }

            $query_params = $_GET;
            unset($query_params['limit']);
            ?>

            <form method="GET" action="">
                <?php foreach ($query_params as $key => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endforeach; ?>

                <select class="form-control select2" name="limit" id="limit"
                    onchange="this.form.submit()">
                    <?php foreach ($per_page_options as $option): ?>
                        <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                            <?= $option ?> / Halaman
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

        </div>
    </div>




</div>

<div class="modal fade bd-example-modal-sm" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>
<script>
    flatpickr("#start_date", {
        dateFormat: "d/m/Y",
        defaultDate: "<?= $start_date; ?>",
        allowInput: true,
    });

    flatpickr("#until_date", {
        dateFormat: "d/m/Y",
        defaultDate: "<?= $until_date; ?>",
        allowInput: true,
    });
</script>
<script>
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>/product/create");
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/product/edit?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Delete Data');
        $("#load-form").load("<?= base_url() ?>/product/remove?id=" + id);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('table-item');
        const headers = table.querySelectorAll('th.sortable'); // Hanya ambil header yang sortable
        const grandTotalRow = table.querySelector('tr.fw-bold');

        updateRowNumbers();

        headers.forEach(header => {
            header.innerHTML += ' <i class="bi bi-arrow-down-up"></i>';
            header.addEventListener('click', () => {
                sortTable(header);
            });
        });

        function sortTable(header) {
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const rows = Array.from(table.querySelectorAll('tbody tr:not(.fw-bold)'));
            const isAscending = !header.classList.contains('asc');

            headers.forEach(h => {
                h.classList.remove('asc', 'desc');
                h.querySelector('i').className = 'bi bi-arrow-down-up';
            });

            rows.sort((a, b) => {
                let aValue, bValue;

                if (a.children[columnIndex].hasAttribute('data-sort')) {
                    aValue = a.children[columnIndex].getAttribute('data-sort');
                    bValue = b.children[columnIndex].getAttribute('data-sort');
                } else {
                    aValue = a.children[columnIndex].textContent.trim();
                    bValue = b.children[columnIndex].textContent.trim();
                }

                if (columnIndex === 1) {
                    return isAscending 
                        ? aValue.localeCompare(bValue, 'id', { sensitivity: 'base' })
                        : bValue.localeCompare(aValue, 'id', { sensitivity: 'base' });
                }

                const aNum = parseFloat(aValue.toString().replace(/[^\d.-]/g, ''));
                const bNum = parseFloat(bValue.toString().replace(/[^\d.-]/g, ''));

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAscending ? aNum - bNum : bNum - aNum;
                }

                return isAscending 
                    ? aValue.toString().localeCompare(bValue.toString())
                    : bValue.toString().localeCompare(aValue.toString());
            });

            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.insertBefore(row, grandTotalRow));

            updateRowNumbers();

            header.classList.add(isAscending ? 'asc' : 'desc');
            header.querySelector('i').className = isAscending 
                ? 'bi bi-arrow-up' 
                : 'bi bi-arrow-down';
        }

        function updateRowNumbers() {
            const rows = table.querySelectorAll('tbody tr:not(.fw-bold)');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }
    });
</script>

<script>
    var complete = false;
    var offset = 0;
    var loading = false;

    function loadMoreData() {
        if (!complete && !loading) {
            loading = true;

            var limit = $('select[name="limit"]').val();
            var order_status = $('select[name="order_status"]').val();
            var payment_type = $('select[name="payment_type"]').val();

            const startDate = $('#start_date').val();
            const startTime = $('#start_time').val();
            const untilDate = $('#until_date').val();
            const untilTime = $('#until_time').val();

            console.log(startDate, startTime, untilDate, untilTime);

            const startDateTime = combineDateTime(startDate, startTime);
            const untilDateTime = combineDateTime(untilDate, untilTime);

            console.log(startDateTime, untilDateTime);

            $.ajax({
                type: 'GET',
                url: "<?= base_url() ?>/product/item-stock",
                data: {
                    start_date: startDateTime,
                    until_date: untilDateTime,
                    brand: "<?= $brand ?>",
                    product: "<?= $_GET['product'] ?>",
                    type: "<?= $_GET['type'] ?>",
                    type_sub: "<?= $_GET['type_sub'] ?>",
                    keyword: "<?= $_GET['keyword'] ?>",
                    page: "<?= $_GET['page'] ?>",
                    id: "<?= $_GET['id'] ?>",
                    order_status: order_status,
                    payment_type: payment_type,
                    limit: limit
                },
                success: function(data) {
                    $('#tbody').append(data);
                    offset += parseInt(limit);
                    loading = false;

                    if (!data) {
                        complete = true;
                    }
                    select3();
                    console.log($('input[name="jam_order"]').val());
                },
                error: function(xhr, status, error) {
                    loading = false;
                }
            });
        } else {
            $("#msg").html("<i class='fa fa-check'></i> Proses memuat data selesai!");
        }
    }

    loadMoreData();

    function combineDateTime(date, time) {
        if (!date || !time) return '';

        const [day, month, year] = date.split('/');
        const formattedDate = `${year}-${month}-${day}`;

        return `${formattedDate} ${time}`;
    }
</script>
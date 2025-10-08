<?php
$chart_title = "";
$site = $_GET['site'];
$customer = $_GET['customer'];

// if ($_GET['start_date'] == "") {
//     $start_date = DATE("Y-m-01");
// } else {
//     $start_date = $_GET['start_date'];
// }
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
<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">KONTEN INFLUENCER</h3>
            <h3 class="text-primary fw-600"><?= $detail['title'] ?></h3>
            <p class="mb-0"><?= $detail['desc'] ?></p>
        </div>
    </div>
</div>

<a href="<?= base_url() ?>endorse?id_campaign=<?= $detail['id'] ?>" class="btn btn-primary mt-0 mb-2"><i class="bi bi-chevron-double-left fs-16"></i> Kembali</a>
<?= $notif ?>


<!-- <div class="col-lg-12 mb-3">
    <div class="checkbox-wrapper-13">
        <input id="c1-13" type="checkbox" value="1" class="checkAll">
        <label for="c1-13">Pilih Semua Data</label>
    </div>
</div> -->

<div class="col-lg-12">
    <div class="col-lg-12">
        <div id="tbody">
            <?php $this->load->view('loading', true) ?>
        </div>
    </div>

    <?= $pagination ?>
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

<input type="hidden" id="id_selected" name="id_selected" form="form-action">


<script>
    var list_id_v2 = '';

    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1); 
        }
        $('#id_selected').val(selectedValues.join(','));
    }

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/endorse?id_campaign=<?= $detail['id'] ?>&ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/endorse/action?code=hapus_data&id=" + id);
    }

    function ubah_status(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Ubah Status Konten');
        $("#load-form").load("<?= base_url() ?>/endorse/action?code=ubah_status&id=" + id);
    }

    function ubah_status_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Ubah Status Data');
        $("#load-form").load("<?= base_url() ?>/endorse/action?code=ubah_status_data&id=" + id);
    }

    function refresh_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/endorse/action?code=refresh_data&id_campaign=<?= $detail['id'] ?>");
    }

    function create(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Tambah Konten');
        $("#load-form").load("<?= base_url() ?>/endorse/create?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/endorse/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Konten');
        $("#load-form").load("<?= base_url() ?>/endorse/edit?id=" + id);
    }

    function sync_all(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/endorse/sync_all?id=" + id);
    }

    function sync(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/endorse/sync?id=" + id);
    }

    function clone(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Kloning Data');
        $("#load-form").load("<?= base_url() ?>/endorse/clone?id=" + id);
    }

    function show_chart(id) {
        $('#chart-' + id).html('<i class="fa fa-circle-o-notch fa-spin"></i> Memuat data ...')
        $.ajax({
            dataType: "json",
            url: '<?= base_url() ?>/ajax/get-chart-endorse<?= $param ?>&id=' + id,
            success: function(html) {
                $("#chart-'" + id).html(html.html);
                $("#table-").html(html.table);
            }
        });
    }
</script>
<script>
    function loadMoreData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/endorse/item-endorse<?= $url_item ?>",
            success: function(data) {
                $('#tbody').html('');
                $('#tbody').append(data);
                select3();
            },
            error: function(xhr, status, error) {}
        });
    }
    loadMoreData();
</script>
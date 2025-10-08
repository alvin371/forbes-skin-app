<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">PRODUCT STOCK</h3>
            <p>Product Name : <?= $data['name'] ?></p>
            <p>Stock In : <?= $data['stock_in'] ?></p>
            <p>Stock Out : <?= $data['stock_out'] ?> (<?= $data['stock_out_pos'] ?> + <?= $data['stock_out'] - $data['stock_out_pos'] ?>)</p>
            <p>Stock Balance : <?= $data['stock'] ?></p>
            <p>SKU : <?= $data['sku'] ?></p>
            <p>Brand : <?= $data['brand'] ?></p>
        </div>
    </div>
    <div class="col-lg-12">
        <?=$notif?>
        <div class="form-message"></div>
            <div class="table-responsive table-fixed scroll-v-none" id="table-item">
                <table class="table table-hover table-striped table-bordered" >
                <thead>
                    <tr class="bg-primary text-white">
                        <th style="max-width:0px!important">#</th>
                        <th class="text-start">DATE</th>
                        <th class="text-start">TYPE</th>
                        <th class="text-start">SUB TYPE</th>
                        <th class="text-start">ID TRX</th>
                        <th class="text-end">QTY</th>
                        <th class="text-end">BALANCE</th>
                        <th class="text-start">DESC</th>
                        <th style="max-width:10px!important">CREATED AT</th>
                        <th style="max-width:10px!important">STATUS</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
    <?=$pagination?>
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
var complete = false;
var offset = 0;
var loading = false;
function loadMoreData() {
    if(!complete){
        if (!loading) {
            loading = true;
            $.ajax({
                type: 'GET',
                url: "<?= base_url() ?>/product/item-stock?start_date=<?=$start_date?>&until_date=<?=$until_date?>&brand=<?=$brand?>&product=<?=$_GET['product']?>&type=<?=$_GET['type']?>&type_sub=<?=$_GET['type_sub']?>&keyword=<?=$_GET['keyword']?>&page=<?=$_GET['page']?>&id=<?=$_GET['id']?>",
                success: function (data) {
                    $('#tbody').append(data);
                    offset += 30;
                    loading = false;
                    if (!data) {
                        complete = true;
                    }
                    select3();
                },
                error: function (xhr, status, error) {
                    loading = false;
                }
            });
        }
    }else{
        $("#msg").html("<i class='fa fa-check'></i> Proses memuat data selesai!");
    }
}
loadMoreData();
</script>

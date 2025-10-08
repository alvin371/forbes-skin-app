<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">Discount</h3>
        </div>
        <div class="col-lg-2 pt-0">
            <a href="#!" onclick="create()" class="btn btn-2 btn-primary mb-3">CREATE DATA</a>
        </div>

    </div>
    <div class="col-lg-12">
        <div class="form-message"></div>
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="datatable">
                <thead>
                    <tr class="bg-primary text-white">
                        <th style="max-width:0px!important">#</th>
                        <th class="text-start">TITLE</th>
                        <th class="text-start">CODE</th>
                        <th class="text-end">MIN NOMINAL</th>
                        <th class="text-start">MIN NOMINAL</th>
                        <th class="text-end">DISCOUNT NOMINAL</th>
                        <th style="max-width:10px!important">CREATED AT</th>
                        <th style="max-width:10px!important">STATUS</th>
                        <th style="max-width:10px!important">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $k => $v) { ?>
                        <tr>
                            <td class="req"><?= $k + 1 ?></td>
                            <td class="req text-start"><?= $v['title'] ?></td>
                            <td class="req text-start"><?= $v['code'] ?></td>
                            <td class="req text-end"><?= $v['min_nominal'] ?></td>
                            <td class="req text-start"><?= $v['type'] ?></td>
                            <td class="req text-end"><?= $v['nominal'] ?></td>
                            <td class="req"><?= $v['created_at'] ?></td>
                            <td class="req"><?= $v['status'] ?></td>
                            <td class="req pt-1">
                                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-act btn-edit">EDIT</a>
                                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-act btn-delete">DELETE</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
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
    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Create Data');
        $("#load-form").load("<?= base_url() ?>/discount/create");
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/discount/edit?id=" + id);
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Delete Data');
        $("#load-form").load("<?= base_url() ?>/discount/remove?id=" + id);
    }
</script>
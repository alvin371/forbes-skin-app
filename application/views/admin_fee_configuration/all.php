<div class="w-100">
    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">CRM</h3>
        </div>
        <div class="col-lg-2 pt-0">
        </div>

    </div>
    <div class="col-lg-12">
        <div class="form-message"></div>
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered" id="datatable">
                <thead>
                    <tr class="bg-primary text-white">
                        <th style="max-width:0px!important">#</th>
                        <th class="text-start">FULL NAME</th>
                        <th class="text-start">PHONE NUMBER</th>
                        <th class="text-start">BIRTH DATE</th>
                        <th class="text-start">SERVICE TYPE</th>
                        <th class="text-start">SERVICE START</th>
                        <th class="text-start">SERVICE EXPIRED</th>
                        <th class="text-end">COUNT ORDER</th>
                        <th class="text-start">FIRST ORDER</th>
                        <th class="text-start">LAST ODER</th>
                        <th class="text-start">ORDER STATUS</th>
                        <th style="max-width:10px!important">CREATED AT</th>
                        <th style="max-width:10px!important">STATUS</th>
                        <th style="max-width:10px!important">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $k => $v) { ?>
                        <tr>
                            <td class="req"><?= $k + 1 ?></td>
                            <td class="req text-start"><?= $v['full_name'] ?></td>
                            <td class="req text-start"><?= $v['phone'] ?></td>
                            <td class="req text-start"><?= $v['birth_date'] ?></td>
                            <td class="req text-start"><?= $v['service_type'] ?></td>
                            <td class="req text-start"><?= $v['service_str'] ?></td>
                            <td class="req text-start"><?= $v['service_exp'] ?></td>
                            <td class="req text-end"><?= $v['count_trx'] ?></td>
                            <td class="req text-start"><?= $v['first_order'] ?></td>
                            <td class="req text-start"><?= $v['last_order'] ?></td>
                            <td class="req text-start"><?= $v['order_status'] ?></td>
                            <td class="req"><?= $v['created_at'] ?></td>
                            <td class="req"><?= $v['status'] ?></td>
                            <td class="req pt-1">
                                <a target="_blank" href="https://api.whatsapp.com/send?phone=6285756350376&text=Hai%20Aang%20Muammar%20Zein.%20Kami%20memiliki%20penawaran%20menarik%20untuk%20anda!" class="btn btn-act btn-sync">CHAT WA</a>
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
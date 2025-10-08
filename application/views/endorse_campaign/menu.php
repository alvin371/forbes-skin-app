<?php
$btn_1 = "btn-edit";
$btn_2 = "btn-edit";
if ($_GET['p'] == "") {
    $btn_1 = "btn-primary";
} else {
    $btn_2 = "btn-primary";
} 
?>
<div class="col-md-12">
    <a href="<?= base_url() ?>endorse-campaign" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">EXTERNAL</a>
    <a href="<?= base_url() ?>endorse-campaign?p=internal" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">INTERNAL</a>
</div>
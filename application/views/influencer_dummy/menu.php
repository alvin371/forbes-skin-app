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
    <a href="<?= base_url() ?>influencer-dummy" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">TIKTOK</a>
    <a href="<?= base_url() ?>influencer-dummy?p=instagram" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">INSTAGRAM</a>
</div>
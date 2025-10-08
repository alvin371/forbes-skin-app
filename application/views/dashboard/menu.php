<?php
$btn_1 = "btn-edit";
$btn_2 = "btn-edit";
$btn_3 = "btn-edit";
if ($_GET['t'] == "kol") {
    $btn_2 = "btn-primary";
} else if ($_GET['t'] == "influencer") {
    $btn_3 = "btn-primary";
} else {
    $btn_1 = "btn-primary";
}
?>
<div class="col-md-12">
    <a href="<?= base_url() ?>dashboard" class="btn <?= $btn_1 ?> me-1 mb-3 dashboard-menu-btn" style="min-width:90px!important">ORDER</a>
    <a href="<?= base_url() ?>dashboard?t=kol" class="btn <?= $btn_2 ?> me-1 mb-3 dashboard-menu-btn" style="min-width:90px!important">KOL</a>
    <a href="<?= base_url() ?>dashboard?t=influencer" class="btn <?= $btn_3 ?> mb-3 dashboard-menu-btn" style="min-width:90px!important">INFLUENCER</a>
</div>
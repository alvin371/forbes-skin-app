<?php
$btn_1 = "btn-edit"; // ADS
$btn_2 = "btn-edit"; // KOL
$btn_3 = "btn-edit"; // INFLUENCER

$type = $_GET['t'] ?? 'ads'; 

if ($type == "ads") {
    $btn_1 = "btn-primary";
} else if ($type == "kol") {
    $btn_2 = "btn-primary";
} else if ($type == "influencer") {
    $btn_3 = "btn-primary";
}

?>
<div class="col-md-12">
    <a href="<?= base_url() ?>overview?t=ads" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">ADS</a>
    <a href="<?= base_url() ?>overview?t=kol" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">KOL</a>
    <a href="<?= base_url() ?>overview?t=influencer" class="btn <?= $btn_3 ?> mb-3" style="min-width:90px!important">INFLUENCER</a>
</div>
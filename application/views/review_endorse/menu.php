<?php
$btn_1 = "btn-edit"; 
$btn_2 = "btn-edit";

$url = current_url();
if ($url == base_url() . "payment") {
    $btn_1 = "btn-primary";
} else if ($url == base_url() . "review-endorse") {
    $btn_2 = "btn-primary";
} 

?>

<div class="col-md-12">
    <a href="<?= base_url() ?>payment" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">PAYMENT</a>
    <a href="<?= base_url() ?>review-endorse" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">REVIEW</a>
</div>
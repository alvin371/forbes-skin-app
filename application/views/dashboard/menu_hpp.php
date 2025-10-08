<?php
$btn_1 = "btn-edit"; 
$btn_2 = "btn-edit";

$query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

$url = current_url();
if ($url == base_url() . "dashboard/hpp") {
    $btn_1 = "btn-primary";
} else if ($url == base_url() . "dashboard/hpp-bundling") {
    $btn_2 = "btn-primary";
} else {
    $btn_3 = "btn-primary";
}
?>

<div class="col-md-12">
    <a href="<?= base_url() ?>dashboard/hpp<?= $query_string ?>" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">
        HPP PER PRODUK
    </a>
    <a href="<?= base_url() ?>dashboard/hpp-bundling<?= $query_string ?>" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">
        HPP BUNDLING
    </a>
</div>

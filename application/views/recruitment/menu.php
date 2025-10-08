<?php
$btn_1 = "btn-edit"; 
$btn_2 = "btn-edit";
$btn_3 = "btn-edit";

$url = current_url();
if ($url == base_url() . "recruitment") {
    $btn_1 = "btn-primary";
} else if ($url == base_url() . "interview") {
    $btn_2 = "btn-primary";
} else if (
    $url == base_url() . "interview/questions" ||
    $url == base_url() . "interview/add_question" ||
    preg_match("#^" . preg_quote(base_url() . "interview/edit_question/") . "\d+$#", $url)
) {
    $btn_3 = "btn-primary";
}

?>

<div class="col-md-12">
    <a href="<?= base_url() ?>recruitment" class="btn <?= $btn_1 ?> me-1 mb-3" style="min-width:90px!important">RECRUITMENT</a>
    <a href="<?= base_url() ?>interview" class="btn <?= $btn_2 ?> me-1 mb-3" style="min-width:90px!important">INTERVIEW</a>
    <a href="<?= base_url() ?>interview/questions" class="btn <?= $btn_3 ?> me-1 mb-3" style="min-width:90px!important">QUESTIONS</a>
</div>
<?php
$k = $start;
foreach ($data as $v) {
    if($v['img']){
        $v['img'] = base_url().'/assets/img/marketplace/'.$v['img'];
    }else{
        $v['img'] = base_url().'/assets/img/marketplace/default.png';
    }
?>
<tbody>
    <tr>
    <td class="text-start">
    <p class="mb-1 text-blue fw-700 fs-16">#<?=$k+1?></p>
    </td>
     <td class="req text-start">
     <div class="row">
        <div class="col-12" style="position:relative">
        <div class="row">
        <div class="firstDivImg">
            <a href="<?=$v['img']?>" target="_blank"><img class="divIcon" src="<?=$v['img']?>" alt=""></a>
        </div>
        <div class="secondDivImg">
        <p class="mb-1 fw-700 fs-16"><?=$v['name']?></p>
        </div>
        </div>
        </div>
    </div>          
    </td>
                            <td class="req text-start">
                                <?php
                                $conf = json_decode($v['configuration'], true);
                                foreach ($conf as $k2 => $v2) { ?>
                                    Date : <?= $template->date_format_indo($v2['date']) ?>
                                    <br>
                                    Type : <?= $v2['type'] ?>
                                    <br>
                                    Nominal : <?= $template->separator_only($v2['fee']) ?>
                                    <br>
                                    <?php if(($k2+1) < count($conf)){ ?>
                                    --------------------------
                                    <br>
                                    <?php } ?>
                                <?php } ?>
                                <?php if (empty($conf)) { ?>
                                    <i class="text-red">Konfigurasi belum tersedia!</i>
                                <?php } ?>
                            </td>
                            <td class="req text-start"><?= $v['desc'] ?></td>
        
    <td class="text-end">
        <a href="#!" onclick="edit('<?=$v['id']?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
        <a href="#!" onclick="remove('<?=$v['id']?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
    </td>
    </tr>
</tbody>
<?php $k += 1;} ?>
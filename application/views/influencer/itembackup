
<?php
$k = $start;
foreach ($data as $v) {  
    if($v['desc']==""){
        $v['desc'] = "-";
    }
    if($v['sync_at']){
        $v['sync_at'] = DATE('d/m/Y',strtotime($v['sync_at']));
    }else{
        $v['sync_at'] = '-';
    }
    if($v['type']=="Tiktok"){
        $v['img'] = base_url().'/assets/img/icon/icon-tiktok.png';
    }else if($v['type']=="Instagram"){
        $v['img'] = base_url().'/assets/img/icon/icon-ig.png';
    }else if($v['type']=="Youtube"){
        $v['img'] = base_url().'/assets/img/icon/icon-youtube.png';
    }else if($v['type']=="Facebook"){
        $v['img'] = base_url().'/assets/img/icon/icon-fb.png';
    }else if($v['type']=="Twitter"){
        $v['img'] = base_url().'/assets/img/icon/icon-twitter.png';
    }else{
        $v['img'] = base_url().'/assets/img/icon/icon-no.png';
    }

    if($v['tipe_kontak']=="WA"){
        $v['img_2'] = base_url().'/assets/img/icon/icon-wa.png';
    }else if($v['tipe_kontak']=="Email"){
        $v['img_2'] = base_url().'/assets/img/icon/icon-email.png';
    }else if($v['tipe_kontak']=="IG"){
        $v['img_2'] = base_url().'/assets/img/icon/icon-ig.png';
    }else if($v['tipe_kontak']=="HP"){
        $v['img_2'] = base_url().'/assets/img/icon/icon-phone.png';
    }else{
        $v['img_2'] = base_url().'/assets/img/icon/icon-no.png';
    }

    if($v['url']){
        $v['img'] = '<a href="'.$v['url'].'" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="'.$v['img'].'"></a>';
    }else{
        $v['img'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="'.$v['img'].'">';
    }

    if($v['contact']){
        $v['img_2'] = '<a href="'.$v['contact'].'" target="_blank"><img style="width:40px;border-radius:10px;" class="mt-0" src="'.$v['img_2'].'"></a>';
    }else{
        $v['img_2'] = '<img style="width:40px;border-radius:10px; filter: grayscale(100%)!important;" class="mt-0" src="'.$v['img_2'].'">';
    }

    ?>
     <tbody>
     <tr>
     <td class="text-start br-b-0">
    <p class="mb-1 text-blue fw-700 fs-16">#<?=$k+1?>


    <div class="checkbox-wrapper-13 d-inline">
                <input class="checkItem" style="top:10px" type="checkbox" value="<?=$v['id']?>" data-id="<?=$k?>" name="list_id" form="form-action">
                </div>
                <input type="hidden" value="<?=$v['is_manual']?>" name="is_manual[<?=$k-$start?>]" form="form-action">
                <input type="hidden" value="<?=$v['marketplace']?>" name="marketplace[<?=$k-$start?>]" form="form-action">
                <input type="hidden" value="<?=$v['brand']?>" name="brand[<?=$k-$start?>]" form="form-action">
                <input type="hidden" value="<?=$v['order_id']?>" name="order_id[<?=$k-$start?>]" form="form-action">

    </p>
    </td>
    <td class="text-start"><?= $v['status_reach'] ?>
    <div class="mt-2">
    <?=$v['img']?>
    <?=$v['img_2']?>
    </div>
</td>
                            <!-- <td class="text-start">
                                
                            <?= $v['full_name'] ?>
                        
                        </td> -->
                            <td class="text-start"><?= $v['username'] ?></td>
                            <td class="text-start"><?= $v['brand'] ?></td>
                            <td class="text-start"><?= $v['pic'] ?></td>
                            <td class="text-start"><?= $v['type'] ?></td>
                            <!-- <td class="text-start"><?= $v['account_id'] ?></td> -->
                            <!-- <td class="text-start"><?= $v['sync_at'] ?></td> -->
                            <!-- <td class="text-start td-breakline"><a href="<?= $v['url'] ?>" target="_blank"><?= $v['url'] ?></a></td> -->
                            <td class="text-start"><?= $v['niche'] ?></td>
                            <td class="text-end"><?= $template->separator_only($v['follower']) ?></td>
                            <td class="text-end"><?= $v['ratecard'] ?></td>
                            <td class="text-end"><?= $template->separator_1($v['er']) ?>%</td>
                            <td class="text-end"><?=  $template->separator_only($v['avg_interaksi']) ?></td>
                            <td class="text-end"><?=  $template->separator_only($v['avg_view']) ?></td>
                            <!-- <td class="text-start"><?= $v['tipe_kontak'] ?></td> -->
                            <!-- <td class="text-start"><a target="_blank" href="<?= $v['contact'] ?>"><?= $v['contact'] ?></a></td> -->
                            <td class="text-end"><?= $v['frequency'] ?></td>
                            <td class="text-end"><?= $template->separator_only($v['like']) ?></td>
                            <td class="text-end"><?= $template->separator_only($v['comment']) ?></td>
                            <td class="text-end"><?= $template->separator_only($v['collect']) ?></td>
                            <td class="text-end"><?= $template->separator_only($v['share']) ?></td>
                            <td class="text-end"><?= $template->separator_only($v['view']) ?></td>
                            <td class="text-end"><?= $template->separator_only($v['total_cost']) ?></td>
                            <td class="text-end"><?= $template->separator_only((round(doubleval($v['cpm'])))) ?></td>
                            <td class="text-start"><?= $v['status'] ?></td>
    <!-- <td class="text-end  br-b-0">
        <a href="#!" onclick="sync('<?=$v['id']?>')" class="mt-0 text-green me-1"><i class="bi bi-bootstrap-reboot text-icon"></i></a>
        <a href="#!" onclick="edit('<?=$v['id']?>')" class="mt-0 text-blue me-1"><i class="bi bi-pen text-icon"></i></a>
        <a href="#!" onclick="remove('<?=$v['id']?>')" class="mt-0 text-red"><i class="bi bi-trash text-icon"></i></a>
    </td> -->
    
                        </tr>
        </tr>
        <tr>
            <td class="br-t-0"></td>
            <td colspan="27" class="text-start br-t-0">Ket : <?=$v['desc']?>
                
            <div class="mt-2">
            <a href="#!" onclick="remove('<?=$v['id']?>')" class="btn btn-delete  mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete Data</a>
                <a href="#!" onclick="sync('<?=$v['id']?>')" class="btn btn-sync ms-1 mt-0 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh</a>
                <a href="#!" onclick="edit('<?=$v['id']?>')" class="btn btn-edit  mt-0 ms-1 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit Data</a>
            </div>
            </td>
        </tr>
     </tbody>
<?php $k += 1; } ?>

<script>
     $('input[name="list_id"]').change(function() {
        get_id();
    });
</script>
<?php
$k = $start;
foreach ($data as $v) {
    if ($v['last_order']) {
        $datetime1 = DATE("Y-m-d", strtotime($v['last_order']));
        $datetime2 = DATE("Y-m-d");
        $timestamp1 = strtotime($datetime1);
        $timestamp2 = strtotime($datetime2);
        $interval_seconds = abs($timestamp2 - $timestamp1);
        $interval_days = floor($interval_seconds / (60 * 60 * 24));
        if ($interval_days > 0) {
            $order_status = "Transaksi terakhir $interval_days yang lalu.";
        } else {
            $order_status = "Transaksi terakhir hari ini.";
        }
    } else {
        $order_status = "Belum memiliki transaksi!";
    }
?>
<tr>
    <style>
        .select2{
            margin-top:-12px!important;
            margin-bottom:0px!important;
        }
        .select2-container--default .select2-selection--single {
            background-color: unset;
            border: 1px solid #aaa;
            border-radius: 4px;
        }
        .select2-container .select2-selection--single {
            box-sizing: border-box;
            cursor: pointer;
            display: block;
            height: unset!important;
            user-select: none;
            -webkit-user-select: none;
            border: unset;
            border-radius: unset;
        }

    </style>
    <td class="req"><?= $k + 1 ?>
    <form class="d-none" action="<?= base_url() ?>/customer/update-item" method="POST" id="form-modal-<?=$k?>" enctype="multipart/form-data">
    </form>
    <input form="form-modal-<?=$k?>" type="hidden" name="id" value="<?= $v['id'] ?>">
    <br>
        <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-act btn-delete" style="width:80px">DELETE</a>
    </td>
    <td class="req"><?= $v['created_at'] ?></td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[id_2]">
           <option value=""></option>
           <?php
            foreach ($cs as $k2 => $v2) {
                $text = '';
                if ($v['id_2'] == $v2['full_name']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['full_name'] ?>"><?= $v2['full_name'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[kd]" >
        <option value=""></option>
        <?php
            $arr = array();
           
$arr[] = 'Masuk Grup';
$arr[] = 'Kecantikan';
$arr[] = 'Haid';
$arr[] = 'No Respon';
$arr[] = 'Keluar Grup';
$arr[] = 'Non Promil';
$arr[] = 'No Tidak Bisa';
$arr[] = 'Promil';
$arr[] = '1 Box';
$arr[] = 'Kesehatan';
$arr[] = 'Paket COD';
$arr[] = 'Busui';
$arr[] = 'Hamil';
$arr[] = 'Retur';
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['kd'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[full_name]" value="<?=$v['full_name']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[phone]" value="<?=$v['phone']?>">
    </td>
      
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[brand]" style="width:70px">
        <option value=""></option>
            <?php
            foreach ($brands as $k2 => $v2) {
                $text = '';
                if ($v['brand'] == $v2['code']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['code'] ?>"><?= $v2['code'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-v3-<?=$k?>" name="dt[marketplace]">
        <option value=""></option>
            <?php
            foreach ($marketplace as $k2 => $v2) {
                $text = '';
                if ($v['marketplace'] == $v2['name']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[akun_type]" >
        <?php
            $arr = array();
            $arr[] = "Normal";
            $arr[] = "Reseller";
            $arr[] = "Distributor";
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['akun_type'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>                 
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[username]" value="<?=$v['username']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="date" class="text-start form-table so-<?=$k?>" name="dt[birth_date]" value="<?=$v['birth_date']?>">
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[histori]" style="width:200px;height:100px"><?=$v['histori']?></textarea>
    </td>

    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[grup]">
        <option value=""></option>
            <?php
            foreach ($group_wa as $k2 => $v2) {
                $text = '';
                if ($v['grup'] == $v2['name']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="datetime-local" class="text-start form-table so-<?=$k?>" name="dt[first_order]" value="<?php if($v['first_order']){ echo DATE("Y-m-d H:i", strtotime($v['first_order'])); }  ?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="datetime-local" class="text-start form-table so-<?=$k?>" name="dt[join]" value="<?php if($v['join']){ echo DATE("Y-m-d H:i", strtotime($v['join'])); }  ?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-end form-table io-<?=$k?>" name="dt[masa_join]" value="<?=$v['masa_join']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="date" class="text-start form-table so-<?=$k?>" name="dt[batas_join]" value="<?=$v['batas_join']?>">
    </td>
    <td class="req text-start">
        <input form="form-modal-<?=$k?>"  type="date" class="text-start form-table so-<?=$k?>" name="dt[waktu_fu_ro]" value="<?=$v['waktu_fu_ro']?>">
    </td>
    <td class="text-start">
        <input form="form-modal-<?=$k?>"  type="date" class="text-start form-table so-<?=$k?>" name="dt[waktu_fu_perkembangan]" value="<?=$v['waktu_fu_perkembangan']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="datetime-local" class="text-start form-table so-<?=$k?>" name="dt[last_order]" value="<?php if($v['first_order']){ echo DATE("Y-m-d H:i", strtotime($v['last_order'])); }  ?>">
    </td>

    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[gift]" style="width:200px;height:100px"><?=$v['gift']?></textarea>
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[treatment]" style="width:200px;height:100px"><?=$v['treatment']?></textarea>
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[riwayat_keluhan]" style="width:200px;height:100px"><?=$v['riwayat_keluhan']?></textarea>
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[perkembangan]" style="width:200px;height:100px"><?=$v['perkembangan']?></textarea>
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[pencapaian]" >
        <option value=""></option>
        <?php
            $arr = array();

            $arr[] = 'Berhasil Hamil';
            $arr[] = 'no tidak valid';
            $arr[] = 'shopee';
            $arr[] = 'no todak valid';
            $arr[] = 'belum mau masuk grup';
            $arr[] = 'tidak mau masuk grup';
            $arr[] = 'campaign tumbler';
            $arr[] = 'minta di masukan ke group ketika mau menikah';
            $arr[] = 'ia';
            $arr[] = 'perlu masuk grup';
            $arr[] = 'nomor tidak valid';
            $arr[] = 'no tdk valid';
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['pencapaian'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[address]" style="width:200px;height:100px"><?=$v['address']?></textarea>
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[province_text]" value="<?=$v['province_text']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[city_text]" value="<?=$v['city_text']?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[subdistrict_text]" value="<?=$v['subdistrict_text']?>">
    </td>
    <td class="req text-end"><?= $v['count_order'] ?></td>
                        </tr>
    <script>
        $(document).ready(function(){


        
    // $(function(){
    //     $('#customer-<?=$k?>').select2({
    //         minimumInputLength: 1,
    //         allowClear: false,
    //         placeholder: 'Pilih Customer',
    //         minimumResultsForSearch: Infinity, // Disable search box
    //         ajax: {
    //             dataType: 'json',
    //             url: '<?=base_url()?>/ajax/get-customer-list',
    //             delay: 100,
    //             data: function(params) {
    //                 return {
    //                     search: params.term
    //                 }
    //             },
    //             processResults: function (data, page) {
    //                 return {
    //                     results: data
    //                 };
    //             },
    //         }
    //     }).on('select2:select', function (evt) {
    //         var id = $("#customer-<?=$k?> option:selected").val();
    //         $.ajax({
	// 			dataType: "json",
	// 			url: '<?= base_url() ?>/ajax/get-customer-detail?id=' + id+'&id_trx=<?=$v['id']?>',
	// 			success: function(html) {
	// 				$('#n-province_text-<?=$k?>').val(html.province_text);
	// 				$('#n-city_text-<?=$k?>').val(html.city_text);
	// 				$('#n-subdistrict_text-<?=$k?>').val(html.subdistrict_text);
	// 				$('#n-address-<?=$k?>').val(html.address);
	// 				$('#n-address_2-<?=$k?>').val(html.address+' '+html.subdistrict_text+' '+html.city_text+' '+html.province_text);
	// 				$('#n-phone-<?=$k?>').val(html.phone);
	// 				$('#n-username-<?=$k?>').val(html.username);
	// 				$('#n-address').val(html.address);
	// 			}
	// 		});
    //     });
    // });

        $(".to-<?=$k?>").change(function() {
            var form = $("#form-modal-<?=$k?>");
            var mydata = new FormData(form[0]);
            var column = $(this).attr("name");
            var value = $(this).val();
            submit_form_<?=$k?>(form,mydata,column,value);
        });
        $(".co-<?=$k?>").change(function() {
            var form = $("#form-modal-<?=$k?>");
            var mydata = new FormData(form[0]);
            var column = $(this).attr("name");
            var value = $(this).val();
            submit_form_<?=$k?>(form,mydata,column,value);
        });
        $(".so-<?=$k?>").change(function() {
            var form = $("#form-modal-<?=$k?>");
            var mydata = new FormData(form[0]);
            var column = $(this).attr("name");
            var value = $(this).val();
            submit_form_<?=$k?>(form,mydata,column,value);
        });
        $(".io-<?=$k?>").keypress(function(event) {
            if (event.which === 13) {
                event.preventDefault();
                var form = $("#form-modal-<?=$k?>");
                var mydata = new FormData(form[0]);
                var column = $(this).attr("name");
                var value = $(this).val();
                submit_form_<?=$k?>(form,mydata,column,value);
            }
        });
        function submit_form_<?=$k?>(form,mydata,column,value){
            mydata.append("column", column);
            mydata.append("value", value);
            $.ajax({
                type: "POST",
                dataType: "json",
                url: form.attr("action"),
                data: mydata,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
                    form.find(".form-message").slideUp().html("");
                },
                success: function(response, textStatus, xhr) {
                    if (response.status) {
                        $(".form-message").hide().html(response.msg).slideDown("fast");
                        setTimeout(function() {
                            // window.location.href = "";
                            $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                        }, 2500);
                    } else {
                        $(".form-message").hide().html(response.msg).slideDown("fast");
                        $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    $(".btn-send").removeClass("disabled").html('SAVE CHANGES').attr('disabled', false);
                    $(".form-message").hide().html(xhr).slideDown("fast");
                }
            });
            return false;
        }
        });
    </script>
</tr>
<?php $k += 1;} ?>
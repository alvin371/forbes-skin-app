<?php
$k = $start;
foreach ($data as $v) {
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
    <form class="d-none" action="<?= base_url() ?>/transaction/update-item" method="POST" id="form-modal-<?=$k?>" enctype="multipart/form-data">
    </form>
    <input form="form-modal-<?=$k?>" type="hidden" name="id" value="<?= $v['id'] ?>">
    <br>
        <a href="<?= base_url() ?>/transaction/print?id=<?= $v['id'] ?>" target="_blank" class="btn btn-act btn-sync mb-1" style="width:80px">PRINT</a>
    <br>
        <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-act btn-delete" style="width:80px">DELETE</a>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-date-<?=$k?>" type="datetime-local" class="text-start form-table so-v3-<?=$k?>" name="dt[date]" value="<?php if($v['date']){ echo DATE("Y-m-d H:i", strtotime($v['date'])); }  ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[id_trx]" value="<?=$v['id_trx']?>">
    </td>
    <td class="req text-center">
        <input <?php if($v['corel']){ echo 'checked'; }?> form="form-modal-<?=$k?>" type="checkbox" class="text-center form-table so-<?=$k?>" name="dt[corel]" value="true">
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[brand]" style="width:70px">
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
        <select id="marketplace-<?=$k?>" form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-v3-<?=$k?>" name="dt[marketplace]">
        <option value=""></option>
        <?php
            $arr = array();
            $arr[] = "GUDANG";
            $arr[] = "ENDORSE";
            $arr[] = "FREE";
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['marketplace'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
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
        <select form="form-modal-<?=$k?>" id="cs-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[cs]">
            <option value=""></option>
            <?php
            foreach ($cs as $k2 => $v2) {
                $text = '';
                if ($v['cs'] == $v2['full_name']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['full_name'] ?>"><?= $v2['full_name'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="cs-phone-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[cs_phone]" value="<?=$v['cs_phone']?>">
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[cb_cl]"  style="width:50px">
        <?php
            $arr = array();
            $arr[] = "CB";
            $arr[] = "CL";
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['cb_cl'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" id="customer-<?=$k?>" type="text" class="d-none form-table-select2-ajax so-<?=$k?>" name="dt[customer]" style="width:200px;">
        <option value="<?=$v['customer']?>"><?=$v['customer_text']?></option>
        </select>
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" id="n-address_2-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[address_2]" style="width:200px;height:100px"><?=$v['address_2']?></textarea>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-phone-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[phone]" value="<?=$v['phone']?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-username-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[c_username]" value="<?=$v['c_username']?>">
        <input type="hidden" form="form-modal-<?=$k?>" id="n-type-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[c_type]" value="<?=$v['c_type']?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[tag]" value="<?= $v['tag'] ?>">
    </td>
    <td class="req text-start" id="n-pesanan-<?=$k?>">
        <?= nl2br($v['pesanan']) ?>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[tambahan]" value="<?= $v['tambahan'] ?>">
    </td>
    <?php 
    $item = json_decode($v['json'],true);
    foreach($product as $k2=>$v2){ ?>
        <td class="req text-start">
            <input form="form-modal-<?=$k?>" type="hidden" class="text-end form-table io-<?=$k?>" name="dtt[<?=$v2['id']?>][sku]" value="<?= $v2['sku'] ?>">
            <input form="form-modal-<?=$k?>" type="hidden" class="text-end form-table io-<?=$k?>" name="dtt[<?=$v2['id']?>][product]" value="<?= $v2['id'] ?>">
            <input form="form-modal-<?=$k?>" type="hidden" class="text-end form-table io-<?=$k?>" name="dtt[<?=$v2['id']?>][product_text]" value="<?= $v2['name'] ?>">
            <input form="form-modal-<?=$k?>" type="hidden" class="text-end form-table io-<?=$k?>" name="dtt[<?=$v2['id']?>][product_sub]" value="<?= $v2['sub_name'] ?>">
            <input form="form-modal-<?=$k?>" type="hidden" class="text-end form-table io-<?=$k?>" name="dtt[<?=$v2['id']?>][brand]" value="<?= $v2['brand'] ?>">
            <input form="form-modal-<?=$k?>" type="text" class="text-end form-table io-v2-<?=$k?>" name="dtt[<?=$v2['id']?>][qty]" value="<?= $item[$v2['id']]['qty'] ?>">
            <!-- <input form="form-modal-<?=$k?>" id="p-price-<?=$k?>-<?=$k2?>" type="text" class="text-end form-table io-v2-<?=$k?>" name="dtt[<?=$v2['id']?>][price]" value="<?= $v2['price_normal'] ?>"> -->
        </td>
    <?php } ?>
    <td class="req text-end">
        <input form="form-modal-<?=$k?>" id="n-price-total-<?=$k?>" type="text" class="text-end form-table io-v2-<?=$k?>" name="dt[price_total]" value="<?= $v['price_total'] ?>">
        <br>
        <a href="#!" id="get-price-total-<?=$k?>" style="text-decoration: none!important;"><i class="bi bi-calculator"></i> Hitung Otomatis</a>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-diskon-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[discount]" value="<?= $v['discount'] ?>">
    </td>
    <td class="req">
        <input readonly form="form-modal-<?=$k?>" id="n-price-total-2-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[price_total_2]" value="<?= $v['price_total_2'] ?>">
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[shipping]">
            <?php
            foreach ($shipping as $k2 => $v2) {
                $text = '';
                if ($v['shipping'] == $v2['name']) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2['name'] ?>"><?= $v2['name'] ?> </option>
            <?php } ?>
        </select>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-ongkir-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[shipping_price]" value="<?= $v['shipping_price'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-jenis-potongan-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[jenis_potongan]" value="<?= $v['jenis_potongan'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-marketplace-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[marketplace_fee]" value="<?= $v['marketplace_fee'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-sku-seller-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[sku_seller]" value="<?= $v['sku_seller'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-packing-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[packing_price]" value="<?= $v['packing_price'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-other-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[other_price]" value="<?= $v['other_price'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-return-fee-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[return]" value="<?= $v['return'] ?>">
    </td>
    <td class="req">
        <input readonly form="form-modal-<?=$k?>" id="n-net-price-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[net_price]" value="<?= $v['net_price'] ?>">
    </td>
    <td class="req text-start">
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[payment_type]"  style="width:50px">
        <?php
            $arr = array();
            $arr[] = "COD";
            $arr[] = "TF";
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['payment_type'] == $v2) {
                    $text = 'selected';
                }
            ?>
                <option <?= $text ?> value="<?= $v2 ?>"><?= $v2 ?></option>
            <?php } ?>
        </select>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[jumlah_cod]" value="<?= $v['jumlah_cod'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[jumlah_tf]" value="<?= $v['jumlah_tf'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="datetime-local" class="text-start form-table co-<?=$k?>" name="dt[payment_at]" value="<?php if($v['payment_at']){ echo DATE("Y-m-d H:i", strtotime($v['payment_at'])); }  ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="datetime-local" class="text-start form-table co-<?=$k?>" name="dt[payment_check_at]" value="<?php if($v['payment_check_at']){ echo DATE("Y-m-d H:i", strtotime($v['payment_check_at'])); }  ?>">
    </td>
    <td class="req text-center">
        <input <?php if($v['payment_check']){ echo 'checked'; }?> form="form-modal-<?=$k?>" type="checkbox" class="text-center form-table so-<?=$k?>" name="dt[payment_check]" value="true">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[payment_desc]" value="<?= $v['payment_desc'] ?>">
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" id="n-address-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[address]" style="width:200px;height:100px"><?=$v['address']?></textarea>
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-province_text-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[province_text]" value="<?= $v['province_text'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-city_text-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[city_text]" value="<?= $v['city_text'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" id="n-subdistrict_text-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[subdistrict_text]" value="<?= $v['subdistrict_text'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[sku]" value="<?= $v['sku'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[dari_gudang]" value="<?= $v['dari_gudang'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[kardus]" value="<?= $v['kardus'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[qty_kardus]" value="<?= $v['qty_kardus'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[awb_number]" value="<?= $v['awb_number'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[id_marketplace]" value="<?= $v['id_marketplace'] ?>">
    </td>
    <td class="req">
        <input form="form-modal-<?=$k?>" type="text" class="text-start form-table io-<?=$k?>" name="dt[c_tiktok]" value="<?= $v['c_tiktok'] ?>">
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[desc]" style="width:200px;height:100px"><?=$v['desc']?></textarea>
    </td>
    <!-- <td class="req"><?= $v['created_at'] ?></td> -->
    <script>
        $(document).ready(function(){



$("#cs-<?=$k?>").change(function() {
            var id = $("#cs-<?=$k?> option:selected").val();
            $.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/ajax/get-user-detail?id=' + id+'&id_trx=<?=$v['id']?>',
				success: function(html) {
					$('#cs-phone-<?=$k?>').val(html.phone);
				}
			});
        });
        
    $(function(){
        $('#customer-<?=$k?>').select2({
            minimumInputLength: 1,
            allowClear: false,
            placeholder: 'Pilih Customer',
            minimumResultsForSearch: Infinity, // Disable search box
            ajax: {
                dataType: 'json',
                url: '<?=base_url()?>/ajax/get-customer-list',
                delay: 100,
                data: function(params) {
                    return {
                        search: params.term
                    }
                },
                processResults: function (data, page) {
                    return {
                        results: data
                    };
                },
            }
        }).on('select2:select', function (evt) {
            var id = $("#customer-<?=$k?> option:selected").val();
            $.ajax({
				dataType: "json",
				url: '<?= base_url() ?>/ajax/get-customer-detail?id=' + id+'&id_trx=<?=$v['id']?>',
				success: function(html) {
					$('#n-province_text-<?=$k?>').val(html.province_text);
					$('#n-city_text-<?=$k?>').val(html.city_text);
					$('#n-subdistrict_text-<?=$k?>').val(html.subdistrict_text);
					$('#n-address-<?=$k?>').val(html.address);
					$('#n-address_2-<?=$k?>').val(html.address);
					$('#n-phone-<?=$k?>').val(html.phone);
					$('#n-username-<?=$k?>').val(html.username);
					$('#n-type-<?=$k?>').val(html.akun_type);
					$('#n-address').val(html.address);
				}
			});
        });
    });

        <?php foreach($product as $k2=>$v2){ ?>
            $("#check-product-<?=$k?>-<?=$k2?>").click(function() {
                $("#check-product-<?=$k?>-<?=$k2?>").html('<i class="fa fa-refresh fa-spin"></i>');
                var id_customer = $("#customer-<?=$k?>").val();
                $.ajax({
                    dataType: "json",
                    url: '<?= base_url() ?>/ajax/get-product-detail?id=<?=$v2['id']?>&id_trx=<?=$v['id']?>&id_customer='+id_customer,
                    success: function(html) {
                        $('#p-price-<?=$k?>-<?=$k2?>').val(html.price);
                        $('#n-price-total-<?=$k?>').val(html.price_total);
                        get_marketplace_fee_<?=$k?>();
                        $("#check-product-<?=$k?>-<?=$k2?>").html('<i class="fa fa-refresh"></i>');
                    }
                });
            });
        
        <?php } ?>

        $("#get-price-total-<?=$k?>").click(function() {
                $("#get-price-total-<?=$k?>").html('<i class="fa fa-refresh fa-spin"></i>');
                var id_customer = $("#customer-<?=$k?>").val();
                $.ajax({
                    dataType: "json",
                    url: '<?= base_url() ?>/ajax/get-price-total?id_trx=<?=$v['id']?>',
                    success: function(html) {
                        $('#n-price-total-<?=$k?>').val(html.price_total);
                        $(".form-message").hide().html(html.msg).slideDown("fast");
                        get_total();
                        $("#get-price-total-<?=$k?>").html('<i class="bi bi-calculator"></i> Hitung Otomatis');
                    }
                });
            });

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
                        // $('#n-price-total-<?=$k?>').val(response.price_total);
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
        
        $(".so-v2-<?=$k?>").click(function(event) {
            var form = $("#form-modal-<?=$k?>");
            var mydata = new FormData(form[0]);
            var column = $(this).attr("name");
            var value = $(this).val();
            submit_form_v2_<?=$k?>(form,mydata,column,value);
        });
        $(".io-v2-<?=$k?>").keypress(function(event) {
            if (event.which === 13) {
                event.preventDefault();
                var form = $("#form-modal-<?=$k?>");
                var mydata = new FormData(form[0]);
                var column = $(this).attr("name");
                var value = $(this).val();
                submit_form_v2_<?=$k?>(form,mydata,column,value);
            }
        });
        function submit_form_v2_<?=$k?>(form,mydata,column,value){
            mydata.append("column", column);
            mydata.append("value", value);
            $.ajax({
                type: "POST",
                dataType: "json",
                url: form.attr("action")+'?price=true',
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
                        // $('#n-price-total-<?=$k?>').val(response.price_total);
                        $('#n-pesanan-<?=$k?>').html(response.pesanan);
                        get_grand_total();
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

        $(".so-v3-<?=$k?>").change(function(event) {
            var form = $("#form-modal-<?=$k?>");
            var mydata = new FormData(form[0]);
                var column = $(this).attr("name");
                var value = $(this).val();
            submit_form_v3_<?=$k?>(form,mydata,column,value);
        });
        $(".io-v3-<?=$k?>").keypress(function(event) {
            if (event.which === 13) {
                event.preventDefault();
                var form = $("#form-modal-<?=$k?>");
                var mydata = new FormData(form[0]);
                var column = $(this).attr("name");
                var value = $(this).val();
                submit_form_v3_<?=$k?>(form,mydata,column,value);
            }
        });
        function submit_form_v3_<?=$k?>(form,mydata,column,value){
            mydata.append("column", column);
            mydata.append("value", value);
            $.ajax({
                type: "POST",
                dataType: "json",
                url: form.attr("action")+'?price=true',
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
                        get_marketplace_fee_<?=$k?>();
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

        function get_marketplace_fee_<?=$k?>(){
            var id_marketplace = $("#marketplace-<?=$k?>").val();
			var date = $("#n-date-<?=$k?>").val();
			var total_2 = $("#n-price-total-2-<?=$k?>").val();
			$.ajax({
				dataType: "json",
				url: "<?= base_url() ?>/transaction/get-marketplace-fee",
				type: "POST",
				data: {
					id_trx: '<?= $v['id'] ?>',
					id_marketplace: id_marketplace,
					date: date,
					total_2: total_2
				},
				success: function(response) {
					$("#n-marketplace-fee-<?=$k?>").val(response.html);
                    $("#n-jenis-potongan-<?=$k?>").val(response.jenis_potongan);
                    get_total();
				}
			});
        }
        
            $('#n-price-total-<?=$k?>').keyup(function() {
                get_total();
            });
            $('#n-diskon-fee-<?=$k?>').keyup(function() {
                get_total();
            });
            function get_total() {
                var val = parseFloat($('#n-price-total-<?=$k?>').val()) - parseFloat($('#n-diskon-fee-<?=$k?>').val());
                $("#n-price-total-2-<?=$k?>").val(val);
                // get_marketplace_fee_<?=$k?>();
                get_grand_total();
            }

            $('#n-ongkir-fee-<?=$k?>').keyup(function() {
                get_grand_total();
            });
            $('#n-marketplace-fee-<?=$k?>').keyup(function() {
                get_grand_total();
            });
            $('#n-packing-fee-<?=$k?>').keyup(function() {
                get_grand_total();
            });
           
            $('#n-other-fee-<?=$k?>').keyup(function() {
                get_grand_total();
            });
            $('#n-return-fee-<?=$k?>').keyup(function() {
                get_grand_total();
            });
            $('#n-sku-seller-fee-<?=$k?>').keyup(function() {
                $('#n-sku-seller-fee-<?=$k?>').val($(this).val());
                get_grand_total();
            });

            function get_grand_total() {
                var val = parseFloat($('#n-price-total-2-<?=$k?>').val()) + parseFloat($('#n-ongkir-fee-<?=$k?>').val()) - parseFloat($('#n-marketplace-fee-<?=$k?>').val()) - parseFloat($('#n-sku-seller-fee-<?=$k?>').val()) - parseFloat($('#n-packing-fee-<?=$k?>').val()) - parseFloat($('#n-return-fee-<?=$k?>').val()) + parseFloat($('#n-other-fee-<?=$k?>').val());
                $("#n-net-price-<?=$k?>").val(val);
            }
        });
    </script>
</tr>
<?php $k += 1;} ?>
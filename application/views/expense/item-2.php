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
    <form class="d-none" action="<?= base_url() ?>/expense/update-item" method="POST" id="form-modal-<?=$k?>" enctype="multipart/form-data">
    </form>
    <input form="form-modal-<?=$k?>" type="hidden" name="id" value="<?= $v['id'] ?>">
    <br>
        <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-act btn-delete" style="width:80px">DELETE</a>
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  type="datetime-local" class="text-start form-table so-<?=$k?>" name="dt[date]" value="<?php if($v['date']){ echo DATE("Y-m-d H:i", strtotime($v['date'])); }  ?>">
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
        <select form="form-modal-<?=$k?>" type="text" class="d-none form-table-select2 so-<?=$k?>" name="dt[category]" >
        <?php
            $arr = array();
            $arr[] = "Pengeluaran";
            $arr[] = "Gift";
            foreach ($arr as $k2 => $v2) {
                $text = '';
                if ($v['category'] == $v2) {
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
    <td>
        <input form="form-modal-<?=$k?>"  type="text" class="text-start form-table io-<?=$k?>" name="dt[title]" value="<?=$v['title']?>">
    </td>
    <td class="req">
        <textarea form="form-modal-<?=$k?>" type="text" class="text-start form-table to-<?=$k?>" name="dt[desc]" style="width:200px;height:100px"><?=$v['desc']?></textarea>
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  id="price-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[price]" value="<?=abs($v['price'])?>">
    </td>
    <td>
        <input form="form-modal-<?=$k?>"  id="qty-<?=$k?>" type="text" class="text-end form-table io-<?=$k?>" name="dt[qty]" value="<?=abs($v['qty'])?>">
    </td>
    <td>
        <input readonly form="form-modal-<?=$k?>" id="price-total-<?=$k?>"  type="text" class="text-end form-table io-<?=$k?>" name="dt[price_total]" value="<?=abs($v['price_total'])?>">
    </td>
    
                        </tr>
    <script>

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

        $(document).ready(function(){
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
                        if(response.brand){
                            $("#n-brand-<?=$k?>").html(response.brand);
                        }
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

        $("#price-<?=$k?>").keypress(function(event) {
            if (event.which === 13) {
                event.preventDefault();
                get_total();
            }
        });
        $("#qty-<?=$k?>").keypress(function(event) {
            if (event.which === 13) {
                event.preventDefault();
                get_total();
            }
        });
        function get_total(){
            var val = parseFloat($('#price-<?=$k?>').val()) * parseFloat($('#qty-<?=$k?>').val());
            $("#price-total-<?=$k?>").val(val);
        }
        
        });
    </script>
        </tr>
<?php $k += 1; } ?>
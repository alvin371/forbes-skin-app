<html>
    <head>
        <title><?=$title?></title>
        <link rel="shortcut icon" type="image/png" href="<?=base_url()?>/assets/img/fav.png">
        <style>
            body{
                margin:0px;
                padding:10px;
                font-family: 'Arial', sans-serif;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                border: 1px solid #413f42;
                padding: 8px;
                text-align: left;
                text-align:center;
                font-size:14px;
                color:#000;
            }
            /* .print-div{
                display: inline-block;
                page-break-after: always;
                width: 100%;
                vertical-align: top;
            } */
            .td-desc{
                font-size:12px;
                text-align:center;
                font-weight:600;
            }
            .black-div{
                background:#000!important;
                color:#FFF!important;
                font-weight:600;
            }
            .text-start{
                text-align:left;
            }
            .text-end{
                text-align:right;
            }
            .text-center{
                text-align:center;
            }
            .text-bold{
                font-weight:600!important;
            }
            @page { size: auto;  margin: 0mm; }
            @media print {
                .black-div{
                    background:#FFF!important;
                    color:#000!important;
                    font-weight:600;
                }
            }
            .mb-1{
                margin-top:0px;
                margin-bottom:4px;
            }
        </style>
    </head>
    <body>
        <?php
        $data['date'] = $template->date_format_indo($data['date']);
        $text_2nd = "";
        $text = "";
        $text_2 = "";
        foreach(json_decode($data['pesanan'],true) as $k=>$v){
            if($v['price_total'] > 0){
                $text .= '<p class="mb-1">'.$v['qty'] .'x '.$v['item_name'] .'</p>';
            }else{
                $text_2 .= '<p class="mb-1">'.$v['qty'] .'x '.$v['item_name'] .'</p>';
            }
            $text_2nd .= '<p class="mb-1">'.$v['qty'] .'x '.$v['item_name'] .'</p>';
            
        }
        if($text == ""){
            $data['pesanan'] = $text_2nd;
            $data['tambahan'] = "";
        }else{
            $data['pesanan'] = $text;
            $data['tambahan'] = $text_2;
        }

        if($data['payment_type']=="COD"){
            $data['jumlah_cod'] = $template->separator_only($data['customer_price']);
        }
        ?>
        <table class="print-div">
            <tr>
                <td style="width:120px">TANGGAL KIRIM</td>
                <td class="text-start text-bold"><?=$data['date']?></td>
            </tr>
            <tr> 
                <td class="black-div">PENERIMA</td>
                <td class="black-div"><?=$data['customer_text']?></td>
            </tr>
            <tr>
                <td>ALAMAT</td>
                <td class="text-start text-bold "><?=$data['address']?></td>
            </tr>
            <tr>
                <td>NO HP</td>
                <td class="text-start text-bold"><?=$data['phone']?></td>
            </tr>
            <tr>
                <td>PESANAN</td>
                <td class="text-start text-bold"><?=$data['pesanan']?></td>
            </tr>
            <tr>
                <td>TAMBAHAN</td>
                <td class="text-start text-bold"><?=$data['tambahan']?></td>
            </tr>
            <tr>
                <td>EKSPEDISI</td>
                <td class="text-start text-bold"><?=$data['shipping']?></td>
            </tr>
            <tr>
                <td>TOTAL COD</td>
                <td class="text-start text-bold"><?=$data['jumlah_cod']?></td>
            </tr>
            <tr>
                <td class="black-div">PENGIRIM</td>
                <td class="black-div">PT BH SKIN SINERGI BERSAMA</td>
            </tr>
            <tr>
                <td>NO HP</td>
                <td class="text-start text-bold">6285161055542</td>
            </tr>
            <tr>
                <td colspan="2" class="td-desc"><i>*Tidak Menerima Komplain, Jika Tidak Menyertakan Video Unboxing*</i></td>
            </tr>
        </table>
    </body>
</html>
<script>
    print();
</script>
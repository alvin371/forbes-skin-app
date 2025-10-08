<div class="w-100">
    <div class="row">
        <div class="col-lg-12">
            <h3 class="text-primary fw-600">RETUR ORDER</h3>
        </div>
    </div>

    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <button id="btn-scan-camera" class="btn btn-primary"><i class="fa fa-camera me-3"></i>Scan Pakai Kamera</button>
                </div>
            <div id="reader" style="width: 300px; display: none;"></div>

            <div id="form-return" class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Scan Barcode No Resi</label>
                            <input type="text" id="scan-resi" class="form-control" placeholder="Scan barcode no resi disini" autofocus>
                            <small class="text-muted">Gunakan scanner barcode untuk scan no resi</small>
                        </div>
                        <!--<button id="btn-add-manual" class="btn btn-secondary">Tambah Manual</button>-->
                    </div>
                </div>
                
                <div class="row mt-3">
                    <label>Tanggal Retur</label>
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="datetime-local" id="return-date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button id="btn-process-return" class="btn btn-primary" disabled>Proses Retur</button>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <label>Status Default Produk</label>
                        <select id="default-status" class="form-control">
                            <option value="Good" selected>Good</option>
                            <option value="Bad">Bad</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tipe Return</label>
                        <select id="default-return-type" class="form-control">
                            <option value="RETURN" selected>RETURN</option>
                            <option value="RETURN_UNSHIPPED">RETURN_UNSHIPPED</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="table-return-list">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>No Resi</th>
                                        <th>Order ID</th>
                                        <th>Marketplace</th>
                                        <th>Tanggal Order</th>
                                        <th>Status</th>
                                        <th>Produk</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const returnList = [];
        let html5QrCode;
        let scanTimeout;
        
        function playBeepSound() {
            const beepSound = new Audio('https://app.bhskin.co.id/assets/webfile/store-scanner-beep-90395.mp3');
            beepSound.play().catch(e => console.log("Gagal memainkan suara:", e));
        }
    
        $('#scan-resi').on('input', function() {
            clearTimeout(scanTimeout);
            const input = $(this);
            
            scanTimeout = setTimeout(() => {
                const awbNumber = input.val().trim();
                if (awbNumber.length >= 10) {
                    playBeepSound(); 
                    addToReturnList(awbNumber);
                    input.val('').focus();
                }
            }, 100); 
        });
    
        $('#btn-scan-camera').click(function () {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    $('#reader').hide();
                    $(this).text('Scan Pakai Kamera');
                });
                return;
            }
            
            $('#reader').show();
            $(this).text('Stop Scanning');
            html5QrCode = new Html5Qrcode("reader");
    
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: 250
                },
                (decodedText) => {
                    playBeepSound(); 
                    addToReturnList(decodedText);
                    
                    $('#reader').css('border', '3px solid green');
                    setTimeout(() => $('#reader').css('border', 'none'), 300);
                },
                (errorMessage) => {
                    console.log("Scan error:", errorMessage);
                }
            ).catch(err => {
                alert("Gagal mengakses kamera: " + err);
            });
        });
    
        $('#btn-add-manual').click(function() {
            const awbNumber = prompt('Masukkan No Resi:');
            if (awbNumber) {
                addToReturnList(awbNumber.trim());
            }
        });
    
        function addToReturnList(awbNumber) {
            const normalizedAwb = awbNumber.toString().trim().toLowerCase();

            const isDuplicate = returnList.some(item =>
                item.awb_number.toString().trim().toLowerCase() === normalizedAwb
            );

            if (isDuplicate) {
                return;
            }

            $.ajax({
                url: '<?= base_url() ?>transaction/get_transaction_by_awb',
                method: 'POST',
                data: { awb_number: awbNumber },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        response.data.scanTime = new Date();

                        const defaultStatus = $('#default-status').val();           
                        const defaultReturn = $('#default-return-type').val();      

                        let products = JSON.parse(response.data.pesanan);
                        Object.values(products).forEach(p => {
                            p.status = p.status || defaultStatus;
                            p.return_status = p.return_status || defaultReturn;        
                        });
                        response.data.pesanan = JSON.stringify(products);

                        returnList.push(response.data);
                        updateReturnTable();
                        checkProcessButton();
                    } else {
                        Swal.fire({
                        title: "Informasi",
                        text: response.message,
                        icon: "info" 
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Gagal mengambil data transaksi',
                        icon: "error"
                    });
                }
            });
        }

    
        function updateReturnTable() {
            const tbody = $('#table-return-list tbody');
            tbody.empty();

            const sortedList = [...returnList].sort((a, b) => {
                if (!a.scanTime) a.scanTime = new Date();
                if (!b.scanTime) b.scanTime = new Date();
                return b.scanTime - a.scanTime;
            });

            for (let i = 0; i < sortedList.length; i++) {
                const item = sortedList[i];

                let productList = '';
                try {
                    const products = JSON.parse(item.pesanan);
                    productList = Object.values(products).map((p, idx) => {
                        const status  = p.status || $('#default-status').val();
                        const rstatus = p.return_status || $('#default-return-type').val();

                        return `
                        <div class="row mb-1">
                            <div class="col-md-8">
                            ${p.qty}x ${p.product_text} | ${p.sku}
                            </div>
                            <div class="col-md-4">
                            <select class="form-control form-control-sm product-status"
                                    data-awb="${item.awb_number}" data-sku="${p.sku}">
                                <option value="Good" ${status === 'Good' ? 'selected' : ''}>Good</option>
                                <option value="Bad"  ${status === 'Bad'  ? 'selected' : ''}>Bad</option>
                            </select>
                            <select class="form-control form-control-sm return-status"
                                    data-awb="${item.awb_number}" data-sku="${p.sku}">
                                <option value="RETURN"           ${rstatus === 'RETURN' ? 'selected' : ''}>RETURN</option>
                                <option value="RETURN_UNSHIPPED" ${rstatus === 'RETURN_UNSHIPPED' ? 'selected' : ''}>RETURN_UNSHIPPED</option>
                            </select>
                            </div>
                        </div>
                        `;

                    }).join('');
                } catch (e) {
                    productList = 'Invalid product data';
                }

                const row = `
                    <tr data-awb="${item.awb_number || ''}">
                        <input type="hidden" name="id[]" value="${item.id || ''}">
                        <td>${i + 1}</td>
                        <td>${item.awb_number || ''}</td>
                        <td>${item.order_id || ''}</td>
                        <td>${item.marketplace || ''}</td>
                        <td>${item.date || ''}</td>
                        <td>${item.order_status || ''}</td>
                        <td>${productList}</td>
                        <td>
                            <button class="btn btn-sm btn-danger btn-remove" data-awb="${item.awb_number || ''}">
                                Hapus
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            }

            $('.product-status').change(function() {
                const awb = $(this).data('awb');
                const sku = $(this).data('sku');
                const status = $(this).val();

                const trx = returnList.find(r => r.awb_number === awb);
                if (trx) {
                    let products = JSON.parse(trx.pesanan);
                    Object.values(products).forEach(p => {
                        if (p.sku === sku) {
                            p.status = status;
                        }
                    });
                    trx.pesanan = JSON.stringify(products);
                }
            });

            $('.return-status').change(function() {
                const awb = $(this).data('awb');
                const sku = $(this).data('sku');
                const rstatus = $(this).val();                

                const trx = returnList.find(r => r.awb_number === awb);
                if (trx) {
                    let products = JSON.parse(trx.pesanan);
                    Object.values(products).forEach(p => {
                    if (p.sku === sku) {
                        p.return_status = rstatus;              
                    }
                    });
                    trx.pesanan = JSON.stringify(products);
                }
            });


            $('.btn-remove').click(function() {
                const awbNumber = $(this).data('awb');
                const index = returnList.findIndex(item => item.awb_number === awbNumber);
                if (index !== -1) {
                    returnList.splice(index, 1);
                    updateReturnTable();
                    checkProcessButton();
                }
            });
        }



    
        function checkProcessButton() {
            $('#btn-process-return').prop('disabled', returnList.length === 0);
        }
    
        $('#btn-process-return').click(function () {
            if (returnList.length === 0) {
                Swal.fire({ icon:'warning', title:'Tidak ada order untuk diproses', showConfirmButton:true, timer:1500 });
                return;
            }

            const returnDate = $('#return-date').val();
            if (!returnDate) {
                Swal.fire({ icon:'warning', title:'Harap masukkan tanggal retur', showConfirmButton:true, timer:1500 });
                return;
            }

            // KUMPULKAN ITEM YANG AKAN DI-RETUR (payload)
            const payload = [];
            returnList.forEach(order => {
                try {
                const pesanan = JSON.parse(order.pesanan);
                Object.values(pesanan).forEach(item => {
                    if (item.status) {
                    payload.push({
                        order_id: order.id,            
                        sku: item.sku,
                        product: item.product_text,
                        qty: item.qty,
                        status: item.status,
                        return_status: item.return_status
                    });
                    }
                });
                } catch (e) {
                console.error("Gagal parse pesanan:", e, order.pesanan);
                }
            });

            const trxIds = [...new Set(payload.map(x => x.order_id))];
            console.log(payload);

            const doAjax = () => {
                $.ajax({
                url: '<?= base_url() ?>transaction/process_multi_return',
                method: 'POST',
                data: {
                    return_date: returnDate,
                    return_list: JSON.stringify(payload || []),
                    trx_ids: trxIds
                },
                dataType: 'json',
                beforeSend: function () {
                    $('#btn-process-return').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
                },
                success: function (response) {
                    Swal.fire({
                    title: response.status === 'success' ? 'Berhasil' : 'Gagal',
                    text: response.message,
                    icon: response.status === 'success' ? 'success' : 'error',
                    timer: 1500,
                    }).then(() => {
                    if (response.status === 'success') {
                        window.location.reload();
                    } else {
                        $('#btn-process-return').prop('disabled', false).html('Proses Retur');
                    }
                    });
                },
                error: function () {
                    Swal.fire({
                    position: 'top-end',
                    title: 'Gagal memproses retur',
                    text: 'Terjadi kesalahan pada server atau jaringan.',
                    icon: 'error',
                    showConfirmButton: false,
                    timer: 1500,
                    customClass: { title: 'text-sm', popup: 'p-3' }
                    });
                    $('#btn-process-return').prop('disabled', false).html('Proses Retur');
                }
                });
            };

            if (payload.length === 0) {
                Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: `Tidak ada item return.`,
                showCancelButton: false,
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-secondary' }
                }).then((r) => { if (r.isConfirmed) doAjax(); });
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Konfirmasi',
                text: `Apakah Anda yakin ingin memproses retur untuk ${payload.length} item?`,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-secondary' }
            }).then((result) => { if (result.isConfirmed) doAjax(); });
        });
    });
</script>
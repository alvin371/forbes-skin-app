<?php $current_view = isset($_GET['view']) ? $_GET['view'] : 'card';  ?>
<style>
#page-loading {
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: none;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
    border: 0.25em solid transparent;
    border-top-color: #e91e63;
    border-right-color: #ff4081;
    border-bottom-color: #f48fb1;
    border-left-color: #ad1457;
    border-radius: 50%;
    animation: spin 1s linear infinite, colorShift 3s ease-in-out infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

@keyframes colorShift {
    0% {
        border-top-color: #e91e63;
        border-right-color: #ff4081;
        border-bottom-color: #f48fb1;
        border-left-color: #ad1457;
    }
    25% {
        border-top-color: #ff4081;
        border-right-color: #f48fb1;
        border-bottom-color: #ad1457;
        border-left-color: #e91e63;
    }
    50% {
        border-top-color: #f48fb1;
        border-right-color: #ad1457;
        border-bottom-color: #e91e63;
        border-left-color: #ff4081;
    }
    75% {
        border-top-color: #ad1457;
        border-right-color: #e91e63;
        border-bottom-color: #ff4081;
        border-left-color: #f48fb1;
    }
    100% {
        border-top-color: #e91e63;
        border-right-color: #ff4081;
        border-bottom-color: #f48fb1;
        border-left-color: #ad1457;
    }
}

.spinner-logo {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 60px; 
    height: auto;
    animation: pulse-logo 1.2s ease-in-out infinite;
    transform-origin: center;
}

@keyframes pulse-logo {
    0% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, -50%) scale(1.2); }
    100% { transform: translate(-50%, -50%) scale(1); }
}
</style>
<div class="w-100">
    <div id="page-loading" style="
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.85);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    ">
        <div style="position: relative; width: 100px; height: 100px;">
            <div class="spinner-border" role="status" style="width: 100px; height: 100px;">
            </div>

            <img src="<?= base_url() . '/assets/img/acneno-logo.png'; ?>" alt="Logo" class="spinner-logo" style="
                position: absolute;
                top: 50%;
                left: 50%;
                width: 40px;
                height: 40px;
                transform: translate(-50%, -50%);
            ">
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col-lg-12 mb-3">
            <h3 class="text-primary fw-600">INFLUENCER</h3>
        </div>
        <input type="hidden" name="view" value="<?= $current_view ?>">
        <div class="col-lg-12">
            <form action="">
                <div class="row">
                    <div class="col-md-12">
                        <?php
                        $arr = array();
                        $arr[] = "Semua Status";
                        $arr[] = "Affiliate";
                        $arr[] = "Belum Reachout";
                        $arr[] = "Sudah Reachout";
                        $arr[] = "Off Endorsement";
                        $arr[] = "Pernah Kerjasama";
                        $arr[] = "Repeat Kerjasama";
                        $arr[] = "Blacklist / Ghosting";
                        foreach ($arr as $k => $val) {
                            $class = "btn-default";
                            $class_2 = "dot";

                            $value = $val;
                            if ($k == 0) {
                                $value = '';
                            }

                            $status = isset($_GET['status']) ? $_GET['status'] : '';

                            $statusArray = $status ? explode(',', $status) : [];

                            if (($key = array_search($value, $statusArray)) !== false) {
                                unset($statusArray[$key]);
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            } else {
                                $statusArray[] = $value;
                            }

                            $status = implode(',', $statusArray);

                            if ($k == 0) {
                                $status = '';
                            }

                            if ($k == 0 && $_GET['status'] == "") {
                                $class_2 = "dot-active";
                                $class = "btn-default-selected";
                            }

                        ?>
                            <a href="<?= $url_1 ?>&status=<?= $status ?>" class="btn <?= $class ?> mb-3 me-2"><span class="<?= $class_2 ?>"></span> <?= $val ?></a>
                        <?php }  ?>

                        <input type="hidden" name="ids" value="<?= $ids ?>">
                        <input type="hidden" name="status" value="<?= $_GET['status'] ?>">
                    </div>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary-category dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-top-right-radius: 0px !important;
                            border-bottom-right-radius: 0px !important;"><?= $keyword_category ?></button>
                            <ul class="dropdown-menu">
                                <?php
                                $arr = array();
                                // $arr[] = 'Nama Creator';
                                $arr[] = 'Username';
                                $arr[] = 'Platform';
                                $arr[] = 'URL';
                                $arr[] = 'Niche';
                                $arr[] = 'Keterangan';
                                $arr[] = 'PIC';
                                foreach ($arr as $k => $val) {
                                    $class = "btn-default";
                                    if ($_GET['order_status'] == $val) {
                                        $class = "btn-default-selected";
                                    }
                                ?>
                                    <li><a class="dropdown-item" href="<?= $url_2 ?>&keyword_category=<?= $val ?>"><?= $val ?></a></li>
                                <?php }  ?>
                            </ul>
                            <input type="hidden" name="keyword_category" value="<?= $keyword_category ?>">
                            <input type="text" name="keyword" class="form-control" value="<?= $_GET['keyword'] ?>" style="border-top-left-radius: 0px !important;
                            border-bottom-left-radius: 0px !important;">
                        </div>
                    </div>
                    <?php if ( $current_view == 'card'): ?>
                    <div class="col-md-2">
                        <?php
                        $arr = [];
                        $arr[] = "";
                        // $arr[] = "Follower";
                        // $arr[] = "Views";
                        // $arr[] = "ER";
                        $arr[] = "Frequency";
                        $arr[] = "RC";
                        $arr[] = "CPM";
                        $arr[] = "ER";
                        ?>
                        <select class="form-control" name="sort">
                            <?php foreach ($arr as $k => $v) {
                                $text = "";
                                if (isset($_GET['sort']) && $_GET['sort'] == $v) {
                                    $text = "selected";
                                }
                            ?>
                                <option <?= $text ?> value="<?= $v ?>"><?= $v ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <?php
                        $arr = [];
                        $arr[] = "";
                        $arr[] = "Asc";
                        $arr[] = "Desc";
                        ?>
                        <select class="form-control" name="sort_sub">
                            <?php foreach ($arr as $k => $v) {
                                $text = "";
                                if (isset($_GET['sort_sub']) && $_GET['sort_sub'] == $v) {
                                    $text = "selected";
                                }
                            ?>
                                <option <?= $text ?> value="<?= $v ?>"><?= $v ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-lg-6">
                        <button class="btn btn-edit-active mb-2" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
                        <!-- <a href="#!" onclick="sync_all()" class="btn btn-sync mt-0 ms-1 mb-2"><i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Semua</a> -->
                        <a href="#!" onclick="create()" class="btn btn-primary px-2 mt-0 ms-1 mb-2"><i class="bi bi-plus-circle-dotted fs-16"></i> Tambah Data</a>
                        <a href="#" id="btn-refresh" class="btn btn-sync px-2 mt-0 ms-1 mb-2" disabled="false">
                            <i class="bi bi-bootstrap-reboot fs-16 me-1"></i>
                            <span class="btn-text">Refresh Data Baru</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </a>


                    </div>

                </div>

        </div>
        </form>
    </div>
</div>

<tr>
    <td>
        <div class="d-flex justify-content-between align-items-center w-100">
            <span><?= $notif ?></span>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownView" data-bs-toggle="dropdown" aria-expanded="false">
                    Pilih Tampilan
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownView">
                    <?php
                    $current_params = $_GET;
                    
                    $current_params['view'] = 'card';
                    $card_url = 'influencer?' . http_build_query($current_params);
                    
                    $current_params['view'] = 'table';
                    $table_url = 'influencer?' . http_build_query($current_params);
                    ?>
                    <li><a class="dropdown-item" href="<?= $card_url ?>">Tampilan Kartu</a></li>
                    <li><a class="dropdown-item" href="<?= $table_url ?>">Tampilan List</a></li>
                </ul>
            </div>
        </div>
    </td>
</tr>

<div class="col-lg-12 mb-3">
    <div class="checkbox-wrapper-13">
        <input id="c1-13" type="checkbox" value="1" class="checkAll">
        <label for="c1-13">Pilih Semua Data</label>
    </div>
</div>

<div class="col-lg-12">
    <div class="col-lg-12">
        <div id="tbody">
            <?php $this->load->view('loading', true) ?>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <div>
            <?= $pagination ?>
        </div>
        <div>
            <?php
            $per_page_options = [10, 20, 50, 100, 500];
            $limit = $_GET['limit'] ?? 10;
            if (!in_array($limit, $per_page_options)) {
                $limit = 10;
            }

            $query_params = $_GET;
            unset($query_params['limit']);
            ?>

            <form method="GET" action="">
                <?php foreach ($query_params as $key => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endforeach; ?>

                <select class="form-control select2" name="limit" id="limit"
                    onchange="this.form.submit()">
                    <?php foreach ($per_page_options as $option): ?>
                        <option value="<?= $option ?>" <?= ($limit == $option) ? 'selected' : '' ?>>
                            <?= $option ?> / Halaman
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

        </div>
    </div>
</div>


<div class="floating-div">
    <button class="btn mb-2 btn-edit-active dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear fs-16"></i> Aksi
    </button>
    <ul class="dropdown-menu text-end" style="padding:0px;background:unset;border:unset">


        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="refresh_data()">
                    <i class="bi bi-bootstrap-reboot fs-16"></i> Refresh Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="tampilkan_data()">
                    <i class="bi bi-eye fs-16"></i> Tampilkan Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status()">
                    <i class="bi bi-cursor fs-16"></i> Ubah Status Influencer
                </button>
            </a></li>
        <li><a class="dropdown-items" href="#!" style="padding:0px;">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="ubah_status_data()">
                    <i class="bi bi-cursor fs-16"></i> Ubah Status Data
                </button>
            </a></li>

        <li><a class="dropdown-items" href="#!" style="padding:0px">
                <button type="button" class="btn mb-2 btn-edit-active" onclick="hapus_data()">
                    <i class="bi bi-trash fs-16"></i> Hapus Data
                </button>
            </a></li>

    </ul>

</div>


<div class="modal fade bd-example-modal-lg" tabindex="-1" varietas="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-form">
    <div class="modal-dialog" id="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title-form"></h5>
                <a class="close a-link" data-bs-dismiss="modal"><i class="bi bi-x-circle fs-24"></i></a>
            </div>
            <div class="modal-body">
                <div id="load-form"></div>
            </div>
        </div>
    </div>
</div>


<input type="hidden" id="id_selected" name="id_selected" form="form-action">

<script>
    var list_id_v2 = '';
    
    $('#btn-refresh').on('click', function(e) {
        e.preventDefault();
    
        Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin me-refresh data influencer?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Refresh Data',
        cancelButtonText: 'Batal',
        customClass: {
            confirmButton: 'btn btn-primary me-2',  
            cancelButton: 'btn btn-secondary'  
        },
        buttonsStyling: false 
    }).then((result) => {
            if (result.isConfirmed) {
                $('#page-loading').fadeIn(200);
    
                $.getJSON('<?= base_url() ?>influencer/sync_external_process', function(response) {
                    console.log("Response received", response);
    
                    if (response.status === 'success') {
                    $.toast({
                        heading: "Informasi",
                        text: response.message,
                        showHideTransition: "slide",
                        icon: "success",
                        position: "top-right",
                        loaderBg: "#def7f0",
                        hideAfter: 2500,
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2600); 
                } else {
                    $.toast({
                        heading: "Gagal",
                        text: response.message,
                        icon: "error",
                        position: "top-right"
                    });
                }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Failed", textStatus, errorThrown);
                    Swal.fire("Error", "Gagal terhubung ke server. Coba lagi.", "error");
                }).always(function() {
                    $('#page-loading').fadeOut(200);
                });
            }
        });
    });




    function get_id() {
        list_id_v2 = '';
        var selectedValues = [];
        $('input[name="list_id"]').each(function() {
            if ($(this).is(":checked")) {
                selectedValues.push($(this).val());
                list_id_v2 += $(this).val() + ',';
            } else {
                selectedValues.push('0');
            }
        });
        if (list_id_v2.length > 0) {
            list_id_v2 = list_id_v2.slice(0, -1); 
        }
        $('#id_selected').val(selectedValues.join(','));
    }

    function tampilkan_data(id) {
        window.location.href = "<?= base_url() ?>/influencer?ids=" + list_id_v2;
    }

    function hapus_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/influencer/action?code=hapus_data&id=" + id);
    }

    function ubah_status(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Ubah Status Influencer');
        $("#load-form").load("<?= base_url() ?>/influencer/action?code=ubah_status&id=" + id);
    }

    function ubah_status_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Ubah Status Data');
        $("#load-form").load("<?= base_url() ?>/influencer/action?code=ubah_status_data&id=" + id);
    }

    function refresh_data(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/influencer/action?code=refresh_data&id=" + id);
    }

    function create() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass("modal-lg"); 
        $("#title-form").html('Tambah Data');
        $("#load-form").load("<?= base_url() ?>/influencer/create");
    }

    function remove(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Hapus Data');
        $("#load-form").load("<?= base_url() ?>/influencer/remove?id=" + id);
    }

    function edit(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#modal-dialog").addClass("modal-lg"); 
        $("#title-form").html('Edit Data');
        $("#load-form").load("<?= base_url() ?>/influencer/edit?id=" + id);
    }

    function sync(id) {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Data');
        $("#load-form").load("<?= base_url() ?>/influencer/sync?id=" + id);
    }

    function sync_all() {
        $("#load-form").html('Loading...');
        $("#modal-form").modal('show');
        $("#title-form").html('Refresh Semua Data');
        $("#load-form").load("<?= base_url() ?>/influencer/sync_all<?= $param ?>");
    }
</script>
<script>
    function loadMoreData() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortColumn = urlParams.get('sort_column') || 'id';
        const sortOrder = urlParams.get('sort_order') || 'DESC';
        
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/influencer/item?<?= $param ?>&sort_column=" + sortColumn + "&sort_order=" + sortOrder + "&view=<?= $current_view ?>",
            success: function(data) {
                $('#tbody').html('').append(data);
                select3();
                initSorting();
                updateSortingIcons(sortColumn, sortOrder);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            }
        });
    }

    function initSorting() {
        $('th.sortable').off('click').on('click', function() {
            const scrollPosition = $(window).scrollTop();
            
            const urlParams = new URLSearchParams(window.location.search);
            const currentSortColumn = urlParams.get('sort_column') || 'id';
            const currentSortOrder = urlParams.get('sort_order') || 'desc';
            
            const columnName = $(this).text().trim().toLowerCase();
            const columnMap = {
                'nama influencer': 'username',  
                'followers': 'follower',
                'engagement internal': 'cpm',
                'engagement external': 'cpm_2',
                'ratecard': 'ratecard'
            };
            
            const clickedColumn = columnMap[columnName] || 'id';
            
            let newSortOrder;
            if (clickedColumn === currentSortColumn) {
                newSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                newSortOrder = 'desc';
            }
            
            loadMoreDataWithSort(clickedColumn, newSortOrder, scrollPosition);
        });
    }

    function loadMoreDataWithSort(sortColumn, sortOrder, scrollPosition) {
        const urlParams = new URLSearchParams(window.location.search);
        
        const columnMap = {
            'username': 'username',
            'follower': 'follower',
            'engagement_internal': 'cpm', 
            'engagement_external': 'cpm_2',
            'ratecard': 'ratecard'
        };
        
        const dbSortColumn = columnMap[sortColumn] || sortColumn;
        
        urlParams.set('sort_column', dbSortColumn);
        urlParams.set('sort_order', sortOrder);
        
        urlParams.set('view', '<?= $current_view ?>');
        
        history.pushState(null, '', '?' + urlParams.toString());
        
        $('#tbody').html('<tr><td colspan="13" class="text-center"><div class="spinner-border" role="status"></div></td></tr>');
        
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>/influencer/item?" + urlParams.toString(),
            success: function(data) {
                $('#tbody').html(data);
                select3();
                initSorting(); 
                updateSortingIcons(sortColumn, sortOrder);
                
                $(window).scrollTop(scrollPosition);
            },
            error: function(xhr, status, error) {
                console.error("Error loading data:", error);
            }
        });
    }

    function updateSortingIcons(sortColumn, sortOrder) {
        $('th.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
        const reverseColumnMap = {
            'username': 'nama influencer',
            'follower': 'followers',
            'engagement_internal': 'engagement internal',
            'engagement_external': 'engagement external',
            'ratecard': 'ratecard'
        };
        
        const headerText = reverseColumnMap[sortColumn];
        if (headerText) {
            $('th.sortable').each(function() {
                if ($(this).text().trim().toLowerCase() === headerText) {
                    $(this).find('i')
                        .removeClass('bi-arrow-down-up')
                        .addClass(sortOrder === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down');
                }
            });
        }
    }

    function updateRowNumbers(table) {
        table.find('tr:gt(0)').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    function comparer(index) {
        return function(a, b) {
            let valA, valB;

            if (index === 3 || index === 4) {
                const cpmRegex = /CPM : ([\d.,]+)/;
                
                const textA = $(a).children('td').eq(index).text();
                const textB = $(b).children('td').eq(index).text();
                
                const matchA = textA.match(cpmRegex);
                const matchB = textB.match(cpmRegex);
                
                valA = matchA ? matchA[1].replace(/\./g, '') : '0';
                valB = matchB ? matchB[1].replace(/\./g, '') : '0';
            } else {
                valA = $(a).children('td').eq(index).text().trim();
                valB = $(b).children('td').eq(index).text().trim();
            }

            if (!valA && !valB) return 0;
            if (!valA) return 1;
            if (!valB) return -1;

            if (index === 2 || index === 5) { 
                valA = valA.replace(/\./g, '');
                valB = valB.replace(/\./g, '');
            }

            const numA = parseFloat(valA.replace(/[^\d.-]/g, ''));
            const numB = parseFloat(valB.replace(/[^\d.-]/g, ''));

            if (!isNaN(numA) && !isNaN(numB)) {
                return numA - numB;
            }

            return valA.localeCompare(valB);
        };
    }

    function getCellValue(row, index) {
        return $(row).children('td').eq(index).text() || $(row).children('td').eq(index).find('span').text();
    }

    $(document).ready(function() {
        loadMoreData();
    });
</script>
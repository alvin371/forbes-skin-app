<?php
if (!$_SESSION['is_login']) {
  redirect(base_url() . 'auth/login');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <meta name="description" content="We are Building Legacy, that Impactfull to the Society">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap-datepicker.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
  
  <!-- Career Tree Visualization CSS -->
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/career-tree.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-quartz.css" />
  <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>

  <!-- Include jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script> -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="<?= base_url() ?>assets/js/bootstrap-datepicker.min.js"></script> -->

  <!-- Font Awesome 5.15.4 (Free) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- D3.js v7 for Career Tree Visualization -->
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <script src="<?= base_url() ?>assets/js/career-tree-visualization.js"></script>
  
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script> -->
  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-database-compat.js"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
  <!-- Toastr -->
  <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

  <!-- Load daterangepicker -->

  <!-- <script>
    // Konfigurasi Firebase
    const firebaseConfig = {
      apiKey: "AIzaSyDZUQ4Hei7Kmlplwl2ZLcncxE1rj3rzNkM",
      authDomain: "notif-order-24c9c.firebaseapp.com",
      databaseURL: "https://notif-order-24c9c-default-rtdb.asia-southeast1.firebasedatabase.app",
      projectId: "notif-order-24c9c",
      storageBucket: "notif-order-24c9c.appspot.com",
      messagingSenderId: "741337654239",
      appId: "1:741337654239:web:2dd302ccb675e58df3da6a",
      measurementId: "G-EYS8BRN1EB"
    };

    // Inisialisasi Firebase
    firebase.initializeApp(firebaseConfig);

    // Akses database
    const database = firebase.database();

    console.log("Firebase berhasil diinisialisasi!");
  </script> -->

  <script>
    $(document).ready(function() {
      // Verifikasi library
      console.log('jQuery version:', $.fn.jquery);
      console.log('Moment.js version:', moment.version);
      console.log('Daterangepicker available:', typeof $.fn.daterangepicker === 'function');

      // Inisialisasi daterangepicker
      $('#date-range').daterangepicker({
        locale: {
          format: 'YYYY-MM-DD',
          applyLabel: 'Terapkan',
          cancelLabel: 'Batal',
          daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
          monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ],
          firstDay: 1
        },
        opens: 'right',
        autoUpdateInput: false,
        showDropdowns: true
      });

      $('#date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
      });
    });
  </script>



  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">

  <link rel="stylesheet" href="<?= base_url() ?>assets/css/style.css?v=1.0.4" type="text/css" media="screen" />

  <?php
  if ($data[0]['img']) {
    $img = base_url() . '/assets/webfile/home/' . $data[0]['img'];
  } else {
    $img = base_url() . '/assets/img/acneno-logo.png';
  }
  $img = base_url() . '/assets/img/fav.png';
  ?>
  <meta property="og:image" content="<?= $img ?>" />
  <meta property="og:image:width" content="1000" />
  <meta property="og:image:height" content="1000" />

  <link rel="stylesheet" href="https://icons.getbootstrap.com/assets/font/bootstrap-icons.css">
  <link href="https://pictogrammers.github.io/@mdi/font/2.0.46/css/materialdesignicons.min.css" media="all" rel="stylesheet" type="text/css" />


  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js@3.3.2/dist/chart.min.js"></script> -->
  <script src="<?= base_url() ?>assets/chart/chart.js"></script>
  <script src="<?= base_url() ?>assets/chart/gauge.min.js"></script>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script> -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

  <link rel="stylesheet" href="<?= base_url() ?>assets/toast/jquery.toast.css">
  <script src="<?= base_url() ?>assets/toast/jquery.toast.js"></script>

  <link rel="stylesheet" href="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/assets/owl.theme.default.min.css">
  <link rel="stylesheet" href="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/assets/owl.carousel.min.css">
  <script src="https://owlcarousel2.github.io/OwlCarousel2/assets/owlcarousel/owl.carousel.js"></script>




  <!-- <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.min.css' rel='stylesheet' />
  <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.css' rel='stylesheet' />
  <link href='<?= base_url() ?>assets/fullcalendar/fullcalendar.print.min.css' rel='stylesheet' media='print' />
  <script src='<?= base_url() ?>assets/fullcalendar/moment.min.js'></script>
  <script src='<?= base_url() ?>assets/fullcalendar/fullcalendar.min.js'></script> -->

  <!-- <link rel="stylesheet" href="<?= base_url() ?>assets/fullcalendar/fullcalendar.css">
  <script src="<?= base_url() ?>assets/fullcalendar/moment.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/fullcalendar.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/jquery-ui.min.js"></script>
  <script src="<?= base_url() ?>assets/fullcalendar/jquery.min.js"></script> -->

  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://unpkg.com/tippy.js@6"></script>

  <style>
    #calendar {
      border-radius: 10px;
      /* padding:20px 15px; */
      max-width: 100%;
      margin: 0 auto;
      background: #FFF;
    }

    .fc-center h2 {
      font-size: 21px;
    }

    input[type="file"]::-webkit-file-upload-button {
      height: 45px;
    }

    .form-table-1 {
      border: unset;
      background: transparent;
      min-width: 100px !important;
    }

    .btn-action {
      font-size: 15px;
    }

    .btn-action {
      font-size: 11px;
      height: 32px;
      padding-top: 0px !important;
    }

    .content-body {
      padding: 32px;
      min-height: 101vh;
      background-color: #F2F2F2;
    }

    .box-legend {
      width: 7px !important;
      height: 7px !important;
      margin-right: 5px !important;
      margin-top: 4px !important;
    }

    .select2 {
      height: 45px !important;
      margin-top: 0px !important;
      margin-bottom: 10px !important;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.07) !important;
      /* min-width: 100% !important; */
      /* max-width: 100% !important; */
      /* width: 100% !important; */
    }

    .select2-container .select2-selection--single {
      box-sizing: border-box;
      cursor: pointer;
      display: block;
      height: 45px !important;
      user-select: none;
      -webkit-user-select: none;
      border: 1px solid #ced4da;
      border-radius: 0.5rem !important;
    }


    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #444;
      line-height: 45px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 45px !important;
      position: absolute;
      top: 0px;
      right: 1px;
      width: 20px;
    }

    .floating-div {
      position: fixed;
      bottom: 10px;
      right: 30px;
      max-width: fit-content;
    }
  </style>


  <style>
    @supports (-webkit-appearance: none) or (-moz-appearance: none) {
      .checkbox-wrapper-13 input[type=checkbox] {
        --active: #275EFE;
        --active-inner: #fff;
        --focus: 2px rgba(39, 94, 254, .3);
        --border: #BBC1E1;
        --border-hover: #275EFE;
        --background: #fff;
        --disabled: #F6F8FF;
        --disabled-inner: #E1E6F9;
        -webkit-appearance: none;
        -moz-appearance: none;
        height: 21px;
        outline: none;
        display: inline-block;
        vertical-align: top;
        position: relative;
        cursor: pointer;
        border: 1px solid var(--bc, var(--border));
        background: var(--b, var(--background));
        transition: background 0.3s, border-color 0.3s, box-shadow 0.2s;
        /* padding-left: 1rem !important; */
      }

      .checkbox-wrapper-13 input[type=checkbox]:after {
        content: "";
        display: block;
        left: 0;
        top: 0;
        position: absolute;
        transition: transform var(--d-t, 0.3s) var(--d-t-e, ease), opacity var(--d-o, 0.2s);
      }

      .checkbox-wrapper-13 input[type=checkbox]:checked {
        --b: var(--active);
        --bc: var(--active);
        --d-o: .3s;
        --d-t: .6s;
        --d-t-e: cubic-bezier(.2, .85, .32, 1.2);
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled {
        --b: var(--disabled);
        cursor: not-allowed;
        opacity: 0.9;
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled:checked {
        --b: var(--disabled-inner);
        --bc: var(--border);
      }

      .checkbox-wrapper-13 input[type=checkbox]:disabled+label {
        cursor: not-allowed;
      }

      .checkbox-wrapper-13 input[type=checkbox]:hover:not(:checked):not(:disabled) {
        --bc: var(--border-hover);
      }

      .checkbox-wrapper-13 input[type=checkbox]:focus {
        box-shadow: 0 0 0 var(--focus);
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch) {
        width: 21px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):after {
        opacity: var(--o, 0);
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):checked {
        --o: 1;
      }

      .checkbox-wrapper-13 input[type=checkbox]+label {
        display: inline-block;
        vertical-align: middle;
        cursor: pointer;
        margin-left: 2px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch) {
        border-radius: 7px;
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):after {
        width: 5px;
        height: 9px;
        border: 2px solid var(--active-inner);
        border-top: 0;
        border-left: 0;
        left: 7px;
        top: 4px;
        transform: rotate(var(--r, 20deg));
      }

      .checkbox-wrapper-13 input[type=checkbox]:not(.switch):checked {
        --r: 43deg;
      }
    }

    .checkbox-wrapper-13 * {
      box-sizing: inherit;
    }

    .checkbox-wrapper-13 *:before,
    .checkbox-wrapper-13 *:after {
      box-sizing: inherit;
    }

    .d-inline {
      display: inline;
    }
  </style>

  <style>
    .box-main {
      border: 1px #ced4da solid;
      padding: 16px;
      background: #FFF;
      border-radius: 12px;
      margin-bottom: 10px;
    }

    .card {
      border-radius: 12px;
      border: #FFF 1px solid;
      padding: 10px;
    }

    /* input[readonly] {
      background-color: rgba(0, 0, 0, 0.05);
    } */

    /* .modal-dialog {
        min-height: 100vh;
    } */


    .bg-b {
      background: #ebf6f6;
    }

    .bg-o {
      background: #ffe8e7;
    }

    .sidebar,
    .img-logo {
      z-index: 100 !important;
    }

    .td-breakline {
      word-wrap: break-word;
    }

    .tr-search td {
      padding-top: 20px !important;
      padding-bottom: 20px !important;
    }

    .summary th {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary td {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary table tr:first-child th:first-child {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .summary table tr:first-child th:last-child {
      font-size: 12px !important;
      padding: 5px !important;
    }

    .dropdown-items {
      display: block;
      width: 100%;
      padding: 0px;
      clear: both;
      font-weight: 400;
      color: var(--bs-dropdown-link-color);
      text-align: inherit;
      text-decoration: none;
      white-space: nowrap;
      background-color: transparent;
      border: 0;
    }

    .w-100 {
      width: 100% !important;
    }

    .select2-container {
      width: 100% !important;
    }

    /* width */
    ::-webkit-scrollbar {
      width: 9px !important;
      height: 5px;
    }

    /* Track */
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
  </style>

  <style>
    .btn-copy {
      background: #c2b5071a;
      border-color: #c2b507;
    }

    .btn-copy:hover {
      background: #c2b507;
      border-color: #c2b507;
      color: #fff !important;
    }

    /* 
    .item-menu {
      padding: 14px 30px 14px 30px !important;
    } */

    .icon-side {
      margin-left: auto;
      /* Membuat ikon chevron otomatis menempel di sisi kanan */
      padding-right: 1rem;
      /* Jarak kanan ikon dari batas elemen */
    }
  </style>
</head>

<body>

  <nav class="sidebar offcanvas-md offcanvas-start <?php if ($_SESSION['minimize_sidebar']) {
                                                      echo 'active';
                                                    } ?>" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="d-flex justify-content-end m-3 d-block d-md-none">
      <button aria-label="Close" data-bs-dismiss="offcanvas" data-bs-target=".sidebar" class="btn p-0 border-0 fs-4" style="background:transparent!important;color:#fff!important">
        <i class="fa fa-close"></i>
      </button>
    </div>
    <?php
    $this->load->library('permission');
    
    $uri_1  = $this->uri->segment(1);
    $uri_2  = $this->uri->segment(2);
    $m = $this->input->get('m');
    $t = $this->input->get('t');
    $brand = $this->input->get('brand');
    
    $user_id = $_SESSION['user']['id'];
    
    $CI =& get_instance();
    $CI->load->library('permission');
    
    $can_view_dashboard = $CI->permission->check_permission($user_id, 'dashboard', 'view');
    ?>

    <div class="d-flex mb-3 img-logo" style="padding-left:15px;padding-top:20px;padding-bottom:20px;position:sticky!important;top:0;background:#FFF;z-index:100;
        z-index: 100;box-shadow: 4px 4px 4px #adb5bd1A;">
        <?php if ($can_view_dashboard): ?>
          <a href="<?= base_url() ?>">
        <?php endif; ?>
            <img src="<?= base_url() ?>assets/img/acneno-logo.png" alt="Logo" style="width:214px;padding-left:10px" />
        <?php if ($can_view_dashboard): ?>
          </a>
        <?php endif; ?>
    </div>
    <!-- <div class="d-flex justify-content-start mb-3">
        <h1 class="sidebar-title text-white">DNX1SCREEN</h1>
      </div> -->
    <?php
    $menu_marketing = $menu_overview = $menu_overview_ads = $menu_overview_kol = $menu_overview_influencer = '';
    $menu_ads = $menu_ads_tiktok = $menu_ads_meta = $menu_ads_shopee = $menu_ads_lazada = '';
    $menu_endorsement = $menu_influencer = $menu_influencer_dummy = $menu_calendar = $menu_payment_fee = $menu_codeboost = '';
    $menu_order_customer = $menu_toko = $menu_order_item = $menu_crm_mg = $menu_crm_pome = $menu_grup_wa = '';
    $menu_operasional = $menu_stock = $menu_product = $menu_product_3rd = $menu_discount = $menu_marketplace = $menu_shipping = '';
    $menu_hr_management = $menu_quest_level = $menu_position = $menu_benefit = $menu_quest = $menu_milestone = $menu_recruitment = $menu_roles = '';
    $menu_akun = $menu_user = $menu_profile = $menu_logout = '';
    
    // Get user permissions for menu visibility using module names from clear_and_replace_modules.sql
    // Access permission library through CodeIgniter instance
    $CI =& get_instance();
    $CI->load->library('permission');
    
    // System Management
    $can_view_report = $CI->permission->check_permission($user_id, 'report', 'view');
    $can_view_expense = $CI->permission->check_permission($user_id, 'expense', 'view');
    
    // Marketing Category - show if user has access to any marketing module
    $can_view_marketing = $CI->permission->check_permission($user_id, 'marketing', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_tiktok', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_meta', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_shopee', 'view') ||
                         $CI->permission->check_permission($user_id, 'ads_lazada', 'view') ||
                         $CI->permission->check_permission($user_id, 'influencer', 'view') ||
                         $CI->permission->check_permission($user_id, 'influencer_dummy', 'view') ||
                         $CI->permission->check_permission($user_id, 'endorse_campaign', 'view') ||
                         $CI->permission->check_permission($user_id, 'calendar', 'view') ||
                         $CI->permission->check_permission($user_id, 'payment', 'view') ||
                         $CI->permission->check_permission($user_id, 'codeboost', 'view');

    // Marketing Sub-modules
    $can_view_overview = $CI->permission->check_permission($user_id, 'marketing', 'view');
    $can_view_advertiser = $CI->permission->check_permission($user_id, 'ads_tiktok', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_meta', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_shopee', 'view') ||
                          $CI->permission->check_permission($user_id, 'ads_lazada', 'view');
    $can_view_endorsement = $CI->permission->check_permission($user_id, 'influencer', 'view') ||
                           $CI->permission->check_permission($user_id, 'influencer_dummy', 'view') ||
                           $CI->permission->check_permission($user_id, 'endorse_campaign', 'view') ||
                           $CI->permission->check_permission($user_id, 'calendar', 'view') ||
                           $CI->permission->check_permission($user_id, 'payment', 'view') ||
                           $CI->permission->check_permission($user_id, 'codeboost', 'view');
    
    // Order & Customer Management - show if user has access to any module
    $can_view_order_customer = $CI->permission->check_permission($user_id, 'marketplace_account', 'view') ||
                               $CI->permission->check_permission($user_id, 'transaction', 'view') ||
                               $CI->permission->check_permission($user_id, 'transaction_item', 'view') ||
                               $CI->permission->check_permission($user_id, 'crm_mg', 'view') ||
                               $CI->permission->check_permission($user_id, 'crm_pome', 'view') ||
                               $CI->permission->check_permission($user_id, 'group_wa', 'view');
    
    // Operations - show if user has access to any operations module
    $can_view_operasional = $CI->permission->check_permission($user_id, 'stock', 'view') ||
                           $CI->permission->check_permission($user_id, 'product', 'view');
    
    // HR Management - show if user has access to any HR module
    $can_view_hr_management = $CI->permission->check_permission($user_id, 'quest_level', 'view') ||
                              $CI->permission->check_permission($user_id, 'position', 'view') ||
                              $CI->permission->check_permission($user_id, 'benefit', 'view') ||
                              $CI->permission->check_permission($user_id, 'quest', 'view') ||
                              $CI->permission->check_permission($user_id, 'milestone', 'view') ||
                              $CI->permission->check_permission($user_id, 'recruitment', 'view');
    
    // Account Management - show if user has access to any account module  
    $can_view_akun = $CI->permission->check_permission($user_id, 'user', 'view') ||
                    $CI->permission->check_permission($user_id, 'profile', 'view') ||
                    $CI->permission->check_permission($user_id, 'roles', 'view') ||
                    $CI->permission->check_permission($user_id, 'modules', 'view');
    
    // Individual module permissions for detailed checks
    $modules_permissions = [
        // System Management
        'dashboard' => $CI->permission->check_permission($user_id, 'dashboard', 'view'),
        'report' => $CI->permission->check_permission($user_id, 'report', 'view'),
        'expense' => $CI->permission->check_permission($user_id, 'expense', 'view'),
        
        // Marketing
        'overview' => $CI->permission->check_permission($user_id, 'marketing', 'view'),
        'ads_tiktok' => $CI->permission->check_permission($user_id, 'ads_tiktok', 'view'),
        'ads_meta' => $CI->permission->check_permission($user_id, 'ads_meta', 'view'),
        'ads_shopee' => $CI->permission->check_permission($user_id, 'ads_shopee', 'view'),
        'ads_lazada' => $CI->permission->check_permission($user_id, 'ads_lazada', 'view'),
        'influencer' => $CI->permission->check_permission($user_id, 'influencer', 'view'),
        'influencer_dummy' => $CI->permission->check_permission($user_id, 'influencer_dummy', 'view'),
        'endorse_campaign' => $CI->permission->check_permission($user_id, 'endorse_campaign', 'view'),
        'calendar' => $CI->permission->check_permission($user_id, 'calendar', 'view'),
        'payment' => $CI->permission->check_permission($user_id, 'payment', 'view'),
        'codeboost' => $CI->permission->check_permission($user_id, 'codeboost', 'view'),
        
        // Order & Customer Management
        'marketplace_account' => $CI->permission->check_permission($user_id, 'marketplace_account', 'view'),
        'transaction' => $CI->permission->check_permission($user_id, 'transaction', 'view'),
        'transaction_item' => $CI->permission->check_permission($user_id, 'transaction_item', 'view'),
        'crm_mg' => $CI->permission->check_permission($user_id, 'crm_mg', 'view'),
        'crm_pome' => $CI->permission->check_permission($user_id, 'crm_pome', 'view'),
        'group_wa' => $CI->permission->check_permission($user_id, 'group_wa', 'view'),
        
        // Operations
        'stock' => $CI->permission->check_permission($user_id, 'stock', 'view'),
        'product' => $CI->permission->check_permission($user_id, 'product', 'view'),
        
        // HR Management
        'quest_level' => $CI->permission->check_permission($user_id, 'quest_level', 'view'),
        'position' => $CI->permission->check_permission($user_id, 'position', 'view'),
        'roles' => $CI->permission->check_permission($user_id, 'roles', 'view'),
        'benefit' => $CI->permission->check_permission($user_id, 'benefit', 'view'),
        'quest' => $CI->permission->check_permission($user_id, 'quest', 'view'),
        'milestone' => $CI->permission->check_permission($user_id, 'milestone', 'view'),
        'recruitment' => $CI->permission->check_permission($user_id, 'recruitment', 'view'),
        'modules' => $CI->permission->check_permission($user_id, 'modules', 'view'),
        
        // Account Management
        'user' => $CI->permission->check_permission($user_id, 'user', 'view'),
        'profile' => $CI->permission->check_permission($user_id, 'profile', 'view'),
        
        // Additional
        'scraper' => $CI->permission->check_permission($user_id, 'scraper', 'view')
    ];

    if ($uri_1 == 'dashboard') {
      $menu_dashboard = 'active';
    } else if ($uri_1 == 'report') {
      $menu_report = 'active';
    } else if ($uri_1 == 'label') {
      $menu_label = 'active';
    } else if ($uri_1 == 'group-wa') {
      $menu_group = 'active';
    } else if ($uri_1 == 'material') {
      $menu_material = 'active';
    } else if ($uri_1 == 'digger') {
      $menu_digger = 'active';
    } else if ($uri_1 == 'layer') {
      $menu_layer = 'active';
    } else if ($uri_1 == 'master-plan') {
      $menu_master_plan = 'active';
    } else if ($uri_1 == 'pricelist-kurs-idr') {
      $menu_kurs = 'active';
    } else if ($uri_1 == 'pricelist-product') {
      $menu_pricelist_product = 'active';
    } else if ($uri_1 == 'pricelist-ammonium-nitrate') {
      $menu_pricelist_ammonium = 'active';
    } else if ($uri_1 == 'rate') {
      $menu_rate = 'active';
    } else if ($uri_1 == 'equipment') {
      $menu_equipment = 'active';
    } else if ($uri_1 == 'site') {
      $menu_site = 'active';
    } else if ($uri_1 == 'customer') {
      $menu_customer = 'active';
    } else if ($uri_1 == 'customer-location') {
      $menu_customer_location = 'active';
    } else if ($uri_1 == 'loading-sheet') {
      $menu_loading_sheet = 'active';
    } else {
      $menu_ads = $menu_endorsement = $menu_marketing = '';
    }



    if ($uri_1 == 'overview') {
      $menu_marketing = 'show';
      $menu_overview = 'active';
    } else if ($uri_1 == 'ads') {
      $menu_marketing = 'show';
      $menu_ads = 'show';
      if ($m == 'tiktok') {
        $menu_ads_tiktok = 'active';
      } else if ($m == 'meta') {
        $menu_ads_meta = 'active';
      } else if ($m == 'shopee') {
        $menu_ads_shopee = 'active';
      } else if ($m == 'lazada') {
        $menu_ads_lazada = 'active';
      }
    } else if ($uri_1 == 'influencer') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_influencer = 'active';
    } else if ($uri_1 == 'influencer-dummy') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_influencer_dummy = 'active';
    } else if ($uri_1 == 'payment' || $uri_1 == 'review-endorse') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_payment_fee = 'active';
    } else if ($uri_1 == 'codeboost') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_codeboost = 'active';
    } else if ($uri_1 == 'calendar') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_calendar = 'active';
    } else if ($uri_1 == 'endorse' || $uri_1 == 'endorse-campaign') {
      $menu_marketing = 'show';
      $menu_endorsement = 'show';
      $menu_endorse_campaign = 'active';
    } else if ($uri_1 == 'transaction' || $uri_1 == 'transaction-item' || $uri_1 == 'crm' || $uri_1 == 'group-wa') {
      $menu_order_customer = 'show';
      if ($uri_1 == 'transaction') {
        $menu_order = 'active';
      } else if ($uri_1 == 'transaction-item') {
        $menu_order_item = 'active';
      } else if ($uri_1 == 'crm') {
        if ($brand == 'POME') {
          $menu_crm_pome = 'active';
        } else if ($brand == 'MG') {
          $menu_crm_mg = 'active';
        }
      } elseif ($uri_1 == 'group-wa') {
        $menu_grup_wa = 'active';
      }
    } else if ($uri_1 == 'stock' || $uri_1 == 'product' ||  $uri_1 == 'marketplace' || $uri_1 == 'marketplace-account' || $uri_1 == 'shipping' || $uri_1 == 'channel') {
      $menu_operasional = 'show';
      if ($uri_1 == 'stock') {
        $menu_stock = 'active';
      } else if ($uri_1 == 'product' || $uri_1 == 'marketplace' || $uri_1 == 'shipping' || $uri_1 == 'marketplace-account') {
        $menu_product = 'active';
      }
    } else if ($uri_1 == 'quest_level' || $uri_1 == 'position' || $uri_1 == 'benefit' || $uri_1 == 'quest' || $uri_1 == 'milestone' || $uri_1 == 'recruitment') {
      $menu_hr_management = 'show';
      if ($uri_1 == 'quest_level') {
        $menu_quest_level = 'active';
      } else if ($uri_1 == 'position') {
        $menu_position = 'active';
      } else if ($uri_1 == 'benefit') {
        $menu_benefit = 'active';
      } else if ($uri_1 == 'quest') {
        $menu_quest = 'active';
      } else if ($uri_1 == 'milestone') {
        $menu_milestone = 'active'; 
      } else if ($uri_1 == 'recruitment') {
        $menu_recruitment = 'active';
      }
    } else if ($uri_1 == 'user' || $uri_1 == 'profile' || $uri_1 == 'roles' || $uri_1 == 'modules') {
      $menu_akun = 'show';
      if ($uri_1 == 'user') {
        $menu_user = 'active';
      } else if ($uri_1 == 'profile') {
        $menu_profile = 'active';
      } else if ($uri_1 == 'roles') {
        $menu_roles = 'active';
      }
    } else {
      $menu_marketing = $menu_overview = '';
      $menu_ads = $menu_ads_tiktok = $menu_ads_meta = $menu_ads_shopee = $menu_ads_lazada = '';
      $menu_endorsement = $menu_influencer = $menu_influencer_dummy = $menu_calendar = $menu_payment_fee = $menu_codeboost = '';
      $menu_order_customer = $menu_toko = $menu_order_item = $menu_crm_mg = $menu_crm_pome = $menu_grup_wa = '';
      $menu_operasional = $menu_stock = $menu_product = '';
      $menu_hr_management = $menu_quest_level = $menu_position = $menu_benefit = $menu_quest = $menu_milestone = $menu_recruitment = $menu_roles = '';
      $menu_akun = $menu_user = $menu_profile = $menu_logout = '';
    }
    ?>


    <div class="pt-0 d-flex flex-column gap-5">
      <div class="menu p-0">
        <?php if ($modules_permissions['dashboard']): ?>
          <a href="<?= base_url() ?>dashboard" class="item-menu <?= $menu_dashboard ?>">
            <i class="icon bi bi-house"></i>
            DASHBOARD
          </a>
        <?php endif; ?>
        
        <?php if ($modules_permissions['report']): ?>
          <a href="<?= base_url() ?>report" class="item-menu <?= $menu_report ?>">
            <i class="icon bi bi-graph-up-arrow"></i>
            REPORT
          </a>
        <?php endif; ?>

        <?php if ($modules_permissions['expense']): ?>
          <a href="<?= base_url() ?>expense" class="item-menu <?= $menu_expense ?>">
            <i class="icon bi bi-credit-card"></i>
            PENGELUARAN
          </a>
        <?php endif; ?>

        <?php if ($can_view_marketing): ?>
          <a class="item-menu fw-bold <?= $menu_marketing ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-report"
            role="button"
            aria-expanded="<?= $menu_marketing ? 'true' : 'false'; ?>"
            aria-controls="submenu-report">
            MARKETING
            <i class="bi <?= $menu_marketing ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_marketing ? 'show' : '' ?>" id="submenu-report">
            <?php if ($modules_permissions['overview']): ?>
              <a href="<?= base_url() ?>overview" class="ms-2 item-menu <?= $menu_overview ?>">
                OVERVIEW
              </a>
            <?php endif; ?>

            <?php if ($can_view_advertiser): ?>
              <a class="item-menu <?= $menu_ads ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-2"
                data-bs-toggle="collapse"
                href="#submenu-advertiser"
                role="button"
                aria-expanded="<?= $menu_ads ? 'true' : 'false'; ?>"
                aria-controls="submenu-advertiser">
                ADVERTISER
                <i class="bi <?= $menu_ads ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>
              <div class="collapse <?= $menu_ads ? 'show' : '' ?>" id="submenu-advertiser">
                <?php if ($modules_permissions['ads_tiktok']): ?>
                  <a href="<?= base_url() ?>ads?m=tiktok" class="ms-2 item-menu <?= $menu_ads_tiktok ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/3.png" alt="TikTok" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    TIKTOK
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_meta']): ?>
                  <a href="<?= base_url() ?>ads?m=meta" class="ms-2 item-menu <?= $menu_ads_meta ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/5.png" alt="Meta" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    META
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_shopee']): ?>
                  <a href="<?= base_url() ?>ads?m=shopee" class="ms-2 item-menu <?= $menu_ads_shopee ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/1.png" alt="Shopee" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    SHOPEE
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['ads_lazada']): ?>
                  <a href="<?= base_url() ?>ads?m=lazada" class="ms-2 item-menu <?= $menu_ads_lazada ?>">
                    <i class="icon">
                      <img src="<?= base_url() ?>assets/img/marketplace/2.png" alt="Lazada" class="rounded-circle border" style="width: 35px; height: 35px;">
                    </i>
                    LAZADA
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($can_view_endorsement): ?>
              <a class="item-menu <?= $menu_endorsement ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between ms-2"
                data-bs-toggle="collapse"
                href="#submenu-endorse"
                role="button"
                aria-expanded="<?= $menu_endorsement ? 'true' : 'false'; ?>"
                aria-controls="submenu-endorse">
                ENDORSEMENT
                <i class="bi <?= $menu_endorsement ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
              </a>
              <div class="collapse <?= $menu_endorsement ? 'show' : '' ?>" id="submenu-endorse">
                <?php if ($modules_permissions['influencer']): ?>
                  <a href="<?= base_url() ?>influencer" class="ms-3 item-menu <?= $menu_influencer ?>">
                    <i class="icon bi bi-person-bounding-box"></i>
                    INFLUENCER
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['influencer_dummy']): ?>
                  <a href="<?= base_url() ?>influencer-dummy" class="ms-3 item-menu <?= $menu_influencer_dummy ?>">
                    <i class="icon bi bi-person-lines-fill"></i>
                    INFLUENCER LISTING
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['endorse_campaign']): ?>
                  <a href="<?= base_url() ?>endorse-campaign" class="ms-3 item-menu <?= $menu_endorse_campaign ?>">
                    <i class="icon bi bi-person-video2"></i>
                    ENDORSE CAMPAIGN
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['calendar']): ?>
                  <a href="<?= base_url() ?>calendar?group_by[]=rencana_at&group_by[]=posting_at" class="ms-3 item-menu <?= $menu_calendar ?>">
                    <i class="icon bi bi-calendar-week"></i>
                    ENDORSE CALENDAR
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['payment']): ?>
                  <a href="<?= base_url() ?>payment" class="ms-3 item-menu <?= $menu_payment_fee ?>">
                    <i class="icon bi bi-wallet2"></i>
                    PAYMENT & REVIEW
                  </a>
                <?php endif; ?>
                <?php if ($modules_permissions['codeboost']): ?>
                  <a href="<?= base_url() ?>codeboost" class="ms-3 item-menu <?= $menu_codeboost ?>">
                    <i class="icon bi bi-box-arrow-in-up"></i>
                    CODEBOOST
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_order_customer): ?>
          <a class="item-menu fw-bold <?= $menu_order_customer ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-order-customer"
            role="button"
            aria-expanded="<?= $menu_order_customer ? 'true' : 'false'; ?>"
            aria-controls="submenu-order-customer">
            ORDER & CUSTOMER
            <i class="bi <?= $menu_order_customer ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_order_customer ? 'show' : '' ?>" id="submenu-order-customer">
            <!-- <?php if ($modules_permissions['marketplace_account']): ?>
              <a href="<?= base_url() ?>marketplace-account" class="ms-3 item-menu <?= $menu_toko ?>">
                <i class="icon bi bi-houses"></i>
                TOKO
              </a>
            <?php endif; ?> -->

            <?php if ($modules_permissions['transaction']): ?>
              <a href="<?= base_url() ?>transaction" class="ms-3 item-menu <?= $menu_order ?>">
                <i class="icon bi bi-handbag"></i>
                ORDER
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['transaction_item']): ?>
              <a href="<?= base_url() ?>transaction-item" class="ms-3 item-menu <?= $menu_order_item ?>">
                <i class="icon bi bi-arrow-left-right"></i>
                ORDER ITEM
              </a>
            <?php endif; ?>

            <?php if ($modules_permissions['crm_mg']): ?>
              <a href="<?= base_url() ?>crm?brand=MG" class="ms-3 item-menu <?= $menu_crm_mg ?>">
                <i class="icon bi bi-person-heart"></i>
                CRM MG
              </a>
            <?php endif; ?>
            
            <?php if ($modules_permissions['crm_pome']): ?>
              <a href="<?= base_url() ?>crm?brand=POME" class="ms-3 item-menu <?= $menu_crm_pome ?>">
                <i class="icon bi bi-person-heart"></i>
                CRM POME
              </a>
            <?php endif; ?>
            
            <?php if ($modules_permissions['group_wa']): ?>
              <a href="<?= base_url() ?>group-wa" class="ms-3 item-menu <?= $menu_group ?>">
                <i class="icon bi bi-whatsapp"></i>
                GRUP WA
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($can_view_operasional): ?>
          <a class="item-menu fw-bold <?= $menu_operasional ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-operasional"
            role="button"
            aria-expanded="<?= $menu_operasional ? 'true' : 'false'; ?>"
            aria-controls="submenu-operasional">
            OPERASIONAL
            <i class="bi <?= $menu_operasional ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_operasional ? 'show' : '' ?>" id="submenu-operasional">
            <?php if ($modules_permissions['stock']): ?>
              <a href="<?= base_url() ?>stock" class="ms-3 item-menu <?= $menu_stock ?>">
                <i class="icon bi bi-arrow-left-right"></i>
                STOK
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['product'] || $modules_permissions['marketplace-account']): ?>
              <a href="<?= base_url() ?>product" class="ms-3 item-menu <?= $menu_product ?>">
                <i class="icon bi bi-box"></i>
                KONFIGURASI
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- HR MANAGEMENT - Not being used yet
        <?php if ($can_view_hr_management): ?>
          <a class="item-menu fw-bold <?= $menu_hr_management ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-hr-management"
            role="button"
            aria-expanded="<?= $menu_hr_management ? 'true' : 'false'; ?>"
            aria-controls="submenu-hr-management">
            HR MANAGEMENT
            <i class="bi <?= $menu_hr_management ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_hr_management ? 'show' : '' ?>" id="submenu-hr-management">
            <?php if ($modules_permissions['recruitment']): ?>
              <a href="<?= base_url() ?>recruitment" class="ms-3 item-menu <?= $menu_recruitment ?>">
                <i class="icon bi bi-person-fill-up"></i>
                RECRUITMENT
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['quest_level']): ?>
              <a href="<?= base_url() ?>quest_level" class="ms-3 item-menu <?= $menu_quest_level ?>">
                <i class="icon bi bi-award"></i>
                QUEST LEVELS
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['position']): ?>
              <a href="<?= base_url() ?>position" class="ms-3 item-menu <?= $menu_position ?>">
                <i class="icon bi bi-briefcase"></i>
                POSITIONS
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['benefit']): ?>
              <a href="<?= base_url() ?>benefit" class="ms-3 item-menu <?= $menu_benefit ?>">
                <i class="icon bi bi-gift"></i>
                BENEFITS
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['quest']): ?>
              <a href="<?= base_url() ?>quest" class="ms-3 item-menu <?= $menu_quest ?>">
                <i class="icon bi bi-trophy"></i>
                QUEST MANAGEMENT
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['milestone']): ?>
              <a href="<?= base_url() ?>milestone" class="ms-3 item-menu <?= $menu_milestone ?>">
                <i class="icon bi bi-trophy-fill"></i>
                MILESTONE & LEADERBOARD
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        -->

        <?php if ($can_view_akun): ?>
          <a class="item-menu fw-bold <?= $menu_akun ? '' : 'collapsed' ?> d-flex align-items-center justify-content-between"
            data-bs-toggle="collapse"
            href="#submenu-akun"
            role="button"
            aria-expanded="<?= $menu_akun ? 'true' : 'false'; ?>"
            aria-controls="submenu-akun">
            AKUN
            <i class="bi <?= $menu_akun ? 'bi-chevron-up' : 'bi-chevron-down' ?> icon-side ms-auto"></i>
          </a>

          <div class="collapse <?= $menu_akun ? 'show' : '' ?>" id="submenu-akun">
            <?php if ($modules_permissions['user']): ?>
              <a href="<?= base_url() ?>user" class="ms-3 item-menu <?= $menu_user ?>">
                <i class="icon bi bi-person-vcard"></i>
                USER
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['roles']): ?>
              <a href="<?= base_url() ?>roles" class="ms-3 item-menu <?= $menu_roles ?>">
                <i class="icon bi bi-shield-check"></i>
                ROLE MANAGEMENT
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['modules']): ?>
              <a href="<?= base_url() ?>modules" class="ms-3 item-menu <?= $uri_1 == 'modules' ? 'active' : '' ?>">
                <i class="icon bi bi-shield-lock"></i>
                MODULES & PERMISSIONS
              </a>
            <?php endif; ?>
            <?php if ($modules_permissions['profile']): ?>
              <a href="<?= base_url() ?>profile" class="ms-3 item-menu <?= $menu_profile ?>">
                <i class="icon bi bi-person-circle"></i>
                AKUN SAYA
              </a>
            <?php endif; ?>
            <a href="<?= base_url() ?>auth/logout-process" class="ms-3 item-menu <?= $menu_logout ?>">
              <i class="icon bi bi-door-open"></i>
              KELUAR
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <?php
  $style_1 = '';
  $style_2 = '';
  $style_3 = '';
  ?>


  <!-- Main Content -->
  <main class="content div-dashboard <?php if ($_SESSION['minimize_sidebar']) {
                                        echo 'active';
                                      } ?>">

    <nav class="navbar navbar-expand-lg" style="top: 0;
        position: sticky;
        background: #FFF;
        padding: 10px 30px;
        z-index: 100;box-shadow: 4px 4px 4px #adb5bd1A;
    ">
      <div class="container-fluid" style="padding-right: 0px; padding-left: 0px;">
          <div class="avatar-icon">
              <button class="sidebarCollapseDefault btn p-0 border-0 d-none d-md-block mt-0 mb-0" aria-label="Hamburger Button" style="padding-top:0px!important;">
                  <i class="mdi menu-sidebar mdi-menu"></i>
              </button>
              <button data-bs-toggle="offcanvas" data-bs-target=".sidebar" aria-controls="sidebar" aria-label="Hamburger Button" class="sidebarCollapseMobile btn p-0 border-0 d-block d-md-none" style="padding-top:0px!important;">
                  <i class="mdi menu-sidebar mdi-menu"></i>
              </button>
          </div>
          
          <div class="d-flex align-items-center justify-content-end gap-4">
              <!-- Notification Bell -->
              <div class="notification-container" style="position: relative; margin-right: 10px;">
                  <button class="p-0" onclick="toggleNotifications()" style="position: relative; background-color: transparent; border: none;">
                      <i class="bi bi-bell" style="font-size: 20px; color: #5a7dbaff;"></i>
                      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none; font-size: 10px; padding: 3px 5px;">
                          0
                      </span>
                  </button>
                  
                  <!-- Dropdown Notifikasi -->
                  <div class="dropdown-menu p-0" id="notificationDropdown" style="display: none; width: 320px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border-radius: 12px; overflow: hidden; right: 1px;">
                      <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #f8f9fa; border-bottom: 1px solid #eee;">
                          <h6 class="mb-0 fw-bold" style="font-size: 15px;">Notifikasi</h6>
                          <a href="javascript:void(0)" onclick="markAllRead()" class="text-primary" style="font-size: 13px; text-decoration: none;">Tandai Semua Dibaca</a>
                      </div>
                      <div class="notification-list" id="notificationList" style="max-height: 400px; overflow-y: auto;">
                          <!-- Notifikasi akan dimuat di sini -->
                          <div class="text-center py-4 text-muted">
                              <i class="bi bi-bell-slash" style="font-size: 24px;"></i>
                              <p class="mt-2 mb-0">Tidak ada notifikasi</p>
                          </div>
                      </div>
                      <div class="text-center p-2" style="background-color: #f8f9fa; border-top: 1px solid #eee;">
                          <a href="<?= base_url('notifications') ?>" class="text-primary" style="font-size: 13px; text-decoration: none;">Lihat Semua Notifikasi</a>
                      </div>
                  </div>
              </div>
                                        
              <!-- Profile Dropdown -->
              <div class="dropdown">
                  <button class="btn p-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none;">
                      <?php
                      $img = $_SESSION['user']['img'];
                      if ($img == "") {
                          $img = base_url() . '/assets/img/user/default.png';
                      } else {
                          $img = base_url() . '/assets/img/user/' . $img . '?token=' . DATE("Ymdhis", strtotime($_SESSION['user']['updated_at']));
                      }
                      ?>
                      <img src="<?= $img ?>" class="avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer;">
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown" style="border: none; border-radius: 12px; padding: 8px; min-width: 200px;">
                      <!-- User Info Header -->
                      <li class="dropdown-header px-3 py-2" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 8px;">
                          <div class="d-flex align-items-center">
                              <img src="<?= $img ?>" class="me-2" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                              <div>
                                  <div class="fw-bold text-dark" style="font-size: 14px;"><?= $_SESSION['user']['full_name'] ?></div>
                                  <small class="text-muted" style="font-size: 12px;"><?= $_SESSION['user']['role_text'] ?></small>
                              </div>
                          </div>
                      </li>
                      
                      <!-- Profile Link -->
                      <li>
                          <a class="dropdown-item d-flex align-items-center py-2 px-3" href="<?= base_url() ?>profile" style="border-radius: 8px; transition: all 0.2s;">
                              <i class="bi bi-person-circle me-2 text-primary" style="font-size: 16px;"></i>
                              <span>My Profile</span>
                          </a>
                      </li>
                      
                      <!-- Divider -->
                      <li><hr class="dropdown-divider my-2"></li>
                      
                      <!-- Logout Link -->
                      <li>
                          <a class="dropdown-item d-flex align-items-center py-2 px-3 text-danger" href="javascript:void(0)" style="border-radius: 8px; transition: all 0.2s;" 
                            onclick="showLogoutConfirmation();">
                              <i class="bi bi-box-arrow-right me-2" style="font-size: 16px;"></i>
                              <span>Logout</span>
                          </a>
                      </li>
                  </ul>
              </div>
          </div>
      </div>
  </nav>


    <div class="content-body">

      <div class="w-100 pt-0 pb-5">
        <?= $content ?>
      </div>
    </div>
    <!-- <footer>
        <div class="row">
          <div class="col-lg-6 text-center text-lg-start">
            <p class="mb-0">Copyright <a class="a-green" href="https://karyastudio.com" target="_blank">Karya Studio Teknologi Digital</a> &#169; 2022</p>
          </div>
          <div class="col-lg-6 text-center text-lg-end">
            <p class="mb-0">PT Kargo Maritim Indonesia V.1.0.1</p>
          </div>
        </div>
      </footer> -->
  </main>
  <style>
  .notification-container {
      position: relative;
      margin-right: 15px;
  }

  .notification-badge {
      position: absolute;
      top: 0;
      right: 0;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      transform: translate(25%, -25%);
  }

  .notification-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      width: 320px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 1000;
      max-height: 400px;
      overflow-y: auto;
      display: none;
  }

  .notification-header {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #f8f9fa;
  }

  .notification-header h6 {
      margin: 0;
      font-weight: 600;
      font-size: 15px;
      color: #212529;
  }

  .mark-all-read {
      font-size: 13px;
      color: #0d6efd;
      text-decoration: none;
      cursor: pointer;
  }

  .mark-all-read:hover {
      text-decoration: underline;
  }

  .notification-list {
      max-height: 400px;
      overflow-y: auto;
  }

  .notification-item {
      padding: 12px 16px;
      border-bottom: 1px solid #f8f9fa;
      cursor: pointer;
      transition: background-color 0.2s;
  }

  .notification-item:hover {
      background-color: #f8f9fa;
  }

  .notification-item.unread {
      background-color: #f0f7ff;
      border-left: 3px solid #0d6efd;
  }

  .notification-content {
      display: flex;
      gap: 12px;
  }

  .notification-icon {
      font-size: 18px;
      margin-top: 2px;
  }

  .notification-icon.info {
      color: #0dcaf0;
  }

  .notification-icon.success {
      color: #198754;
  }

  .notification-icon.warning {
      color: #ffc107;
  }

  .notification-icon.danger {
      color: #dc3545;
  }

  .notification-details {
      flex: 1;
  }

  .notification-title {
      font-weight: 600;
      font-size: 14px;
      color: #212529;
      margin-bottom: 4px;
  }

  .notification-message {
      font-size: 13px;
      color: #6c757d;
      margin-bottom: 4px;
      line-height: 1.4;
  }

  .notification-time {
      font-size: 12px;
      color: #adb5bd;
  }

  .notification-footer {
      padding: 10px 16px;
      text-align: center;
      border-top: 1px solid #eee;
      background-color: #f8f9fa;
  }

  .view-all {
      color: #0d6efd;
      text-decoration: none;
      font-size: 13px;
      cursor: pointer;
  }

  .view-all:hover {
      text-decoration: underline;
  }

  .no-notifications {
      padding: 30px 15px;
      text-align: center;
      color: #adb5bd;
  }

  .no-notifications i {
      font-size: 24px;
      margin-bottom: 8px;
  }

  .no-notifications p {
      margin: 0;
      font-size: 14px;
  }
  </style>

  <style>
    .select2-container--open .select2-dropdown {
      z-index: 10000 !important;
    }

    .h-100 {
      height: 100%;
    }

    .divIcon {
      width: 55px;
      height: 55px;
      border-radius: 10px;
      object-fit: cover;
    }
  </style>
  <script>
    $('.select2').select2();
  </script>


  <script>
    let notificationDropdownOpen = false;

    function toggleNotifications() {
        const dropdown = $('#notificationDropdown');
        
        if (notificationDropdownOpen) {
            dropdown.hide();
            notificationDropdownOpen = false;
        } else {
            dropdown.show();
            notificationDropdownOpen = true;
            loadNotifications();
        }
    }

    $(document).on('click', function(event) {
        const container = $('.notification-container');
        if (!container.is(event.target) && !container.has(event.target).length && notificationDropdownOpen) {
            $('#notificationDropdown').hide();
            notificationDropdownOpen = false;
        }
    });

    var originalTitle = document.title;

    function loadNotifications() {
      $.ajax({
          url: '<?= base_url("notifications/get_notifications") ?>',
          method: 'GET',
          dataType: 'json',
          success: function(data) {
              if (data.error === 'session_expired') {
                  window.location.href = '<?= base_url("auth/login") ?>';
                  return;
              }

              displayNotifications(data.notifications);
              updateNotificationBadge(data.unread_count);

              if (data.unread_count > 0) {
                  document.title = '(' + data.unread_count + ') ' + originalTitle;
              } else {
                  document.title = originalTitle;
              }
          },
          error: function(xhr, status, error) {
              console.error('Error loading notifications:', error);
          }
      });
    }


    async function handleNotificationClick(notificationId, title) {
        try {
            await markRead(notificationId);
            if (title.includes('Review')) {
                window.location.href = '<?= base_url("review-endorse?keyword_category=SPV&keyword=") ?>' + 
                                    encodeURIComponent('<?= $_SESSION['user']['full_name'] ?>');
            } else if (title.includes('Pengajuan')) {
                window.location.href = '<?= base_url("payment") ?>';
            }
        } catch (error) {
            console.error('Error in notification process:', error);
        }
    }


    function displayNotifications(notifications) {
      const listContainer = $('#notificationList');
      
      if (notifications.length === 0) {
          listContainer.html(`
              <div class="no-notifications">
                  <i class="bi bi-bell-slash"></i>
                  <p>Tidak ada notifikasi</p>
              </div>
          `);
          return;
      }
      
      let html = '';
      notifications.forEach(notification => {
          const unreadClass = notification.is_read == '0' ? 'unread' : '';
          const timeAgo = formatTimeAgo(notification.created_at);
          
          let iconClass = 'bi-info-circle info';
          if (notification.type === 'success') iconClass = 'bi-check-circle success';
          if (notification.type === 'warning') iconClass = 'bi-exclamation-triangle warning';
          if (notification.type === 'danger') iconClass = 'bi-x-circle danger';
          
          const escapedTitle = notification.title.replace(/'/g, "\\'");
          
          html += `
              <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${notification.id}, '${escapedTitle}')">
                  <div class="notification-content">
                      <div class="notification-icon ${iconClass}"></div>
                      <div class="notification-details">
                          <div class="notification-title">${notification.title}</div>
                          <div class="notification-message">${notification.message}</div>
                          <div class="notification-time">${timeAgo}</div>
                      </div>
                  </div>
              </div>
          `;
      });
      
      listContainer.html(html);
    }

    function updateNotificationBadge(count) {
        const badge = $('#notificationBadge');
        if (count > 0) {
            badge.text(count > 99 ? '99+' : count);
            badge.css('display', 'flex');
        } else {
            badge.hide();
        }
    }

    async function markRead(notificationId) {
      try {
          const response = await $.ajax({
              url: '<?= base_url("notifications/mark_read") ?>',
              method: 'POST',
              dataType: 'json',
              contentType: 'application/json',
              data: JSON.stringify({
                  notification_id: notificationId
              })
          });
          
          if (response.success) {
              loadNotifications();
          }
          return response;
      } catch (error) {
          console.error('Error marking notification as read:', error);
          throw error;
      }
    }

    function markAllRead() {
        $.ajax({
            url: '<?= base_url("notifications/mark_all_read") ?>',
            method: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    loadNotifications();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking all notifications as read:', error);
            }
        });
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'Baru saja';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} menit yang lalu`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} jam yang lalu`;
        } else if (diffInSeconds < 2592000) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} hari yang lalu`;
        } else {
            return date.toLocaleDateString('id-ID');
        }
    }

    $(document).ready(function() {
        $.ajax({
            url: '<?= base_url("notifications/get_unread_count") ?>',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                updateNotificationBadge(data.count);
            },
            error: function(xhr, status, error) {
                console.error('Error loading notification count:', error);
            }
        });
    });

    setInterval(function() {
      $.ajax({
          url: '<?= base_url("notifications/get_unread_count") ?>',
          method: 'GET',
          dataType: 'json',
          success: function(data) {
              updateNotificationBadge(data.count);

              if (data.count > 0) {
                  document.title = '(' + data.count + ') ' + originalTitle;
              } else {
                  document.title = originalTitle;
              }
          },
          error: function(xhr, status, error) {
              console.error('Error auto-refreshing notification count:', error);
          }
      });
    }, 30000);
  </script>
  <script>
    $(document).ready(function() {
      $('.sidebarCollapseDefault').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('.content').toggleClass('active');
        $.ajax({
          dataType: "json",
          url: '<?= base_url() ?>ajax/minimize-sidebar',
          success: function(html) {}
        });
      });
    });

    function func_pass_1() {
      var x = document.getElementById("password_1");
      var show_eye_1 = document.getElementById("show_eye_1");
      var hide_eye_1 = document.getElementById("hide_eye_1");
      hide_eye_1.classList.remove("d-none");
      if (x.type === "password") {
        x.type = "text";
        show_eye_1.style.display = "none";
        hide_eye_1.style.display = "block";
      } else {
        x.type = "password";
        show_eye_1.style.display = "block";
        hide_eye_1.style.display = "none";
      }
    }

    // document.addEventListener("DOMContentLoaded", function() {
    //   // Prevent collapse from closing when clicking inside
    //   document.querySelectorAll('.collapse').forEach(function(collapse) {
    //     collapse.addEventListener('click', function(event) {
    //       event.stopPropagation();
    //     });
    //   });

    //   // Ensure toggle remains open when active
    //   const toggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    //   toggles.forEach(function(toggle) {
    //     toggle.addEventListener('click', function(event) {
    //       const target = document.querySelector(toggle.getAttribute('href'));
    //       if (target.classList.contains('show')) {
    //         event.preventDefault(); // Prevent collapsing the already open element
    //       }
    //     });
    //   });
    // });

    document.addEventListener("DOMContentLoaded", function() {
      // Select all elements with `data-bs-toggle="collapse"`
      const toggles = document.querySelectorAll('[data-bs-toggle="collapse"]');

      toggles.forEach(function(toggle) {
        // Get the target collapse element
        const targetSelector = toggle.getAttribute('href');
        const target = document.querySelector(targetSelector);

        if (target) {
          // Add event listener for when the collapse is shown
          target.addEventListener('shown.bs.collapse', function() {
            const icon = toggle.querySelector('.icon-side');
            if (icon) {
              icon.classList.remove('bi-chevron-down');
              icon.classList.add('bi-chevron-up');
            }
          });

          // Add event listener for when the collapse is hidden
          target.addEventListener('hidden.bs.collapse', function() {
            const icon = toggle.querySelector('.icon-side');
            if (icon) {
              icon.classList.remove('bi-chevron-up');
              icon.classList.add('bi-chevron-down');
            }
          });
        }
      });
    });


    function func_pass_2() {
      var y = document.getElementById("password_2");
      var show_eye_2 = document.getElementById("show_eye_2");
      var hide_eye_2 = document.getElementById("hide_eye_2");
      hide_eye_2.classList.remove("d-none");
      if (y.type === "password") {
        y.type = "text";
        show_eye_2.style.display = "none";
        hide_eye_2.style.display = "block";
      } else {
        y.type = "password";
        show_eye_2.style.display = "block";
        hide_eye_2.style.display = "none";
      }
    }

    function func_pass_3() {
      var z = document.getElementById("password_3");
      var show_eye_3 = document.getElementById("show_eye_3");
      var hide_eye_3 = document.getElementById("hide_eye_3");
      hide_eye_3.classList.remove("d-none");
      if (z.type === "password") {
        z.type = "text";
        show_eye_3.style.display = "none";
        hide_eye_3.style.display = "block";
      } else {
        z.type = "password";
        show_eye_3.style.display = "block";
        hide_eye_3.style.display = "none";
      }
    }

    $(document).ready(function() {
      $('#datatable-full').DataTable({
        paging: false, // Disables pagination, showing all rows
        searching: true, // Disables the search box
        ordering: true, // Disables column sorting
        info: false // Hides the table information summary
      });
    });


    // select();

    // function select() {
    //   $(document).ready(function() {
    //     $('.select').select2();
    //   });
    // }

    select2_product();

    function select2_product() {
      $(document).ready(function() {
        $('#select2-product').select2();
      });
    }

    select2();

    function select2() {
      $(document).ready(function() {
        $('#select2').select2();
      });
    }


    function select3() {
      $(document).ready(function() {
        $('#select3').select2();
      });
    }

    // select3();

    // function select3() {
    //   $(document).ready(function() {
    //     $('.form-table-select2').select2();
    //   });
    // }



    select_5();

    function select_5() {
      $(document).ready(function() {
        $('.select-5').select2();
      });
    }


    function copy(id) {
      // Get the text field
      var copyText = document.getElementById("box-order-id-" + id);

      // Select the text field
      copyText.select();
      copyText.setSelectionRange(0, 99999); // For mobile devices

      // Copy the text inside the text field
      navigator.clipboard.writeText(copyText.value);

      // Alert the copied text
      // alert("Copied the text: " + );
      $(document).ready(function() {
        $.toast({
          heading: "Informasi",
          text: "Kode order <b>" + copyText.value + "</b> berhasil disalin!",
          showHideTransition: "slide",
          icon: "success",
          position: "top-right",
          loaderBg: "#def7f0",
          hideAfter: 2500,
        });
      });
    }

    $(document).ready(function() {
      $(".checkAll").click(function() {
        $(".checkItem").prop('checked', $(this).prop('checked'));
        get_id();
      });
    });
  </script>
  <script>
    var refreshTime = 180000; // every 3 minutes in milliseconds
    $(document).ready(function() {
      setInterval(sessionCheck, refreshTime);
    });

    function sessionCheck() {
      $.ajax({
        cache: false,
        type: "GET",
        url: "<?= base_url() ?>ajax/refresh-token",
        success: function(data) {
          console.log("Refresh token");
        }
      });
    }
  </script>
  
  <!-- Logout Confirmation with SweetAlert2 -->
  <script>
    function showLogoutConfirmation() {
      Swal.fire({
        title: 'Logout Confirmation',
        text: 'Are you sure you want to logout from your account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Logout',
        cancelButtonText: '<i class="bi bi-x-circle me-1"></i> Cancel',
        reverseButtons: true,
        customClass: {
          popup: 'logout-swal-popup',
          title: 'logout-swal-title',
          content: 'logout-swal-content',
          confirmButton: 'logout-swal-confirm',
          cancelButton: 'logout-swal-cancel'
        },
        backdrop: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        focusConfirm: false,
        showClass: {
          popup: 'animate__animated animate__fadeInDown animate__faster'
        },
        hideClass: {
          popup: 'animate__animated animate__fadeOutUp animate__faster'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Logging out...',
            text: 'Please wait while we sign you out securely.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Redirect to logout after short delay for better UX
          setTimeout(() => {
            window.location.href = '<?= base_url() ?>auth/logout_process';
          }, 1000);
        }
      });
    }
  </script>
  
  <!-- Profile Dropdown Styling -->
  <style>
    /* Profile dropdown enhanced styling */
    .dropdown-menu {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
      border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    
    .dropdown-item:hover {
      background-color: #f8f9fa !important;
      color: #495057 !important;
      transform: translateX(2px);
    }
    
    .dropdown-item.text-danger:hover {
      background-color: #fee !important;
      color: #dc3545 !important;
    }
    
    .avatar:hover {
      transform: scale(1.05);
      transition: transform 0.2s ease;
    }
    
    /* Animation for dropdown */
    .dropdown-menu.show {
      animation: dropdownFadeIn 0.2s ease-out;
    }
    
    @keyframes dropdownFadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Profile button focus state */
    #profileDropdown:focus {
      box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25) !important;
      border-radius: 50% !important;
    }
    
    /* Custom SweetAlert2 Logout Styling */
    .logout-swal-popup {
      border-radius: 16px !important;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
    }
    
    .logout-swal-title {
      color: #495057 !important;
      font-weight: 600 !important;
      font-size: 1.25rem !important;
    }
    
    .logout-swal-content {
      color: #6c757d !important;
      font-size: 0.95rem !important;
    }
    
    .logout-swal-confirm {
      border-radius: 8px !important;
      font-weight: 500 !important;
      padding: 10px 24px !important;
      font-size: 0.9rem !important;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3) !important;
    }
    
    .logout-swal-cancel {
      border-radius: 8px !important;
      font-weight: 500 !important;
      padding: 10px 24px !important;
      font-size: 0.9rem !important;
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.2) !important;
    }
    
    /* SweetAlert2 button hover effects */
    .logout-swal-confirm:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4) !important;
    }
    
    .logout-swal-cancel:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3) !important;
    }
  </style>
</body>

</html>
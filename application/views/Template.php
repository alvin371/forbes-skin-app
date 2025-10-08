<?php
if ($_SESSION['is_login']) {
  redirect(base_url() . 'dashboard');
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://icons.getbootstrap.com/assets/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= base_url() ?>assets/css/style.css?v=1.0.1" type="text/css" media="screen" />

  <?php
  if ($data[0]['img']) {
    $img = base_url() . '/assets/webfile/home/' . $data[0]['img'];
  } else {
    $img = base_url() . '/assets/img/logo.png';
  }
  $img = base_url() . '/assets/img/fav.png';
  ?>
  <meta property="og:image" content="<?= $img ?>" />
  <meta property="og:image:width" content="1000" />
  <meta property="og:image:height" content="1000" />

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <link rel="stylesheet" href="<?= base_url() ?>assets/toast/jquery.toast.css">
  <script src="<?= base_url() ?>assets/toast/jquery.toast.js"></script>

  <style {csp-style-nonce}>
    * {
      transition: background-color 300ms ease, color 300ms ease;
    }

    *:focus {
      background-color: rgba(221, 72, 20, .2);
      outline: none;
    }

    html,
    body {
      /* font-size: 16px; */
      margin: 0;
      padding: 0;
      /* background: linear-gradient(90deg, #012062 50%, #de6602 50%); */
    }

    .form-control {
      margin-bottom: 15px;
    }

    .form-control {
      /* height:55px; */
      margin-bottom: 15px;
      margin-top: 5px;
    }

    .fw-500 {
      font-weight: 500;
    }

    .fw-600 {
      font-weight: 600;
    }

    .fw-700 {
      font-weight: 700;
    }

    .div-login {
      background: #FFF;
      height: 100vh;
      border-top-left-radius: 100px;
      border-top-right-radius: 0px;
      border-bottom-left-radius: 0px;
      border-bottom-right-radius: 100px;
    }

    .container-form {
      left: 50%;
      top: 50%;
      width: 450px;
      position: absolute;
      transform: translate(-50%, -50%);
    }

    .div-icon {
      position: relative;
    }

    .icon-right {
      position: absolute;
      right: 10px;
      font-size: 20px;
      padding-top: 8px;
    }

    .xbanner {
      background-size: cover;
      background-position: top;
      background-repeat: no-repeat;
      height: 100vh;
      display: flex;
    }

    @media only screen and (max-width: 600px) {
      .container-form {
        padding-left: 30px;
        padding-right: 30px;
        width: 100%;
      }

      .xbanner {
        height: 50vh !important;
      }
    }

    body {
      background: unset !important;
    }

    .w-100 {
      width: 100% !important;
    }
  </style>
</head>

<body>
  <?= $content ?>
  <script>
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
  </script>
</body>

</html>
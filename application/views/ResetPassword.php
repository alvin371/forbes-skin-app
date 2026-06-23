<?php
$valid = isset($valid) ? (bool) $valid : false;
$token = isset($token) ? $token : '';
$channel = isset($channel) ? $channel : 'web';
$login_url = isset($login_url) ? $login_url : base_url('auth/login');
$deeplink = isset($deeplink) ? $deeplink : '';
?>
<div class="fullscreen-container">
  <div class="login-container text-start">
    <div class="login-logo">
      <img src="<?= base_url() ?>/assets/img/acneno-logo.png" style="height:100px; width: 100px; margin-top: -20px;" alt="" class="">
    </div>

    <?php if (!$valid): ?>
      <div class="text-white" style="margin-top: -10px;">
        <div class="col-lg-12 pt-4 text-center">
          <i class="bi bi-x-circle" style="font-size: 40px; color:#ffb3b3;"></i>
          <h5 class="text-white mt-3">Link Tidak Valid</h5>
          <small class="text-light">Link reset password sudah kedaluwarsa atau pernah dipakai. Silakan minta link baru.</small>
        </div>
        <div class="col-lg-12 mt-4">
          <a href="<?= base_url() ?>auth/forgot-password" class="btn text-white w-100" style="background-color: #8666BC;">Minta Link Baru</a>
        </div>
        <div class="col-lg-12 mt-3 text-center">
          <small class="text-muted">
            <a href="<?= $login_url ?>" class="text-white" style="text-decoration: underline;">Kembali ke login</a>
          </small>
        </div>
      </div>
    <?php else: ?>
      <form action="<?= base_url() ?>/auth/reset-password-process" id="form" method="POST" class="text-white" style="margin-top: -20px;">
        <div class="form-message"></div>
        <input type="hidden" name="token" value="<?= html_escape($token) ?>">
        <div class="col-lg-12 pt-4">
          <h5 class="text-white mb-1">Reset Password</h5>
          <small class="text-light">Masukkan password baru kamu.</small>
        </div>

        <div class="col-lg-12 pt-3">
          <label for="">Password Baru</label>
          <div class="div-icon">
            <div class="icon-right text-secondary" onclick="func_pass_1()">
              <i class="bi bi-eye-slash" id="show_eye_1" style="display: block;"></i>
              <i class="bi bi-eye" id="hide_eye_1" style="display: none;"></i>
            </div>
            <input name="password" type="password" class="form-control" placeholder="Masukkan password baru" id="password_1" autocomplete="new-password">
          </div>
          <ul id="pw-rules" class="mt-2 ps-3" style="font-size:12px; color:#eee; list-style:none; padding-left:0;">
            <li data-rule="len"><span class="mark">&#10007;</span> Minimal 8 karakter</li>
            <li data-rule="upper"><span class="mark">&#10007;</span> Satu huruf besar</li>
            <li data-rule="lower"><span class="mark">&#10007;</span> Satu huruf kecil</li>
            <li data-rule="num"><span class="mark">&#10007;</span> Satu angka</li>
            <li data-rule="spec"><span class="mark">&#10007;</span> Satu karakter spesial</li>
          </ul>
        </div>

        <div class="col-lg-12">
          <label for="">Konfirmasi Password</label>
          <div class="div-icon">
            <div class="icon-right text-secondary" onclick="func_pass_2()">
              <i class="bi bi-eye-slash" id="show_eye_2" style="display: block;"></i>
              <i class="bi bi-eye" id="hide_eye_2" style="display: none;"></i>
            </div>
            <input name="confirm" type="password" class="form-control" placeholder="Ulangi password baru" id="password_2" autocomplete="new-password">
          </div>
          <small id="match-msg" style="font-size:12px;"></small>
        </div>

        <div class="col-lg-12 mt-4">
          <button class="btn text-white w-100" id="submit-btn" style="background-color: #8666BC;" disabled>Simpan Password</button>
        </div>
        <div class="col-lg-12 mt-3 text-center">
          <small class="text-muted">
            <a href="<?= $login_url ?>" class="text-white" style="text-decoration: underline;">Kembali ke login</a>
          </small>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<style>
  .fullscreen-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: url("<?php echo base_url('assets/img/bg-login.jpg'); ?>") no-repeat center center fixed;
    background-size: cover;
  }
  .login-container {
    background: rgba(37, 37, 37, 0.14);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 15px;
    padding: 50px;
    min-height: 50vh;
    min-width: 25vw;
  }
  @media (max-width: 480px) {
    .login-container { min-width: 330px; }
  }
  .login-logo { display: flex; justify-content: center; margin-bottom: 5px; }
  .login-logo img { width: 60px; height: 60px; }
  #pw-rules li.ok { color: #b6f0c0; }
  #pw-rules li .mark { display:inline-block; width:14px; }
</style>

<?php if ($valid): ?>
<script type="text/javascript">
  var RESET_CHANNEL = <?= json_encode($channel) ?>;
  var RESET_LOGIN_URL = <?= json_encode($login_url) ?>;
  var RESET_DEEPLINK = <?= json_encode($deeplink) ?>;

  function evalRules() {
    var pw = $("#password_1").val();
    var rules = {
      len: pw.length >= 8,
      upper: /[A-Z]/.test(pw),
      lower: /[a-z]/.test(pw),
      num: /[0-9]/.test(pw),
      spec: /[!@#$%^&*(),.?":{}|<>]/.test(pw)
    };
    var allOk = true;
    $("#pw-rules li").each(function() {
      var key = $(this).data("rule");
      if (rules[key]) {
        $(this).addClass("ok");
        $(this).find(".mark").html("&#10003;");
      } else {
        $(this).removeClass("ok");
        $(this).find(".mark").html("&#10007;");
        allOk = false;
      }
    });
    return allOk;
  }

  function evalMatch() {
    var pw = $("#password_1").val();
    var cf = $("#password_2").val();
    if (cf.length === 0) { $("#match-msg").html(""); return false; }
    if (pw === cf) {
      $("#match-msg").css("color", "#b6f0c0").html("Password cocok");
      return true;
    }
    $("#match-msg").css("color", "#ffb3b3").html("Password tidak cocok");
    return false;
  }

  function refreshSubmit() {
    var ok = evalRules() & evalMatch();
    $("#submit-btn").prop("disabled", !ok);
  }

  $("#password_1, #password_2").on("input", refreshSubmit);

  $("#form").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);
    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: mydata,
      cache: false,
      contentType: false,
      processData: false,
      beforeSend: function() {
        $("#submit-btn").addClass("disabled").attr('disabled', true).html('Menyimpan...');
        form.find(".form-message").slideUp().html("");
      },
      success: function(response) {
        $(".form-message").hide().html(response).slideDown("fast");
        if (response.indexOf("success") != -1) {
          setTimeout(function() {
            if (RESET_CHANNEL === 'api' && RESET_DEEPLINK) {
              window.location.href = RESET_DEEPLINK;
            } else {
              window.location.href = RESET_LOGIN_URL;
            }
          }, 2000);
        } else {
          $("#submit-btn").removeClass("disabled").attr('disabled', false).html('Simpan Password');
        }
      },
      error: function(xhr) {
        $(".form-message").hide().html(xhr.responseText).slideDown("fast");
        $("#submit-btn").removeClass("disabled").attr('disabled', false).html('Simpan Password');
      }
    });
    return false;
  });

  function func_pass_1() {
    var x = document.getElementById("password_1");
    var s = document.getElementById("show_eye_1"), h = document.getElementById("hide_eye_1");
    if (x.type === "password") { x.type = "text"; s.style.display = "none"; h.style.display = "block"; }
    else { x.type = "password"; s.style.display = "block"; h.style.display = "none"; }
  }
  function func_pass_2() {
    var y = document.getElementById("password_2");
    var s = document.getElementById("show_eye_2"), h = document.getElementById("hide_eye_2");
    if (y.type === "password") { y.type = "text"; s.style.display = "none"; h.style.display = "block"; }
    else { y.type = "password"; s.style.display = "block"; h.style.display = "none"; }
  }
</script>
<?php endif; ?>

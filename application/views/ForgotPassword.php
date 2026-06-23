<div class="fullscreen-container">
  <div class="login-container text-start">
    <div class="login-logo">
      <img src="<?= base_url() ?>/assets/img/acneno-logo.png" style="height:100px; width: 100px; margin-top: -20px;" alt="" class="">
    </div>
    <form action="<?= base_url() ?>/auth/forgot-password-process" id="form" method="POST" class="text-white" style="margin-top: -20px;">
      <div class="form-message"></div>
      <div class="col-lg-12 pt-4">
        <h5 class="text-white mb-1">Lupa Password</h5>
        <small class="text-light">Masukkan email kamu, kami kirim link untuk reset password.</small>
      </div>
      <div class="col-lg-12 pt-3">
        <label for="" class="text-start">Email</label>
        <input name="email" type="text" class="form-control" placeholder="Masukkan email kamu disini" autocomplete="email">
      </div>
      <div class="col-lg-12 mt-4">
        <div class="row align-items-center">
          <div class="col-12">
            <button class="btn text-white w-100" id="submit-btn" style="background-color: #8666BC;">Kirim Link Reset</button>
          </div>
        </div>
      </div>

      <div class="col-lg-12 mt-3 text-center">
        <small class="text-muted">
          <a href="<?= base_url() ?>auth/login" class="text-white" style="text-decoration: underline;">Kembali ke login</a>
        </small>
      </div>
    </form>
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
    .login-container {
      min-width: 330px;
    }
  }

  .login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 5px;
  }

  .login-logo img {
    width: 60px;
    height: 60px;
  }
</style>

<script type="text/javascript">
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
        $("#submit-btn").addClass("disabled").attr('disabled', true).html('Mengirim...');
        form.find(".form-message").slideUp().html("");
      },
      success: function(response) {
        $(".form-message").hide().html(response).slideDown("fast");
        $("#submit-btn").removeClass("disabled").attr('disabled', false).html('Kirim Link Reset');
      },
      error: function(xhr) {
        $(".form-message").hide().html(xhr.responseText).slideDown("fast");
        $("#submit-btn").removeClass("disabled").attr('disabled', false).html('Kirim Link Reset');
      }
    });
    return false;
  });
</script>

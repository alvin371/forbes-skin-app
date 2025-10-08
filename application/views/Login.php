<div class="fullscreen-container">
  <div class="login-container text-start">
    <div class="login-logo">
      <img src="<?= base_url() ?>/assets/img/logo.png" style="height:100px; width: 100px; margin-top: -20px;" alt="" class="">
    </div>
    <form action="<?= base_url() ?>/auth/login-process" id="form" method="POST" class="text-white" style="margin-top: -20px;">
       <div class="form-message"></div> 
      <div class="col-lg-12 pt-4">
        <label for="" class="text-start">Username</label>
        <input name="email" type="text" class="form-control" placeholder="Masukkan username disini">
      </div>
      <div class="col-lg-12">
        <label for="">Password</label>
        <div class="div-icon">
          <div class="icon-right text-secondary" onclick="func_pass_1()">
            <i class="bi bi-eye-slash" id="show_eye_1" style="display: block;"></i>
            <i class="bi bi-eye" id="hide_eye_1" style="display: none;"></i>
          </div>
          <input name="password" type="password" class="form-control" placeholder="Masukkan password disini" id="password_1">
        </div>
      </div>
      <div class="col-lg-12 mt-4">
        <div class="row align-items-center">
          <div class="col-12">
            <button class="btn text-white w-100" style="background-color: #8666BC;">Masuk Sekarang</button>
          </div>
        </div>
      </div>
      
      <!-- Sign Up Link -->
      <div class="col-lg-12 mt-3 text-center">
        <small class="text-muted">
          Don't have an account? 
          <a href="<?= base_url() ?>auth/signup" class="text-white" style="text-decoration: underline;">Sign up here</a>
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
        $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
        form.find(".form-message").slideUp().html("");
      },
      success: function(response, textStatus, xhr) {
        var str = response;
        console.log(str);
        if (str.indexOf("success") != -1) {
          $(".form-message").hide().html(response).slideDown("fast");
          setTimeout(function() {
            // Get redirect URL from server
            $.ajax({
              url: "<?= base_url() ?>auth/get_redirect_url",
              type: "GET",
              dataType: "json",
              success: function(redirectResponse) {
                console.log("Redirect to:", redirectResponse.url);
                window.location.href = redirectResponse.url;
              },
              error: function() {
                // Fallback to homepage if redirect URL fetch fails
                window.location.href = "";
              }
            });
            $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
          }, 2500);
        } else {
          $(".form-message").hide().html(response).slideDown("fast");
          $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
        }
      },
      error: function(xhr, textStatus, errorThrown) {
        $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
        $(".form-message").hide().html(xhr).slideDown("fast");
      }
    });
    return false;
  });
</script>
</section>
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
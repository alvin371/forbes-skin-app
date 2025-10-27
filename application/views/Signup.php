<!-- Google reCAPTCHA v3 Script -->
<script src="https://www.google.com/recaptcha/api.js?render=<?= env('RECAPTCHA_SITE_KEY') ?>"></script>

<div class="fullscreen-container">
  <div class="login-container text-start">
    <div class="login-logo">
      <img src="<?= base_url() ?>/assets/img/acneno-logo.png" style="height:100px; width: 100px; margin-top: -20px;" alt="" class="">
    </div>
    <h4 class="text-white text-center mb-3" style="margin-top: -10px;">Create Account</h4>
    <form action="<?= base_url() ?>/auth/signup-process" id="signupForm" method="POST" class="text-white">
       <div class="form-message"></div> 
       
      <!-- Full Name -->
      <div class="col-lg-12 pt-2">
        <label for="full_name" class="text-start">Full Name *</label>
        <input name="full_name" id="full_name" type="text" class="form-control" placeholder="Enter your full name" required>
        <small class="text-muted">Minimum 2 characters</small>
      </div>
      
      <!-- Username -->
      <div class="col-lg-12 pt-2">
        <label for="username" class="text-start">Username *</label>
        <input name="username" id="username" type="text" class="form-control" placeholder="Enter username (letters, numbers, underscore)" required>
        <small class="text-muted">3-50 characters, alphanumeric and underscore only</small>
      </div>
      
      <!-- Email -->
      <div class="col-lg-12 pt-2">
        <label for="email" class="text-start">Email Address *</label>
        <input name="email" id="email" type="email" class="form-control" placeholder="Enter your email address" required>
        <small class="text-muted">Valid email address required</small>
      </div>
      
      <!-- Password -->
      <div class="col-lg-12 pt-2">
        <label for="password" class="text-start">Password *</label>
        <div class="div-icon">
          <div class="icon-right text-secondary" onclick="togglePassword('password', 'show_eye_1', 'hide_eye_1')">
            <i class="bi bi-eye-slash" id="show_eye_1" style="display: block;"></i>
            <i class="bi bi-eye" id="hide_eye_1" style="display: none;"></i>
          </div>
          <input name="password" id="password" type="password" class="form-control" placeholder="Enter password" required>
        </div>
        <div class="password-requirements mt-2">
          <small class="text-muted d-block">Password must contain:</small>
          <small class="requirement" id="length">✗ At least 8 characters</small>
          <small class="requirement" id="uppercase">✗ One uppercase letter (A-Z)</small>
          <small class="requirement" id="lowercase">✗ One lowercase letter (a-z)</small>
          <small class="requirement" id="number">✗ One number (0-9)</small>
          <small class="requirement" id="special">✗ One special character (!@#$%^&*)</small>
        </div>
        <div class="password-strength mt-2">
          <div class="strength-bar">
            <div class="strength-fill" id="strengthFill"></div>
          </div>
          <small class="strength-text" id="strengthText">Password strength: Very Weak</small>
        </div>
      </div>
      
      <!-- Confirm Password -->
      <div class="col-lg-12 pt-2">
        <label for="confirm_password" class="text-start">Confirm Password *</label>
        <div class="div-icon">
          <div class="icon-right text-secondary" onclick="togglePassword('confirm_password', 'show_eye_2', 'hide_eye_2')">
            <i class="bi bi-eye-slash" id="show_eye_2" style="display: block;"></i>
            <i class="bi bi-eye" id="hide_eye_2" style="display: none;"></i>
          </div>
          <input name="confirm_password" id="confirm_password" type="password" class="form-control" placeholder="Confirm your password" required>
        </div>
        <small class="password-match" id="passwordMatch" style="display: none;"></small>
      </div>
      
      <!-- Submit Button -->
      <div class="col-lg-12 mt-4">
        <div class="row align-items-center">
          <div class="col-12">
            <button type="submit" class="btn text-white w-100 btn-send" style="background-color: #8666BC;" id="signupBtn" disabled>Create Account</button>
          </div>
        </div>
      </div>
      
      <!-- Login Link -->
      <div class="col-lg-12 mt-3 text-center">
        <small class="text-muted">
          Already have an account? 
          <a href="<?= base_url() ?>auth/login" class="text-white" style="text-decoration: underline;">Login here</a>
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
    padding: 30px;
    min-height: 70vh;
    min-width: 400px;
    max-width: 500px;
  }

  @media (max-width: 480px) {
    .login-container {
      min-width: 330px;
      padding: 20px;
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
  
  .password-requirements {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
    padding: 8px;
    margin-top: 5px;
  }
  
  .requirement {
    display: block;
    color: #ff6b6b;
    font-size: 0.75rem;
    margin: 2px 0;
  }
  
  .requirement.valid {
    color: #51cf66;
  }
  
  .password-strength {
    margin-top: 8px;
  }
  
  .strength-bar {
    width: 100%;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
  }
  
  .strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
  }
  
  .strength-text {
    display: block;
    color: #ccc;
    font-size: 0.75rem;
    margin-top: 2px;
  }
  
  .password-match {
    display: block !important;
    font-size: 0.75rem;
    margin-top: 4px;
  }
  
  .password-match.valid {
    color: #51cf66;
  }
  
  .password-match.invalid {
    color: #ff6b6b;
  }

  .div-icon {
    position: relative;
  }

  .icon-right {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    z-index: 10;
  }
</style>

<script type="text/javascript">
  let passwordRequirements = {
    length: false,
    uppercase: false,
    lowercase: false,
    number: false,
    special: false
  };

  function togglePassword(inputId, showIconId, hideIconId) {
    const input = document.getElementById(inputId);
    const showIcon = document.getElementById(showIconId);
    const hideIcon = document.getElementById(hideIconId);
    
    if (input.type === "password") {
      input.type = "text";
      showIcon.style.display = "none";
      hideIcon.style.display = "block";
    } else {
      input.type = "password";
      showIcon.style.display = "block";
      hideIcon.style.display = "none";
    }
  }

  function validatePassword(password) {
    // Check length
    passwordRequirements.length = password.length >= 8;
    document.getElementById('length').className = passwordRequirements.length ? 'requirement valid' : 'requirement';
    document.getElementById('length').textContent = passwordRequirements.length ? '✓ At least 8 characters' : '✗ At least 8 characters';
    
    // Check uppercase
    passwordRequirements.uppercase = /[A-Z]/.test(password);
    document.getElementById('uppercase').className = passwordRequirements.uppercase ? 'requirement valid' : 'requirement';
    document.getElementById('uppercase').textContent = passwordRequirements.uppercase ? '✓ One uppercase letter (A-Z)' : '✗ One uppercase letter (A-Z)';
    
    // Check lowercase
    passwordRequirements.lowercase = /[a-z]/.test(password);
    document.getElementById('lowercase').className = passwordRequirements.lowercase ? 'requirement valid' : 'requirement';
    document.getElementById('lowercase').textContent = passwordRequirements.lowercase ? '✓ One lowercase letter (a-z)' : '✗ One lowercase letter (a-z)';
    
    // Check number
    passwordRequirements.number = /[0-9]/.test(password);
    document.getElementById('number').className = passwordRequirements.number ? 'requirement valid' : 'requirement';
    document.getElementById('number').textContent = passwordRequirements.number ? '✓ One number (0-9)' : '✗ One number (0-9)';
    
    // Check special character
    passwordRequirements.special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    document.getElementById('special').className = passwordRequirements.special ? 'requirement valid' : 'requirement';
    document.getElementById('special').textContent = passwordRequirements.special ? '✓ One special character (!@#$%^&*)' : '✗ One special character (!@#$%^&*)';
    
    // Calculate strength
    let strength = 0;
    let strengthColors = ['#ff6b6b', '#ffa94d', '#69db7c', '#51cf66', '#40c057'];
    let strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    
    if (passwordRequirements.length) strength++;
    if (passwordRequirements.uppercase) strength++;
    if (passwordRequirements.lowercase) strength++;
    if (passwordRequirements.number) strength++;
    if (passwordRequirements.special) strength++;
    
    document.getElementById('strengthFill').style.width = (strength * 20) + '%';
    document.getElementById('strengthFill').style.backgroundColor = strengthColors[strength - 1] || '#ff6b6b';
    document.getElementById('strengthText').textContent = 'Password strength: ' + (strengthTexts[strength - 1] || 'Very Weak');
    
    return Object.values(passwordRequirements).every(req => req);
  }

  function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchElement = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
      matchElement.style.display = 'none';
      return false;
    }
    
    matchElement.style.display = 'block';
    
    if (password === confirmPassword) {
      matchElement.textContent = '✓ Passwords match';
      matchElement.className = 'password-match valid';
      return true;
    } else {
      matchElement.textContent = '✗ Passwords do not match';
      matchElement.className = 'password-match invalid';
      return false;
    }
  }

  function updateSubmitButton() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const fullName = document.getElementById('full_name').value.trim();
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    
    const isPasswordValid = validatePassword(password);
    const isPasswordMatching = validatePasswordMatch();
    const isFormValid = fullName.length >= 2 && 
                       username.length >= 3 && 
                       email.length > 0 && 
                       isPasswordValid && 
                       isPasswordMatching;
    
    const submitBtn = document.getElementById('signupBtn');
    submitBtn.disabled = !isFormValid;
    submitBtn.style.opacity = isFormValid ? '1' : '0.6';
  }

  // Event listeners
  document.getElementById('password').addEventListener('input', function() {
    updateSubmitButton();
  });

  document.getElementById('confirm_password').addEventListener('input', function() {
    updateSubmitButton();
  });

  document.getElementById('full_name').addEventListener('input', updateSubmitButton);
  document.getElementById('username').addEventListener('input', updateSubmitButton);
  document.getElementById('email').addEventListener('input', updateSubmitButton);

  // Form submission with reCAPTCHA v3
  $("#signupForm").submit(function(e) {
    e.preventDefault();
    var form = $(this);

    // Execute reCAPTCHA v3
    grecaptcha.ready(function() {
      grecaptcha.execute('<?= env('RECAPTCHA_SITE_KEY') ?>', {action: 'signup'}).then(function(token) {
        // Create FormData and add reCAPTCHA token
        var mydata = new FormData(form[0]);
        mydata.append('g-recaptcha-response', token);

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
                window.location.href = "<?= base_url() ?>auth/login";
              }, 2500);
            } else {
              $(".form-message").hide().html(response).slideDown("fast");
              $(".btn-send").removeClass("disabled").html('Create Account').attr('disabled', false);
              updateSubmitButton(); // Re-enable based on validation
            }
          },
          error: function(xhr, textStatus, errorThrown) {
            $(".btn-send").removeClass("disabled").html('Create Account').attr('disabled', false);
            $(".form-message").hide().html('<div class="alert alert-danger">An error occurred. Please try again.</div>').slideDown("fast");
            updateSubmitButton(); // Re-enable based on validation
          }
        });
      });
    });

    return false;
  });

  // Initial validation
  updateSubmitButton();
</script>
<?php
  if (!headers_sent()) {
    session_start();
  }

  $message = $auth->get_message();

  $test_msg = strtolower($message);
  $pos = strrpos($test_msg, "password");

  $is_email = false;
  $is_pwd = true;
  if($pos === false) {
    $is_email = true;
    $is_pwd = false;
  }

  $pos1 = strrpos($test_msg, "out");
  $pos2 = strrpos($test_msg, "expired");

  if($pos1 !== false) {
    $is_email = false;
    $is_pwd = false;
    echo '<h2 class="h3_like_msg">'.$message.'</h2>';
  } else if($pos2 !== false) {
    $is_email = false;
    $is_pwd = false;
    echo '<h2 class="h3_like_msg">'.$message.'</h2>';

    //clear expired cookies
    setcookie(
      ncoas_membership_AUTH,
      ' ',
      time() - YEAR_IN_SECONDS,
      COOKIEPATH,
      COOKIE_DOMAIN
    );
    setcookie(
      ncoas_membership_SEC_AUTH,
      ' ',
      time() - YEAR_IN_SECONDS,
      COOKIEPATH,
      COOKIE_DOMAIN
    );
  } else if(isset($_SESSION['not_logged'])) {
    echo '<h2 class="h3_like_msg">' . $_SESSION['not_logged'] . '</h2>';
    unset($_SESSION['not_logged']);
  }

  $settings = BSettings::get_instance();
  $enableFirstTimeLogin = $settings->get_value('first-time-login-toggle', FALSE);
?>

<div class="swpm-login-widget-form">
  <?php if ($enableFirstTimeLogin) {?>
  <p class="small text-right">
    <a href="#first-time-login">First Time Logging In?</a>
  </p>
  <?php } ?>
  <form id="swpm-login-form" name="swpm-login-form" method="post" action="" class="ncoa_userprofile_form">
    <input type="hidden" name="first-time-login" value="false"/>
    <p class="swpm-email-block">
      <input  data-label="Your Email"
              type="text"
              placeholder="<?php echo  BUtils::_('Enter Your Email') ?>"
              class="<?php echo (count($_POST) > 0 && $message!="" && $is_email)? 'error': ''; ?> required-field email"
              id="swpm_email"
              value="<?php echo BUtils::get_login_email(); ?>"
              name="swpm_email" />
      <?php echo ($message!="" && $is_email)? '<span class="error_msg">'.$message.'</span>': ''; ?>
    </p>

    <div data-container="form-chunk">
      <p class="swpm-password-block">
        <input  data-label="Your Password"
                placeholder="<?php echo  BUtils::_('Enter Your Password') ?>"
                type="password"
                class="<?php echo (count($_POST) > 0 && $message!="" && $is_pwd)? 'error': ''; ?> required-field"
                id="swpm_password"
                value=""
                name="swpm_password" />
        <?php echo ($message!="" && $is_pwd)? '<span class="error_msg">' . $message . '</span>': ''; ?>
      </p>
      <p class="small" data-container="remember-me">
        <span class="swpm-remember-checkbox">
          <input  type="checkbox"
                  name="rememberme"
                  id="rememberme"
                  value="rememberme" <?php echo Butils::get_rememberme(); ?>>
        </span>
        <label for="rememberme" class="ncoa-label">
          <?php echo  BUtils::_('Remember Me') ?>
        </label>
        <span class="text-muted">
          Do not check this option if you are using a public computer.
        </span>
      </p>
    </div>
    <p>
      <button class="button" type="submit" name="login">
        <?php echo  BUtils::_('Login') ?>
      </button>
    </p>
  </form>
</div>


<div id="ncoa-mmmm-message" style="display:none;" title="Corrections Needed">
  <p class="message"></p>
</div>

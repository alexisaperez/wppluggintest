<div class="registration-form-content" >
  <?php
    global $message;
    $err_array = array();
    $email_msg = '';
    $password_msg = '';
    $email_re = '';

    if(count($_POST) > 0 && $message["message"]!=""){
      echo '<h2 class="h3_like_msg">'.$message["message"].'</h2>';
      $email_re = $_POST["email_re"];

      if(isset($message["extra"])){
        $array = $message["extra"];
        if(count($array) > 0){
          $err_array = array_keys($array);
          //print_r($err_array);
          if(in_array("email", $err_array)){
            $email_msg = $array['email'];
          }
          if(in_array("password", $err_array)){
            $password_msg = $array['password'];
          }
        }
      }
    }


  ?>

  <form id="mmm-registration-form" name="mmm-registration-form" method="post" action="" class="ncoa_userprofile_list ncoa_userprofile_form">
    <div class="mmm-form-group">
      <label for="first_name" class="ncoa-label"><?php echo  BUtils::_('First name') ?></label>
      <input data-label="First name" type="text" id="first_name" value="<?php echo $data["first_name"];?>" tabindex="1" size="30" name="first_name" class="required-field input_wide <?php echo (in_array("first_name", $err_array))?'error':''; ?>" />
    </div>

    <div class="mmm-form-group">
      <label for="last_name" class="ncoa-label"><?php echo  BUtils::_('Last name') ?></label>
      <input data-label="Last name" type="text" id="last_name" value="<?php echo $data["last_name"];?>" tabindex="2" size="30"  name="last_name" class="required-field input_wide <?php echo (in_array("last_name", $err_array))?'error':''; ?>" />
    </div>

    <div class="mmm-form-group">
      <label for="email" class="ncoa-label"><?php echo  BUtils::_('Email address') ?></label>
      <input data-label="Email" type="text" id="email" class="required-field email input_wide confirm <?php echo (in_array("email", $err_array))?'error':''; ?>" size="30" value="<?php echo $data["email"];?>" tabindex="3"  name="email" /><br /><?php echo ($email_msg!="")?'<span class="error_msg">'.$email_msg.'</span>':''; ?>
    </div>
      
    <div class="mmm-form-group">
      <label for="email_re" class="ncoa-label"><?php echo  BUtils::_('Confirm your email address ') ?></label>
      <input data-label="Email confirmation" type="text" id="email_re" class="required-field email input_wide <?php echo (in_array("email", $err_array))?'error':''; ?>" size="30" value="<?php if(isset($data["email_re"])){echo $data["email_re"];}?>" tabindex="4" name="email_re" />
    </div>

    <div class="mmm-form-group password">
      <label for="password" class="ncoa-label"><?php echo  BUtils::_('Password') ?></label>
      <input data-label="Password" type="password" autocomplete="off" id="password" value="" tabindex="5" maxlength="30" size="30"  name="password" class="required-field input_wide confirm password <?php echo (in_array("password", $err_array))?'error':''; ?>" /><span class="password-check"></span><span class="mmmm-tooltip mmmm-pwd-tooltip" title="Password requirements: Minimum of 8 characters / Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &#38;, etc) / Cannot contain user's first or last name"><span class="ui-icon ui-icon-info">help</span></span><br /><?php echo ($password_msg!="")?'<span class="error_msg">'.$password_msg.'</span>':''; ?><span style="display:none;"  class="password-error">Your password is not strong enough</span><div id="helper-text-dialog" style="display:none;">
        <p>Strong passwords are extremely important to prevent unauthorized access to your account</p>
        <h4> Password Requirements</h4>
        <ul class="helper-text">
          <li class="length">Your password must contain at least 8 characters</li>
          <li class="lowercase">Your password must contain at least 1 lowercase letter</li>
          <li class="uppercase">Your password must contain at least 1 uppercase letter</li>
          <li class="special">Your password must contain at least 1 special character</li>
          <li class="number">Your password must contain at least 1 number</li>
          <li class="last">Your password cannot contain your first or last name</li>
        </ul>
        <div style="display: block;" class="tool_tip_white_arrow_left tool_tip_white_arrow_up"></div></div>
    </div>

    <div class="mmm-form-group password">
      <label for="password_re" class="ncoa-label"><?php echo  BUtils::_('Confirm your password') ?></label>
      <input data-label="Password confirmation" type="password" autocomplete="off" id="password_re" maxlength="30" value="" tabindex="6" size="30"  name="password_re" class="required-field input_wide <?php echo (in_array("password", $err_array))?'error':''; ?>" /><span class="password_re-check"></span><span style="display:none;"  class="password_re-error">Your passwords do not match. Please try again.</span>
    </div>

    <div class="mmm-form-group">
      <label for="date_select" class="ncoa-label"><?php echo  BUtils::_('Birth month and year (Optional)') ?></label><br /><span id="date_select"><?php 
        echo bUtils::month_dropdown($data["birth_month"]); 
        echo "&nbsp;&nbsp;";
        echo bUtils::year_dropdown($data["birth_year"]); ?></span>
    </div>
    <div class="mmm-form-group">
      <label for="zip" class="ncoa-label"><?php echo  BUtils::_('Zip code (Optional)') ?></label><br /><input type="text" data-label="Zip" id="zip" tabindex="9"  name="zip"  value="<?php echo $data["zip"];?>" class="zipcode" />
    </div>

    <div>
      <input data-label="I have read and agree to the Terms of Use and Privacy Policy" type="checkbox" id="tos_check" name="tos_check" <?php echo (!empty($data["tos_check"]))?'checked="true"': ''; ?> tabindex="10" class="required-field" /> <label for="tos_check" class="ncoa-label <?php echo (in_array("tos_check", $err_array))?'label_error':''; ?>">I have read and agree to the <a href="<?php echo get_permalink(BUtils::get_terms_url()); ?>" target="_blank">Terms of Use</a> and <a href="<?php echo get_permalink(BUtils::get_privacy_url()); ?>" target="_blank">Privacy Policy</a>.</label>
    </div> 
    <div>
      <br />
      <input type="submit" class="button" value="<?php echo  BUtils::_('Sign up') ?>" tabindex="11" id="submit" name="registration" /><input type="hidden" id="form_sid" name="form_sid" /><input type="hidden" id="form_cid" name="form_cid" /><input type="hidden" id="form_pid" name="form_pid" />
    </div>
  </form><div id="mqc_ajaxurl" data="<?php echo admin_url( 'admin-ajax.php' ); ?>"></div>
</div><div class="help">
    If you are having trouble signing up, or have questions, please <a href="<?php echo get_permalink(BUtils::get_contact_url()); ?>">contact us</a>.
</div>

<div id="ncoa-mmmm-message" style="display:none;" title="Corrections Needed">
  <p class="message"></p>
</div>
<?php 
if( $debug ) { ?>

  <!-- DEVELOPER DIAGS -->
  <hr />
  <h2>Developer Diagnostics</h2>
  <pre><?php print_r($debug); ?></pre>

<?php } ?>
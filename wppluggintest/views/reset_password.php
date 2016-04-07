<?php
  global $message;
  $err_array = array();
  $password_msg = '';
  $temporary_msg = '';

  if(count($_POST) > 0 && $message["message"]!=""){
    $pos1 = strrpos($message["message"], "temporary");
    if($pos1 !== false){
      $temporary_msg = $message["message"];
    }else{
      echo '<h2 class="h3_like_msg">'.$message["message"].'</h2>';

      if(isset($message["extra"])){
        $array = $message["extra"];
        if(count($array) > 0){
          $err_array = array_keys($array);
          if(in_array("password_temp", $err_array)){
            $temporary_msg = $array['password_temp'];
          }
          if(in_array("password", $err_array)){
            $password_msg = $array['password'];
          }
        }
      }
    }
  }
?>



<div class="ncoa-password-reset-widget-form">

  <form id="mmm-temporary-form" name="mmm-temporary-form" method="post" action="" class="ncoa_userprofile_form">
    <div class="mmm-form-group">
      <?php echo BUtils::check_userprofile_identity_pw_reset(); ?><label for="password_temp" class="ncoa-label"><?php echo  BUtils::_('Temporary password') ?></label><br />
      <input data-label="Temporary password" type="password" autocomplete="off" id="password_temp" value="" tabindex="1" size="30"  name="password_temp" class="required-field password input_wide <?php echo ($temporary_msg!="")?'error':''; ?>" /><br /><?php echo ($temporary_msg!="")?'<span class="error_msg">'.$temporary_msg.'</span>':''; ?>
    </div>
    <div class="mmm-form-group password-reset">
     <label for="password" class="ncoa-label"><?php echo BUtils::_('Password') ?></label><br />
      <span class="password-check-span"><span class="password-check"></span></span><!--end of password-check-span tag--><input data-label="Password" type="password" autocomplete="off" id="password" value="" tabindex="2" size="30"  name="password" class="required-field input_wide confirm password <?php echo (in_array("password", $err_array))?'error':''; ?>" /><span class="mmmm-tooltip mmmm-pwd-tooltip" title="Password requirements: Minimum of 8 characters / Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &#38;, etc) / Cannot contain user's first or last name"><span class="ui-icon ui-icon-info">help</span></span><br /><?php echo ($password_msg!="")?'<span class="error_msg">'.$password_msg.'</span>':''; ?></span><span style="display:none;"  class="password-error">Your password is not strong enough</span><div id="helper-text-dialog" style="display:none;">
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
    <div class="mmm-form-group password-reset">
      <label for="password_re" class="ncoa-label"><?php echo  BUtils::_('Confirm your password') ?></label><br />
      <span class="password-re-check-span"><span class="password_re-check"></span></span><!--end of password-re-check-span tag--><input data-label="Password confirmation" type="password" autocomplete="off" id="password_re" value="" tabindex="3" size="30"  name="password_re" class="required-field input_wide <?php echo (in_array("password", $err_array))?'error':''; ?>" /><span style="display:none;"  class="password_re-error">Your passwords do not match. Please try again.</span>
    </div>
    <p><input class="button" type="submit" name="mmm-temporary" value="<?php echo  BUtils::_('Reset Your Password')?>" /></p>
  </form>
</div>

<div id="ncoa-mmmm-message" style="display:none;" title="Corrections Needed">
  <p class="message"></p>
</div>

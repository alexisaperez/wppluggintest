<div class="swpm-login-widget-logged">
  <h2 class="mmmm-entry-title">
    <span id="fn-entry">
      <?php echo $auth->get('first_name'); ?>
    </span>
    <span id="ln-entry">
      <?php echo $auth->get('last_name'); ?>
    </span>
  </h2>
<?php

//avatar toggle
 $settings = BSettings::get_instance();
 $avatar_enabled = $settings->get_value('avatar-toggle', '') !== '';
 // echo $avatar_enabled;

  echo BUtils::check_userprofile_identity_mqc();

  global $message;
  $err_array = array();
  $email_msg = '';
  $password_msg = '';
  $zip_msg = '';
  $birth_msg = '';

  if(count($_POST) > 0 && $message["message"] != "") {
    echo '<h2 class="h3_like_msg">'.$message["message"].'</h2>';

    if(isset($message["extra"])) {
      $array = $message["extra"];
      if(count($array) > 0) {
        $err_array = array_keys($array);

        if(in_array("email", $err_array)){
          $email_msg = $array['email'];
        }
        if(in_array("password", $err_array)){
          $password_msg = $array['password'];
        }
        if(in_array("zip", $err_array)){
          $zip_msg = $array['zip'];
        }
        if(in_array("birth_month", $err_array)){
          $birth_msg = $array['birth_month'];
        }
      }
    }

  }
  else if(isset($_SESSION["pwd_change"])) {
    echo '<h2 class="h3_like_msg">'.$_SESSION["pwd_change"].'</h2>';
    unset($_SESSION["pwd_change"]);
  }
?>

<!-- [mmm_ajax_redirect] -->
  <form id="swpm-editprofile-form"
        name="swpm-editprofile-form"
        method="post"
        action=""
        class="ncoa_userprofile_form">
    <noscript id="avatars_content">
    <?php
        if( $avatar_enabled == true ){
            // echo ' avatar enabled';
      echo BUtils::get_avatars(); 
       }else {
         echo " ";
        }
         ?>
    </noscript>

    <div id="decode_temp"></div>

    <div class="avatars_chooser">
        <?php
        if( $avatar_enabled == true ){
            // echo ' avatar enabled';
            echo BUtils::get_current_avatar(); 
        }else {
         echo " ";
        }
      ?>
    </div>

    <!--EMAIL-->
    <div class="ncoa_userprofile_list">
      <div class="swpm-logged-email-label swpm-logged-label">
        <?php echo  BUtils::_('Email address') ?>
        <?php echo ($email_msg!="")?'<span class="error_msg">'.$email_msg.'</span>':''; ?>
        <?php /*
        <span class="link">
          <a class="change" href="#" title="change email">
            <?php echo  BUtils::_('Change') ?>
          </a>
          <a class="cancel hide" title="cancel change email"  href="#">
            <?php echo  BUtils::_('Cancel') ?>
          </a>
        </span>*/?>
      </div>
      <div class="logged swpm-logged-email-value swpm-logged-value hide">
        <?php echo $auth->get('email'); ?>
      </div>
      <div class="update">
        <label for="email" class="ncoa-label hide">
          <?php echo  BUtils::_('Email address') ?>
        </label>
        <input  data-label="Email" data-origin="<?php echo $data["email"];?>" type="text" id="email" class="email" value="<?php echo $data["email"];?>" readonly tabindex="4" size="50" name="email" />
      </div>
    </div>

    <!--PASSWORD--> 
    <div class="ncoa_userprofile_list password">
      <div class="swpm-logged-password-label swpm-logged-label"><?php echo  BUtils::_('Password') ?><?php echo ($password_msg!="")?'<span class="error_msg">'.$password_msg.'</span>':''; ?><span class="link"><a class="change" title="change password" href="#"><?php echo  BUtils::_('Change') ?></a><a class="cancel hide" title="cancel change password" href="#"><?php echo  BUtils::_('Cancel') ?></a></span></div>
      <div class="logged swpm-logged-password-value swpm-logged-value hide"><?php echo  BUtils::_('<span class="asterisk">**********</span>') ?></div>
      <div class="update password"><span class="password-check-span"><label for="password" class="ncoa-label hide"><?php echo  BUtils::_('Password') ?></label><input  data-label="Password" type="password" id="password" data-origin="" value="" tabindex="11" size="50" name="password" class="password confirm"/><span class="password-check"></span><span style="display:none;" class="password-error">Your password is not strong enough</span></span><!--end of password-check-span tag--><span class="mmmm-tooltip mmmm-pwd-tooltip" title="Password requirements: Minimum of 8 characters / Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &#38;, etc) / Cannot contain user's first or last name"><span class="ui-icon ui-icon-info">help</span></span><span class="password-re-check-span"><label for="password_re" class="ncoa-label"><?php echo  BUtils::_('Confirm your password')?></label><input  data-label="Password confirmation" type="password" id="password_re" data-origin="" value="" tabindex="12" size="50" name="password_re" class="password"/><span class="password_re-check"></span><span style="display:none;" class="password_re-error">Your passwords do not match. Please try again. </span></span><!--/end password-re-check-span container tag-->
          <div id="helper-text-dialog" style="display:none;"><p>Strong passwords are extremely important to prevent unauthorized access to your account</p><h4> Password Requirements</h4><ul class="helper-text"><li class="length">Your password must contain at least 8 characters </li><li class="lowercase">Your password must contain at least 1 lowercase letter</li><li class="uppercase">Your password must contain at least 1 uppercase letter</li><li class="special">Your password must contain at least 1 special character</li><li class="number">Your password must contain at least 1 number</li><li class="last">Your password cannot contain your first or last name</li></ul>
            <div style="display: block;" class="tool_tip_white_arrow_left tool_tip_white_arrow_up"></div>
          </div>
        </span>
      </div>

<?php
  // add tab to front end
  // Detect plugin. For use on Front End only.
  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

  // check for plugin using plugin name
  if ( is_plugin_active( 'nisc-users-management/nisc-users-management.php' ) && (isset($data["related"]) && !empty($data["related"]))) {
    //plugin is activate
    echo "<strong>Your NISC Member Senior Center</strong><br />";
    //format address
    $address_formated = '';
    $address = $data["related"][0];

    if(!empty($address['name'])){
      $address_formated .= $address['name'] . '<br />';
    }

    if(!empty($address['street'])){
      $address_formated .= $address['street'] . '<br />';
    }
    if(!empty($address['city'])){
      $address_formated .= $address['city'] . ', ';
    }
    if(!empty($address['state'])){
      $address_formated .= $address['state'] . ' ';
    }
    if(!empty($address['postalCode'])){
      $address_formated .= $address['postalCode'] . '<br />';
    }
    if(!empty($address['phone'])){
      $address_formated .= $address['phone'] . '<br />';
    }
    if(!empty($address['website'])){
      $address_formated .= '<a href="//' . $address['website'] . '" target="_blank">'. $address['website'] . '</a>';
    }

    //center info
    echo $address_formated;

    echo "<br /><br />";
    echo "<strong>Your NISC Primary Contact</strong><br />";
    //echo $data['related'][1];

    //center primary contact
    $primarycontact_formatted = '';
    $primary_profile = $data["related"][1];
    if(!empty($primary_profile->Name)){
      $primarycontact_formatted .= $primary_profile->Name;
    }
    if(!empty($primary_profile->Email)){
      $primarycontact_formatted .= '<br /><a href="mailto:"'
        . $primary_profile->Email . '">'
        . $primary_profile->Email . '</a>';
    }
    echo $primarycontact_formatted;

    //echo "<strong>Your NISC Membership Type</strong><br />";
    //echo $data['related'][2]; 
    echo "<br /><br />";  
    echo "<strong>Your NISC Membership Status</strong><br />";
      if ($data['related'][3] == 'Active') {
        echo "<span style='color:#06ae00;'> " .  $data['related'][3] . "</span>";
        }else{
        echo "<span style='color:#FF0000;'>" .  $data['related'][3] . "</span>";
      }


  } else {
?>

      <div class="ncoa_userprofile_list">
        <div  id="swpm-logged-birthdate-label" class="swpm-logged-birthdate-label swpm-logged-label"> <?php echo  BUtils::_('Birth month and year (Optional)') ?> <?php echo ($birth_msg!="")?'<br /><span class="error_msg">'.$birth_msg.'</span>':''; ?><span class="link"><a class="change" href="#" title="change birthdate">
              <?php echo  BUtils::_('Change') ?>
            </a>
            <a class="cancel hide" href="#" title="cancel change birthday">
              <?php echo  BUtils::_('Cancel') ?>
            </a>
          </span>
        </div>
        <div class="logged swpm-logged-birthdate-value swpm-logged-value hide">
          <?php
            if(isset($data["birth_month"])){
              echo $data["birth_month"];
              echo ($data["birth_month"]!='')?'/':'';
              echo ($data["birth_year"]);
            }
          ?>
        </div>
        <div  id="date_select"
              class="update"
              data-origin="<?php if(isset($data["birth_month"])){echo $data["birth_month"].'/'.$data["birth_year"];}?>">
          <?php
            if(isset($data["birth_month"])){
              echo bUtils::month_dropdown($data["birth_month"]);
            } else {
              echo bUtils::month_dropdown();
            }
          ?>
          &nbsp;&nbsp;
          <?php
            if(isset($data["birth_year"])){
              echo bUtils::year_dropdown($data["birth_year"]);
            } else {
              echo bUtils::year_dropdown();
            }
          ?>
        </div>
      </div>

      <div class="ncoa_userprofile_list">
        <div class="swpm-logged-zip-label swpm-logged-label">
          <?php echo  BUtils::_('Zip code (Optional)') ?>
          <?php echo ($zip_msg!="")?'<span class="error_msg">'.$zip_msg.'</span>':''; ?>
          <span class="link">
            <a class="change" href="#" title="change zip">
              <?php echo  BUtils::_('Change') ?>
            </a>
            <a class="cancel hide" href="#" title="cancel change zip">
              <?php echo  BUtils::_('Cancel') ?>
            </a>
          </span>
        </div>
        <div class="logged swpm-logged-zip-value swpm-logged-value hide">
          <?php echo $auth->get('zip');?>
        </div>
        <div class="update">
          <label for="zip" class="ncoa-label hide">
            <?php echo  BUtils::_('Zip code') ?>
          </label>
          <input  type="text"
                  data-label="Zip"
                  data-origin="<?php if(isset($data["zip"])){echo $data["zip"];}?>"
                  id="zip"
                  value="<?php if(isset($data["zip"])){echo $data["zip"];} ?>"
                  tabindex="13"
                  name="zip"
                  class="zipcode"/>
        </div>
      </div>
<?php
  }
?>

      <p>
        <input  class="button submit"
                type="submit"
                name="editprofile_submit"
                value="<?php echo  BUtils::_('Submit your changes')?>"
                tabindex="14"
                id="submit"/>
        <input type="hidden" id="form_SID" name="form_SID"/>
        <input type="hidden" id="form_CID" name="form_CID"/>
        <input type="hidden" id="form_PID" name="form_PID" />
      </p>
    </form>
    <div id="mqc_ajaxurl" data="<?php echo admin_url( 'admin-ajax.php' ); ?>">
    </div>
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

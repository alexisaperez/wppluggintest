<?php
  global $message;
  if (count($_POST) > 0) {
    echo "<pre>" . var_export($_POST, TRUE) . "</pre>";
  }
?>
<div class="ncoa-password-reset-widget-form">

  <form id="ncoa-reset-form" name="ncoa-reset-form" method="post" action="" class="ncoa_userprofile_form">
    <div class="forms ncoa_userprofile_list">
      <p>
        Enter your email address below and you will receive instructions on how
        to reset your password. If you need further assistance, please
        <a href="<?php echo get_permalink(BUtils::get_contact_url()); ?>">
          contact us</a>.
      </p>
      <p>
        <label for="ncoa_reset_email" class="ncoa-label">
          <?php echo  BUtils::_('Enter Your Email')?>
        </label><br />

        <input  data-label="Your Email"
                id="ncoa_reset_email"
                name="ncoa_reset_email"
                type="text"
                value="<?PHP if(isset($_POST['ncoa_reset_email'])) echo htmlspecialchars($_POST['ncoa_reset_email']); ?>"
                class="required-field email mmm_text_field <?php echo (count($_POST) > 0 && !$message["succeeded"])? 'error': ''; ?>"
                size="40" />
          <?php if(count($_POST) > 0 && !$message["succeeded"]): ?>
            <br />
            <?php echo ($message["message"]!="")?'<span class="error_msg">'.$message["message"].'</span>':''; ?>
          <?php endif; ?>
      </p>
      <?php
        if(count($_POST) > 0 && $message["succeeded"]){
          echo '<h2 class="h3_like_msg">'.$message["message"].'</h2>';
        }
      ?>
      <p>
        <?php
          if($message["message"]!="" && $message["message"] != "Email address not valid."){
        ?>
          <input  class="button btn-disabled"
                  type="submit"
                  name="mmm-reset"
                  disabled="disabled"
                  value="<?php echo  BUtils::_('Reset Password')?>" />
        <?php
          }else{
        ?>
          <input  class="button"
                  type="submit"
                  name="mmm-reset"
                  value="<?php echo  BUtils::_('Reset Password')?>" />
        <?php
          }
        ?>
      </p>
    </div>
  </form>
</div>

<div id="ncoa-mmmm-message" style="display:none;" title="Corrections Needed">
  <p class="message"></p>
</div>

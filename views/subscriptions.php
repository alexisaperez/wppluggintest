<div class="mmm-saved-mqc">  

  <?php
    global $message;
    $err_array = array();

    if(count($_POST) > 0 && $message["message"]!=""){
      echo '<h2 class="h3_like_msg">'.$message["message"].'</h2>';
    }
  ?>

  <form id="swpm-subscriptionprofile-form" name="swpm-subscriptionprofile-form" method="post" action="" class="ncoa_userprofile_form">

    <?php echo BUtils::get_subscriptions(); ?>  
    
    <p class="subsc_button">    
      <input class="button submit" type="submit" name="updatesubscriptions_submit" value="<?php echo  BUtils::_('Submit your changes')?>" tabindex="14" id="submit"  />
    </p>
  </form>

</div>
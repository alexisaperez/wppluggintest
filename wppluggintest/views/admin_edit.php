<div class="wrap" id="swpm-profile-page" type="edit">
    <form action="" method="post" name="swpm-edit-user" id="swpm-edit-user" class="validate"<?php do_action('user_new_form_tag');?>>
    <input name="action" type="hidden" value="edituser" />
    <?php wp_nonce_field( 'edit-swpmuser', '_wpnonce_edit-swpmuser' ) ?>
    <h3><?php echo  BUtils::_('Edit Member') ?></h3>
    <p><?php echo  BUtils::_('Edit existing member details.'); ?></p>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="user_name"><?php echo BUtils::_('User ID'); ?></label></th>
            <td><input readonly class="regular-text" name="user_name" type="text" id="user_name" value="<?php echo esc_attr(stripslashes($user_name)); ?>" aria-required="true" /></td>
      </tr>
    <tr class="form-required">
            <th scope="row"><label for="email"><?php echo  BUtils::_('E-mail'); ?> <span class="description"><?php echo  BUtils::_('(required)'); ?></span></label></th>
            <td><input readonly name="email"  class="regular-text validate[required,custom[email],ajax[ajaxEmailCall]]"  type="text" id="email" value="<?php echo esc_attr($email); ?>" /></td>
    </tr>    
       
    <tr class="form-required">
            <th scope="row"><label for="password"><?php echo  BUtils::_('Password'); ?> <span class="description"><?php /* translators: password input field */_e('(twice, required)'); ?></span></label></th>
            <td><input class="regular-text"  name="password" type="password" id="pass1" autocomplete="off" />
            <br />
            <input class="regular-text" name="password_re" type="password" id="pass2" autocomplete="off" />
            </td>
    </tr>        

    </table>
    
    <?php submit_button( BUtils::_('Edit User '), 'primary', 'editswpmuser', true, array( 'id' => 'createswpmusersub' ) ); ?>
</form>
</div>

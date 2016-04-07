<div class="wrap" id="swpm-profile-page" type="add">
<form action="" method="post" name="swpm-create-user" id="swpm-create-user" class="validate"<?php do_action('user_new_form_tag');?>>
<input name="action" type="hidden" value="createuser" />
<?php wp_nonce_field( 'create-swpmuser', '_wpnonce_create-swpmuser' ) ?>
<h3><?php echo  BUtils::_('Add Member') ?></h3>
<p><?php echo  BUtils::_('Create a brand new user and add it to this site.'); ?></p>
<table class="form-table">
    <tbody>
      <tr>
            <th scope="row"><label for="user_name"><?php echo  BUtils::_('User ID'); ?></label></th>
            <td><input class="regular-text validate[custom[SWPMUserName],minSize[4]]" name="user_name" type="text" id="user_name" value="<?php echo esc_attr(stripslashes($user_name)); ?>" aria-required="true" /></td>
      </tr>
	<tr class="form-required">
            <th scope="row"><label for="email"><?php echo  BUtils::_('E-mail'); ?> <span class="description"><?php echo  BUtils::_('(required)'); ?></span></label></th>
            <td><input name="email"  class="regular-text validate[required,custom[email],ajax[ajaxEmailCall]]"  type="text" id="email" value="<?php echo esc_attr($email); ?>" /></td>
	</tr>
      <tr>
            <th scope="row"><label for="first_name"><?php echo  BUtils::_('First name'); ?></label></th>
            <td><input class="regular-text" name="first_name" type="text" id="first_name" value="<?php echo esc_attr(stripslashes($first_name)); ?>" aria-required="true" /></td>
      </tr>
      <tr>
            <th scope="row"><label for="last_name"><?php echo  BUtils::_('Last name'); ?></label></th>
            <td><input class="regular-text" name="last_name" type="text" id="last_name" value="<?php echo esc_attr(stripslashes($last_name)); ?>" aria-required="true" /></td>
      </tr>
	<tr class="form-required">
            <th scope="row"><label for="password"><?php echo  BUtils::_('Password'); ?> <span class="description"><?php /* translators: password input field */_e('(twice, required)'); ?></span></label></th>
            <td><input class="regular-text"  name="password" type="password" id="pass1" autocomplete="off" />
            <br />
            <input class="regular-text" name="password_re" type="password" id="pass2" autocomplete="off" />
            <br />
            <div id="pass-strength-result"><?php echo  BUtils::_('Strength indicator'); ?></div>
            <p class="description indicator-hint"><?php echo  BUtils::_('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
            </td>
	</tr> 
	
</tbody>
</table>              
<?php submit_button( BUtils::_('Add New Member '), 'primary', 'createswpmuser', true, array( 'id' => 'createswpmusersub' ) ); ?>
</form>
</div>


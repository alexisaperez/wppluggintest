<?php
    echo BUtils::check_userprofile_identity_mqc();
?>

<div class="ncoa-users-mng">
  <form id="ncoa-users-mng-form"
        name="ncoa-users-mng-form"
        method="post"
        action=""
        class="ncoa-users-mng-form">

    <h2 class="mmmm-entry-title">
      Hello
      <span id="fn-entry"><?php echo $auth->get('first_name'); ?></span>
      <span id="ln-entry"><?php echo $auth->get('last_name'); ?></span>
    </h2>
    <p>
      You are your senior center's primary NISC contact. You can manage your
      NISC membership here.
    </p>
    <h4>
      Your NISC Member Senior Center
      <small>
        <a href="#" data-toggle="forms" data-target="centerInfo">
          EDIT CENTER INFORMATION
        </a>
      </small>
    </h4>
    <p data-editableContent="centerInfo">
      <?php
        $centerInfo = $data['related'][0];
        echo $centerInfo['name'] . "<br>";
        echo $centerInfo['street'] . "<br>";
        echo $centerInfo['city'] . ", " . $centerInfo['state'] . $centerInfo['postalCode'] . "<br>";
        echo $centerInfo['phone'] . "<br>";
        if (!empty($centerInfo['website'])) {
          $site = $centerInfo['website'];
          echo "<a href='" . $site . "' target='" . $site . "'>" . $site . "</a>";
        }
      ?>
    </p>
    <div data-editableForm="centerInfo" class="update">
      <input type="hidden" name="center_id" value="<?=$centerInfo['Id']?>">
      <p>
        <label for="center-name" class="ncoa-label">Name</label>
        <input type="text" placeholder="Center Name" name="center_name" value="<?=$centerInfo['name']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">Street</label>
        <input type="text" placeholder="Street Address" name="center_street" value="<?=$centerInfo['street']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">City</label>
        <input type="text" placeholder="City" name="center_city" value="<?=$centerInfo['city']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">State</label>
        <input type="text" placeholder="State" name="center_state" value="<?=$centerInfo['state']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">Postal Code</label>
        <input type="text" placeholder="Postal Code" name="center_postalCode" value="<?=$centerInfo['postalCode']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">Phone</label>
        <input type="text" placeholder="Phone Number" name="center_phone" value="<?=$centerInfo['phone']?>">
      </p>
      <p>
        <label for="center-name" class="ncoa-label">Website</label>
        <input type="text" placeholder="http://www.example.com" name="center_website" value="<?=$centerInfo['website']?>">
      </p>
      <p>
        <input  class="button submit"
                type="submit"
                name="centeraddress_submit"
                value="<?php echo  BUtils::_('Submit your changes')?>"
                tabindex="10"
                id="centeraddress_submit" />
      </p>
    </div>

    <h4>
      Your Account Users
      <small>
        <a href="#" data-toggle="forms" data-target="add_ncoa_users">
          ADD USERS
        </a>/ CHECK BELOW TO DELETE USERS
      </small>
    </h4>

    <div data-editableContent="add_ncoa_users">
      <?php
        //loop through $data['related'][4][0] to get all users

        $users =  $data['related'][4][0];

        $mstatus = $data['related'][4];
        $lastname = $users["LastName"];
        $firstname = $users["FirstName"];
        $email = $users["Email"];
        echo '<ul>';
        for($i = 0; $i < count($users[0]); $i++){
          $lastname = $LastName = $users[0][$i]->LastName;
          $firstname = $FirstName = $users[0][$i]->FirstName;
          $name = $Name = $users[0][$i]->Name;
          $moved_on= $Moved_On__c = $users[0][$i]->Moved_On__c;
          $centeruser_email = $Email = $users[0][$i]->Email;
          $centeruser_id = $Id = $users[0][$i]->Id;


          echo "<li>";
          echo '<label><input type="checkbox" name="user_delete_chkbox[]" value="' . $centeruser_email . '"> ';

          // echo $name . '-- ';
          echo $firstname . ' ';
          echo $lastname;
          // @NOTE This section can be removed in prod - only exists for Ben's
          // testing.
          if ($moved_on) {
            echo " (moved on = true - <em>won't be visible in prod</em>)";
          }
          echo ' </label>';
          echo " <a href=mailto:". $email . ">".  $centeruser_email ."</a>";
          echo "</li>";
        }
        echo "</ul>";
      ?>
      <p>
        <input  class="button submit"
              type="submit"
              name="centeruserdelete_submit"
              value="<?php echo  BUtils::_('Delete user')?>"
              tabindex="10"
              id="centeruserdelete_submit"/>
      </p>
    </div>
    <div data-editableForm="add_ncoa_users" class="add_ncoa_users">
      <!-- add center users here with add and edit-->
      <p>
        <label for="first_name_centeruser" class="ncoa-label">
          <?php echo BUtils::_('First name') ?>
        </label>
        <input  data-label="First name"
                type="text"
                id="first_name_centeruser"
                class="show-first-name"
                value=""
                tabindex="1"
                size="30"
                name="first_name_centeruser"
                class="required-field input_wide <?php echo is_array($err_array) && in_array("first_name_centeruser", $err_array) ? 'error':''; ?>" />
      </p>
      <p>
        <label for="last_name_centeruser" class="ncoa-label">
          <?php echo  BUtils::_('Last name') ?>
        </label>
        <input  data-label="Last name"
                type="text"
                id="last_name_centeruser"
                class="show-last-name"
                value=""
                tabindex="2"
                size="30"
                name="last_name_centeruser"
                class="required-field input_wide <?php echo (in_array("last_name_centeruser", $err_array))?'error':''; ?>" />
      </p>
      <p>
        <label for="email_centeruser" class="ncoa-label">
          <?php echo  BUtils::_('Email') ?>
        </label>
        <input  data-label="Email"
                type="text"
                id="email_centeruser"
                class="show-email"
                value=""
                tabindex="2"
                size="30"
                name="email_centeruser"
                class="required-field input_wide <?php echo (in_array("email_centeruser", $err_array))?'error':''; ?>" />
      </p>
      <p class="mmm-form-group password">
        <label for="password" class="ncoa-label">
          <?php echo  BUtils::_('Password') ?>
        </label>
        <input  data-label="Password"
                type="password"
                autocomplete="off"
                id="password"
                value=""
                tabindex="5"
                maxlength="30"
                size="30"
                name="password"
                class="required-field input_wide confirm password <?php echo (in_array("password", $err_array))?'error':''; ?>" />
        <span class="password-check"></span>
        <span class="mmmm-tooltip mmmm-pwd-tooltip" title="Password requirements: Minimum of 8 characters / Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &#38;, etc) / Cannot contain user's first or last name">
          <span class="ui-icon ui-icon-info">help</span>
        </span>
        <br />
        <?php
          echo ($password_msg!="") ? '<span class="error_msg">' .
            $password_msg . '</span>' : '';
        ?>
        <span style="display:none;"  class="password-error">
          Your password is not strong enough
        </span>
        <div id="helper-text-dialog" style="display:none;">
          <p>
            Strong passwords are extremely important to prevent unauthorized
            access to your account
          </p>
          <h4> Password Requirements</h4>
          <ul class="helper-text">
            <li class="length">
              Your password must contain at least 8 characters
            </li>
            <li class="lowercase">
              Your password must contain at least 1 lowercase letter
            </li>
            <li class="uppercase">
              Your password must contain at least 1 uppercase letter
            </li>
            <li class="special">
              Your password must contain at least 1 special character
            </li>
            <li class="number">
              Your password must contain at least 1 number
            </li>
            <li class="last">
              Your password cannot contain your first or last name
            </li>
          </ul>
          <div style="display: block;" class="tool_tip_white_arrow_left tool_tip_white_arrow_up"></div>
        </div>
      </p>

      <p class="mmm-form-group password">
        <label for="password_re" class="ncoa-label">
          <?php echo  BUtils::_('Confirm your password') ?>
        </label>
        <input  data-label="Password confirmation"
                type="password"
                autocomplete="off"
                id="password_re"
                maxlength="30"
                value=""
                tabindex="6"
                size="30"
                name="password_re"
                class="required-field input_wide <?php echo (in_array("password", $err_array))?'error':''; ?>" />
        <span class="password_re-check"></span>
        <span style="display:none;" class="password_re-error">
          Your passwords do not match. Please try again.
        </span>
      </p>
      <p>
        <input  class="button submit"
                type="submit"
                name="centeruser_submit"
                value="<?php echo  BUtils::_('Submit your changes')?>"
                tabindex="10"
                id="centeruser_submit"/>
      </p><?php print_r($_POST); ?>
    </div><!--end add ncoa users not primary-->

    <h4>Your NISC Membership Status</h4>
    <ul class="nisc_mem_status">
      <?php
        $address_formated = '';
        $related_org_id = $data["related"][6];
        $mstatus = $data['related'][4];

        echo "<li><strong>Type:</strong> " . $data['related'][2] . "</li>";
        echo"<li><strong>Associated Multi-site Centers:</strong>";
        echo "<ul>";
        $children = $data['related'][0][0];
        for($i = 0; $i < count($children[0]); $i++) {
          $child_name = $Name = $children[0][$i]->Name;
          $child_id = $Id = $children[0][$i]->Id;
          echo "<li>";
          echo $child_name;
          echo "</li>";
        }
        echo"</ul></li>";
        if ($data['related'][3] == 'Active') {
          //if Active, show as green else show as red
          echo "<li><strong>Status:</strong><span style='color:#06ae00;'> " .  $data['related'][3] . "</span></li>";
          }else{
          echo "<li><strong>Status:</strong><span style='color:#FF0000;'> " .  $data['related'][3] . "</span></li>";
        }
        echo "<li><strong>Membership expiration date:</strong> " . $mstatus['Membership_End_Date__c'] . "</li>";
        echo "<li><strong>Last renewal date:</strong> " . $mstatus['Membership_Start_Date__c'] . "</li>";

         if ($data['related'][2] !== 'NISC Multi-Site Member') {
          //if NOT a Multi-Site Member display renew link
          echo '<li><a href="https://www.ncoa.org/national-institute-of-senior-centers/join-nisc/nisc-membership-application/" target="_blank">Renew now</a></li>';

          }else{
          echo 'This is a Multi-site, no renew link is displayed';

        }
       // echo '<li><a href="https://www.ncoa.org/national-institute-of-senior-centers/join-nisc/nisc-membership-application/" target="_blank">Renew now</a></li>';

      ?>
    </ul>

  </form>
</div>

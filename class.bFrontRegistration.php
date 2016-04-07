<?php

/**
 * Handles all objects that allow visitors to register to the site via the sign
 * up page and create account.
 */
class BFrontRegistration extends BRegistration {

    private $enable_debug;
    private $debug;
    private $last_id;
  

    private function __construct() {
        $this->debug = array();
        $this->enable_debug = false;
        $settings = BSettings::get_instance();
        $settings_value = $settings->get_value('enable-debug');
        if(isset($settings_value) && !empty($settings_value)){
            $this->enable_debug = true;
        }
    }

    public static function get_instance(){
        self::$_intance = empty(self::$_intance)? new BFrontRegistration():self::$_intance;
        return self::$_intance;
    }

    public function get_debug(){
        return $this->debug;
    }

    public function registration_ui(){
        global $wpdb;

        $settings_configs = BSettings::get_instance();
        $member = BTransfer::$default_fields;
        $mmmm_report = filter_input(INPUT_GET, 'mmmm-report');
        $registration = filter_input(INPUT_POST, 'registration');

        if (!empty($registration)){
            $member = $_POST;
        }else if (!empty($mmmm_report)) {

            if (isset($_SESSION['mqc_client'])) {
                $pos = strrpos($_SESSION['mqc_client'], "self");
                if ($pos === false) { // note: three equal signs
                    // not found...
                }else{
                    //found self
                    $member['birth_month'] = isset( $_COOKIE['mqc_birth_month'] ) ? $_COOKIE['mqc_birth_month'] : '';
                    $member['birth_year'] = isset( $_COOKIE['mqc_birth_year'] ) ? $_COOKIE['mqc_birth_year'] : '';
                }
            }
            $member['zip'] = isset( $_COOKIE['mqc_zip'] ) ? $_COOKIE['mqc_zip'] : '';

            unset($_SESSION['mqc_client']);

        }


        if($this->enable_debug){
            $this->debug[] = $member;
        }else{
            $this->debug = array();
        }

        $args = array( 'data' => $member, 'debug' => $this->debug );
        ob_start();
        extract($args, EXTR_SKIP);
        include(ncoas_membership_PATH . 'views/add.php');
        return ob_get_clean();
    }


    public function register() {
        global $message;

        if($this->create_mmm_user()&&$this->send_reg_email()){
            $mmmm_report = filter_input(INPUT_GET, 'mmmm-report');
            $url = get_permalink(BUtils::get_profile_url());
            $ext = bUtils::get_url_ext();
            if (!empty($mmmm_report)) {
                //save screening id
                BMembers::update_screeningid($this->last_id);
                $url = get_permalink(BUtils::get_saved_results_url()).$ext;
            }

            $auth = BAuth::get_instance();
            $member_info = $this->member_info;
            $auth->login($member_info['email'], $member_info['plain_password']);

            //if (!headers_sent()) {
                wp_redirect($url.$ext);
                //header("Location: ".$url.$ext);
                exit;
            //}

        }
    }
    private function create_mmm_user(){
        global $wpdb;
        global $message;

        $member = BTransfer::$default_fields;
        unset($member['password_temp']);
        $form = new BForm($member);

        if (!$form->is_valid()) {
            $this->debug[] = $form;
            $message = array(
              'succeeded' => false,
              'message' => BUtils::_('Please correct the following:'),
              'extra' => $form->get_errors()
            );
            //BTransfer::get_instance()->set('status', $message);
            return false;
        }

        $member_info = $form->get_sanitized();

        $plain_password = $member_info['plain_password'];
        unset($member_info['tos_check']);
        unset($member_info['plain_password']);

        //update SF
        try{
            //ADD cid, pid
            $member_info['cid'] = filter_input(INPUT_GET, 'form_cid');
            $member_info['pid'] = filter_input(INPUT_GET, 'form_pid');

            //salesforce
            $sForce = BSforce::get_instance();
            $member_id = $sForce->new_user_profile($member_info);

            if(!isset($member_id)) {
                //no sales force data
            }
            $member_info['user_name'] = $member_id;

            //ADD sid
            $member_info['sid'] = filter_input(INPUT_GET, 'form_sid');

            if(empty($member_info['sid'])){
                $member_info['sid'] = isset( $_GET['SID'] ) ? sanitize_text_field( $_GET['SID'] ) : null;
            }
            if(empty($member_info['cid'])){
                $member_info['cid'] = isset( $_GET['CID'] ) ? sanitize_text_field( $_GET['CID'] ) : null;
            }
            if(empty($member_info['pid'])){
                $member_info['pid'] = isset( $_GET['PID'] ) ? sanitize_text_field( $_GET['PID'] ) : null;
            }

            //removed from local DB
            unset($member_info['zip']);
            unset($member_info['birth_month']);
            unset($member_info['birth_year']);
            $wpdb->insert($wpdb->prefix . "ncoas_members", $member_info);
            $last_insert_id = $wpdb->insert_id;
            $this->last_id = $last_insert_id;
            $member_info['plain_password'] = $plain_password;
            $this->member_info = $member_info;
            return true;
        } catch (Exception $e) {
            $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            return;
        }
    }

    public function update_subscriptions() {
        global $wpdb;
        global $message;

        //update SF
        try{

            $checked_subscriptions = $_POST['subscription'];
            if(!isset($checked_subscriptions)){
              $checked_subscriptions = array();
            }

            $sforce = BSforce::get_instance();
            $result = $sforce->update_subscriptions($_POST['campaign'], $checked_subscriptions, $_POST['mmm_profile_contact_id']);

            $message = array('succeeded' => true, 'message' => 'Subscriptions Updated.');
            //BTransfer::get_instance()->set('status', $message);

        } catch (Exception $e) {
            $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            return;
        }
    }


    public function edit() {

        global $wpdb;
        global $message;

        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return;
        }
        $user_data = (array) $auth->userData;

        unset($user_data['permitted']);
        unset($user_data['first_name']);
        unset($user_data['last_name']);
        unset($user_data['password_temp']);
        unset($user_data['sid']);
        unset($user_data['cid']);
        unset($user_data['pid']);

        $form = new BForm($user_data);

        if ($form->is_valid()) {
            global $wpdb;
            $fields_info = $form->get_fields();
            $member_info = $form->get_sanitized();

            // update corresponding wp user.
            if (isset($member_info['plain_password'])) {
                $plain_pass = $member_info['plain_password'];
                unset($member_info['plain_password']);
            }

            if (!isset($member_info['birth_month']) || !isset($member_info['birth_year'])){
                unset($member_info['birth_year']);
                unset($member_info['birth_month']);
            }

            //update SF
            try{

                //assign SF id
                if(isset($user_data['user_name'])){
                    $member_info['user_name'] = $user_data['user_name'];
                    $sforce = BSforce::get_instance();
                    $result = $sforce->update_user_profile($member_info);
                }

                if(isset($member_info['centeraddress_submit'])) {
                  $sforce = BSforce::get_instance();
                  $sforce->update_center_address($member_info);
                }


                //removed from local DB
                unset($member_info['zip']);
                unset($member_info['birth_month']);
                unset($member_info['birth_year']);

                //on success update local DB
                $wpdb->update($wpdb->prefix . "ncoas_members", $member_info, array('member_id' => $auth->get('member_id')));
                $auth->reload_user_data();

                if($fields_info["email"] != $member_info["email"]){
                    //email change, update cookie
                    $auth->set_cookie();
                }

                if(isset($plain_pass) && isset($member_info["password"]) && ($fields_info["password"] != $member_info["password"])){
                    //password change, login again
                    session_start();
                    $_SESSION["pwd_change"] = BUtils::_("Profile Updated.");
                    $auth->logout();
                    $auth->login($member_info['email'], $plain_pass);
                    $plain_pass = "";
                }

                $message = array('succeeded' => true, 'message' => 'Profile Updated.');
                //BTransfer::get_instance()->set('status', $message);

            } catch (Exception $e) {
                $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            }
        } else {
            $this->debug[] = $form;
            $message = array('succeeded' => false, 'message' => BUtils::_('Please correct the following:'),
                'extra' => $form->get_errors());
            //BTransfer::get_instance()->set('status', $message);
            return;
        }
    }

    public function edit_center($data) {
      $auth = BAuth::get_instance();
      if (!$auth->is_logged_in()){
        return;
      }
      $sforce = bSforce::get_instance();
      $sforce->update_center_address($data);
      $auth->reload_user_data();
    }


/*primary edit email and name*/
    public function edit_center_primary($user_data) {
        global $wpdb;
        global $message;

        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return;
        }

        //$user_data = (array) $auth->userData;
        //echo "edit_Primary 1  ";
        unset($user_data['centerprimary_submit']);
        $form = new BForm($user_data);

         // $user_data['email']  = $fields_info['email'] ;
        if ($form->is_valid()) {
            global $wpdb;
            $fields_info = $form->get_fields();
            $member_info = $form->get_sanitized();

            // update corresponding wp user.
         //be sure to unset fields that do not need to be sent or unused

            //update SF
         try{
            //salesforce

            if(isset($member_info['email'])){
                 // print_r($member_info);
                 // die();
                $sForce = BSforce::get_instance();
                // print_r($sForce);
                //  die();
                $result = $sForce->update_center_primary($member_info);

            }//use name or last_name, first_name?
             if(isset($member_info['primary_id'])){
                 // print_r($member_info);
                 // die();
                $sForce = BSforce::get_instance();
                // print_r($sForce);
                //  die();
                $result = $sForce->update_center_primary($member_info);

            }
               if(isset($member_info['last_name'])){
                 // print_r($member_info);
                 // die();
                $sForce = BSforce::get_instance();
                // print_r($sForce);
                //  die();
                $result = $sForce->update_center_primary($member_info);

            }

            //on success update local DB
            $wpdb->update($wpdb->prefix . "ncoas_members", $member_info, array('member_id' => $auth->get('member_id')));
            $auth->reload_user_data();

                // print_r($member_info);
                //  die();
            if($fields_info["email"] != $member_info["email"]){
                //email change, update cookie
                $auth->set_cookie();
            }
           return $result;
             // print_r($member_info);
             //     die();
            } catch (Exception $e) {
                $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            }
        }

    }
public function delete_center_users() {
        global $wpdb;
        global $message;

        $member = BTransfer::$default_fields;
        unset($user_data['centeruserdelete_submit']);
        $form = new BForm($member);
 
      // echo "delete edit_center_users";
        //salesforce
         if ($form->is_valid()) {
            global $wpdb;
            $fields_info = $form->get_fields();
            $member_info = $form->get_sanitized();
            unset($member_info['plain_password']);
            // update corresponding wp user.
         //be sure to unset fields that do not need to be sent or unused
            }
            $member_info = $form->get_sanitized();
            $member_info['email'] = $_POST['email_centeruser'];
            $member_info['last_name'] = $_POST['last_name_centeruser'];
            $member_info['first_name'] = $_POST['first_name_centeruser'];
            
            $email_post = $_POST['email_centeruser'];

        //update SF
        try{
            //salesforce
            $sForce = BSforce::get_instance();
            $member_id = $sForce->flag_movedon_center_users($member_info);
          
            if(!isset($member_id)) {
                //no sales force data
            }     

            return true;
        } catch (Exception $e) {
            $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            return;
        }
    }
/*  add center users via manage page*/
    public function edit_center_users() {
        global $wpdb;
        global $message;

        $member = BTransfer::$default_fields;
        unset($user_data['centeruser_submit']);
        $form = new BForm($member);
 
        //echo "add/edit_center_users";
        //salesforce
       

      
        if ($form->is_valid()) {
            global $wpdb;
            $fields_info = $form->get_fields();
            $member_info = $form->get_sanitized();
            unset($member_info['plain_password']);
            // update corresponding wp user.
         //be sure to unset fields that do not need to be sent or unused
            }
            $member_info = $form->get_sanitized();
            $member_info['email'] = $_POST['email_centeruser'];
            $member_info['last_name'] = $_POST['last_name_centeruser'];
            $member_info['first_name'] = $_POST['first_name_centeruser'];
            
            $email_post = $_POST['email_centeruser'];
        //update SF
        try{
            //salesforce
            $sForce = BSforce::get_instance();
            $member_id = $sForce->add_center_users($member_info);
            // $member_id = $sForce->new_user_profile($member_info);
            if(!isset($member_id)) {
                //no sales force data
            }
            $member_info['user_name'] = $member_id;
           
            /*at this point in code, $member_id is an array*/
            /*need to extract id from array reassing to user_name*/
            //object(stdClass)#78 (3) 
           //{ ["created"]=> bool(false) 
           //["id"]=> string(18) "0033B000005SDAJQA4" 
           //["success"]=> bool(true) } 
            $mObj = new stdClass();
            $mObj->id = $member_id[0]->id;
            $member_id =  $mObj->id;
            $member_info['user_name']=  $member_id;
       
 
                 global $wpdb;
    
            $query = 'SELECT email FROM ' .$wpdb->prefix . 'ncoas_members ' .'  WHERE email = %s "'.  $email_post .' "';
            $result =(array) $wpdb->get_row($wpdb->prepare($query, $email));
            // $result['email'];

            
   
            //removed from local DB
            unset($member_info['plain_password']);
            unset($member_info['sid']);
            unset($member_info['cid']);
            unset($member_info['pid']);
            unset($member_info['zip']);
            unset($member_info['birth_month']);
            unset($member_info['birth_year']);
            
            /* this is public var for use in bSforce  add_center_users()*/
            $add_user = false;
            /* prevent duplicate emails on WP DB*/
            if ( $result['email'] !== $email_post) {
                /* add email if not a duplicate*/
                 $wpdb->insert($wpdb->prefix . "ncoas_members", $member_info);
                $last_insert_id = $wpdb->insert_id;
                $this->last_id = $last_insert_id;
                $member_info['plain_password'] = $plain_password;
                $this->member_info = $member_info;
                $add_user = true;
                //echo $add_user;

            } else {
                 $message = BUtils::_("Email already exists");
                 // echo $result['email'];
                 //  echo $email_post;
                 //  echo 'emial exists   ';
               return  ;
            }

          
 

            return true;
        } catch (Exception $e) {
            $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            return;
        }
 
       
    }

    public function reset_password($email) {
        global $message;
        global $wpdb;

        $email = sanitize_email($email);
        if (!is_email($email)) {
            $message = BUtils::_("Email address not valid.");
            $message = array('succeeded' => false, 'message' => $message);
            //BTransfer::get_instance()->set('status', $message);
            return;
        }

        $query = 'SELECT member_id,first_name,last_name FROM ' .
                $wpdb->prefix . 'ncoas_members ' .
                ' WHERE email = %s';
        $user = $wpdb->get_row($wpdb->prepare($query, $email));
        if (empty($user)) {
            $message = BUtils::_("No user found with that email address.");
            $message = array('succeeded' => false, 'message' => $message);
            //BTransfer::get_instance()->set('status', $message);
            return;
        }

        $settings = BSettings::get_instance();
        //$password = wp_generate_password().rand(0, 9);
        $password = trim(wp_generate_password (12, true, true).rand(0, 9));

        $password_hash = BUtils::encrypt_password($password); //should use $saned??;
        $wpdb->update($wpdb->prefix . "ncoas_members", array('password' => $password_hash, 'password_temp' => 1), array('member_id' => $user->member_id));

        // update wp user pass.
        $body = $settings->get_value('reset-mail-body');
        $subject = $settings->get_value('reset-mail-subject');
        $reset_link = get_permalink(BUtils::get_temporary_url())."?email=".$email;
        $search = array('{first_name}', '{last_name}', '{password}', '{reset_link}');
        $replace = array($user->first_name, $user->last_name, $password, $reset_link);
        $body = str_replace($search, $replace, $body);
        $from_address = $settings->get_value('email-from');
        $from_name = $settings->get_value('email-name');
        $headers = 'From: ' . $from_name . " <".$from_address.">\r\n";
        wp_mail($email, $subject, $body, $headers);
        $message = BUtils::_("Your password has been reset - please check your email for login information.");

        $message = array('succeeded' => true, 'message' => $message);
        //BTransfer::get_instance()->set('status', $message);
    }

    public function temporary_password() {
        global $message;
        global $wpdb;

        $auth = BAuth::get_instance();
        $user_data = (array) $auth->userData;// move this up here authenticate

        $email = isset( $_GET['email'] ) ? sanitize_text_field( $_GET['email'] ) : '';
        $password_db = '';
        $temporary_flag = 1;

        if($email == ''){
            //email from login
            if (!$auth->is_logged_in()) {
                $message = BUtils::_("You are not logged in.");
                $message = array('succeeded' => false, 'message' => $message);
                //BTransfer::get_instance()->set('status', $message);
                return;
            }

            // $user_data = (array) $auth->userData; move this up
            $email = $user_data["email"];
            $password_db = $user_data["password"];
            $temporary_flag = $user_data["password_temp"];

        }else{
            $email = sanitize_email($email);
            if (!is_email($email)) {
                //Email not valid
                $message = BUtils::_("No user found with this email address.");
                $message = array('succeeded' => false, 'message' => $message);
                return;
            }else{
                $query = 'SELECT member_id, first_name, last_name, password, password_temp FROM ' .
                $wpdb->prefix . 'ncoas_members ' .
                ' WHERE email = %s';
                $user_data =(array) $wpdb->get_row($wpdb->prepare($query, $email));
                if (empty($user_data)) {
                    $message = BUtils::_("No user found with this email address.");
                    $message = array('succeeded' => false, 'message' => $message);
                    //BTransfer::get_instance()->set('status', $message);
                    return;
                }
                $password_db = $user_data["password"];
                $user_data["password"] = '';
                $temporary_flag = $user_data["password_temp"];
                $user_data["password_temp"] = '';
            }
        }

        $login_url = BUtils::get_profile_url();
        $ext = bUtils::get_url_ext();

        if($temporary_flag != 1){
            wp_redirect(get_permalink($login_url).$ext);
            exit;
        }

        unset($user_data['email']);
        $first_name = $user_data['first_name'];
        unset($user_data['first_name']);
        $last_name = $user_data['last_name'];
        unset($user_data['last_name']);
        unset($user_data['birth_month']);
        unset($user_data['birth_year']);
        unset($user_data['zip']);
        unset($user_data['avatar']);
        unset($user_data['screening_id']);
        unset($user_data['subset_id']);
        unset($user_data['report_date']);
        unset($user_data['created']);
        unset($user_data['sid']);
        unset($user_data['cid']);
        unset($user_data['pid']);
        $form = new BForm($user_data);

        if ($form->is_valid()) {
            $member_info = $form->get_sanitized();
            $password_temp = $member_info['password_temp'];
            $password = $member_info['plain_password'];

            //check old pwd
            if(!($auth->check_password($password_temp, $password_db))){
                $message = BUtils::_("Invalid temporary password");
                $message = array('succeeded' => false, 'message' => $message);
                //BTransfer::get_instance()->set('status', $message);
                return;
            }

            //update new pwd
            $password_hash = BUtils::encrypt_password(trim($password)); //should use $saned??;

            $wpdb->update($wpdb->prefix . "ncoas_members", array('password' => $password_hash, 'password_temp' => 0), array('member_id' => $user_data['member_id']));


            // update wp user temporary pass.
            $settings = BSettings::get_instance();
            $body = $settings->get_value('change-email-body');
            $subject = $settings->get_value('change-email-subject');
            //$login_link = get_permalink($settings->get_value('login-page-url'));
            $login_link = get_permalink(BUtils::get_login_url());
            $search = array('{first_name}', '{last_name}', '{password}', '{login_link}');
            //$replace = array($user->user_name, $user->first_name, $user->last_name, $password);
            $replace = array($first_name, $last_name, $password, $login_link);
            $body = str_replace($search, $replace, $body);
            $from_address = $settings->get_value('email-from');
            $from_name = $settings->get_value('email-name');
            $headers = 'From: ' . $from_name . " <".$from_address.">\r\n";
            wp_mail($email, $subject, $body, $headers);

            //login with new credentials
            $auth->logout();
            $auth->login($email, $password);

            $message = array('succeeded' => true, 'message' => 'Password Updated.');
            //BTransfer::get_instance()->set('status', $message);

            $profile_url = BUtils::get_profile_url();
            $ext = bUtils::get_url_ext();

            //if (!headers_sent()) {
                wp_redirect(get_permalink($profile_url).$ext);
                exit;
            //}

        } else {
            $this->debug[] = $form;
            $message = array('succeeded' => false, 'message' => BUtils::_('Please correct the following:'),
                'extra' => $form->get_errors());
            //BTransfer::get_instance()->set('status', $message);
            return;
        }
    }
}

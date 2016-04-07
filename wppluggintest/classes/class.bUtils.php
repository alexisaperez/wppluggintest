<?php

/**
 * This handles mutiple areas of functionality : emails sent after registration,
 * handles the avatar functionality, allows for Admins to delete user accounts,
 * encrypts passwords, creates mandatory WP pages, sets up birth date picker for
 * the create account page, defines the urls in the settings for all pages,
 * checks for user identity when password is reset, saves MQC results to user
 * accounts, updates enrollment information.
 */
abstract class BUtils {

    public static function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    public static function get_user_by_id($swpm_id) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT user_name FROM {$wpdb->prefix}ncoas_members WHERE member_id = %d", $swpm_id);
        return $wpdb->get_var($query);
    }

    public static function get_user_by_user_name($swpm_user_name) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}ncoas_members WHERE user_name = %s", $swpm_user_name);
        return $wpdb->get_var($query);
    }

    //new for mmm
    public static function get_user_by_user_email($swpm_user_email) {
          global $wpdb;
          $query = $wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}ncoas_members WHERE email = %s", $swpm_user_email);
            return $wpdb->get_var($query);
      }
//--
    public static function get_registration_link($for = 'all', $send_email = false, $member_id = '') {
        $members = array();
        global $wpdb;
        switch ($for) {
            case 'one':
                if (empty($member_id)) {
                    return array();
                }
                $query = $wpdb->prepare("SELECT * FROM  {$wpdb->prefix}ncoas_members WHERE member_id =  %d", $member_id);
                $members = $wpdb->get_results($query);
                break;
            case 'all':
                $query = "SELECT * FROM  {$wpdb->prefix}ncoas_members WHERE reg_code != '' ";
                $members = $wpdb->get_results($query);
                break;
        }
        $settings = BSettings::get_instance();
        $separator = '?';
        $url = $settings->get_value('registration-page-url');
        if (strpos($url, '?') !== false) {
            $separator = '&';
        }
        $subject = $settings->get_value('reg-complete-mail-subject');
        if (empty($subject)) {
            $subject = "Please complete your registration";
        }
        $body = $settings->get_value('reg-complete-mail-body');
        if (empty($body)) {
            $body = "Please use the following link to complete your registration. \n {reg_link}";
        }
        $from_address = $settings->get_value('email-from');
        $from_name = $settings->get_value('email-name');
        $links = array();
        foreach ($members as $member) {
            $reg_url = $url . $separator . 'member_id=' . $member->member_id . '&code=' . $member->reg_code;
            if (!empty($send_email) && empty($member->user_name)) {
                $tags = array("{first_name}", "{last_name}", "{reg_link}");
                $vals = array($member->first_name, $member->last_name, $reg_url);
                $email_body = str_replace($tags, $vals, $body);
                $headers = 'From: ' . $from_name . " <".$from_address.">\r\n";
                wp_mail($member->email, $subject, $email_body, $headers);
            }
            $links[] = $reg_url;
        }
        return $links;
    }

    public static function is_multisite_install() {
        if (function_exists('is_multisite') && is_multisite()) {
            return true;
        } else {
            return false;
        }
    }

    public static function _($msg) {
        return __($msg, 'swpm');
    }

    public static function e($msg) {
        _e($msg, 'swpm');
    }

    public static function is_admin() {
        return current_user_can('manage_options');
    }

    public static function swpm_username_exists($user_name) {
        global $wpdb;
        $member_table = $wpdb->prefix . 'ncoas_members';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE user_name=%s', sanitize_user($user_name));
        return $wpdb->get_var($query);
    }

    public static function swpm_useremail_exists($user_email) {
        global $wpdb;
        $member_table = $wpdb->prefix . 'ncoas_members';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE email=%s', sanitize_user($user_email));
        return $wpdb->get_var($query);
    }
     /* ==============
     * avatars functions
     * =========================================== */
     ///avatar related


     public static function swpm_avatar_exists($avatar) {
        global $wpdb;
        $member_table = $wpdb->prefix . 'ncoas_members';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE avatar=%s', sanitize_user($avatar));

        return $wpdb->get_var($query);
    }



    public static function get_avatars() {
        $avatar_dir = plugins_url( 'images/avatars/' ,  dirname(__FILE__) );
        $avatars_name = array(" mistery.png",
            "mistery.png",
            "susan.png",
            "david.png",
            "john.png",
            "linda.png",
            "mike.png",
            "vivian.png");

        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return;
        }
        $user_data = (array) $auth->userData;
        $selected_val_avatar = $user_data["avatar"];

        $html_output ='<div id="avatars" class="" >'. "\n";
        for ($avatar = 1; $avatar <= 7; $avatar++) {
            $checked = '';
            $fade = 'fade';
            if($avatars_name[$avatar] == $user_data["avatar"]){
                $checked = 'checked="checked"';
                $fade = '';
            }
            $html_output .='<div class="avatar '. $fade . '"><input type="radio" value="'.$avatars_name[$avatar] .'"name="avatar" id="'.$avatar .'" '. $checked .' />'."\n";
            $html_output .='<img src="'.$avatar_dir.$avatars_name[$avatar].'"/>'."\n";
            $html_output .=' </div>'."\n";
        }
        $html_output .= '</div>'."\n";

        return $html_output;
    }

    public static function get_current_avatar() {
        $avatar_dir = plugins_url( 'images/avatars/' ,  dirname(__FILE__) );

        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return;
        }
        $user_data = (array) $auth->userData;
        $selected_val_avatar = $user_data["avatar"];

        $msg = 'Cancel Avatar Change';//buttton text
        $msg2 = 'Change';//button text
        $html_output = '<div class="logged main_avatar">';

        if(isset($_POST['editprofile_submit'])){
            global $wpdb;
            $auth->reload_user_data();
            $user_data = (array) $auth->userData;

            $html_output .= '<img class="default show" src="'. $avatar_dir. $user_data['avatar'].'"/>'. '</ br> ' ."\n";

        }else {
            $html_output .= '<img class="default show" src="'.$avatar_dir.$selected_val_avatar.'"/>'. '</ br> ' ."\n";
        }
        $html_output .= '<span class="link"><a id="cancel_link" class="cancel_link button hide_avatars" href="#">'. BUtils::_($msg) . '</a></span>'."\n";
        $html_output .= '<input class="button change_avatar" id="change_avatar" name="change_avatar" value="'.BUtils::_($msg2).'" type="button" />'."\n";
        $html_output .= '</div>'."\n";

        return $html_output;
    }



    public static function account_delete_confirmation_ui($msg = "") {
        ob_start();
        include(ncoas_membership_PATH . 'views/account_delete_warning.php');
        ob_get_flush();
        wp_die("", "", array('back_link' => true));
    }

    public static function delete_account_button() {
        $allow_account_deletion = BSettings::get_instance()->get_value('allow-account-deletion');
        if (empty($allow_account_deletion)) {
            return "";
        }

        return '<a href="/?delete_account=1"><div class="swpm-account-delete-button">' . BUtils::_("Delete Account") . '</div></a>';
    }

    public static function encrypt_password($plain_password) {
        include_once(ABSPATH . WPINC . '/class-phpass.php');
        $wp_hasher = new PasswordHash(8, TRUE);
        $password_hash = $wp_hasher->HashPassword(trim($plain_password));
        return $password_hash;
    }

    /*
     * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
     */

    public static function sanitize_value_by_array($val_to_check, $valid_values) {
        $keys = array_keys($valid_values);
        $keys = array_map('strtolower', $keys);
        if (in_array($val_to_check, $keys)) {
            return $val_to_check;
        }
        return reset($keys); //Return he first element from the valid values
    }


    /* ==============
     * Mandatory pages creation assistant function
     * =========================================== */

    public static function create_mandatory_wp_pages() {
        $settings = BSettings::get_instance();

        //Create registration page
        $swpm_rego_page = array(
            'post_title' => BUtils::_('Create an account'),
            'post_name' => 'create-account',
            'post_content' => '[ncoa_userprofile_registration_form]',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $rego_page_obj = get_page_by_path('create-account');
        if (!$rego_page_obj) {
            $rego_page_id = wp_insert_post($swpm_rego_page);
        } else {
            $rego_page_id = $rego_page_obj->ID;
            if ($rego_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $rego_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_rego_page_permalink = get_permalink($rego_page_id);
        $settings->set_value('registration-page-url', $swpm_rego_page_permalink);

        //Create login page
        $swpm_login_page = array(
            'post_title' => BUtils::_('Sign in to your account'),
            'post_name' => 'user-login',
            'post_content' => '[ncoa_userprofile_login_form]',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );


        $login_page_obj = get_page_by_path('user-login');
        if (!$login_page_obj) {
            $login_page_id = wp_insert_post($swpm_login_page);
        } else {
            $login_page_id = $login_page_obj->ID;
            if ($login_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $login_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_login_page_permalink = get_permalink($login_page_id);
        $settings->set_value('login-page-url', $swpm_login_page_permalink);



        //Create profile page
        $swpm_profile_page = array(
            'post_title' => BUtils::_('Your Profile'),
            'post_name' => 'your-profile',
            'post_content' => '[ncoa_userprofile_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $profile_page_obj = get_page_by_path('your-profile');
        if (!$profile_page_obj) {
            $profile_page_id = wp_insert_post($swpm_profile_page);
        } else {
            $profile_page_id = $profile_page_obj->ID;
            if ($profile_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $profile_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_profile_page_permalink = get_permalink($profile_page_id);
        $settings->set_value('profile-page-url', $swpm_profile_page_permalink);

        //Create reset page
        $swpm_reset_page = array(
            'post_title' => BUtils::_('Reset your password'),
            'post_name' => 'password-reset',
            'post_content' => '[ncoa_userprofile_reset_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $reset_page_obj = get_page_by_path('password-reset');
        if (!$reset_page_obj) {
            $reset_page_id = wp_insert_post($swpm_reset_page);
        } else {
            $reset_page_id = $reset_page_obj->ID;
            if ($reset_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $reset_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_reset_page_permalink = get_permalink($reset_page_id);
        $settings->set_value('reset-page-url', $swpm_reset_page_permalink);

        // check for plugin using plugin name
        if ( is_plugin_active( 'nisc-users-management/nisc-users-management.php' ) ) {
            //ncoa-membership plugin is activate

            $swpm_manage_page = array(
                'post_title' => BUtils::_('Manage Your Membership'),
                'post_name' => 'manage-membership',
                'post_content' => '[ncoa_manage_membership]',
                'post_parent' => $login_page_id,
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            );
            $member_page_obj = get_page_by_path('manage-membership');
            if (!$member_page_obj) {
                $manage_page_id = wp_insert_post($swpm_manage_page);
            } else {
                $manage_page_id = $member_page_obj->ID;
                if ($member_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                    wp_update_post(array('ID' => $member_page_obj->ID, 'post_status' => 'publish'));
                }
            }
            $swpm_manage_page_permalink = get_permalink($manage_page_id);
            $settings->set_value('mng-page-url', $swpm_manage_page_permalink);

        }

        $settings->save(); //Save all settings object changes
    }

    public static function set_default_admin_settings() {
        $settings = BSettings::get_instance();

        //Set Domain names
        $settings->set_value('mmm-domain', 'mymedicarematters.org');
        $settings->set_value('nisc-domain', 'ncoa.org');
        //Set timeout
        $settings->set_value('membership-timeout', '20');
        //Set password
        $settings->set_value('membership-pwd-length', '8');

        $settings->save(); //Save all settings object changes
    }

    public static function swpm_birthyear_exists($birth_year) {

       global $wpdb;
        $member_table = $wpdb->prefix . 'ncoas_members';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE birth_year=%d', sanitize_user($birth_year));

        return $wpdb->get_var($query);

    }

    public static function swpm_birthmonth_exists($birth_month) {
         global $wpdb;
        $member_table = $wpdb->prefix . 'ncoas_members';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE birth_month=%d', sanitize_user($birth_month));

        return $wpdb->get_var($query);
    }


    //new for mmm date dropdown-
    public static function month_dropdown($curr_month = "", $year_limit = 0){

        /*months*/
        $html_output ='<select tabindex="7" name="birth_month" id="birth_month" >'.'<option value="">Select Month</option>'."\n";
        $months = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

        for ($birth_month = 1; $birth_month <= 12; $birth_month++) {
            if($curr_month == $birth_month){
                $html_output .='<option value="'.$birth_month.'" selected="true">';
            }else{
                $html_output .='<option value="'.$birth_month.'">';
            }
            $html_output .= $months[$birth_month].'</option>'."\n";
        }
        $html_output .='</select>';

        return $html_output;
    }

    public static function year_dropdown($curr_year = "", $year_limit = 0){

        /*years*/
        $html_output ='<select tabindex="8" name="birth_year" id="birth_year">'.'<option value="">Select Year</option>'."\n";
        for ($birth_year = 1900; $birth_year <= (date("Y") - $year_limit); $birth_year++) {

            if($curr_year == $birth_year){
                $html_output .='<option value="'.$birth_year.'" selected="true">';
            }else{
                $html_output .='<option value="'.$birth_year.'">';
            }
            $html_output .= $birth_year.'</option>'."\n";
        }
        $html_output .='</select>'."\n";

        return $html_output;
    }

    ///end date dropdown

    public static function return_birthdate_at_registration() {
        //first submit of birth info
        if(isset($_POST['registration'])){

           global $wpdb;
            $table_name = $wpdb->prefix . "ncoas_members";
            $birth_month = $_POST['birth_month'];
            $birth_year = $_POST['birth_year'];
            $wpdb->update($table_name,
                 array('birth_year' => $birth_year,
                       'birth_month' => $birth_month),
                  array('member_id' => $member_id),
                  array('%d','%d'),
                  array('%d')
                  ) ;

           return ;
          $wpdb->print_error();
        }
    }



    public static function return_new_birthdate($swpm_id) {
        global $wpdb;
        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return;
        }
        $user_data = (array) $auth->userData;

        if(isset($_POST['editprofile_submit'])){
            $auth->reload_user_data();
        }
        if (($birth_month = " ") ||  ($birth_year =" ")) {
            echo 'Please select both month and year';

        } else {
            return $user_data['birth_month'] . $user_data['birth_year'];
        }
        $wpdb->print_error();
    }


    /* URLs defined on settings page */

    public static function get_registration_url() {
        $setting = BSettings::get_instance();
        $registration_url = $setting->get_value('registration-page-url');
        return $registration_url;
    }

    public static function get_profile_url() {
        $setting = BSettings::get_instance();
        $profile_url = $setting->get_value('profile-page-url');
        return $profile_url;
    }

    public static function get_subscription_url() {
        $setting = BSettings::get_instance();
        $subscription_url = $setting->get_value('subscription-page-url');
        return $subscription_url;
    }

    public static function get_saved_results_url() {
        $setting = BSettings::get_instance();
        $saved_results_url = $setting->get_value('mqc-saved-results-page-url');
        return $saved_results_url;
    }

    public static function get_mqc_url() {
        $setting = BSettings::get_instance();
        $mqc_url = $setting->get_value('mqc-page-url');
        return $mqc_url;
    }

    public static function get_login_url() {
        $setting = BSettings::get_instance();
        $login_url = $setting->get_value('login-page-url');
        return $login_url;
    }

    public static function get_reset_url() {
        $setting = BSettings::get_instance();
        $reset_url = $setting->get_value('reset-page-url');
        return $reset_url;
    }

    public static function get_temporary_url() {
        $setting = BSettings::get_instance();
        $temporary_url = $setting->get_value('temporary-page-url');
        return $temporary_url;
    }

    public static function get_terms_url() {
        $setting = BSettings::get_instance();
        $terms_url = $setting->get_value('terms-url');
        return $terms_url;
    }

    public static function get_privacy_url() {
        $setting = BSettings::get_instance();
        $privacy_url = $setting->get_value('privacy-url');
        return $privacy_url;
    }

    public static function get_contact_url() {
        $setting = BSettings::get_instance();
        $contact_url = $setting->get_value('contact-url');
        return $contact_url;
    }

    public static function get_mng_url() {
        $setting = BSettings::get_instance();
        $mng_url = $setting->get_value('mng-page-url');
        return $mng_url;
    }

    public static function get_benefits_url() {
        $setting = BSettings::get_instance();
        $benefits_url = $setting->get_value('benefits-page-url');
        return $benefits_url;
    }

    public static function get_accreditation_url() {
        $setting = BSettings::get_instance();
        $accreditation_url = $setting->get_value('accreditation-page-url');
        return $accreditation_url;
    }

    public static function get_opportunities_url() {
        $setting = BSettings::get_instance();
        $opportunities_url = $setting->get_value('opportunities-page-url');
        return $opportunities_url;
    }

    public static function get_news_url() {
        $setting = BSettings::get_instance();
        $news_url = $setting->get_value('news-page-url');
        return $news_url;
    }

    public static function get_domain_names() {
        $setting = BSettings::get_instance();
        $stack = array();
        $domain = $setting->get_value('mmm-domain');
        if(!empty($domain)){
            array_push($stack, $setting->get_value('mmm-domain'));
        }
        $domainn = $setting->get_value('nisc-domain');
        if(!empty($domainn)){
            array_push($stack, $setting->get_value('nisc-domain'));
        }

        return $stack;
    }


    // //check last name  first name when pw reset
    public static function check_userprofile_identity_pw_reset() {
        $auth = BAuth::get_instance();

        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;
            $first_name = $user_data['first_name'];
            $last_name = $user_data['last_name'];
        }else{

            global $wpdb;
            $email = filter_input(INPUT_GET, 'email');
            $query = 'SELECT first_name, last_name FROM ' .
            $wpdb->prefix . 'ncoas_members WHERE email = %s';
            $user_data =(array) $wpdb->get_row($wpdb->prepare($query, $email));

            $first_name = $user_data['first_name'];
            $last_name = $user_data['last_name'];
        }


        $id_check_resetpw_text = '<div id="pw-reset-name-check"><span id="fn-entry">' . $first_name .'</span><span  id="ln-entry">' . $last_name . '</span></div>';

        return $id_check_resetpw_text;
    }

    //check identity
    public static function check_userprofile_identity_mqc() {
        $auth = BAuth::get_instance();
            if (!$auth->is_logged_in()) {
                return;
            }
        $user_data = (array) $auth->userData;

        $first_name = $user_data['first_name'];

        $id_check_text = '';

        if (BMembers::is_member_logged_in()) {

            $id_check_text .= ' <div id="mqc-userprofile-security-check" title="User Profile Security Check"><p>You\'re logged in as <span class="name">' . $first_name . '</span>. Not you? <a href=" '. get_permalink(BUtils::get_login_url()) .'?mmmm-logout=true"     title="Sign in">Sign in as a different user.</a> </p></div>';
        }else{
            $id_check_text .= '<div id="mqc-userprofile-security-check"  title="User Profile Security Check"> <p>'. '  ' .' </p></div>';
        }
        return $id_check_text;
    }


    public static function get_userprofile_text($is_bottom = false) {

        if(BUtils::get_saved_results_url() == get_the_ID()){
            return;
        }

        $setting = BSettings::get_instance();
        $signup_text = '';

        if($is_bottom){
            $signup_text = $setting->get_value('mqc-signup-text');
            $signup_text = '<div id="userprofile-bottom">'.nl2br($signup_text);
        }else{
            $signup_text = '<div id="userprofile-side"><p>Save Your Report on the Web</p>';
        }

        if (BMembers::is_member_logged_in()) {
            $signup_text .= '<div class="membership-center"><a class="button" href="'. get_permalink(BUtils::get_saved_results_url()).'?mmmm-report=true">Save this report</a></div></div>';
        }else{
            $signup_text .= '<div class="membership-center"><a class="button" href="'. get_permalink(BUtils::get_registration_url()).'?mmmm-report=true">Create an account</a><span class="or"> or </span><a href="'. get_permalink(BUtils::get_login_url()).'?mmmm-report=true" class="button">Log in to save</a></div></div>';
        }
        return $signup_text;
    }


    public static function get_url_ext() {

        $sid = filter_input(INPUT_GET, 'form_sid');
        $pid = filter_input(INPUT_GET, 'form_pid');
        $cid = filter_input(INPUT_GET, 'form_cid');

        $ext = '';
        if (!empty($sid)) {
            $ext = '?SID='.$sid;
            if (!empty($pid)&&!empty($cid)) {
                $ext .= "&PID=". $pid . "&CID=" . $cid;
            }
        }else{
            $sid  = isset( $_GET['SID'] ) ? sanitize_text_field( $_GET['SID'] ) : '';
            if (!empty($sid)) {
                $ext = '?SID='.$sid;
            }
            $cid = isset( $_GET['CID'] ) ? sanitize_text_field( $_GET['CID'] ) : '';
            $pid = isset( $_GET['PID'] ) ? sanitize_text_field( $_GET['PID'] ) : '';
            if (!empty($pid)&&!empty($cid)) {
                $ext .= "&PID=". $pid . "&CID=" . $cid;
            }
        }

        return $ext;
    }

    // return if remember me is checked or not
    public static function get_rememberme() {
        $checked = '';
        if(count($_POST) > 0){
            if(isset($_POST['rememberme'])){
                $checked = "checked='checked'";
            }
        }else if(isset( $_COOKIE['mmm-rememberme'] )){
            $checked = "checked='checked'";
        }
        return $checked;
    }

    //prepopulate email if remember me
    public static function get_login_email() {
        $email = '';
        if(count($_POST) > 0){
            $email = $_POST['swpm_email'];
        }else if(isset( $_COOKIE['mmm-rememberme'] )){
            $email = $_COOKIE['mmm-rememberme'];
        }
        return $email;
    }

    //update enrollment information
    public static function update_enrollment_information($enrollment_sdate, $enrollment_edate, $enrollment_mdate) {

        if($enrollment_sdate == "" || $enrollment_edate == "" || $enrollment_mdate == ""){
            return;
        }

        //update db
        global $wpdb;
        $auth = BAuth::get_instance();

        $wpdb->update(
            $wpdb->prefix . "ncoas_members",
            array(
                'enrollment_sdate' => $enrollment_sdate,
                'enrollment_edate' => $enrollment_edate,
                'enrollment_mdate' => $enrollment_mdate
            ),
            array('member_id' => $auth->get('member_id'))
        );

    }


    public static function get_subscriptions() {
        //get instance
        $sforce = BSforce::get_instance();

        //get subscriptions
        $mysubscriptions = $sforce->get_subscriptions();
        return $mysubscriptions;
    }

}

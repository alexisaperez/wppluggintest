<?php

include_once('class.bUtils.php');
include_once('class.bSettings.php');
include_once('class.bAuth.php');
include_once('class.bForm.php');
include_once('class.bTransfer.php');
include_once('class.bMessages.php');
include_once('class.bRegistration.php');
include_once('class.bFrontRegistration.php');
include_once('class.bAdminRegistration.php');
include_once('class.bSforce.php');


/**
 * Contains the functions for login, the profile form, edit profile, reset
 * password, saving mqc report, activating NCOA tabs depending on enviroment,
 * member management, registers all the associated files in the /css/ and /js/
 * directory.
 * @package Authentication
 */
class NcoasMembership {

    private $enable_debug;
    private $debug;

    public function __construct() {
        $this->debug = array();
        $this->enable_debug = false;
        $settings = BSettings::get_instance();
        $settings_value = $settings->get_value('enable-debug');
        if(isset($settings_value) && !empty($settings_value)){
            $this->enable_debug = true;
        }

        add_action('admin_menu', array(&$this, 'menu'));
        add_action('init', array(&$this, 'init'));

        add_shortcode("ncoa_userprofile_registration_form", array(&$this, 'registration_form'));
        add_shortcode('ncoa_userprofile_form', array(&$this, 'profile_form'));
        add_shortcode('ncoa_userprofile_login_form', array(&$this, 'login'));
        add_shortcode('ncoa_userprofile_reset_form', array(&$this, 'reset'));
        add_shortcode('ncoa_userprofile_temporary_form', array(&$this, 'temporary_reset'));

        add_shortcode('ncoa_userprofile_subscriptions', array(&$this, 'subscriptions'));
        add_shortcode('ncoa_userprofile_mqc', array(&$this, 'saved_mqc'));

        add_shortcode('ncoa_userprofile_tabs', array(&$this, 'ncoa_tabs'));
        add_shortcode('ncoa_manage_membership', array(&$this, 'ncoa_manage_membership'));

        add_action('admin_notices', array(&$this, 'notices'));
        add_action('wp_enqueue_scripts', array(&$this, 'front_library'));
        add_action('load-toplevel_page_simple_wp_membership', array(&$this, 'admin_library'));
        add_action('load-wp-membership_page_simple_wp_membership_levels', array(&$this, 'admin_library'));

        //init is too early for settings api.
        add_action('admin_init', array(&$this, 'admin_init_hook'));
        add_action('plugins_loaded', array(&$this, "plugins_loaded"));
        add_action('password_reset', array(&$this, 'wp_password_reset_hook'), 10, 2);
         ///avatar stuff
        add_filter('the_content', array(&$this, 'remove_empty_p'), 20, 1);

    }
// function avatar_toggle($avatar) {
//     $avatar_on = true;
//     if ()
//         //input is swpm-settings[avatar-toggle]
//         //type checkbox
//     // $islogo = ($attachment['islogo'] == 'on') ? '1' : '0';
//     // update_post_meta($post['ID'], '_islogo', $islogo);
//     return $avatar;
// }

    /**
     * Remove empty paragraphs created by wpautop()
     */
    function remove_empty_p( $content ) {
        $content = force_balance_tags( $content );
        $content = preg_replace( '#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $content );
        $content = preg_replace( '~\s?<p>(\s|&nbsp;)+</p>\s?~', '', $content );
        return $content;
    }

    function wp_password_reset_hook( $user, $pass )
    {
        $swpm_id = BUtils::get_user_by_user_name($user->user_login);
        if (!empty($swpm_id)){
            $password_hash = BUtils::encrypt_password($pass);
            global $wpdb;
            $wpdb->update($wpdb->prefix . "ncoas_members", array('password' => $password_hash), array('member_id' => $swpm_id));
        }
    }

    public function admin_init_hook(){
        BSettings::get_instance()->init_config_hooks();
        $addon_saved = filter_input(INPUT_POST, 'swpm-addon-settings');
        if(!empty($addon_saved)){
            do_action('swpm_addon_settings_save');
        }
    }

    public function shutdown(){
    }


    public function login() {
        ob_start();
        $auth = BAuth::get_instance();
        if ($auth->is_logged_in()){
            $setting = BSettings::get_instance();
            $profile_url = BUtils::get_profile_url();
            $temporary_url = BUtils::get_temporary_url();
            $get_saved_results_url = BUtils::get_saved_results_url();

            $mmmm_report = filter_input(INPUT_GET, 'mmmm-report');
            $ext = bUtils::get_url_ext();
            $user = (array) $auth->userData;

            if($user["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }
            if (!empty($mmmm_report)) {
                //save screening id
                $user_id = $user["member_id"];
                BMembers::update_screeningid($user_id);
                wp_redirect(get_permalink($get_saved_results_url).$ext);
                exit;
            }else{
                wp_redirect(get_permalink($profile_url).$ext);
                exit;
            }
        }
        else {
            include(ncoas_membership_PATH . 'views/login.php');
        }
        return ob_get_clean();
    }

    public function reset() {
        $succeeded = $this->notices();
        if($succeeded){
            return '';
        }
        ob_start();
        include(ncoas_membership_PATH . 'views/forgot_password.php');
        return ob_get_clean();
    }

    public function temporary_reset(){
        $auth = BAuth::get_instance();
        $this->notices();
        $email = isset( $_GET['email'] ) ? sanitize_text_field( $_GET['email'] ) : '';

        if ($auth->is_logged_in() || !empty($email)) {
            ob_start();
            include(ncoas_membership_PATH . 'views/reset_password.php');
            return ob_get_clean();
        }else{
            $ext = bUtils::get_url_ext();
            $login_url = BUtils::get_login_url();
            $login_url = get_permalink($login_url).$ext;
            //if (!headers_sent()) {
                wp_redirect($login_url);
                exit;
            //}
        }
    }

    public function profile_form() {
        $auth = BAuth::get_instance();
        $this->notices();
        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;

            $temporary_url = BUtils::get_temporary_url();
            if($user_data["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }

            if($this->enable_debug){
                $this->debug[] = $user_data;
            }else{
                $this->debug = array();
            }
            $args = array( 'data' => $user_data, 'debug' => $this->debug );
            ob_start();
            extract($args, EXTR_SKIP);
            include(ncoas_membership_PATH . 'views/edit.php');
            return ob_get_clean();
        }else{
            $this->not_valid();
        }

    }


    public function subscriptions() {
        $auth = BAuth::get_instance();
        $this->notices();
        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;
            $temporary_url = BUtils::get_temporary_url();
            if($user_data["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }
            ob_start();
            include(ncoas_membership_PATH . 'views/subscriptions.php');
            return ob_get_clean();
        }else{
            $this->not_valid();
        }
    }

    public function saved_mqc() {
        $auth = BAuth::get_instance();
        $this->notices();
        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;
            $temporary_url = BUtils::get_temporary_url();
            if($user_data["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }

            ob_start();
            include(ncoas_membership_PATH . 'views/saved_mqc.php');
            return ob_get_clean();
        }else{
            $this->not_valid();
        }
    }

    public function ncoa_tabs() {
        $auth = BAuth::get_instance();
        $this->notices();
        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;
            $temporary_url = BUtils::get_temporary_url();
            if($user_data["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }
            // ob_start();
            // include(ncoas_membership_PATH . 'views/subscriptions.php');
            // return ob_get_clean();
        }else{
            $this->not_valid();
        }
    }

    public function ncoa_manage_membership() {
        $auth = BAuth::get_instance();
        $this->notices();
        if ($auth->is_logged_in()) {
            $user_data = (array) $auth->userData;
            $temporary_url = BUtils::get_temporary_url();
            if($user_data["password_temp"] == 1){
                wp_redirect(get_permalink($temporary_url).$ext);
                exit;
            }

            $args = array( 'data' => $user_data, 'debug' => $this->debug );
            ob_start();
            extract($args, EXTR_SKIP);
            include(ncoas_membership_PATH . 'views/manage.php');
            return ob_get_clean();
        }else{
            $this->not_valid();
        }
    }


    public function not_valid() {
        //expired or not logged
        $auth = BAuth::get_instance();
        $status = $auth->get_message();
        $status = strtolower($status);
        $pos = strpos($status, "expired");
        session_start();

        if ($pos === false) {
            $_SESSION['not_logged'] = "You are not logged in to view this page, please sign in.";
        }

        $ext = bUtils::get_url_ext();
        $login_url = BUtils::get_login_url();
        $login_url = get_permalink($login_url).$ext;


        // $string = '<script type="text/javascript">';
        // $string .= 'window.location = "' . $login_url . '"';
        // $string .= '</script>';
        // echo $string;

        //if (!headers_sent()) {
            wp_redirect($login_url);
            exit;
        //}
    }

    public function notices() {
        $message = BTransfer::get_instance()->get('status');
        $succeeded = false;
        if (empty($message)) { return false;}
        if ($message['succeeded']) {
            echo "<div class='mmm_update' id='message' class='updated'>";
            $succeeded = true;
        } else{
            echo "<div class='mmm_update' id='message' class='error'>";
        }
        echo $message['message'];
        $extra = isset($message['extra']) ? $message['extra'] : array();
        if (is_string($extra)){
            echo $extra;
        }
        else if (is_array($extra)) {
            echo '<ul>';
            foreach ($extra as $key => $value){
                echo '<li>' . $value . '</li>';
            }
            echo '</ul>';
        }
        echo "</div>";
        return $succeeded;
    }


    public function admin_init() {
        $createswpmuser = filter_input(INPUT_POST, 'createswpmuser');
        if (!empty($createswpmuser)) {
            BAdminRegistration::get_instance()->register();
        }
        $editswpmuser = filter_input(INPUT_POST, 'editswpmuser');
        if (!empty($editswpmuser)) {
            $id = filter_input(INPUT_GET, 'member_id', FILTER_VALIDATE_INT);
            BAdminRegistration::get_instance()->edit($id);
        }
        $createswpmlevel = filter_input(INPUT_POST, 'createswpmlevel');
        if (!empty($createswpmlevel)) {
            BMembershipLevel::get_instance()->create();
        }
        $editswpmlevel = filter_input(INPUT_POST, 'editswpmlevel');
        if (!empty($editswpmlevel)) {
            $id = filter_input(INPUT_GET, 'id');
            BMembershipLevel::get_instance()->edit($id);
        }
    }



    public function init() {

        //Set up localisation. First loaded ones will override strings present in later loaded file.
        //Allows users to have a customized language in a different folder.
        $locale = apply_filters( 'plugin_locale', get_locale(), 'swpm' );
        load_textdomain( 'swpm', WP_LANG_DIR . "/swpm-$locale.mo" );

        if (!isset($_COOKIE['swpm_session'])) { // give a unique ID to current session.
            $uid = md5(microtime());
            $_COOKIE['swpm_session'] = $uid; // fake it for current session/
            setcookie('swpm_session', $uid, 0, '/');
        }

        if(current_user_can('manage_options')){ // admin stuff
            $this->admin_init();
        }

        if (!is_admin()){ //frontend stuff
            $auth = BAuth::get_instance();
            include_once(ncoas_membership_PATH . 'classes/class.bMembers.php');
            //$this->verify_and_delete_account();

            $mmmm_logout = filter_input(INPUT_GET, 'mmmm-logout');
            $mmmm_report = filter_input(INPUT_GET, 'mmmm-report');

            if (!empty($mmmm_logout)) {
                $ext = bUtils::get_url_ext();
                $url = get_permalink(bUtils::get_login_url());

                $auth->logout();

                //if (!headers_sent()) {
                    wp_redirect($url.$ext);
                    //header("Location: ".$url.$ext);
                    exit;
                //}
            }else if (!empty($mmmm_report)) {
                //add to report
                $http = "http";
                if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'){
                    $http = "https";
                }
                $link =  $http."://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];

                if(BUtils::get_saved_results_url() == url_to_postid( $link )){
                    $user = (array) $auth->userData;
                    $user_id = $user["member_id"];
                    BMembers::update_screeningid($user_id);
                }
            }

            $this->process_password_reset();
            $this->register_member();
            $this->edit_profile();
            $this->subscriptions_profile();
            $this->center_submit();
        }
    }

    public function process_password_reset() {
        $mmm_temporary = filter_input(INPUT_POST, 'mmm-temporary');
        $mmm_reset = filter_input(INPUT_POST, 'mmm-reset');
        $ncoa_reset_email = filter_input(INPUT_POST, 'ncoa_reset_email', FILTER_UNSAFE_RAW);

        if (!empty($mmm_reset)) {
            BFrontRegistration::get_instance()->reset_password($ncoa_reset_email);
        }
        if (!empty($mmm_temporary)) {
            BFrontRegistration::get_instance()->temporary_password();
        }
    }

    private function edit_profile() {
        $editprofile_submit = filter_input(INPUT_POST, 'editprofile_submit');
        if (!empty($editprofile_submit)) {
            BFrontRegistration::get_instance()->edit();
            $this->debug = BFrontRegistration::get_instance()->get_debug();
        }
    }

    private function subscriptions_profile() {
        $updatesubscriptions_submit = filter_input(INPUT_POST, 'updatesubscriptions_submit');
        if (!empty($updatesubscriptions_submit)) {
            BFrontRegistration::get_instance()->update_subscriptions();
            $this->debug = BFrontRegistration::get_instance()->get_debug();
        }
    }

    private function center_submit() {
      $centeraddress_submit = filter_input(INPUT_POST, 'centeraddress_submit');
      $centerprimary_submit = filter_input(INPUT_POST, 'centerprimary_submit');
      $centeruser_submit = filter_input(INPUT_POST, 'centeruser_submit');
      $centeruserdelete_submit = filter_input(INPUT_POST, 'centeruserdelete_submit');
      if (!empty($centeraddress_submit)) {
        BFrontRegistration::get_instance()->edit_center($_POST);
      }
      if (!empty($centerprimary_submit)) {
        BFrontRegistration::get_instance()->edit_center_primary($_POST);
      }
      if (!empty($centeruser_submit)) {//add users that are not primary
        BFrontRegistration::get_instance()->edit_center_users($_POST);
      }
       if (!empty($centeruserdelete_submit)) {//flag as moved on users that are not primary
        BFrontRegistration::get_instance()->delete_center_users($_POST);
      }
    }

    public function admin_library() {
        $this->common_library();
        wp_enqueue_style('jquery.tools.dateinput', ncoas_membership_URL . '/css/jquery.tools.dateinput.css');
        wp_enqueue_script('jquery.tools', ncoas_membership_URL . '/js/jquery.tools18.min.js');
    }

    public function front_library() {
        $this->common_library();
    }

    private function common_library() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('swpm.common', ncoas_membership_URL . '/css/ncoas.common.css');
        wp_enqueue_script('jquery.ncoas_userprofile_scripts', ncoas_membership_URL . '/js/jquery.ncoas_userprofile_scripts.js');
        wp_enqueue_script('jquery.ui.ncoa', ncoas_membership_URL . '/js/jquery-ui.min.js');
        wp_enqueue_script('ncoas-manage', ncoas_membership_URL . '/js/ncoas-manage.js');
    }

    public function registration_form($atts) {
        $succeeded = $this->notices();
        if($succeeded){
            return;
        }
        return BFrontRegistration::get_instance()->registration_ui();
    }

    private function register_member() {
        $registration = filter_input(INPUT_POST, 'registration');
        if (!empty($registration)){
            BFrontRegistration::get_instance()->register();
            $this->debug = BFrontRegistration::get_instance()->get_debug();
        }
    }

    public function menu() {
        $menu_parent_slug = 'ncoa_membership';

        add_menu_page(__("NCOA User Profile", 'swpm'), __("NCOA User Profile", 'swpm')
                , 'manage_options', $menu_parent_slug, array(&$this, "admin_members")
                , ncoas_membership_URL . '/images/logo.png');
        add_submenu_page($menu_parent_slug, __("Members", 'swpm'), __('Members', 'swpm'),
                'manage_options', 'ncoa_membership', array(&$this, "admin_members"));
        add_submenu_page($menu_parent_slug, __("Settings", 'swpm'), __("Settings", 'swpm'),
                'manage_options', 'ncoa_membership_settings', array(&$this, "admin_settings"));
        do_action('swpm_after_main_admin_menu', $menu_parent_slug);
    }

    public function admin_members() {
        include_once(ncoas_membership_PATH . 'classes/class.bMembers.php');
        $members = new BMembers();
        $action = filter_input(INPUT_GET, 'member_action');
        $action = empty($action)? filter_input(INPUT_POST, 'action') : $action;
        switch ($action) {
            case 'add':
            case 'edit':
                $members->process_form_request();
                break;
            case 'delete':
            case 'bulk_delete':
                $members->delete();
            default:
                $members->show();
                break;
        }
    }

    public function admin_settings() {
        $current_tab = BSettings::get_instance()->current_tab;
        switch ($current_tab) {
            case 8:
                include(ncoas_membership_PATH . 'views/admin_addons.php');
                break;
            default:
                include(ncoas_membership_PATH . 'views/admin_settings.php');
                break;
        }
    }

    public function plugins_loaded(){
        //Runs when plugins_loaded action gets fired
        if(is_admin()){
            //Check and run DB upgrade operation (if needed)
            if (get_option('swpm_db_version') != ncoas_membership_DB) {
                include_once('class.bInstallation.php');
                BInstallation::run_safe_installer();
            }
        }
    }

    public static function activate() {
        include_once('class.bInstallation.php');
        BInstallation::run_safe_installer();
    }

    public function deactivate() {
    }

    private function verify_and_delete_account(){
        include_once(ncoas_membership_PATH . 'classes/class.bMembers.php');
        $delete_account = filter_input(INPUT_GET, 'delete_account');
        if (empty($delete_account)) {return; }
        $password = filter_input(INPUT_POST, 'account_delete_confirm_pass',FILTER_UNSAFE_RAW);

        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()){return;}
        if (empty($password)){
            BUtils::account_delete_confirmation_ui();
        }

        $nonce_field = filter_input(INPUT_POST, 'account_delete_confirm_nonce');
        if (empty($nonce_field) || !wp_verify_nonce($nonce_field, 'swpm_account_delete_confirm')){
            BUtils::account_delete_confirmation_ui(BUtils::_("Sorry, Nonce verification failed."));
        }
        if ($auth->match_password($password)){
            $auth->delete();
            wp_redirect(home_url());
            exit;
        }
        else{
            BUtils::account_delete_confirmation_ui(BUtils::_("Sorry, Password didn't match."));
        }
    }

}

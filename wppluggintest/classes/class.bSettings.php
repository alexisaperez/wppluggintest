<?php

/**
 * This handles the setup of the Admin interface, creating tabs, default content
 * pages upon installation and activation of the plugin, sets up general
 * settings in the dashboard to manage the plugin options.
 */
class BSettings {

    private static $_this;
    private $settings;
    public $current_tab;
    private $tabs;
    private function __construct() {
        $this->settings = (array) get_option('swpm-settings');
        // echo "<pre>";
        // var_dump($this->settings);
        // echo "</pre>";
    }
    public function init_config_hooks(){
        $page = filter_input(INPUT_GET, 'page');
//        if($page == 'ncoa_membership_settings'){
        if(is_admin()){ // for frontend just load settings but dont try to render settings page.
            $tab = filter_input(INPUT_GET, 'tab');
            $tab = empty($tab)?filter_input(INPUT_POST, 'tab'):$tab;
            $this->current_tab = empty($tab) ? 1 : $tab;
             $this->tabs = array(1=> 'General Settings', 2=> 'Email Settings', 3=> 'Custom Settings');
            add_action('swpm-draw-tab', array(&$this, 'draw_tabs'));
            $method = 'tab_' . $this->current_tab;
            if (method_exists($this, $method)){
                $this->$method();
            }
        }
    }

    private function tab_1() {

        register_setting('swpm-settings-tab-1', 'swpm-settings', array(&$this, 'sanitize_tab_1'));

        //This settings section has no heading
        add_settings_section('swpm-general-post-submission-check', '',
                array(&$this, 'swpm_general_post_submit_check_callback'), 'ncoa_membership_settings');

        add_settings_field('membership-title', BUtils::_('Membership Pages Title'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-title',
                      'message'=>''));

        add_settings_field('membership-pwd', BUtils::_('SalesForce Password'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-pwd',
                      'message'=>''));

        add_settings_field('membership-token', BUtils::_('SalesForce Token'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-token',
                      'message'=>''));

        add_settings_field('membership-acct', BUtils::_('Membership Account'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-acct',
                      'message'=>'SalesForce Default Membership Account '));

        add_settings_field('membership-test', BUtils::_('SalesForce Sandbox?'),
                array(&$this, 'checkbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-test',
                      'message'=>'Is this a test sandbox?'));

        add_settings_field('avatar-toggle', BUtils::_('Enable Avatar'),
                array(&$this, 'checkbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'avatar-toggle',
                      'message'=>'Check to enable avatar for MMM Environment'));

        add_settings_field('first-time-login-toggle', BUtils::_('Allow "First Time Login" options.'),
                array(&$this, 'checkbox_callback'), 'ncoa_membership_settings',
                'pages-settings',
                array('item' => 'first-time-login-toggle',
                      'message'=>'Enable the "First time logging in?" link on the login page.'));

        add_settings_section('pages-settings', BUtils::_('Pages Settings'),
                array(&$this, 'pages_settings_callback'), 'ncoa_membership_settings');

        add_settings_field('login-page-url', BUtils::_('Login Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'login-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_login_form]</code>'));

        add_settings_field('no-account-page-url', BUtils::_('"No Account" Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'no-account-page-url',
                      'message'=>'Content to display when a user without a username attempts to login.'));

        add_settings_field('registration-page-url', BUtils::_('Registration Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'registration-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_registration_form]</code>'));

        add_settings_field('profile-page-url', BUtils::_('Profile Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'profile-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_form]</code>'));

        add_settings_field('reset-page-url', BUtils::_('Password Reset Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'reset-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_reset_form]</code>'));

        add_settings_field('temporary-page-url', BUtils::_('Temporary Pwd Reset Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'temporary-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_temporary_form]</code>'));


        add_settings_field('terms-url', BUtils::_('Terms of Use Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'terms-url',
                      'message'=>''));
        add_settings_field('privacy-url', BUtils::_('Privacy Policy Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'privacy-url',
                      'message'=>''));

        add_settings_field('contact-url', BUtils::_('Contact Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'contact-url',
                      'message'=>''));



        add_settings_field('membership-timeout', BUtils::_('Session Timeout'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-timeout',
                      'message'=>'in minutes'));


        add_settings_field('membership-pwd-length', BUtils::_('Password Length'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'pages-settings',
                array('item' => 'membership-pwd-length',
                      'message'=>''));


        add_settings_section('debug-settings', BUtils::_('Debug Settings'),
                array(&$this, 'testndebug_settings_callback'), 'ncoa_membership_settings');
        add_settings_field('enable-debug', 'Enable Debug',
                array(&$this, 'checkbox_callback'), 'ncoa_membership_settings', 'debug-settings',
                array('item' => 'enable-debug',
                      'message'=> ''));

    }

    private function tab_2() {
        register_setting('swpm-settings-tab-2', 'swpm-settings', array(&$this, 'sanitize_tab_2'));

        add_settings_section('email-settings', BUtils::_('Email Settings'),
                array(&$this, 'email_settings_callback'), 'ncoa_membership_settings');
        add_settings_field('email-name', BUtils::_('From Email Name'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'email-settings',
                array('item' => 'email-name',
                    'message'=>''));
        add_settings_field('email-from', BUtils::_('From Email Address'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'email-settings',
                array('item' => 'email-from',
                    'message'=>''));

        add_settings_section('reg-email-settings', BUtils::_('Registration Complete'),
                array(&$this, 'reg_email_settings_callback'), 'ncoa_membership_settings');
        add_settings_field('reg-complete-mail-subject', BUtils::_('Email Subject'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'reg-email-settings',
                array('item' => 'reg-complete-mail-subject',
                      'message'=>''));
        add_settings_field('reg-complete-mail-body', BUtils::_('Email Body'),
                array(&$this, 'textarea_callback'), 'ncoa_membership_settings', 'reg-email-settings',
                array('item' => 'reg-complete-mail-body',
                      'message'=>''));

        add_settings_section('reset-email-settings', BUtils::_('Reset Password'),
                array(&$this, 'reset_email_settings_callback'), 'ncoa_membership_settings');
        add_settings_field('reset-mail-subject', BUtils::_('Email Subject'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'reset-email-settings',
                array('item' => 'reset-mail-subject',
                      'message'=>''));
        add_settings_field('reset-mail-body', BUtils::_('Email Body'),
                array(&$this, 'textarea_callback'), 'ncoa_membership_settings', 'reset-email-settings',
                array('item' => 'reset-mail-body',
                      'message'=>''));

        add_settings_section('change-email-settings', BUtils::_('Change Temporary Password'),
                array(&$this, 'change_email_settings_callback'), 'ncoa_membership_settings');
        add_settings_field('change-email-subject', BUtils::_('Email Subject'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'change-email-settings',
                array('item' => 'change-email-subject',
                      'message'=>''));
        add_settings_field('change-email-body', BUtils::_('Email Body'),
                array(&$this, 'textarea_callback'), 'ncoa_membership_settings', 'change-email-settings',
                array('item' => 'change-email-body',
                      'message'=>''));


    }

    private function tab_3() {

        register_setting('swpm-settings-tab-3', 'swpm-settings', array(&$this, 'sanitize_tab_3'));

        /* MMM Custom settings */
        add_settings_section('mmm-settings', BUtils::_('MMM Settings'),
                array(&$this, 'mmm_settings_callback'), 'ncoa_membership_settings');

        add_settings_field('mmm-domain', BUtils::_('Domain name'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'mmm-settings',
                array('item' => 'mmm-domain',
                      'message'=>''));

        add_settings_field('subscription-page-url', BUtils::_('Email subscriptions Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'mmm-settings',
                array('item' => 'subscription-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_subscriptions]</code>'));

        add_settings_field('mqc-saved-results-page-url', BUtils::_('MQC saved results Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'mmm-settings',
                array('item' => 'mqc-saved-results-page-url',
                      'message'=>'use shortcode <code>[ncoa_userprofile_mqc]</code>'));

        add_settings_field('mqc-page-url', BUtils::_('MQC Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'mmm-settings',
                array('item' => 'mqc-page-url',
                      'message'=>''));

        add_settings_field( 'mqc-signup-text', BUtils::_('MQC signup text'),
            array(&$this, 'wysiwygbox_callback'), 'ncoa_membership_settings', 'mmm-settings',
                array('item' => 'mqc-signup-text',
                      'message'=>'') );

        /* NCOA Custom settings */
        add_settings_section('nisc-settings', BUtils::_('NISC Settings'),
                array(&$this, 'ncoa_settings_callback'), 'ncoa_membership_settings');

        add_settings_field('nisc-domain', BUtils::_('Domain name'),
                array(&$this, 'textfield_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'nisc-domain',
                      'message'=>''));

        add_settings_field('mng-page-url', BUtils::_('Manager Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'mng-page-url',
                      'message'=>'use shortcode <code>[ncoa_manage_membership]</code>'));

        add_settings_field('benefits-page-url', BUtils::_('Benefits Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'benefits-page-url',
                      'message'=>''));

        add_settings_field('accreditation-page-url', BUtils::_('Accreditation Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'accreditation-page-url',
                      'message'=>''));

        add_settings_field('opportunities-page-url', BUtils::_('Opportunities Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'opportunities-page-url',
                      'message'=>''));

        add_settings_field('news-page-url', BUtils::_('News Page'),
                array(&$this, 'pages_selectbox_callback'), 'ncoa_membership_settings', 'nisc-settings',
                array('item' => 'news-page-url',
                      'message'=>''));

    }

    public static function get_instance() {
        self::$_this = empty(self::$_this) ? new BSettings() : self::$_this;
        return self::$_this;
    }

    public function wysiwygbox_callback($args){
        $item = $args['item'];
        $text = $this->get_value($item);
        $args = array("textarea_rows" => 5, "textarea_name" => "swpm-settings[".$item."]", 'media_buttons' => false );
        wp_editor($text, $item, $args);

    }

    public function selectbox_callback($args){
        $item = $args['item'];
        $options = $args['options'];
        $default = $args['default'];
        $msg = isset($args['message'])?$args['message']: '';
        $selected = esc_attr($this->get_value($item), $default);
        echo "<select name='swpm-settings[" . $item . "]' >";
        foreach($options as $key => $value){
            $is_selected = ($key == $selected)? 'selected="selected"': '';
            echo '<option ' . $is_selected . ' value="'. esc_attr($key) . '">' . esc_attr($value) . '</option>';
        }
        echo '</select>';
        echo '<br/><i>'.$msg.'</i>';
    }
    // Fields
    static function pages_selectbox_callback( $args ) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $options = get_option( 'swpm-settings' );
        $selected = isset( $options[$item] ) ? $options[$item] : '';

        $dd_args = array( 'child_of' => 0, 'depth' => 0, 'echo' => 1, 'name' => "swpm-settings[" . $item . "]", 'selected' => $selected, 'show_option_none' => 'Please Select' );
        wp_dropdown_pages( $dd_args );
        echo '<br/><i>'.$msg.'</i>';

    }

    public function checkbox_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $is = esc_attr($this->get_value($item));
        echo "<input type='checkbox' $is name='swpm-settings[" . $item . "]' value=\"checked='checked'\" />";
        echo '<br/><i>'.$msg.'</i>';
    }

    public function textarea_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $text = esc_attr($this->get_value($item));
        echo "<textarea name='swpm-settings[" . $item . "]'  rows='6' cols='60' >" . $text . "</textarea>";
        echo '<br/><i>'.$msg.'</i>';
    }

    public function textfield_small_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $text = esc_attr($this->get_value($item));
        echo "<input type='text' name='swpm-settings[" . $item . "]'  size='5' value='" . $text . "' />";
        echo '<br/><i>'.$msg.'</i>';
    }

    public function textfield_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $text = esc_attr($this->get_value($item));
        echo "<input type='text' name='swpm-settings[" . $item . "]'  size='50' value='" . $text . "' />";
        echo '<br/><i>'.$msg.'</i>';
    }

    public function textfield_long_callback($args) {
        $item = $args['item'];
        $msg = isset($args['message'])?$args['message']: '';
        $text = esc_attr($this->get_value($item));
        echo "<input type='text' name='swpm-settings[" . $item . "]'  size='100' value='" . $text . "' />";
        echo '<br/><i>'.$msg.'</i>';
    }

    public function swpm_documentation_callback() {
    }

    public function swpm_general_post_submit_check_callback(){

        //Show settings updated message
        if(isset($_REQUEST['settings-updated'])){
            echo '<div id="message" class="updated fade"><p>' . BUtils::_('Settings updated!') . '</p></div>';
        }
    }

    public function general_settings_callback() {
        BUtils::e('General Plugin Settings.');
    }

    public function pages_settings_callback() {
        BUtils::e('Page Setup and URL Related settings.');
    }
    public function testndebug_settings_callback(){
        BUtils::e('Testing and Debug Related Settings.');
    }
    public function reg_email_settings_callback() {
        BUtils::e('This email will be sent to your users when they complete the account creation process.');
    }
    public function email_settings_callback(){
        BUtils::e('Settings in this section apply to all emails.');
    }
    public function reset_email_settings_callback() {
        BUtils::e('This email will be sent to your users after reset password request.');
    }
    public function change_email_settings_callback() {
        BUtils::e('This email will be sent to your users after change the temporary password.');
    }
    public function reg_prompt_email_settings_callback() {
        BUtils::e('This email will be sent to prompt user to complete registration.');
    }
    public function advanced_settings_callback(){
        BUtils::e('This page allows you to configure some advanced features of the plugin.');
    }
    public function mmm_settings_callback(){
        BUtils::e('Additional information needed for MMM website.');
    }
    public function ncoa_settings_callback(){
        BUtils::e('Additional information needed for NCOA website.');
    }

    public function sanitize_tab_1($input) {
        if (empty($this->settings)){
            $this->settings = (array) get_option('swpm-settings');
        }
        $output = $this->settings;
        //general settings block

        $output['avatar-toggle'] = isset($input['avatar-toggle'])
          ? esc_attr($input['avatar-toggle']) : "";
        $output['first-time-login-toggle'] =
          isset($input['first-time-login-toggle'])
          ? esc_attr($input['first-time-login-toggle']) : "";
        $output['enable-sandbox-testing'] =
          isset($input['enable-sandbox-testing'])
          ? esc_attr($input['enable-sandbox-testing']) : "";
        $output['membership-title'] = $input['membership-title'];
        $output['membership-pwd'] = $input['membership-pwd'];
        $output['membership-token'] = $input['membership-token'];
        $output['membership-acct'] = $input['membership-acct'];
        $output['membership-test'] = $input['membership-test'];
        $output['login-page-url'] = $input['login-page-url'];
        $output['no-account-page-url'] = $input['no-account-page-url'];
        $output['registration-page-url'] = $input['registration-page-url'];
        $output['profile-page-url'] = $input['profile-page-url'];
        $output['reset-page-url'] = $input['reset-page-url'];
        $output['temporary-page-url'] = $input['temporary-page-url'];

        $output['terms-url'] = $input['terms-url'];
        $output['privacy-url'] = $input['privacy-url'];
        $output['contact-url'] = $input['contact-url'];
        $output['membership-timeout'] = $input['membership-timeout'];
        $output['membership-pwd-length'] = $input['membership-pwd-length'];
        //$output['mqc-signup-text'] = $input['mqc-signup-text'];
        $output['enable-debug'] = isset($input['enable-debug'])
          ? esc_attr($input['enable-debug']) : "";

        return $output;
    }

    public function sanitize_tab_2($input) {
        if (empty($this->settings)){
            $this->settings = (array) get_option('swpm-settings');
        }
        $output = $this->settings;

        $output['email-name'] = trim($input['email-name']);
        $output['email-from'] = trim($input['email-from']);
        $output['reg-complete-mail-subject'] = sanitize_text_field($input['reg-complete-mail-subject']);
        $output['reg-complete-mail-body'] = wp_kses_data(force_balance_tags($input['reg-complete-mail-body']));
        $output['reset-mail-subject'] = sanitize_text_field($input['reset-mail-subject']);
        $output['reset-mail-body'] = wp_kses_data(force_balance_tags($input['reset-mail-body']));
        $output['change-email-subject'] = sanitize_text_field($input['change-email-subject']);
        $output['change-email-body'] = wp_kses_data(force_balance_tags($input['change-email-body']));

        return $output;
    }

    public function sanitize_tab_3($input) {
        if (empty($this->settings)){
            $this->settings = (array) get_option('swpm-settings');
        }
        $output = $this->settings;
        //custom settings block
        //MMM
        $output['mmm-domain'] = $input['mmm-domain'];
        $output['subscription-page-url'] = $input['subscription-page-url'];
        $output['mqc-saved-results-page-url'] = $input['mqc-saved-results-page-url'];
        $output['mqc-page-url'] = $input['mqc-page-url'];
        $output['mqc-signup-text'] = $input['mqc-signup-text'];

        //NISC
        $output['nisc-domain'] = $input['nisc-domain'];
        $output['mng-page-url'] = $input['mng-page-url'];
        $output['benefits-page-url'] = $input['benefits-page-url'];
        $output['accreditation-page-url'] = $input['accreditation-page-url'];
        $output['opportunities-page-url'] = $input['opportunities-page-url'];
        $output['news-page-url'] = $input['news-page-url'];

        return $output;
    }

    public function get_value($key, $default = "") {
        if (isset($this->settings[$key])){
            return $this->settings[$key];
        }
        return $default;
    }

    public function set_value($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    public function save() {
        update_option('swpm-settings', $this->settings);
    }

    public function draw_tabs() {
        $current = $this->current_tab;
        ?>
        <h3 class="nav-tab-wrapper">
            <?php foreach ($this->tabs as $id=>$label):?>
            <a class="nav-tab <?php echo ($current == $id) ? 'nav-tab-active' : ''; ?>" href="admin.php?page=ncoa_membership_settings&tab=<?php echo  $id?>"><?php echo  $label?></a>
            <?php endforeach;?>
        </h3>
        <?php
    }


}

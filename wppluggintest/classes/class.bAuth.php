<?php
/**
 * The basic authentication hooks for members to log in.
 * @package Authentication
 */
class BAuth {

    public $protected;
    public $permitted;
    private $isLoggedIn;
    private $isTemporary;
    private $lastStatusMsg;
    private static $_this;
    public $userData;

    private function __construct() {
        $this->isLoggedIn = false;
        $this->isTemporary = false;
        $this->userData = null;
    }
    private function init(){
        $valid = $this->validate();
        if (!$valid){
            $this->authenticate();
        }
    }
    public static function get_instance() {
        if (empty(self::$_this)){
            self::$_this = new BAuth();
            self::$_this->init();
        }
        return self::$_this;
    }


    //edited function commented above for mmm, below is new for mmm
    private function authenticate($email = null, $pass = null) {
        global $wpdb;
        $settings = BSettings::get_instance();
        $firstTimeLoginOn = $settings->get_value('first-time-login-toggle', FALSE);
        $mmmm_password = empty($pass)?filter_input(INPUT_POST, 'swpm_password') : $pass;
        $mmmm_email = empty($email)? apply_filters('swpm_email', filter_input(INPUT_POST, 'swpm_email')) : $email;
        if (isset($_POST['first-time-login'])) {
          $first_login = $_POST['first-time-login'] === 'true';
        } else {
          $first_login = FALSE;
        }
        $email = sanitize_user($mmmm_email);
        $pass = trim($mmmm_password);

        // As long as we have a value for the email address, we'll
        // pull back any matching records from the WP database.
        if (!empty($mmmm_email)) {
          $query = "SELECT * FROM "
            . $wpdb->prefix
            . "ncoas_members WHERE email = %s";
          $userData = $wpdb->get_row($wpdb->prepare($query, $email));
        }

        // If it's not the "first login attempt" by an account, and there's
        // no username or password, we'll just return false here.
        if (!($firstTimeLoginOn && $first_login) && (empty($mmmm_email) || empty($mmmm_password))) {
          return false;
        }

        // The user has indicated that this is their first login attempt.
        if (($firstTimeLoginOn && $first_login) && !empty($mmmm_email)) {
          // User already has an account in WP.
          if (isset($userData)) {
            $this->lastStatusMsg = BUtils::_(
              "You already have an account. Try logging in!"
            );
            return false;
          } else {
            $response = BSforce::get_instance()->get_user_by_email(
              $mmmm_email
            );
            // User doesn't have an account in WP or SF
            if ($response === FALSE) {
              $pg = $settings->get_value('no-account-page-url', FALSE);
              if ($pg !== FALSE) {
                wp_redirect( get_permalink($pg) );
                exit;
              } else {
                $this->lastStatusMsg = BUtils::_(
                  "No such account. Please contact your administrator."
                );
              }
              return FALSE;
            }

            // Looks like the user has an SF account, but not a WP account.
            // @NOTE I'm not sure if this is the right email to send out!
            $settings = BSettings::get_instance();
            $subject = $settings->get_value('reg-complete-mail-subject');
            $body = $settings->get_value('reg-complete-mail-body');
            $from_address = $settings->get_value('email-from');
            $from_name = $settings->get_value('email-name');
            $login_link = get_permalink($settings->get_value('login-page-url'));
            $headers = 'From: ' . $from_name . " <" . $from_address . ">\r\n";
            $member_info['login_link'] = $login_link;
            $values = array_values($member_info);
            $user = BSforce::get_instance()->get_user_by_email(
              $_POST['swpm_email']
            );
            $keys = array_map('swpm_enclose_var', array_keys($member_info));
            $body = str_replace(
              array("{first_name} ", "{last_name}", "{login_link}"),
              array($user->Name, "", $login_link),
              $body
            );
            $email = sanitize_email($user->Email);
            wp_mail(trim($email), $subject, $body, $headers);
            $this->lastStatusMsg = BUtils::_(
              "Please check your email to complete the signup process."
            );
            return FALSE;
          }
        }



        if(!isset($userData)){
            $this->isLoggedIn = false;
            $this->isTemporary = false;
            $this->userData = null;
            $this->lastStatusMsg = BUtils::_("User not found.");
            return false;
        }

        //get additional userdata from Sales force: zip, birth_month and birth_date
        $tempData = (array)$userData;
        $sforce = BSforce::get_instance();
        $result = $sforce->get_additional_userdata($tempData);

        //set additional values
        if(isset($result['Contact_Zipcode__c']) && !empty($result['Contact_Zipcode__c'])){
            $userData->zip = $result['Contact_Zipcode__c'];
        }else{
            $userData->zip = '';
        }
        if(isset($result['Birth_Month__c']) && !empty($result['Birth_Month__c'])){
            $userData->birth_month = intval($result['Birth_Month__c']);
            $userData->birth_year = intval($result['Birth_Year__c']);
        }else{
            $userData->birth_month = '';
            $userData->birth_year = '';
        }
        if(isset($result['related']) && !empty($result['related'])){
            $userData->related = $result['related'];
        }else{
            $userData->related = '';
        }

        $this->userData = $userData;
        if (!$userData) {
            $this->isLoggedIn = false;
            $this->isTemporary = false;
            $this->userData = null;
            $this->lastStatusMsg = BUtils::_("Email Not Found.");
            return false;
        }
        $check = $this->check_password($pass, $userData->password);
        if (!$check) {
            $this->isLoggedIn = false;
            $this->isTemporary = false;
            $this->userData = null;
            $this->lastStatusMsg = BUtils::_("Password Empty or Invalid.");
            return false;
        }
        if ($this->check_constraints()) {
            $rememberme = filter_input(INPUT_POST, 'rememberme');
            $remember = empty($rememberme) ? 0 : 1;
            $this->set_cookie($remember);
            $this->isLoggedIn = true;
            if($userData->password_temp == 1){
                $this->isTemporary = true;
            }
            $this->lastStatusMsg = "Logged In.";
            //do_action('swpm_login', $email, $pass, $remember);
            return true;
        }
    }

    private function check_constraints() {
        if (empty($this->userData)){
            return false;
        }
        $this->isLoggedIn = true;
        return true;
    }

    public function check_password($password, $hash) {
        global $wp_hasher;
        if (empty($password)){
            return false;
        }
        if (empty($wp_hasher)) {
            require_once( ABSPATH . 'wp-includes/class-phpass.php');
            $wp_hasher = new PasswordHash(8, TRUE);
        }
        return $wp_hasher->CheckPassword($password, $hash);
    }

    public function match_password($password){
        if (!$this->is_logged_in()) {return false;}
        return $this->check_password($password, $this->get('password'));
    }

    public function login($email, $pass, $remember = '', $secure = '') {
        //Blog::log_simple_debug("login",true);
        if ($this->isLoggedIn){
            return;
        }
        if ($this->authenticate($email, $pass) && $this->validate()) {
            $this->set_cookie($remember, $secure);
        } else {
            $this->isLoggedIn = false;
            $this->isTemporary = false;
            $this->userData = null;
        }
        return $this->lastStatusMsg;
    }


    public function logout() {
        if (!$this->isLoggedIn){
            return;
        }

        setcookie(ncoas_membership_AUTH, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(ncoas_membership_SEC_AUTH, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        $this->userData = null;
        $this->isLoggedIn = false;
        $this->isTemporary = false;
        $this->lastStatusMsg = BUtils::_("Logged Out Successfully.");
        session_start();
        $_SESSION["not_logged"] = BUtils::_("Logged Out Successfully.");
    }


    public function set_cookie($remember = '', $secure = '') {
        $settings = BSettings::get_instance();
        $timeout = $settings->get_value('membership-timeout');
        $expiration = time() + (60 * $timeout); //timeout in seconds

        if($remember == 1){
            //add remember cookie
            setcookie('mmm-rememberme', $this->userData->email , $expiration);
        }else{
            setcookie('mmm-rememberme', '', time()-3600);
        }

        // make sure cookie doesn't live beyond account expiration date.
        // but if expired account login is enabled then ignore if account is expired
        $pass_frag = substr($this->userData->password, 8, 4);
        $scheme = 'auth';
        if (!$secure){
            $secure = is_ssl();
        }
        $key = BAuth::b_hash($this->userData->email . $pass_frag . '|' . $expiration, $scheme);
        $hash = hash_hmac('md5', $this->userData->email . '|' . $expiration, $key);
        $auth_cookie = $this->userData->email . '|' . $expiration . '|' . $hash;
        $auth_cookie_name = $secure ? ncoas_membership_SEC_AUTH : ncoas_membership_AUTH;
        setcookie($auth_cookie_name, $auth_cookie, 0, COOKIEPATH, COOKIE_DOMAIN, $secure, true);

        //check if cookie for ZIP and screening_id exists
        if(!isset( $_COOKIE['mqc_zip'] ) && isset($this->userData->zip)){
            //set zip
            setcookie( 'mqc_zip', $this->userData->zip, 0, '/' );
        }
        if(!isset( $_COOKIE['screening_id'] )){
            //set screening_id
            setcookie( 'screening_id', $this->userData->screening_id, 0, '/' );
        }
        if(!isset( $_COOKIE['enrollment_startdate'] )){
            //set  enrollment dates
            $enrollment_sdate = $this->userData->enrollment_sdate;
            $enrollment_edate = $this->userData->enrollment_edate;
            $enrollment_mdate = $this->userData->enrollment_mdate;

            if(isset($enrollment_sdate) && !empty($enrollment_sdate)){
                setcookie( 'enrollment_startdate', $enrollment_sdate, 0, '/');
                setcookie( 'enrollment_enddate', $enrollment_edate, 0, '/');
                setcookie( 'mqc_birth_month', $enrollment_mdate, 0, '/');
            }

        }

    }

    private function validate() {
        $auth_cookie_name = is_ssl() ? ncoas_membership_SEC_AUTH : ncoas_membership_AUTH;
        if (!isset($_COOKIE[$auth_cookie_name]) || empty($_COOKIE[$auth_cookie_name])){
            return false;
        }
        $cookie_elements = explode('|', $_COOKIE[$auth_cookie_name]);
        if (count($cookie_elements) != 3){
            return false;
        }
        list($email, $expiration, $hmac) = $cookie_elements;

        // Quick check to see if an honest cookie has expired
        if ($expiration < time()) {
            $this->lastStatusMsg = BUtils::_("Your Session has Expired.");
            //do_action('auth_cookie_expired', $cookie_elements);
            return false;
        }
        //Blog::log_simple_debug("validate:Session Expired",true);
        global $wpdb;
        $query = " SELECT * FROM " . $wpdb->prefix . "ncoas_members WHERE email = %s";
        $user = $wpdb->get_row($wpdb->prepare($query, $email));
        if (empty($user)) {
            $this->lastStatusMsg = BUtils::_("Invalid Email");
            return false;
        }

        //get additional userdata from Sales force: zip, birth_month and birth_date
        $tempData = (array)$user;
        $sforce = BSforce::get_instance();
        $result = $sforce->get_additional_userdata($tempData);
        //set additional values
        if(isset($result['Contact_Zipcode__c']) && !empty($result['Contact_Zipcode__c'])){
            $user->zip = $result['Contact_Zipcode__c'];
        }else{
            $user->zip = '';
        }
        if(isset($result['Birth_Month__c']) && !empty($result['Birth_Month__c'])){
            $user->birth_month = intval($result['Birth_Month__c']);
            $user->birth_year = intval($result['Birth_Year__c']);
        }else{
            $user->birth_month = '';
            $user->birth_year = '';
        }

        if(isset($result['related']) && !empty($result['related'])){
            $user->related = $result['related'];
        }else{
            $user->related = '';
        }

        //Blog::log_simple_debug("validate:Invalid User ID:" . serialize($user),true);
        $pass_frag = substr($user->password, 8, 4);
        $key = BAuth::b_hash($email . $pass_frag . '|' . $expiration);
        $hash = hash_hmac('md5', $email . '|' . $expiration, $key);

        if ($hmac != $hash) {
            //$this->lastStatusMsg = BUtils::_("Sorry! Something went wrong");
            return false;
        }
        if ($expiration < time()){
            $GLOBALS['login_grace_period'] = 1;
        }
        $this->userData = $user;
        return $this->check_constraints();
    }

    public static function b_hash($data, $scheme = 'auth') {
        $salt = wp_salt($scheme) . 'j4H!B3TA,J4nIn4.';
        return hash_hmac('md5', $data, $salt);
    }

    public function is_logged_in() {
        return $this->isLoggedIn;
    }

    public function is_temporary() {
        return $this->isTemporary;
    }

    public function get($key, $default = "") {
        if (isset($this->userData->$key)){
            return $this->userData->$key;
        }
        if (isset($this->permitted->$key)){
            return $this->permitted->$key;
        }
        if (!empty($this->permitted)){
            return $this->permitted->get($key, $default);
        }
        return $default;
    }

    public function get_message() {
        return $this->lastStatusMsg;
    }
    public function set_message($message) {
        $this->lastStatusMsg = BUtils::_($message);
        echo $this->lastStatusMsg;
    }
    public function get_expire_date(){
        return "";
    }
    public function delete(){
        if (!$this->is_logged_in()) {return ;}
        //$user_name = $this->get('user_name');
        $user_id   = $this->get('member_id');
        wp_clear_auth_cookie();
        $this->logout();
        BMembers::delete_swpm_user_by_id($user_id);
    }

    public function reload_user_data(){
        if (!$this->is_logged_in()) {return ;}

        global $wpdb;
        $query = "SELECT * FROM " . $wpdb->prefix . "ncoas_members WHERE member_id = %d";
        $this->userData = $wpdb->get_row($wpdb->prepare($query, $this->userData->member_id));

        //get additional userdata from Sales force: zip, birth_month and birth_date
        $tempData = (array)$this->userData;
        $sforce = BSforce::get_instance();
        $result = $sforce->get_additional_userdata($tempData);
        //set additional values
        if(isset($result['Contact_Zipcode__c']) && !empty($result['Contact_Zipcode__c'])){
            $this->userData->zip = $result['Contact_Zipcode__c'];
        }else{
            $this->userData->zip = '';
        }
        if(isset($result['Birth_Month__c']) && !empty($result['Birth_Month__c'])){
            $this->userData->birth_month = intval($result['Birth_Month__c']);
            $this->userData->birth_year = intval($result['Birth_Year__c']);
        }else{
            $this->userData->birth_month = '';
            $this->userData->birth_year = '';
        }
        if(isset($result['related']) && !empty($result['related'])){
            $this->userData->related = $result['related'];
        }else{
            $this->userData->related = '';
        }
    }
}

<?php

/**
 * Creates all the basic field objects used in all forms , ensuring that email
 * and password are trimmed and sanitized before input to DB.
 */
class BForm {

    protected $fields;
    protected $op;
    protected $errors;
    protected $debug;
    protected $sanitized;

    public function __construct($fields) {
        $this->fields = $fields;
        $this->sanitized = array();
        $this->errors = array();
        $this->validate_wp_user_email();
        if ($this->is_valid()) {
            foreach ($fields as $key => $value){
                $this->$key();
            }
        }
    }

    /**
     * Magic PHP function that captures all calls to undefined methods. A
     * message is sent to the log indicating as much has happened. When this
     * does happen, it's likely that a form field doesn't have a validation
     * method, and should probably have one defined.
     */
    public function __call($name, $args) {
      error_log(__CLASS__ . " doesn't have a method called '" . $name . "'.");
    }

    protected function validate_wp_user_email(){
    }


    protected function user_name() {
        $user_name = filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING);
        $this->sanitized['user_name'] = sanitize_text_field($user_name);
    }

    protected function first_name() {
        $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        if (empty($first_name)) {
            //$this->errors['first_name'] = BUtils::_('First name is required');
            return;
        }
        $this->sanitized['first_name'] = sanitize_text_field($first_name);
    }

    protected function last_name() {
        $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        if (empty($last_name)) {
            //$this->errors['last_name'] = BUtils::_('Last name is required');
            return;
        }
        $this->sanitized['last_name'] = sanitize_text_field($last_name);
    }

    protected function password() {
        $password = filter_input(INPUT_POST, 'password',FILTER_UNSAFE_RAW);
        $password_re = filter_input(INPUT_POST, 'password_re',FILTER_UNSAFE_RAW);

        if (empty($this->fields['password']) && empty($password)) {
            $this->errors['password'] = BUtils::_('Password is required');
            return;
        }

        if (!empty($password)) {
            $saned = rtrim(ltrim(sanitize_text_field($password)));
            $saned_re = rtrim(ltrim(sanitize_text_field($password_re)));

            if ($saned != $saned_re){
                $this->errors['password'] = BUtils::_('Passwords do not match please re-enter the confirmation password');
            }

            $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
            $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);

            if (!$this->strong_password($password, $first_name, $last_name)) {
                $this->debug['password'] = $saned;
                $pwd_length = intval(BSettings::get_instance()->get_value('membership-pwd-length'));
                $this->errors['password'] = BUtils::_('Password is not strong, please revise the requirements:<br />- Minimum of ' . $pwd_length . ' characters,<br />- Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &, etc)<br />- Cannot contain user\'s first or last name');
                return;
            }

            $this->sanitized['plain_password'] = $password;
            $this->sanitized['password'] = BUtils::encrypt_password(trim($password));
        }
    }

    protected function email() {
        global $wpdb;
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW));
        $email_re = trim(filter_input(INPUT_POST, 'email_re', FILTER_UNSAFE_RAW));


        if (empty($email)) {
            $this->errors['email'] = BUtils::_('Email is required');
            return;
        }
        if (!is_email($email)) {
            $this->debug['email'] = $email;
            $this->errors['email'] = BUtils::_('Email is invalid');
            return;
        }

        $saned = sanitize_text_field($email);
        $saned_re = sanitize_text_field($email_re);
        if(!empty($email_re) && ($saned != $saned_re)){
            $this->errors['email'] = BUtils::_('Email mismatch');
                return;
        }

        $query = "SELECT count(member_id) FROM {$wpdb->prefix}ncoas_members WHERE email= %s";
        $member_id = filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT);
        if (!empty($member_id)) {
            $query .= ' AND member_id !=%d';
            $result = $wpdb->get_var($wpdb->prepare($query, strip_tags($saned), $member_id));
        }
        else{
            $result = $wpdb->get_var($wpdb->prepare($query, strip_tags($saned)));
        }

        if ($result > 0) {
            if ($saned != $this->fields['email']) {
                $form_type = filter_input(INPUT_POST, 'editprofile_submit', FILTER_UNSAFE_RAW);
                $ext = bUtils::get_url_ext();
                $this->debug['email'] = $email;
                if(!empty($form_type)){
                    //edit
                    $this->errors['email'] = 'An account with this email address already exists. If you wish to access that alternate account please log out and <a href="'.get_permalink(BUtils::get_login_url()).$ext.'">sign in to your alternate account</a>.';
                }else{
                    //add
                    $this->errors['email'] = 'An account with this email address already exists. Please <a href="'.get_permalink(BUtils::get_login_url()).$ext.'">sign in to your account</a>. <br />If you cannot remember your password, <a href="'.get_permalink(BUtils::get_reset_url()).'">you may reset it now</a>.';
                }
                return;
            }
        }
        $this->sanitized['email'] = $saned;
    }

    protected function birth_month() {
        $birth_month = filter_input(INPUT_POST, 'birth_month', FILTER_SANITIZE_STRING);
        $birth_year = filter_input(INPUT_POST, 'birth_year', FILTER_SANITIZE_STRING);

        if (empty($birth_month)&&empty($birth_year)) {
            //return;
        }else if (empty($birth_month) || empty($birth_year)) {
            if(!(empty($birth_month)&&empty($birth_year))){
                $this->debug['birth_month'] = $birth_month;
                $this->errors['birth_month'] = BUtils::_('Please select both month and year');
                return;
            }
        }

        $this->sanitized['birth_month'] = sanitize_text_field($birth_month);
        $this->sanitized['birth_year'] = sanitize_text_field($birth_year);
    }

    protected function birth_year() {
    }

    protected function avatar() {
        $avatar = filter_input(INPUT_POST, 'avatar', FILTER_SANITIZE_STRING);
        if (empty($avatar)) {return;}
        $this->sanitized['avatar'] = sanitize_text_field($avatar);
    }

    protected function zip() {
        $zip = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING);
        //if (empty($zip)) {return;}

        if (preg_match('/^(\d{5})?$/', $zip)){
            $domains = BUtils::get_domain_names();
            $host = $_SERVER['HTTP_HOST'];
            if (in_array($_SERVER['HTTP_HOST'], $domains)) {
                $key = array_search($host, $domains);
                if($key == 0 && !empty($zip)){
                    //MMM
                    try{
                        $response = benefitscheckup::runGeneric( 'getStateDetails', array( $zip, 1 ) );
                        // Not a valid zip
                        if( ! $response ) {
                            $this->debug['zip'] = $zip;
                            $this->errors['zip'] = BUtils::_('Please enter a valid zip code for the 50 U.S. States or the District of Columbia');
                        }
                    }catch(Exception $ex){}
                }
            }

            $this->sanitized['zip'] = sanitize_text_field($zip);
        }else{
            $this->debug['zip'] = $zip;
            $this->errors['zip'] = BUtils::_('Zip field is invalid');
        }
    }

    protected function screening_id() {
        $screening_id = filter_input(INPUT_POST, 'screening_id', FILTER_SANITIZE_STRING);
        $subset_id = filter_input(INPUT_POST, 'subset_id', FILTER_SANITIZE_STRING);
        if (empty($screening_id) || empty($subset_id)) {return;}

        $this->sanitized['screening_id'] = sanitize_text_field($screening_id);
        $this->sanitized['subset_id'] = sanitize_text_field($subset_id);

        date_default_timezone_set('America/New_York');
        $this->sanitized['report_date'] = date("m/d/y");
    }

    protected function subset_id() {

    }
    protected function enrollment_sdate() {

    }
    protected function enrollment_edate() {

    }
    protected function enrollment_mdate() {

    }
    protected function related() {

    }
    protected function primary_id() {

    }

    //  protected function non_primarycontact() {

    // }

    protected function tos_check() {
        $tos_check = filter_input(INPUT_POST, 'tos_check', FILTER_SANITIZE_STRING);
        if (empty($tos_check)) {
            $this->errors['tos_check'] = BUtils::_('Please read and agree to the Terms of Use and Privacy Policy');
            return;
        }
        $this->sanitized['tos_check'] = sanitize_text_field($tos_check);
    }

    protected function report_date() {

    }

    protected function password_re() {

    }
    protected function error_handler() {

    }


    protected function password_temp() {
        if (!is_admin()){ //frontend stuff
            $password_temp = filter_input(INPUT_POST, 'password_temp',FILTER_UNSAFE_RAW);

            if (empty($this->fields['password_temp']) && empty($password_temp)) {
                $this->errors['password_temp'] = BUtils::_('Temporary password is required');
                return;
            }

            if (!$this->strong_password($password_temp)) {
                $this->debug['password_temp'] = $saned;
                $this->errors['password_temp'] = BUtils::_('Temporary password invalid');
                return;
            }

            $this->sanitized['password_temp'] = $password_temp;
        }else{
            $password_temp = filter_input(INPUT_POST, 'password_temp',FILTER_UNSAFE_RAW);
            $this->sanitized['password_temp'] = $password_temp;
        }

    }

    protected function form_sid() {
        $sid = filter_input(INPUT_POST, 'form_sid', FILTER_SANITIZE_STRING);
        $this->sanitized['sid'] = sanitize_text_field($sid);
    }

    protected function form_cid() {
        $cid = filter_input(INPUT_POST, 'form_cid', FILTER_SANITIZE_STRING);
        $this->sanitized['cid'] = sanitize_text_field($cid);
    }

    protected function form_pid() {
        $pid = filter_input(INPUT_POST, 'form_pid', FILTER_SANITIZE_STRING);
        $this->sanitized['pid'] = sanitize_text_field($pid);
    }

    protected function created() {

    }

    public function is_valid() {
        return count($this->errors) < 1;
    }

    public function get_fields() {
        return $this->fields;
    }

    public function get_sanitized() {
        return $this->sanitized;
    }

    public function get_errors() {
        return $this->errors;
    }

    public function get_debug() {
        return $this->debug;
    }

    protected function valid_pass($candidate) {
        $r1='/[A-Z]/';  //Uppercase
        $r2='/[a-z]/';  //lowercase
        $r3='/[!@#$%^&*()\-_=+{};:,<.>]/';  // whatever you mean by 'special char'
        $r4='/[0-9]/';  //numbers

        if(preg_match_all($r1,$candidate, $o)<1) return FALSE;

        if(preg_match_all($r2,$candidate, $o)<1) return FALSE;

        if(preg_match_all($r3,$candidate, $o)<1) return FALSE;

        if(preg_match_all($r4,$candidate, $o)<1) return FALSE;

        $pwd_length = intval(BSettings::get_instance()->get_value('membership-pwd-length'));
        if(strlen($candidate)<$pwd_length) return FALSE;

        return TRUE;
    }

    protected function strong_password($password, $first_name = '', $last_name = ''){

        $is_strong = true;
        if(!($this->valid_pass($password))){
            $is_strong = false;
        }

        //if (preg_match_all('/(.)\1{2,}/i', $password)){
        //    $is_strong = false;
        //}

        if (!empty($first_name)&&strlen($first_name)>2) {
            if (preg_match_all('/'.$first_name.'/i', $password, $matches)){
                $is_strong = false;
            }
        }

        if (!empty($last_name)&&strlen($last_name)>2) {
            if (preg_match_all('/'.$last_name.'/i', $password, $matches)){
                $is_strong = false;
            }
        }

        return $is_strong;
    }

}

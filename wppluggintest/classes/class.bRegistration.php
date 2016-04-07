<?php
/**
 * Basic registration setup, sets up the parameters to send new users, members
 * registration email includes sender and recipient info, etc..
 */
abstract class BRegistration {
  protected $member_info = array();
  protected static $_intance = null;

  protected function send_reg_email() {
    global $wpdb;
    if (empty($this->member_info)) {
      return false;
    }
    $member_info = $this->member_info;
    $settings = BSettings::get_instance();
    $subject = $settings->get_value('reg-complete-mail-subject');
    $body = $settings->get_value('reg-complete-mail-body');
    $from_address = $settings->get_value('email-from');
    $from_name = $settings->get_value('email-name');
    $login_link = get_permalink($settings->get_value('login-page-url'));
    $headers = 'From: ' . $from_name . " <" . $from_address . ">\r\n";
    $member_info['password'] = $member_info['plain_password'];
    $member_info['login_link'] = $login_link;
    $values = array_values($member_info);
    $keys = array_map('swpm_enclose_var', array_keys($member_info));
    $body = str_replace($keys, $values, $body);
    $email = sanitize_email(
      filter_input(INPUT_POST, 'email', FILTER_UNSAFE_RAW)
    );
    wp_mail(trim($email), $subject, $body, $headers);
    return true;
  }
}

function swpm_enclose_var($n) {
  return '{' . $n  . '}';
}

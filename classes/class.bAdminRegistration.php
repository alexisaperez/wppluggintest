<?php
/**
 * Defines all the objects that allow for an admin to create a user account via
 * the Admin Dashboard on WP.
 */
class BAdminRegistration extends BRegistration {
  protected static $_instance;

  /**
   * A protected constructor - use `get_instance()` for this singleton class.
   */
  protected function __construct() {}

  /**
   * Returns a singleton instance of this class.
   * @return BAdminRegistration
   */
  public static function get_instance(){
    if (empty(self::$_instance)) {
      self::$_instance = new BAdminRegistration();
    }
    return self::$_instance;
  }

  public function register() {
    global $wpdb;
    $member = BTransfer::$default_fields;
    unset($member['tos_check']);
    $form = new BForm($member);
    if ($form->is_valid()) {
      $member_info = $form->get_sanitized();
      $plain_password = $member_info['plain_password'];
      unset($member_info['plain_password']);
      $wpdb->insert($wpdb->prefix . "ncoas_members", $member_info);

      $settings = BSettings::get_instance();
      $send_notification = $settings->get_value(
        'enable-notification-after-manual-user-add'
      );
      $member_info['plain_password'] = $plain_password;
      $this->member_info = $member_info;
      if (!empty($send_notification)){
        $this->send_reg_email();
      }
      $message = array(
        'succeeded' => true,
        'message' => BUtils::_('Account creation successful.')
      );
      BTransfer::get_instance()->set('status', $message);
      wp_redirect('admin.php?page=ncoa_membership');
      return;
    }
    $message = array(
      'succeeded' => false,
      'message' => BUtils::_('Please correct the following:'),
      'extra' => $form->get_errors()
    );
    BTransfer::get_instance()->set('status', $message);
  }



  public function edit($id) {
    global $wpdb;
    $query = $wpdb->prepare(
      "SELECT * FROM " . $wpdb->prefix . "ncoas_members WHERE member_id = %d",
      $id
    );
    $member = $wpdb->get_row($query, ARRAY_A);

    $form = new BForm($member);
    if ($form->is_valid()) {
      $member = $form->get_sanitized();
      unset($member['plain_password']);
      $wpdb->update(
        $wpdb->prefix . "ncoas_members",
        $member,
        array('member_id' => $id)
      );
      $message = array(
        'succeeded' => true,
        'message' => 'Updated Successfully.'
      );
      do_action(
        'swpm_admin_edit_custom_fields',
        $member + array('member_id'=>$id)
      );
      BTransfer::get_instance()->set('status', $message);
      wp_redirect('admin.php?page=ncoa_membership');
    }
    $message = array(
      'succeeded' => false,
      'message' => BUtils::_('Please correct the following:'),
      'extra' => $form->get_errors()
    );
    BTransfer::get_instance()->set('status', $message);
  }
}

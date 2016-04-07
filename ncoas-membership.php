<?php
/*
Plugin Name: NCOA Services User Profile
Plugin URI: http://tammantech.com/
Description: Custom NCOA Services plugin for user profiles based of simple-membership-plugin.
Version: 0.0.7
Author: Tamman Technologies
Author URI: http://tammantech.com/
License: Private
*/

//TESTING UPDATE class

require_once( 'BFIGitHubPluginUploader.php' );
if ( is_admin() ) {
    new BFIGitHubPluginUpdater( __FILE__, 'alexisaperez', "wppoc" );
}

//END OF TEST

//Direct access to this file is not permitted
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"])){
    exit("Do not access this file directly.");
}

include_once('classes/class.ncoas-membership.php');

define('ncoas_membership_VER', '0.0.7');
define('ncoas_membership_DB', '0.0.7');
define('ncoas_membership_PATH', dirname(__FILE__) . '/');
define('ncoas_membership_URL', plugins_url('',__FILE__));
if (!defined('COOKIEHASH')) {
  define('COOKIEHASH', md5(get_site_option( 'siteurl' )));
}
define('ncoas_membership_AUTH', 'ncoas_membership_'. COOKIEHASH);
define('ncoas_membership_SEC_AUTH', 'ncoas_membership_sec_'. COOKIEHASH);

register_activation_hook(
  ncoas_membership_PATH . 'ncoas-membership.php',
  'NcoasMembership::activate'
);
register_deactivation_hook(
  ncoas_membership_PATH . 'ncoas-membership.php',
  'NcoasMembership::deactivate'
);
add_action('swpm_login','NcoasMembership::swpm_login', 10,3);

if (!class_exists('WP_List_Table')){
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

$simple_membership = new NcoasMembership();

/**
 * Add settings link in plugins listing page.
 * @param String[] $links
 * @param String $file
 */
function mmm_add_settings_link($links, $file) {
  if ($file == plugin_basename(__FILE__)) {
    $settings_link = '<a href="admin.php?page=ncoa_membership_settings">Settings</a>';
    array_unshift($links, $settings_link);
  }
  return $links;
}

add_filter('plugin_action_links', 'mmm_add_settings_link', 10, 2);

function userprofile_template($template) {
  //if your profile, subscription or MQC saved results
  $postid = get_the_ID();
  $setting = BSettings::get_instance();
  $profile_url = $setting->get_value('profile-page-url');
  //MMM
  $subscription_url = $setting->get_value('subscription-page-url');
  $saved_results_url = $setting->get_value('mqc-saved-results-page-url');
  //NISC
  $mng_url = BUtils::get_mng_url();
  $benefits_url = BUtils::get_benefits_url();
  $accreditation_url = BUtils::get_accreditation_url();
  $opportunities_url = BUtils::get_opportunities_url();
  $news_url = BUtils::get_news_url();


  if ( $postid == $profile_url || $postid == $subscription_url || $postid == $saved_results_url || $postid == $benefits_url || $postid == $accreditation_url || $postid == $opportunities_url || $postid == $news_url || $postid == $mng_url ) {
    $template = WP_PLUGIN_DIR . '/ncoas-membership/template/userprofile-template.php';
  }
  return $template;
}
add_filter( 'page_template', 'userprofile_template' );



function mmm_js_variables() {
  $settings = BSettings::get_instance();
  echo '<script>';
  $membershipt = $settings->get_value('membership-timeout');
  if(!empty($membershipt)){
    echo 'var mmm_timeout_min = ' . $membershipt .'; ';// Number of minutes until it times out.
  }
  $membershipp = $settings->get_value('membership-pwd-length');
  if(!empty($membershipp)){
    echo 'var mmm_pwd_length = ' . $membershipp .'; ';// Number of minutes until it times out.
  }
  echo '</script>';
}
add_action( 'wp_head', 'mmm_js_variables' );

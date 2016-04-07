<?php

/**
 * This file sets up the settings for all the mandatory pages created when
 * activating plugin such as title, name, shortcode, parents and children.
 */
class miscUtils {

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
        if (!$profile_page_obj) {
            $reset_page_id = wp_insert_post($swpm_reset_page);
        } else {
            $reset_page_id = $reset_page_obj->ID;
            if ($reset_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $reset_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_reset_page_permalink = get_permalink($reset_page_id);
        $settings->set_value('reset-page-url', $swpm_reset_page_permalink);

        //Temporary password
        $swpm_temporary_page = array(
            'post_title' => BUtils::_('Temporary password reset'),
            'post_name' => 'temporary-password-reset',
            'post_content' => '[ncoa_userprofile_temporary_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $temporary_page_obj = get_page_by_path('temporary-password-reset');
        if (!$temporary_page_obj) {
            $temporary_page_id = wp_insert_post($swpm_temporary_page);
        } else {
            $temporary_page_id = $reset_page_obj->ID;
            if ($temporary_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $temporary_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_temporary_page_permalink = get_permalink($temporary_page_id);
        $settings->set_value('temporary-page-url', $swpm_temporary_page_permalink);

        $settings->save(); //Save all settings object changes
    }

}

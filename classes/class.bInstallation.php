<?php

/**
 * Handles basic installation of the plugin and setting some baseline settings
 * and values.
 */
class BInstallation {

    /*
     * This function is capable of handing both single site or multi-site install and upgrade all in one.
     */
    static function run_safe_installer()
    {
        global $wpdb;

        //Do this if multi-site setup
        if (function_exists('is_multisite') && is_multisite())
        {
            // check if it is a network activation - if so, run the activation function for each blog id
            if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1))
            {
                $old_blog = $wpdb->blogid;
                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    BInstallation::installer();
                    BInstallation::initdb();
                }
                switch_to_blog($old_blog);
                return;
            }
        }

        //Do this if single site standard install
        BInstallation::installer();
        BInstallation::initdb();
    }

    public static function installer() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = '';
        if (!empty($wpdb->charset)){
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }else{
            $charset_collate = "DEFAULT CHARSET=utf8";
        }
        if (!empty($wpdb->collate)){
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        $sql = "CREATE TABLE " . $wpdb->prefix . "ncoas_members (
			member_id int(12) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			user_name varchar(32) DEFAULT NULL,
            email varchar(64) NOT NULL,
            password varchar(64) NOT NULL,
            password_temp int(2) DEFAULT 0,
            first_name varchar(64) DEFAULT NULL,
            last_name varchar(64) DEFAULT NULL,
            avatar varchar(64) DEFAULT 'mistery.png',
            screening_id varchar(64) DEFAULT NULL,
            subset_id varchar(64) DEFAULT NULL,
            enrollment_sdate VARCHAR(32) NULL,
            enrollment_edate VARCHAR(32) NULL,
            enrollment_mdate VARCHAR(32) NULL,
            report_date varchar(32) DEFAULT NULL,
            sid varchar(32) DEFAULT NULL,
            cid varchar(32) DEFAULT NULL,
            pid varchar(32) DEFAULT NULL,
            created timestamp DEFAULT CURRENT_TIMESTAMP
          )" . $charset_collate . ";";
        dbDelta($sql);

        //Save the current DB version
        update_option("swpm_db_version", ncoas_membership_DB);
    }

    public static function initdb() {
        $settings = BSettings::get_instance();

        $installed_version = $settings->get_value('swpm-active-version');

        //Set other default settings values
        $reg_email_subject = "Your registration is complete";
        $reg_email_body = "Dear {first_name} {last_name},\n\n" .
                "Your registration is now complete!\n" .
                //"Registration details:\n" .
                //"Email: {email}\n" .
                //"Password: {password}\n\n" .
                "Please login to the member area at the following URL:\n" .
                "{login_link}\n\n" .
                "Thank You";

        $reset_email_subject = get_bloginfo('name') . ": New Password";
        $reset_email_body = "Dear {first_name} {last_name}," .
                "\n\nHere is your temporary password:" .
                "\n{password}" .
                "\n\nPlease reset your password at the following URL:\n" .
                "{reset_link}\n\n" .
                "\n\nThank You";

        $change_email_subject = "Password Updated";
        $change_email_body = "Dear {first_name} {last_name}," .
                "\n\nYour password has been updated!" .
                "\nPlease login to the member area at the following URL:" .
                "\n{login_link}" .
                "\n\nThank You";

        if (empty($installed_version)) {
            //Do fresh install tasks

            /* * * Create the mandatory pages (if they are not there) ** */
            bUtils::create_mandatory_wp_pages();
            bUtils::set_default_admin_settings();

            /* * * End of page creation * * */
            $settings->set_value('reg-complete-mail-subject', stripslashes($reg_email_subject))
                    ->set_value('reg-complete-mail-body', stripslashes($reg_email_body))
                    ->set_value('reset-mail-subject', stripslashes($reset_email_subject))
                    ->set_value('reset-mail-body', stripslashes($reset_email_body))
                    ->set_value('change-email-subject', stripslashes($change_email_subject))
                    ->set_value('change-email-body', stripslashes($change_email_body))
                    ->set_value('email-name', trim(get_option('blogname')))
                    ->set_value('email-from', trim(get_option('admin_email')));
        }

        $settings->set_value('swpm-active-version', ncoas_membership_VER)->save(); //save everything.
    }
}

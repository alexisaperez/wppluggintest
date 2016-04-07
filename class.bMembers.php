<?php

/**
 * The base member object constructor, handles logged in/ logged out states, the
 * constructors for adding, editing and deleting members.
 * @package Authentication
 */
class BMembers extends WP_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => BUtils::_('Member'),
            'plural' => BUtils::_('Members'),
            'ajax' => false
        ));
    }

    function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />'
            , 'member_id' => BUtils::_('ID')
            , 'user_name' => BUtils::_('User ID')
            , 'email' => BUtils::_('Email')
            , 'first_name' => BUtils::_('First Name')
            , 'last_name' => BUtils::_('Last Name')
            , 'screening_id' => BUtils::_('Screening ID')
            , 'subset_id' => BUtils::_('Subset ID')
            , 'report_date' => BUtils::_('Report Date')
            , 'sid' => BUtils::_('SID')
            , 'cid' => BUtils::_('CID')
            , 'pid' => BUtils::_('PID')
            , 'created' => BUtils::_('Created')
        );
    }

    function get_sortable_columns() {
        return array(
            'member_id' => array('member_id', true),
            'user_name' => array('user_name', true),
            'email' => array('email', true)
            //'first_name' => array('first_name', true),
            //'last_name' => array('last_name', true)
        );
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk_delete' => BUtils::_('Delete')
        );
        return $actions;
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function column_member_id($item) {
        $actions = array(
            'edit' => sprintf('<a href="admin.php?page=%s&member_action=edit&member_id=%s">Edit</a>', $_REQUEST['page'], $item['member_id']),
            'delete' => sprintf('<a href="?page=%s&member_action=delete&member_id=%s"
                                    onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>', $_REQUEST['page'], $item['member_id']),
        );
        return $item['member_id'] . $this->row_actions($actions);
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="members[]" value="%s" />', $item['member_id']
        );
    }

    function prepare_items() {
        global $wpdb;
        $query = "SELECT * FROM " . $wpdb->prefix . "ncoas_members";
        $s = filter_input(INPUT_POST, 's');
        if (!empty($s)){
            $query .= " WHERE email LIKE '%" . strip_tags($s) . "%' "
                    . " OR user_name LIKE '%" . strip_tags($s) . "%' ";
        }
        $orderby = filter_input(INPUT_GET, 'orderby');
        $orderby = empty($orderby) ? 'user_name' : $orderby ;
        $order = filter_input(INPUT_GET, 'order');
        $order = empty($order) ? 'DESC' : $order;

        $sortable_columns = $this->get_sortable_columns();
        $orderby = BUtils::sanitize_value_by_array($orderby, $sortable_columns);
        $order = BUtils::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));

        $query.=' ORDER BY ' . $orderby . ' ' . $order;
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        $perpage = 20;
        $paged  = filter_input(INPUT_GET, 'paged');
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    function no_items() {
        _e('No Member found.');
    }

    function process_form_request() {
        if (isset($_REQUEST['member_id']))
            return $this->edit(absint($_REQUEST['member_id']));
        return $this->add();
    }

    function add() {
        $form = apply_filters('swpm_admin_registration_form_override', '');
        if (!empty($form)) {echo $form;return;}
        global $wpdb;
        $member = BTransfer::$default_fields;
        if (isset($_POST['createswpmuser'])) {
            $member = $_POST;
        }
        extract($member, EXTR_SKIP);
        include_once(ncoas_membership_PATH . 'views/admin_add.php');
        return false;
    }

    function edit($id) {
        global $wpdb;
        $id = absint($id);
        $query = "SELECT * FROM {$wpdb->prefix}ncoas_members WHERE member_id = $id";
        $member = $wpdb->get_row($query, ARRAY_A);
        if (isset($_POST["editswpmuser"])) {
            $_POST['user_name'] = $member['user_name'];
            $_POST['email'] = $member['email'];
            //$_POST['first_name'] = $member['first_name'];
            //$_POST['last_name'] = $member['last_name'];
            //$_POST['birth_month'] = $member['birth_month'];
            //$_POST['birth_year'] = $member['birth_year'];
            //$_POST['zip'] = $member['zip'];
            //$_POST['avatar'] = $member['avatar'];
            //$_POST['screening_id'] = $member['screening_id'];
            //$_POST['report_date'] = $member['report_date'];
            $member = $_POST;
        }
        extract($member, EXTR_SKIP);
        include_once(ncoas_membership_PATH . 'views/admin_edit.php');
        return false;
    }

    function delete() {
        global $wpdb;
        if (isset($_REQUEST['members'])) {
            $members = $_REQUEST['members'];
            if (!empty($members)) {
                $members = array_map('absint', $members);
                foreach ($members as $swpm_id) {
                    $user_name = BUtils::get_user_by_id(absint($swpm_id));
                }
                $query = "DELETE FROM " . $wpdb->prefix . "ncoas_members WHERE member_id IN (" . implode(',', $members) . ")";
                $wpdb->query($query);
            }
        }
        else if (isset($_REQUEST['member_id'])) {
            $id = absint($_REQUEST['member_id']);
            BMembers::delete_user_by_id($id);
        }
    }
    public static function delete_user_by_id($id){
        $user_name = BUtils::get_user_by_id($id);
        BMembers::delete_swpm_user_by_id($id);
    }

    public static function delete_swpm_user_by_id($id){
        global $wpdb;
        $query = "DELETE FROM " . $wpdb->prefix . "ncoas_members WHERE member_id = $id";
        $wpdb->query($query);
    }

    function show() {
        include_once(ncoas_membership_PATH . 'views/admin_members.php');
    }

    public static function update_screeningid($member_id='') {
        global $wpdb;
        global $message;

        $auth = BAuth::get_instance();
        $screening_id = isset( $_COOKIE['screening_id'] ) ? $_COOKIE['screening_id'] : '';
        $subset_id = isset( $_SESSION['subset_id'] ) ? $_SESSION['subset_id'] : '';
        unset($_SESSION['subset_id']);

        $enrollment_sdate = isset( $_COOKIE['enrollment_startdate'] ) ? $_COOKIE['enrollment_startdate'] : '';
        $enrollment_edate = isset( $_COOKIE['enrollment_enddate'] ) ? $_COOKIE['enrollment_enddate'] : '';
        $enrollment_mdate = isset( $_COOKIE['mqc_birth_month'] ) ? $_COOKIE['mqc_birth_month'] : '';

        //ADD pid and cid
        $pid = filter_input(INPUT_GET, 'form_pid');
        $cid = filter_input(INPUT_GET, 'form_cid');

        if(empty($cid)){
            $cid = isset( $_GET['CID'] ) ? sanitize_text_field( $_GET['CID'] ) : '';
        }
        if(empty($pid)){
            $pid = isset( $_GET['PID'] ) ? sanitize_text_field( $_GET['PID'] ) : '';
        }


        if($screening_id == ''||$subset_id == ''||$member_id == ''||$enrollment_sdate == ''||$enrollment_edate == ''||$enrollment_mdate == ''){
            return;
        }

        date_default_timezone_set('America/New_York');
        $date = date("m/d/y g:i A");

        $member_info = array(
            'screening_id' => $screening_id,  // string
            'subset_id' => $subset_id,  // string

            'enrollment_sdate' => $enrollment_sdate,  // string
            'enrollment_edate' => $enrollment_edate,  // string
            'enrollment_mdate' => $enrollment_mdate,  // string

            'report_date' => $date,  // integer (number)

            'cid' => $cid,  // string
            'pid' => $pid  // string
        );

        //update SF
        try{

            //update salesforce db
            $sForce = BSforce::get_instance();
            $response = $sForce->upsert_mqc_report($member_info);

            if(empty($response['errors'])){
                $wpdb->update(
                        $wpdb->prefix . "ncoas_members", $member_info, array('member_id' => $member_id));
                $auth->reload_user_data();
                $message = array('succeeded' => true, 'message' => 'Report saved.');
            }

            //BTransfer::get_instance()->set('status', $message);
        } catch (Exception $e) {
            $message = array('succeeded' => false, 'message' => BUtils::_('An error has occurred, please try again.'));
            return;
        }

    }


    /* ===================
     * Members util functions
     * ============================= */

    public static function is_member_logged_in() {

        $auth = BAuth::get_instance();
        if ($auth->is_logged_in()) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_logged_in_members_id() {
        $auth = BAuth::get_instance();
        if (!$auth->is_logged_in()) {
            return bUtils::_("User is not logged in.");
        }
        return $auth->get('member_id');
    }


}

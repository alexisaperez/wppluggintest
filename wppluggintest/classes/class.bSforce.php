<?php
/**
 *  A base class which when extended will provide a connection to the SalesForce
 *  API.
 */
class BSforceConnectionManager {
  protected $SFUsername;
  protected $SFPassword;
  protected $SFAccount;
  protected $SFConnection;
  protected $SFClient;
  protected $SFSession;

  /**
   *  Discovers the connection parameters needed for the current environment
   *  (testing vs. prod) and uses those values to establish a connection to
   *  SalesForce.
   *  To access the connection to SalesForce, use `$this->$SFClient` or
   *  `$this->SFConnection` in your implementing class.
   */
  protected function __construct() {
    require_once (
      ncoas_membership_PATH  . "soapclient/SforceEnterpriseClient.php"
    );
    $settings = BSettings::get_instance();
    $pwd = $settings->get_value('membership-pwd');
    $token = $settings->get_value('membership-token');
    $acct = $settings->get_value('membership-acct');

    $this->SFUsername = "ncoasapi@ncoa.org";
    $this->SFPassword = $pwd . $token;
    $this->SFAccount = $acct;

    $wsdl = $settings->get_value('membership-test');
    if(empty($wsdl)){
      $this->wsdl = 'enterprise.prod.wsdl.xml';
    } else {
      $this->wsdl = 'enterprise.test.wsdl.xml';
    }

    //ini_set("soap.wsdl_cache_enabled", "0");
    try {
      $this->SFConnection = new SforceEnterpriseClient();
      $this->SFClient = $this->SFConnection->createConnection(
        ncoas_membership_PATH . 'soapclient/' . $this->wsdl
      );
      $this->SFSession = $this->SFConnection->login(
        $this->SFUsername, $this->SFPassword
      );
    } catch (Exception $e) {
      echo $e->faultstring;
    }
  }
}


/**
 * Holds all the interaction, creation, shuttling back and forth from/to
 * Salesforce.
 */
class BSforce extends BSforceConnectionManager {

  private static $_this;

  /**
   *  Currently, this simply calls the parent constructor so that we have a
   *  connection to SalesForce ready to use.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Returns an existing instance of this class, or creates one if needed and
   * returns that instead.
   */
  public static function get_instance() {
    if (empty(self::$_this)) {
      self::$_this = new BSforce();
    }
    return self::$_this;
  }


  /**
   * Fetches the zipcode, birth month, and birth year for the `user_name` and
   * `member_id` provided in the `$member_info` array.
   * @param mixed[] $member_info Associative array containing user information.
   * @return string[] The additional information found about the user.
   */
  public function get_additional_userdata($member_info) {
    /*
      zip, birth_month, birth_year
    */
    $profile_id = $member_info['user_name'];

    if(empty($profile_id)){
      $profile_id = $this->new_user_profile($member_info);

      global $wpdb;
      $wpdb->update(
        $wpdb->prefix . "ncoas_members",
        array('user_name' => $profile_id),
        array('member_id' => $member_info['member_id'])
      );

      //check screening ID
      $this->upsert_mqc_report($member_info, $profile_id);
    }

    $user_query = "SELECT Related_Contact__c, Contact_Zipcode__c, Birth_Year__c, Birth_Month__c FROM MMM_Profile__c where Id = '" . $profile_id . "'";
    $user_response = $this->SFConnection->query(($user_query));
    $arrayu = (array)($user_response->records[0]);

    $arrayu['related'] = '';
    $related = self::get_center_info($arrayu['Related_Contact__c']);

    if(!empty($related)){
      $arrayu['related'] = $related;
    }

    return $arrayu;
  }


  public function get_center_info($profile_id) {
    /*
      check if default account or NISC - check contact!
    */
    $user_query = "SELECT Id, AccountId, Membership_Status_del__c, Membership_Type__c from Contact where Id = '" . $profile_id . "'";
    $user_response = $this->SFConnection->query(($user_query));
    $arrayc = (array)($user_response->records[0]);

    if($arrayc['AccountId'] == $this->SFAccount){
      //default account
      return;
    }

    $stack = array();
    $membershiptype = $arrayc['Membership_Type__c'];//multisite or not


  /* related organizations query*/
    $user_queryf = "SELECT Id, Contact__c, Related_Organization__c,Related_Contact__c from Contact_Connector__c";
     $user_responsef = $this->SFConnection->query(($user_queryf));

    $arrayf = (array)($user_responsef->records);
    $related_organization = array($arrayf);

    //center info
    $address = $this->get_center_address($arrayc['AccountId']);
    $stack[] = $address;

    $primarycontact = $this->get_center_primary(
      $arrayc['AccountId'], $profile_id
    );

    array_push($stack, $primarycontact, $membershiptype);

    //membership status
    $membershipstatus = $this->get_center_membership($arrayc['AccountId']);
    //Membership_Status_del__c
    array_push($stack, $arrayc['Membership_Status_del__c']);
    array_push($stack, $membershipstatus, $membershiptype,  $related_organization);
    return $stack;
  }

  /**
   * Get center address.
   * @param String $acct_id The Center's account Id
   * @return String[] An associative array containing the address information.
   */
  public function get_center_address($acct_id) {
    $user_querya = "SELECT Id, Name, ParentId, BillingAddress, Phone, Website from Account where Id = '" . $acct_id . "' LIMIT 1";
    $user_responsea = $this->SFConnection->query(($user_querya));
    $arraya = (array)($user_responsea->records[0]);
    $address = (array)$arraya['BillingAddress'];
    $address['Id'] = $acct_id;
    //additional address info
    $address['phone'] = $arraya['Phone'];
    $address['name'] = $arraya['Name'];
    $address['website'] = $arraya['Website'];
    $address['parentid'] = $arraya['ParentId'];


    $user_queryg = "SELECT Id, Name FROM Account WHERE  ParentId= '"
      . $acct_id ."'   ";
    $user_responseg = $this->SFConnection->query(($user_queryg));
    $arrayg = (array)($user_responseg->records);
    $child_orgs = array($arrayg);

    array_push($address, $child_orgs);
    return $address;
  }


  /**
   * Update's a center's address.
   * The `$data` parameter is expected to contain the following fields:
   *  - `center_id`
   *  - `center_name`
   *  - `center_city`
   *  - `center_street`
   *  - `center_state`
   *  - `center_postalCode`
   *  - `center_phone`
   *  - `center_website`
   * @param mixed[] $data An associative array containing the new address info.
   */
  public function update_center_address($data) {
    $doc = new stdClass();
    $doc->Id = $data['center_id'];
    $doc->Name = $data['center_name'];
    $doc->BillingStreet = $data['center_street'];
    $doc->BillingCity = $data['center_city'];
    $doc->BillingState = $data['center_state'];
    $doc->BillingPostalCode = $data['center_postalCode'];
    $doc->Phone = $data['center_phone'];
    $doc->Website = $data['center_website'];

    return $this->SFConnection->upsert("Id", array($doc), "Account");
  }



  /**
   * Returns the SalesForce user record based on the provided email address.
   * @param String $email The email address of the desired user record.
   * @return Object|BOOL The user object if found, or FALSE if not.
   */
  public function get_user_by_email($email) {
    $query = "SELECT Id, Name, Email FROM Contact WHERE Email='" . $email
      . "' LIMIT 1";

    $response = $this->SFConnection->query( $query );
    if ($response->size > 0) {
      return $response->records[0];
    }

    return FALSE;
  }




  /**
   * Center Primary Contact information.
   * @param String $acct_id The center's Id
   * @param String $profile_id A user's profile Id
   * @return Object containing information about the primary user for the given
   * center ID. If the user found has the same ID as the `$profile_id` provided,
   * the object will also contain an `isprimary` field which will be set to
   * `TRUE`.
   */
  public function get_center_primary($acct_id, $profile_id) {
    $user_queryp = "SELECT Id, Name, Email FROM Contact WHERE AccountId='"
      . $acct_id . "' AND Primary_Contact__c=true";
    $user_responsep = $this->SFConnection->query(($user_queryp));
    $profile_obj = $user_responsep->records[0];

    $profile_obj->isprimary = false;
    if(property_exists($profile_obj, 'Id') && $profile_obj->Id === $profile_id) {
      $profile_obj->isprimary = true;
    }
    return $profile_obj;
  }

  /**
   * Update center primary contact.
   * @param String[] $member_info Updated information to be saved to the db.
   * @return Object returned by the upsert opertion.
   */
  public function update_center_primary($member_info) {
    $user_query = "SELECT Id, Name, LastName, FirstName, Email from Contact where Id='" . $primary_id . "' and Primary_Contact__c=true";
    $response = $this->SFConnection->query(($user_query));
    $arrayo = (array)($response->records[0]);

    $primary_id = $_POST['primary_id'];
    $sObject = new stdClass();
    $sObject->Id =  $primary_id;
    $sObject->Email = $member_info['email'];
    $sObject->LastName = $member_info['last_name'];
    $sObject->FirstName = $member_info['first_name'];

    // update  if there are changes
    if(isset($member_info['email']) && !empty($member_info['email'])){
      $sObject->Email = $member_info['email'];
    } else {
      $sObject->fieldsToNull = array("Email");
    }
    if(isset($member_info['first_name']) && !empty($member_info['first_name'])){
      $sObject->FirstName = $member_info['first_name'];
    } else {
      $sObject->fieldsToNull = array("FirstName");
    }

    if(isset($member_info['last_name']) && !empty($member_info['last_name'])){
      $sObject->LastName = $member_info['last_name'];
    } else {
      $sObject->fieldsToNull = array("LastName");
    }

    // check if Id is for contact or Mmm profile
    $upsertResponse = $this->SFConnection->upsert(
      'Email', array ($sObject), 'Contact'
    );
    return $upsertResponse;
  }


  /**
   * Center users other than primary.
   * @param String $acct_id
   * @return Object
   * @TODO Not implemented - can this go away?
   */
  public function get_center_users($acct_id) {}

  /**
   * Assembles an object representing an "account" or "center" user,
   * upserts that user record in the SF and WP databases, then reloads the
   * user data so that the proper information displays on the page.
   * @param String[] $member_info The member to add to the center.
   */
  public function add_center_users($member_info) {
    $memberObjectRef;
    $member_id = $this->new_user_profile($member_info, $memberObjectRef);
    $memberObj = new stdClass();
    $memberObj->Email = $memberObjectRef->Username__c;
    $memberObj->FirstName = $memberObjectRef->First_Name__c;
    $memberObj->LastName = $memberObjectRef->Last_Name__c;
    $memberObj->Id = $memberObjectRef->Related_Contact__c;
    $memberObj->AccountId = $_POST['center_id'];
    $memberObj->Moved_on__c = false;

    $email_value =  $memberObj->Email;
    $upsertResponse = $this->SFConnection->upsert(
      'Email', array($memberObj), 'Contact'
    );
    $auth = bAuth::get_instance();
    $auth->reload_user_data();
    return $upsertResponse;
  }

  /**
   * This upserts a user with the flag 'Moved On = true' if toggled
   * this does NOT delete a user from either the WP or SF DB
   * it simply adds the flag 'Moved On = true' and removes them from the
   * manage.php
   * @param String[] $member_info The member record to update.
   * @return void
   */
  public function flag_movedon_center_users($member_info) {
    $memberObjectRef;
    $member_id = $this->new_user_profile($member_info, $memberObjectRef);
    $memberObj = new stdClass();
    $memberObj->Email = $memberObjectRef->Username__c;
    $memberObj->Moved_on__c = false;

    $checked_count = count($_POST['user_delete_chkbox']);
    $email_array = $_POST['user_delete_chkbox'];

    //to run PHP script on submit
    if(!isset($_POST['centeruser_delete_submit'])) {
      if(!empty($_POST['user_delete_chkbox'])) {
        //Loop to store and display values of individual checked checkbox.
        foreach($email_array as $selected) {
          $memberObj->Email = $selected;
          $memberObj->Moved_on__c = true;
          $upsertResponse = $this->SFConnection->upsert(
            'Email', array($memberObj), 'Contact'
          );
          $auth = bAuth::get_instance();
          $auth->reload_user_data();
          return $upsertResponse;
        }
      }
    }
  }

  /**
   * Returns all members of a center given that center's ID.
   * @param String $acct_id The center's ID.
   * @return mixed `void` if no results were found. An array of record otherwise
   */
  public function get_center_membership($acct_id) {
    //Opportunity
    $user_query = "SELECT Type, Membership_Start_Date__c, Membership_End_Date__c from Opportunity where AccountId = '" . $acct_id . "'";
    $response = $this->SFConnection->query(($user_query));
    $arrayo = (array)($response->records[0]);

    if(empty($arrayo)){
      return;
    }
    /*get center users not primary*/
    $center_id = $acct_id;
    $user_queryg = "SELECT Id, LastName,Moved_on__c, FirstName, Email FROM Contact WHERE AccountId='". $center_id . "' AND Primary_Contact__c=false";
    // @NOTE After Ben tests, we needed to add "AND Moved_on__c=false" to the
    // end of this query.
    $responseg = $this->SFConnection->query(($user_queryg));
    $arrayg = (array)($responseg->records);
    $non_primarycontact = array($arrayg);
    array_push($arrayo, $non_primarycontact);

    return $arrayo;
  }

  /**
   * User Creation within the WordPress database.
   * send submitted user information to sales force get the salesforce
   * username/id and store in the WP db redirect to new user to profile page.
   *
   * @param mixed[] $member_info The data to be used for account creation
   * (`first_name`, `last_name`, `email`, `zip`, `password`, `birth_month`,
   * `birth_year`, `cid`, `pid`).
   * @return String The newly created account's profile id.
   */
  public function new_user_profile($member_info, &$memberObject=NULL) {
    //create/update SF contact object
    $sObject = new stdClass();
    if (isset($member_info['account_id'])) {
      $sObject->AccountId = $member_info['account_id'];
    } else {
      $sObject->AccountId = $this->SFAccount;
    }
    $sObject->AccountId = $this->SFAccount;
    $sObject->FirstName = $member_info['first_name'];
    $sObject->LastName = $member_info['last_name'];
    $sObject->Email = $member_info['email'];
    if(isset($member_info['zip']) && !empty($member_info['zip'])){
      $sObject->MailingPostalCode = $member_info['zip'];
    }

    $upsertResponse = $this->SFConnection->upsert(
      'Email', array($sObject), 'Contact'
    );
    $array = (array)$upsertResponse;
    $contactId = $array[0]->id;

    //create SF MMM profile object
    //MMM Profile - MMM_Profile__c
    $mObject = new stdClass();
    $mObject->Username__c = $member_info['email'];
    $mObject->Password__c = $member_info['password'];
    if(isset($member_info['birth_month']) && !empty($member_info['birth_month'])){
      $mObject->Birth_Month__c = intval($member_info['birth_month']);
      $mObject->Birth_Year__c = intval($member_info['birth_year']);

      $type = "iep";
      $birthdate_info = intval($member_info['birth_month'])."/01/".intval($member_info['birth_year']); //03/01/1951

      $date_result = benefitscheckup::getDateRange($birthdate_info, $type);
      $enrollment_sdate = $date_result[start_month]."/".$date_result[start_day]."/".$date_result[start_year];
      $enrollment_edate = $date_result[end_month]."/".$date_result[end_day]."/".$date_result[end_year];

      $mObject->Medicare_IEP_Start_Date__c = date(DATE_ATOM, strtotime($enrollment_sdate));
      $mObject->Medicare_IEP_End_Date__c = date(DATE_ATOM, strtotime($enrollment_edate));
    }
    //$mObject->Contact_Zipcode__c = $member_info['zip'];
    $mObject->First_Name__c = $member_info['first_name'];
    $mObject->Last_Name__c = $member_info['last_name'];

    if(!empty($member_info['cid'])){
      $mObject->External_Campaign_ID__c = $member_info['cid'];
    }
    if(!empty($member_info['pid'])){
      $mObject->External_Partner_ID__c = $member_info['pid'];
    }

    //check if MMM profile already created
    $user_query = "SELECT Related_Contact__c from MMM_Profile__c where Username__c = '" . $member_info['email'] . "'";
    $user_response = $this->SFConnection->query(($user_query));

    if($user_response->size == 0){
      //new user
      $mObject->Related_Contact__c = $contactId;
    }



    // If the calling method needs access to the member object that we've just
    // created, we'll assign that by reference here.
    $memberObject = $mObject;

    //upsert MMM profile
    $upsertResponseProfile = $this->SFConnection->upsert(
      'Username__c', array ($mObject), 'MMM_Profile__c'
    );
    $arrayp = (array)$upsertResponseProfile;
    $profileId = $arrayp[0]->id;

    return $profileId;
  }


  /**
   * Update a user's profile infomation.
   * This method will update both the wordpress and SalesForce database.
   * @param mixed[] $member_info The member info to be updated.
   * @return Object representing the results of the SF upsert operation.
   */
  public function update_user_profile($member_info) {
    //Array ( [user_name] => 0033B000001krkEQAQ [email] => cyamamoto@tammantech.com [birth_month] => 3 [birth_year] => 1901 [zip] => 19133 [avatar] => susan.png )
    //update zip if there are changes
    $cObject = new stdClass();
    $cObject->Email = $member_info['email'];
    if(isset($member_info['zip']) && !empty($member_info['zip'])){
      $cObject->MailingPostalCode = $member_info['zip'];
    } else {
      $cObject->fieldsToNull = array("MailingPostalCode");
    }

    $upsertResponse = $this->SFConnection->upsert(
      'Email', array ($cObject), 'Contact'
    );

    $sObject = new stdClass();
    //$sObject->Name = $member_info['user_name'];
    $sObject->Username__c = $member_info['email'];
    if(isset($member_info['birth_month']) && !empty($member_info['birth_month'])){
      $sObject->Birth_Month__c = intval($member_info['birth_month']);
      $sObject->Birth_Year__c = intval($member_info['birth_year']);

      $type = "iep";
      $birthdate_info = $member_info['birth_month']."/01/".$member_info['birth_year']; //03/01/1951
      $date_result = benefitscheckup::getDateRange($birthdate_info, $type);
      $enrollment_sdate = $date_result[start_month]."/".$date_result[start_day]."/".$date_result[start_year];
      $enrollment_edate = $date_result[end_month]."/".$date_result[end_day]."/".$date_result[end_year];

      //update MMM_Profile__c IEP info based of birth data
      //$sObject->Medicare_IEP_Birth_Month__c = $member_info['birth_month'];
      $sObject->Medicare_IEP_Start_Date__c = date(DATE_ATOM, strtotime($enrollment_sdate));
      $sObject->Medicare_IEP_End_Date__c = date(DATE_ATOM, strtotime($enrollment_edate));

    } else {
      $sObject->fieldsToNull = array(
        "Birth_Month__c",
        "Birth_Year__c",
        "Medicare_IEP_Start_Date__c",
        "Medicare_IEP_End_Date__c"
      );
    }
    //$sObject->Contact_Zipcode__c = $member_info['zip'];

    $upsertResponse = $this->SFConnection->upsert(
      'Username__c', array ($sObject), 'MMM_Profile__c'
    );
    return $upsertResponse;
  }


  /**
   * Checkbox list of newsletters, pre-check the subscribed ones
   * If submitted changes, send new subscribed/unsubscribed items.
   * @return String A string of HTML containing checkboxes.
   */
  public function get_subscriptions() {
    $auth = BAuth::get_instance();
    $user_data = (array) $auth->userData;
    $profile_id = $user_data['user_name'];

    //Get 'Name', 'MMM_Profile__c' from current user
    //then get contact Id (Related_Contact__c)
    $user_query = "SELECT Related_Contact__c from MMM_Profile__c where Id = '"
      . $profile_id . "'";
    $user_response = $this->SFConnection->query(($user_query));
    $arrayu = (array)($user_response->records[0]);
    $contact_id = $arrayu['Related_Contact__c'];

    $members_query = "SELECT CampaignId FROM CampaignMember where ContactId = '" . $contact_id . "'";
    $members_response = $this->SFConnection->query(($members_query));

    $campaigns = array();
    foreach ($members_response->records as $record) {
      $arraym = (array)$record;
      array_push($campaigns, $arraym['CampaignId']);
    }

    //MMM
    $query = "SELECT Id, Name from Campaign where Campaign_Project_Area_Product_Line__c = 'NCOAS' and Sub_Project_Product__c = 'MMM'";

    if(isset($_SESSION["niscdomain"])){
      //NISC
      $query = "SELECT Id, Name from Campaign where Campaign_Project_Area_Product_Line__c = 'NCOA'";
    }

    $response = $this->SFConnection->query(($query));

    $subscriptions = "";
    foreach ($response->records as $record) {
      $arrayr = (array)$record;

      $subscriptions .= '<p class="subsc_item">';
      $subscriptions .= '<input type="hidden" name="campaign[]" value="' . $arrayr['Id'] . '" /><input type="checkbox" name="subscription[]" value="' . $arrayr['Id'] . '" ';

      //if subscribed mark as checked
      if(in_array($arrayr['Id'], $campaigns)){
        $subscriptions .= 'checked';
      }

      $subscriptions .= ' /> <span>'. $arrayr['Name'] . '</span>';
      $subscriptions .= '</p>';
    }

    $subscriptions .= '<p class="subsc_hide"><input type="hidden" value="' . $contact_id . '" id="mmm_profile_contact_id" name="mmm_profile_contact_id" /></p>';

    return $subscriptions;
  }


  /**
   * Allows subscriptions to be updated for a given contact.
   * @param mixed[] $campaigns
   * @param mixed[] $checked_subscriptions
   * @param String $contact_id The id of the contact to update.
   * @return void
   */
  public function update_subscriptions($campaigns, $checked_subscriptions, $contact_id) {
    $auth = BAuth::get_instance();
    $user_data = (array) $auth->userData;
    $profile_id = $user_data['user_name'];

    //campaign
    foreach ($campaigns as $record) {
      //for each campaign check if subscribed or not
      if(in_array($record, $checked_subscriptions)){
        //subscribe
        $sObject = new stdclass();
        $sObject->CampaignId = $record;
        $sObject->ContactId = $contact_id;
        $createResponse = $this->SFClient->create(
          array($sObject), 'CampaignMember'
        );
      } else {
        //unsubscribe
        $contact_query = "SELECT Id from CampaignMember where CampaignId = '" . $record . "' and ContactId='" . $contact_id . "'";
        $contact_response = $this->SFConnection->query(($contact_query));
        $arrayc = (array)($contact_response->records[0]);
        $campaign_member_id = $arrayc['Id'];
        $deleteResult = $this->SFClient->delete($campaign_member_id);
      }
    }
  }


  /**
   * Update_screeningid
   * @param String $member_info
   * @param String $profile_id
   * @return mixed[]
   */
  public function upsert_mqc_report($member_info, $profile_id = NULL) {
    //upsert mqc report info
    //-------------------------------
    //get current profile ID
    $auth = BAuth::get_instance();
    $user_data = (array) $auth->userData;
    $username = $user_data['user_name'];

    if(!isset($user_data) || empty($user_data)){
      $username = $profile_id;
    }else{
      $username = $user_data['user_name'];
    }

    //update MMM_Assessment_History__c
    $sObject_a = new stdclass();
    $sObject_a->MMM_Profile__c = $username;
    $sObject_a->Assessment_Date__c = date(DATE_ATOM, strtotime($member_info['report_date']));
    $sObject_a->Assessment_Screening_ID__c = $member_info['screening_id'];
    $sObject_a->Assessment_Subset_ID__c = $member_info['subset_id'];

    if(isset($member_info['enrollment_sdate']) && !empty($member_info['enrollment_sdate'])){
      $sObject_a->Assessment_IEP_Start_Date__c = date(DATE_ATOM, strtotime($member_info['enrollment_sdate']));
    }
    if(isset($member_info['enrollment_edate']) && !empty($member_info['enrollment_edate'])){
      $sObject_a->Assessment_IEP_End_Date__c = date(DATE_ATOM, strtotime($member_info['enrollment_edate']));
    }
    if(isset($member_info['enrollment_mdate']) && !empty($member_info['enrollment_mdate'])){
      $sObject_a->Assessment_IEP_Birth_Month__c = intval($member_info['enrollment_mdate']);
    }
    if(!empty($member_info['cid'])){
      $sObject_a->External_Campaign_ID__c = $member_info['cid'];
    }
    if(!empty($member_info['pid'])){
      $sObject_a->External_Partner_ID__c = $member_info['pid'];
    }

    if(isset($_SESSION['mqc_client'])){
      $mystring = $_SESSION['mqc_client'];
      $pos = strrpos($mystring, "-");
      if ($pos === false) {
          // not found...
      }else{
        $mystring = substr($mystring, ($pos + 1));
        $sObject_a->Assessment_type__c = $mystring;
      }

    }

    $assessResponse = $this->SFClient->create(
      array ($sObject_a), 'MMM_Assessment_History__c'
    );

    return (array)$assessResponse[0];
  }
}

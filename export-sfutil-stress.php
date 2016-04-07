<?php

global $wpdb;
include ('../../../wp-load.php');

?>

  <h2>Salesforce Stress Test</h2>
  <p>
    <form action="" method="post">
    <input type="submit" name="submit" value="Export" />
    </form>
  </p>

<?php 

if (isset($_POST['submit'])) 
{ 
  // Process Form
  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bUtils.php');
  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bSettings.php');
  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bSforce.php');

  //salesforce 
  $sForce = BSforce::get_instance();
  $mySforceConnection = new SforceEnterpriseClient();
  $mySoapClient = $mySforceConnection->createConnection(ncoas_membership_PATH  . "soapclient/enterprise.wsdl.xml");

  //$errors = array();
  $counter = 5000;

  for($i = 1; $i <= $counter; ++$i) {
      try{ 
        $member_info['email'] = 'mail'.$i.'@mail.com';
        $member_info['first_name'] = 'first'.$i;
        $member_info['last_name'] = 'last'.$i;

        if(intval($i)%2 == 0){
          $member_info['screening_id'] = '5074035';
          $member_info['subset_id'] = '75';
        }

        // -----------------------------------------
        // create user: SF contact + MMM profile
        // -----------------------------------------
        $member_id = $sForce::new_user_profile($member_info);
        //array_push($errors, $member_info['email']);

        $result = $wpdb->update( 
          $wpdb->prefix . "ncoas_members", 
          array('error_handler' => ''),   
          array( 'member_id' => '173' )
        );

        // -----------------------------------------
        // Assessment: SF contact + MMM profile
        // -----------------------------------------
        if(!empty($member_info['screening_id'])){

          $settings = BSettings::get_instance();
          $pwd = $settings->get_value('membership-pwd');
          $token = $settings->get_value('membership-token');
          $mylogin = $mySforceConnection->login("ncoasapi@ncoa.org", $pwd . $token);

          $user_query = "SELECT Assessment_Screening_ID__c, Assessment_Subset_ID__c from MMM_Assessment_History__c where MMM_Profile__c = '" . $member_id . "' and Assessment_Screening_ID__c='" . $member_info['screening_id'] . "' and Assessment_Subset_ID__c='" . $member_info['subset_id'] . "'";
          $user_response = $mySforceConnection->query(($user_query));
          $arrayu = (array)($user_response->records[0]);

          if(empty($arrayu)){
            //just add if there is no entry
            $sObject_a = new stdclass();
            $sObject_a->MMM_Profile__c = $member_id;
            $sObject_a->Assessment_Date__c = $member_info['report_date'];
            $sObject_a->Assessment_Screening_ID__c = $member_info['screening_id'];
            $sObject_a->Assessment_Subset_ID__c = $member_info['subset_id'];
            if(!empty($member_info['enrollment_sdate'])){
              $sObject_a->Assessment_IEP_Start_Date__c = date(DATE_ATOM, strtotime($member_info['enrollment_sdate']));
            }
            if(!empty($member_info['enrollment_edate'])){
              $sObject_a->Assessment_IEP_End_Date__c = date(DATE_ATOM, strtotime($member_info['enrollment_edate']));
            }
            if(!empty($member_info['enrollment_mdate'])){
              $sObject_a->Assessment_IEP_Birth_Month__c = $member_info['enrollment_mdate'];
            }                    
            if(!empty($member_info['cid'])){
              $sObject_a->External_Campaign_ID__c = $member_info['cid'];
            } 
            if(!empty($member_info['pid'])){
              $sObject_a->External_Partner_ID__c = $member_info['pid'];
            }

            $assesResponse = $mySforceConnection->create(array ($sObject_a), 'MMM_Assessment_History__c');
          }
          
        }


      }catch (Exception $e) {
        //array_push($errors, $e);

        $wpdb->update( 
          $wpdb->prefix . "ncoas_members",    
          array( 'error_handler' => $e ),  
          array( 'member_id' => '173' )
        );
      }
  }

  echo "<h3>Export Result</h3>";
  echo "<pre>";
  print_r($errors);
  echo "</pre>";
 
}

?>
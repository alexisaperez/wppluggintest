<?php

global $wpdb;
include ('../../../wp-load.php');

  $query = 'SELECT member_id, user_name, email, password, first_name, last_name, screening_id, subset_id, enrollment_sdate, enrollment_edate, enrollment_mdate, report_date, sid, cid, pid FROM ' . $wpdb->prefix . 'ncoas_members ORDER BY member_id ASC';
  $user_data = $wpdb->get_results($query);
  $numdata = 100;
  $valrecords = count($user_data);

  if (!empty($user_data)) {
    $totalbtn = ceil($valrecords/$numdata);

    ?>
    <h2>Salesforce Export Util</h2>
    <p>
      <form action="" method="post">
        
    <?php 
    
    for($i = 1; $i <= $totalbtn; ++$i) {
        echo '<input type="submit" name="submit" value="Export ' . $i . '" /> ';
    }

    ?>
      </form>
    </p>
    <?php 
  }


if (isset($_POST['submit'])) 
{ 
  // Process Form
  //$totalbtn
  $record = intval(str_replace("Export ", "", $_POST['submit']));
  $recordtotal  = (intval($record) * $numdata);
  $recordinit = ($recordtotal - $numdata)+1;

  if($recordtotal > $valrecords){
    $recordtotal = $valrecords;
  }
  echo 'Export ' . $record . ', record ' . $recordinit . ' to ' . $recordtotal;

  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bUtils.php');
  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bSettings.php');
  include_once(WP_PLUGIN_DIR . '/ncoas-membership/classes/class.bSforce.php');

  //$errors = array();
  $counter = 1;

  if (!empty($user_data)) {

    //salesforce 
    $sForce = BSforce::get_instance();
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(ncoas_membership_PATH  . "soapclient/enterprise.prod.wsdl.xml");
    
    for($i = $recordinit; $i <= $recordtotal; ++$i) {
      
      $member_info = (array) $user_data[($i-1)]; 

      try{ 

        // -----------------------------------------
        // create user: SF contact + MMM profile
        // -----------------------------------------
        $member_id = $sForce::new_user_profile($member_info);
        //array_push($errors, $member_info['email']);

        $result = $wpdb->update( 
          $wpdb->prefix . "ncoas_members", 
          array( 
                'user_name' => $member_id,
                'error_handler' => ''
          ),   
          array( 'member_id' => $member_info['member_id'] )
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
            $sObject_a->Assessment_Date__c = date(DATE_ATOM, strtotime($member_info['report_date']));
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
          array( 'member_id' => $member_info['member_id'] )
        );
      }
      $counter = $counter + 1;

    }
  }

  echo "<h3>Export complete!</h3>";
 
}

?>
<div class="mmm-saved-mqc">

<?php
  global $message;

  $screening_id = $auth->get('screening_id');
  $subset_id = $auth->get('subset_id');
  $ext = bUtils::get_url_ext();
  $mqc_url = BUtils::get_mqc_url() . $ext;

  if($message[message]!=""){
    echo '<h2 class="h3_like_msg">'.$message[message].'</h2>';
  }

  if($screening_id !== ""){
    //saved
    echo '<p>You last took the Medicare QuickCheck on '. $auth->get('report_date') .' (EST). <a href="'.get_permalink($mqc_url).'">Take the Medicare QuickCheck again.</a></p>';
    
    // Process All Other Questionnaires
    $options = get_option( 'benefitscheckup-group' );
    $debug = ( isset( $options['flag-debug'] ) && $options['flag-debug'] == '1' );
    
    $response = benefitscheckup::runGeneric( 'getPersonalReport', array( $screening_id, $subset_id ));
    $output = array( 'submitted' => $submitted, 'transmitted' => '', 'error' => '', 'received' => '', 'shipinfo' => '', 'personal' => array(), 'fields' => array(), 'next_subset_id' => '' );

    $output['submitted'] = array( 'screening_id' => $screening_id , 'subset_id' => $subset_id);
    $output['received'] = $response;

    $sections = array( 'summary', 'medicare_narrative', 'enrollment_period', 'recommendations', 'waystogethelp', 'sidebar' );
    $output['personal'] = array();
    foreach( $sections as $h => $section ) {
      foreach( $response as $i => $val ) {

        // One Section At A Time
        if( $val['SECTION_CODE'] != $section ) { continue; }

        // Store
        $output['personal'][$section][$val['SORT_ORDER']] = $response[$i];
      }
    }

    // Overload Summary Recommendation Content
    $zip_copy = '';
    $birth_copy = '';
    if( isset( $output['personal']['summary'][1]['REPORT_CONTENT'] ) ) {
      $response = benefitscheckup::runGeneric( 'getSummaryContent', array( $screening_id ), false );
      $output['personal']['summary'][1]['REPORT_CONTENT'] = $response;
      $zip_copy = $response;
      $birth_copy = $response;
    }

    $birth_intro = "Your birth date is ";
    $pos = strpos($birth_copy, $birth_intro);
    if($pos >= 0){
      $len = strlen($birth_intro) + 10;
      $rest = substr($zip_copy, $pos, $len);
      $birthdate_info = str_replace("$birth_intro", "", $rest);

      if(!isset( $_COOKIE['enrollment_startdate'] )){        
        //information from parsed summary
        $enrollment_mdate = intval(substr($birthdate_info, 0, 2));

        $type = "iep";
        $date_result = benefitscheckup::getDateRange($birthdate_info, $type);
        $enrollment_sdate = $date_result[start_month]."/".$date_result[start_day]."/".$date_result[start_year];
        $enrollment_edate = $date_result[end_month]."/".$date_result[end_day]."/".$date_result[end_year];

        //add to database
        BUtils::update_enrollment_information($enrollment_sdate, $enrollment_edate, $enrollment_mdate);

        //set cookie
        setcookie( 'enrollment_startdate', $enrollment_sdate, 0, '/');
        setcookie( 'enrollment_enddate', $enrollment_edate, 0, '/');
        setcookie( 'mqc_birth_month', $enrollment_mdate, 0, '/');

      } 
    }

    $pos = strpos($birth_copy, '(zip');
    if($pos >= 0){
      $rest = substr($birth_copy, $pos, 16);
      $mqc_zip = str_replace("(zip code: ", "", $rest);
      
      $shipinfo = benefitscheckup::runGeneric( 'getShipContentObject', array( $mqc_zip ), false );
      $output['shipinfo'] = $shipinfo;

      $args = array( 'data' => $output, 'mqc_zip' => $mqc_zip, 'debug' => $debug );
      echo benefitscheckup::requireToVar( 'views/results.html.php', $args );
    }

  }else{
    //none saved
    echo '<p>You have not yet taken the Medicare QuickCheck. <a href="'.get_permalink($mqc_url).'">Take the Medicare QuickCheck now to get a free personalized report.</a></p>';
  }
?>
	
</div>

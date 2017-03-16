<?php
  require_once("file_functions.php");

  $file_list = file_list_array('json');

  for($i = 0 ; $i < count($file_list); $i ++) {
    if(preg_match("/TPE/", $file_list[$i])) {
      if (!isset($start)) {
        $start = $i;
      }
      $end = $i;
    }
    if(preg_match("/702/", $file_list[$i])) {
      $test = $i;
    }
  }

  $jsonfile = fopen($file_list[$test], 'r');
  $json_data = fgets($jsonfile);
  fclose($jsonfile);

  $meta_tag = array('session',
                    'title',
                    'date',
                    'start_time',
                    'end_time',
                    'location',
                    'chairman',
                    'note_taker',
                    'attend_committee',
                    'attend_unit');

  $json_data = json_decode($json_data, $assoc=TRUE);
  foreach($meta_tag as $key => $value) {
    print_r($json_data[$meta_tag[$key]]);
    echo '<br>';
  }

  // require_once("commission_db_connect.php");
  // $dbh = null;

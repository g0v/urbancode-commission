<?php
  require_once("object_definition.php");

  //development example setup
  $filename = "TPE_O_702_1";
  $filepath = "./json/TPE/$filename.json";

  //extract admin and round variable from file name
  preg_match("/^(.*?)_([O|N])_([0-9]+)_(.*?)$/", $filename, $fmatch);
  $admin = $fmatch[1].$fmatch[2];
  $round = $fmatch[4];

  //read in json contect and decode it
  $jsonfile = fopen($filepath, 'r');
  $json_data = fgets($jsonfile);
  fclose($jsonfile);
  $json_data = json_decode($json_data, $assoc=TRUE);

  //declare note obj
  $note = new note();
  $note->admin = $admin;
  $note->round = $round;
  $meta_tag = $note->getKeys();
  foreach($meta_tag as $value) {
    if(isset($json_data[$value])) {
      $note->$value = $json_data[$value];
    }
  }
  $note->setNoteCode();
  $note->setJson(array('attend_committee', 'attend_unit'));

  foreach($json_data as $key => $value) {
    if(preg_match("/item/", $key)) {
      createItem($value);
      break;
    }
  }

  function createItem($item_array) {
    $case_item = new case_item;
    print_r($item_array);
  }
  // require_once("commission_db_connect.php");
  // $dbh = null;

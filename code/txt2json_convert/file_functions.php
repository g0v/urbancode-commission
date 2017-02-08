<?php
function check_list() {
  $list = glob('./txt/*/');
  foreach($list as &$folder_name) {
    $folder_name = preg_replace('/txt/', 'json', $folder_name);
    if(!file_exists($folder_name)) {
      mkdir($folder_name);
    }
  }
}

function file_list_array() {
  $list = glob('./txt/*/');
  $total_list = array();
  foreach($list as $folder_name) {
    $file_list = glob("$folder_name/*.txt");
    foreach($file_list as $file) {
      array_push($total_list, $file);
    }
  }
  return($total_list);
}
?>

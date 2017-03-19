<?php
function check_list($dir = 'txt', $replace = 'json') {
  $list = glob("./$dir/*/");
  foreach($list as &$folder_name) {
    $folder_name = preg_replace('/txt/', $replace, $folder_name);
    if(!file_exists($folder_name)) {
      mkdir($folder_name);
    }
  }
}

function file_list_array($dir = 'txt') {
  $list = glob("./$dir/*/");
  $total_list = array();
  foreach($list as $folder_name) {
    $file_list = glob("$folder_name/*.$dir");
    foreach($file_list as $file) {
      array_push($total_list, $file);
    }
  }
  return($total_list);
}

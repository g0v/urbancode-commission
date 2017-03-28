<?php
Class titlePack {
  function getTitleString() {
    $var_array = get_object_vars($this);
    $tag_array = array();
    $title_array = array();
    foreach($var_array as $k => $value) {
      array_push($tag_array, $k);
      array_push($title_array, $value);
    }
    $title = implode("|", $title_array);
    return($title);
  }
  function pregTag($string) {
    $var_array = get_object_vars($this);
    foreach($var_array as $k => $value) {
      if(preg_match("/$value/", $string)) $found = $k;
    }
    $found = isset($found)? $found : 'not found';
    return($found);
  }
}

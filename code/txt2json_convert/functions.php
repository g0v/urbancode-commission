<?php
function printarray($array) {
  foreach($array as $k => $v) {
    if(!is_array($v)) {
      echo '[' . $k . '] => ' . $v . '<br>';
    } else {
      echo '[' . $k . '] => ';
      printarray($v);
    }
  }
}

function find_index($fulltxt, $title_string) {
  $index = array();
  foreach($fulltxt as $k => $v) {
    if(preg_match("/$title_string/", $v)) {
      array_push($index, $k);
    }
  }
  return($index);
}

function slice_my_array($fulltxt, $index_array) {
  array_push($index_array, count($fulltxt));
  $sliced_txt = array();
  for($i = 0; $i < count($index_array); $i++) {
    if($i == 0) {
      $slice_start = 0;
    } else {
      $slice_start = $index_array[$i-1];
    }

    if(count($index_array) == 1) {
      $slice_length = count($fulltxt) - $slice_start;
    } else {
      $slice_length = $index_array[$i] - $slice_start;
    }
    array_push($sliced_txt, array_slice($fulltxt, $slice_start, $slice_length));
  }
  return($sliced_txt);
}

function combine_array_sentence($txt_array) {
  //compose sentences in section_array
  //txt_array need to be an array of txt array
  //e.g. array(array(of txt),array(of txt)...)
  for($n = 0; $n < count($txt_array); $n++) {
    for($n_line = 0; $n_line < count($txt_array[$n]); $n_line++) {
      if(preg_match('/：$|。$/', $txt_array[$n][$n_line])) {
        $txt_array[$n][$n_line] .= '\\r\\n';
      }
    }
    $txt_array[$n] = implode("", $txt_array[$n]);
    $txt_array[$n] = rtrim($txt_array[$n], '\\r\\n');
  }
  return($txt_array);
}

function clean_empty($txt_array) {
  for($i = 0; $i < count($txt_array); $i++) {
    if(count($txt_array[$i]) == 0) {
      unset($txt_array[$i]);
    }
  }
  return(array_values($txt_array));
}
?>

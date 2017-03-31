<?php
$zh_number_cap = '壹|貳|參|肆|伍|陸|柒|捌|玖|拾|零';
$zh_number_low = '一|二|三|四|五|六|七|八|九|十|〇';

function file_list_array($dir = 'txt', $filter = 'all') {
  $list = glob("./$dir/*");
  foreach($list as $k => $file) {
    if(!preg_match("/.$dir$/", $file)) {
      unset($list[$k]);
    }
    if($filter != 'all') {
      if(!preg_match("/$filter/", $file)) {
        unset($list[$k]);
      }
    }
  }
  $list = array_values($list);
  return($list);
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

function zh2Num($numstr) {
  global $zh_number_low;
  $zh_number_tenfold = array("廿","卅","卌");
  $zh_tenfold_string = implode("|", $zh_number_tenfold);

  $zh_number_array = explode("|", $zh_number_low);
  $numstr_array = mbStringToArray($numstr);
  $new_array = array();
  foreach($numstr_array as $place => $numchar) {
    if($numchar == '十') {
      if($place == 0) {
        $numchar = 1;
      } else if($place == count($numstr_array)-1) {
        $numchar = 0;
      } else {
        continue;
      }
    } else if(preg_match("/$zh_tenfold_string/", $numchar)) {
      foreach($zh_number_tenfold as $k => $value) {
        if($numchar == $value) $numchar = ($k+2) * 10;
      }
    } else if(preg_match("/$zh_number_low/", $numchar)){
      foreach($zh_number_array as $k => $value) {
        if($numchar == $value) $numchar = $k+1;
      }
    }
    array_unshift($new_array, $numchar);
    $num = 0;
    foreach($new_array as $pos => $int) {
      $num = $num + $int * pow(10, $pos);
    }
  }
  return($num);
}

function mbStringToArray ($string) {
    $strlen = mb_strlen($string);
    while ($strlen) {
        $array[] = mb_substr($string,0,1,"UTF-8");
        $string = mb_substr($string,1,$strlen,"UTF-8");
        $strlen = mb_strlen($string);
    }
    if(isset($array)) return($array);
}

function findDate($txt_line) {
  global $zh_number_low;
  preg_match('/中華民國(.*)年/', $txt_line, $m_year);
  preg_match('/年(.*)月/', $txt_line, $m_month);
  preg_match('/月(.*)日/', $txt_line, $m_day);
  $m_date = array(trim($m_year[1]), trim($m_month[1]), trim($m_day[1]));

  foreach($m_date as &$value) {
    if(preg_match("/$zh_number_low/", $value)) {
      $value = zh2Num($value);
    }
  }
  $m_date[0] += 1911;
  $m_date = implode("/", $m_date);
  return($m_date);
}

function findTime($txt_line) {
  global $zh_number_low;
  $zh_number = $zh_number_low."|廿|卅|卌";

  $noon = (preg_match("/下午/", $txt_line))? 'after' : 'before';
  $txt_line = preg_replace("/\(|\)|（|）|時間：/", "", $txt_line);
  if(preg_match("/散會(：)?/", $txt_line)) $txt_line = preg_split("/散會(：)?/", $txt_line)[1];
  $s_time = preg_split("/點|時|：|:/", $txt_line);
  $s_time = preg_replace("/(上|下|中)午|分/", "", $s_time);

  foreach($s_time as $k => &$txt) {
    if(preg_match("/\d/", $txt)) {
      preg_match_all("/\d+/", $txt, $match);
      $txt = implode("", $match[0]);
    } else if(preg_match("/、/", $txt)) {
      unset($s_time[$k]);
    } else if(preg_match("/$zh_number/", $txt)) {
      preg_match_all("/$zh_number/", $txt, $match);
      $txt = implode("", $match[0]);
      $txt = zh2num($txt);
    } else if($txt == "") {
      $txt = '00';
    } else {
      unset($s_time[$k]);
    }
  }
  $s_time = array_values($s_time);
  if($noon == 'after' && $s_time[0] < 12) $s_time[0] += 12;
  if(!isset($s_time[1])) $s_time[1] = "00";
  $s_time = implode(":", $s_time);

  return($s_time);
}

function fixLetter($txtline) {
  // array({慣用字},{異體字})
  $pair_array = array(array("：", "︰"),
                      array("錄", "錄"),
                      array("年", "年"),
                      array("論", "論"),
                      array("參", "參"),
                      array("理", "理"),
                      array("說", "說"),
                      array("都", "都"),
                      array("見", "見"),
                      array("六", "六"));
  foreach($pair_array as $pair) {
    $txtline = preg_replace("/$pair[1]/", $pair[0], $txtline);
  }
  return($txtline);
}

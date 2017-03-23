<html>
<head>
  <meta charset="utf-8">
</head>
<body>
<?php
  include_once('functions.php');
  include_once('file_functions.php');

  $zh_number_cap = '壹|貳|參|肆|伍|陸|柒|捌|玖|拾';
  $zh_number_low = '一|二|三|四|五|六|七|八|九|十';
  $section_title = '宣讀|報告事項|審議事項|臨時動議|討論事項|研議事項';

  $file_list = file_list_array('txt', 'TPE');
  // print_r($file_list);

  // record_parse('./txt/TPE/TPE_O_655_1.txt');
  for($cnt = 0; $cnt < count($file_list); $cnt++) {
    record_parse($file_list[$cnt]);
  }

function record_parse($target_file) {
  preg_match('~([O|N]_)(.*?)(_\d\.txt)~',$target_file, $target);
  $target = $target[2];

  $txtfile = fopen($target_file, 'r');
  $fulltxt = array();

  //read in txt lines
  while(!feof($txtfile)) {
    $txtline = trim(fgets($txtfile));
    $txtline = mb_convert_encoding($txtline, "UTF-8");
    $txtline = preg_replace("/ +/", "", $txtline);
    $txtline = preg_replace("/︰/", "：", $txtline);
    $txtline = preg_replace("/錄/", "錄", $txtline);
    $txtline = preg_replace("/年/", "年", $txtline);

    //drop page number or empty lines
    if(strlen($txtline) != 0) {
      // $page_test = preg_replace("/ +/", "", $txtline);
      if((preg_match("/-[0-9]+-/", $txtline))) {
      } else if((preg_match("/^第[0-9]+頁\/第[0-9]+頁$/", $txtline))) {
      } else if((preg_match("/^第-[0-9]+-頁，共[0-9]+頁$/", $txtline))) {
      } else if((preg_match("/^[0-9]+$/", $txtline))) {
      } else {
        array_push($fulltxt, $txtline);
      }
      // unset($page_test);
    }
  }
  fclose($txtfile);

  if(empty($fulltxt)) {
    return("empty file: $target_file");
  }

  //basic parameters setup
  global $zh_number_cap;
  global $zh_number_low;
  global $section_title;
  $record_end = '散會';

  //identify item index position
  $section_index = find_index($fulltxt, "(($zh_number_cap).*.($section_title))|$record_end");
  foreach($fulltxt as $k => $v) {
    if(preg_match("/散會/", $v)) {$end_index = $k;}
  }

  if(!isset($end_index)) {$end_index = count($fulltxt)-1;}

  //slice txt into parts by index position
  $parse_txt = slice_my_array($fulltxt, $section_index);

  //parse commission note header (if exist)
  $item_start = 0; //save start position of items in $parse_txt
  if(preg_match("/都市計畫委員會|(紀|記)錄/", $parse_txt[0][0])) {
    array_push($parse_txt[0], $fulltxt[$end_index]);
    $header_array = header_parse($parse_txt[0], $target);
    $item_start = 1; //if header exist, set item start as $parse_txt[1]
  }

  $item_array = array();
  foreach($parse_txt as $k => $txt_part) {
    if($k < $item_start) continue;
    if(preg_match("/宣讀/", $txt_part[0])) {
      $item_array['read_item'] = $txt_part;
    } else if(preg_match("/報告事項/", $txt_part[0])) {
      $item_array['report_item'] = $txt_part;
    } else if(preg_match("/確認事項/", $txt_part[0])) {
      $item_array['confirm_item'] = $txt_part;
    } else if(preg_match("/審議事項/", $txt_part[0])) {
      $item_array['deliberate_item'] = $txt_part;
    } else if(preg_match("/研議事項/", $txt_part[0])) {
      $item_array['discuss_item'] = $txt_part;
    } else if(preg_match("/臨時動議/", $txt_part[0])) {
      $item_array['extempore_item'] = $txt_part;
    }
  }
  array_walk($item_array, 'item_parse');

  foreach($item_array as &$item) {
    $item = clean_empty($item);
  }

  $json_array = array();
  if(isset($header_array)) {
    foreach ($header_array as $key => $value) {
      $json_array[$key] = $value;
    }
  }
  if(isset($item_array)) {
    foreach ($item_array as $key => $value) {
      $json_array[$key] = $value;
    }
  }

  $output_file = preg_replace("/txt/", "json", $target_file);
  echo $output_file."<br>";

  $json_txt = json_encode($json_array, JSON_UNESCAPED_UNICODE);

  $output_json = fopen($output_file, "w");
  fwrite($output_json, $json_txt);

}

  function header_parse($header_txt, $target) {
    $header_array = array();

    // $header_array['title'] = '臺北市都市計畫委員會第' . $target . '次委員會議紀錄';
    $header_array['title'] = $header_txt[0];
    $header_array['session'] = $target;

    for($i = 0; $i < count($header_txt); $i++) {
      $line_txt = $header_txt[$i];
      if(preg_match("/^時間/", $line_txt)) {
        //parse date
        $header_array['date'] = findDate($line_txt);

        //parse start time
        preg_match('/午(.*)時/', $line_txt, $t_hour);
        if(preg_match('/分/', $line_txt)) {
          preg_match('/.*時(.*)分/', $line_txt, $t_minute);
        } else {
          $t_minute = array('','00');
        }
        $s_time = trim($t_hour[1]) . ':' . trim($t_minute[1]);
        $header_array['start_time'] = $s_time;
      } else if(preg_match("/散會/", $line_txt)) {
        //parse end time
        $header_array['end_time'] = findTime($line_txt);
      } else if(preg_match("/地點/", $line_txt)) {
        //parse location
        preg_match('/：(.*)$/', $line_txt, $location_txt);
        $header_array['location'] = $location_txt[1];
      } else if(preg_match('/^主席|彙整/', $line_txt)) {
        //parse chairman
        preg_match('/主席：(.*)(彙整|紀錄)/', $line_txt, $chairman_txt);
        $chairman_txt = preg_replace('/兼主任委員/', '', $chairman_txt);
        $header_array['chairman'] = $chairman_txt[1];

        //parse note_taker
        preg_match('/(彙整|紀錄)：(.*)$/', $line_txt, $note_taker_txt);
        $header_array['note_taker'] = $note_taker_txt[2];
      } else if(preg_match('/出席委員/', $line_txt)) {
        //parse attend committe
        if(preg_match('/詳簽到表/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_committe_txt);
          $attend_committe_txt = array($attend_committe_txt[1]);
          $header_array['attend_committee'] = $attend_committe_txt;
        }
      } else if(preg_match('/列席單位/', $line_txt)) {
        //parse attend committe
        if(preg_match('/詳簽到表/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_unit_txt);
          $attend_unit_txt = array($attend_unit_txt[1]);
          $header_array['attend_unit'] = $attend_unit_txt;
        }
      } else {
        if($i == count($header_txt)-1) {
          $header_array['end_time'] = findTime($line_txt);
        }
      }
    }
    foreach($header_array as $k => &$value) {
      if(!is_array($value)) {
        $value = trim($value);
      }
    }
    return($header_array);
  }

  function item_parse(&$item_txt) {
    global $zh_number_cap;
    global $zh_number_low;
    global $section_title;

    //if the first line of item_txt if the title of sections, unset it
    if(preg_match("/宣讀/", $item_txt[0])) {
      $item_txt = array(array('case_title' => implode("", $item_txt)));
      return;
    }
    if(preg_match("/$section_title/", $item_txt[0])) {
        unset($item_txt[0]);
        $item_txt = array_values($item_txt);
    }
    //partition cases by "案名" or section titles (e.g. 審議事項二)
    $case_index = find_index($item_txt, '^案名：[\s\S]|'.$section_title);
    $case_array = slice_my_array($item_txt, $case_index);

    //parse case contents
    $case_parsed = array();
    for($i = 0; $i < count($case_array); $i++) {
      if(count($case_array[$i]) > 0) {
        array_push($case_parsed, case_parse($case_array[$i]));
      }
    }
    $item_txt = $case_parsed;
  }

  function case_parse($case_txt) {
    global $zh_number_low;
    global $section_title;

    //partition $case_txt into two - content and petition parts
    $part_index = find_index($case_txt, '臺北市都市計畫委員會公民或團體所提意見綜理表');
    array_push($part_index, count($case_txt));
    $case_part = clean_empty(slice_my_array($case_txt, $part_index));

    //parse of content part ($case_part[0])
    $case_title = '^案名：';
    $description = '^(案情概要)?(說|說)明：';
    $committee_speak = '^委員發言摘要：';
    $response = '^發展局回應：';
    $resolution = '^決議(：|︰)';
    $add_resolution = '^附帶決議：';
    $attached ='^附件：';
    $section_tag = array('case_title', 'description', 'committee_speak', 'response',
                         'resolution', 'add_resolution', 'attached');

    $session_title = "$case_title|$description|$committee_speak|$response|$resolution|$add_resolution|$attached";
    $session_index = find_index($case_part[0], $session_title);
    $session_array = clean_empty(slice_my_array($case_part[0], $session_index));

    //parse session contents
    $case_output = array();
    for($i = 0; $i < count($session_array); $i++) {
      if(count($session_array[$i]) > 0) {
        $session_parsed = section_parse($session_array[$i]);

        //if the session is residual, break loop
        if(preg_match("/^($section_title)($zh_number_low)$/", $session_parsed[0])) {
          break;
        }

        for($t = 0; $t < count($section_tag); $t++) {
          $match = 1;
          $thistag = $$section_tag[$t];
          if (preg_match("/^$thistag/", $session_parsed[0])) {
            $case_output["$section_tag[$t]"] = $session_parsed;
          } else {
            if (!isset($match)) {
              $case_output['other'] = $session_parsed;
            }
          }
          unset($match);
        }
      }
    }

    //parse petition contents
    if(isset($case_part[1])) {
      $case_output['petition'] = petition_parse($case_part[1]);
    }
    return($case_output);
  }

  function petition_parse($petition_array) {
    $petition_count = '^案名|^編號';
    $reason = '陳情理由';
    $suggest = '建議辦法';
    $response = '市府回覆(意見)?';
    $adhoc = '專案小組審查意見';
    $resolution = '(委員會)?決議';
    $petition_title = "^$reason|^$suggest|^$response|^$resolution";
    $petition_tag = array('reason', 'suggest', 'response', 'adhoc', 'resolution');

    $petition_cnt = find_index($petition_array, $petition_count);
    $petition_case = clean_empty(slice_my_array($petition_array, $petition_cnt));

    $petition_output = array();

    for($n = 1; $n < count($petition_case); $n++) {
      if (preg_match('/^案名/', $petition_case[$n][0])) {
        $petition_case_name = combine_array_sentence(array($petition_case[$n]))[0];
        $petition_case_name = preg_replace('/^案名/', '', $petition_case_name);
      } else if (preg_match('/^編號/', $petition_case[$n][0])) {
        if(isset($petition_case_name)) $petition_this['petition_case'] = $petition_case_name;
        $petition_index = find_index($petition_case[$n], $petition_title);
        $petition_section = clean_empty(slice_my_array($petition_case[$n], $petition_index));

        for($k = 0; $k < count($petition_section); $k++) {
          if (preg_match('/^編號/', $petition_section[$k][0])) {
            $first_line = $petition_section[$k][0];
            $name_pos = strpos($first_line, "陳情人");

            $petition_num = substr($first_line, 0, $name_pos);
            $petition_num = trim(preg_replace("/編號/", "", $petition_num));
            $petition_this['petition_num'] = $petition_num;

            $name = substr($first_line, $name_pos);
            $name = trim(preg_replace("/陳情人/", "", $name));
            $petition_this['name'] = $name;
          } else {
            for($t = 0; $t < count($petition_tag); $t++) {
              $match = 1;
              $thistag = $$petition_tag[$t];
              if (preg_match("/^$thistag/", $petition_section[$k][0])) {
                $petition_this["$petition_tag[$t]"] = section_parse($petition_section[$k]);
              } else {
                if (!isset($match)) {
                  $petition_this['other_part'] = $petition_section[$k];
                }
              }
              unset($match);
            }
          }
        }
      } else {
        $petition_this['petition_other_tag'] = $petition_case[$n];
      }

      if(isset($petition_this)) {
        array_push($petition_output, $petition_this);
        unset($petition_this);
      }
    }
    return($petition_output);
  }

  function section_parse($section_txt) {
    global $zh_number_low;

    $section_title = explode('|', $zh_number_low);
    array_walk($section_title, function(&$value, $key) { $value = '^' . $value . '、'; });
    $section_title = implode('|', $section_title);

    $section_index = find_index($section_txt, $section_title);
    $section_array = clean_empty(slice_my_array($section_txt, $section_index));

    $section_array = combine_array_sentence($section_array);

    return($section_array);
  }
?>
</body>
</html>

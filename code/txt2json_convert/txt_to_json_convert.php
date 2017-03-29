<html>
<head>
  <meta charset="utf-8">
</head>
<body>
  <?php
  include_once('functions.php');

  $filter = 'TPE';
  include_once($filter."_variables.php");
  $section_title = $sectionPack->getTitleString();

  $file_list = file_list_array('txt', $filter);

  // record_parse('./txt/TPE_O_633_1.txt');
  for($cnt = 0; $cnt < count($file_list); $cnt++) {
    //TPE_O_657 and TPE_O_632 contains major issues
    if($filter = 'TPE' && preg_match("/TPE_O_(657|632)/", $file_list[$cnt])) continue;
    record_parse($file_list[$cnt]);
  }

  function record_parse($target_file) {
    global $zh_number_low;
    global $noteTitle;

    preg_match('~([O|N]_)(.*?)(_\d\.txt)~',$target_file, $target);
    $target = $target[2];

    $txtfile = fopen($target_file, 'r');
    $fulltxt = array();

    //read in txt lines
    while(!feof($txtfile)) {
      $txtline = trim(fgets($txtfile));
      $txtline = mb_convert_encoding($txtline, "UTF-8");
      //置換中文異體字
      $txtline = preg_replace("/ +/", "", $txtline);
      $txtline = preg_replace("/︰/", "：", $txtline);
      $txtline = preg_replace("/錄/", "錄", $txtline);
      $txtline = preg_replace("/年/", "年", $txtline);
      $txtline = preg_replace("/論/", "論", $txtline);
      $txtline = preg_replace("/參/", "參", $txtline);
      $txtline = preg_replace("/理/", "理", $txtline);

      //drop page number or empty lines
      if(strlen($txtline) != 0) {
        if((preg_match("/-[0-9]+-/", $txtline))) {
        } else if((preg_match("/^第-?[0-9]+-?頁(\/)?(，)?(第|共)[0-9]+頁$/", $txtline))) {
        } else if((preg_match("/^[0-9]+$/", $txtline))) {
        } else if((preg_match("/^($zh_number_low)+$/", $txtline))) {
        } else {
          array_push($fulltxt, $txtline);
        }
      }
    }
    fclose($txtfile);

    if(empty($fulltxt)) return("empty file: $target_file");

    //basic parameters setup
    global $zh_number_cap;
    global $zh_number_low;
    global $sectionPack;
    global $section_title;
    global $record_end;

    //identify item index position
    $section_index = find_index($fulltxt, "(($zh_number_cap).*.($section_title))|$record_end");
    foreach($fulltxt as $k => $v) {
      if(preg_match("/$record_end/", $v)) {$end_index = $k;}
    }

    if(!isset($end_index)) $end_index = count($fulltxt)-1;
    //slice txt into parts by index position
    $parse_txt = slice_my_array($fulltxt, $section_index);
    //parse commission note header (if exist)
    $item_start = 0; //save start position of items in $parse_txt
    if(preg_match("/$noteTitle/", $parse_txt[0][0])) {
      array_push($parse_txt[0], $fulltxt[$end_index]);
      $header_array = header_parse($parse_txt[0], $target);
      $item_start = 1; //if header exist, set item start as $parse_txt[1]
    }

    $item_array = array();
    foreach($parse_txt as $k => $txt_part) {
      if($k < $item_start) continue;
      $tag = $sectionPack->pregTag($txt_part[0]);
      if($tag != 'not found') $item_array[$tag] = $txt_part;
    }
    array_walk($item_array, 'item_parse');

    foreach($item_array as &$item) $item = clean_empty($item);

    $json_array = array();
    if(isset($header_array)) {
      foreach ($header_array as $key => $value) $json_array[$key] = $value;
    }
    if(isset($item_array)) {
      foreach ($item_array as $key => $value) $json_array[$key] = $value;
    }

    $output_file = preg_replace("/txt/", "json", $target_file);
    echo $output_file."<br>";

    $json_txt = json_encode($json_array, JSON_UNESCAPED_UNICODE);

    $output_json = fopen($output_file, "w");
    fwrite($output_json, $json_txt);
  }

  function header_parse($header_txt, $target) {
    global $record_end;

    $header_array = array();

    $header_array['title'] = $header_txt[0];
    $header_array['session'] = $target;

    $flag = 0;
    for($i = 0; $i < count($header_txt); $i++) {
      if($flag == 1) {$flag =0; continue;}
      $line_txt = $header_txt[$i];
      if(preg_match("/^時間/", $line_txt)) {
        //parse date
        $header_array['date'] = findDate($line_txt);
        //parse start time
        preg_match('/(上|下)午(.*)$/', $line_txt, $s_time);
        $s_time = findTime($s_time[0]);
        $header_array['start_time'] = $s_time;
      } else if(preg_match("/$record_end/", $line_txt)) {
        //parse end time
        $header_array['end_time'] = findTime($line_txt);
      } else if(preg_match("/地點/", $line_txt)) {
        //parse location
        preg_match('/：(.*)$/', $line_txt, $location_txt);
        $header_array['location'] = $location_txt[1];
      } else if(preg_match('/^主席|彙整/', $line_txt)) {
        //parse chair and minute taker (note taker)
        $chair_parsed = chair_note_parse($line_txt);
        if(!$chair_parsed) {
          //if line not complete, sent next line to the function
          $chair_parsed = chair_note_parse($line_txt.$header_txt[$i+1]);
          $flag = 1;
        }
        $header_array['chairman'] = $chair_parsed[0];
        $header_array['note_taker'] = $chair_parsed[1];
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
        if($i == count($header_txt)-1) $header_array['end_time'] = findTime($line_txt);
      }
    }
    foreach($header_array as $k => &$value) {
      if(!is_array($value)) $value = trim($value);
    }
    return($header_array);
  }

  function chair_note_parse($line_txt) {
    //parse chairman
    preg_match('/主席：(.*)(彙整|(紀|記)錄)/', $line_txt, $chairman_txt);

    // if this line is not complete, return 0
    if(count($chairman_txt) == 0) return(0);

    $chairman_txt = preg_replace("/(兼)?(副)?主任委員/", '', $chairman_txt);
    $chairman_txt = preg_replace("/彙整|(紀|記)錄/", '', $chairman_txt);
    $chair = $chairman_txt[1];
    //parse note_taker
    preg_match('/(彙整|(紀|記)錄)(：|:)(.*)$/', $line_txt, $note_taker_txt);
    $note_taker_txt = preg_replace("/技士|組長/", '', $note_taker_txt);
    $note_taker = $note_taker_txt[4];

    return(array($chair,$note_taker));
  }

  function item_parse(&$item_txt) {
    global $zh_number_cap;
    global $zh_number_low;
    global $section_title;
    //處理 宣讀|確認 上次會議記錄部分
    if(preg_match("/(宣讀|確認)上.*次/", $item_txt[0])) {
      $item_txt = array(array('case_title' => $item_txt[0], 'description' => section_parse($item_txt)));
      return;
    }
    //if the first line of item_txt if the title of sections, unset it
    if(preg_match("/$section_title/", $item_txt[0])) {
      unset($item_txt[0]);
      $item_txt = array_values($item_txt);
    }
    //partition cases by "案(名|由)" or section titles (e.g. 審議事項二)
    $case_index = find_index($item_txt, '^案(名|由)：[\s\S]|'.$section_title);
    $case_array = slice_my_array($item_txt, $case_index);
    //parse case contents
    $case_parsed = array();
    for($i = 0; $i < count($case_array); $i++) {
      if(count($case_array[$i]) > 0) array_push($case_parsed, case_parse($case_array[$i]));
    }
    $item_txt = clean_empty($case_parsed);
  }

  function case_parse($case_txt) {
    global $zh_number_low;
    global $section_title;
    global $casePack;
    global $petitionTableTitle;
    //partition $case_txt into two - content and petition parts
    $part_index = find_index($case_txt, "$petitionTableTitle");
    array_push($part_index, count($case_txt));
    $case_part = clean_empty(slice_my_array($case_txt, $part_index));
    //parse of content part ($case_part[0])
    $session_title = $casePack->getTitleString();
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
        $tag = $casePack->pregTag($session_parsed[0]);
        if($tag != 'not found') $case_output[$tag] = $session_parsed;
      }
    }
    //parse petition contents
    if(isset($case_part[1])) $case_output['petition'] = petition_parse($case_part[1]);
    return($case_output);
  }

  function petition_parse($petition_array) {
    global $petitionPack;
    global $petitionTableTitle;
    $petition_title = $petitionPack->getTitleString();
    $petition_tag = array('reason', 'suggest', 'response', 'adhoc', 'resolution');

    if(preg_match("/$petitionTableTitle/", $petition_array[0])) {
      unset($petition_array[0]);
      $petition_array = array_values($petition_array);
    }
    //依'編號'切割petition array
    $petition_cnt = find_index($petition_array, $petitionPack->petition_num);
    $petition_case = clean_empty(slice_my_array($petition_array, $petition_cnt));
    //如果petition_case[0]是案名，存入petition_header
    if(preg_match("/案名/", $petition_case[0][0])) {
      $petition_header = $petition_case[0];
      unset($petition_case[0]);
    }
    //移除陳情文中的案名line
    if(isset($petition_header)) {
      foreach($petition_case as &$petition_active) {
        foreach($petition_active as $active_k => $active_line) {
          foreach($petition_header as $header_k => $header_line) {
            if($active_line === $header_line) unset($petition_active[$active_k]);
          }
        }
        $petition_active = array_values($petition_active);
      }
      $petition_case_name = combine_array_sentence(array($petition_header))[0];
    }

    $petition_output = array();
    foreach($petition_case as $k => $peition) {
      if(isset($petition_case_name)) $petition_this['petition_case'] = $petition_case_name;
      $petition_index = find_index($peition, $petition_title);
      $petition_section = clean_empty(slice_my_array($peition, $petition_index));

      foreach($petition_section as $single_petition) {
        if (preg_match("/$petitionPack->petition_num/", $single_petition[0])) {
          $first_line = $single_petition[0];
          $name_pos = strpos($first_line, "陳情人");
          $petition_num = substr($first_line, 0, $name_pos);
          $petition_num = trim(preg_replace("/$petitionPack->petition_num/", "", $petition_num));
          $petition_this['petition_num'] = $petition_num;
          $petition_name = substr($first_line, $name_pos);
          $petition_name = trim(preg_replace("/陳情人/", "", $petition_name));
          $petition_this['name'] = $petition_name;
        } else {
          $tag = $petitionPack->pregTag($single_petition[0]);
          if ($tag === "petition_num|name") continue;
          if ($tag != 'not found') $petition_this[$tag] = section_parse($single_petition);
        }
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

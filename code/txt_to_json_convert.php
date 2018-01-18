<?php
include_once('functions.php');
require_once("../commission_db_connect.php");

// $sql = "SELECT * FROM file WHERE transform = 1 AND totxt = 0 ORDER BY id DESC LIMIT 1";
$sql = "SELECT * FROM file WHERE transform = 1 AND totxt = 0 AND (filename LIKE '%TPE%' OR filename LIKE '%MOI%') ORDER BY id DESC LIMIT 1";
$result = $db2->query($sql, PDO::FETCH_ASSOC);
unset($sql);
while ($row = $result->fetch()) {
  $filename = $row['filename'];
}

$place = substr($filename,0,3);

// $place = '';
// include_once("variables/MOI_variables.php");
// include_once("petitionParser/MOI_petitionParser.php");
// $section_title = $sectionPack->getTitleString();
// record_parse('./txt/MOI_O_892_1.txt');

if(preg_match("/TPE|MOI/", $place)) {
    include_once("variables/".$place."_variables.php");
    include_once("petitionParser/".$place."_petitionParser.php");
    $section_title = $sectionPack->getTitleString();
    if(!($place = 'TPE' && preg_match("/TPE_O_(657|632)/", $filename))) {
        //TPE_O_657 and TPE_O_632 contains major issues
        $file = './txt/'.$filename.'.txt';
        try {
            record_parse($file);
            $result = 1;
        } catch (Exception $e) {
            $result = 2;
        } finally {
            $sql = "UPDATE file SET totxt = $result WHERE filename = :filename";
            $stmt = $db2->prepare($sql);
            $stmt->bindParam(":filename", $filename);
            $stmt->execute();
            unset($sql);
        }
    }
}

function record_parse($target_file) {
  global $zh_number_low;
  global $noteTitle;

  preg_match('~([O|N]_)(.*?)(_\d+\.txt)~',$target_file, $target);
  $target = $target[2];

  $txtfile = fopen($target_file, 'r');
  $fulltxt = array();

  //setup for page number checker
  $current_page = 0;
  $zh_page = 0;

  //read in txt lines
  while(!feof($txtfile)) {
      $page_num = array();
      $page_line = 0;

      $txtline = trim(fgets($txtfile));
      $txtline = mb_convert_encoding($txtline, "UTF-8");
      $txtline = preg_replace("/ +/", "", $txtline);
      //置換中文異體字
      $txtline = fixLetter($txtline);

      //check whether certain line is page number
      if(strlen($txtline) != 0) {
          if((preg_match("/-([0-9]+)-/", $txtline, $page_num))) {
          } else if((preg_match("/^第-?([0-9]+)-?頁(\/)?(，)?(第|共)[0-9]+頁$/", $txtline, $page_num))) {
          } else if((preg_match("/^([0-9]+)$/", $txtline, $page_num))) {
          } else if((preg_match("/^(($zh_number_low)+)$/", $txtline, $page_num))) {
              $zh_page = 1;
          }
      }
      //drop page number lines
      if(!empty($page_num) && !$zh_page) {
          $this_page = $page_num[0];
          if($this_page == $current_page+1) {
              $current_page += 1;
              $page_line = 1;
          }
      } else if(!empty($page_num) && $zh_page) {
          $page_line = 1;
      }
      if($page_line) {
      } else {
          array_push($fulltxt, $txtline);
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
  $section_index = find_index($fulltxt, "(($zh_number_low).*.($section_title))|$record_end");
  //if cannot find item index with lower case number, try cap case number
  if(sizeof($section_index) == 1){
        $section_index = find_index($fulltxt, "(($zh_number_cap).*.($section_title))|$record_end");
  }
  foreach($fulltxt as $k => $v) {
    if(preg_match("/$record_end/", $v)) $end_index = $k;
  }
  if(!isset($end_index)) $end_index = count($fulltxt)-1;
  //slice txt into parts by index position
  $parse_txt = slice_my_array($fulltxt, $section_index);
  //parse commission note header (if exist)
  $item_start = 0; //save start position of items in $parse_txt
  if(preg_match("/$noteTitle/", $parse_txt[0][0])) {
    array_push($parse_txt[0], $fulltxt[$end_index]);
    $header_array = new headerPack($parse_txt[0], $target);
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

  $json_obj = new jsonArray;
  if(isset($header_array)) $json_obj->loadInto($header_array);
  if(isset($item_array)) $json_obj->loadInto($item_array);

  $output_file = preg_replace("/txt/", "json", $target_file);
  echo $output_file."<br>";

  $json_txt = json_encode($json_obj, JSON_UNESCAPED_UNICODE);
  $output_json = fopen($output_file, "w");
  fwrite($output_json, $json_txt);
}

function item_parse(&$item_txt) {
  global $zh_number_cap;
  global $zh_number_low;
  global $section_title;
  global $casePack;

  //處理 宣讀|確認 上次會議記錄部分
  if(preg_match("/(宣讀|確認).*次/", $item_txt[0])) {
    $item_txt = array(array('case_title' => $item_txt[0], 'description' => section_parse($item_txt)));
    return;
  }
  //if the first line of item_txt if the title of sections, unset it
  if(preg_match("/$section_title/", $item_txt[0])) {
    unset($item_txt[0]);
    $item_txt = array_values($item_txt);
  }
  //partition cases by "案(名|由)" or section titles (e.g. 審議事項二)
  $case_index = find_index($item_txt, "$casePack->case_title[\s\S]|".$section_title);
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

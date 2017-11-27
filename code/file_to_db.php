<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once("object_definition.php");
require_once("commission_db_connect.php");

$sql = "SELECT filename FROM file WHERE transform = 1 AND todb = 0 ORDER BY id LIMIT 1";
$result = $db2->query($sql, PDO::FETCH_ASSOC);
unset($sql);
while ($row = $result->fetch()) {
  $filename = $row['filename'];
}

//development example setup
// $filename = "TPE_O_702_1";
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
$note = new note_meta();
$note->admin = $admin;
$note->round = $round;

loadJson($note, $json_data);
$note->setNoteCode();
$note->setJson(array('attend_committee', 'attend_unit'));

insertNote('note_table', $note);

foreach($json_data as $key => $value) {
  if(preg_match("/item/", $key)) {
    foreach($value as $item_array) {
      $case_item = createItem($item_array, $key, $note->note_code);
      insertNote('case_table', $case_item);
      if(isset($item_array['petition'])) {
        foreach($item_array['petition'] as $petition_array) {
          $petition = createPetition($petition_array, $note->note_code, $case_item->case_code);
          insertNote('petition_table', $petition);
        }
      }
    }
  }
}
$dbh = null;

$sql = "UPDATE file SET todb = 1 WHERE filename = :filename";
$stmt = $db2->prepare($sql);
$stmt->bindParam(":filename", $filename);
$stmt->execute();
$db2 = null;

function createItem($item_array, $key, $note_code) {
  $item_tag = array('',
                    'report_item',
                    'confirm_item',
                    'deliberate_item',
                    'discuss_item',
                    'extempore_item');

  $case_item = new case_item;
  loadJson($case_item, $item_array);
  $case_item->type = array_search($key, $item_tag);
  $case_item->case_code = getRanStr(6);
  $case_item->note_code = $note_code;
  return($case_item);
}

function createPetition($petition_array, $note_code, $case_code) {
  $petition = new petition;
  loadJson($petition, $petition_array);
  $petition->note_code = $note_code;
  $petition->case_code = $case_code;
  return($petition);
}

function loadJson($class, $json) {
  $tag = $class->getKeys();
  foreach($tag as $value) {
    if(isset($json[$value])) {
      $class->$value = $json[$value];
    }
  }
}

function getRanStr($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

function insertNote($table, $note_object) {
  global $dbh;

  $fields = array();
  $values = array();
  $cnt = 0;
  $question_mark = array();
  foreach($note_object as $k => $v) {
    array_push($fields, $k);
    if(gettype($v)=='array') {
      $v = json_encode($v, JSON_UNESCAPED_UNICODE);
    }
    array_push($values, $v);
    array_push($question_mark, '?');
    $cnt++;
  }
  $fields = implode(",", $fields);
  $question_mark = implode(",", $question_mark);

  $sql = "INSERT IGNORE INTO $table ($fields) VALUES ($question_mark)";
  $stmt = $dbh->prepare($sql);
  for($i = 1; $i <= count($values); $i++) {
    $stmt->bindParam($i, $values[$i-1]);
  }
  $stmt->execute();
}

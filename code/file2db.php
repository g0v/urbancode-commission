<?php
if(file_exists('php_ini_setup.php')) include_once('php_ini_setup.php');
include_once("file2db_object_definition.php");
require_once("../commission_db_connect.php");

$sql = "SELECT filename FROM file WHERE transform = 1 AND totxt = 1 AND todb = 0 ORDER BY id DESC LIMIT 1";
$result = $db2->query($sql, PDO::FETCH_ASSOC);
unset($sql);

while ($row = $result->fetch()) {
  $filename = $row['filename'];
}

// development example setup
// $filename = "MOI_O_864_1";

if(isset($filename)) {
    //extract admin and round variable from file name
    preg_match("/^(.*?)_([O|N])_([0-9]+)_(.*?)$/", $filename, $fmatch);
    $admin = $fmatch[1].$fmatch[2];
    $round = $fmatch[4];

    $filepath = "./json/$filename.json";

    if(file_exists($filepath)) {
        file2db($filepath, $admin, $round, $db2);
        $db_end_update = $db2->prepare('UPDATE file SET todb = 1 WHERE filename = :filename');
    } else {
        $db_end_update = $db2->prepare('UPDATE file SET totxt = 0 WHERE filename = :filename');
    }
    try {
        $db_end_update->bindParam(':filename', $filename);
        $db_end_update->execute();
    } catch (PDOException $e) {
        echo 'error: ' . $e->getMessage();
    }
} else {
    echo 'all json files were parsed';
}

function file2db($filepath, $admin, $round, $db2)
{
    //read in json content and decode it
    $jsonfile = fopen($filepath, 'r');
    $json_data = fgets($jsonfile);

    if($json_data == '{}') echo 'file is empty';

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
                $case_item = createItem($item_array, $key, $note->note_code, $note->date);
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

    print_r($filepath);

    try {
        $sql = "UPDATE file SET todb = 1 WHERE filename = :filename";
        $stmt = $db2->prepare($sql);
        $stmt->bindParam(":filename", $filename);
        $stmt->execute();
    } catch (PDOException $e) {
        echo 'error: ' . $e->getMessage();
    }

    $db2 = null;
}

function createItem($item_array, $key, $note_code, $note_date) {
    $item_tag = array('',
                    'read_item',
                    'report_item',
                    'confirm_item',
                    'deliberate_item',
                    'discuss_item',
                    'extempore_item');

    $case_item = new case_item;
    loadJson($case_item, $item_array);
    $case_item->type = array_search($key, $item_tag);
    $case_item->note_code = $note_code;
    $case_item->note_date = $note_date;
    $case_item->case_code = buildCaseCode($case_item, $note_date);
    return($case_item);
}

function createPetition($petition_array, $note_code, $case_code) {
    $petition = new petition;
    loadJson($petition, $petition_array);
    $petition->note_code = $note_code;
    $petition->case_code = $case_code;
    $petition->petition_code = buildPetitionCode($petition, $case_code);
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

function buildCaseCode($case_item, $note_date) {
    $admin = substr($case_item->note_code, 0, 3);

    $case_title = $case_item->case_title;
    if(gettype($case_title) == 'string') {
        $title = $case_title;
    } else {
        $title = implode('', $case_title);
    }

    $title = preg_replace('/「|」/', '', $title);
    if(preg_match('/：/', $title)) $title = mb_substr($title, mb_strpos($title, '：')+1);

    if(preg_match('/通盤檢討/', $title)) {
        $code_head = '通檢';
    } else if(preg_match('/確認.*紀錄/', $title)) {
        $code_head = '確認';
    } else {
        $code_head = mb_substr($title, 0, 2);
    }

    switch($code_head) {
        case '確認':
            $dist = '次';
            preg_match('/第(.*?)(及?)?(.*?)次/', $title, $match);
            preg_match('/\d+/', $match[sizeof($match)-1], $match);
            $code_sec1 = $match[0].'次';
            break;
        default:
            $dist = '鄉|鎮|區|專案';
            preg_match("/$dist/", $title, $match, PREG_OFFSET_CAPTURE);
            if(!empty($match)) {
                $code_sec1 = mb_substr($title, $match[0][1]/3-2, 3);
            } else if (preg_match("/配合/", $title)) {
                preg_match("/配合/", $title, $match, PREG_OFFSET_CAPTURE);
                $code_sec1 = mb_substr($title, $match[0][1]/3, 3);
            } else {
                $code_sec1 = '變都計';
            }
    }

    $code_sec2 = preg_replace('/\//', '', $note_date);
    $case_code = $admin . $code_head . $code_sec1. $code_sec2;

    return $case_code;
}

function buildPetitionCode($petition) {
    $case = $petition->case_code;
    $name = mb_substr($petition->name, 0, 1);
    $petition_code = $case . $petition->petition_num . $name;
    return $petition_code;
}

function insertNote($table, $note_object) {
    global $dbh;

    $key = preg_replace('/_table/', '_code', $table);

    $input_code = "'". $note_object->$key . "'";

    //check if object to be insert already in the database
    $sql = "SELECT * from $table WHERE $key = $input_code";
    $query = $dbh->prepare($sql);
    $query->execute();
    $exist_result = $query->fetchAll();

    $fields = array();
    $values = array();
    $question_mark = array();
    $updates = array();

    foreach($note_object as $k => $v) {
        array_push($fields, $k);

        if(gettype($v) == 'array') $v = json_encode($v, JSON_UNESCAPED_UNICODE);

        //unescaped ' would cause PDO error
        $v = preg_replace('/\'/', '\\\'', $v);

        array_push($values, $v);
        array_push($question_mark, '?');
        if($k != $key) array_push($updates, "$k='$v'");
    }
    $fields = implode(",", $fields);
    $question_mark = implode(",", $question_mark);
    $updates = implode(",", $updates);

    if(sizeof($exist_result) > 0) {
        echo "item $input_code exist".PHP_EOL;

        $sql = "UPDATE $table SET $updates WHERE $key = $input_code";
        $stmt = $dbh->prepare($sql);
    } else {
        $sql = "INSERT INTO $table ($fields) VALUES ($question_mark)";
        $stmt = $dbh->prepare($sql);
        for($i = 1; $i <= count($values); $i++) {
            $stmt->bindParam($i, $values[$i-1]);
        }
    }

    try {
        $result = $stmt->execute();
        if($stmt->errorInfo()[0] != '00000') {
            var_dump($stmt->errorInfo());
            var_dump($values);
        }
        unset($stmt);
    } catch (PDOException $e) {
        echo 'error: ' . $e->getMessage();
    }
}

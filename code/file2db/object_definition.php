<?php
abstract class note_part {
  public $note_code;

  function getKeys() {
    $keyArray = array();
    foreach ($this as $key => $value) {
      array_push($keyArray, $key);
    }
    return($keyArray);
  }

  function setJson($input) {
    if(gettype($input) == 'string') $input = array($input);
    foreach($input as $txt) {
      $this->$txt = json_encode($this->$txt);
    }
  }
}

class note_meta extends note_part {
  public $admin;
  public $session;
  public $round;
  public $title;
  public $date;
  public $start_time;
  public $end_time;
  public $location;
  public $chairman;
  public $note_taker;
  public $attend_committee;
  public $attend_unit;

  function setNoteCode() {
    $this->note_code = $this->admin.$this->session.$this->round;
  }
}

class case_item extends note_part {
  public $type;
  public $case_title;
  public $case_code;
  public $description;
  public $committee_speak;
  public $response;
  public $resolution;
  public $add_resolution;
  public $attached;
}

class petition extends note_part {
  public $case_code;
  public $petition_num;
  public $name;
  public $location;
  public $reason;
  public $suggest;
  public $adhoc;
  public $resolution;
}

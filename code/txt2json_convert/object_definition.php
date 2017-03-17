<?php
class note {
  public $admin;
  public $session;
  public $round;
  public $note_code;
  public $title;
  public $date;
  public $start_time;
  public $end_time;
  public $location;
  public $chairman;
  public $note_taker;
  public $attend_committee;
  public $attend_unit;

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

  function setNoteCode() {
    $this->note_code = $this->admin.$this->session.$this->round;
  }
}

class case_item {
  public $note_code;
  public $type;
  public $title;
  public $case_code;
  public $description;
  public $committee_speak;
  public $response;
  public $resolution;
  public $add_resolution;
  public $attached;
}

class petition {

}

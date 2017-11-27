<?php
include_once('variables_head.php');

$noteTitle = '都市計畫委員會|(紀|記)錄';
$petitionTableTitle = '臺北市都市計畫委員會公民或團體(所提|陳情)意見綜理表';
$record_end = '散會';

//定義會議記錄各段落大標
$sectionPack = new titlePack;
$sectionPack->read_item = '(宣讀|確認)上.*次';
$sectionPack->report_item = '報告事項$';
$sectionPack->confirm_item = '確認事項$';
$sectionPack->deliberate_item = '審議事項$';
$sectionPack->dicuss_item = '(研議|討論)事項$';
$sectionPack->extempore_item = '臨時動議$';

//定義各案件小段落標題
$casePack = new titlePack;
$casePack->case_title = '^案(名|由)：';
$casePack->description = '^(案情概要)?(說|說)明：';
$casePack->committee_speak = '^(民意代表)?(及)?(出席)?委員(及|、)?(民意代表)?(、)?(里長)?(與會單位)?發言摘要(與回應)?(：)?';
$casePack->response = '^(市府|發展局)回應：';
$casePack->resolution = '^決議(：)?';
$casePack->add_resolution = '^附帶決議(：)?';
$casePack->attached ='^附件：';

//定義陳情案件段落標題
$petitionPack = new titlePack;
$petitionPack->petition_num = '^編號.*陳情人';
$petitionPack->reason = '^陳情理由';
$petitionPack->suggest = '^建議辦法';
$petitionPack->response = '^(申請單位|市府)回(覆|應)(意見)?';
$petitionPack->adhoc = '^專案小組(審查意見)?';
$petitionPack->resolution = '^(委員會)?決議';

//定義 header 類別與處理方法
class headerPack {
  //定義會議meta||header標題
  private $timeTag = '^時間';
  private $locationTag = '^地點';
  private $chairTag = '^主席|彙整';
  private $attendCommitteeTag = '出席委員';
  private $attendUnitTag = '列席單位';

  public function __construct($header_txt, $target) {
    $record_end = "散會";

    $this->title = $header_txt[0];
    $this->session = $target;

    $flag = 0;
    foreach($header_txt as $k => $line_txt) {
      if($flag == 1) {$flag =0; continue;}
      if(preg_match("/$this->timeTag/", $line_txt)) {
        $this->date = findDate($line_txt); //parse date
        //parse start time
        preg_match('/(上|下)午(.*)$/', $line_txt, $s_time);
        $s_time = findTime($s_time[0]);
        $this->start_time = $s_time;
      } else if(preg_match("/$record_end/", $line_txt)) {
        $this->end_time = findTime($line_txt); //parse end time
      } else if(preg_match("/$this->locationTag/", $line_txt)) {
        //parse location
        preg_match('/：(.*)$/', $line_txt, $location_txt);
        $this->location = $location_txt[1];
      } else if(preg_match("/$this->chairTag/", $line_txt)) {
        //parse chair and minute taker (note taker)
        $chair_parsed = $this->chair_note_parse($line_txt);
        if(!$chair_parsed) {
          //if line not complete, sent next line to the function
          $chair_parsed = $this->chair_note_parse($line_txt.$header_txt[$k+1]);
          $flag = 1;
        }
        $this->chairman = $chair_parsed[0];
        $this->note_taker = $chair_parsed[1];
      } else if(preg_match("/$this->attendCommitteeTag/", $line_txt)) {
        //parse attend committe
        if(preg_match('/詳簽到表/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_committe_txt);
          $attend_committe_txt = array($attend_committe_txt[1]);
          $this->attend_committee = $attend_committe_txt;
        }
      } else if(preg_match("/$this->attendUnitTag/", $line_txt)) {
        //parse attend committe
        if(preg_match('/詳簽到表/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_unit_txt);
          $attend_unit_txt = array($attend_unit_txt[1]);
          $this->attend_unit = $attend_unit_txt;
        }
      } else {
        if($k == count($header_txt)-1) $this->end_time = findTime($line_txt);
      }
    }
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
}

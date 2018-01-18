<?php
include_once('variables_head.php');
// include_once(dirname(__FILE__)."/../functions.php");

$noteTitle = '都市計畫委員會|(紀|記)錄';
<<<<<<< HEAD:code/variables/MOI_variables.php
$petitionTableTitleArray = array('(公民或(機關)?團體(逕向內政部)?陳情意見(部分|綜理表)(：)$)',
                                '(附表：本會專案小組會議後逕向本部陳情意見)');
$petitionTableTitle = implode('|', $petitionTableTitleArray);
// $petitionTableTitle = '公民或(機關)?團體(逕向內政部)?陳情意見(部分|綜理表)(：)$';
=======
//***內政部會議記錄的陳情部分要另外處理
$petitionTableTitle = '000公民或(機關)?團體(逕向內政部)?陳情意見(部分|綜理表)(：)$';
>>>>>>> b85f1a4c573074ceb247cc99fbf905a9deaebf7e:code/txt2json_convert/MOI_variables.php
$record_end = '散會';

//定義會議記錄各段落大標
$sectionPack = new titlePack;
$sectionPack->read_item = '確認本會.*會議(記|紀)錄';
// $sectionPack->report_item = '報告事項$';
// $sectionPack->confirm_item = '';
$sectionPack->deliberate_item = '核定案件';
// $sectionPack->dicuss_item = '(研議|討論)事項$';
// $sectionPack->extempore_item = '臨時動議$';

//定義各案件小段落標題
$casePack = new titlePack;
$casePack->case_title = '^第.*案：';
$casePack->description = '^(案情概要)?說明：';
$casePack->committee_speak = '^(民意代表)?(及)?(出席)?委員(及|、)?(民意代表)?(、)?(里長)?(與會單位)?發言摘要(與回應)?(：)?';
$casePack->response = '^(市府|發展局)回應：';
// $casePack->adhoc = '本會專案小組(出席委員)?初步建議意見';
$casePack->resolution = '^決議(：)?';
$casePack->add_resolution = '^附帶決議(：)?';
//***多個附件的處理方式要再研究
$casePack->attached ='^(【)?附(件|錄|表)(】)?.*：';

//定義陳情案件段落標題
$petitionPack = new titlePack;
$petitionPack->petition_num = '^[0-9]+$';
// $petitionPack->reason = '^陳情理由';
// $petitionPack->suggest = '^建議辦法';
$petitionPack->response = '^建議(未便|同意)?採納;';
// $petitionPack->adhoc = '^專案小組(審查意見)?';
// $petitionPack->resolution = '^(委員會)?決議';

//定義 header 類別與處理方法
class headerPack {
  //定義會議meta||header標題
  private $timeTag = '時間：';
  private $locationTag = '地點：';
  private $chairTag = '主席：';
  private $noteTakerTag = '紀錄彙整';
  private $attendCommitteeTag = '出席委員';
  private $attendUnitTag = '列席單位';

  public function __construct($header_txt, $target) {
    $record_end = "散會";

    $this->title = $header_txt[0];
    $this->session = $target;

    $flag = 0;
    foreach($header_txt as $k => $line_txt) {
      if($flag == 1) {$flag =0; continue;}
    if(preg_match("/$record_end/", $line_txt)) {
      $this->end_time = findTime($line_txt); //parse end time
     } else if(preg_match("/$this->timeTag/", $line_txt)) {
        $this->date = findDate($line_txt); //parse date
        //parse start time
        preg_match('/(上|下)午(.*)$/', $line_txt, $s_time);
        if(count($s_time) == 0) preg_match('/(上|下)午(.*)$/', $line_txt.$header_txt[$k+1], $s_time);
        $s_time = findTime($s_time[0]);
        $this->start_time = $s_time;
      } else if(preg_match("/$this->locationTag/", $line_txt)) {
        //parse location
        preg_match('/：(.*)$/', $line_txt, $location_txt);
        $this->location = $location_txt[1];
      } else if(preg_match("/$this->chairTag/", $line_txt)) {
        //parse chair
        $this->chairman = $this->chair_parse($line_txt);
      } else if(preg_match("/$this->noteTakerTag/", $line_txt)) {
        //parse minute taker (note taker)
        $this->note_taker = $this->note_taker_parse($line_txt);
      } else if(preg_match("/$this->attendCommitteeTag/", $line_txt)) {
        //parse attend committe
        if(preg_match('/詳會議簽到簿/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_committe_txt);
          $attend_committe_txt = array($attend_committe_txt[1]);
          $this->attend_committee = $attend_committe_txt;
        }
      } else if(preg_match("/$this->attendUnitTag/", $line_txt)) {
        //parse attend committe
        if(preg_match('/詳會議簽到簿/', $line_txt)) {
          preg_match('/：(.*)$/', $line_txt, $attend_unit_txt);
          $attend_unit_txt = array($attend_unit_txt[1]);
          $this->attend_unit = $attend_unit_txt;
        }
      }
    }
  }

  function chair_parse($line_txt) {
    preg_match('/主席：(.*)/', $line_txt, $chairman_txt);
    $chairman_txt = preg_replace("/(兼)?(副)?主任委員/", '', $chairman_txt);
    $chairman_txt = preg_replace("/委員兼執行秘書/", '', $chairman_txt);
    $chair = $chairman_txt[1];
    return($chair);
  }
  function note_taker_parse($line_txt) {
    preg_match('/紀錄彙整：(.*)/', $line_txt, $taker_txt);
    $taker = $taker_txt[1];
    return($taker);
  }
}

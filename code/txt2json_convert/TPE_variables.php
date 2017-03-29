<?php
include_once("class_definition.php");

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
$casePack->committee_speak = '^委員發言摘要：';
$casePack->response = '^(市府|發展局)回應：';
$casePack->resolution = '^決議(：)?';
$casePack->add_resolution = '^附帶決議(：)?';
$casePack->attached ='^附件：';

//定義陳情案件段落標題
$petitionPack = new titlePack;
$petitionPack->petition_num = '^編號.*陳情人';//'^案(名|由)|^編號';
$petitionPack->reason = '^陳情理由';
$petitionPack->suggest = '^建議辦法';
$petitionPack->response = '^(申請單位|市府)回(覆|應)(意見)?';
$petitionPack->adhoc = '^專案小組(審查意見)?';
$petitionPack->resolution = '^(委員會)?決議';

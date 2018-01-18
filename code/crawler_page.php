<?php
error_reporting(E_ALL);
set_time_limit(60);
ini_set('memory_limit', '1024M');
include_once 'crawler_toolbox.php';

function TXG()
{
    global $page_upload, $gov, $url, $text;
    $content = curl_simple('http://www.ud.taichung.gov.tw/lp.asp?CtNode=22422&CtUnit=6506&BaseDSD=7&mp=127010&nowPage=1');//&pagesize=3000');
    preg_match('/class="list"[\s\S]*?<\/ul>[\s\S]*?<\/div>/', $content, $match);
    $html = str_get_html($match[0]);
    $list = $html->find('ul', 0)->find('li');
    foreach ($list as $value) {
        $link = $value->find('a', 0);

        $title = $link->plaintext;
        if (preg_match("/年會議紀錄/", $title)) {
            $gov = 'TXG_O';
        } else {
            $gov = 'TXG_N';
        }
        $url  = 'http://www.ud.taichung.gov.tw/' . $link->href;
        $text = '';

        $page_upload->execute();
    }
}
function TPE()
{
    global $page_upload, $gov, $url, $text;
    $content = curl_simple('http://www.tupc.gov.taipei/lp.asp?CtNode=6308&CtUnit=4388&BaseDSD=7&mp=120021&nowPage=1&pagesize=1');//5000');
    $html    = str_get_html($content);
    $list    = $html->find('.list', 0)->find('ul', 0)->find('li');
    foreach ($list as $value) {
        $link = $value->find('a', 0);

        $gov  = 'TPE_O';
        $url  = 'http://www.tupc.gov.taipei/' . $link->href;
        if(preg_match('/doc|docx|pdf/',$url)){
            $text = $link->plaintext;
        }else{
            $text = '';
        }

        $page_upload->execute();
    }

}

function TAO()
{
  global $page_upload, $gov, $url, $text;

  $content = curl_simple('http://urdb.tycg.gov.tw/home.jsp?id=116&parentpath=0%2C2%2C7%2C87', ['page' => 1, 'pagesize' => 1]);//5000]);
  $html = str_get_html($content);
  $list = $html->find('#messageform', 0)->find('div[id=home_content]', 0)->find('div[id=home_content00]', 0)->find('div#content_list', 0)->find('.list_list');
  // print_r(count($list));
  $i    = 0;
  foreach ($list as $tr) {
    if ($i == 1) {
      $i = 0;
      continue;
    }
    $i = 1;

    $link  = $tr->find('.list_title', 0)->find('a', 0);
    $title = $link->plaintext;
    if (preg_match('/桃園縣/', $title)) {
      $gov = 'TAO_O';
    } else {
      $gov = 'TAO_N';
    }
    $url  = $link->href;
    $text = $link->plaintext;
    $page_upload->execute();
  }
}
function KHH($type = 'new')
{
    global $page_upload, $gov, $url, $text;

    if ($type = 'all') {
        $gov_list = [['KHH_N', 'A3'], ['KHH_O', 'A1'], ['KHQ', 'A2']];
    } else {
        $gov_list = [['KHH_N', 'A3']];
    }

    foreach ($gov_list as $gov_attr) {
        $gov      = $gov_attr[0];
        $page_max = 1;

        for ($page_num = 1; $page_num <= $page_max; $page_num++) {
            $content = curl_simple('http://kupc.kcg.gov.tw/KUPC/web_page/KPP0013.jsp', ['KP005008' => $gov_attr[1],
                'PNO'                                                                                  => $page_num]);
            $html = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

            $list = $html->find('a[title] font');

            foreach ($list as $font) {
                $link = $font->parent();
                $url  = 'http://kupc.kcg.gov.tw/KUPC' . str_replace('../', '/', $link->href);
                $text = $link->plaintext;
                $page_upload->execute();
            }

            if ($type == 'all') {
                preg_match('/\D*(\d*)/', $html->find('.text13 div[align=center]', 0), $match);
                $page_max = ceil(intval($match[1]) / 10);
            }
        }
    }
}

function KEE($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'KEE_O';
    $page_max = 1;

    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $url = 'https://upgis.klcg.gov.tw/KL_LAND/meeting/reclist.asp?ToPage=' . $page_num;
        $content = curl_simple($url);
        $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

        $list = $html->find('input[name=image]');

        foreach ($list as $input) {
            preg_match("/openpdf\('(.*)','(.*)','(.*)'\);/", $input->attr['onclick'], $match);

            $url  = 'http://upgis.klcg.gov.tw/kl_land/Data/' . $match[1] . '/' . $match[2] . '/' . $match[3] . '.pdf';
            $text = $match[2];
            $page_upload->execute();
        }

        if ($type == 'all') {
            preg_match('/共.*?(\d*)/', $html->find('tr[valign=baseline]', 0)->find('td', 0)->plaintext, $match);
            $page_max = intval($match[1]);
        }
    }
}

function HSZ($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'HSZ_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://landuse.hccg.gov.tw/updoc/05plan/plan_d3_list.asp?offset=' . ($page_num - 1) * 10);
        $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

        $list = $html->find('a[target=_blank]');

        foreach ($list as $link) {
            $url  = 'http://landuse.hccg.gov.tw/updoc' . str_replace('../', '/', $link->href);
            $text = $link->plaintext;
            $page_upload->execute();
        }

        if ($type == 'all') {
            preg_match('/(\d*)\s*最後一頁/', $html->find('.num', 0)->parent()->plaintext, $match);
            $page_max = intval($match[1]);
        }
    }

}

//暫緩，:8080問題
function CYI($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'CYI_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://urban.cyhg.gov.tw:8080/CHIAYI/web_page/CYP000702.jsp?SK=4&S01=1&offset=0' . ($page_num - 1) * 20);
        $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

        $list = $html->find('a font[color="#0066FF"]');

        foreach ($list as $font) {
            $link = $font->parent();
            $url  = 'http://urban.cyhg.gov.tw:8080/CHIAYI' . str_replace('../', '/', $link->href);
            $text = $link->plaintext;
            $page_upload->execute();
        }

        if ($type == 'all') {
            preg_match('/\D*(\d*)/', $html->find('.text13 div[align=center]', 1), $match);
            $page_max = ceil(intval($match[1]) / 20);
        }
    }

}

function YUN($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'YUN_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://urbangis.yunlin.gov.tw/YLCityGIS/InfoArea/PeopleView/Meeting.aspx?Type=1BB');
        $html    = str_get_html($content);

        $list = $html->find('.gvStyle', 0)->find('a');

        foreach ($list as $link) {
            $url  = 'http://urbangis.yunlin.gov.tw/YLCityGIS/InfoArea/PeopleView/' . $link->href;
            $text = $link->plaintext;
            $page_upload->execute();
        }
    }
}

//南投縣
function NAN($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'NAN_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://up.nantou.gov.tw/committee/list/page/' . $page_num);
        $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

        $list = $html->find('.style1 a');

        foreach ($list as $link) {
            $url  = 'http://up.nantou.gov.tw/' . $link->href;
            $text = '';
            $page_upload->execute();
        }

        if ($type == 'all') {
            $page_max = str_replace('/committee/list/page/', '', $html->find('img[alt=最終頁]', 0)->parent()->href);
            $page_max = intval($page_max);
        }
    }

}

//苗栗縣
function MIA($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov = 'MIA_O';
    if ($type == 'new') {
        $page_max = 1;
    } elseif ($type == 'all') {
        $page_max = 9999;
    }

    for ($page_num = 1; $page_num <= $page_max; $page_num++) {
        if ($page_num == 1) {
            $content = curl_simple('http://urbanplanning.miaoli.gov.tw/upmiaoli/urbanplan11/04.aspx');
        } else {
            if ($page_num % 10 == 1) {
                preg_match("/.*Back\('(.*?)',''\)\">...<\/a><\/td>/", $html->find('.ListTable_pager', 0), $match);
            } else {
                preg_match("/.*Back\('(.*?)',''\)\">" . $page_num . "/", $html->find('.ListTable_pager', 0), $match);
            }
            if (!isset($match[1])) {
                break;
            }
            $__EVENTTARGET        = $match[1];
            $__VIEWSTATE          = $html->find('#__VIEWSTATE', 0)->attr['value'];
            $__VIEWSTATEGENERATOR = $html->find('#__VIEWSTATEGENERATOR', 0)->attr['value'];
            $__EVENTVALIDATION    = $html->find('#__EVENTVALIDATION', 0)->attr['value'];
            $content              = curl_simple('http://urbanplanning.miaoli.gov.tw/upmiaoli/urbanplan11/04.aspx', ["__EVENTTARGET" => $__EVENTTARGET, "__VIEWSTATE" => $__VIEWSTATE, "__VIEWSTATEGENERATOR" => $__VIEWSTATEGENERATOR, "__EVENTVALIDATION" => $__EVENTVALIDATION, "_ctl0:ContentPlaceHolder1:RadioButtonList1" => 0]);
        }

        $html = str_get_html($content);
        $list = $html->find('.ListTable tr[class=ListTable_row] a,.ListTable tr[!class] a');
        foreach ($list as $link) {
            preg_match("/showDoc\('..\/(.*)','(.*)','(.*)'\);/", $link->href, $match);
            $url  = 'http://urbanplanning.miaoli.gov.tw/upmiaoli/' . $match[1] . '?cfg=' . $match[2] . '&file=' . $match[3];
            $text = $link->plaintext;
            $page_upload->execute();
        }
    }

}

//連江縣
function LIE($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'LIE_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://web.matsu.gov.tw/ch/Download_DownloadPage.aspx?ClsID=24&ClsTwoID=0&ClsThreeID=0&Page=' . $page_num);
        $html    = str_get_html($content);
        $list    = $html->find('.list03 .row,.list03 .altrow');

        foreach ($list as $tr) {
            $url  = 'http://web.matsu.gov.tw/' . str_replace('../', '', $tr->find('td', 2)->find('a', 0)->href);
            $text = $tr->find('td', 0)->find('span', 0)->plaintext;
            $page_upload->execute();
        }

        if ($type == 'all') {
            preg_match("/\d\/(\d*)/", $html->find('.tfood', 0)->plaintext, $match);
            $page_max = intval($match[1]);
        }
    }

}

//宜蘭縣
function ILA($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov = 'ILA_O';

    $content = curl_simple('http://up.e-land.gov.tw/c081.aspx');
    $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

    $list = $html->find('#Table1 a[href]');

    foreach ($list as $link) {
        $url  = 'http://up.e-land.gov.tw/' . $link->href;
        $text = $link->plaintext;
        $page_upload->execute();
    }
}

//花蓮縣
function HUA($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov = 'HUA_O';

    $content = curl_simple('http://gis.hl.gov.tw/geoweb/consult.aspx');
    $html    = str_get_html(mb_convert_encoding($content, "UTF-8", "BIG5"));

    $list = $html->find('#ctl00_ContentPlaceHolder1_GridView2 a');

    foreach ($list as $link) {
        $url  = 'http://gis.hl.gov.tw/geoweb/' . $link->href;
        $text = $link->plaintext;
        $page_upload->execute();
    }
}

//新竹縣
function HSQ($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'HSQ_O';
    $page_max = date("Y");
    for ($page_num = 2007; $page_num <= $page_max; $page_num++) {
        if ($type == 'new') {
            $page_num = date("Y");
        }

        $content = curl_simple('http://urbanplan.hsinchu.gov.tw/modules/urbanplan/meeting/default.asp?year=' . $page_num);
        $html    = str_get_html($content);
        $list    = $html->find('.GridTD a');

        foreach ($list as $link) {
            $url  = 'http://urbanplan.hsinchu.gov.tw/modules/urbanplan/meeting/' . $link->href;
            $text = '';
            $page_upload->execute();
        }
    }

}
//內政部
function MOI($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'MOI_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {

        $content = curl_simple('http://www.cpami.gov.tw/chinese/index.php?option=com_filedownload&view=filedownload&Itemid=68&filter_cat=5&filter_gp=5&limitstart=' . ($page_num - 1) * 15);
        $html    = str_get_html($content);
        $list    = $html->find('.datatable a');

        foreach ($list as $link) {
            $url  = 'http://www.cpami.gov.tw/' . $link->href;
            $text = $link->plaintext;
            $page_upload->execute();
        }

        if ($type == 'all') {
            preg_match("/共\s*(\d*)/", $html->find('.pageresult', 0)->plaintext, $match);
            $page_max = ceil(intval($match[1]) / 15);
        }
    }

}

//內政部CRO
function MOICRO($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'MOICRO_O';
    $page_max = 1;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {
        $content = curl_simple('https://www.cpami.gov.tw/%E4%BE%BF%E6%B0%91%E6%9C%8D%E5%8B%99/%E4%B8%8B%E8%BC%89%E5%B0%88%E5%8D%80/%E4%B8%8B%E8%BC%89%E5%B0%88%E5%8D%80%E6%B8%85%E5%96%AE.html?filter_cat=5&filter_gp=5&start=0');
        $html    = str_get_html($content);
        $list    = $html->find('.datatable tbody tr');

        foreach ($list as $tr) {
            $link = $tr->find('a', 0);
            $url  = 'http://www.cpami.gov.tw/' . $link->href;
            $text = $link->title;
            $page_upload->execute();
        }
    }
}

function NWT($type = 'new')
{
    global $page_upload, $gov, $url, $text;
    $gov      = 'NWT_N';
    $page_max = 8;
    for ($page_num = 1; $page_num <= $page_max; $page_num++) {
        $content = curl_simple('http://www.planning.ntpc.gov.tw/download/?page='.$page_num.'&type_id=10479&parent_id=10160');
        $html    = str_get_html($content);
        $list    = $html->find('#download_box tr');
        foreach ($list as $tr) {
            $text = $tr->children(0)->plaintext;
            if(!preg_match('/會議紀錄/', $text)) continue;
            if(preg_match('/專案小組/', $text)) continue;
            $url = $tr->children(2)->children(0)->href;
            $url = 'http://www.planning.ntpc.gov.tw/download/' . $url;
            $page_upload->execute();
        }
    }
}

$function_list = [
                'NWT',
                'TXG',
                'TPE',
                'TAO',
                'KHH',
                'KEE',
                'HSZ',
                'YUN',
                'NAN',
                'MIA',
                // 'LIE',
                'ILA',
                'HUA',
                'HSQ',
                'MOI',
                'MOICRO'
                ];

foreach ($function_list as $place) {
    try {
        call_user_func($place, 'all');
        echo 'Success ('. $place .').' . PHP_EOL;
    } catch (Exception $e) {
        echo 'Crawler failed ('. $place .'): ' . $e->getMessage() . PHP_EOL;
        $log_file = 'error.log';
        $current = file_get_contents($log_file);
        $current .= 'Crawler failed ('. $place .'): ' . $e->getMessage() . '\n';
        file_put_contents($log_file, $current);
    }
}
//
// $place = 'MOICRO';
//
// try {
//     MOICRO('all');
//     echo 'Success ('. $place .').' . PHP_EOL;
// } catch (Exception $e) {
//     echo 'Crawler failed ('. $place .'): ' . $e->getMessage() . PHP_EOL;
//     $log_file = 'error.log';
//     $current = file_get_contents($log_file);
//     $current .= 'Crawler failed ('. $place .'): ' . $e->getMessage() . '\n';
//     file_put_contents($log_file, $current);
// }

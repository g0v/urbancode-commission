<?php
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}
include_once 'toolbox.php';

$result = $db->query("SELECT * FROM page WHERE already=0 AND ((text!='' AND gov!='MOICRO_O' AND gov!='KEE_O') OR gov='TPE_O' OR gov='TXG_O' OR gov='TXG_N') LIMIT 0,5");
while ($row = $result->fetch()) {
    $mode         = '';
    $page_already = $db->prepare("UPDATE page SET already=:already WHERE url=:url");
    $page_already->bindValue(':url', $row['url']);

    $row['url']=htmlspecialchars_decode($row['url']);

    $page_already->bindValue(':already', 2);
    $page_already->execute();

    if ($row['text'] != '') {
        $text = preg_replace('/\s/', '', $row['text']);
        $url  = $row['url'];
    } else if ($row['gov'] == 'TPE_O') {
        $html = str_get_html(curl_simple($row['url']));
        if (!$html->find('.download', 0)) {
            continue;
        }
        $text = $html->find('.download', 0)->find('li', 0)->plaintext;
        $url  = 'http://www.tupc.gov.taipei/' . $html->find('.download', 0)->find('a', 0)->href;
    } else if ($row['gov'] == 'TXG_O' || $row['gov'] == 'TXG_N') {
        $html = curl_simple($row['url']);
        if (preg_match('/class="attachment"[\s\S]*?<\/section>/', $html, $match)) {
            $mode  = 'mul';
            $html  = str_get_html($match[0]);
            $list  = $html->find('.filename', 0)->find('li');
            $error = 0;
            foreach ($list as $li) {
                $url  = 'http://www.ud.taichung.gov.tw/' . $li->find('a', 0)->href;
                $text = $li->find('a text', 0);
                $error += download($row['gov'], $url, $text);
            }
        } else if (preg_match('/class="cp"[\s\S]*?<\/section>/', $html, $match)) {
            $html = str_get_html($match[0]);
            $text = $html->find('article', 0)->find('a', 0)->plaintext;
            $url  = 'http://www.ud.taichung.gov.tw/' . $html->find('article', 0)->find('a', 0)->href;
        }
    }

    if ($mode != 'mul') {
        $error = download($row['gov'], $url, $text);
    }
    if (!$error) {
        $page_already->bindValue(':already', 1);
        $page_already->execute();
    } elseif ($error) {
        $page_already->bindValue(':already', 2);
        $page_already->execute();
    }
}

function download($gov, $url, $text)
{
    global $number, $nth, $type, $db;
    $number = '';
    $nth    = '1';
    $type   = '';

    file_text_parser($text);
    $filename = $gov . '_' . $number . '_' . $nth;

    if ($type != 'none') {
        file_put_contents('origin/file', curl_simple($url));
        $error = file_rename($filename,$url);
    }

    $db->exec("REPLACE INTO file(filename,type) VALUES('$filename','$type')");
    return $error;
}

function file_text_parser($text)
{
    global $number, $nth, $type;
    if (preg_match("/\d{6,7}\D*(\d+)次\D*(\d-?\d?)/", $text, $match)) {
        $number =$match[1];
        $nth=$match[2];
    } else if (preg_match_all("/\D*(\d+)\D*/u", $text) == 1) {
        if (preg_match("/(\d*)次/u", $text, $match)) {
            $number = $match[1];
        } elseif (preg_match("/^(\d+)$/", $text, $match)) {
            $number = $match[1];
        } else {
            exit($text);
        }
    } elseif (preg_match_all("/\D*(\d+)\D*/u", $text) == 2) {
        if (preg_match("/(\d+)[屆年]\D*(\d+)[次會]/u", $text, $match)) {
            $match[1] = mb_strlen($match[1]) == 1 ? '0' . $match[1] : $match[1];
            $match[2] = mb_strlen($match[2]) == 1 ? '0' . $match[2] : $match[2];
            $number   = $match[1] . $match[2];
        } elseif (preg_match("/\d{6,7}\D*(\d+)/u", $text, $match)) {
            $number = $match[1];
        } elseif (preg_match("/(\d+)次\D*紀錄(\d+)/u", $text, $match)) {
            $number = $match[1];
            $nth    = $match[2];
        } elseif ($text == '桃園縣都市計畫委員會第16第31次會議紀錄') {
            $number = '1631';
        } elseif (preg_match("/(\d+)次\D*第(\d+)案/u", $text, $match)) {
            $number = $match[1];
            $nth    = $match[2];
        }elseif(preg_match("/(\d+)次\D*部分(\d+)/u", $text, $match)){
            $number = $match[1];
            $nth    = $match[2];
        } elseif ($text == '第162,163次都市計畫委員會審議紀錄') {
            $number = '162';
        } else {
            exit($text);
        }
    } elseif (preg_match_all("/\D*(\d+)\D*/u", $text) == 3) {
        if (preg_match("/高雄市都市計畫委員會第36次會議紀錄(\d-?\d?)/", $text, $match)) {
            $number = '36';
            $nth    = $match[1];
        } else {
            exit($text);
        }
    } elseif ($text == '第二次專案會議紀錄') {
        $number = '第二次專案會議紀錄';
    }else {
        exit($text);
    }

    if (preg_match('/續/', $text) && $nth == '1' && !preg_match('/暨延續/', $text)) {
        $nth = '2';
    }

    if (preg_match('/無|x|X/', $text)) {
        $type = 'none';
    }
}

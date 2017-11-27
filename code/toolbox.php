<?php
error_reporting(E_ALL);
include_once '../connect_mysql.php';
include_once 'simple_html_dom.php';

try {
  $page_upload = $db->prepare('INSERT IGNORE INTO page(gov,url,text) VALUES(:gov,:url,:text)');
} catch (PDOException $e) {
    echo 'error: ' . $e->getMessage();
}
$page_upload->bindParam(':gov', $gov);
$page_upload->bindParam(':text', $text);
$page_upload->bindParam(':url', $url);

function file_rename($filename, $url)
{
    global $type;
    $mime = mime_content_type('origin/file');
    if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || preg_match('/docx/', $url)) {
        $type = 'docx';
    } elseif ($mime === 'application/msword' || preg_match('/doc/', $url)) {
        $type = 'doc';
    } elseif ($mime === 'application/pdf' || preg_match('/pdf/', $url)) {
        $type = 'pdf';
    } else {
        $type = 'unknow';
        return 1;
    }
    rename('origin/file', 'origin/' . $filename . '.' . $type);
    return 0;
}

function file_to_txt($filename, $type)
{
    global $error;
    if ($type == 'pdf') {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile('origin/' . $filename . '.pdf')->getText();
        file_put_contents('txt/' . $filename . '.txt', $pdf);
    } else {
        $error = 1;
    }
}

function curl_simple($url, $post = '', $port = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36");
    if ($port) {
        curl_setopt($ch, CURLOPT_PORT, $port);
    }
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    return curl_exec($ch);
}

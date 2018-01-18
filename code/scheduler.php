<?php
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

require_once('crawler_page.php');
sleep(5);
require_once('crawler_file.php');
sleep(5);
require_once('crawler_transform.php');
sleep(5);
require_once('txt2json_convert.php');
sleep(5);
require_once('file2db.php');

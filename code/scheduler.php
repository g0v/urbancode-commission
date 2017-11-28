<?php
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

require_once('page.php');
sleep(5);
require_once('file.php');
sleep(5);
require_once('transform.php');
sleep(5);
require_once('txt_to_json_convert.php');
sleep(5);
require_once('file_to_db.php');

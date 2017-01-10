<?php
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
include 'connect_mysql.php';
include 'toolbox.php';
include 'pdfparser/vendor/autoload.php';

$result=$db->query("SELECT * FROM file WHERE type='pdf' AND transform=0 LIMIT 0,3");
while ($row=$result->fetch()) {
	$filename=$row['filename'];
	$type=$row['type'];
	$db->exec("UPDATE file SET transform=2 WHERE filename='$filename'");
	file_to_txt($filename,$type);

	$db=new PDO($dsn,$db_user,$db_password,$db_options);
	if (!$error) {
		$db->exec("UPDATE file SET transform=1 WHERE filename='$filename'");
	}elseif ($error) {
		$db->exec("UPDATE file SET transform=2 WHERE filename='$filename'");
	}
}
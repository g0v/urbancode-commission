<?php
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
include_once 'crawler_toolbox.php';
include_once 'pdfparser/vendor/autoload.php';

try {
	$result=$db->query("SELECT * FROM file WHERE type='pdf' AND transform=0 LIMIT 0,3");
} catch (PDOException  $e) {
    echo 'error: ' . $e->getMessage();
}
while ($row=$result->fetch()) {
	$filename=$row['filename'];
	$type=$row['type'];
	$db->exec("UPDATE file SET transform=2 WHERE filename='$filename'");
	file_to_txt($filename,$type);

	if (!$error) {
		$db->exec("UPDATE file SET transform=1 WHERE filename='$filename'");
	}elseif ($error) {
		$db->exec("UPDATE file SET transform=2 WHERE filename='$filename'");
	}
}

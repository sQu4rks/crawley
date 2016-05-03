<?php
ini_set("display_errors", "1");

/*
 * Used to export the results into simple files for further analysis..
 */

require_once(dirname(__FILE__)."/inc/load.php");

ini_set("memory_limit", "1G");

$wordpressExport = fopen("export_wp.txt", "wb");
if(!$wordpressExport){
	die("Failed to open wordpress export file!");
}

$otherExport = fopen("export_other.txt", "wb");
if(!$wordpressExport){
	die("Failed to open other export file!");
}

$statusExport = fopen("export_status.txt", "wb");
if(!$wordpressExport){
	die("Failed to open status export file!");
}

$refExport = fopen("export_references.txt", "wb");
if(!$refExport){
	die("Failed to open references export file!");
}

$session = $CASSANDRA->getPointer();
$statement = new Cassandra\SimpleStatement("SELECT * FROM crawl");
$options   = new Cassandra\ExecutionOptions(array('page_size' => 10000));
$rows      = $session->execute($statement, $options);

$count = 0;

while (true) {
	$count += $rows->count();
	foreach($rows as $row){
		$meta = $row['generatormeta'];
		fputs($statusExport, $row['domainname'].":".$row['httpstatus']."\n");
		if(strlen($meta) == 0){
			continue;
		}
		if(strpos($meta, "WordPress ") === false || strpos($meta, ".") === false || strpos($meta, "responsive") !== false){
			fputs($otherExport, $row['domainname'].":".$meta."\n");
		}
		else{
			fputs($wordpressExport, $row['domainname'].":".$meta."\n");
		}
	}

	echo "$count...\n";

	if ($rows->isLastPage()) {
		break;
	}

	$rows = $rows->nextPage();
}

fclose($otherExport);
fclose($wordpressExport);

$session = $CASSANDRA->getPointer();
$statement = new Cassandra\SimpleStatement("SELECT * FROM reference");
$options   = new Cassandra\ExecutionOptions(array('page_size' => 5000));
$rows      = $session->execute($statement, $options);

$count = 0;

while (true) {
	$count += $rows->count();
	foreach($rows as $row){
		fputs($refExport, $row['fromdomain'].":".$row['todomain']."\n");
	}

	echo "$count...\n";

	if ($rows->isLastPage()) {
		break;
	}

	$rows = $rows->nextPage();
}

fclose($refExport);

	
	
	
	
	
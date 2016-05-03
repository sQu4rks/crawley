<?php
require_once(dirname(__FILE__)."/inc/load.php");

if(!isset($argv[1]) || $argv[1] != 'valid'){
	die("No access!");
}

$pid = new Pid('/tmp');
if($pid->already_running) {
	die(); //exit if script is still running
}


$session = $CASSANDRA->getPointer();
$statement = new Cassandra\SimpleStatement("SELECT domainname FROM crawl WHERE chunkid=0");
$options   = new Cassandra\ExecutionOptions(array('page_size' => 2000));
$rows      = $session->execute($statement, $options);

$count = 0;
$chplus = 0;
while (true) {
	$count += $rows->count();
	if($count == 0){
		break;
	}

	$unordered = array();
	foreach($rows as $row){
		$unordered[] = $row['domainname'];
	}
	
	echo "Received ".sizeof($unordered)." domains...\n";
	
	$numChunks = floor(sizeof($unordered)/$CHUNKSIZE);
	if(sizeof($unordered)%$CHUNKSIZE != 0){
		$numChunks++;
	}
	echo "Will trying to create $numChunks chunks...\n";
	for($x=0;$x<$numChunks;$x++){
		$start = $x*$CHUNKSIZE;
		$end = ($x+1)*$CHUNKSIZE;
		if($x == $numChunks -1){
			$end = sizeof($unordered);
		}
		if($end - $start > 100){
			$DB->query("START TRANSACTION");
			$res = $DB->query("INSERT INTO DataChunk (chunkId, size) VALUES (NULL, ".($end-$start).")");
			$chunkId = $DB->lastInsertId();
			$arr = array();
			for($y=$start;$y<$end;$y++){
				$arr[] = array($unordered[$y], -2, 0, "", $chunkId);
			}
			$CASSANDRA->updateDomain($arr);
			$DB->query("COMMIT");
			$chplus++;
			echo "$chunkId OK...\n";
		}
	}

	if ($rows->isLastPage()) {
		break;
	}

	$rows = $rows->nextPage();
}

if($chplus > 0){
	echo $chplus." new Chunks were created!\n";
	file_put_contents("/home/crawl/cron.log", $chplus." new Chunks were created!\n", FILE_APPEND);
}








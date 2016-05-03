<?php
require_once(dirname(__FILE__)."/inc/load.php");

if(!isset($argv[1]) || $argv[1] != 'valid'){
	die("No access!");
}

$pid = new Pid('/tmp');
if($pid->already_running) {
	die(); //exit if script is still running
}

//TODO:
//check for Chunks which are dispatched only but longer than 1800s ago
//look if there is data without beeing assigned to a chunk and create new chunks
//update statistics about how many chunks are completed and not
//CHUNKSIZE = 500


$res = $DB->query("SELECT sum(size) as leftsize FROM DataChunk WHERE finishedTime IS NULL");
$line = $res->fetch();
$left = $line['leftsize'];
$res = $DB->query("SELECT sum(size) as donesize FROM DataChunk WHERE finishedTime IS NOT NULL");
$line = $res->fetch();
$done = $line['donesize'];
$res = $DB->query("SELECT sum(refs) as totalrefs FROM DataChunk WHERE 1");
$line = $res->fetch();
$refs = $line['totalrefs'];
echo "-$left-$refs-$done-\n";
$DB->query("INSERT INTO Stat (time, numDone, numLeft, numRefs) VALUES (".time().", $done, $left, $refs)");

//clean unfinished chunks
$res = $DB->query("UPDATE DataChunk SET finishedTime=NULL, dispatchedTime=NULL, refs=NULL, agentId=NULL WHERE finishedTime IS NULL AND dispatchedTime<".(time() - 3600));








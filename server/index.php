<?php
use Bricky\Template;
require_once(dirname(__FILE__)."/inc/load.php");

$TEMPLATE = new Template("index");
$MENU->setActive("index");

$res = $DB->query("SELECT * FROM Stat WHERE 1 ORDER BY time DESC LIMIT 1");
$data = $res->fetch();

$OBJECTS['left'] = $data['numLeft'];
$OBJECTS['done'] = $data['numDone'];
$OBJECTS['refCount'] = $data['numRefs'];

$total = $data['numLeft'] + $data['numDone'];
$percentLeft = (int)($data['numLeft']/$total*10000);
$percentDone = (int)($data['numDone']/$total*10000);

$percentLeft = $percentLeft/100;
$percentDone = 100 - $percentLeft;

$OBJECTS['percentLeft'] = $percentLeft;
$OBJECTS['percentDone'] = $percentDone;

$res = $DB->query("SELECT * FROM Stat WHERE 1 ORDER BY time DESC LIMIT 100");
$res = $res->fetchAll();
$dataDone = array();
$dataLeft = array();
$dataRefs = array();
$newest = null;
$secnewest = null;
$count = 0;
foreach($res as $entry){
	if($newest == null){
		$newest = $entry;
	}
	else if($secnewest == null){
		$secnewest = $entry;
	}
	$dataDone[] = "[ ".round(-$count*0.25, 2).", ".($entry['numDone'])."]";
	$dataLeft[] = "[ ".round(-$count*0.25, 2).", ".($entry['numLeft'])."]";
	$dataRefs[] = "[ ".round(-$count*0.25, 2).", ".($entry['numRefs'])."]";
	$count++;
}
$doneData = "[ ".implode(",", $dataDone)." ]";
$leftData = "[ ".implode(",", $dataLeft)." ]";
$refsData = "[ ".implode(",", $dataRefs)." ]";

$OBJECTS['doneData'] = $doneData;
$OBJECTS['leftData'] = $leftData;
$OBJECTS['refsData'] = $refsData;

$res = $DB->query("SELECT count(agentId) AS count FROM Agent WHERE lastAction>".(time() - 900));
$entry = $res->fetch();
$OBJECTS['numAgents'] = $entry['count'];
$OBJECTS['speed'] = floor(($newest['numDone'] - $secnewest['numDone'])/($newest['time'] - $secnewest['time'])*60);

$OBJECTS['timeLeft'] = round($data['numLeft']/$OBJECTS['speed']/60/24, 2);

echo $TEMPLATE->render($OBJECTS);





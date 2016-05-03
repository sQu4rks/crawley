<?php
ini_set("display_errors", "1");

require_once(dirname(__FILE__)."/inc/load.php");

ini_set("memory_limit", "1G");

if(isset($_GET['mastertoken']) && isset($_GET['name'])){
	$master = $_GET['mastertoken'];
	$name = $_GET['name'];
	if($master == "Y9vHG7C65VYBD6D9atN6"){
		$res = $DB->query("SELECT * FROM Agent WHERE agentName=".$DB->quote($name));
		$line = $res->fetch();
		if($line){
			die($line['token']);
		}
		$token = Util::randomString(15);
		$valid = false;
		while(!$valid){
			$res = $DB->query("SELECT * FROM Agent WHERE token=".$DB->quote($token));
			$line = $res->fetch();
			if($line){
				$valid = false;
				$token = Util::randomString(15);
			}
			else{
				$valid = true;
			}
		}
		$DB->query("INSERT INTO Agent (token, lastAction, agentName) VALUES (".$DB->quote($token).", '0', ".$DB->quote($name).")");
		die($token);
	}
}

$token = $_GET['token'];
$action = $_GET['action'];
$chunksize = 500;

$res = $DB->query("SELECT * FROM Agent WHERE token=".$DB->quote($token));
$agent = $res->fetch();

if(!$agent){
	die("Invalid token!\n");
}

$DB->query("UPDATE Agent SET lastAction='".time()."' WHERE agentId=".$agent['agentId']);

switch($action){
	case 'getdata':
		$chunkId = $_GET['chunk'];
		$res = $DB->query("SELECT * FROM DataChunk WHERE chunkId=".$DB->quote($chunkId));
		$chunk = $res->fetch();
		if(!$chunk){
			die("ERROR:Chunk not found!");
		}
		$res = $CASSANDRA->selectDomainChunk($chunk['chunkId']);
		$output = array();
		foreach($res as $entry){
			$output[] = $entry['domainname'].",".$entry['httpstatus'].",".$entry['time'].",".$entry['generatormeta'];
		}
		echo $chunkId."\n".implode("\n", $output);
		break;
	case 'getchunks':
		$res = $DB->query("SELECT chunkId FROM DataChunk WHERE 1");
		if($res->rowCount() == 0){
			die("ERROR:No chunks found!");
		}
		$res = $res->fetchAll();
		$output = array();
		foreach($res as $entry){
			$output[] = $entry['chunkId'];
		}
		echo implode(",", $output);
		break;
	case 'gettaskforce': //example: 107648
		$chunkId = $_GET['chunk'];
		$res = $DB->query("SELECT * FROM DataChunk WHERE chunkId=".$DB->quote($chunkId));
		$chunk = $res->fetch();
		if(!$chunk){
			die("ERROR:Chunk not found!");
		}
		$DB->query("UPDATE DataChunk SET agentId='".$agent['agentId']."', dispatchedTime='".time()."' WHERE chunkId=".$DB->quote($chunkId));
		$res = $DB->query("SELECT * FROM DataChunk WHERE finishedTime IS NULL AND agentId='".$agent['agentId']."' ORDER BY dispatchedTime DESC LIMIT 1");
		$chunk = $res->fetch();
		if(!$chunk){
			die("ERROR:No domains available to scan!");
		}
		$output = array();
		$res = $CASSANDRA->selectDomainChunk($chunk['chunkId']);
		foreach($res as $entry){
			if(strpos($entry['domainname'], "'") !== false){
				$repl = str_replace("'", "", $entry['domainname']);
				$CASSANDRA->replaceDomain($entry['domainname'], $repl);
				$entry['domainname'] = $repl;
			}
			$output[] = $entry['domainname'];
		}
		if(sizeof($output) != $chunk['size']){
			$DB->query("UPDATE DataChunk SET size=".sizeof($output)." WHERE chunkId=".$chunk['chunkId']);
		}
		echo $chunk['chunkId']."\n".implode("\n", $output);
		break;
	case 'gettask':
		$DB->query("UPDATE DataChunk SET agentId='".$agent['agentId']."', dispatchedTime='".time()."' WHERE agentId IS NULL LIMIT 1");
		$res = $DB->query("SELECT * FROM DataChunk WHERE finishedTime IS NULL AND agentId='".$agent['agentId']."' ORDER BY dispatchedTime DESC LIMIT 1");
		$chunk = $res->fetch();
		if(!$chunk){
			die("ERROR:No domains available to scan!");
		}
		$output = array();
		$res = $CASSANDRA->selectDomainChunk($chunk['chunkId']);
		foreach($res as $entry){
			if(strpos($entry['domainname'], "'") !== false){
				$repl = str_replace("'", "", $entry['domainname']);
				$CASSANDRA->replaceDomain($entry['domainname'], $repl);
				$entry['domainname'] = $repl;
			}
			$output[] = $entry['domainname'];
		}
		if(sizeof($output) != $chunk['size']){
			$DB->query("UPDATE DataChunk SET size=".sizeof($output)." WHERE chunkId=".$chunk['chunkId']);
		}
		echo $chunk['chunkId']."\n".implode("\n", $output);
		break;
	case 'sendtask':
		$chunkId = $_POST['chunk'];
		$result = $_POST['result'];
		$collected = $_POST['collected'];
		$res = $DB->query("SELECT * FROM DataChunk WHERE agentId=".$agent['agentId']." AND chunkId=".$DB->quote($chunkId));
		$chunk = $res->fetch();
		if(!$chunk){
			die("ERROR:Chunk not found!");
		}
		$result = explode("\n", $result);
		if(sizeof($result) != $chunk['size']){
			die("ERROR:Wrong number of results was sent!");
		}
		
		$arr = array();
		foreach($result as $entry){
			//[domainName],[httpStatus],[time],[generatorMeta]
			$entry = explode(",", $entry);
			if(sizeof($entry) < 4){
				continue;
			}
			if(strlen($entry[3]) > 250){
				$entry[3] = "";
			}
			$meta = $entry[3];
			for($x=4;$x<sizeof($entry);$x++){
				$meta .= ",".$entry[$x];
			}
			if(!Util::validUtf($meta)){
				$meta = "";
			}
			$arr[] = array($entry[0], $entry[1], $entry[2], $meta, $chunkId);
		}
		$CASSANDRA->updateDomain($arr);

		$collected = explode("\n", $collected);
		$domainList = array();
		foreach($collected as $entry){
			$entry = explode(",", $entry);
			if(sizeof($entry) != 2){
				continue;
			}
			$domainList[] = $DB->quote(strtolower($entry[0]));
		}

		$arr = array();
		foreach($domainList as $domain){
			$res = $CASSANDRA->selectDomain($domain);
			if($res){
				continue;
			}
			$arr[] = array($domain, "-2", 0, "", 0);
		}
		$CASSANDRA->insertDomain($arr);
		
		$counter = 0;
		$arr = array();
		foreach($collected as $entry){
			$entry = explode(",", strtolower($entry));
			if(sizeof($entry) != 2){
				continue;
			}
			else if($entry[0] == $entry[1]){
				continue;
			}
			$counter++;
			$id = substr(hash("whirlpool", $entry[1]."###".$entry[0]), 0, 16);
			$arr[] = array($id, $entry[1], $entry[0]);
		}
		$CASSANDRA->insertReference($arr);
		
		$DB->query("UPDATE DataChunk SET finishedTime='".time()."', refs='$counter' WHERE chunkId=".$DB->quote($chunkId));
		die("OK");
		break;
}






<?php
use Bricky\Template;
require_once(dirname(__FILE__)."/inc/load.php");

$TEMPLATE = new Template("domains");
$MENU->setActive("domains");

if(isset($_GET['name'])){
	$domainName = urldecode($_GET['name']);
	$domain = $CASSANDRA->selectDomain($domainName);
	if($domain){
		$set = new DataSet();
		$set->setValues($domain);
		$OBJECTS['domain'] = $set;
		
		$res = $CASSANDRA->selectReferenceTo($domainName);
		$refsTo = array();
		foreach($res as $entry){
			$set = new DataSet();
			$set->setValues($entry);
			$refsTo[] = $set;
		}
		$OBJECTS['refsTo'] = $refsTo;
		
		$res = $CASSANDRA->selectReferenceFrom($domainName);
		$refsFrom = array();
		foreach($res as $entry){
			$set = new DataSet();
			$set->setValues($entry);
			$refsFrom[] = $set;
		}
		$OBJECTS['refsFrom'] = $refsFrom;
	}
	else{
		die("INVALID!");
	}
}
else{
	die("INVALID!");
}

echo $TEMPLATE->render($OBJECTS);





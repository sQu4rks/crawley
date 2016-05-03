<?php
use Bricky\Template;
require_once(dirname(__FILE__)."/inc/load.php");

$TEMPLATE = new Template("chunks");
$MENU->setActive("chunks");

if(isset($_GET['id'])){
	$chunkId = $_GET['id'];
	$res = $DB->query("SELECT * FROM DataChunk WHERE chunkId=".$DB->quote($chunkId));
	$chunk = $res->fetch();
	if($chunk){
		$res = $CASSANDRA->selectDomainChunk($chunk['chunkId']);
		$data = array();
		foreach($res as $entry){
			$set = new DataSet();
			$set->setValues($entry);
			$data[] = $set;
		}
		$OBJECTS['data'] = $data;
	}
	$OBJECTS['chunk'] = $chunk;
	$TEMPLATE = new Template("chunks.detail");
}
else{
	$page = 0;
	$pagesize = 30;
	if(isset($_GET['page'])){
		$page = intval($_GET['page']);
		if($page < 0){
			$page = 0;
		}
	}
	$res = $DB->query("SELECT * FROM DataChunk WHERE agentId IS NOT NULL ORDER BY chunkId DESC LIMIT ".($pagesize*$page).", $pagesize");
	$res = $res->fetchAll();
	$chunks = array();
	foreach($res as $entry){
		$set = new DataSet();
		$set->setValues($entry);
		$chunks[] = $set;
	}
	$OBJECTS['chunks'] = $chunks;
	$OBJECTS['pageNum'] = ($page + 1);
	$OBJECTS['prevPage'] = ($page - 1);
	$OBJECTS['nextPage'] = ($page + 1);
}

echo $TEMPLATE->render($OBJECTS);





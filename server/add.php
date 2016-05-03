<?php
use Bricky\Template;
require_once(dirname(__FILE__)."/inc/load.php");

$TEMPLATE = new Template("add");
$MENU->setActive("add");
$message = "";

if(isset($_POST['action'])){
	$domain = $_POST['domain'];
	$domain = str_replace("http://", "", str_replace("https://", "", str_replace("'", "", $domain)));
	$res = $CASSANDRA->selectDomain($domain);
	if(strlen($domain) == 0){
		$message = "<div class='alert alert-danger'>Empty domain!</div>";
	}
	else if($res){
		$message = "<div class='alert alert-warning'>Domain '$domain' already in database!</div>";
	}
	else{
		$CASSANDRA->insertDomain(array(array($domain, -2, 0, "", 0)));
		$message = "<div class='alert alert-success'>Domain '$domain' was added to database!</div>";
	}
}
else{
	//nothing
}

$OBJECTS['message'] = $message;

echo $TEMPLATE->render($OBJECTS);





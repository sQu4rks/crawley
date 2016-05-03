<?php
//just to debug
ini_set("display_errors", "1");

$CONN['dbhost'] = "localhost";
$CONN['dbuser'] = "dbuser";
$CONN['dbpass'] = "dbpass";
$CONN['db'] = "crawly";

require_once(dirname(__FILE__)."/dataset.class.php");
require_once(dirname(__FILE__)."/menu.class.php");
require_once(dirname(__FILE__)."/template.class.php");
require_once(dirname(__FILE__)."/util.class.php");
require_once(dirname(__FILE__)."/cassandra.class.php");
require_once(dirname(__FILE__)."/pid.class.php");

$DB = new PDO('mysql:dbname=' . $CONN['db'] . ";" . "host=" . $CONN['dbhost'], $CONN['dbuser'], $CONN['dbpass']);
$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$CASSANDRA = new CrawlyCassandra();
$CHUNKSIZE = 500;
	
$MENU = new Menu();
$OBJECTS['menu'] = $MENU;
	
	
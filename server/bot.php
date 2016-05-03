<?php
use Bricky\Template;
require_once(dirname(__FILE__)."/inc/load.php");

$TEMPLATE = new Template("bot");
$MENU->setActive("bot");

echo $TEMPLATE->render($OBJECTS);





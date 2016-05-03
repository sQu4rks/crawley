<?php

$SERVER = "https://crawl.hashes.org/api.php?";

$numagents = 10;
$mastertoken = "...";

if(!isset($argv[1])){
	die("Need an agent prefix!");
}
$agentprefix = $argv[1];
if(isset($argv[2]) && intval($argv[2]) > 0){
	$numagents = intval($argv[2]);
}

$token = array();
for($x=0;$x<$numagents;$x++){
	$res = doApiRequest($SERVER."mastertoken=$mastertoken&name=$agentprefix$x", array());
	$token[] = $res;
}

echo "Got tokens for ".sizeof($token)." agents...\n";


$thread_create = array();
$thread_start = array();
$count = 0;
foreach($token as $agent){
	$thread_create[] = "thread$count = myThread($count, 'Thread-$agent', $count, '$agent')";
	$thread_start[] = "thread$count.start()";
	$count++;
}

$val = file_get_contents("template.py");
$val = str_replace("__THREADS__", implode("\n", $thread_create)."\n\n".implode("\n", $thread_start), $val);
file_put_contents("runner_$agentprefix.py", $val);


function doApiRequest($url, $data){
	echo "REQUEST: $url...\n";
	$curl = curl_init();
	curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => "$url",
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 40,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => http_build_query($data)
		)
	);
	$resp = curl_exec($curl);
	curl_close($curl);
	return $resp;
}





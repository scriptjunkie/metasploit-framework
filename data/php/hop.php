<?php
$magic = 'TzGq';
$tempdir = sys_get_temp_dir() . "/hop" . $magic;
if(!is_dir($tempdir)){
	mkdir($tempdir); //make sure it's there
}

//get url
$url = $_SERVER["QUERY_STRING"];
//like /path/hop.php?/uRIcksm_lOnGidENTifIEr

//Looks for a file with a name or contents prefix, if found, send it and deletes it
function findSendDelete($tempdir, $prefix){
	if($dh = opendir($tempdir)){
		while(($file = readdir($dh)) !== false){
			if(strpos($file, $prefix) !== 0){
				continue;
			}
			readfile($tempdir."/".$file);
			unlink($tempdir."/".$file);
			break;
		}
	}
}

//handle control
if($url === "/control"){
	if($_SERVER['REQUEST_METHOD'] === 'POST'){
		//handle data for payload - save in a "down" file or the "init" file
		$postdata = file_get_contents("php://input");
		if(array_key_exists('HTTP_X_INIT', $_SERVER)){
			$f = fopen($tempdir."/init", "w"); //only one init file
		}else{
			$prefix = "down_" . bin2hex($_SERVER['HTTP_X_URLFRAG']);
			$f = fopen(tempnam($tempdir,$prefix), "w");
		}
		fwrite($f, $postdata);
		fclose($f);
	}else{
		findSendDelete($tempdir, "up_");
	}
}else if($_SERVER['REQUEST_METHOD'] === 'POST'){
	//get data
	$postdata = file_get_contents("php://input");
	//See if we should send anything down
	if($postdata === 'RECV'){
		findSendDelete($tempdir, "down_" . bin2hex($url));
		$fname = $tempdir . "/up_recv_" . bin2hex($url); //Only keep one RECV poll
	}else{
		$fname = tempnam($tempdir, "up_"); //actual data gets its own filename
	}
	//find free and write new file
	$f = fopen($fname, "w");
	fwrite($f, $magic);
	//Little-endian pack length and data
	$urlen = strlen($url);
	fwrite($f, pack('V', $urlen));
	fwrite($f, $url);
	fwrite($f, $postdata);
	fclose($f);
//Initial query will be a GET and have a 12345 in it
}else if(strpos($url, "12345") !== FALSE){
	readfile($tempdir."/init");
}
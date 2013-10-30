<?php

error_reporting(E_ERROR);

set_time_limit(0);

if(!isset($argv[1])){
	die("Insira um domínio como parâmetro: www.exemplo.com.br\n");
}else{
	$dominio = $argv[1];
}

$urlbase = "http://$dominio/";

$exceptions = array(
	"#",
	"javascript:void(0)",
	"javascript:;"
);

$urlList = array();

function addUrl($url, $status, $verified){

	global $urlbase, $urlList;
	
	$url = isAbsolute($url) ?  $url : $urlbase.$url;

	$md5Url = md5($url);
	
	$urlList[$md5Url]['url'] = $url;

	if($urlList[$md5Url]['status'] == 0){
		$urlList[$md5Url]['status'] = $status;
		$urlList[$md5Url]['datetime'] = date('Y-m-d H:i:s');
	}

	if(!$urlList[$md5Url]['verified']){
		$urlList[$md5Url]['verified'] = $verified;
	}

}

function getPage($url){

    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

	$result = curl_exec($ch);

	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	addUrl($url, $http_status, true);

    curl_close($ch);

    return array('html' => $result, 'status' => $http_status);

}

function isAbsolute($url){
	return strstr($url, 'http') !== false;
}

function isValidUrl($url){

	global $exceptions, $dominio;	
	
	if(!empty($url) && !in_array($url, $exceptions)){
		if(!(strstr($url, 'http') !== false && !strstr($url, $dominio))){
			return true;			
		}
	}

	return false;

}

function extractValues($html){
	
	global $urlbase, $urlList;

    $dom = new DOMDocument();

    $dom->loadHTML($html);

    $xpath = new DOMXpath($dom);

	foreach ($xpath->query("//a") as $key => $link) {
		
		$url = trim($link->getAttribute('href'));
		$md5Url = md5($url);

		if(isValidUrl($url)){
			addUrl($url,  0, false);
		}	

	}

    unset($dom, $xpath);

}

function urlCounter(){
	
	global $urlList;

	$urls = count($urlList);
	$urlCounter = 0;

	foreach($urlList as $link){	
		if($link['verified']){
			$urlCounter++;
		}
	}
	
	return $urls == $urlCounter;
}

function verifier($url){
	
	echo "Verificando: $url\n";

	$page = getPage($url);

	extractValues($page['html']);
	
}

verifier($urlbase);

while(!urlCounter()){

	foreach($urlList as $link){
		
		if(!$link['verified']){
			verifier($link['url']);
		}

	}
}

foreach($urlList as $link){
	echo "\"".$link['url']."\";".$link['status'].";\"".$link['datetime']."\"\n";
}

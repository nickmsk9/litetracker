<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Функционал для анонсера
===================================================================
*/


//Информация о правах класса
function get_priv_info($class) {
	global $memcache , $db;
	// if(empty($class) ) {
		// return false;
	// }
	$class = (int)$class;
	
	//Определяем права пользовател
	if (false === ($row = $memcache->get('priv_'.$class)))
	{
		$row = $db->super_query("SELECT * FROM priv WHERE id=".$class);
		$memcache->set('priv_'.$class , $row , 0, 300);
	}
	return $row;
}

//Преобразуем размер файла
function mksize($bytes)
{
    if ($bytes < 1000 * 1024)
        return number_format($bytes / 1024, 2) . " kB";
    elseif ($bytes < 1000 * 1048576)
        return number_format($bytes / 1048576, 2) . " MB";
    elseif ($bytes < 1000 * 1073741824)
        return number_format($bytes / 1073741824, 2) . " GB";
    else
        return number_format($bytes / 1099511627776, 2) . " TB";
}

	
/**
 * Checks that user client was not banned. Dies on false
 * @param string $peer_id Peer_id of client
 * @return void
 * Взято с kinokpk
 */
function checkclient($peer_id){
	$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
	//die($peer_id);
	//return true;
	//check by headers
	if (function_exists('getallheaders')){
		$headers = getallheaders();
	}else{
		$headers = emu_getallheaders();
	}
	if (isset($headers['Cookie']) || isset($headers['Accept-Language']) || isset($headers['Accept-Charset']))err('Вы не можете использовать этот клиент. Возможно вы читер.');

	//check by agent
	$banned = array();
	$banned[]= "FUTB";
	$banned[]= "ABC";
	$banned[]= "Opera";
	$banned[]= "Mozilla";
	$banned[]= "Rufus";
	// $banned[]= "Deluge";
	$banned[]= "BinTorrent";
	$banned[]= "TorrentStorm";
	$banned[]= "Burst!";
	$banned[]= "BitBuddy";
	$banned[]= "Shareaza";
	$banned[]= "TurboBT";
	$banned[]= "eXeem";
	$banned[]= "RAZA";
	$banned[]= "AG";
	$banned[]= "MLDonkey";
	$banned[]= "Ares";
	$banned[]= "Red Swoosh";
	$banned[]= "FDM";
	$banned[]= "SHAD0W";


	for($i=0;$i<sizeof($banned);$i++){
		if(strpos($agent, $banned[$i]) !== false) err("Извините, клиент ".$banned[$i]." запрещен на нашем трекере.");
	}


	if(strpos($agent, "uTorrent") !== false && strpos($agent, "B") !== false) err("Бета-версии uTorrent запрещены на нашем трекере, используйте стабильные релизы.");

	#    //check by peer_id

	if(substr($peer_id, 0, 6) == "exbc\08") err("Клиент BitComet 0.56 запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 4) == "FUTB") err("Клиент FUTB запрещен на нашем трекере."); //patched version of BitComet 0.57 (FUTB- Fuck U TorrentBits)
	elseif(substr($peer_id, 1, 2) == 'BC' && substr($peer_id, 5, 2) != 70 && substr($peer_id, 5, 2) != 63 && substr($peer_id, 5, 2) != 77 && substr($peer_id, 5, 2) >= 59/* && substr($peer_id, 5, 2) <= 88*/) err("BitComet ".substr($peer_id, 5, 2)." is banned.");
	elseif(preg_match("/^0P3R4H/", $peer_id)) err("Клиент Opera запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 7) == "exbc\0L") err("Клиент BitLord 1.0 запрещен на нашем трекере..");
	elseif(substr($peer_id, 0, 7) == "exbcL") err("Клиент BitLord 1.1 запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 3) == "-TS") err("Клиент TorrentStorm запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 5) == "Mbrst") err("Клиент Burst! запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 3) == "-BB") err("Клиент BitBuddy запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 3) == "-SZ") err("Клиент Shareaza запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 5) == "turbo") err("Клиент TurboBT запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 4) == "T03A") err("Клиет BitTornado установленной у вас версии забанен, пожалуйста, обновите клиент.");
	elseif(substr($peer_id, 0, 4) == "T03B") err("Клиет BitTornado установленной у вас версии забанен, пожалуйста, обновите клиент.");
	elseif(substr($peer_id, 0, 3 ) == "FRS") err("Клиент Rufus запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 2 ) == "eX") err("Клиент eXeem запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 8 ) == "-TR0005-") err("Клиент Transmission/0.5 запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 8 ) == "-TR0006-") err("Клиент Transmission/0.6 запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 8 ) == "-XX0025-") err("Клиент Transmission/0.6 запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 1 ) == ",") err ("Клиент RAZA запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 3 ) == "-AG") err("Ваш битторрент-клиент запрещен на нашем трекере.");
	elseif(substr($peer_id, 0, 3 ) == "R34") err("Клиент BTuga/Revolution-3.4 запрещен на нашем трекере.");
	elseif(preg_match("/MLDonkey\/([0-9]+).([0-9]+).([0-9]+)*/", $peer_id, $matches)) err("MLDonkey не является битторрент-клиентом.");
	elseif(preg_match("/ed2k_plugin v([0-9]+\\.[0-9]+).*/", $peer_id, $matches)) err("eDonkey не является битторрент-клиентом.");
	elseif(substr($peer_id, 0, 4) == "exbc") err("Это версия BitComet заблокирована.");
	elseif(substr($peer_id, 0, 3) == '-FG') err("FlashGet запрещен на нашем трекере.");

	elseif(substr($peer_id, 1, 2) == 'UT'){
		$UTVersion = (int) substr($peer_id, 3, 4);
		if($UTVersion<1610)err("uTorrent версии ниже ".$UTVersion." запрещен на нашем трекере.");
		//elseif($UTVersion>1610 AND $UTVersion<1810)err("uTorrent версии ".$UTVersion." запрещен на нашем трекере.");
	}



	//exbcLORD         BitLord 1.1
	//-UT1750            uTorrent 1750
	//-UT1610-           uTorrent 1610
	//-BC0070-\tiB       BitTorrent/3.4.2
	//-TR1110-6lzvmrvc7i06 Transmission/1.11 (5504)
	//-lt0C00            rtorrent/0.8.0/0.12.0
	//T03I                BitTornado/T-0.3.18

	//check by agent and version (not all versions are banned)

}

/**
 * Get all headers emulation
 * @return array Emulated headers
 */
function emu_getallheaders() {
	$headers = array();
	foreach($_SERVER as $name => $value)
	if(substr($name, 0, 5) == 'HTTP_')
	$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	return $headers;
}


	
function validip($ip) {
	if (!empty($ip) && $ip == long2ip(ip2long($ip)))
	{
		$reserved_ips = array (
		array('0.0.0.0','2.255.255.255'),
		array('10.0.0.0','10.255.255.255'),
		array('127.0.0.0','127.255.255.255'),
		array('169.254.0.0','169.254.255.255'),
		array('172.16.0.0','172.31.255.255'),
		array('192.0.2.0','192.0.2.255'),
		array('192.168.0.0','192.168.255.255'),
		array('255.255.255.0','255.255.255.255')
		);

		foreach ($reserved_ips as $r)
		{
			$min = ip2long($r[0]);
			$max = ip2long($r[1]);
			if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
		}
		return true;
	}
	else return false;
}	

function getip() {
	if (isset($_SERVER)) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && validip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && validip($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR') && validip(getenv('HTTP_X_FORWARDED_FOR'))) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_CLIENT_IP') && validip(getenv('HTTP_CLIENT_IP'))) {
			$ip = getenv('HTTP_CLIENT_IP');
		} else {
			$ip = getenv('REMOTE_ADDR');
		}
	}

	return $ip;
}

function gzip() {
	if (@extension_loaded('zlib') && @ini_get('zlib.output_compression') != '1' && @ini_get('output_handler') != 'ob_gzhandler') {
		@ob_start('ob_gzhandler');
	}
	return;
}



function err($msg){
	benc_resp(array('failure reason' => array('type' => 'string', 'value' => $msg)));
	die();
}


function benc_resp($d)
{
	benc_resp_raw(benc(array('type' => 'dictionary', 'value' => $d)));
}

function benc_resp_raw($x)
{
	header("Content-Type: text/plain");
	header("Pragma: no-cache");
	echo($x);
	return;
}

function portblacklisted($port)
{
	// direct connect
	if ($port >= 411 && $port <= 413) return true;

	// bittorrent
	if ($port >= 6881 && $port <= 6889) return true;

	// kazaa
	if ($port == 1214) return true;

	// gnutella
	if ($port >= 6346 && $port <= 6347) return true;

	// emule
	if ($port == 4662) return true;

	// winmx
	if ($port == 6699) return true;

	return false;
}


function sqlesc($value) {
	// Quote if not a number or a numeric string
	if (!is_numeric($value)) {
		$value = "'" . mysql_real_escape_string($value) . "'";
	}
	return $value;
}

//Определяем ратио
function get_ratio($uploaded , $downloaded) {
	
	if($downloaded > 0) {
		$ratio =  ($uploaded / ($downloaded / 10) / 1);
		$ratio = number_format($ratio);
		$ratio = str_replace(',' , '' , $ratio);
	}else {
		$ratio = '0';
	}

	return $ratio;
}
?>

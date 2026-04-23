<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Функционал для анонсера
===================================================================
*/


function announce_fail_message()
{
	return 'Не удалось обработать запрос трекера.';
}

function announce_get_string_param($name)
{
	return (string) ($_GET[$name] ?? '');
}

function announce_get_int_param($name)
{
	return (int) ($_GET[$name] ?? 0);
}

function announce_ensure_string_length($value, $length, $label)
{
	$value = (string) $value;
	$length = (int) $length;

	if (strlen($value) !== $length) {
		err(sprintf($GLOBALS['language']['announce_2'], $label, strlen($value), urlencode($value)));
	}

	return $value;
}

function announce_parse_request()
{
	$request = array(
		'info_hash' => announce_ensure_string_length(announce_get_string_param('info_hash'), 20, 'info_hash'),
		'peer_id' => announce_ensure_string_length(announce_get_string_param('peer_id'), 20, 'peer_id'),
		'event' => announce_get_string_param('event'),
		'ip' => announce_get_string_param('ip'),
		'localip' => announce_get_string_param('localip'),
		'port' => announce_get_int_param('port'),
		'downloaded' => announce_get_int_param('downloaded'),
		'uploaded' => announce_get_int_param('uploaded'),
		'left' => announce_get_int_param('left'),
		'passkey' => trim((string) ($_GET['passkey'] ?? '')),
		'compact' => ((int) ($_GET['compact'] ?? 0) === 1),
		'no_peer_id' => ((int) ($_GET['no_peer_id'] ?? 0) === 1),
	);

	foreach (array('info_hash', 'peer_id', 'port', 'downloaded', 'uploaded', 'left') as $field) {
		if ($request[$field] === '' && !is_int($request[$field])) {
			err(sprintf($GLOBALS['language']['announce_1'], $field));
		}
	}

	return $request;
}

function announce_numwant($default = 50)
{
	foreach (array('num want', 'numwant', 'num_want') as $key) {
		if (isset($_GET[$key])) {
			return max(1, (int) $_GET[$key]);
		}
	}

	return max(1, (int) $default);
}

function announce_apply_rate_limit($scope, $identifier, $limit, $windowSeconds, $message)
{
	$result = lt_rate_limit_hit($scope, $identifier, $limit, $windowSeconds);
	if (!empty($result['blocked'])) {
		err((string) $message);
	}

	return $result;
}

function announce_cache_get($key, $namespace)
{
	return lt_cache_get($key, $namespace);
}

function announce_cache_set($key, $value, $ttl, $namespace)
{
	return lt_cache_set($key, $value, $ttl, $namespace);
}

function announce_safe_query($query)
{
	global $db;

	$result = $db->query($query, 0);
	if (!$result) {
		err(announce_fail_message());
	}

	return $result;
}

function announce_super_query($query)
{
	global $db;

	$row = $db->super_query($query);
	if ($row === false) {
		err(announce_fail_message());
	}

	return $row;
}

function announce_rows_affected()
{
	global $db;

	return (int) $db->affected_rows();
}

function announce_escape($value)
{
	global $db;

	if (!is_numeric($value)) {
		return "'".$db->safesql((string) $value)."'";
	}

	return (string) $value;
}

function announce_fetch_ip_ban($ipLong)
{
	$ipLong = (string) $ipLong;

	return lt_cache_remember(
		'ip_ban:'.$ipLong,
		1000,
		function () use ($ipLong) {
			return announce_super_query("SELECT * FROM bans WHERE '".$ipLong."' >= first AND '".$ipLong."' <= last");
		},
		'announce'
	);
}

function announce_fetch_user_by_passkey($passkey)
{
	$passkey = trim((string) $passkey);
	if ($passkey === '') {
		return array();
	}

	return announce_super_query("SELECT id, slots FROM users WHERE passkey = ".announce_escape($passkey)." LIMIT 1");
}

function announce_fetch_user_stats_by_passkey($passkey)
{
	$passkey = trim((string) $passkey);
	if ($passkey === '') {
		return array();
	}

	return announce_super_query("SELECT id, uploaded, downloaded, class FROM users WHERE passkey = ".announce_escape($passkey)." LIMIT 1");
}

function announce_fetch_torrent($infoHashHex)
{
	$infoHashHex = strtolower(trim((string) $infoHashHex));

	return lt_cache_remember(
		'torrent:'.$infoHashHex,
		400,
		function () use ($infoHashHex) {
			return announce_super_query(
				'SELECT torrents.id, torrents.banned, (trackers.seeders + trackers.leechers) AS numpeers, UNIX_TIMESTAMP(torrents.added) AS ts
				 FROM torrents
				 LEFT JOIN trackers ON torrents.id = trackers.torrent
				 WHERE torrents.infohash = '.announce_escape($infoHashHex).' AND trackers.tracker = "localhost"
				 LIMIT 1'
			);
		},
		'announce'
	);
}

function announce_fetch_peer_rows($torrentId, $fields, $limitSql = '')
{
	$torrentId = (int) $torrentId;
	$fields = trim((string) $fields);
	$limitSql = trim((string) $limitSql);

	return announce_safe_query("SELECT ".$fields." FROM peers WHERE torrent = ".$torrentId." ".$limitSql);
}

function announce_fetch_self_peer($torrentId, $peerId, $fields)
{
	$torrentId = (int) $torrentId;
	$fields = trim((string) $fields);
	$peerId = (string) $peerId;

	return announce_super_query("SELECT ".$fields." FROM peers WHERE torrent = ".$torrentId." AND peer_id = ".announce_escape($peerId)." LIMIT 1");
}

function announce_count_peers_by_passkey($torrentId, $passkey)
{
	$row = announce_super_query("SELECT COUNT(*) AS cnt FROM peers WHERE torrent = ".(int) $torrentId." AND passkey = ".announce_escape($passkey));

	return (int) ($row['cnt'] ?? 0);
}

//Информация о правах класса
function get_priv_info($class) {
	global $db;
	$class = (int)$class;

	return lt_cache_remember(
		'priv_'.$class,
		300,
		function () use ($db, $class) {
			return $db->super_query("SELECT * FROM priv WHERE id=".$class);
		},
		'privileges'
	);
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

	$bannedAgents = array(
		'Opera' => 'Клиент Opera запрещен на нашем трекере.',
		'Mozilla' => 'Браузерные запросы запрещены на нашем трекере.',
		'BinTorrent' => 'Клиент BinTorrent запрещен на нашем трекере.',
		'eXeem' => 'Клиент eXeem запрещен на нашем трекере.',
		'MLDonkey' => 'MLDonkey не является поддерживаемым bittorrent-клиентом.',
		'Ares' => 'Клиент Ares запрещен на нашем трекере.',
		'Red Swoosh' => 'Клиент Red Swoosh запрещен на нашем трекере.',
		'FDM' => 'Клиент FDM запрещен на нашем трекере.',
		'SHAD0W' => 'Клиент SHAD0W запрещен на нашем трекере.',
	);

	foreach ($bannedAgents as $needle => $message) {
		if (strpos($agent, $needle) !== false) {
			err($message);
		}
	}

	if (strpos($agent, 'uTorrent') !== false && strpos($agent, 'B') !== false) {
		err('Бета-версии uTorrent запрещены на нашем трекере, используйте стабильные релизы.');
	}

	$peerIdPrefixes = array(
		'FUTB' => 'Клиент FUTB запрещен на нашем трекере.',
		'-BB' => 'Клиент BitBuddy запрещен на нашем трекере.',
		'-SZ' => 'Клиент Shareaza запрещен на нашем трекере.',
		'-AG' => 'Ваш битторрент-клиент запрещен на нашем трекере.',
		'R34' => 'Клиент BTuga/Revolution-3.4 запрещен на нашем трекере.',
		'-FG' => 'FlashGet запрещен на нашем трекере.',
	);

	foreach ($peerIdPrefixes as $prefix => $message) {
		if (strpos($peer_id, $prefix) === 0) {
			err($message);
		}
	}

	if (preg_match('/^0P3R4H/', $peer_id)) {
		err('Клиент Opera запрещен на нашем трекере.');
	}

	if (strpos($peer_id, 'eX') === 0) {
		err('Клиент eXeem запрещен на нашем трекере.');
	}

	if (strpos($peer_id, ',') === 0) {
		err('Клиент RAZA запрещен на нашем трекере.');
	}

	if (preg_match('/MLDonkey\/([0-9]+).([0-9]+).([0-9]+)*/', $peer_id, $matches)) {
		err('MLDonkey не является битторрент-клиентом.');
	}

	if (preg_match('/ed2k_plugin v([0-9]+\.[0-9]+).*/', $peer_id, $matches)) {
		err('eDonkey не является битторрент-клиентом.');
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
	return announce_escape($value);
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

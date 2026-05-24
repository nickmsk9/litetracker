<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Функции для торрент файла
===================================================================
*/


/**
 * Converts String to Hex
 * @param string $string String to be converted
 * @return string Converted string
 */
function hex($string){
	$hex='';
	for ($i=0; $i < strlen($string); $i++){
		$hex .= dechex(ord($string[$i]));
	}
	return $hex;
}

/**
 * Makes magnet link e.g. instead of downloading .torrent
 * @param string $info_hash SHA1 infohash
 * @param string $filename Name to display
 * @param string|array $trackers Announce url or array of announce-urls of trackers
 * @return string
 */
function make_magnet($info_hash,$filename,$trackers){
	if (is_array($trackers)) {
		$trackers = array_values(array_filter($trackers, 'strlen'));
		$trackers = implode('&tr=', array_map('urlencode', $trackers));
	} else {
		$trackers = urlencode((string) $trackers);
	}

	return 'magnet:?xt=urn:btih:'.$info_hash.'&dn='.urlencode($filename).($trackers !== '' ? '&tr='.$trackers : '');

}



/**
 * Makes magnet link to a DChub
 * @param string $tiger_hash TIGER infohash
 * @param string $filename Name to display
 * @param int $filesize Filesize in bytes
 * @param string|array $trackers Announce url or array of announce-urls of trackers
 * @return string
 */
function make_dc_magnet($tiger_hash,$filename,$filesize,$hubs){
	if (is_array($hubs)) $hubs = implode('&xs=',array_map('urlencode',$hubs)); else $hubs = urlencode($hubs);
	return 'magnet:?xt=urn:tree:tiger:'.$tiger_hash.'&xl='.$filesize.'&dn='.urlencode($filename).'&xs='.$hubs;

}


/**
 * Gets available retrackers (DChubs, and other stuff) by user's ip
 * @param boolean $all Get All Retrackers, ignoring subnet mask default false
 * @param string $table Table used to get stuff
 * @return array Array of retrackers or empty array if no retrackers present
 */
function get_retrackers($all = false, $table = 'retrackers') {
	// global $IPCHECK;
	global $db;
	$ip = getip();
	$rtarray = array();
	$return = array();
	$retrackers = array();
	$row = $db->query("SELECT announce_url, mask FROM $table ORDER BY sort ASC");
	while ($res = $db->get_row() ) { $rtarray[] = $res; if ($all) $return[] = $res['announce_url']; }

	if (!$rtarray) return array();

	if ($all) return $return;

	foreach ($rtarray as $retracker) {

		if (!empty($retracker['mask'])) {
			$RTCHECK = new IPAddressSubnetSniffer(array($retracker['mask']));

			if ($RTCHECK->ip_is_allowed($ip)) $retrackers[] = $retracker['announce_url'];
		}
		else $retrackers[] = $retracker['announce_url'];
		// $retrackers[] = $retracker['announce_url'];
	}

	if ($retrackers) return $retrackers; else return array();
}

/**
 * Gets dictionary value
 * @param array $d torrent dictionary
 * @param string $k dictionary key
 * @param string $t value type
 * @return void|multiple Return value
 * @see bdec_dict()
 */
function dict_get($d, $k, $t) {
	if ($d["type"] != "dictionary")
	err("not a dictionary");
	$dd = $d["value"];
	if (!isset($dd[$k]))
	return;
	$v = $dd[$k];
	if ($v["type"] != $t)
	err("invalid dictionary entry type");
	return $v["value"];
}

/**
 * Check that dicitionary is valid
 * @param array $d Dictionary
 * @param string $s Undocumented
 * @return Ambigous <multitype:, unknown> Undocumented
 */
function dict_check($d, $s) {
	if ($d["type"] != "dictionary")
	err("not a dictionary");
	$a = explode(":", $s);
	$dd = $d["value"];
	$ret = array();
	foreach ($a as $k) {
		unset($t);
		if (preg_match('/^(.*)\((.*)\)$/', $k, $m)) {
			$k = $m[1];
			$t = $m[2];
		}
		if (!isset($dd[$k]))
		err("dictionary is missing key(s)");
		if (isset($t)) {
			if ($dd[$k]["type"] != $t)
			err("invalid entry in dictionary");
			$ret[] = $dd[$k]["value"];
		}
		else
		$ret[] = $dd[$k];
	}
	return $ret;
}

/**
 * Binary encodes an value
 * @param mixed $obj Value to be encoded
 * @return string Encoded value
 * @see benc_str()
 * @see benc_int()
 * @see benc_list()
 * @see benc_dict()
 */
function benc($obj) {
	if (!is_array($obj) || !isset($obj["type"]) || !isset($obj["value"]))
	return;
	$c = $obj["value"];
	switch ($obj["type"]) {
		case "string":
			return benc_str($c);
		case "integer":
			return benc_int($c);
		case "list":
			return benc_list($c);
		case "dictionary":
			return benc_dict($c);
		default:
			return;
	}
}
/**
 * Binary encodes a string
 * @param string $s String to be encoded
 * @return string Encoded string
 */
function benc_str($s) {
	return strlen($s) . ":$s";
}
/**
 * Binary encodes an integer
 * @param int $i Integer to be encoded
 * @return string Encoded Integer
 */
function benc_int($i) {
	return "i" . $i . "e";
}
/**
 * Binary encodes a list
 * @param array $a List to be encoded
 * @return string Encoded list
 */
function benc_list($a) {
	$s = "l";
	foreach ($a as $e) {
		$s .= benc($e);
	}
	$s .= "e";
	return $s;
}

/**
 * Binary encodes a dictionary
 * @param array $d Dictionary to be encoded
 * @return string Encoded dictionary
 * @see benc() benc_str()
 */
function benc_dict($d) {
	$s = "d";
	$keys = array_keys($d);
	sort($keys);
	foreach ($keys as $k) {
		$v = $d[$k];
		$s .= benc_str($k);
		$s .= benc($v);
	}
	$s .= "e";
	return $s;
}
/**
 * Binary decodes a torrent file
 * @param string $f File path to be decoded
 * @return array Decoded file
 * @see bdec()
 */
function bdec_file($f, $ms) {
	$fp = fopen($f, "rb");

	if (!$fp)
		return;
	$e = fread($fp, $ms);
	fclose($fp);
	return bdec($e);
}

function lt_torrent_decode_file($path) {
	$path = (string) $path;
	if ($path === '' || !is_file($path) || !is_readable($path)) {
		return;
	}

	$size = (int) filesize($path);
	$readBytes = max(1024 * 1024, $size + 1);

	return bdec_file($path, $readBytes);
}
/**
 * Binary decodes a Value
 * @param string $s Value to be decoded
 * @return array Decoded value
 */
function bdec($s) {
	if (preg_match('/^(\d+):/', $s, $m)) {
		$l = $m[1];
		$pl = strlen($l) + 1;
		$v = substr($s, $pl, $l);
		$ss = substr($s, 0, $pl + $l);
		if (strlen($v) != $l) return;
		return array('type' => "string", 'value' => $v, 'strlen' => strlen($ss), 'string' => $ss);
	}
	if (preg_match('/^i(\d+)e/', $s, $m)) {
		$v = $m[1];
		$ss = "i" . $v . "e";
		if ($v === "-0")
		return;
		if ($v[0] == "0" && strlen($v) != 1)
		return;
		return array('type' => "integer", 'value' => $v, 'strlen' => strlen($ss), 'string' => $ss);
	}
	switch ($s[0]) {
		case "l":
			return bdec_list($s);
		case "d":
			return bdec_dict($s);
		default:
			return;
	}
}
/**
 * Binary decodes a list
 * @param string $s List to be decoded
 * @return array Decoded list
 */
function bdec_list($s) {
	if ($s[0] != "l")
	return;
	$sl = strlen($s);
	$i = 1;
	$v = array();
	$ss = "l";
	for (;;) {
		if ($i >= $sl)
		return;
		if ($s[$i] == "e")
		break;
		$ret = bdec(substr($s, $i));
		if (!isset($ret) || !is_array($ret))
		return;
		$v[] = $ret;
		$i += $ret["strlen"];
		$ss .= $ret["string"];
	}
	$ss .= "e";
	return array('type' => "list", 'value' => $v, 'strlen' => strlen($ss), 'string' => $ss);
}
/**
 * Binary decodes a dictionary
 * @param string $s Dictionary to be decoded
 * @return array Decoded dictionary
 */
function bdec_dict($s) {
	if ($s[0] != "d")
	return;
	$sl = strlen($s);
	$i = 1;
	$v = array();
	$ss = "d";
	for (;;) {
		if ($i >= $sl)
		return;
		if ($s[$i] == "e")
		break;
		$ret = bdec(substr($s, $i));
		if (!isset($ret) || !is_array($ret) || $ret["type"] != "string")
		return;
		$k = $ret["value"];
		$i += $ret["strlen"];
		$ss .= $ret["string"];
		if ($i >= $sl)
		return;
		$ret = bdec(substr($s, $i));
		if (!isset($ret) || !is_array($ret))
		return;
		$v[$k] = $ret;
		$i += $ret["strlen"];
		$ss .= $ret["string"];
	}
	$ss .= "e";
	return array('type' => "dictionary", 'value' => $v, 'strlen' => strlen($ss), 'string' => $ss);
}

/**
 * Gets announce urls from DECODED torrent dicrionary
 * @param array $dict Decoded torrent dictionary
 * @return array|boolean Array of urls on success, false on fail
 */
function lt_tracker_url_key($url, $includeQuery = true) {
	$url = trim((string) $url);
	if ($url === '') {
		return '';
	}

	$parts = parse_url($url);
	if (!$parts || empty($parts['host'])) {
		return rtrim($url, '/');
	}

	$scheme = strtolower((string) ($parts['scheme'] ?? ''));
	$host = strtolower((string) $parts['host']);
	$port = (isset($parts['port']) ? ':'.(int) $parts['port'] : '');
	$path = (string) ($parts['path'] ?? '');
	if ($path === '') {
		$path = '/';
	}
	$path = rtrim($path, '/');
	if ($path === '') {
		$path = '/';
	}
	$query = ($includeQuery && isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '');

	return $scheme.'://'.$host.$port.$path.$query;
}

function lt_tracker_unique_urls($trackers) {
	$result = array();
	$seen = array();

	foreach ((array) $trackers as $tracker) {
		$tracker = trim((string) $tracker);
		if ($tracker === '') {
			continue;
		}

		$key = lt_tracker_url_key($tracker, true);
		if ($key === '' || isset($seen[$key])) {
			continue;
		}

		$seen[$key] = true;
		$result[] = $tracker;
	}

	return $result;
}

function lt_tracker_is_site_url($url) {
	global $config;

	$url = trim((string) $url);
	if ($url === '' || $url === 'localhost') {
		return true;
	}

	$key = lt_tracker_url_key($url, false);
	if ($key === '') {
		return false;
	}

	$siteUrls = array(
		(string) ($config['announce_url'] ?? ''),
		(string) ($config['local_retracker_url'] ?? ''),
	);

	foreach ($siteUrls as $siteUrl) {
		$siteKey = lt_tracker_url_key($siteUrl, false);
		if ($siteKey !== '' && $siteKey === $key) {
			return true;
		}
	}

	return false;
}

function lt_torrent_external_trackers($trackers) {
	$trackers = lt_tracker_unique_urls($trackers);
	$result = array();
	$retrackers = array();

	if (function_exists('get_retrackers')) {
		$retrackers = get_retrackers(true);
	}

	$retrackerKeys = array();
	foreach ((array) $retrackers as $retracker) {
		$key = lt_tracker_url_key($retracker, false);
		if ($key !== '') {
			$retrackerKeys[$key] = true;
		}
	}

	foreach ($trackers as $tracker) {
		if (lt_tracker_is_site_url($tracker)) {
			continue;
		}

		$parts = parse_url($tracker);
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));
		if (!in_array($scheme, array('http', 'https', 'udp'), true)) {
			continue;
		}

		$key = lt_tracker_url_key($tracker, false);
		if ($key !== '' && isset($retrackerKeys[$key])) {
			continue;
		}

		$result[] = $tracker;
	}

	return lt_tracker_unique_urls($result);
}

function lt_torrent_site_announce_urls($user = null, $includeLocalRetracker = false) {
	global $config;

	$announceBaseUrl = trim((string) ($config['announce_url'] ?? ''));
	if ($announceBaseUrl === '') {
		$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
		$host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
		$announceBaseUrl = $scheme.'://'.$host.'/announce.php';
	}

	$urls = array();
	if (is_array($user) && !empty($user['passkey'])) {
		$urls[] = $announceBaseUrl.(strpos($announceBaseUrl, '?') === false ? '?' : '&').'passkey='.$user['passkey'];
	} else {
		$urls[] = $announceBaseUrl;
	}

	if ($includeLocalRetracker) {
		$localRetrackerUrl = trim((string) ($config['local_retracker_url'] ?? ''));
		if ($localRetrackerUrl !== '' && lt_tracker_is_site_url($localRetrackerUrl)) {
			$urls[] = $localRetrackerUrl;
		}
	}

	return lt_tracker_unique_urls($urls);
}

function lt_torrent_store_trackers($torrentId, $trackers) {
	global $db;

	$torrentId = (int) $torrentId;
	if ($torrentId <= 0) {
		return array();
	}

	$externalTrackers = lt_torrent_external_trackers($trackers);
	$stored = array_merge(array('localhost'), $externalTrackers);

	foreach ($stored as $trackerUrl) {
		$db->query("INSERT INTO trackers (torrent, tracker, state) VALUES (".$torrentId.", '".$db->safesql($trackerUrl)."', '')");
	}

	return $stored;
}

function lt_torrent_site_base_url() {
	global $config;

	$siteBaseUrl = rtrim((string) ($config['site_url'] ?? ''), '/');
	if ($siteBaseUrl !== '') {
		return $siteBaseUrl;
	}

	$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
	$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

	return ($host !== '' ? $scheme.'://'.$host : '');
}

function lt_torrent_details_url($torrentId) {
	$torrentId = (int) $torrentId;
	if ($torrentId <= 0) {
		return '';
	}

	$siteBaseUrl = lt_torrent_site_base_url();

	return ($siteBaseUrl !== '' ? $siteBaseUrl : '').'/details.php?id='.$torrentId;
}

function lt_torrent_rewrite_file_announces($path, $announceUrls = null, $torrentId = null) {
	$dict = lt_torrent_decode_file($path);
	if (!is_array($dict)) {
		return false;
	}

	if ($announceUrls === null) {
		$announceUrls = lt_torrent_site_announce_urls(null, false);
	}

	$dict = put_announce_urls($dict, (array) $announceUrls);
	if (!is_array($dict)) {
		return false;
	}

	$detailsUrl = lt_torrent_details_url((int) $torrentId);
	if ($detailsUrl !== '') {
		$dict['value']['comment'] = bdec(benc_str($detailsUrl));
	}

	return (file_put_contents($path, benc($dict), LOCK_EX) !== false);
}

function get_announce_urls($dict){
	$announce = (isset($dict['value']['announce']) ? $dict['value']['announce'] : null);
	$announceList = (isset($dict['value']['announce-list']) ? $dict['value']['announce-list'] : null);
	$anarray = array();

	if (!empty($announce['value'])) {
		$anarray[] = $announce['value'];
	}

	if (!empty($announceList)) {
		if (empty($announceList['value'])) return ($anarray ? lt_tracker_unique_urls($anarray) : false);
		$retrackers = get_retrackers(true);
		foreach ($announceList['value'] as $urls) {
			if (empty($urls['value']) || !is_array($urls['value'])) {
				continue;
			}
			foreach ($urls['value'] as $announceUrl) {
				if (empty($announceUrl['value'])) {
					continue;
				}
				if (!in_array($announceUrl['value'],$retrackers))
				$anarray[] = $announceUrl['value'];
			}
		}
	}

	$anarray = lt_tracker_unique_urls($anarray);

	return ($anarray ? $anarray : false);
}

/**
 * Puts announce urls into DECODED dictionary. DICT is global.
 * @param array $dict Decoded dictionary to be processed
 * @param array $anarray Array of announce urls. First element good to be a local announce-url
 * @return void Uses global $dict
 */
function put_announce_urls(&$dict,$anarray){
	unset($dict['value']['announce']);
	unset($dict['value']['announce-list']);
	$anarray = lt_tracker_unique_urls((array) $anarray);
	if (!$anarray) {
		return $dict;
	}

	$dict['value']['announce'] = bdec(benc_str($anarray[0]));

	$announces = array();
	foreach ($anarray as $announce) {
		$list = array(bdec(benc_str($announce)));
		$announces[] = array('type' => 'list', 'value' => $list, 'strlen' => strlen(benc_list($list)), 'string' => benc_list($list));
	}

	$dict['value']['announce-list']['type'] = 'list';
	$dict['value']['announce-list']['value'] = $announces;
	$dict['value']['announce-list']['string'] = benc_list($announces);
	$dict['value']['announce-list']['strlen'] = strlen($dict['value']['announce-list']['string']);

	return $dict;
}

/**
 * Gets port from adress
 * @param string $urlInfo URL to be parsed
 * @return int Port
 */
function getUrlPort($urlInfo) {
	if( isset($urlInfo['port']) ) {
		$port = $urlInfo['port'];
	} else { // no port specified; get default port
		if (isset($urlInfo['scheme'])) {
			switch($urlInfo['scheme']) {
				case 'http':
					$port = 80; // default for http
					break;
				case 'https':
					$port = 443; // default for https
					break;
				default:
					$port = 0; // error; unsupported scheme
					break;
			}
		} else {
			$port = 80; // error; unknown scheme, using default 80 port
		}
	}
	return $port;
}

/**
 * Checks that tracker returns failed event.
 * @param string $result Bencoded result to be parsed
 * @return string String to be used in remote tracker statistics
 */
function check_fail($result) {
	$reason = '';
	if (is_array($result) && isset($result['value']['failure reason']['value'])) {
		$reason = (string) $result['value']['failure reason']['value'];
	}

	return ($reason !== '' ? 'failed:'.$reason.'_' : 'ok_');
}

/**
 * Gets amout of remote tracker peers. May be recursivity.
 * @param string $url Announce url of request
 * @param string $info_hash Info-hash of torrent to be parsed
 * @param string $method Method of gathering amount of peers. May be scrape or announce. Default 'scrape'. If scrape fails, recursivety swithes to announce and executes again.
 * @return array Result array ('tracker','seeders','leechers','state');
 */
function get_remote_peers($url, $info_hash, $method = 'scrape') {
	global $CRON, $config;
	$emptyResult = array('tracker' => (string) $url, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:unknown_'.$method);
	$urlorig = (string) $url;
	$info_hash = preg_replace('~[^a-f0-9]~i', '', (string) $info_hash);
	if (strlen($info_hash) !== 40) {
		$emptyResult['state'] = 'failed:invalid_infohash_'.$method;
		return $emptyResult;
	}

	$maxTimeout = max(1, (int) ($config['remote_tracker_timeout'] ?? 8));
	$timeout = (int) ($CRON['multi_timeout'] ?? 0);
	if ($timeout <= 0 || $timeout > $maxTimeout) {
		$timeout = $maxTimeout;
	}

	if ($method == "announce") {
		$get_params = array(
    			"info_hash" => pack("H*", $info_hash),
    			"peer_id" => "-UT1820-5dmPcUOYGnrx",
    			"port" => rand(10000, 65535),
    			"uploaded" => 0,
				"no_peer_id" => 1,
    			"downloaded" => 0,
				"compact" => 1,
    			"left" => 1,
    			"numwant" => 9999
		);
	} else {
		$urlInfoForScrape = parse_url($url);
		if (!empty($urlInfoForScrape['path'])) {
			$urlInfoForScrape['path'] = preg_replace('~announce~i', 'scrape', $urlInfoForScrape['path'], 1);
			$url = (isset($urlInfoForScrape['scheme']) ? $urlInfoForScrape['scheme'].'://' : '')
				. (isset($urlInfoForScrape['user']) ? $urlInfoForScrape['user'].(isset($urlInfoForScrape['pass']) ? ':'.$urlInfoForScrape['pass'] : '').'@' : '')
				. ($urlInfoForScrape['host'] ?? '')
				. (isset($urlInfoForScrape['port']) ? ':'.$urlInfoForScrape['port'] : '')
				. ($urlInfoForScrape['path'] ?? '')
				. (isset($urlInfoForScrape['query']) ? '?'.$urlInfoForScrape['query'] : '');
		}
		$get_params = array(
    			"info_hash" => pack("H*", $info_hash)
		);
	}


	$urlInfo = parse_url($url);
	$scheme = strtolower((string) ($urlInfo['scheme'] ?? 'http'));
	if (!in_array($scheme, array('http', 'https'), true)) {
		return array('tracker' => (string) ($urlInfo['host'] ?? $url), 'seeders' => 0, 'leechers' => 0, 'state' => 'skipped:unsupported_scheme_'.$scheme);
	}
	if ($scheme !== 'https') {
		$scheme = 'http';
	}
	$http_host = (string) ($urlInfo['host'] ?? '');
	if ($http_host === '') {
		return array('tracker' => $url, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:no_host_detected_'.$method);
	}
	$http_port = getUrlPort($urlInfo);

	if ($http_port === 0)
	return array('tracker' => $http_host, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:no_port_detected_'.$method);
	else
	$http_port = ':' . $http_port;

	$http_path = ($urlInfo['path'] ?? '/');
	$get_request_params = explode('&', (string) ($urlInfo['query'] ?? ''));
	$new_get_request_params = array();

	foreach (array_filter($get_request_params) as $array_value) {
		$parts = explode('=', $array_value, 2);
		$key = (string) ($parts[0] ?? '');
		$value = (string) ($parts[1] ?? '');
		if ($key === '') {
			continue;
		}
		$new_get_request_params[$key] = $value;
	}

	// Params gathering complete

	// Creating params
	$http_params = http_build_query(array_merge($new_get_request_params, $get_params), '', '&', PHP_QUERY_RFC3986);

	$opts = array('http' =>
	array(
        'method' => 'GET',
	    'header' => 'User-Agent: qBittorrent/5.0.0',
    	'timeout' => $timeout
	//'Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
	)
	);

	if ($scheme === 'https') {
		$opts['ssl'] = array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true,
		);
	}

	$context = stream_context_create($opts);
	$result = @file_get_contents($scheme.'://'.$http_host.$http_port.$http_path.($http_params ? '?'.$http_params : ''), false, $context);
	// $result = true;
	if (!$result)
	{
		if ($method=='scrape')
		return get_remote_peers($urlorig, $info_hash, "announce"); else
		return array('tracker' => $http_host, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:no_benc_result_or_timeout_'.$method);

	}


	//var_dump($method);
	$resulttemp=$result;
	try {
		$result = bdec($result);
	} catch (Throwable $e) {
		return array('tracker' => $http_host, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:unable_to_bdec_'.$method);
	}

	if (!is_array($result)) return array('tracker' => $http_host, 'seeders' => 0, 'leechers' => 0, 'state' => 'failed:unable_to_bdec_'.$method);
	unset($resulttemp);
	//    print('<pre>'); var_dump($result);
	if ($method == 'scrape') {

		if (!empty($result['value']['files']['value']) && is_array($result['value']['files']['value'])) {
			$peersarray = array_shift($result['value']['files']['value']);
			return array(
				'tracker' => $http_host,
				'seeders' => max(0, (int) ($peersarray['value']['complete']['value'] ?? 0)),
				'leechers' => max(0, (int) ($peersarray['value']['incomplete']['value'] ?? 0)),
				'state' => check_fail($result).$method
			);
		} else return get_remote_peers($urlorig, $info_hash, "announce");
	}

	if($method == 'announce') {
		$peersValue = $result['value']['peers']['value'] ?? '';
		$peerCount = (is_array($peersValue) ? count($peersValue) : (int) floor(strlen((string) $peersValue) / 6));
		return array(
			'tracker' => $http_host,
			'seeders' => max(0, (int) ($result['value']['complete']['value'] ?? $peerCount)),
			'leechers' => max(0, (int) ($result['value']['incomplete']['value'] ?? 0)),
			'state'=> check_fail($result).$method
		);
	}

	return $emptyResult;
}

?>

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

require_once __DIR__ . '/functions.common.php';


function announce_fail_message()
{
	return 'Не удалось обработать запрос трекера.';
}

function announce_get_string_param($name, $get = null)
{
	$get = (is_array($get) ? $get : $_GET);

	return (string) ($get[$name] ?? '');
}

function announce_get_int_param($name, $get = null)
{
	$get = (is_array($get) ? $get : $_GET);

	return (int) ($get[$name] ?? 0);
}

function announce_is_valid_info_hash($value)
{
	return strlen((string) $value) === 20;
}

function announce_is_valid_peer_id($value)
{
	return strlen((string) $value) === 20;
}

function announce_ensure_string_length($value, $length, $label)
{
	$value = (string) $value;
	$length = (int) $length;

	if (strlen($value) !== $length) {
		err(sprintf($GLOBALS['language']['announce_2'], $label, strlen($value), 'REDACTED'));
	}

	return $value;
}

function announce_normalize_event($event)
{
	return trim((string) $event);
}

function announce_normalize_numwant($get = null, $default = 50)
{
	$get = (is_array($get) ? $get : $_GET);
	foreach (array('num want', 'numwant', 'num_want') as $key) {
		if (isset($get[$key])) {
			return max(1, min(200, (int) $get[$key]));
		}
	}

	return max(1, min(200, (int) $default));
}

function announce_detect_client_flags($server = null)
{
	if (is_array($server)) {
		$headers = array();
		foreach ($server as $name => $value) {
			if (substr((string) $name, 0, 5) === 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $name, 5)))))] = $value;
			}
		}
	} else {
		$server = $_SERVER;
		$headers = (function_exists('getallheaders') ? getallheaders() : emu_getallheaders());
	}

	return array(
		'agent' => (string) ($server['HTTP_USER_AGENT'] ?? ''),
		'headers' => $headers,
		'has_browser_headers' => (
			isset($headers['Cookie'])
			|| isset($headers['Accept-Language'])
			|| isset($headers['Accept-Charset'])
		),
	);
}

function announce_parse_request($get = null, $server = null)
{
	$get = (is_array($get) ? $get : $_GET);
	$clientFlags = announce_detect_client_flags($server);
	$request = array(
		'info_hash' => announce_ensure_string_length(announce_get_string_param('info_hash', $get), 20, 'info_hash'),
		'peer_id' => announce_ensure_string_length(announce_get_string_param('peer_id', $get), 20, 'peer_id'),
		'event' => announce_normalize_event(announce_get_string_param('event', $get)),
		'ip' => announce_get_string_param('ip', $get),
		'localip' => announce_get_string_param('localip', $get),
		'port' => announce_get_int_param('port', $get),
		'downloaded' => announce_get_int_param('downloaded', $get),
		'uploaded' => announce_get_int_param('uploaded', $get),
		'left' => announce_get_int_param('left', $get),
		'passkey' => trim((string) ($get['passkey'] ?? '')),
		'compact' => ((int) ($get['compact'] ?? 0) === 1),
		'no_peer_id' => ((int) ($get['no_peer_id'] ?? 0) === 1),
		'numwant' => announce_normalize_numwant($get, 50),
		'announce_debug' => ((int) ($get['announce_debug'] ?? 0) === 1),
		'client_flags' => $clientFlags,
		'agent' => $clientFlags['agent'],
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
	return announce_normalize_numwant($_GET, $default);
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

	if (is_int($value) || is_float($value)) {
		return (string) $value;
	}

	return "'".$db->safesql((string) $value)."'";
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

	return announce_super_query("SELECT id, uploaded, downloaded, class FROM users WHERE passkey = ".announce_escape($passkey)." LIMIT 1");
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
				'SELECT torrents.id, torrents.banned, torrents.size, (trackers.seeders + trackers.leechers) AS numpeers, UNIX_TIMESTAMP(torrents.added) AS ts
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

function announce_load_ban_context($ip)
{
	$ip = (string) $ip;
	$ipBan = ip2long_db($ip);

	return array(
		'ip' => $ip,
		'ip_ban' => $ipBan,
		'ban' => announce_fetch_ip_ban($ipBan),
	);
}

function announce_load_user_context($passkey, $guest)
{
	$passkey = trim((string) $passkey);
	$guest = (bool) $guest;

	return array(
		'guest' => $guest,
		'passkey' => $passkey,
		'user' => ($guest ? array() : announce_fetch_user_by_passkey($passkey)),
	);
}

function announce_load_tracker_context(array $torrent)
{
	return array(
		'tracker' => 'localhost',
		'numpeers' => (int) ($torrent['numpeers'] ?? 0),
	);
}

function announce_load_torrent_context($infoHash)
{
	$infoHashHex = bin2hex((string) $infoHash);
	$torrent = announce_fetch_torrent($infoHashHex);
	if (!is_array($torrent)) {
		$torrent = array();
	}
	$tracker = announce_load_tracker_context($torrent);

	return array(
		'info_hash_hex' => $infoHashHex,
		'torrent' => $torrent,
		'tracker' => $tracker,
		'torrentid' => (int) ($torrent['id'] ?? 0),
		'torrent_size' => (int) ($torrent['size'] ?? 0),
		'numpeers' => (int) ($tracker['numpeers'] ?? 0),
	);
}

function announce_peer_fields()
{
	return "seeder, peer_id, ip, port, uploaded, downloaded, userid, UNIX_TIMESTAMP(last_action) AS prevts, UNIX_TIMESTAMP(NOW()) AS nowts, last_action";
}

function announce_load_peer_pool($torrentId, $peerId, $numwant, $numpeers, $fields = null)
{
	global $db;

	$torrentId = (int) $torrentId;
	$peerId = (string) $peerId;
	$numwant = (int) $numwant;
	$numpeers = (int) $numpeers;
	$fields = ($fields === null ? announce_peer_fields() : (string) $fields);
	$peerPoolLimit = max(100, min(1000, $numwant * 4));
	if ($numpeers > 0) {
		$peerPoolLimit = min($peerPoolLimit, $numpeers);
	}

	$peersSql = announce_fetch_peer_rows($torrentId, $fields, 'ORDER BY last_action DESC LIMIT '.$peerPoolLimit);
	$self = null;
	$userid = 0;
	$peerCandidates = array();

	while ($row = $db->get_row($peersSql)) {
		if ((string) ($row['peer_id'] ?? '') === $peerId) {
			$userid = (int) ($row['userid'] ?? 0);
			$self = $row;
			continue;
		}

		$peerCandidates[] = $row;
	}

	if ($peerCandidates) {
		shuffle($peerCandidates);
		if (count($peerCandidates) > $numwant) {
			$peerCandidates = array_slice($peerCandidates, 0, $numwant);
		}
	}

	if ($self === null) {
		$row = announce_fetch_self_peer($torrentId, $peerId, $fields);
		if (!empty($row['peer_id'])) {
			$userid = (int) ($row['userid'] ?? 0);
			$self = $row;
		}
	}

	return array(
		'fields' => $fields,
		'pool_limit' => $peerPoolLimit,
		'candidates' => $peerCandidates,
		'self' => $self,
		'userid' => $userid,
		'returned_peer_count' => count($peerCandidates),
	);
}

function announce_load_peer_context($torrentId, $peerId, $numwant, $numpeers)
{
	return announce_load_peer_pool($torrentId, $peerId, $numwant, $numpeers, announce_peer_fields());
}

function announce_debug_enabled(array $request, $ip)
{
	if (empty($request['announce_debug'])) {
		return false;
	}

	return in_array((string) $ip, array('127.0.0.1', '::1', '0.0.0.0'), true);
}

function announce_debug_log(array $request, $startTime, $peerCount, $ip)
{
	global $db;

	if (!announce_debug_enabled($request, $ip)) {
		return;
	}

	$durationMs = round((microtime(true) - (float) $startTime) * 1000, 2);
	error_log(
		'announce_debug event=' . announce_normalize_event($request['event'] ?? '') .
		' queries=' . (int) ($db->query_num ?? 0) .
		' duration_ms=' . $durationMs .
		' peers=' . (int) $peerCount
	);
}

function announce_prepare_authenticated_write_context($guest, $self, $torrentId, $passkey, $seeder, array $user, $uploaded, $downloaded, $left, $userid)
{
	global $config, $language;

	if ($guest) {
		return (int) $userid;
	}

	$torrentId = (int) $torrentId;
	$passkey = (string) $passkey;
	$seeder = (string) $seeder;

	if ($self === null) {
		$valid = announce_count_peers_by_passkey($torrentId, $passkey);
		if ($valid >= 1 && $seeder === '0') {
			announce_safe_query("DELETE FROM peers WHERE torrent=".$torrentId." AND passkey=".announce_escape($passkey));
			announce_safe_query("UPDATE trackers SET leechers=(leechers-".$valid.") WHERE torrent=".$torrentId." AND tracker='localhost'");
			err($language['announce_9']);
		}

		if ($valid >= 3 && $seeder === '1') {
			announce_safe_query("DELETE FROM peers WHERE torrent=".$torrentId." AND passkey=".announce_escape($passkey));
			announce_safe_query("UPDATE trackers SET seeders=(seeders-".$valid.") WHERE torrent=".$torrentId." AND tracker='localhost'");
			err($language['announce_9']);
		}

		$az = $user;
		if (empty($az['id'])) {
			err(sprintf($language['announce_10'], (string) ($config['sitename'] ?? 'LiteTracker')));
		}

		return (int) $az['id'];
	}

	if (!announce_validate_stats($uploaded, $downloaded, $left)) {
		err('Invalid statistics (possible tracker abuse).');
	}

	$upthis = max(0, (int) $uploaded - (int) $self['uploaded']);
	$downthis = max(0, (int) $downloaded - (int) $self['downloaded']);

	if ($upthis > 0 || $downthis > 0) {
		announce_safe_query('UPDATE users SET uploaded = uploaded + '.$upthis.', downloaded = downloaded + '.$downthis.' WHERE id='.(int) $userid);
	}

	return (int) $userid;
}

function announce_probe_connectable($ip, $port)
{
	global $config;

	$connectable = '1';
	if (!empty($config['announce_connectivity_probe'])) {
		$probeCacheKey = 'announce:connectable:'.md5($ip.':'.$port);
		$cachedConnectable = lt_cache_get($probeCacheKey, 'announce');
		if ($cachedConnectable === null) {
			$sockres = fsockopen($ip, $port, $errno, $errstr, 2);
			$cachedConnectable = ($sockres ? '1' : '0');
			if ($sockres) {
				fclose($sockres);
			}
			lt_cache_set($probeCacheKey, (string) $cachedConnectable, 30 * 60, 'announce');
		}
		$connectable = (string) $cachedConnectable;
	}

	return $connectable;
}

function announce_process_event_write_path($event, $self, $userid, $torrentId, $peerId, $uploaded, $downloaded, $left, $seeder, $port, $ip, $agent, $passkey)
{
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;
	$uploaded = (int) $uploaded;
	$downloaded = (int) $downloaded;
	$left = (int) $left;
	$port = (int) $port;
	$seeder = (string) $seeder;
	$dt = announce_escape(date('Y-m-d H:i:s', time()));
	$updateset = array();
	$snatch_updateset = array();
	$trupdateset = array();

	if ($event === 'stopped') {
		if ($self !== null) {
			announce_safe_query('DELETE FROM peers WHERE torrent = '.$torrentId.' AND peer_id = '.announce_escape($peerId));
			if (announce_rows_affected()) {
				if (!empty($self['seeder'])) {
					$trupdateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
				} else {
					$trupdateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
				}
			}
		}
	} else {
		if ($event === 'completed' && $left === 0) {
			$can_count_completed = true;
			if ($userid > 0) {
				$snatched_state = announce_super_query('SELECT finished FROM snatched WHERE torrent = '.$torrentId.' AND userid = '.(int) $userid.' LIMIT 1');
				$can_count_completed = empty($snatched_state['finished']);
			} elseif ($self !== null && !empty($self['seeder'])) {
				$can_count_completed = false;
			}

			if ($can_count_completed) {
				$snatch_updateset[] = "finished = 1";
				$snatch_updateset[] = "completedat = ".time();
				$updateset[] = 'completed = completed + 1';
			}
		} elseif ($event === 'completed' && $left !== 0) {
			err('Invalid completed event (torrent not fully downloaded).');
		}

		if ($self !== null) {
			$downloaded2 = max(0, $downloaded - (int) $self['downloaded']);
			$uploaded2 = max(0, $uploaded - (int) $self['uploaded']);

			if ($downloaded2 > 0 || $uploaded2 > 0) {
				$snatch_updateset[] = "uploaded = uploaded + ".$uploaded2;
				$snatch_updateset[] = "downloaded = downloaded + ".$downloaded2;
			}

			announce_safe_query(
				"UPDATE peers
				 SET uploaded = ".$uploaded.",
				     downloaded = ".$downloaded.",
				     uploadoffset = ".$uploaded2.",
				     downloadoffset = ".$downloaded2.",
				     to_go = ".$left.",
				     last_action = NOW(),
				     seeder = '".$seeder."'"
				. ($seeder === "1" && (string) ($self["seeder"] ?? '') !== $seeder ? ", finishedat = ".time() : "")
				. " WHERE torrent = ".$torrentId." AND peer_id = ".announce_escape($peerId)
			);

			if (announce_rows_affected() && (string) ($self['seeder'] ?? '') !== $seeder) {
				if ($seeder === '1') {
					$trupdateset[] = 'seeders = seeders + 1';
					$trupdateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
				} else {
					$trupdateset[] = 'leechers = leechers + 1';
					$trupdateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
				}
			}
		} else {
			if (portblacklisted($port)) {
				err('Port '.$port.' is blacklisted.');
			}

			$connectable = announce_probe_connectable($ip, $port);
			$ret = announce_safe_query(
				"INSERT INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, prev_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey)
				 VALUES ('".$connectable."', ".$torrentId.", ".announce_escape($peerId).", ".announce_escape($ip).", ".$port.", ".$uploaded.", ".$downloaded.", ".$left.", NOW(), NOW(), NOW(), '".$seeder."', '".$userid."', ".announce_escape($agent).", ".$uploaded.", ".$downloaded.", ".announce_escape($passkey).")"
			);

			if ($ret) {
				if ($seeder === '1') {
					$trupdateset[] = 'seeders = seeders + 1';
				} else {
					$trupdateset[] = 'leechers = leechers + 1';
				}

				if ($userid > 0) {
					$ts_now = (int) time();
					announce_safe_query(
						"INSERT INTO snatched (userid, torrent, uploaded, downloaded, startedat, completedat, finished)"
						." VALUES (".$userid.", ".$torrentId.", 0, 0, ".$ts_now.", 0, 0)"
						." ON DUPLICATE KEY UPDATE startedat = IF(startedat = 0, ".$ts_now.", startedat)"
					);
				}
			}
		}
	}

	if ($seeder === '1') {
		$updateset[] = 'last_action = '.$dt;
	}

	return array(
		'trupdateset' => $trupdateset,
		'updateset' => $updateset,
		'snatch_updateset' => $snatch_updateset,
	);
}

function announce_flush_event_updates(array $eventUpdates, $torrentId, $userid, $infoHashHex)
{
	$trupdateset = $eventUpdates['trupdateset'] ?? array();
	$updateset = $eventUpdates['updateset'] ?? array();
	$snatch_updateset = $eventUpdates['snatch_updateset'] ?? array();
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;

	if ($trupdateset) {
		announce_safe_query('UPDATE trackers SET ' . join(", ", $trupdateset) . ' WHERE torrent = '.$torrentId.' AND tracker="localhost"');
		lt_cache_delete('torrent:'.$infoHashHex, 'announce');
	}

	if ($updateset) {
		announce_safe_query('UPDATE torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentId);
		lt_cache_delete('torrent:'.$infoHashHex, 'announce');
	}

	if ($userid > 0 && $snatch_updateset) {
		announce_safe_query('UPDATE snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentId.' AND userid = '.(int) $userid);
	}
}

	/**
 * Validate announce statistics (anti-cheat basic checks)
 */
function announce_validate_stats($uploaded, $downloaded, $left)
{
	$uploaded = (int) $uploaded;
	$downloaded = (int) $downloaded;
	$left = (int) $left;

	if ($uploaded < 0 || $downloaded < 0 || $left < 0) {
		return false;
	}

	if ($uploaded > 1099511627776) {
		return false;
	}

	if ($downloaded > 1099511627776) {
		return false;
	}

	if ($left < 0 || $left > 1099511627776) {
		return false;
	}

	return true;
}

/**
 * Validate announce event parameter
 */
function announce_validate_event($event)
{
	$event = announce_normalize_event($event);
	$valid_events = array('', 'started', 'stopped', 'completed');

	return in_array($event, $valid_events, true);
}

function announce_encode_compact_peers(array $peers)
{
	$plist = '';
	foreach ($peers as $row) {
		$peer_ip = explode('.', (string) $row['ip']);
		if (count($peer_ip) === 4) {
			$plist .= pack("C*", (int) $peer_ip[0], (int) $peer_ip[1], (int) $peer_ip[2], (int) $peer_ip[3]) . pack("n*", (int) $row["port"]);
		}
	}

	return $plist;
}

function announce_encode_peer_list(array $peers, $noPeerId)
{
	$resp = '';
	foreach ($peers as $row) {
		$resp .= 'd'
			. benc_str('ip') . benc_str((string) $row['ip'])
			. (!$noPeerId ? benc_str("peer id") . benc_str((string) $row["peer_id"]) : '')
			. benc_str('port') . 'i' . (int) $row['port'] . 'e'
			. 'e';
	}

	return $resp;
}

function announce_success_response($interval, array $peers, $compact, $noPeerId, $peerId)
{
	$resp = "d" . benc_str("interval") . "i" . (int) $interval . "e" . benc_str("peers");
	$isBitComet = (substr((string) $peerId, 0, 4) == '-BC0');

	if ($compact) {
		$resp .= benc_str(announce_encode_compact_peers($peers));
		return $resp . ($isBitComet ? "7:privatei1ee" : "e");
	}

	$resp .= 'l' . announce_encode_peer_list($peers, $noPeerId);
	return $resp . ($isBitComet ? "e7:privatei1ee" : "ee");
}

function announce_failure_response($reason)
{
	benc_resp(array('failure reason' => array('type' => 'string', 'value' => (string) $reason)));
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

function err($msg){
	announce_failure_response($msg);
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
?>

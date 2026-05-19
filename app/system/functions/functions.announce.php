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

function announce_load_peer_write_context($torrentId, $peerId)
{
	$self = announce_fetch_self_peer((int) $torrentId, (string) $peerId, announce_peer_fields());
	if (empty($self['peer_id'])) {
		$self = null;
	}

	return array(
		'self' => $self,
		'userid' => ($self === null ? 0 : (int) ($self['userid'] ?? 0)),
	);
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
	$result = announce_prepare_authenticated_write_result($guest, $self, $torrentId, $passkey, $seeder, $user, $uploaded, $downloaded, $left, $userid);

	return (int) $result['userid'];
}

function announce_prepare_authenticated_write_result($guest, $self, $torrentId, $passkey, $seeder, array $user, $uploaded, $downloaded, $left, $userid)
{
	global $config, $language;

	if ($guest) {
		return array('userid' => (int) $userid, 'user_touched' => false);
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

		return array('userid' => (int) $az['id'], 'user_touched' => false);
	}

	if (!announce_validate_stats($uploaded, $downloaded, $left)) {
		err('Invalid statistics (possible tracker abuse).');
	}

	$userTouched = announce_apply_user_transfer_delta((int) $userid, announce_calculate_transfer_delta($uploaded, $downloaded, $self));

	return array('userid' => (int) $userid, 'user_touched' => $userTouched);
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

function announce_write_result($event)
{
	return array(
		'event' => announce_normalize_event($event),
		'peer_inserted' => false,
		'peer_updated' => false,
		'peer_deleted' => false,
		'completed_counted' => false,
		'tracker_counters_touched' => false,
		'torrent_touched' => false,
		'user_touched' => false,
		'response_state' => 'ok',
		'trupdateset' => array(),
		'updateset' => array(),
		'snatch_updateset' => array(),
	);
}

function announce_calculate_transfer_delta($uploaded, $downloaded, $self)
{
	$uploaded = (int) $uploaded;
	$downloaded = (int) $downloaded;
	$self = (is_array($self) ? $self : array());

	return array(
		'uploaded' => max(0, $uploaded - (int) ($self['uploaded'] ?? 0)),
		'downloaded' => max(0, $downloaded - (int) ($self['downloaded'] ?? 0)),
	);
}

function announce_apply_user_transfer_delta($userid, array $delta)
{
	$userid = (int) $userid;
	$upthis = (int) ($delta['uploaded'] ?? 0);
	$downthis = (int) ($delta['downloaded'] ?? 0);

	if ($userid > 0 && ($upthis > 0 || $downthis > 0)) {
		announce_safe_query('UPDATE users SET uploaded = uploaded + '.$upthis.', downloaded = downloaded + '.$downthis.' WHERE id='.$userid);
		return true;
	}

	return false;
}

function announce_update_snatched_row($torrentId, $userid, array $snatchUpdates)
{
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;

	if ($userid > 0 && $snatchUpdates) {
		announce_safe_query('UPDATE snatched SET ' . join(", ", $snatchUpdates) . ' WHERE torrent = '.$torrentId.' AND userid = '.$userid);
		return true;
	}

	return false;
}

function announce_maybe_count_completed($torrentId, $userid, $self, $left)
{
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;
	$left = (int) $left;

	if ($left !== 0) {
		err('Invalid completed event (torrent not fully downloaded).');
	}

	$canCountCompleted = true;
	if ($userid > 0) {
		$snatched_state = announce_super_query('SELECT finished FROM snatched WHERE torrent = '.$torrentId.' AND userid = '.$userid.' LIMIT 1');
		$canCountCompleted = empty($snatched_state['finished']);
	} elseif ($self !== null && !empty($self['seeder'])) {
		$canCountCompleted = false;
	}

	if (!$canCountCompleted) {
		return array(
			'completed_counted' => false,
			'snatch_updateset' => array(),
			'updateset' => array(),
		);
	}

	return array(
		'completed_counted' => true,
		'snatch_updateset' => array('finished = 1', 'completedat = '.time()),
		'updateset' => array('completed = completed + 1'),
	);
}

function announce_update_torrent_counters($torrentId, array $updateset, $infoHashHex)
{
	$torrentId = (int) $torrentId;

	if ($updateset) {
		announce_safe_query('UPDATE torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentId);
		lt_cache_delete('torrent:'.$infoHashHex, 'announce');
		return true;
	}

	return false;
}

function announce_update_tracker_counters($torrentId, array $trupdateset, $infoHashHex)
{
	$torrentId = (int) $torrentId;

	if ($trupdateset) {
		announce_safe_query('UPDATE trackers SET ' . join(", ", $trupdateset) . ' WHERE torrent = '.$torrentId.' AND tracker="localhost"');
		lt_cache_delete('torrent:'.$infoHashHex, 'announce');
		return true;
	}

	return false;
}

function announce_delete_peer($torrentId, $peerId)
{
	announce_safe_query('DELETE FROM peers WHERE torrent = '.(int) $torrentId.' AND peer_id = '.announce_escape($peerId));

	return (announce_rows_affected() > 0);
}

function announce_update_peer($torrentId, $peerId, array $request, $self, array $delta)
{
	$torrentId = (int) $torrentId;
	$uploaded = (int) ($request['uploaded'] ?? 0);
	$downloaded = (int) ($request['downloaded'] ?? 0);
	$left = (int) ($request['left'] ?? 0);
	$seeder = (string) ($request['seeder'] ?? ($left === 0 ? '1' : '0'));
	$uploaded2 = (int) ($delta['uploaded'] ?? 0);
	$downloaded2 = (int) ($delta['downloaded'] ?? 0);

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

	return (announce_rows_affected() > 0);
}

function announce_insert_peer($torrentId, $userid, array $request, $ip)
{
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;
	$port = (int) ($request['port'] ?? 0);
	$uploaded = (int) ($request['uploaded'] ?? 0);
	$downloaded = (int) ($request['downloaded'] ?? 0);
	$left = (int) ($request['left'] ?? 0);
	$seeder = (string) ($request['seeder'] ?? ($left === 0 ? '1' : '0'));
	$peerId = (string) ($request['peer_id'] ?? '');
	$agent = (string) ($request['agent'] ?? '');
	$passkey = (string) ($request['passkey'] ?? '');

	if (portblacklisted($port)) {
		err('Port '.$port.' is blacklisted.');
	}

	$connectable = announce_probe_connectable($ip, $port);
	return (bool) announce_safe_query(
		"INSERT INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, prev_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey)
		 VALUES ('".$connectable."', ".$torrentId.", ".announce_escape($peerId).", ".announce_escape($ip).", ".$port.", ".$uploaded.", ".$downloaded.", ".$left.", NOW(), NOW(), NOW(), '".$seeder."', '".$userid."', ".announce_escape($agent).", ".$uploaded.", ".$downloaded.", ".announce_escape($passkey).")"
	);
}

function announce_ensure_snatched_started($torrentId, $userid)
{
	$torrentId = (int) $torrentId;
	$userid = (int) $userid;

	if ($userid > 0) {
		$ts_now = (int) time();
		announce_safe_query(
			"INSERT INTO snatched (userid, torrent, uploaded, downloaded, startedat, completedat, finished)"
			." VALUES (".$userid.", ".$torrentId.", 0, 0, ".$ts_now.", 0, 0)"
			." ON DUPLICATE KEY UPDATE startedat = IF(startedat = 0, ".$ts_now.", startedat)"
		);
		return true;
	}

	return false;
}

function announce_mark_peer_seeder_state($self, $seeder)
{
	$seeder = (string) $seeder;
	if ($self !== null && (string) ($self['seeder'] ?? '') !== $seeder) {
		if ($seeder === '1') {
			return array('seeders = seeders + 1', 'leechers = IF(leechers > 0, leechers - 1, 0)');
		}

		return array('leechers = leechers + 1', 'seeders = IF(seeders > 0, seeders - 1, 0)');
	}

	return array();
}

function announce_apply_pending_write_updates(array $result, array $context)
{
	$torrentId = (int) ($context['torrentid'] ?? 0);
	$userid = (int) ($context['userid'] ?? 0);
	$infoHashHex = (string) ($context['info_hash_hex'] ?? '');

	// Transaction-ready boundary: P25 can wrap this ordered flush for completed writes.
	$result['user_touched'] = !empty($result['user_touched']) || !empty($context['user_touched']);
	$result['tracker_counters_touched'] = announce_update_tracker_counters($torrentId, $result['trupdateset'], $infoHashHex);
	$result['torrent_touched'] = announce_update_torrent_counters($torrentId, $result['updateset'], $infoHashHex);
	announce_update_snatched_row($torrentId, $userid, $result['snatch_updateset']);

	return $result;
}

function announce_handle_started_event(array $context, array $request)
{
	return announce_handle_regular_event($context, $request);
}

function announce_prepare_regular_write_result(array $context, array $request)
{
	$result = announce_write_result($request['event'] ?? '');
	$self = $context['self'] ?? null;
	$torrentId = (int) ($context['torrentid'] ?? 0);
	$userid = (int) ($context['userid'] ?? 0);
	$seeder = (string) ($request['seeder'] ?? '0');

	if ($self !== null) {
		$delta = announce_calculate_transfer_delta($request['uploaded'] ?? 0, $request['downloaded'] ?? 0, $self);
		if ((int) $delta['downloaded'] > 0 || (int) $delta['uploaded'] > 0) {
			$result['snatch_updateset'][] = "uploaded = uploaded + ".(int) $delta['uploaded'];
			$result['snatch_updateset'][] = "downloaded = downloaded + ".(int) $delta['downloaded'];
		}

		$result['peer_updated'] = announce_update_peer($torrentId, (string) ($request['peer_id'] ?? ''), $request, $self, $delta);
		if ($result['peer_updated']) {
			$result['trupdateset'] = array_merge($result['trupdateset'], announce_mark_peer_seeder_state($self, $seeder));
		}
	} else {
		$result['peer_inserted'] = announce_insert_peer($torrentId, $userid, $request, (string) ($context['ip'] ?? ''));
		if ($result['peer_inserted']) {
			$result['trupdateset'][] = ($seeder === '1' ? 'seeders = seeders + 1' : 'leechers = leechers + 1');
			announce_ensure_snatched_started($torrentId, $userid);
		}
	}

	if ($seeder === '1') {
		$result['updateset'][] = 'last_action = '.announce_escape(date('Y-m-d H:i:s', time()));
	}

	return $result;
}

function announce_handle_regular_event(array $context, array $request)
{
	return announce_apply_pending_write_updates(announce_prepare_regular_write_result($context, $request), $context);
}

function announce_handle_completed_event(array $context, array $request)
{
	$result = announce_write_result('completed');
	$completed = announce_maybe_count_completed(
		(int) ($context['torrentid'] ?? 0),
		(int) ($context['userid'] ?? 0),
		$context['self'] ?? null,
		(int) ($request['left'] ?? 0)
	);
	$result['completed_counted'] = (bool) $completed['completed_counted'];
	$result['snatch_updateset'] = array_merge($result['snatch_updateset'], $completed['snatch_updateset']);
	$result['updateset'] = array_merge($result['updateset'], $completed['updateset']);

	$regular = announce_prepare_regular_write_result($context, $request);
	foreach (array('peer_inserted', 'peer_updated', 'peer_deleted', 'tracker_counters_touched', 'torrent_touched', 'user_touched') as $flag) {
		$result[$flag] = !empty($result[$flag]) || !empty($regular[$flag]);
	}
	$result['trupdateset'] = array_merge($result['trupdateset'], $regular['trupdateset']);
	$result['snatch_updateset'] = array_merge($result['snatch_updateset'], $regular['snatch_updateset']);
	$result['updateset'] = array_merge($result['updateset'], $regular['updateset']);

	return announce_apply_pending_write_updates($result, $context);
}

function announce_prepare_stopped_write_result(array $context, array $request)
{
	$result = announce_write_result('stopped');
	$self = $context['self'] ?? null;

	if ($self !== null) {
		$result['peer_deleted'] = announce_delete_peer((int) ($context['torrentid'] ?? 0), (string) ($request['peer_id'] ?? ''));
		if ($result['peer_deleted']) {
			if (!empty($self['seeder'])) {
				$result['trupdateset'][] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
			} else {
				$result['trupdateset'][] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
			}
		}
	}

	if ((string) ($request['seeder'] ?? '0') === '1') {
		$result['updateset'][] = 'last_action = '.announce_escape(date('Y-m-d H:i:s', time()));
	}

	return $result;
}

function announce_handle_stopped_event(array $context, array $request)
{
	return announce_apply_pending_write_updates(announce_prepare_stopped_write_result($context, $request), $context);
}

function announce_dispatch_event_write_handler(array $context, array $request)
{
	$event = announce_normalize_event($request['event'] ?? '');

	if ($event === 'started') {
		return announce_handle_started_event($context, $request);
	}

	if ($event === 'stopped') {
		return announce_handle_stopped_event($context, $request);
	}

	if ($event === 'completed') {
		return announce_handle_completed_event($context, $request);
	}

	return announce_handle_regular_event($context, $request);
}

function announce_process_event_write_path($event, $self, $userid, $torrentId, $peerId, $uploaded, $downloaded, $left, $seeder, $port, $ip, $agent, $passkey)
{
	$context = array(
		'torrentid' => (int) $torrentId,
		'userid' => (int) $userid,
		'self' => $self,
		'ip' => (string) $ip,
	);
	$request = array(
		'event' => announce_normalize_event($event),
		'peer_id' => (string) $peerId,
		'uploaded' => (int) $uploaded,
		'downloaded' => (int) $downloaded,
		'left' => (int) $left,
		'seeder' => (string) $seeder,
		'port' => (int) $port,
		'agent' => (string) $agent,
		'passkey' => (string) $passkey,
	);

	if ($request['event'] === 'stopped') {
		return announce_prepare_stopped_write_result($context, $request);
	}

	if ($request['event'] === 'completed') {
		$result = announce_write_result('completed');
		$completed = announce_maybe_count_completed((int) $torrentId, (int) $userid, $self, (int) $left);
		$result['completed_counted'] = (bool) $completed['completed_counted'];
		$result['snatch_updateset'] = array_merge($result['snatch_updateset'], $completed['snatch_updateset']);
		$result['updateset'] = array_merge($result['updateset'], $completed['updateset']);
		$regular = announce_prepare_regular_write_result($context, $request);
		foreach (array('peer_inserted', 'peer_updated', 'peer_deleted') as $flag) {
			$result[$flag] = !empty($result[$flag]) || !empty($regular[$flag]);
		}
		$result['trupdateset'] = array_merge($result['trupdateset'], $regular['trupdateset']);
		$result['snatch_updateset'] = array_merge($result['snatch_updateset'], $regular['snatch_updateset']);
		$result['updateset'] = array_merge($result['updateset'], $regular['updateset']);
		return $result;
	}

	return announce_prepare_regular_write_result($context, $request);
}

function announce_flush_event_updates(array $eventUpdates, $torrentId, $userid, $infoHashHex)
{
	return announce_apply_pending_write_updates(
		$eventUpdates,
		array(
			'torrentid' => (int) $torrentId,
			'userid' => (int) $userid,
			'info_hash_hex' => (string) $infoHashHex,
		)
	);
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

if (!function_exists('err')) {
function err($msg){
	announce_failure_response($msg);
	die();
}
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

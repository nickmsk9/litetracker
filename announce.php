<?php
/*
===================================================================
LiteTracker
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Анонсер для связи клиента и трекера
===================================================================
*/
define('ANNOUNCE', true);
require 'system/init.announce.php';

$request = announce_parse_request();

$info_hash = $request['info_hash'];
$peer_id = $request['peer_id'];
$event = trim((string) $request['event']);
$port = (int) $request['port'];
$downloaded = (int) $request['downloaded'];
$uploaded = (int) $request['uploaded'];
$left = (int) $request['left'];
$passkey = trim((string) $request['passkey']);
$compact = !empty($request['compact']);
$no_peer_id = !empty($request['no_peer_id']);
$GUEST = ($passkey === '' ? 1 : 0);
$ip = getip();
$ip_ban = ip2long_db($ip);

if (!$GUEST && strlen($passkey) !== 32) {
	err(sprintf($language['announce_3'], strlen($passkey), $passkey));
}

announce_apply_rate_limit(
	'announce',
	($passkey !== '' ? 'passkey:'.$passkey : 'ip:'.$ip),
	180,
	300,
	'Слишком много announce-запросов. Повторите попытку чуть позже.'
);

$ban_resource = announce_fetch_ip_ban($ip_ban);
if (!empty($ban_resource)) {
	err('Please note, your IP ('.long2ip($ip_ban).') has been banned '.convent_date($ban_resource['date']).'');
}

$rsize = announce_numwant(50);
$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!$port || $port > 0xffff) {
	err($language['announce_4']);
}

if ($event === '') {
	$event = '';
}

$seeder = ($left === 0 ? '1' : '0');
$headers = (function_exists('getallheaders') ? getallheaders() : emu_getallheaders());

if (isset($headers['Cookie']) || isset($headers['Accept-Language']) || isset($headers['Accept-Charset'])) {
	err($language['announce_5']);
}

checkclient($peer_id);

if (!$GUEST) {
	$user = announce_fetch_user_by_passkey($passkey);
	if (empty($user['id'])) {
		err($language['announce_6']);
	}
}

$info_hash_hex = bin2hex($info_hash);
$torrent = announce_fetch_torrent($info_hash_hex);
if (empty($torrent['id'])) {
	err($language['announce_7']);
}

$torrentid = (int) $torrent['id'];
$numpeers = (int) ($torrent['numpeers'] ?? 0);
$fields = "seeder, peer_id, ip, port, uploaded, downloaded, userid, UNIX_TIMESTAMP(last_action) AS prevts, UNIX_TIMESTAMP(NOW()) AS nowts, last_action";
$limitSql = ($numpeers > $rsize ? 'ORDER BY last_action DESC LIMIT '.$rsize : '');
$peers_sql = announce_fetch_peer_rows($torrentid, $fields, $limitSql);

$resp = "d" . benc_str("interval") . "i" . $config['announce_interval'] . "e" . benc_str("peers") . ($compact ? '' : 'l');
$plist = '';
$trupdateset = array();
$self = null;
$userid = 0;

while ($row = $db->get_row($peers_sql)) {
	if ((string) ($row['peer_id'] ?? '') === $peer_id) {
		$userid = (int) ($row['userid'] ?? 0);
		$self = $row;
		continue;
	}

	if ($compact) {
		$peer_ip = explode('.', (string) $row['ip']);
		if (count($peer_ip) === 4) {
			$plist .= pack("C*", (int) $peer_ip[0], (int) $peer_ip[1], (int) $peer_ip[2], (int) $peer_ip[3]) . pack("n*", (int) $row["port"]);
		}
		continue;
	}

	$resp .= 'd'
		. benc_str('ip') . benc_str((string) $row['ip'])
		. (!$no_peer_id ? benc_str("peer id") . benc_str((string) $row["peer_id"]) : '')
		. benc_str('port') . 'i' . (int) $row['port'] . 'e'
		. 'e';
}

$resp .= ($compact ? benc_str($plist) : '') . (substr($peer_id, 0, 4) == '-BC0' ? "e7:privatei1ee" : "ee");

if ($self === null) {
	$row = announce_fetch_self_peer($torrentid, $peer_id, $fields);
	if (!empty($row['peer_id'])) {
		$userid = (int) ($row['userid'] ?? 0);
		$self = $row;
	}
}

$announce_wait = 15 * 60;
if ($self !== null && !empty($self['prevts']) && !empty($self['nowts']) && (int) $self['prevts'] > ((int) $self['nowts'] - $announce_wait)) {
	err(sprintf($language['announce_8'], $announce_wait));
}

if (!$GUEST) {
	if ($self === null) {
		$valid = announce_count_peers_by_passkey($torrentid, $passkey);
		if ($valid >= 1 && $seeder === '0') {
			announce_safe_query("DELETE FROM peers WHERE torrent=".$torrentid." AND passkey=".announce_escape($passkey));
			announce_safe_query("UPDATE trackers SET leechers=(leechers-".$valid.") WHERE torrent=".$torrentid." AND tracker='localhost'");
			err($language['announce_9']);
		}

		if ($valid >= 3 && $seeder === '1') {
			announce_safe_query("DELETE FROM peers WHERE torrent=".$torrentid." AND passkey=".announce_escape($passkey));
			announce_safe_query("UPDATE trackers SET seeders=(seeders-".$valid.") WHERE torrent=".$torrentid." AND tracker='localhost'");
			err($language['announce_9']);
		}

		$az = announce_fetch_user_stats_by_passkey($passkey);
		if (empty($az['id'])) {
			err(sprintf($language['announce_10'], $config['sitename']));
		}

		$PRIV = get_priv_info((int) $az['class']);
		$userid = (int) $az['id'];

	} else {
		$upthis = max(0, $uploaded - (int) $self['uploaded']);
		$downthis = max(0, $downloaded - (int) $self['downloaded']);

		if ($upthis > 0 || $downthis > 0) {
			announce_safe_query('UPDATE users SET uploaded = uploaded + '.$upthis.', downloaded = downloaded + '.$downthis.' WHERE id='.(int) $userid);
		}
	}
}

$dt = announce_escape(date('Y-m-d H:i:s', time()));
$updateset = array();
$snatch_updateset = array();

if ($event === 'stopped') {
	if ($self !== null) {
		announce_safe_query('DELETE FROM peers WHERE torrent = '.$torrentid.' AND peer_id = '.announce_escape($peer_id));
		if (announce_rows_affected()) {
			if (!empty($self['seeder'])) {
				$trupdateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
			} else {
				$trupdateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
			}
		}
	}
} else {
	if ($event === 'completed') {
		$snatch_updateset[] = "finished = 1";
		$snatch_updateset[] = "completedat = ".$dt;
		$updateset[] = 'completed = completed + 1';
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
			. " WHERE torrent = ".$torrentid." AND peer_id = ".announce_escape($peer_id)
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

		$ret = announce_safe_query(
			"INSERT INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey)
			 VALUES ('".$connectable."', ".$torrentid.", ".announce_escape($peer_id).", ".announce_escape($ip).", ".$port.", ".$uploaded.", ".$downloaded.", ".$left.", NOW(), NOW(), '".$seeder."', '".$userid."', ".announce_escape($agent).", ".$uploaded.", ".$downloaded.", ".announce_escape($passkey).")"
		);

		if ($ret) {
			if ($seeder === '1') {
				$trupdateset[] = 'seeders = seeders + 1';
			} else {
				$trupdateset[] = 'leechers = leechers + 1';
			}
		}
	}
}

if ($seeder === '1') {
	$updateset[] = 'last_action = '.$dt;
}

if ($trupdateset) {
	announce_safe_query('UPDATE trackers SET ' . join(", ", $trupdateset) . ' WHERE torrent = '.$torrentid.' AND tracker="localhost"');
}

if ($updateset) {
	announce_safe_query('UPDATE torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentid);
}

if ($userid > 0 && $snatch_updateset) {
	announce_safe_query('UPDATE snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentid.' AND userid = '.(int) $userid);
}

benc_resp_raw($resp);

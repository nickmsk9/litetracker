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
$announce_start = microtime(true);

$info_hash = $request['info_hash'];
$peer_id = $request['peer_id'];
$event = $request['event'];
$port = (int) $request['port'];
$downloaded = (int) $request['downloaded'];
$uploaded = (int) $request['uploaded'];
$left = (int) $request['left'];
$passkey = trim((string) $request['passkey']);
$compact = !empty($request['compact']);
$no_peer_id = !empty($request['no_peer_id']);
$rsize = (int) $request['numwant'];
$agent = (string) $request['agent'];
$client_flags = $request['client_flags'];
$GUEST = ($passkey === '' ? 1 : 0);
$ip = getip();
$announce_interval = (int) ($config['announce_interval'] ?? 1800);

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

announce_apply_rate_limit(
	'announce_ip',
	'ip:'.$ip,
	600,
	300,
	'Слишком много запросов с вашего IP. Повторите попытку чуть позже.'
);

$ban_context = announce_load_ban_context($ip);
$ip_ban = $ban_context['ip_ban'];
$ban_resource = $ban_context['ban'];
if (!empty($ban_resource)) {
	err('Please note, your IP ('.long2ip($ip_ban).') has been banned '.convent_date($ban_resource['date']).'');
}

if (!$port || $port < 1 || $port > 0xffff) {
	err($language['announce_4']);
}

if (!announce_validate_event($event)) {
	err('Invalid event parameter.');
}

if (!announce_validate_stats($uploaded, $downloaded, $left)) {
	err('Invalid statistics (possible tracker abuse).');
}

$seeder = ($left === 0 ? '1' : '0');

if (!empty($client_flags['has_browser_headers'])) {
	err($language['announce_5']);
}

checkclient($peer_id);

$user_context = announce_load_user_context($passkey, $GUEST);
$user = $user_context['user'];
if (!$GUEST) {
	if (empty($user['id'])) {
		err($language['announce_6']);
	}
}

$torrent_context = announce_load_torrent_context($info_hash);
$info_hash_hex = $torrent_context['info_hash_hex'];
$torrent = $torrent_context['torrent'];
if (empty($torrent['id'])) {
	err($language['announce_7']);
}

$torrent_size = (int) $torrent_context['torrent_size'];
if ($torrent_size > 0 && $left > $torrent_size) {
	err('Invalid left value (greater than torrent size).');
}

$torrentid = (int) $torrent_context['torrentid'];
$numpeers = (int) $torrent_context['numpeers'];
$peer_context = announce_load_peer_context($torrentid, $peer_id, $rsize, $numpeers);

$trupdateset = array();
$self = $peer_context['self'];
$userid = (int) $peer_context['userid'];
$peer_candidates = $peer_context['candidates'];

$resp = announce_success_response($announce_interval, $peer_candidates, $compact, $no_peer_id, $peer_id);

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

			$az = $user;
			if (empty($az['id'])) {
				err(sprintf($language['announce_10'], (string) ($config['sitename'] ?? 'LiteTracker')));
			}

		$PRIV = get_priv_info((int) $az['class']);
		$userid = (int) $az['id'];

	} else {
		if (!announce_validate_stats($uploaded, $downloaded, $left)) {
			err('Invalid statistics (possible tracker abuse).');
		}

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
	if ($event === 'completed' && $left === 0) {
		$can_count_completed = true;
		if ($userid > 0) {
			$snatched_state = announce_super_query('SELECT finished FROM snatched WHERE torrent = '.$torrentid.' AND userid = '.(int) $userid.' LIMIT 1');
			$can_count_completed = empty($snatched_state['finished']);
		} elseif ($self !== null && !empty($self['seeder'])) {
			$can_count_completed = false;
		}

		if ($can_count_completed) {
			$snatch_updateset[] = "finished = 1";
			$snatch_updateset[] = "completedat = ".$dt;
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
			"INSERT INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, prev_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey)
			 VALUES ('".$connectable."', ".$torrentid.", ".announce_escape($peer_id).", ".announce_escape($ip).", ".$port.", ".$uploaded.", ".$downloaded.", ".$left.", NOW(), NOW(), NOW(), '".$seeder."', '".$userid."', ".announce_escape($agent).", ".$uploaded.", ".$downloaded.", ".announce_escape($passkey).")"
		);

		if ($ret) {
			if ($seeder === '1') {
				$trupdateset[] = 'seeders = seeders + 1';
			} else {
				$trupdateset[] = 'leechers = leechers + 1';
			}

			// Ensure snatched row exists for this user/torrent so that subsequent
			// UPDATE snatched ... WHERE userid=X AND torrent=Y does not silently fail.
			// ON DUPLICATE KEY UPDATE is a no-op if the row already exists (preserves startedat).
			if ($userid > 0) {
				$ts_now = (int) time();
				announce_safe_query(
					"INSERT INTO snatched (userid, torrent, uploaded, downloaded, startedat, completedat, finished)"
					." VALUES (".$userid.", ".$torrentid.", 0, 0, ".$ts_now.", 0, 0)"
					." ON DUPLICATE KEY UPDATE startedat = IF(startedat = 0, ".$ts_now.", startedat)"
				);
			}
		}
	}
}

if ($seeder === '1') {
	$updateset[] = 'last_action = '.$dt;
}

if ($trupdateset) {
	announce_safe_query('UPDATE trackers SET ' . join(", ", $trupdateset) . ' WHERE torrent = '.$torrentid.' AND tracker="localhost"');
	lt_cache_delete('torrent:'.$info_hash_hex, 'announce');
}

if ($updateset) {
	announce_safe_query('UPDATE torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentid);
	lt_cache_delete('torrent:'.$info_hash_hex, 'announce');
}

if ($userid > 0 && $snatch_updateset) {
	announce_safe_query('UPDATE snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentid.' AND userid = '.(int) $userid);
}

announce_debug_log($request, $announce_start, (int) $peer_context['returned_peer_count'], $ip);
benc_resp_raw($resp);

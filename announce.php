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
require __DIR__ . '/app/system/init.announce.php';

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
$peer_write_context = announce_load_peer_write_context($torrentid, $peer_id);
$self = $peer_write_context['self'];
$userid = (int) $peer_write_context['userid'];

$announce_wait = 15 * 60;
if ($self !== null && !empty($self['prevts']) && !empty($self['nowts']) && (int) $self['prevts'] > ((int) $self['nowts'] - $announce_wait)) {
	err(sprintf($language['announce_8'], $announce_wait));
}

$auth_write = announce_prepare_authenticated_write_result($GUEST, $self, $torrentid, $passkey, $seeder, $user, $uploaded, $downloaded, $left, $userid);
$userid = (int) $auth_write['userid'];
$write_context = array(
	'torrentid' => $torrentid,
	'userid' => $userid,
	'self' => $self,
	'ip' => $ip,
	'info_hash_hex' => $info_hash_hex,
	'user_touched' => !empty($auth_write['user_touched']),
);
$write_request = array_merge($request, array('seeder' => $seeder));
announce_dispatch_event_write_handler($write_context, $write_request);

$peer_context = announce_load_peer_context($torrentid, $peer_id, $rsize, $numpeers);
$resp = announce_success_response($announce_interval, $peer_context['candidates'], $compact, $no_peer_id, $peer_id);

announce_debug_log($request, $announce_start, (int) $peer_context['returned_peer_count'], $ip);
benc_resp_raw($resp);

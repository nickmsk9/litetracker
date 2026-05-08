<?php
#!/usr/bin/env php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Обновление пиров для торрента
===================================================================
*/

//Устанавливаем полный путь
define('DIRNAME' , str_replace('\\' , '/' , dirname( __FILE__ ) ) );

function update_peers_response_gif()
{
	return base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");
}

function update_peers_wants_json()
{
	return isset($_GET['ajax']) || stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
}

function update_peers_redirect_url()
{
	$return = trim((string) ($_GET['return'] ?? ''));
	if ($return === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $return)) {
		return '';
	}

	return str_replace(array("\r", "\n"), '', $return);
}

function update_peers_finish($lock = null, array $payload = array())
{
	global $memcached;

	if ($lock) {
		lt_lock_release($lock);
	}

	if (isset($memcached)) {
		$memcached->delete('CRON');
	}

	if (update_peers_wants_json()) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge(array('success' => true), $payload));
		exit;
	}

	$redirectUrl = update_peers_redirect_url();
	if ($redirectUrl !== '') {
		header('Location: '.$redirectUrl);
		exit;
	}

	header("Content-Type: image/gif");
	print update_peers_response_gif();
	exit;
}

ignore_user_abort(true);
set_time_limit(0);

//Подключаем главный системный файл
require DIRNAME.'/system/init.autoclean.php';
//Функции для обновления
require DIRNAME.'/system/functions/functions.benc.php';

lt_require_cron_access();

$updatePeersLock = lt_lock_acquire('update.peers');
if (!$updatePeersLock) {
	update_peers_finish(null, array('success' => false, 'message' => 'Обновление уже выполняется.'));
}

//Порядковый номер
$id = (int) ($_GET['id'] ?? 0);
$updatedTrackers = 0;

//Если один релиз , то обновляем одну запись
if ($id) {

	$anarray = $db->query("SELECT torrents.infohash, trackers.tracker
							FROM trackers
							LEFT JOIN torrents ON torrents.id=trackers.torrent
							WHERE trackers.torrent=".$id." AND trackers.tracker<>'localhost'");

	while ($trackerRow = $db->get_array($anarray)) {
		$infohash = (string) ($trackerRow[0] ?? '');
		$url = (string) ($trackerRow[1] ?? '');
		$peers = get_remote_peers($url, $infohash);
		$db->query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".$db->safesql((string) ($peers['state'] ?? ''))."' WHERE torrent=".$id." AND tracker='".$db->safesql($url)."'");
		$updatedTrackers++;
	}

	update_peers_finish($updatePeersLock, array('updated_trackers' => $updatedTrackers));
}

if ($CRON['multi_remote'] && (( time() - $CRON['last_remotecheck'] ) < $CRON['remotecheck_interval'] ) ) {
	update_peers_finish($updatePeersLock, array('updated_trackers' => $updatedTrackers));
}



if ($CRON['multi_remote']) {
	$CRON['remote_lastchecked'] = (int)$CRON['remote_lastchecked'];
	$CRON['remote_torrents'] = (int)$CRON['remote_torrents'];

	$db->query("UPDATE cron SET cron_value=1 WHERE cron_name='in_remotecheck'");
	$db->query("UPDATE cron SET cron_value=".time()." WHERE cron_name='last_remotecheck'");
	$db->query("UPDATE cron SET cron_value=cron_value+1 WHERE cron_name='num_checked'");

	$res = $db->query("SELECT torrents.id, torrents.infohash, trackers.tracker FROM trackers LEFT JOIN torrents ON torrents.id=trackers.torrent WHERE ".($CRON['remotepeers_cleantime']?"trackers.lastchecked<".(time()-$CRON['remotepeers_cleantime'])." AND ":'')."trackers.tracker<>'localhost'".($CRON['remote_lastchecked']?" AND torrents.id<{$CRON['remote_lastchecked']}":'')." ORDER BY torrents.id DESC".($CRON['remote_torrents']?" LIMIT {$CRON['remote_torrents']}":''));

	$parray = array();
	while ($row = $db->get_row($res)) {
		$LAST_ID = (int) $row['id'];
		$parray[] = array(
			'id' => (int) $row['id'],
			'info_hash' => (string) $row['infohash'],
			'tracker' => (string) $row['tracker'],
		);
	}

	if ($parray) {
		$db->query("UPDATE cron SET cron_value=$LAST_ID WHERE cron_name='remote_lastchecked'");
		foreach ($parray as $torrent) {
			$id = (int) $torrent['id'];
			$hash = $torrent['info_hash'];
			$url = $torrent['tracker'];
			$peers = get_remote_peers($url, $hash);
			$db->query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".$db->safesql((string) ($peers['state'] ?? ''))."' WHERE torrent=$id AND tracker='".$db->safesql($url)."'");
			$updatedTrackers++;
		}

	} else $db->query("UPDATE cron SET cron_value=0 WHERE cron_name='remote_lastchecked'");

	$db->query("UPDATE cron SET cron_value=0 WHERE cron_name='in_remotecheck'");
}

update_peers_finish($updatePeersLock, array('updated_trackers' => $updatedTrackers));
?>

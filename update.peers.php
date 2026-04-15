<?php
#!/usr/bin/env php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Обновление пиров для торрента
===================================================================
*/

header("Content-Type: image/gif");

//Устанавливаем полный путь
define('DIRNAME' , str_replace('\\' , '/' , dirname( __FILE__ ) ) );

//Подключаем главный системный файл
require DIRNAME.'/system/init.php';
//Функции для обновления
require DIRNAME.'/system/functions/functions.benc.php';


//Порядковый номер
$id = (int) $_GET['id'];

//Если один релиз , то обновляем одну запись
if ($id) {

	$anarray = $db->query("SELECT torrents.infohash, trackers.tracker 
							FROM trackers 
							LEFT JOIN torrents ON torrents.id=trackers.torrent 
							WHERE trackers.torrent=".$id." AND trackers.tracker<>'localhost'");

	while (list($infohash,$url) = mysql_fetch_array($anarray)) {
		$peers = get_remote_peers($url, $infohash);
		$db->query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".mysql_real_escape_string($peers['state'])."' WHERE torrent=".$id." AND tracker='$url'");
	}
}

if ($CRON['multi_remote'] && (( time() - $CRON['last_remotecheck'] ) < $CRON['remotecheck_interval'] ) ) {
	print "ok 1";
	die();
}



if ($CRON['multi_remote']) {
	$CRON['remote_lastchecked'] = (int)$CRON['remote_lastchecked'];
	$CRON['remote_torrents'] = (int)$CRON['remote_torrents'];

	$db->query("UPDATE cron SET cron_value=1 WHERE cron_name='in_remotecheck'");
	$db->query("UPDATE cron SET cron_value=".time()." WHERE cron_name='last_remotecheck'");
	$db->query("UPDATE cron SET cron_value=cron_value+1 WHERE cron_name='num_checked'");

	$res = $db->query("SELECT torrents.id, torrents.infohash, trackers.tracker FROM trackers LEFT JOIN torrents ON torrents.id=trackers.torrent WHERE ".($CRON['remotepeers_cleantime']?"trackers.lastchecked<".(time()-$CRON['remotepeers_cleantime'])." AND ":'')."trackers.tracker<>'localhost'".($CRON['remote_lastchecked']?" AND torrents.id<{$CRON['remote_lastchecked']}":'')." ORDER BY torrents.id DESC".($CRON['remote_torrents']?" LIMIT {$CRON['remote_torrents']}":''));

	while ($row = $db->get_row($res)) {$LAST_ID=$row['id']; $parray[$row['id']] = array('info_hash'=>$row['infohash'],'tracker'=>$row['tracker']); }

	if ($parray) {
		$db->query("UPDATE cron SET cron_value=$LAST_ID WHERE cron_name='remote_lastchecked'");
		foreach ($parray as $id => $torrent) {
			$hash = $torrent['info_hash'];
			$url = $torrent['tracker'];
			$peers = get_remote_peers($url, $hash);
			$db->query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".mysql_real_escape_string($peers['state'])."' WHERE torrent=$id AND tracker='$url'");
		}
		
	} else $db->query("UPDATE cron SET cron_value=0 WHERE cron_name='remote_lastchecked'");

	$db->query("UPDATE cron SET cron_value=0 WHERE cron_name='in_remotecheck'");
}

$memcache->delete('CRON');
print "ok 2";
?>
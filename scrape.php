<?php
/*
===================================================================
LiteTracker
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Scrape - файл
===================================================================
*/

define('IN_ANNOUNCE', true);
require_once 'system/init.announce.php';

$rawInfoHash = announce_get_string_param('info_hash');
if ($rawInfoHash === '') {
	err('Не указан info_hash.');
}

$rawInfoHash = announce_ensure_string_length($rawInfoHash, 20, 'info_hash');
$hash = bin2hex($rawInfoHash);

announce_apply_rate_limit(
	'scrape',
	'ip:'.getip(),
	120,
	300,
	'Слишком много scrape-запросов. Повторите попытку чуть позже.'
);

$row = lt_cache_remember(
	'scrape:'.$hash,
	rand(100, 300),
	function () use ($hash) {
		return announce_super_query(
			"SELECT torrents.id, torrents.infohash, torrents.completed, trackers.seeders, trackers.leechers
			 FROM torrents
			 LEFT JOIN trackers ON torrents.id = trackers.torrent
			 WHERE torrents.infohash = " . announce_escape($hash) . "
			 LIMIT 1"
		);
	},
	'announce'
);

if (empty($row['infohash'])) {
	err('Торрент не найден.');
}

$seeders = (int) ($row['seeders'] ?? 0);
$completed = (int) ($row['completed'] ?? 0);
$leechers = (int) ($row['leechers'] ?? 0);

$response = 'd5:files';
$response .= 'd20:'.pack('H*', (string) $row['infohash'])."d8:completei{$seeders}e10:downloadedi{$completed}e10:incompletei{$leechers}eeee";

header("Pragma: no-cache");
print($response);

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

$rawInfoHashes = array();
$infoHashParam = ($_GET['info_hash'] ?? '');
if (is_array($infoHashParam)) {
	foreach ($infoHashParam as $rawValue) {
		$rawValue = (string) $rawValue;
		if ($rawValue !== '') {
			$rawInfoHashes[] = $rawValue;
		}
	}
} else {
	$rawValue = (string) $infoHashParam;
	if ($rawValue !== '') {
		$rawInfoHashes[] = $rawValue;
	}
}

if (!$rawInfoHashes) {
	err('Не указан info_hash.');
}

$hashes = array();
foreach ($rawInfoHashes as $rawInfoHash) {
	$rawInfoHash = announce_ensure_string_length($rawInfoHash, 20, 'info_hash');
	$hashes[] = bin2hex($rawInfoHash);
}
$hashes = array_values(array_unique($hashes));

announce_apply_rate_limit(
	'scrape',
	'ip:'.getip(),
	120,
	300,
	'Слишком много scrape-запросов. Повторите попытку чуть позже.'
);

$escapedHashes = array();
foreach ($hashes as $hash) {
	$escapedHashes[] = announce_escape($hash);
}

$rowsByHash = array();
$sql = announce_safe_query(
	"SELECT torrents.id, torrents.infohash, torrents.completed, trackers.seeders, trackers.leechers
	 FROM torrents
	 LEFT JOIN trackers ON torrents.id = trackers.torrent
	 WHERE torrents.infohash IN (" . implode(',', $escapedHashes) . ")"
);
while ($row = ($sql ? $db->get_row($sql) : false)) {
	if (!empty($row['infohash'])) {
		$rowsByHash[(string) $row['infohash']] = $row;
	}
}

if (!$rowsByHash) {
	err('Торрент не найден.');
}

$response = 'd5:files';
$response .= 'd';
foreach ($hashes as $hash) {
	if (empty($rowsByHash[$hash])) {
		continue;
	}

	$row = $rowsByHash[$hash];
	$seeders = (int) ($row['seeders'] ?? 0);
	$completed = (int) ($row['completed'] ?? 0);
	$leechers = (int) ($row['leechers'] ?? 0);
	$response .= '20:'.pack('H*', (string) $row['infohash'])."d8:completei{$seeders}e10:downloadedi{$completed}e10:incompletei{$leechers}ee";
}
$response .= 'ee';

header("Pragma: no-cache");
print($response);

<?php
#!/usr/bin/env php

define('DIRNAME', str_replace('\\', '/', dirname(__FILE__)));
require DIRNAME.'/../system/init.autoclean.php';

if (PHP_SAPI !== 'cli') {
	header('HTTP/1.1 403 Forbidden');
	echo "CLI only\n";
	exit(1);
}

function tm_stdout($message)
{
	echo $message."\n";
}

function tm_fail($message, $exitCode = 3)
{
	tm_stdout('ERROR: '.$message);
	exit((int) $exitCode);
}

function tm_query($sql)
{
	global $db;

	$result = $db->query($sql, 0);
	if ($result === false) {
		tm_fail('DB query failed');
	}

	return $result;
}

function tm_super_query($sql)
{
	global $db;

	$row = $db->super_query($sql);
	if ($row === false) {
		tm_fail('DB super query failed');
	}

	return $row;
}

function tm_parse_options($argv)
{
	$options = array(
		'limit' => 500,
		'older-than' => 1800,
		'torrent' => 0,
		'dry-run' => false,
	);

	for ($i = 2; $i < count($argv); $i++) {
		$arg = (string) $argv[$i];
		if ($arg === '--dry-run') {
			$options['dry-run'] = true;
			continue;
		}

		if (strpos($arg, '--limit=') === 0) {
			$options['limit'] = max(1, min(5000, (int) substr($arg, 8)));
			continue;
		}

		if (strpos($arg, '--older-than=') === 0) {
			$options['older-than'] = max(60, min(30 * 24 * 3600, (int) substr($arg, 13)));
			continue;
		}

		if (strpos($arg, '--torrent=') === 0) {
			$options['torrent'] = max(0, (int) substr($arg, 10));
			continue;
		}

		tm_fail('Unknown option: '.$arg, 2);
	}

	return $options;
}

function tm_cleanup_peers($limit, $olderThanSeconds, $dryRun)
{
	global $db;

	$cutoff = date('Y-m-d H:i:s', time() - max(60, (int) $olderThanSeconds));
	$res = tm_query("SELECT id FROM peers WHERE last_action < '".$db->safesql($cutoff)."' ORDER BY last_action ASC LIMIT ".(int) $limit);

	$peerIds = array();
	while ($row = $db->get_row($res)) {
		$peerIds[] = (int) ($row['id'] ?? 0);
	}

	$total = count($peerIds);
	if ($total === 0) {
		return array('selected' => 0, 'deleted' => 0, 'cutoff' => $cutoff);
	}

	if ($dryRun) {
		return array('selected' => $total, 'deleted' => 0, 'cutoff' => $cutoff);
	}

	$idsSql = implode(',', $peerIds);
	tm_query('DELETE FROM peers WHERE id IN ('.$idsSql.')');
	$deleted = (int) $db->affected_rows();

	return array('selected' => $total, 'deleted' => $deleted, 'cutoff' => $cutoff);
}

function tm_collect_tracker_torrents($limit, $torrentId)
{
	global $db;

	if ($torrentId > 0) {
		$res = tm_query(
			"SELECT tr.torrent AS torrent_id, tr.seeders AS tracker_seeders, tr.leechers AS tracker_leechers, t.infohash
			 FROM trackers tr
			 LEFT JOIN torrents t ON t.id = tr.torrent
			 WHERE tr.tracker = 'localhost' AND tr.torrent = ".(int) $torrentId."\n			 LIMIT 1"
		);
	} else {
		$res = tm_query(
			"SELECT tr.torrent AS torrent_id, tr.seeders AS tracker_seeders, tr.leechers AS tracker_leechers, t.infohash
			 FROM trackers tr
			 LEFT JOIN torrents t ON t.id = tr.torrent
			 WHERE tr.tracker = 'localhost'
			 ORDER BY tr.torrent ASC
			 LIMIT ".(int) $limit
		);
	}

	$rows = array();
	while ($row = $db->get_row($res)) {
		$rows[] = array(
			'torrent_id' => (int) ($row['torrent_id'] ?? 0),
			'tracker_seeders' => (int) ($row['tracker_seeders'] ?? 0),
			'tracker_leechers' => (int) ($row['tracker_leechers'] ?? 0),
			'infohash' => strtolower(trim((string) ($row['infohash'] ?? ''))),
		);
	}

	return $rows;
}

function tm_recount_stats($limit, $torrentId, $dryRun)
{
	global $db;

	$rows = tm_collect_tracker_torrents($limit, $torrentId);
	if (!$rows) {
		return array(
			'scanned' => 0,
			'changed' => 0,
			'tracker_updates' => 0,
			'torrents_updates' => 0,
			'cache_invalidations' => 0,
		);
	}

	$hasTorrentSeeders = function_exists('lt_column_exists') ? lt_column_exists('torrents', 'seeders') : false;
	$hasTorrentLeechers = function_exists('lt_column_exists') ? lt_column_exists('torrents', 'leechers') : false;
	$hasTorrentStatsCols = ($hasTorrentSeeders && $hasTorrentLeechers);

	$changed = 0;
	$trackerUpdates = 0;
	$torrentsUpdates = 0;
	$cacheInvalidations = 0;

	foreach ($rows as $row) {
		$torrentIdCurrent = (int) $row['torrent_id'];
		if ($torrentIdCurrent <= 0) {
			continue;
		}

		$stats = tm_super_query(
			"SELECT
				COALESCE(SUM(CASE WHEN seeder = 1 THEN 1 ELSE 0 END), 0) AS seeders,
				COALESCE(SUM(CASE WHEN seeder = 0 THEN 1 ELSE 0 END), 0) AS leechers
			 FROM peers
			 WHERE torrent = ".$torrentIdCurrent
		);

		$newSeeders = (int) ($stats['seeders'] ?? 0);
		$newLeechers = (int) ($stats['leechers'] ?? 0);
		$oldSeeders = (int) $row['tracker_seeders'];
		$oldLeechers = (int) $row['tracker_leechers'];

		if ($newSeeders === $oldSeeders && $newLeechers === $oldLeechers) {
			continue;
		}

		$changed++;
		if ($dryRun) {
			continue;
		}

		tm_query(
			"UPDATE trackers
			 SET seeders = ".$newSeeders.", leechers = ".$newLeechers.", lastchecked = ".time()."
			 WHERE tracker = 'localhost' AND torrent = ".$torrentIdCurrent
		);
		$trackerUpdates += (int) $db->affected_rows();

		if ($hasTorrentStatsCols) {
			tm_query(
				"UPDATE torrents
				 SET seeders = ".$newSeeders.", leechers = ".$newLeechers."
				 WHERE id = ".$torrentIdCurrent." AND (seeders <> ".$newSeeders." OR leechers <> ".$newLeechers.")"
			);
			$torrentsUpdates += (int) $db->affected_rows();
		}

		if ($row['infohash'] !== '' && function_exists('lt_cache_delete')) {
			lt_cache_delete('torrent:'.$row['infohash'], 'announce');
			$cacheInvalidations++;
		}
	}

	return array(
		'scanned' => count($rows),
		'changed' => $changed,
		'tracker_updates' => $trackerUpdates,
		'torrents_updates' => $torrentsUpdates,
		'cache_invalidations' => $cacheInvalidations,
	);
}

$mode = trim((string) ($argv[1] ?? ''));
if (!in_array($mode, array('cleanup-peers', 'recount-stats', 'all'), true)) {
	tm_stdout('Usage: php scripts/tracker_maintenance.php <cleanup-peers|recount-stats|all> [--limit=500] [--older-than=1800] [--torrent=ID] [--dry-run]');
	exit(2);
}

$options = tm_parse_options($argv);
$limit = (int) $options['limit'];
$olderThan = (int) $options['older-than'];
$torrent = (int) $options['torrent'];
$dryRun = !empty($options['dry-run']);

$lock = function_exists('lt_lock_acquire') ? lt_lock_acquire('tracker_maintenance') : true;
if (!$lock) {
	tm_fail('maintenance already running', 4);
}

$exitCode = 0;

try {
	if ($mode === 'cleanup-peers' || $mode === 'all') {
		$cleanup = tm_cleanup_peers($limit, $olderThan, $dryRun);
		tm_stdout('cleanup-peers: cutoff='.$cleanup['cutoff'].' selected='.(int) $cleanup['selected'].' deleted='.(int) $cleanup['deleted'].' dry_run='.($dryRun ? '1' : '0'));
	}

	if ($mode === 'recount-stats' || $mode === 'all') {
		$recount = tm_recount_stats($limit, $torrent, $dryRun);
		tm_stdout(
			'recount-stats: scanned='.(int) $recount['scanned']
			.' changed='.(int) $recount['changed']
			.' tracker_updates='.(int) $recount['tracker_updates']
			.' torrents_updates='.(int) $recount['torrents_updates']
			.' cache_invalidations='.(int) $recount['cache_invalidations']
			.' dry_run='.($dryRun ? '1' : '0')
		);
	}
} catch (Exception $e) {
	$exitCode = 3;
	tm_stdout('ERROR: '.$e->getMessage());
}

if ($lock && function_exists('lt_lock_release')) {
	lt_lock_release($lock);
}

exit($exitCode);

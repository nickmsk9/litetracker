<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: RSS-лента новостей
===================================================================
*/

require 'system/init.php';

function rss_xml($value)
{
	return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function rss_site_url($path = '')
{
	global $config;

	$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
	if ($host === '') {
		$host = trim((string) ($config['public_host'] ?? 'localhost'));
	}

	$scheme = 'http';
	if (
		(!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
	) {
		$scheme = 'https';
	} elseif (!empty($config['announce_url']) && preg_match('~^https://~i', (string) $config['announce_url'])) {
		$scheme = 'https';
	}

	$path = ltrim((string) $path, '/');

	return $scheme.'://'.$host.'/'.($path !== '' ? $path : '');
}

function rss_pub_date($date)
{
	$timestamp = strtotime((string) $date);
	if ($timestamp === false) {
		$timestamp = time();
	}

	return date(DATE_RSS, $timestamp);
}

if (ob_get_length()) {
	ob_clean();
}

header('Content-Type: application/rss+xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$siteTitle = trim((string) ($config['sitename'] ?? 'LiteTracker'));
if ($siteTitle === '') {
	$siteTitle = 'LiteTracker';
}

$items = array();
$latestDate = date(DATE_RSS);
$query = $db->query("SELECT id, name, text, date FROM news ORDER BY date DESC LIMIT 20");
while ($row = $db->get_row($query)) {
	$row['name'] = lt_fix_utf8_mojibake((string) $row['name']);
	$row['text'] = lt_fix_utf8_mojibake((string) $row['text']);
	$items[] = $row;
}
$db->free($query);

if (!empty($items[0]['date'])) {
	$latestDate = rss_pub_date($items[0]['date']);
}

echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rss version="2.0">
	<channel>
		<title><?=rss_xml($siteTitle);?> - Новости</title>
		<link><?=rss_xml(rss_site_url('news.php'));?></link>
		<description><?=rss_xml('Последние новости '.$siteTitle);?></description>
		<language>ru</language>
		<lastBuildDate><?=rss_xml($latestDate);?></lastBuildDate>
		<generator>LiteTracker</generator>
<?php foreach ($items as $item) { ?>
<?php
	$itemUrl = rss_site_url('news.php?id='.(int) $item['id']);
	$title = trim((string) $item['name']);
	if ($title === '') {
		$title = 'Новость #'.(int) $item['id'];
	}
	$description = cleanhtml((string) $item['text']);
?>
		<item>
			<title><?=rss_xml($title);?></title>
			<link><?=rss_xml($itemUrl);?></link>
			<guid isPermaLink="true"><?=rss_xml($itemUrl);?></guid>
			<pubDate><?=rss_xml(rss_pub_date($item['date']));?></pubDate>
			<description><?=rss_xml($description);?></description>
		</item>
<?php } ?>
	</channel>
</rss>

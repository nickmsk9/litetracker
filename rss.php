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

require __DIR__ . '/app/system/init.php';

function rss_xml_text($value)
{
	$value = lt_fix_utf8_mojibake((string) $value);
	if ($value === '') {
		return '';
	}

	if (function_exists('mb_convert_encoding')) {
		$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
	}

	// XML 1.0 allows tab, LF, CR and visible Unicode ranges only.
	$value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);

	return (is_string($value) ? $value : '');
}

function rss_xml($value)
{
	return htmlspecialchars(rss_xml_text($value), ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
}

function rss_site_url($path = '')
{
	global $config;

	$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
	$scheme = '';

	if ($host !== '') {
		$host = preg_replace('~[^a-z0-9.\-:\[\]]~i', '', $host);
	}

	if (
		(!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
	) {
		$scheme = 'https';
	} elseif (!empty($_SERVER['REQUEST_SCHEME']) && in_array(strtolower((string) $_SERVER['REQUEST_SCHEME']), array('http', 'https'), true)) {
		$scheme = strtolower((string) $_SERVER['REQUEST_SCHEME']);
	}

	if (($host === '' || $scheme === '') && !empty($config['announce_url']) && preg_match('~^(https?)://([^/]+)~i', (string) $config['announce_url'], $match)) {
		if ($scheme === '') {
			$scheme = strtolower($match[1]);
		}
		if ($host === '') {
			$host = $match[2];
		}
	}

	if ($scheme === '') {
		$scheme = 'http';
	}
	if ($host === '') {
		$host = 'localhost';
	}

	$path = str_replace('\\', '/', ltrim((string) $path, '/'));

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

function rss_description($value)
{
	$html = cleanhtml(rss_xml_text($value));
	$text = trim(preg_replace('~\s+~u', ' ', strip_tags((string) $html)));

	return ($text !== '' ? $text : '');
}

while (ob_get_level() > 0) {
	ob_end_clean();
}

header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$siteTitle = trim((string) ($config['sitename'] ?? 'LiteTracker'));
if ($siteTitle === '') {
	$siteTitle = 'LiteTracker';
}

$items = array();
$latestDate = date(DATE_RSS);
$query = $db->query("SELECT id, name, text, date FROM news ORDER BY date DESC LIMIT 20");
if ($query) {
	while ($row = $db->get_row($query)) {
		$row['name'] = rss_xml_text($row['name'] ?? '');
		$row['text'] = rss_xml_text($row['text'] ?? '');
		$items[] = $row;
	}
	$db->free($query);
}

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
	$description = rss_description((string) $item['text']);
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

<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: Экспериментальный граббер русских описаний релизов
===================================================================
*/

function lt_metadata_plain_description($text)
{
	$text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = strip_tags($text);
	$text = preg_replace('~\[/?(?:b|i|u|s|center|url|img|quote|code|size|color|font)[^\]]*\]~iu', '', $text);
	$text = preg_replace('~\[\d+\]~u', '', $text);
	$text = preg_replace('~^\s*(?:Тип|Год|Жанр|Качество|Видео|Аудио)\s*:.*$~imu', '', $text);
	$text = preg_replace('~[ \t]+~u', ' ', $text);
	$text = preg_replace('~\h*\R\h*~u', "\n", $text);
	$text = preg_replace('~\R{3,}~u', "\n\n", $text);
	$text = trim((string) $text);

	if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > 2600) {
		$cut = mb_substr($text, 0, 2600, 'UTF-8');
		$sentenceEnd = max(
			(int) mb_strrpos($cut, '.', 0, 'UTF-8'),
			(int) mb_strrpos($cut, '!', 0, 'UTF-8'),
			(int) mb_strrpos($cut, '?', 0, 'UTF-8')
		);
		$text = trim(mb_substr($cut, 0, $sentenceEnd > 1200 ? $sentenceEnd + 1 : 2500, 'UTF-8'));
	} elseif (!function_exists('mb_strlen') && strlen($text) > 3000) {
		$text = trim(substr($text, 0, 3000));
	}

	return $text;
}

function lt_metadata_provider_for_category($category)
{
	$category = trim((string) $category);
	$key = function_exists('lt_torrent_description_template_key') ? lt_torrent_description_template_key($category) : '';
	$normalized = function_exists('mb_strtolower') ? mb_strtolower($category, 'UTF-8') : strtolower($category);

	if ($key === 'anime' || strpos($normalized, 'аниме') !== false || strpos($normalized, 'anime') !== false) {
		return 'anime';
	}

	return 'wikipedia';
}

function lt_metadata_cache_key($provider, $title, $year = null, $category = '')
{
	return 'metadata:'.$provider.':'.sha1((string) $title.'|'.(string) $year.'|'.(string) $category);
}

function lt_metadata_cache_get($provider, $title, $year = null, $category = '')
{
	global $memcached;

	if (!isset($memcached) || !is_object($memcached) || !method_exists($memcached, 'get')) {
		return false;
	}

	return $memcached->get(lt_metadata_cache_key($provider, $title, $year, $category));
}

function lt_metadata_cache_set($provider, $title, $year, $category, $value)
{
	global $memcached;

	if (!isset($memcached) || !is_object($memcached) || !method_exists($memcached, 'set')) {
		return false;
	}

	return $memcached->set(lt_metadata_cache_key($provider, $title, $year, $category), $value, 0, 86400);
}

function lt_metadata_http_json($url, $timeout = 4)
{
	$timeout = max(1, min(8, (int) $timeout));
	$body = false;
	$status = 0;

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => $timeout,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_USERAGENT => 'LiteTracker Metadata Grabber/1.0 (no-key wikipedia provider)',
			CURLOPT_HTTPHEADER => array('Accept: application/json'),
		));
		$body = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	} else {
		$context = stream_context_create(array(
			'http' => array(
				'timeout' => $timeout,
				'header' => "Accept: application/json\r\nUser-Agent: LiteTracker Metadata Grabber/1.0 (no-key wikipedia provider)\r\n",
			),
		));
		$body = @file_get_contents($url, false, $context);
		$headers = (function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : array());
		if (!empty($headers[0]) && preg_match('~\s(\d{3})\s~', (string) $headers[0], $match)) {
			$status = (int) $match[1];
		}
	}

	if ($body === false || $body === '') {
		return array('ok' => false, 'status' => $status, 'json' => null);
	}

	$json = json_decode((string) $body, true);
	if (!is_array($json)) {
		return array('ok' => false, 'status' => $status, 'json' => null);
	}

	return array('ok' => ($status === 0 || ($status >= 200 && $status < 300)), 'status' => $status, 'json' => $json);
}

function lt_metadata_item(array $data)
{
	$description = lt_metadata_plain_description($data['description'] ?? '');
	$lang = trim((string) ($data['description_lang'] ?? ($description !== '' ? 'ru' : '')));

	return array(
		'provider' => trim((string) ($data['provider'] ?? 'wikipedia')),
		'external_id' => trim((string) ($data['external_id'] ?? '')),
		'title' => lt_metadata_plain_description($data['title'] ?? ''),
		'year' => trim((string) ($data['year'] ?? '')),
		'description' => $description,
		'description_lang' => $lang,
		'source_url' => filter_var((string) ($data['source_url'] ?? ''), FILTER_VALIDATE_URL) ? (string) $data['source_url'] : '',
		'score' => (float) ($data['score'] ?? 0),
		'warning' => lt_metadata_plain_description($data['warning'] ?? ''),
	);
}

function lt_metadata_add_unique(&$items, array $item)
{
	$item = lt_metadata_item($item);
	$key = ($item['source_url'] !== '' ? $item['source_url'] : $item['provider'].':'.$item['external_id'].':'.$item['title']);

	foreach ($items as $existing) {
		$existingKey = (!empty($existing['source_url']) ? $existing['source_url'] : $existing['provider'].':'.$existing['external_id'].':'.$existing['title']);
		if ($existingKey === $key) {
			return;
		}
	}

	$items[] = $item;
}

function lt_metadata_wikipedia_search($query, $limit = 5)
{
	$url = 'https://ru.wikipedia.org/w/api.php?'.http_build_query(array(
		'action' => 'query',
		'list' => 'search',
		'srsearch' => $query,
		'srlimit' => max(1, min(10, (int) $limit)),
		'format' => 'json',
		'utf8' => 1,
	));
	$response = lt_metadata_http_json($url, 4);
	if (!$response['ok']) {
		return array();
	}

	return (array) ($response['json']['query']['search'] ?? array());
}

function lt_metadata_wikipedia_summary($title)
{
	$url = 'https://ru.wikipedia.org/w/api.php?'.http_build_query(array(
		'action' => 'query',
		'prop' => 'extracts|info',
		'exintro' => 1,
		'explaintext' => 1,
		'inprop' => 'url',
		'redirects' => 1,
		'format' => 'json',
		'utf8' => 1,
		'titles' => $title,
	));
	$response = lt_metadata_http_json($url, 4);
	if (!$response['ok']) {
		return array();
	}

	$pages = (array) ($response['json']['query']['pages'] ?? array());
	foreach ($pages as $page) {
		if (!empty($page['missing'])) {
			continue;
		}

		return (array) $page;
	}

	return array();
}

function lt_metadata_year_from_text($text)
{
	if (preg_match('~\b(19|20)\d{2}\b~u', (string) $text, $match)) {
		return $match[0];
	}

	return '';
}

function lt_metadata_search_wikipedia($title, $year = null, $category = '')
{
	$title = trim((string) $title);
	if ($title === '') {
		return array();
	}

	$cached = lt_metadata_cache_get('wikipedia', $title, $year, $category);
	if (is_array($cached)) {
		return $cached;
	}

	$queries = array();
	if ($year !== null && (int) $year > 0) {
		$queries[] = $title.' '.$year;
	}
	$queries[] = $title;
	$items = array();
	$position = 0;

	foreach (array_values(array_unique($queries)) as $query) {
		foreach (lt_metadata_wikipedia_search($query, 6) as $result) {
			$pageTitle = trim((string) ($result['title'] ?? ''));
			if ($pageTitle === '') {
				continue;
			}

			$page = lt_metadata_wikipedia_summary($pageTitle);
			$description = lt_metadata_plain_description($page['extract'] ?? '');
			if ($description === '') {
				continue;
			}

			$pageYear = lt_metadata_year_from_text($pageTitle.' '.$description);
			$score = 0.9 - ($position * 0.04);
			if ($year !== null && (int) $year > 0 && $pageYear === (string) $year) {
				$score += 0.15;
			}
			if ($pageTitle === $title) {
				$score += 0.1;
			}

			lt_metadata_add_unique($items, array(
				'provider' => 'wikipedia',
				'external_id' => (string) ($page['pageid'] ?? $pageTitle),
				'title' => (string) ($page['title'] ?? $pageTitle),
				'year' => $pageYear,
				'description' => $description,
				'description_lang' => 'ru',
				'source_url' => (string) ($page['fullurl'] ?? ('https://ru.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $pageTitle)))),
				'score' => $score,
			));
			$position++;
		}
	}

	usort($items, function ($a, $b) {
		return ($b['score'] <=> $a['score']);
	});
	$items = array_slice($items, 0, 8);
	lt_metadata_cache_set('wikipedia', $title, $year, $category, $items);

	return $items;
}

function lt_metadata_search_wikidata($title, $year = null, $category = '')
{
	$title = trim((string) $title);
	if ($title === '') {
		return array();
	}

	$cached = lt_metadata_cache_get('wikidata', $title, $year, $category);
	if (is_array($cached)) {
		return $cached;
	}

	$queries = array();
	if ($year !== null && (int) $year > 0) {
		$queries[] = $title.' '.$year;
	}
	$queries[] = $title;
	$entityIds = array();

	foreach (array_values(array_unique($queries)) as $query) {
		$url = 'https://www.wikidata.org/w/api.php?'.http_build_query(array(
			'action' => 'wbsearchentities',
			'search' => $query,
			'language' => 'ru',
			'uselang' => 'ru',
			'limit' => 5,
			'format' => 'json',
		));
		$response = lt_metadata_http_json($url, 4);
		if (!$response['ok']) {
			continue;
		}
		foreach ((array) ($response['json']['search'] ?? array()) as $row) {
			if (!empty($row['id'])) {
				$entityIds[(string) $row['id']] = (string) $row['id'];
			}
		}
	}

	if (!$entityIds) {
		return array();
	}

	$url = 'https://www.wikidata.org/w/api.php?'.http_build_query(array(
		'action' => 'wbgetentities',
		'ids' => implode('|', array_slice(array_values($entityIds), 0, 8)),
		'props' => 'labels|descriptions|sitelinks',
		'languages' => 'ru|en',
		'sitefilter' => 'ruwiki|enwiki',
		'format' => 'json',
	));
	$response = lt_metadata_http_json($url, 4);
	if (!$response['ok']) {
		return array();
	}

	$items = array();
	$position = 0;
	foreach ((array) ($response['json']['entities'] ?? array()) as $entityId => $entity) {
		$ruTitle = (string) ($entity['sitelinks']['ruwiki']['title'] ?? '');
		$enTitle = (string) ($entity['sitelinks']['enwiki']['title'] ?? '');
		$label = (string) ($entity['labels']['ru']['value'] ?? $entity['labels']['en']['value'] ?? $entityId);

		if ($ruTitle !== '') {
			$page = lt_metadata_wikipedia_summary($ruTitle);
			$description = lt_metadata_plain_description($page['extract'] ?? '');
			if ($description !== '') {
				lt_metadata_add_unique($items, array(
					'provider' => 'wikidata',
					'external_id' => (string) $entityId,
					'title' => (string) ($page['title'] ?? $label),
					'year' => lt_metadata_year_from_text($ruTitle.' '.$description),
					'description' => $description,
					'description_lang' => 'ru',
					'source_url' => (string) ($page['fullurl'] ?? ('https://ru.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $ruTitle)))),
					'score' => 0.78 - ($position * 0.03),
				));
				$position++;
			}
			continue;
		}

		if ($enTitle !== '') {
			lt_metadata_add_unique($items, array(
				'provider' => 'wikidata',
				'external_id' => (string) $entityId,
				'title' => $label,
				'year' => '',
				'description' => '',
				'description_lang' => 'en',
				'source_url' => 'https://en.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $enTitle)),
				'score' => 0.25,
				'warning' => 'Найдена только английская Wikipedia-страница. Русское описание не найдено.',
			));
		}
	}

	lt_metadata_cache_set('wikidata', $title, $year, $category, $items);

	return $items;
}

function lt_metadata_search_shikimori($title, $year = null)
{
	$title = trim((string) $title);
	if ($title === '') {
		return array();
	}

	$cached = lt_metadata_cache_get('shikimori', $title, $year, 'anime');
	if (is_array($cached)) {
		return $cached;
	}

	$url = 'https://shikimori.one/api/animes?'.http_build_query(array(
		'search' => $title,
		'limit' => 5,
	));
	$response = lt_metadata_http_json($url, 4);
	if (!$response['ok']) {
		return array();
	}

	$items = array();
	foreach ((array) $response['json'] as $row) {
		$id = (int) ($row['id'] ?? 0);
		if ($id <= 0) {
			continue;
		}
		$detail = lt_metadata_http_json('https://shikimori.one/api/animes/'.$id, 4);
		if (!$detail['ok']) {
			continue;
		}
		$data = (array) $detail['json'];
		$description = lt_metadata_plain_description($data['description'] ?? '');
		if ($description === '') {
			continue;
		}
		$itemYear = lt_metadata_year_from_text((string) ($data['aired_on'] ?? ''));
		$score = 0.95;
		if ($year !== null && (int) $year > 0 && $itemYear === (string) $year) {
			$score += 0.1;
		}
		lt_metadata_add_unique($items, array(
			'provider' => 'shikimori',
			'external_id' => (string) $id,
			'title' => (string) ($data['russian'] ?? $data['name'] ?? ''),
			'year' => $itemYear,
			'description' => $description,
			'description_lang' => 'ru',
			'source_url' => !empty($data['url']) ? 'https://shikimori.one'.$data['url'] : 'https://shikimori.one/animes/'.$id,
			'score' => $score,
		));
	}

	lt_metadata_cache_set('shikimori', $title, $year, 'anime', $items);

	return $items;
}

function lt_metadata_search_jikan($title, $year = null)
{
	$title = trim((string) $title);
	if ($title === '') {
		return array();
	}

	$cached = lt_metadata_cache_get('jikan', $title, $year, 'anime');
	if (is_array($cached)) {
		return $cached;
	}

	$url = 'https://api.jikan.moe/v4/anime?'.http_build_query(array(
		'q' => $title,
		'limit' => 5,
		'sfw' => 'true',
	));
	$response = lt_metadata_http_json($url, 4);
	if (!$response['ok']) {
		return array();
	}

	$items = array();
	foreach ((array) ($response['json']['data'] ?? array()) as $row) {
		$itemYear = (int) ($row['year'] ?? $row['aired']['prop']['from']['year'] ?? 0);
		if ($year !== null && (int) $year > 0 && $itemYear > 0 && (int) $year !== $itemYear) {
			continue;
		}
		$description = lt_metadata_plain_description($row['synopsis'] ?? '');
		lt_metadata_add_unique($items, array(
			'provider' => 'jikan',
			'external_id' => (string) ($row['mal_id'] ?? ''),
			'title' => (string) ($row['title'] ?? $row['title_english'] ?? ''),
			'year' => ($itemYear > 0 ? (string) $itemYear : ''),
			'description' => $description,
			'description_lang' => ($description !== '' ? 'en' : ''),
			'source_url' => (string) ($row['url'] ?? ''),
			'score' => isset($row['score']) ? (float) $row['score'] / 10 : 0.2,
			'warning' => 'Jikan вернул английское описание. Оно не вставляется без подтверждения.',
		));
	}

	lt_metadata_cache_set('jikan', $title, $year, 'anime', $items);

	return $items;
}

function lt_metadata_search(array $input)
{
	global $config;

	if (isset($config['metadata_grabber_enabled']) && empty($config['metadata_grabber_enabled'])) {
		throw new RuntimeException('Экспериментальный граббер метаданных отключён.');
	}

	$title = trim((string) ($input['title'] ?? ''));
	if ($title === '') {
		throw new InvalidArgumentException('Укажите название релиза.');
	}

	$year = trim((string) ($input['year'] ?? ''));
	$year = preg_match('~^\d{4}$~', $year) ? (int) $year : null;
	$category = trim((string) ($input['category'] ?? ''));
	$provider = lt_metadata_provider_for_category($category);
	$items = array();

	if ($provider === 'anime') {
		foreach (lt_metadata_search_shikimori($title, $year) as $item) {
			lt_metadata_add_unique($items, $item);
		}
		foreach (lt_metadata_search_wikipedia($title, $year, $category) as $item) {
			lt_metadata_add_unique($items, $item);
		}
		foreach (lt_metadata_search_wikidata($title, $year, $category) as $item) {
			lt_metadata_add_unique($items, $item);
		}
		if (!$items) {
			foreach (lt_metadata_search_jikan($title, $year) as $item) {
				lt_metadata_add_unique($items, $item);
			}
		}
	} else {
		foreach (lt_metadata_search_wikipedia($title, $year, $category) as $item) {
			lt_metadata_add_unique($items, $item);
		}
		foreach (lt_metadata_search_wikidata($title, $year, $category) as $item) {
			lt_metadata_add_unique($items, $item);
		}
	}

	usort($items, function ($a, $b) {
		return ($b['score'] <=> $a['score']);
	});

	return array_slice($items, 0, 10);
}

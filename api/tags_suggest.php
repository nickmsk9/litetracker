<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API подсказок тегов
===================================================================
*/

require __DIR__.'/../system/init.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
	header('Allow: GET');
	api_json_error('Метод запроса не поддерживается.', 405);
}

$q = trim((string) ($_GET['q'] ?? ''));
$length = (function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q));

if ($length < 2) {
	api_json_success(array('items' => array()));
}

api_rate_limit('tags_suggest', 60, 60);

if (function_exists('mb_substr')) {
	$q = mb_substr($q, 0, 30, 'UTF-8');
} else {
	$q = substr($q, 0, 30);
}

$limit = 15;
$items = array();

if (lt_table_exists('tags')) {
	$sql = $db->pquery("SELECT MIN(id) AS id, name, SUM(howmuch) AS tag_count
		FROM tags
		WHERE name LIKE ?
		GROUP BY name
		ORDER BY tag_count DESC, name ASC
		LIMIT ".$limit, 's', array($q.'%'), false);

	if ($sql !== false) {
		while ($row = $db->get_row($sql)) {
			$name = trim((string) ($row['name'] ?? ''));
			if ($name === '') {
				continue;
			}

			$items[] = array(
				'id' => (int) ($row['id'] ?? 0),
				'name' => $name,
				'count' => (int) ($row['tag_count'] ?? 0),
			);
		}
		$db->free($sql);
	}
}

api_json_success(array('items' => $items));

<?php

function browse_suggest_response($term, $categories, $userId)
{
	global $db;

	$term = trim((string) $term);
	$safeTermLike = ($term !== '' ? sqlwildcardesc($term) : '');
	$recent = array();
	$popular = array();
	$quickTags = array();
	$quickCategories = array();

	if ($userId > 0) {
		$sqlRecent = $db->query(
			"SELECT text
			 FROM search_query
			 WHERE id_user = ".(int) $userId." ".($safeTermLike !== '' ? " AND text LIKE '%".$safeTermLike."%'" : '')."
			 ORDER BY last_date DESC
			 LIMIT 6"
		);
		while ($rowRecent = $db->get_row($sqlRecent)) {
			$text = trim((string) ($rowRecent['text'] ?? ''));
			if ($text !== '') {
				$recent[] = $text;
			}
		}
	}

	$sqlPopular = $db->query(
		"SELECT text, SUM(num_views) AS views, MAX(last_date) AS latest_date
		 FROM search_query
		 WHERE text <> '' ".($safeTermLike !== '' ? " AND text LIKE '%".$safeTermLike."%'" : '')."
		 GROUP BY text
		 ORDER BY views DESC, latest_date DESC
		 LIMIT 8"
	);
	while ($rowPopular = $db->get_row($sqlPopular)) {
		$text = trim((string) ($rowPopular['text'] ?? ''));
		if ($text !== '') {
			$popular[] = $text;
		}
	}

	foreach ((array) lt_tags_popular(12) as $tagRow) {
		$tagName = trim((string) ($tagRow['name'] ?? ''));
		if ($tagName === '') {
			continue;
		}
		if ($term !== '' && stripos($tagName, $term) === false) {
			continue;
		}
		$quickTags[] = $tagName;
	}

	foreach ((array) $categories as $category) {
		$categoryName = trim((string) ($category['name'] ?? ''));
		if ($categoryName === '') {
			continue;
		}
		if ($term !== '' && stripos($categoryName, $term) === false) {
			continue;
		}
		$quickCategories[] = array(
			'id' => (int) ($category['id'] ?? 0),
			'name' => $categoryName,
		);
	}

	return array(
		'ok' => 1,
		'recent' => array_values(array_unique($recent)),
		'popular' => array_values(array_unique($popular)),
		'tags' => array_values(array_unique($quickTags)),
		'categories' => array_values($quickCategories),
	);
}

<?php
/*
===================================================================
-------------------------------------------------------------------
Назначение: Функции тегов
===================================================================
*/

function get_tags()
{
	global $db;

	$arr = array();

	$cacheKey = lt_cache_key_tags_all();
	$cacheNs  = lt_cache_key_tags_ns();
	$res = lt_cache_get($cacheKey, $cacheNs);

	if ($res === false || !is_array($res)) {
		$query = $db->query("SELECT name, howmuch FROM tags WHERE howmuch > 0 ORDER BY RAND() LIMIT 15");
		$tags_cache = array();

		while ($cache_data = $db->get_row($query)) {
			if (is_array($cache_data)) {
				$tags_cache[] = $cache_data;
			}
		}

		lt_cache_set($cacheKey, $tags_cache, 24 * 60 * 60, $cacheNs);
		$res = $tags_cache;
	}

	foreach ($res as $row) {
		if (!is_array($row)) {
			continue;
		}

		$name = isset($row['name']) ? trim((string)$row['name']) : '';
		$howmuch = isset($row['howmuch']) ? (int)$row['howmuch'] : 0;

		if ($name !== '') {
			$arr[$name] = $howmuch;
		}
	}

	return $arr;
}

function lt_tags_popular($limit = 30)
{
	global $db;

	$limit = max(1, min(100, (int) $limit));
	$cacheKey = lt_cache_key_tags_popular($limit);
	$cacheNs  = lt_cache_key_tags_ns();
	$ttl = 600;

	$cached = lt_cache_get($cacheKey, $cacheNs);

	if (is_array($cached)) {
		return $cached;
	}

	$tags = array();
	if (!function_exists('lt_table_exists') || lt_table_exists('tags')) {
		$sql = $db->query("SELECT name, SUM(howmuch) AS tag_count
			FROM tags
			WHERE name <> '' AND howmuch > 0
			GROUP BY name
			ORDER BY tag_count DESC, name ASC
			LIMIT ".$limit);

		while ($row = $db->get_row($sql)) {
			$name = trim((string) ($row['name'] ?? ''));
			if ($name === '') {
				continue;
			}

			$tags[] = array(
				'name' => $name,
				'count' => (int) ($row['tag_count'] ?? 0),
			);
		}
		$db->free($sql);
	}

	lt_cache_set($cacheKey, $tags, $ttl, $cacheNs);

	return $tags;
}

function cloud($small, $big, $colour = true)
{
	$tags = get_tags();

	if (empty($tags)) {
		$data = "Нет тэгов";
	} else {
		$minimum_count = min(array_values($tags));
		$maximum_count = max(array_values($tags));
		$spread = $maximum_count - $minimum_count;

		if ($spread == 0) {
			$spread = 1;
		}

		$data = '';
		$cloud = array();

		foreach ($tags as $tag => $count) {
			if (!empty($tag)) {
				$size = $small + ($count - $minimum_count) * ($big - $small) / $spread;
				$colours = array('#003EFF', '#0000FF', '#7EB6FF', '#0099CC', '#62B1F6');

				$cloud[] = "<a href=\"browse.php?tag=";
				$cloud[] = urlencode($tag);
				$cloud[] = "\" style=\"" . ($colour ? "color:" . $colours[mt_rand(0, 4)] . "; " : "") . "font-size:" . floor($size) . "px;\" rel=\"tag\" title=\"Содержится в " . (int)$count . " торрентах\">";
				$cloud[] = htmlentities($tag, ENT_QUOTES, 'UTF-8') . "(" . (int)$count . ")</a>\n";
			}
		}

		$data = join($cloud);
		unset($cloud);
	}

	return $data;
}

function simple_cloud($small, $big)
{
	$data = '<style>
        #tag_cloud a {padding: 3px; text-decoration: none; font-family: verdana; font-weight: normal;}
        #tag_cloud a:link {text-decoration: none; border: 1px solid transparent;}
        #tag_cloud a:visited {border: 1px solid transparent;}
        #tag_cloud a:hover {background: #ddd; border: 1px solid #bbb;}
        #tag_cloud a:active {background: #fff; border: 1px solid transparent;}
        #tag_cloud p {line-height: 28px; text-align: justify;}
        #tag_cloud {width:90%}
        </style>';
	$data .= '<div id="tag_cloud">';
	$data .= '<p>' . cloud($small, $big, true) . '</p>';
	$data .= '</div>';

	return $data;
}

// Вывод тегов
function get_tags_type()
{
	return simple_cloud(15, 20);
}

// Вывод тегов к релизу
function tags_echo($addtags)
{
	$tags = '';

	$addtags = trim((string)$addtags);

	if ($addtags === '') {
		return 'Нет тэгов';
	}

	$tag_list = explode(',', $addtags);
	$result = array();

	foreach ($tag_list as $tag) {
		$tag = trim($tag);

		if ($tag === '') {
			continue;
		}

		$result[] = "<a style=\"font-weight:normal;\" href=\"browse.php?tag=" . urlencode($tag) . "\">" . htmlspecialchars_uni($tag) . "</a>";
	}

	if (!empty($result)) {
		$tags = implode(', ', $result);
	}

	if ($tags === '') {
		$tags = 'Нет тэгов';
	}

	return $tags;
}

?>

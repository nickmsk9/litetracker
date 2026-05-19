<?php

function browse_search_tokens($search)
{
	$parts = preg_split('~\s+~u', trim((string) $search));
	$tokens = array();

	foreach ((array) $parts as $part) {
		$part = trim((string) $part);
		if ($part === '' || (function_exists('mb_strlen') && mb_strlen($part, 'UTF-8') < 2)) {
			continue;
		}
		$tokens[] = $part;
	}

	return array_values(array_unique($tokens));
}

function browse_search_build_clause($search)
{
	global $db;

	$search = trim((string) $search);
	if ($search === '') {
		return array('where' => '', 'score' => '0');
	}

	$safe = $db->safesql($search);
	$safeLike = sqlwildcardesc($search);
	$tokens = browse_search_tokens($search);
	$fields = array(
		't.name',
		't.tags',
		't.descr',
		't.genres',
		't.countries',
		't.languages',
		't.subtitles',
		't.meta_info',
		't.content_type',
		'u.name',
	);

	$whereParts = array();
	$scoreParts = array(
		"(CASE WHEN t.name = '".$safe."' THEN 180 ELSE 0 END)",
		"(CASE WHEN t.name LIKE '".$safeLike."%' THEN 120 ELSE 0 END)",
		"(CASE WHEN t.name LIKE '%".$safeLike."%' THEN 85 ELSE 0 END)",
		"(CASE WHEN t.tags LIKE '%".$safeLike."%' THEN 55 ELSE 0 END)",
		"(CASE WHEN t.genres LIKE '%".$safeLike."%' OR t.countries LIKE '%".$safeLike."%' THEN 45 ELSE 0 END)",
		"(CASE WHEN t.descr LIKE '%".$safeLike."%' THEN 30 ELSE 0 END)",
		"(CASE WHEN u.name LIKE '%".$safeLike."%' THEN 40 ELSE 0 END)",
	);

	foreach ($fields as $field) {
		$whereParts[] = $field." LIKE '%".$safeLike."%'";
	}

	foreach ($tokens as $token) {
		$safeTokenLike = sqlwildcardesc($token);
		$tokenWhere = array();
		foreach ($fields as $field) {
			$tokenWhere[] = $field." LIKE '%".$safeTokenLike."%'";
		}
		$whereParts[] = '('.implode(' OR ', $tokenWhere).')';

		$scoreParts[] = "(CASE WHEN t.name LIKE '%".$safeTokenLike."%' THEN 18 ELSE 0 END)";
		$scoreParts[] = "(CASE WHEN t.tags LIKE '%".$safeTokenLike."%' OR t.genres LIKE '%".$safeTokenLike."%' THEN 12 ELSE 0 END)";
	}

	if (preg_match('~\b(19|20)\d{2}\b~', $search, $match)) {
		$year = $db->safesql($match[0]);
		$whereParts[] = "(t.descr LIKE '%".$year."%' OR t.added LIKE '".$year."-%')";
		$scoreParts[] = "(CASE WHEN t.descr LIKE '%".$year."%' THEN 25 ELSE 0 END)";
	}

	return array(
		'where' => '('.implode(' OR ', $whereParts).')',
		'score' => '('.implode(' + ', $scoreParts).')',
	);
}

function browse_schema_with_actual_options($schema, $baseWhere, $selectedFilters, $joins = array())
{
	global $db;

	$columns = array();
	foreach ($schema as $group => $definition) {
		if (!empty($definition['column'])) {
			$columns[$group] = 't.'.$definition['column'];
		}
	}

	if (!$columns) {
		return $schema;
	}

	$select = array();
	foreach ($columns as $group => $column) {
		$select[] = $column.' AS meta_'.$group;
	}

	$joinSql = ($joins ? "\n\t".implode("\n\t", array_values(array_unique($joins))) : '');
	$cacheKey = 'browse:facet-counts:'.md5(implode('|', $baseWhere).'|'.implode('|', $joins).'|'.implode('|', array_keys($schema)));
	$counts = (function_exists('lt_cache_remember')
		? lt_cache_remember($cacheKey, 60, function () use ($db, $schema, $select, $baseWhere, $joinSql) {
			$localCounts = array();
			$sql = $db->query('SELECT '.implode(', ', $select).' FROM torrents AS t '.$joinSql.' '.($baseWhere ? 'WHERE '.implode(' AND ', $baseWhere) : ''));
			while ($row = $db->get_row($sql)) {
				foreach ($schema as $group => $definition) {
					$key = 'meta_'.$group;
					$raw = trim((string) ($row[$key] ?? ''));
					if ($raw === '') {
						continue;
					}

					$values = ($group === 'type' ? array($raw) : browse_parse_tags($raw));
					foreach ($values as $value) {
						if (!isset($definition['options'][$value])) {
							continue;
						}
						if (!isset($localCounts[$group][$value])) {
							$localCounts[$group][$value] = 0;
						}
						$localCounts[$group][$value]++;
					}
				}
			}

			return $localCounts;
		}, 'browse')
		: array());

	foreach ($schema as $group => $definition) {
		$schema[$group]['counts'] = (array) ($counts[$group] ?? array());
	}

	return $schema;
}

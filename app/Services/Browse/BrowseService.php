<?php

function browse_sort_options()
{
	return array(
		'date' => array('label' => 'Дата', 'order' => 't.added DESC'),
		'size' => array('label' => 'Размер', 'order' => 't.size DESC, t.added DESC'),
		'seeders' => array('label' => 'Раздающие', 'order' => 'seeders DESC, t.added DESC'),
		'name' => array('label' => 'А - Я', 'order' => 't.name ASC'),
	);
}

function browse_parse_request($query, $server, $user)
{
	$search = trim((string) ($query['search'] ?? ''));
	$activeTagRaw = trim((string) ($query['tag'] ?? ''));
	$activeTagParts = ($activeTagRaw !== '' ? lt_torrent_tags_from_string($activeTagRaw) : array());
	$activeTag = trim((string) ($activeTagParts[0] ?? ''));
	$idCategory = isset($query['id_category']) ? (int) $query['id_category'] : 0;
	$sort = trim((string) ($query['sort'] ?? 'date'));
	$sortOptions = browse_sort_options();
	if (empty($sortOptions[$sort])) {
		$sort = 'date';
	}

	$view = (string) ($query['view'] ?? 'compact');
	$view = ($view === 'full' ? 'full' : 'compact');
	$isAjaxRequest = browse_detect_ajax_request($server, $query);

	return array(
		'search' => $search,
		'activeTag' => $activeTag,
		'id_category' => $idCategory,
		'sort' => $sort,
		'view' => $view,
		'isAjaxRequest' => $isAjaxRequest,
		'isSuggestRequest' => ($isAjaxRequest && (string) ($query['mode'] ?? '') === 'suggest'),
		'searchRateLimitId' => ($user ? 'user:'.$user['id'] : 'ip:'.($server['REMOTE_ADDR'] ?? 'cli')),
		'quickFilters' => array(
			'status' => trim((string) ($query['status'] ?? '')),
			'with_screens' => browse_bool_param('with_screens', $query),
			'freeleech' => browse_bool_param('freeleech', $query),
			'bookmarked' => browse_bool_param('bookmarked', $query),
			'completed' => browse_bool_param('completed', $query),
			'alive' => (trim((string) ($query['alive'] ?? '')) === 'alive'),
			'dead' => (trim((string) ($query['alive'] ?? '')) === 'dead'),
		),
	);
}

function browse_check_search_rate_limit($search, $rateLimitId, $isAjaxRequest)
{
	if ($search === '') {
		return null;
	}

	$searchRateLimit = lt_rate_limit_hit('search', $rateLimitId, 30, 5 * 60);
	if (empty($searchRateLimit['blocked'])) {
		return null;
	}

	$message = 'Слишком много поисковых запросов. Попробуйте немного позже.';
	if ($isAjaxRequest) {
		return array('ok' => 0, 'message' => $message);
	}

	err('Ошибка', $message, 1);
}

function browse_handle_suggest_request($query, $categories, $user)
{
	return browse_suggest_response(trim((string) ($query['q'] ?? '')), $categories, (int) ($user['id'] ?? 0));
}

function browse_build_page_model($query, $server)
{
	global $db, $USER, $PRIV, $config;

	$request = browse_parse_request($query, $server, $USER);
	$rateLimitResponse = browse_check_search_rate_limit($request['search'], $request['searchRateLimitId'], $request['isAjaxRequest']);
	if ($rateLimitResponse !== null) {
		return array('json' => $rateLimitResponse);
	}

	$schema = lt_torrent_metadata_schema();
	$categories = categories_array();
	$categoriesById = array();
	foreach ($categories as $category) {
		$categoriesById[(int) $category['id']] = $category;
	}

	if ($request['isSuggestRequest']) {
		return array('json' => browse_handle_suggest_request($query, $categories, $USER));
	}

	$currentCategoryName = (!empty($categoriesById[$request['id_category']]['name']) ? (string) $categoriesById[$request['id_category']]['name'] : '');
	if (!empty($schema['type'])) {
		$typeOptions = lt_torrent_metadata_type_options_for_category($currentCategoryName);
		if ($typeOptions) {
			$schema['type']['options'] = $typeOptions;
		} else {
			unset($schema['type']);
		}
	}

	$selectedFilters = browse_collect_selected_filters($schema, $query);
	$baseWhere = array(lt_torrent_status_filter_sql($USER, 't'));
	$having = array();
	$joins = array('LEFT JOIN users AS u ON u.id = t.id_user');
	$detailsBannedView = (!empty($PRIV['details_banned_view']));
	if (!$detailsBannedView && !lt_torrent_can_moderate($USER)) {
		$baseWhere[] = 't.banned <> 1';
	}
	if ($request['id_category'] > 0) {
		$baseWhere[] = 't.id_category = '.$db->safesql($request['id_category']);
	}

	if ($request['search'] !== '') {
		$searchSql = browse_search_build_clause($request['search']);
		if ($searchSql['where'] !== '') {
			$baseWhere[] = $searchSql['where'];
		}
	} else {
		$searchSql = array('where' => '', 'score' => '0');
	}

	if ($request['activeTag'] !== '') {
		$baseWhere[] = "FIND_IN_SET('".$db->safesql($request['activeTag'])."', t.tags) > 0";
	}

	browse_apply_quick_filters($baseWhere, $having, $joins, $request['quickFilters'], (int) ($USER['id'] ?? 0));
	$schema = browse_schema_with_actual_options($schema, $baseWhere, $selectedFilters, $joins);
	$selectedFilters = browse_collect_selected_filters($schema, $query);
	$where = $baseWhere;
	browse_apply_filter_conditions($where, $schema, $selectedFilters);

	$pagerParams = browse_pager_params($request, $selectedFilters);
	$joinSql = ($joins ? "\n\t".implode("\n\t", array_values(array_unique($joins))) : '');
	$whereSql = ($where ? 'WHERE '.implode(' AND ', $where) : '');
	$havingSql = ($having ? 'HAVING '.implode(' AND ', $having) : '');
	$sortOptions = browse_sort_options();
	$orderBy = ($request['search'] !== ''
		? 'search_score DESC, seeders DESC, t.completed DESC, IF(t.news = \'1\', 1, 0) DESC, t.added DESC'
		: $sortOptions[$request['sort']]['order']);
	$pagerHref = 'browse.php'.($pagerParams ? '?'.http_build_query($pagerParams).'&' : '?');
	$perPage = 10;
	$currentPage = isset($query['page']) ? max(0, (int) $query['page']) : 0;
	$limit = 'LIMIT '.($currentPage * $perPage).' , '.$perPage;
	$rows = array();
	$countTorrent = 0;
	$releasesNewsDays = (int) ($config['releases_news'] ?? 0);
	$searchScoreExpr = ($request['search'] !== '' ? $searchSql['score'] : '0');

	$sql = $db->query("SELECT t.*, COUNT(*) OVER() AS total_count, ".$searchScoreExpr." AS search_score,
	COALESCE(SUM(tr.seeders), 0) AS seeders, COALESCE(SUM(tr.leechers), 0) AS leechers,
	COALESCE(SUM(CASE WHEN tr.tracker <> 'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count,
	IF(COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.seeders ELSE 0 END), 0) > 0, true, false) AS local_seeders,
	IF(ADDDATE(t.added, INTERVAL ".$releasesNewsDays." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
	FROM torrents AS t
	".$joinSql."
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	".$whereSql."
	GROUP BY t.id
	".$havingSql."
	ORDER BY ".$orderBy."
	".$limit);

	while ($row = $db->get_row($sql)) {
		$countTorrent = max($countTorrent, (int) ($row['total_count'] ?? 0));
		$rows[] = $row;
	}

	list($pagertop, $pagerbottom) = pager((string) $perPage, $countTorrent, $pagerHref);
	browse_record_search_query($request['search'], $server, $USER, $countTorrent);
	$torrentAuthorsById = lt_torrent_preload_author_users($rows);

	return array(
		'vars' => array(
			'search' => $request['search'],
			'activeTag' => $request['activeTag'],
			'id_category' => $request['id_category'],
			'sort' => $request['sort'],
			'view' => $request['view'],
			'quickFilters' => $request['quickFilters'],
			'sortOptions' => $sortOptions,
			'schema' => $schema,
			'categories' => $categories,
			'categoriesById' => $categoriesById,
			'selectedFilters' => $selectedFilters,
			'rows' => $rows,
			'pagertop' => $pagertop,
			'pagerbottom' => $pagerbottom,
			'torrentAuthorsById' => $torrentAuthorsById,
			'torrentAuthorPrivilegesByClass' => lt_torrent_preload_author_privileges($torrentAuthorsById),
			'popularTags' => lt_tags_popular(30),
			'canUpload' => ($USER && !empty($PRIV['upload'])),
		),
	);
}

function browse_pager_params($request, $selectedFilters)
{
	$pagerParams = array();
	foreach (array('search', 'activeTag', 'id_category') as $field) {
		$key = ($field === 'activeTag' ? 'tag' : $field);
		if (($field === 'id_category' && $request[$field] > 0) || ($field !== 'id_category' && $request[$field] !== '')) {
			$pagerParams[$key] = $request[$field];
		}
	}
	$pagerParams['sort'] = $request['sort'];
	$pagerParams['view'] = $request['view'];
	foreach (array('status', 'with_screens', 'freeleech', 'bookmarked', 'completed') as $key) {
		if (!empty($request['quickFilters'][$key])) {
			$pagerParams[$key] = ($key === 'status' ? $request['quickFilters'][$key] : '1');
		}
	}
	if (!empty($request['quickFilters']['alive'])) {
		$pagerParams['alive'] = 'alive';
	}
	if (!empty($request['quickFilters']['dead'])) {
		$pagerParams['alive'] = 'dead';
	}
	foreach ($selectedFilters as $group => $values) {
		if ($values) {
			$pagerParams['filter_'.$group] = $values;
		}
	}

	return $pagerParams;
}

function browse_record_search_query($search, $server, $user, $countTorrent)
{
	global $db;

	if ($search === '' || substr_count((string) ($server['QUERY_STRING'] ?? ''), 'page') != 0 || strlen($search) < 5 || !$user) {
		return;
	}

	$searchUserId = (int) ($user['id'] ?? -1);
	$safeSearch = sqlwildcardesc($search);
	$checkQuery = $db->super_query("SELECT COUNT(*) AS count FROM search_query WHERE id_user=".$searchUserId." AND text LIKE '%".$safeSearch."%'");
	if (!empty($checkQuery['count'])) {
		$db->query("UPDATE search_query SET last_date = NOW(), num_views = (num_views + 1), num_torrents = ".$countTorrent." WHERE id_user=".$searchUserId." AND text LIKE '%".$safeSearch."%'");
	} else {
		$db->query("INSERT INTO search_query (text, id_user, last_date, num_torrents) VALUES ('".$safeSearch."', ".$searchUserId.", NOW(), ".$countTorrent.')');
	}
}

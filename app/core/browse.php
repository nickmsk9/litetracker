<?php


function browse_parse_tags($value)
{
	$result = array();
	$parts = explode(',', (string) $value);

	foreach ($parts as $part) {
		$part = trim((string) $part);
		if ($part !== '') {
			$result[] = $part;
		}
	}

	return array_values(array_unique($result));
}

function browse_bool_param($name, $source = null)
{
	$source = ($source === null ? $_GET : $source);
	$value = $source[$name] ?? '';
	if (is_array($value)) {
		return false;
	}

	$value = trim((string) $value);

	return ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'on');
}

function browse_collect_selected_filters($schema, $source = null)
{
	$source = ($source === null ? $_GET : $source);
	$result = array();

	foreach ($schema as $group => $definition) {
		$key = 'filter_'.$group;
		$values = (isset($source[$key]) && is_array($source[$key]) ? $source[$key] : array());
		$result[$group] = lt_torrent_metadata_normalize_values($group, $values);
	}

	return $result;
}

function browse_apply_filter_conditions(&$where, $schema, $selectedFilters)
{
	global $db;

	foreach ($schema as $group => $definition) {
		$values = (!empty($selectedFilters[$group]) ? $selectedFilters[$group] : array());
		if (!$values) {
			continue;
		}

		$column = 't.'.$definition['column'];
		$parts = array();

		foreach ($values as $value) {
			$safeValue = $db->safesql($value);
			if ($group === 'type') {
				$parts[] = $column." = '".$safeValue."'";
				continue;
			}

			$parts[] = "FIND_IN_SET('".$safeValue."', ".$column.") > 0";
		}

		if ($parts) {
			$where[] = '('.implode(' OR ', $parts).')';
		}
	}
}

function browse_filter_options_split($options, $selectedValues, $limit = 4)
{
	$visible = array();
	$hidden = array();
	$index = 0;

	foreach ((array) $options as $value => $label) {
		$isSelected = in_array($value, (array) $selectedValues, true);

		if ($index < $limit || $isSelected) {
			$visible[$value] = $label;
		} else {
			$hidden[$value] = $label;
		}

		$index++;
	}

	return array($visible, $hidden);
}

function browse_apply_quick_filters(&$where, &$having, &$joins, $quick, $userId)
{
	global $db;

	if (!empty($quick['status'])) {
		$where[] = "t.status = '".$db->safesql($quick['status'])."'";
	}

	if (!empty($quick['with_screens'])) {
		$where[] = "(COALESCE(t.screen_1,'') <> '' OR COALESCE(t.screen_2,'') <> '' OR COALESCE(t.screen_3,'') <> '' OR COALESCE(t.screen_4,'') <> '')";
	}

	if (!empty($quick['completed'])) {
		$where[] = 't.completed > 0';
	}

	if (!empty($quick['freeleech'])) {
		$where[] = "(t.meta_info LIKE '%freeleech%' OR t.tags LIKE '%freeleech%')";
	}

	if (!empty($quick['bookmarked']) && $userId > 0) {
		$joins[] = 'LEFT JOIN books AS bkm ON bkm.id_torrent = t.id AND bkm.id_user = '.(int) $userId;
		$where[] = 'bkm.id IS NOT NULL';
	}

	if (!empty($quick['alive'])) {
		$having[] = 'seeders > 0';
	} elseif (!empty($quick['dead'])) {
		$having[] = 'seeders = 0';
	}
}

function browse_detect_ajax_request($server = null, $query = null)
{
	$server = ($server === null ? $_SERVER : $server);
	$query = ($query === null ? $_GET : $query);

	if (isset($query['ajax']) && (string) $query['ajax'] === '1') {
		return true;
	}

	$requestedWith = strtolower((string) ($server['HTTP_X_REQUESTED_WITH'] ?? ''));

	return ($requestedWith === 'xmlhttprequest');
}

function browse_build_url($overrides = array(), $drop = array(), $source = null)
{
	$params = ($source === null ? $_GET : $source);

	foreach ($drop as $key) {
		unset($params[$key]);
	}

	foreach ($overrides as $key => $value) {
		if ($value === null || $value === '' || $value === array()) {
			unset($params[$key]);
			continue;
		}

		$params[$key] = $value;
	}

	$query = http_build_query($params);

	return 'browse.php'.($query !== '' ? '?'.$query : '');
}

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

function browse_handle_request(LiteTracker\Http\Request $request)
{
	$model = browse_build_page_model($request->query(), $request->server());
	if (isset($model['json'])) {
		return new LiteTracker\Http\JsonResponse($model['json']);
	}

	return new LiteTracker\Http\Response(browse_render_page($model['vars']));
}

function browse_render_page(array $vars)
{
	extract($vars, EXTR_SKIP);
	if ($activeTag !== '') {
		$_GET['tag'] = $activeTag;
	} else {
		unset($_GET['tag']);
	}

	ob_start();
	head('Торренты');
?>
<div class="browse-page" data-browse-page>
	<section class="browse-hero">
		<div class="browse-hero-copy">
			<h1 class="browse-hero-title">Торренты</h1>
		</div>
		<?php if ($canUpload) { ?>
		<div class="browse-hero-action">
			<a class="browse-upload-button" href="upload.php">
				<span class="browse-upload-button-icon" aria-hidden="true">
					<svg viewBox="0 0 20 20" fill="none">
						<path d="M10 13V4m0 0L6.75 7.25M10 4l3.25 3.25M4 14.5v.5A1 1 0 0 0 5 16h10a1 1 0 0 0 1-1v-.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
				<span>Загрузить торрент</span>
			</a>
		</div>
		<?php } ?>
	</section>

	<div class="browse-layout">
		<div class="browse-main">
			<section class="browse-panel browse-search-panel">
				<form action="browse.php" method="get" class="browse-search-form" data-browse-search-form>
					<?php if ($id_category > 0) { ?>
					<input type="hidden" name="id_category" value="<?=$id_category;?>">
					<?php } ?>
					<?php if ($activeTag !== '') { ?>
					<input type="hidden" name="tag" value="<?=htmlspecialchars($activeTag, ENT_QUOTES, 'UTF-8');?>">
					<?php } ?>
					<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>
					<?php if ($quickFilters['status'] !== '') { ?><input type="hidden" name="status" value="<?=htmlspecialchars($quickFilters['status'], ENT_QUOTES, 'UTF-8');?>"><?php } ?>
					<?php if ($quickFilters['with_screens']) { ?><input type="hidden" name="with_screens" value="1"><?php } ?>
					<?php if ($quickFilters['freeleech']) { ?><input type="hidden" name="freeleech" value="1"><?php } ?>
					<?php if ($quickFilters['bookmarked']) { ?><input type="hidden" name="bookmarked" value="1"><?php } ?>
					<?php if ($quickFilters['completed']) { ?><input type="hidden" name="completed" value="1"><?php } ?>
					<?php if ($quickFilters['alive']) { ?><input type="hidden" name="alive" value="alive"><?php } ?>
					<?php if ($quickFilters['dead']) { ?><input type="hidden" name="alive" value="dead"><?php } ?>
					<?php foreach ($selectedFilters as $group => $values) { ?>
						<?php foreach ($values as $value) { ?>
						<input type="hidden" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>">
						<?php } ?>
					<?php } ?>
					<div class="browse-search-row">
						<input type="text" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>" class="browse-search-input" placeholder="Поиск..." autocomplete="off" data-browse-search-input>
						<button type="submit" class="browse-search-submit">Найти</button>
					</div>
					<div class="browse-search-suggest" data-browse-suggest hidden></div>
				</form>
			</section>

			<?php if ($categories) { ?>
			<nav class="browse-panel browse-categories" aria-label="Категории торрентов">
				<a class="browse-category-tab<?=($id_category === 0 ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('id_category' => null, 'page' => null)), ENT_QUOTES, 'UTF-8');?>">Все торренты</a>
				<?php foreach ($categories as $category) { ?>
				<a class="browse-category-tab<?=($id_category === (int) $category['id'] ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('id_category' => (int) $category['id'], 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>
			</nav>
			<?php } ?>

			<?php if ($popularTags) { ?>
			<nav class="browse-panel browse-tag-cloud" aria-label="Популярные теги">
				<div class="browse-tag-cloud-title">Популярные теги</div>
				<div class="browse-tag-cloud-list">
					<?php foreach ($popularTags as $popularTag) { ?>
					<?php
					$tagName = (string) ($popularTag['name'] ?? '');
					if ($tagName === '') {
						continue;
					}
					$tagCount = (int) ($popularTag['count'] ?? 0);
					$isActiveTag = (function_exists('mb_strtolower') ? mb_strtolower($tagName, 'UTF-8') === mb_strtolower($activeTag, 'UTF-8') : strtolower($tagName) === strtolower($activeTag));
					?>
					<a class="browse-tag-chip<?=($isActiveTag ? ' is-active' : '');?>" href="<?=htmlspecialchars(browse_build_url(array('tag' => $tagName, 'page' => null)), ENT_QUOTES, 'UTF-8');?>" rel="tag">
						<span><?=htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8');?></span>
						<?php if ($tagCount > 0) { ?><span class="browse-tag-count"><?=$tagCount;?></span><?php } ?>
					</a>
					<?php } ?>
				</div>
			</nav>
			<?php } ?>

			<section class="browse-panel browse-results-panel" data-browse-results-panel>
				<div class="home-browse-toolbar">
					<ul class="browse-sort-list" role="tablist" aria-label="Сортировка торрентов">
						<?php foreach ($sortOptions as $sortKey => $sortOption) { ?>
						<li class="browse-sort-item<?=($sort === $sortKey ? ' is-active' : '');?>">
							<a class="browse-sort-link" href="<?=htmlspecialchars(browse_build_url(array('sort' => $sortKey, 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=$sortOption['label'];?></a>
						</li>
						<?php } ?>
					</ul>

					<div class="browse-view-switch" role="group" aria-label="Вид списка">
						<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-medium" aria-hidden="true"></span>
						</button>
						<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-small" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<?php if ($rows) { ?>
				<div class="browse-torrent-list" data-browse-list data-view="<?=$view;?>">
					<?php foreach ($rows as $row) { ?>
					<?php
					$torrentId = (int) $row['id'];
					$category = (!empty($categoriesById[(int) $row['id_category']]) ? $categoriesById[(int) $row['id_category']] : array('id' => 0, 'name' => 'Без категории', 'image' => ''));
					$user = (array) ($torrentAuthorsById[(int) $row['id_user']] ?? array());
					$torrentCard = lt_torrent_prepare_browse_card($row, $category, $user, $torrentAuthorPrivilegesByClass);
					?>
					<?php include lt_templates_path('default/tpl.torrent.card.php'); ?>
					<?php } ?>
				</div>
				<?php } else { ?>
				<div class="browse-empty-state">Торренты не найдены.</div>
				<?php } ?>
			</section>
			<?php if ($rows) { ?>
			<div class="browse-pagination" data-browse-pagination><?=$pagerbottom ?: $pagertop;?></div>
			<?php } ?>
		</div>

		<aside class="browse-sidebar" data-browse-sidebar>
			<form action="browse.php" method="get" class="browse-filter-panel" data-browse-filter-form>
				<?php if ($search !== '') { ?>
				<input type="hidden" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>">
				<?php } ?>
				<?php if ($activeTag !== '') { ?>
				<input type="hidden" name="tag" value="<?=htmlspecialchars($activeTag, ENT_QUOTES, 'UTF-8');?>">
				<?php } ?>
				<?php if ($id_category > 0) { ?>
				<input type="hidden" name="id_category" value="<?=$id_category;?>">
				<?php } ?>
				<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>

				<fieldset class="browse-filter-group">
					<legend class="browse-filter-title">Быстрые фильтры:</legend>
					<div class="browse-filter-options">
						<label class="browse-filter-option">
							<input type="checkbox" name="with_screens" value="1"<?=($quickFilters['with_screens'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Со скриншотами</span>
						</label>
						<label class="browse-filter-option">
							<input type="checkbox" name="completed" value="1"<?=($quickFilters['completed'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Завершённые</span>
						</label>
						<?php if (!empty($USER['id'])) { ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="bookmarked" value="1"<?=($quickFilters['bookmarked'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>В закладках</span>
						</label>
						<?php } ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="freeleech" value="1"<?=($quickFilters['freeleech'] ? ' checked' : '');?> data-browse-auto-filter>
							<span>Freeleech</span>
						</label>
						<label class="browse-filter-option">
							<select name="alive" class="browse-filter-select" data-browse-auto-filter>
								<option value="">Живые и мёртвые</option>
								<option value="alive"<?=($quickFilters['alive'] ? ' selected' : '');?>>Только живые</option>
								<option value="dead"<?=($quickFilters['dead'] ? ' selected' : '');?>>Только мёртвые</option>
							</select>
						</label>
						<label class="browse-filter-option">
							<select name="status" class="browse-filter-select" data-browse-auto-filter>
								<option value="">Любой статус</option>
								<option value="approved"<?=($quickFilters['status'] === 'approved' ? ' selected' : '');?>>Опубликовано</option>
								<option value="pending"<?=($quickFilters['status'] === 'pending' ? ' selected' : '');?>>Ожидает модерации</option>
								<option value="need_fix"<?=($quickFilters['status'] === 'need_fix' ? ' selected' : '');?>>Нужна доработка</option>
								<option value="hidden"<?=($quickFilters['status'] === 'hidden' ? ' selected' : '');?>>Скрыто</option>
								<option value="deleted"<?=($quickFilters['status'] === 'deleted' ? ' selected' : '');?>>Удалено</option>
							</select>
						</label>
					</div>
				</fieldset>

				<?php foreach ($schema as $group => $definition) { ?>
				<?php
				list($visibleOptions, $hiddenOptions) = browse_filter_options_split($definition['options'], $selectedFilters[$group], 4);
				$hiddenSelectedCount = 0;
				foreach (array_keys($hiddenOptions) as $hiddenValue) {
					if (in_array($hiddenValue, $selectedFilters[$group], true)) {
						$hiddenSelectedCount++;
					}
				}
				$showMoreLabel = 'Показать ещё '.count($hiddenOptions);
				?>
				<fieldset class="browse-filter-group">
					<legend class="browse-filter-title"><?=$definition['label'];?>:</legend>
					<div class="browse-filter-options">
						<?php foreach ($visibleOptions as $value => $label) { ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $selectedFilters[$group], true) ? ' checked' : '');?>>
							<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
						</label>
						<?php } ?>
					</div>
					<?php if ($hiddenOptions) { ?>
					<details class="browse-filter-more"<?=(($hiddenSelectedCount > 0) ? ' open' : '');?>>
						<summary class="browse-filter-more-toggle" data-closed-label="<?=htmlspecialchars($showMoreLabel, ENT_QUOTES, 'UTF-8');?>" data-open-label="Скрыть"><?=$hiddenSelectedCount > 0 ? 'Скрыть' : $showMoreLabel;?></summary>
						<div class="browse-filter-options browse-filter-options-extra">
							<?php foreach ($hiddenOptions as $value => $label) { ?>
							<label class="browse-filter-option">
								<input type="checkbox" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $selectedFilters[$group], true) ? ' checked' : '');?>>
								<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
							</label>
							<?php } ?>
						</div>
					</details>
					<?php } ?>
				</fieldset>
				<?php } ?>

				<button type="submit" class="browse-filter-submit">Применить</button>
			</form>
		</aside>
	</div>
</div>

<?php
	foot();

	return (string) ob_get_clean();
}

<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Страница торрентов
===================================================================
*/

require 'system/init.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

function browse_parse_tags($value)
{
	$result = array();
	$parts = explode(',', (string) $value);

	foreach ($parts as $part) {
		$part = trim((string) $part);
		if ($part === '') {
			continue;
		}

		$result[] = $part;
	}

	return array_values(array_unique($result));
}

function browse_build_url($overrides = array(), $drop = array())
{
	$params = $_GET;

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

function browse_collect_selected_filters($schema)
{
	$result = array();

	foreach ($schema as $group => $definition) {
		$key = 'filter_'.$group;
		$values = (isset($_GET[$key]) && is_array($_GET[$key]) ? $_GET[$key] : array());
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

$search = trim((string) ($_GET['search'] ?? ''));
$id_category = isset($_GET['id_category']) ? (int) $_GET['id_category'] : 0;
$sort = trim((string) ($_GET['sort'] ?? 'date'));
$view = (string) ($_GET['view'] ?? 'compact');
$view = ($view === 'full' ? 'full' : 'compact');
$searchRateLimitId = ($USER ? 'user:'.$USER['id'] : 'ip:'.($_SERVER['REMOTE_ADDR'] ?? 'cli'));

$sortOptions = array(
	'date' => array(
		'label' => 'Дата',
		'order' => 't.added DESC',
	),
	'size' => array(
		'label' => 'Размер',
		'order' => 't.size DESC, t.added DESC',
	),
	'seeders' => array(
		'label' => 'Раздающие',
		'order' => 'seeders DESC, t.added DESC',
	),
	'name' => array(
		'label' => 'А - Я',
		'order' => 't.name ASC',
	),
);

if (empty($sortOptions[$sort])) {
	$sort = 'date';
}

if ($search !== '') {
	$searchRateLimit = lt_rate_limit_hit('search', $searchRateLimitId, 30, 5 * 60);
	if (!empty($searchRateLimit['limited'])) {
		err('Ошибка', 'Слишком много поисковых запросов. Попробуйте немного позже.', 1);
	}
}

$schema = lt_torrent_metadata_schema();
$categories = categories_array();
$categoriesById = array();

foreach ($categories as $category) {
	$categoriesById[(int) $category['id']] = $category;
}

$currentCategoryName = (!empty($categoriesById[$id_category]['name']) ? (string) $categoriesById[$id_category]['name'] : '');
if (!empty($schema['type'])) {
	$typeOptions = lt_torrent_metadata_type_options_for_category($currentCategoryName);
	if ($typeOptions) {
		$schema['type']['options'] = $typeOptions;
	} else {
		unset($schema['type']);
	}
}

$selectedFilters = browse_collect_selected_filters($schema);

$where = array();
if (!$PRIV['details_banned_view']) {
	$where[] = 't.banned <> 1';
}

if ($id_category > 0) {
	$where[] = 't.id_category = '.$db->safesql($id_category);
}

if ($search !== '') {
	$where[] = "t.name LIKE '%".sqlwildcardesc($search)."%'";
}

browse_apply_filter_conditions($where, $schema, $selectedFilters);

$pagerParams = array();
if ($search !== '') {
	$pagerParams['search'] = $search;
}
if ($id_category > 0) {
	$pagerParams['id_category'] = $id_category;
}
$pagerParams['sort'] = $sort;
$pagerParams['view'] = $view;
foreach ($selectedFilters as $group => $values) {
	if ($values) {
		$pagerParams['filter_'.$group] = $values;
	}
}

$db->query("SELECT t.id
	FROM torrents AS t
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	".($where ? 'WHERE '.implode(' AND ', $where) : '')."
	GROUP BY t.id", 1);
$countTorrent = $db->num_rows();

$pagerHref = 'browse.php'.($pagerParams ? '?'.http_build_query($pagerParams).'&' : '?');
list($pagertop, $pagerbottom, $limit) = pager('10', $countTorrent, $pagerHref);

if ($search !== '' && substr_count((string) ($_SERVER['QUERY_STRING'] ?? ''), 'page') == 0 && strlen($search) >= 5 && $USER) {
	$checkQuery = $db->super_query("SELECT COUNT(*) AS count FROM search_query WHERE id_user=".($USER ? $USER['id'] : '-1')." AND text LIKE '%".sqlwildcardesc($search)."%'");

	if (!empty($checkQuery['count'])) {
		$db->query("UPDATE search_query SET last_date = NOW(), num_views = (num_views + 1), num_torrents = ".$countTorrent." WHERE id_user=".($USER ? $USER['id'] : '-1')." AND text LIKE '%".sqlwildcardesc($search)."%'");
	} else {
		$db->query("INSERT INTO search_query (text, id_user, last_date, num_torrents) VALUES ('".sqlwildcardesc($search)."', ".($USER ? $USER['id'] : '-1').", NOW(), ".$countTorrent.')');
	}
}

$rows = array();
$sql = $db->query("SELECT t.*, COALESCE(SUM(tr.seeders), 0) AS seeders, COALESCE(SUM(tr.leechers), 0) AS leechers,
	COALESCE(SUM(CASE WHEN tr.tracker <> 'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count,
	IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker = 'localhost' GROUP BY tracker) > 0, true, false) AS local_seeders,
	IF(ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
	FROM torrents AS t
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	".($where ? 'WHERE '.implode(' AND ', $where) : '')."
	GROUP BY t.id
	ORDER BY ".$sortOptions[$sort]['order']."
	".$limit);

while ($row = $db->get_row($sql)) {
	$rows[] = $row;
}

$canUpload = ($USER && !empty($PRIV['upload']));

head('Торренты');
?>
<div class="browse-page">
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
				<form action="browse.php" method="get" class="browse-search-form">
					<?php if ($id_category > 0) { ?>
					<input type="hidden" name="id_category" value="<?=$id_category;?>">
					<?php } ?>
					<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>
					<?php foreach ($selectedFilters as $group => $values) { ?>
						<?php foreach ($values as $value) { ?>
						<input type="hidden" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>">
						<?php } ?>
					<?php } ?>
					<div class="browse-search-row">
						<input type="text" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>" class="browse-search-input" placeholder="Поиск..." autocomplete="off">
						<button type="submit" class="browse-search-submit">Найти</button>
					</div>
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

			<section class="browse-panel browse-results-panel">
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
					$user = get_user_info((int) $row['id_user']);
					$torrentCard = lt_torrent_prepare_browse_card($row, $category, $user);
					?>
					<?php include __DIR__.'/templates/default/tpl.torrent.card.php'; ?>
					<?php } ?>
				</div>
				<?php } else { ?>
				<div class="browse-empty-state">Торренты не найдены.</div>
				<?php } ?>
			</section>
			<?php if ($rows) { ?>
			<div class="browse-pagination"><?=$pagerbottom ?: $pagertop;?></div>
			<?php } ?>
		</div>

		<aside class="browse-sidebar">
			<form action="browse.php" method="get" class="browse-filter-panel">
				<?php if ($search !== '') { ?>
				<input type="hidden" name="search" value="<?=htmlspecialchars($search, ENT_QUOTES, 'UTF-8');?>">
				<?php } ?>
				<?php if ($id_category > 0) { ?>
				<input type="hidden" name="id_category" value="<?=$id_category;?>">
				<?php } ?>
				<input type="hidden" name="view" value="<?=htmlspecialchars($view, ENT_QUOTES, 'UTF-8');?>" data-browse-view-input>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-browse-list]');
	var viewInputs = document.querySelectorAll('[data-browse-view-input]');
	var viewButtons = document.querySelectorAll('[data-browse-view-toggle]');
	var storageKey = 'litetrackerBrowseView';
	var initialView = list ? (list.getAttribute('data-view') || 'compact') : 'compact';
	var storedView = '';
	var viewExplicit = <?=(!empty($_GET['view']) ? 'true' : 'false');?>;

	try {
		storedView = window.localStorage.getItem(storageKey) || '';
	} catch (error) {
		storedView = '';
	}

	if (list && !viewExplicit && (storedView === 'compact' || storedView === 'full')) {
		initialView = storedView;
	}

	function updateUrlParam(name, value) {
		if (!window.history || !window.history.replaceState) {
			return;
		}

		var url = new URL(window.location.href);
		if (!value) {
			url.searchParams.delete(name);
		} else {
			url.searchParams.set(name, value);
		}
		window.history.replaceState({}, '', url.toString());
	}

	function syncViewLinks(view) {
		var links = document.querySelectorAll('.browse-pagination a, .browse-sort-list a, .browse-categories a');
		for (var i = 0; i < links.length; i++) {
			try {
				var url = new URL(links[i].getAttribute('href'), window.location.href);
				if (url.pathname.split('/').pop() !== 'browse.php') {
					continue;
				}

				url.searchParams.set('view', view);
				links[i].setAttribute('href', 'browse.php' + url.search + url.hash);
			} catch (error) {}
		}
	}

	function setView(view, syncUrl) {
		if (!list) {
			return;
		}

		list.setAttribute('data-view', view);

		for (var i = 0; i < viewInputs.length; i++) {
			viewInputs[i].value = view;
		}

		for (var j = 0; j < viewButtons.length; j++) {
			var active = viewButtons[j].getAttribute('data-browse-view') === view;
			viewButtons[j].classList.toggle('is-active', active);
			viewButtons[j].setAttribute('aria-pressed', active ? 'true' : 'false');
		}

		try {
			window.localStorage.setItem(storageKey, view);
		} catch (error) {}

		syncViewLinks(view);

		if (syncUrl) {
			updateUrlParam('view', view);
		}
	}

	for (var i = 0; i < viewButtons.length; i++) {
		viewButtons[i].addEventListener('click', function () {
			setView(this.getAttribute('data-browse-view') || 'compact', true);
		});
	}

	var filterMoreToggles = document.querySelectorAll('.browse-filter-more-toggle');
	for (var k = 0; k < filterMoreToggles.length; k++) {
		(function (toggle) {
			var details = toggle.parentNode;
			if (!details) {
				return;
			}

			function syncToggleLabel() {
				toggle.textContent = details.open ? (toggle.getAttribute('data-open-label') || 'Скрыть') : (toggle.getAttribute('data-closed-label') || '');
			}

			details.addEventListener('toggle', syncToggleLabel);
			syncToggleLabel();
		})(filterMoreToggles[k]);
	}

	setView(initialView, false);
});
</script>
<?php
foot();
?>

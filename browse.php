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

$schema = lt_torrent_metadata_schema();
$search = trim((string) ($_GET['search'] ?? ''));
$id_category = isset($_GET['id_category']) ? (int) $_GET['id_category'] : 0;
$view = (string) ($_GET['view'] ?? 'compact');
$view = ($view === 'full' ? 'full' : 'compact');
$selectedFilters = browse_collect_selected_filters($schema);
$categories = categories_array();
$categoriesById = array();

foreach ($categories as $category) {
	$categoriesById[(int) $category['id']] = $category;
}

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
list($pagertop, $pagerbottom, $limit) = pager('20', $countTorrent, $pagerHref);

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
	IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker = 'localhost' GROUP BY tracker) > 0, true, false) AS local_seeders,
	IF(ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
	FROM torrents AS t
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	".($where ? 'WHERE '.implode(' AND ', $where) : '')."
	GROUP BY t.id
	ORDER BY t.added DESC
	".$limit);

while ($row = $db->get_row($sql)) {
	$rows[] = $row;
}

$canUpload = ($USER && !empty($PRIV['upload']) && (int) ($USER['class'] ?? 0) >= 3);

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
				<div class="browse-results-header">
					<div class="browse-results-title-group">
						<h2 class="browse-results-title">Торренты</h2>
						<div class="browse-results-count"><?=$countTorrent;?> загружено</div>
					</div>

					<div class="browse-view-switch" role="group" aria-label="Вид списка">
						<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>">
							<svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<path d="M4 5.5h12M4 10h12M4 14.5h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
							</svg>
						</button>
						<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>">
							<svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<rect x="4" y="4" width="12" height="3.2" rx="1" stroke="currentColor" stroke-width="1.4"/>
								<rect x="4" y="8.4" width="12" height="7.6" rx="1" stroke="currentColor" stroke-width="1.4"/>
							</svg>
						</button>
					</div>
				</div>

				<?php if ($rows) { ?>
				<div class="browse-pagination"><?=$pagertop;?></div>
				<div class="browse-torrent-list" data-browse-list data-view="<?=$view;?>">
					<?php foreach ($rows as $row) { ?>
					<?php
					$torrentId = (int) $row['id'];
					$category = (!empty($categoriesById[(int) $row['id_category']]) ? $categoriesById[(int) $row['id_category']] : array('id' => 0, 'name' => 'Без категории', 'image' => ''));
					$categoryName = (string) $category['name'];
					$user = get_user_info((int) $row['id_user']);
					$userName = (!empty($user['name']) ? $user['name'] : 'Неизвестно');
					$userClass = (int) ($user['class'] ?? 0);
					$cover = 'public/images/default_avatar.gif';
					if (!empty($row['image']) && is_file('public/downloads/images/'.$row['image'])) {
						$cover = 'public/downloads/images/'.$row['image'];
					} elseif (!empty($category['image']) && is_file('public/images/categories/'.$category['image'])) {
						$cover = 'public/images/categories/'.$category['image'];
					}

					$typeLabel = lt_torrent_metadata_format('type', $row['content_type'] ?? '');
					$subtitlesLabel = lt_torrent_metadata_format('subtitles', $row['subtitles'] ?? '');
					$languagesLabel = lt_torrent_metadata_format('language', $row['languages'] ?? '');
					$genresLabel = lt_torrent_metadata_format('genre', $row['genres'] ?? '');
					$infoLabel = lt_torrent_metadata_format('info', $row['meta_info'] ?? '');
					$countryLabel = lt_torrent_metadata_format('country', $row['countries'] ?? '');
					$tagsLabel = implode(', ', browse_parse_tags($row['tags'] ?? ''));
					$description = template_truncate_text(format_comment((string) $row['descr']), 520);
					$sizeLabel = mksize((float) $row['size']);
					$filesLabel = number_format((int) $row['num_files']);
					$seedersLabel = number_format((int) $row['seeders']);
					$leechersLabel = number_format((int) $row['leechers']);
					$completedLabel = number_format((int) $row['completed']);
					$dateLabel = convent_date($row['added']);
					?>
					<article class="browse-torrent-card<?=(!empty($row['banned']) ? ' is-banned' : '');?>">
						<div class="browse-torrent-card-head">
							<div class="browse-torrent-card-heading">
								<div class="browse-torrent-card-category-row">
									<a class="browse-torrent-card-category" href="<?=htmlspecialchars(browse_build_url(array('id_category' => (int) $category['id'], 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');?></a>
									<?php if ($typeLabel !== '') { ?>
									<span class="browse-torrent-card-pill"><?=htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8');?></span>
									<?php } ?>
									<?php if (!empty($row['new_release'])) { ?>
									<span class="browse-torrent-card-pill">Новинка</span>
									<?php } ?>
								</div>
								<h3 class="browse-torrent-card-title">
									<a href="details.php?id=<?=$torrentId;?>"><?=htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8');?></a>
								</h3>
							</div>
						</div>

						<div class="browse-torrent-card-meta">
							<span class="browse-torrent-card-meta-item">
								<img src="public/images/up.png" alt="" width="16" height="16">
								<span><?=$seedersLabel;?></span>
							</span>
							<span class="browse-torrent-card-meta-item">
								<img src="public/images/down.png" alt="" width="16" height="16">
								<span><?=$leechersLabel;?></span>
							</span>
							<span class="browse-torrent-card-meta-item">
								<span><?=$sizeLabel;?></span>
							</span>
							<span class="browse-torrent-card-meta-item">
								<img src="public/images/user.png" alt="" width="16" height="16">
								<span><?=get_user_color($userClass, htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'));?></span>
							</span>
							<span class="browse-torrent-card-meta-item">
								<span>Добавлен: <?=$dateLabel;?></span>
							</span>
						</div>

						<div class="browse-torrent-card-body">
							<a class="browse-torrent-card-cover" href="details.php?id=<?=$torrentId;?>">
								<img src="<?=htmlspecialchars($cover, ENT_QUOTES, 'UTF-8');?>" alt="<?=htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8');?>">
							</a>

							<div class="browse-torrent-card-content">
								<div class="browse-torrent-card-section">Информация о торренте</div>
								<dl class="browse-torrent-card-facts">
									<div class="browse-torrent-card-fact">
										<dt>Категория:</dt>
										<dd><?=htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php if ($typeLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Тип:</dt>
										<dd><?=htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($languagesLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Язык:</dt>
										<dd><?=htmlspecialchars($languagesLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($subtitlesLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Субтитры:</dt>
										<dd><?=htmlspecialchars($subtitlesLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($genresLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Жанр:</dt>
										<dd><?=htmlspecialchars($genresLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($infoLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Инфо:</dt>
										<dd><?=htmlspecialchars($infoLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($countryLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Страна:</dt>
										<dd><?=htmlspecialchars($countryLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<?php if ($tagsLabel !== '') { ?>
									<div class="browse-torrent-card-fact">
										<dt>Тэги:</dt>
										<dd><?=htmlspecialchars($tagsLabel, ENT_QUOTES, 'UTF-8');?></dd>
									</div>
									<?php } ?>
									<div class="browse-torrent-card-fact">
										<dt>Размер:</dt>
										<dd><?=$sizeLabel;?></dd>
									</div>
									<div class="browse-torrent-card-fact">
										<dt>Файлов:</dt>
										<dd><?=$filesLabel;?></dd>
									</div>
									<div class="browse-torrent-card-fact">
										<dt>Скачан:</dt>
										<dd><?=$completedLabel;?></dd>
									</div>
								</dl>

								<?php if ($description !== '') { ?>
								<div class="browse-torrent-card-section browse-torrent-card-section-secondary">Описание</div>
								<p class="browse-torrent-card-description"><?=htmlspecialchars($description, ENT_QUOTES, 'UTF-8');?></p>
								<?php } ?>
							</div>
						</div>
					</article>
					<?php } ?>
				</div>
				<div class="browse-pagination"><?=$pagerbottom ?: $pagertop;?></div>
				<?php } else { ?>
				<div class="browse-empty-state">Торренты не найдены.</div>
				<?php } ?>
			</section>
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
				<fieldset class="browse-filter-group">
					<legend class="browse-filter-title"><?=$definition['label'];?>:</legend>
					<div class="browse-filter-options">
						<?php foreach ($definition['options'] as $value => $label) { ?>
						<label class="browse-filter-option">
							<input type="checkbox" name="filter_<?=$group;?>[]" value="<?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8');?>"<?=(in_array($value, $selectedFilters[$group], true) ? ' checked' : '');?>>
							<span><?=htmlspecialchars($label, ENT_QUOTES, 'UTF-8');?></span>
						</label>
						<?php } ?>
					</div>
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

		if (syncUrl) {
			updateUrlParam('view', view);
		}
	}

	for (var i = 0; i < viewButtons.length; i++) {
		viewButtons[i].addEventListener('click', function () {
			setView(this.getAttribute('data-browse-view') || 'compact', true);
		});
	}

	setView(initialView, false);
});
</script>
<?php
foot();
?>

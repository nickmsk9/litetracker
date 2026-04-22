<?php
/*
===================================================================
-------------------------------------------------------------------
Назначение: Главная страница
===================================================================
*/

require 'system/init.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_SIDEBAR_SKIP_BLOCKS'] = array(
	'block-online.php',
	'block-stats.php',
);

function home_build_url($overrides = array(), $drop = array())
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

	return 'index.php'.($query !== '' ? '?'.$query : '');
}

$sort = trim((string) ($_GET['sort'] ?? 'date'));
$id_category = isset($_GET['id_category']) ? (int) $_GET['id_category'] : 0;
$view = (string) ($_GET['view'] ?? 'compact');
$view = ($view === 'full' ? 'full' : 'compact');

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

$db->query("SELECT t.id
	FROM torrents AS t
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	".($where ? 'WHERE '.implode(' AND ', $where) : '')."
	GROUP BY t.id", 1);
$countTorrent = $db->num_rows();

$pagerParams = array(
	'view' => $view,
	'sort' => $sort,
);
if ($id_category > 0) {
	$pagerParams['id_category'] = $id_category;
}

$pagerHref = 'index.php'.($pagerParams ? '?'.http_build_query($pagerParams).'&' : '?');
list($pagertop, $pagerbottom, $limit) = pager('1', $countTorrent, $pagerHref);

$rows = array();
$sql = $db->query("SELECT t.*, COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.seeders ELSE 0 END), 0) AS seeders, COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.leechers ELSE 0 END), 0) AS leechers,
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

head('Главная');
?>
<div class="home-torrents-page">
	<?php if ($categories) { ?>
	<nav class="browse-panel browse-categories" aria-label="Категории торрентов">
			<a class="browse-category-tab<?=($id_category === 0 ? ' is-active' : '');?>" href="<?=htmlspecialchars(home_build_url(array('id_category' => null, 'page' => null)), ENT_QUOTES, 'UTF-8');?>">Все торренты</a>
			<?php foreach ($categories as $category) { ?>
			<a class="browse-category-tab<?=($id_category === (int) $category['id'] ? ' is-active' : '');?>" href="<?=htmlspecialchars(home_build_url(array('id_category' => (int) $category['id'], 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8');?></a>
			<?php } ?>
	</nav>
	<?php } ?>

	<section class="browse-panel browse-results-panel home-results-panel">

		<div class="home-browse-toolbar">
			<div class="home-browse-sort">
				<?php foreach ($sortOptions as $sortKey => $sortOption) { ?>
				<a class="home-browse-sort-link<?=($sort === $sortKey ? ' is-active' : '');?>" href="<?=htmlspecialchars(home_build_url(array('sort' => $sortKey, 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=$sortOption['label'];?></a>
				<?php } ?>
			</div>

			<div class="home-browse-actions">
				<div class="home-browse-count"><?=$countTorrent;?> загружено</div>

				<div class="browse-view-switch" role="group" aria-label="Вид списка">
					<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>" title="Сетка">
						<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
							<rect x="3" y="3" width="6" height="6"></rect>
							<rect x="11" y="3" width="6" height="6"></rect>
							<rect x="3" y="11" width="6" height="6"></rect>
							<rect x="11" y="11" width="6" height="6"></rect>
						</svg>
					</button>
					<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>" title="Список">
						<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
							<rect x="4" y="3" width="12" height="4"></rect>
							<rect x="4" y="8" width="12" height="4"></rect>
							<rect x="4" y="13" width="12" height="4"></rect>
						</svg>
					</button>
				</div>
			</div>
		</div>

		<?php if ($rows) { ?>
		<div class="browse-torrent-list home-torrent-list" data-browse-list data-view="<?=$view;?>">
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
			$tagsLabel = implode(', ', array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($row['tags'] ?? '')))))));
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
							<a class="browse-torrent-card-category" href="<?=htmlspecialchars(home_build_url(array('id_category' => (int) $category['id'], 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');?></a>
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
						<span class="browse-torrent-card-cover-badge"><?=htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');?></span>
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
		<?php } else { ?>
		<div class="browse-empty-state">Торренты не найдены.</div>
		<?php } ?>
	</section>

	<?php if ($rows) { ?>
	<div class="browse-pagination"><?=$pagerbottom ?: $pagertop;?></div>
	<?php } ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-browse-list]');
	var viewButtons = document.querySelectorAll('[data-browse-view-toggle]');
	var storageKey = 'litetrackerHomeView';
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

		for (var i = 0; i < viewButtons.length; i++) {
			var active = viewButtons[i].getAttribute('data-browse-view') === view;
			viewButtons[i].classList.toggle('is-active', active);
			viewButtons[i].setAttribute('aria-pressed', active ? 'true' : 'false');
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
stdfoot();
?>

<?php
/*
===================================================================
-------------------------------------------------------------------
Назначение: Главная страница
===================================================================
*/

require 'system/init.php';

$GLOBALS['LITETRACKER_SIDEBAR_SKIP_BLOCKS'] = array(
	'block-online.php',
	'block-stats.php',
	'block-load_in_server.php',
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
$isAjaxLoad = !empty($_GET['ajax']);

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
list($pagertop, $pagerbottom, $limit) = pager('5', $countTorrent, $pagerHref);

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

function home_render_torrent_cards($rows, $categoriesById)
{
	ob_start();
	foreach ($rows as $row) {
		$category = (!empty($categoriesById[(int) $row['id_category']]) ? $categoriesById[(int) $row['id_category']] : array('id' => 0, 'name' => 'Без категории', 'image' => ''));
		$user = get_user_info((int) $row['id_user']);
		$torrentCard = lt_torrent_prepare_browse_card($row, $category, $user);
		include __DIR__.'/templates/default/tpl.torrent.card.php';
	}

	return ob_get_clean();
}

$currentPage = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
$perPage = 5;
$pagesCount = ($countTorrent > 0 ? (int) ceil($countTorrent / $perPage) : 0);
$nextPage = ($currentPage + 1 < $pagesCount ? $currentPage + 1 : null);
$nextPageUrl = ($nextPage !== null ? home_build_url(array('page' => $nextPage, 'view' => $view, 'sort' => $sort, 'ajax' => 1)) : '');
$homeLoadBlock = $memcached->get('home_load_block_config_v1');
if (false === $homeLoadBlock || !is_array($homeLoadBlock)) {
	$homeLoadBlock = $db->super_query("SELECT active, type, which FROM orbital_blocks WHERE blockfile = 'block-load_in_server.php' LIMIT 1");
	$memcached->set('home_load_block_config_v1', (is_array($homeLoadBlock) ? $homeLoadBlock : array()), 0, 5 * 60);
}

$homeLoadBlockType = trim($homeLoadBlock['type'] ?? 'all');
$homeLoadBlockWhich = trim($homeLoadBlock['which'] ?? 'all');
$homeLoadBlockPages = array();

foreach (explode(',', ($homeLoadBlockWhich === '' ? 'all' : $homeLoadBlockWhich)) as $page) {
	$page = preg_replace('~[^a-z0-9_\-]~', '', strtolower(trim($page)));
	if ($page !== '') {
		$homeLoadBlockPages[$page] = true;
	}
}

if (empty($homeLoadBlockPages)) {
	$homeLoadBlockPages = array('all' => true);
}

$showHomeLoadBlock = (
	!empty($homeLoadBlock['active'])
	&& (isset($homeLoadBlockPages['all']) || isset($homeLoadBlockPages['index']))
	&& (
		$homeLoadBlockType === 'all'
		|| ($homeLoadBlockType === 'guests' && !$USER)
		|| ($homeLoadBlockType === 'users' && $USER)
		|| ($homeLoadBlockType === 'moderators' && !empty($PRIV['block_moderators']))
		|| ($homeLoadBlockType === 'administrators' && !empty($PRIV['block_administrators']))
	)
);

if ($isAjaxLoad) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'html' => home_render_torrent_cards($rows, $categoriesById),
		'nextPage' => $nextPage,
		'nextUrl' => $nextPageUrl,
		'paginationHtml' => $pagerbottom ?: $pagertop,
		'hasMore' => ($nextPage !== null),
	));
	die();
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
			<ul class="browse-sort-list" role="tablist" aria-label="Сортировка торрентов">
				<?php foreach ($sortOptions as $sortKey => $sortOption) { ?>
				<li class="browse-sort-item<?=($sort === $sortKey ? ' is-active' : '');?>">
					<a class="browse-sort-link" href="<?=htmlspecialchars(home_build_url(array('sort' => $sortKey, 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=$sortOption['label'];?></a>
				</li>
				<?php } ?>
			</ul>

			<div class="browse-view-switch" role="group" aria-label="Вид списка">
				<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>" title="Подробный вид">
					<span class="browse-view-icon browse-view-icon-medium" aria-hidden="true"></span>
				</button>
				<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-browse-view-toggle data-browse-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>" title="Компактный вид">
					<span class="browse-view-icon browse-view-icon-small" aria-hidden="true"></span>
				</button>
			</div>
		</div>

		<?php if ($rows) { ?>
		<div class="browse-torrent-list home-torrent-list" data-browse-list data-view="<?=$view;?>">
			<?=home_render_torrent_cards($rows, $categoriesById);?>
		</div>
		<?php } else { ?>
		<div class="browse-empty-state">Торренты не найдены.</div>
		<?php } ?>
	</section>

	<?php if ($rows) { ?>
	<div class="browse-pagination" data-home-pagination>
		<?php if ($nextPageUrl !== '') { ?>
		<button class="home-load-more" type="button" data-home-load-more data-next-url="<?=htmlspecialchars($nextPageUrl, ENT_QUOTES, 'UTF-8');?>">Показать ещё</button>
		<?php } ?>
		<div data-home-pagination-html><?=$pagerbottom ?: $pagertop;?></div>
	</div>
	<?php } ?>

	<?php if ($showHomeLoadBlock) { ?>
	<?php require __DIR__.'/blocks/block-load_in_server.php'; ?>
	<?php } ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-browse-list]');
	var viewButtons = document.querySelectorAll('[data-browse-view-toggle]');
	var loadMoreButton = document.querySelector('[data-home-load-more]');
	var paginationHtml = document.querySelector('[data-home-pagination-html]');
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

	function syncViewLinks(view) {
		var links = document.querySelectorAll('.browse-pagination a, .browse-sort-list a, .browse-categories a');
		for (var i = 0; i < links.length; i++) {
			try {
				var url = new URL(links[i].getAttribute('href'), window.location.href);
				if (url.pathname.split('/').pop() !== 'index.php') {
					continue;
				}

				url.searchParams.set('view', view);
				links[i].setAttribute('href', 'index.php' + url.search + url.hash);
			} catch (error) {}
		}
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

	if (loadMoreButton && list) {
		loadMoreButton.addEventListener('click', function () {
			var button = this;
			var nextUrl = button.getAttribute('data-next-url') || '';
			if (!nextUrl || button.disabled) {
				return;
			}

			button.disabled = true;
			button.textContent = 'Загрузка...';

			fetch(nextUrl, {
				headers: {'X-Requested-With': 'XMLHttpRequest'}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('load failed');
					}
					return response.json();
				})
				.then(function (payload) {
					if (payload.html) {
						list.insertAdjacentHTML('beforeend', payload.html);
					}

					if (paginationHtml && payload.paginationHtml) {
						paginationHtml.innerHTML = payload.paginationHtml;
					}

					if (payload.hasMore && payload.nextUrl) {
						button.setAttribute('data-next-url', payload.nextUrl);
						button.disabled = false;
						button.textContent = 'Показать ещё';
					} else {
						button.remove();
					}

					syncViewLinks(list.getAttribute('data-view') || 'compact');
				})
				.catch(function () {
					button.disabled = false;
					button.textContent = 'Попробовать ещё раз';
				});
		});
	}

	setView(initialView, false);
});
</script>
<?php
stdfoot();
?>

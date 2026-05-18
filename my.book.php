<?php
/*
Назначение: Мои закладки
*/

require 'system/init.php';
is_login();

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

$ltBookmarkAjax = (!empty($_GET['ajax']) || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
$ltBookmarkActionScope = 'bookmarks_action';

function lt_bookmark_json($payload, $statusCode = 200)
{
	header('Content-Type: application/json; charset=UTF-8', true, (int) $statusCode);
	echo json_encode($payload);
	die();
}

function lt_bookmark_parse_tags($value)
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

function lt_bookmark_build_url($overrides = array(), $drop = array())
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

	return 'my.book.php'.($query !== '' ? '?'.$query : '');
}

function lt_bookmark_require_action_token($scope, $isAjax = false)
{
	global $language;

	if (lt_csrf_validate($scope)) {
		return true;
	}

	if ($isAjax) {
		lt_bookmark_json(array('success' => false, 'message' => 'Защитный токен устарел. Обновите страницу и повторите действие.'), 403);
	}

	err($language['default_1'], 'Защитный токен устарел. Обновите страницу и повторите действие.', 1);
}

// безопасный act (фикс warning)
$act = trim((string) ($_REQUEST['act'] ?? ''));

//////////////////////////////////////////////////////////////////////////////
// Массовое удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'check_delete') {
	if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
		err($language['default_1'], 'Действие доступно только через POST.', 1);
	}
	lt_bookmark_require_action_token($ltBookmarkActionScope);

	$array = $_POST['check'] ?? [];

	if(!count($array) || !is_array($array)) {
		err($language['default_1'] , $language['books_5'] , 1);
	}

	$ids = array();
	foreach($array AS $id) {
		$ids[] = (int)$id;
	}

	$i = 0;
	foreach($ids AS $id) {

		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows()) continue;

		$db->query("SELECT * FROM books WHERE id_torrent=".$id);
		if(!$db->num_rows()) continue;

		$db->query("DELETE FROM books WHERE id_torrent=".$id);
		$i++;
	}

	head('Удаление закладок');
	begin_frame('Удаление закладок');
	msg(sprintf($language['books_6'] , $i , count($ids)) , '<a href="javascript:history.go(-1)">'.$language['books_7'].'</a>');
	end_frame();
	foot();
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Добавление
//////////////////////////////////////////////////////////////////////////////
if($act === 'add') {
	lt_bookmark_require_action_token($ltBookmarkActionScope, $ltBookmarkAjax);

	$id = (int) ($_REQUEST['id'] ?? 0);

	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id);
	if(!$count_t['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['download_1']), 404);
		}
		err($language['default_1'] , $language['download_1'] , 1);
	}

	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);
	if($count_b['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['books_1']), 409);
		}
		err($language['default_1'] , $language['books_1'] , 1);
	}

	$db->query("INSERT INTO books(id_torrent , id_user , date ) VALUES (".$id." , ".$USER['id']." , NOW() )");

	if ($ltBookmarkAjax) {
		lt_bookmark_json(array(
			'success' => true,
			'bookmarked' => true,
			'label' => $language['details_26'],
			'href' => 'my.book.php?id='.$id.'&act=delete&'.lt_csrf_query($ltBookmarkActionScope),
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'delete') {
	lt_bookmark_require_action_token($ltBookmarkActionScope, $ltBookmarkAjax);

	$id = (int) ($_REQUEST['id'] ?? 0);

	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id);
	if(!$count_t['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['download_1']), 404);
		}
		err($language['default_1'] , $language['download_1'] , 1);
	}

	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);
	if(!$count_b['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['books_2']), 409);
		}
		err($language['default_1'] , $language['books_2'] , 1);
	}

	$db->query("DELETE FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);

	if ($ltBookmarkAjax) {
		lt_bookmark_json(array(
			'success' => true,
			'bookmarked' => false,
			'label' => $language['details_25'],
			'href' => 'my.book.php?id='.$id.'&act=add&'.lt_csrf_query($ltBookmarkActionScope),
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Вывод
//////////////////////////////////////////////////////////////////////////////

head($language['books_3']);

$sort = trim((string) ($_GET['sort'] ?? 'date'));
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

$bookmarkStatusSql = lt_torrent_status_filter_sql($USER, 't');
$countRow = $db->super_query("SELECT COUNT(*) AS c FROM books AS b INNER JOIN torrents AS t ON b.id_torrent = t.id WHERE b.id_user=".(int) $USER['id']." AND ".$bookmarkStatusSql);
$count = (int) ($countRow['c'] ?? 0);
$pagerParams = array(
	'sort' => $sort,
	'view' => $view,
);
$pagerHref = 'my.book.php?'.http_build_query($pagerParams).'&';
list($pagertop, $pagerbottom, $limit) = pager('10', $count, $pagerHref);

$rows = array();
$sql = $db->query("SELECT t.*,
	COALESCE(SUM(tr.seeders), 0) AS seeders,
	COALESCE(SUM(tr.leechers), 0) AS leechers,
	COALESCE(SUM(CASE WHEN tr.tracker <> 'localhost' THEN 1 ELSE 0 END), 0) AS external_tracker_count,
	IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker = 'localhost' GROUP BY tracker) > 0, true, false) AS local_seeders,
	IF(ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
	FROM books AS b
	INNER JOIN torrents AS t ON b.id_torrent = t.id
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	WHERE b.id_user=".(int) $USER['id']." AND ".$bookmarkStatusSql."
	GROUP BY t.id
	ORDER BY ".$sortOptions[$sort]['order']."
	".$limit);

while ($row = $db->get_row($sql)) {
	$rows[] = $row;
}
?>
<div class="browse-page">
	<section class="browse-hero">
		<div class="browse-hero-copy">
			<h1 class="browse-hero-title">Закладки</h1>
		</div>
	</section>

	<div class="browse-layout browse-layout-single">
		<div class="browse-main">
			<section class="browse-panel browse-results-panel">
				<div class="home-browse-toolbar">
					<ul class="browse-sort-list" role="tablist" aria-label="Сортировка закладок">
						<?php foreach ($sortOptions as $sortKey => $sortOption) { ?>
						<li class="browse-sort-item<?=($sort === $sortKey ? ' is-active' : '');?>">
							<a class="browse-sort-link" href="<?=htmlspecialchars(lt_bookmark_build_url(array('sort' => $sortKey, 'page' => null)), ENT_QUOTES, 'UTF-8');?>"><?=$sortOption['label'];?></a>
						</li>
						<?php } ?>
					</ul>

					<div class="browse-view-switch" role="group" aria-label="Вид списка">
						<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-bookmarks-view-toggle data-bookmarks-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-medium" aria-hidden="true"></span>
						</button>
						<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-bookmarks-view-toggle data-bookmarks-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>">
							<span class="browse-view-icon browse-view-icon-small" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<?php if ($rows) { ?>
				<div class="browse-torrent-list" data-bookmarks-list data-view="<?=$view;?>">
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
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-bookmarks-list]');
	var viewButtons = document.querySelectorAll('[data-bookmarks-view-toggle]');
	var storageKey = 'litetrackerBookmarksView';
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
		if (!value || value === 'compact') {
			url.searchParams.delete(name);
		} else {
			url.searchParams.set(name, value);
		}
		window.history.replaceState({}, '', url.toString());
	}

	function syncViewLinks(view) {
		var links = document.querySelectorAll('.browse-pagination a, .browse-sort-list a');
		for (var i = 0; i < links.length; i++) {
			try {
				var url = new URL(links[i].getAttribute('href'), window.location.href);
				if (url.pathname.split('/').pop() !== 'my.book.php') {
					continue;
				}

				url.searchParams.set('view', view);
				links[i].setAttribute('href', 'my.book.php' + url.search + url.hash);
			} catch (error) {}
		}
	}

	function setView(view, syncUrl) {
		if (!list) {
			return;
		}

		list.setAttribute('data-view', view);

		for (var j = 0; j < viewButtons.length; j++) {
			var active = viewButtons[j].getAttribute('data-bookmarks-view') === view;
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
			setView(this.getAttribute('data-bookmarks-view') || 'compact', true);
		});
	}

	setView(initialView, false);
});
</script>
<?php
foot();
?>

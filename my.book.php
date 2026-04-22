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

// безопасный act (фикс warning)
$act = isset($_GET['act']) ? trim((string) $_GET['act']) : '';

//////////////////////////////////////////////////////////////////////////////
// Массовое удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'check_delete') {

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

	$id = (int)$_GET['id'];

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
			'href' => 'my.book.php?id='.$id.'&act=delete',
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'delete') {

	$id = (int)$_GET['id'];

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
			'href' => 'my.book.php?id='.$id.'&act=add',
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Вывод
//////////////////////////////////////////////////////////////////////////////

head($language['books_3']);

$view = (string) ($_GET['view'] ?? 'compact');
$view = ($view === 'full' ? 'full' : 'compact');

$categories = categories_array();
$categoriesById = array();
foreach ($categories as $category) {
	$categoriesById[(int) $category['id']] = $category;
}

$countRow = $db->super_query("SELECT COUNT(*) AS c FROM books AS b INNER JOIN torrents AS t ON b.id_torrent = t.id WHERE b.id_user=".(int) $USER['id']);
$count = (int) ($countRow['c'] ?? 0);
$pagerHref = 'my.book.php?'.($view !== 'compact' ? 'view='.$view.'&' : '');
list($pagertop, $pagerbottom, $limit) = pager('10', $count, $pagerHref);

$rows = array();
$sql = $db->query("SELECT t.*,
	COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.seeders ELSE 0 END), 0) AS seeders,
	COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.leechers ELSE 0 END), 0) AS leechers,
	IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker = 'localhost' GROUP BY tracker) > 0, true, false) AS local_seeders,
	IF(ADDDATE(t.added, INTERVAL ".$config['releases_news']." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
	FROM books AS b
	INNER JOIN torrents AS t ON b.id_torrent = t.id
	LEFT JOIN trackers AS tr ON tr.torrent = t.id
	WHERE b.id_user=".(int) $USER['id']."
	GROUP BY t.id
	ORDER BY t.added DESC
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

	<div class="browse-layout">
		<div class="browse-main">
			<section class="browse-panel browse-results-panel">
				<div class="browse-results-header">
					<div class="browse-results-title-group">
						<h2 class="browse-results-title">Торренты</h2>
						<div class="browse-results-count"><?=$count;?> загружено</div>
					</div>

					<div class="browse-view-switch" role="group" aria-label="Вид списка">
						<button type="button" class="browse-view-button<?=($view === 'compact' ? ' is-active' : '');?>" data-bookmarks-view-toggle data-bookmarks-view="compact" aria-pressed="<?=($view === 'compact' ? 'true' : 'false');?>">
							<svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<path d="M4 5.5h12M4 10h12M4 14.5h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
							</svg>
						</button>
						<button type="button" class="browse-view-button<?=($view === 'full' ? ' is-active' : '');?>" data-bookmarks-view-toggle data-bookmarks-view="full" aria-pressed="<?=($view === 'full' ? 'true' : 'false');?>">
							<svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<rect x="4" y="4" width="12" height="3.2" rx="1" stroke="currentColor" stroke-width="1.4"/>
								<rect x="4" y="8.4" width="12" height="7.6" rx="1" stroke="currentColor" stroke-width="1.4"/>
							</svg>
						</button>
					</div>
				</div>

				<?php if ($rows) { ?>
				<div class="browse-torrent-list" data-bookmarks-list data-view="<?=$view;?>">
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
					$tagsLabel = implode(', ', lt_bookmark_parse_tags($row['tags'] ?? ''));
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
									<a class="browse-torrent-card-category" href="browse.php?id_category=<?=(int) $category['id'];?>"><?=htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');?></a>
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

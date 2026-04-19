<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Блок новых релизов
===================================================================
*/

if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

global $db, $config, $USER, $memcached;

if (!function_exists('lt_nr_array_get')) {
	function lt_nr_array_get($array, $key, $default = '')
	{
		return (is_array($array) && array_key_exists($key, $array)) ? $array[$key] : $default;
	}
}

if (!function_exists('lt_nr_pick')) {
	function lt_nr_pick($array, $keys, $default = '')
	{
		if (!is_array($array)) {
			return $default;
		}

		foreach ((array)$keys as $key) {
			if (array_key_exists($key, $array) && $array[$key] !== null && $array[$key] !== '') {
				return $array[$key];
			}
		}

		return $default;
	}
}

if (!function_exists('lt_nr_truncate_text')) {
	function lt_nr_truncate_text($text, $limit = 520)
	{
		$text = trim(strip_tags((string)$text));
		$text = preg_replace('~\s+~u', ' ', $text);

		if ($text === '') {
			return '';
		}

		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($text, 'UTF-8') <= $limit) {
				return $text;
			}

			return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...';
		}

		if (strlen($text) <= $limit) {
			return $text;
		}

		return rtrim(substr($text, 0, $limit)) . '...';
	}
}

if (!function_exists('lt_nr_get_cache_engine')) {
	function lt_nr_get_cache_engine()
	{
		if (function_exists('lt_cache')) {
			$cache = lt_cache();
			if (is_object($cache)) {
				return $cache;
			}
		}

		return null;
	}
}

if (!function_exists('lt_nr_cache_get')) {
	function lt_nr_cache_get($key)
	{
		$cache = lt_nr_get_cache_engine();
		if (!$cache) {
			return false;
		}

		return $cache->get($key);
	}
}

if (!function_exists('lt_nr_cache_set')) {
	function lt_nr_cache_set($key, $value, $ttl = 900)
	{
		$cache = lt_nr_get_cache_engine();
		if (!$cache) {
			return false;
		}

		return $cache->set($key, $value, 0, (int)$ttl);
	}
}

$limit = 30;
$days = isset($config['releases_news']) ? (int)$config['releases_news'] : 7;
if ($days <= 0) {
	$days = 7;
}

$cache_key = 'block_newreleases_v3_' . $days . '_' . $limit;
$rows = lt_nr_cache_get($cache_key);

if (!is_array($rows)) {
	$rows = array();

	$sql = $db->query("
		SELECT
			t.*,
			COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.seeders ELSE 0 END), 0) AS seeders,
			COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.leechers ELSE 0 END), 0) AS leechers,
			IF(ADDDATE(t.added, INTERVAL ".$days." DAY) > NOW() AND t.news = '1', 1, 0) AS new_release
		FROM torrents AS t
		LEFT JOIN trackers AS tr ON tr.torrent = t.id
		WHERE t.banned <> 1
		  AND t.news = '1'
		GROUP BY t.id
		ORDER BY t.added DESC
		LIMIT ".$limit
	);

	while ($row = $db->get_row($sql)) {
		if (!is_array($row)) {
			continue;
		}
		$rows[] = $row;
	}

	lt_nr_cache_set($cache_key, $rows, 600);
}

if (!$rows || !is_array($rows)) {
	return;
}

$view = 'compact';
?>
<style>
.newrel-wrap{
	display:block;
}

.newrel-toolbar{
	display:flex;
	align-items:center;
	justify-content:space-between;
	background:#f5f5f5;
	border:1px solid #d8dde2;
	border-radius:4px;
	overflow:hidden;
	margin-bottom:20px;
}

.newrel-toolbar-left{
	display:flex;
	align-items:stretch;
	flex-wrap:wrap;
}

.newrel-sort-link{
	display:inline-flex;
	align-items:center;
	justify-content:center;
	min-height:40px;
	padding:10px 18px;
	border-right:1px solid #e2e6ea;
	background:#f5f5f5;
	color:#8c97a2;
	font-size:14px;
	line-height:1.2;
	text-decoration:none;
}

.newrel-sort-link:hover{
	color:#6f7983;
	background:#f0f2f4;
}

.newrel-sort-link.is-active{
	background:#4d5560;
	color:#fff;
	font-weight:700;
}

.newrel-toolbar-right{
	display:flex;
	align-items:stretch;
	margin-left:auto;
}

.newrel-view-btn{
	display:inline-flex;
	align-items:center;
	justify-content:center;
	width:48px;
	min-width:48px;
	height:40px;
	padding:0;
	border:0;
	border-left:1px solid #e2e6ea;
	border-radius:0;
	background:#f5f5f5;
	color:#8c97a2;
	cursor:pointer;
}

.newrel-view-btn:hover{
	background:#eceff2;
	color:#68717a;
}

.newrel-view-btn.is-active{
	background:#ffffff;
	color:#616971;
}

.newrel-view-btn svg{
	display:block;
	width:18px;
	height:18px;
}

.newrel-list{
	display:flex;
	flex-direction:column;
	gap:10px;
}

.newrel-list[data-view="full"] .newrel-card-full{
	display:block;
}

.newrel-list[data-view="full"] .newrel-card-compact{
	display:none;
}

.newrel-list[data-view="compact"] .newrel-card-full{
	display:none;
}

.newrel-list[data-view="compact"] .newrel-card-compact{
	display:block;
}

.newrel-card{
	background:#f8f8f8;
	border:1px solid #dfe4e8;
	border-radius:4px;
	overflow:hidden;
}

.newrel-card-full-head{
	padding:12px 18px 10px 18px;
	border-bottom:1px solid #d8dde2;
}

.newrel-card-full-title{
	margin:0;
	font-size:17px;
	line-height:1.35;
	font-weight:400;
}

.newrel-card-full-title a{
	color:#3f8ed9;
	text-decoration:none;
}

.newrel-card-full-title a:hover{
	text-decoration:underline;
}

.newrel-meta{
	display:flex;
	align-items:center;
	flex-wrap:wrap;
	gap:0;
	padding:9px 18px 10px 18px;
	border-bottom:1px solid #d8dde2;
	font-size:13px;
	line-height:1.3;
	color:#444;
}

.newrel-meta-item{
	display:inline-flex;
	align-items:center;
	gap:5px;
}

.newrel-meta-sep{
	margin:0 8px;
	color:#9ba5ae;
}

.newrel-meta-item img{
	width:14px;
	height:14px;
	opacity:.85;
}

.newrel-card-full-body{
	display:grid;
	grid-template-columns:240px minmax(0,1fr);
	gap:18px;
	padding:18px;
}

.newrel-cover{
	position:relative;
	display:block;
	width:240px;
	max-width:240px;
	border-radius:3px;
	overflow:hidden;
	background:#eceff3;
}

.newrel-cover img{
	display:block;
	width:240px;
	height:360px;
	object-fit:cover;
}

.newrel-cover-badge{
	position:absolute;
	top:10px;
	left:10px;
	display:inline-flex;
	align-items:center;
	min-height:24px;
	padding:4px 8px;
	border-radius:3px;
	background:rgba(76,84,94,.92);
	color:#fff;
	font-size:12px;
	font-weight:700;
	line-height:1.1;
	text-transform:lowercase;
}

.newrel-content{
	min-width:0;
	font-size:14px;
	line-height:1.5;
	color:#111;
}

.newrel-section-title{
	margin:0 0 8px 0;
	font-size:14px;
	font-weight:700;
	text-decoration:underline;
}

.newrel-facts{
	margin:0 0 18px 0;
	padding:0;
}

.newrel-fact{
	margin:0 0 2px 0;
}

.newrel-fact b{
	font-weight:700;
}

.newrel-description{
	margin:0 0 14px 0;
}

.newrel-update-title{
	font-weight:700;
	margin:18px 0 4px 0;
}

.newrel-card-compact{
	padding:9px 10px;
}

.newrel-card-compact-inner{
	display:grid;
	grid-template-columns:60px minmax(0,1fr);
	gap:14px;
	align-items:start;
}

.newrel-compact-cover{
	position:relative;
	display:block;
	width:60px;
	height:60px;
	border-radius:3px;
	overflow:hidden;
	background:#eceff3;
}

.newrel-compact-cover img{
	display:block;
	width:60px;
	height:60px;
	object-fit:cover;
}

.newrel-compact-cover-badge{
	position:absolute;
	left:0;
	top:0;
	display:inline-flex;
	align-items:center;
	justify-content:center;
	min-width:36px;
	height:20px;
	padding:0 6px;
	background:rgba(76,84,94,.92);
	color:#fff;
	font-size:11px;
	font-weight:700;
	line-height:20px;
	text-transform:lowercase;
}

.newrel-card-compact-title{
	margin:0 0 8px 0;
	font-size:16px;
	line-height:1.35;
	font-weight:400;
}

.newrel-card-compact-title a{
	color:#3f8ed9;
	text-decoration:none;
}

.newrel-card-compact-title a:hover{
	text-decoration:underline;
}

.newrel-card-compact .newrel-meta{
	padding:0;
	border:0;
}

@media (max-width: 900px){
	.newrel-card-full-body{
		grid-template-columns:1fr;
	}
	.newrel-cover,
	.newrel-cover img{
		width:240px;
	}
}
</style>

<div class="newrel-wrap">
	<div class="newrel-toolbar">
		<div class="newrel-toolbar-left">
			<a href="javascript:void(0)" class="newrel-sort-link is-active">Дата</a>
			<a href="javascript:void(0)" class="newrel-sort-link">Размер</a>
			<a href="javascript:void(0)" class="newrel-sort-link">Раздающие</a>
			<a href="javascript:void(0)" class="newrel-sort-link">А — Я</a>
		</div>

		<div class="newrel-toolbar-right">
			<button type="button" class="newrel-view-btn" data-newrel-view="full" title="Полный вид">
				<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
					<rect x="3" y="3" width="6" height="6"></rect>
					<rect x="11" y="3" width="6" height="6"></rect>
					<rect x="3" y="11" width="6" height="6"></rect>
					<rect x="11" y="11" width="6" height="6"></rect>
				</svg>
			</button>
			<button type="button" class="newrel-view-btn" data-newrel-view="compact" title="Компактный вид">
				<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
					<rect x="4" y="3" width="12" height="4"></rect>
					<rect x="4" y="8" width="12" height="4"></rect>
					<rect x="4" y="13" width="12" height="4"></rect>
				</svg>
			</button>
		</div>
	</div>

	<div class="newrel-list" data-newrel-list data-view="<?=$view;?>">
		<?php foreach ($rows as $row) { ?>
			<?php
			$torrentId = (int)lt_nr_array_get($row, 'id', 0);
			$name = trim((string)lt_nr_pick($row, array('name'), 'Без названия'));
			$descrRaw = (string)lt_nr_pick($row, array('descr', 'description'), '');
			$descr = lt_nr_truncate_text($descrRaw, 520);

			$seeders = number_format((int)lt_nr_pick($row, array('seeders'), 0));
			$leechers = number_format((int)lt_nr_pick($row, array('leechers'), 0));
			$size = mksize((float)lt_nr_pick($row, array('size'), 0));
			$added = (string)lt_nr_pick($row, array('last_action', 'added'), '');
			$dateLabel = ($added !== '' ? convent_date($added) : '');

			$idUser = (int)lt_nr_pick($row, array('id_user'), 0);
			$user = get_user_info($idUser);
			$userName = (!empty($user['name']) ? $user['name'] : 'Неизвестно');
			$userClass = (int)($user['class'] ?? 0);

			$idCategory = (int)lt_nr_pick($row, array('id_category'), 0);
			$categoryLabel = 'аниме';
			if (function_exists('categories_array')) {
				$allCategories = categories_array();
				if (is_array($allCategories)) {
					foreach ($allCategories as $cat) {
						if ((int)($cat['id'] ?? 0) === $idCategory) {
							$categoryLabel = trim((string)($cat['name'] ?? 'аниме'));
							break;
						}
					}
				}
			}

			$image = trim((string)lt_nr_pick($row, array('image'), ''));
			$cover = 'public/images/default_avatar.gif';
			if ($image !== '' && is_file('public/downloads/images/'.$image)) {
				$cover = 'public/downloads/images/'.$image;
			}

			$year = trim((string)lt_nr_pick($row, array('year', 'release_year'), ''));
			$country = trim((string)lt_nr_pick($row, array('country', 'countries'), ''));
			$genre = trim((string)lt_nr_pick($row, array('genre', 'genres'), ''));
			$quality = trim((string)lt_nr_pick($row, array('quality', 'content_type', 'type'), ''));
			$languageValue = trim((string)lt_nr_pick($row, array('language', 'languages'), ''));
			$subtitles = trim((string)lt_nr_pick($row, array('subtitles'), ''));
			$formatValue = trim((string)lt_nr_pick($row, array('format'), ''));
			$resolution = trim((string)lt_nr_pick($row, array('resolution'), ''));
			$duration = trim((string)lt_nr_pick($row, array('time', 'duration'), ''));
			$director = trim((string)lt_nr_pick($row, array('director'), ''));
			$series = trim((string)lt_nr_pick($row, array('series', 'episodes'), ''));
			$updateReason = trim((string)lt_nr_pick($row, array('update_info', 'news_reason'), ''));

include $_SERVER['DOCUMENT_ROOT'].'/templates/'.$config['template'].'/blocks/block.releases.news.php';			?>
		<?php } ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-newrel-list]');
	var buttons = document.querySelectorAll('[data-newrel-view]');
	var storageKey = 'litetracker_newreleases_view';

	if (!list || !buttons.length) {
		return;
	}

	var currentView = 'compact';

	try {
		var saved = localStorage.getItem(storageKey);
		if (saved === 'full' || saved === 'compact') {
			currentView = saved;
		}
	} catch (e) {}

	function setView(view) {
		if (view !== 'full' && view !== 'compact') {
			view = 'compact';
		}

		list.setAttribute('data-view', view);

		for (var i = 0; i < buttons.length; i++) {
			var active = buttons[i].getAttribute('data-newrel-view') === view;
			buttons[i].classList.toggle('is-active', active);
		}

		try {
			localStorage.setItem(storageKey, view);
		} catch (e) {}
	}

	for (var i = 0; i < buttons.length; i++) {
		buttons[i].addEventListener('click', function () {
			setView(this.getAttribute('data-newrel-view'));
		});
	}

	setView(currentView);
});
</script>

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

function home_fetch_json($url, $timeout = 4)
{
	$context = stream_context_create(array(
		'http' => array(
			'timeout' => max(1, (int) $timeout),
			'ignore_errors' => true,
		),
	));
	$raw = @file_get_contents($url, false, $context);
	if (!is_string($raw) || $raw === '') {
		return null;
	}

	$decoded = json_decode($raw, true);

	return (is_array($decoded) ? $decoded : null);
}

function home_format_rate_number($value, $decimals = 2, $trim = true)
{
	$number = number_format((float) $value, (int) $decimals, '.', '');
	if (!$trim) {
		return $number;
	}

	return rtrim(rtrim($number, '0'), '.');
}

function home_format_btc_compact($value)
{
	$value = (float) $value;
	if ($value >= 1000) {
		return home_format_rate_number($value / 1000, 1, true).'K';
	}

	return home_format_rate_number($value, 2, true);
}

function home_widget_date_label($timestamp = null)
{
	$monthNames = array(
		'января',
		'февраля',
		'марта',
		'апреля',
		'мая',
		'июня',
		'июля',
		'августа',
		'сентября',
		'октября',
		'ноября',
		'декабря',
	);
	$now = ($timestamp === null ? time() : (int) $timestamp);
	$monthIndex = max(0, min(11, (int) date('n', $now) - 1));

	return date('j', $now).' '.$monthNames[$monthIndex];
}

function home_rates_payload()
{
	$dateLabel = home_widget_date_label();

	$result = array(
		'dateLabel' => $dateLabel,
		'rates' => array(
			'usd' => array('label' => 'USD', 'value' => '—', 'trend' => 'neutral'),
			'eur' => array('label' => 'EUR', 'value' => '—', 'trend' => 'neutral'),
			'btc' => array('label' => 'BTC/USD', 'value' => '—', 'trend' => 'neutral'),
			'ton' => array('label' => 'TON/USD', 'value' => '—', 'trend' => 'neutral'),
		),
	);

	$fiat = home_fetch_json('https://www.cbr-xml-daily.ru/daily_json.js');
	if (!empty($fiat['Valute']['USD']) && is_array($fiat['Valute']['USD'])) {
		$usd = $fiat['Valute']['USD'];
		$current = (float) ($usd['Value'] ?? 0);
		$previous = (float) ($usd['Previous'] ?? 0);
		if ($current > 0) {
			$result['rates']['usd']['value'] = home_format_rate_number($current, 2, true);
			$result['rates']['usd']['trend'] = ($current >= $previous ? 'up' : 'down');
		}
	}
	if (!empty($fiat['Valute']['EUR']) && is_array($fiat['Valute']['EUR'])) {
		$eur = $fiat['Valute']['EUR'];
		$current = (float) ($eur['Value'] ?? 0);
		$previous = (float) ($eur['Previous'] ?? 0);
		if ($current > 0) {
			$result['rates']['eur']['value'] = home_format_rate_number($current, 2, true);
			$result['rates']['eur']['trend'] = ($current >= $previous ? 'up' : 'down');
		}
	}

	$crypto = home_fetch_json('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,the-open-network&vs_currencies=usd&include_24hr_change=true');
	if (!empty($crypto['bitcoin']) && is_array($crypto['bitcoin'])) {
		$btcPrice = (float) ($crypto['bitcoin']['usd'] ?? 0);
		$btcDelta = (float) ($crypto['bitcoin']['usd_24h_change'] ?? 0);
		if ($btcPrice > 0) {
			$result['rates']['btc']['value'] = home_format_btc_compact($btcPrice);
			$result['rates']['btc']['trend'] = ($btcDelta >= 0 ? 'up' : 'down');
		}
	}
	if (!empty($crypto['the-open-network']) && is_array($crypto['the-open-network'])) {
		$tonPrice = (float) ($crypto['the-open-network']['usd'] ?? 0);
		$tonDelta = (float) ($crypto['the-open-network']['usd_24h_change'] ?? 0);
		if ($tonPrice > 0) {
			$result['rates']['ton']['value'] = home_format_rate_number($tonPrice, 2, true);
			$result['rates']['ton']['trend'] = ($tonDelta >= 0 ? 'up' : 'down');
		}
	}

	return $result;
}

if (!empty($_GET['home_widget_rates'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	echo json_encode(home_rates_payload(), JSON_UNESCAPED_UNICODE);
	die();
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
$where[] = lt_torrent_status_filter_sql($USER, 't');
if (!$PRIV['details_banned_view'] && !lt_torrent_can_moderate($USER)) {
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

$torrentAuthorsById = lt_torrent_preload_author_users($rows);
$torrentAuthorPrivilegesByClass = lt_torrent_preload_author_privileges($torrentAuthorsById);

function home_render_torrent_cards($rows, $categoriesById, $usersById, $privilegesByClass)
{
	ob_start();
	foreach ($rows as $row) {
		$category = (!empty($categoriesById[(int) $row['id_category']]) ? $categoriesById[(int) $row['id_category']] : array('id' => 0, 'name' => 'Без категории', 'image' => ''));
		$user = (array) ($usersById[(int) $row['id_user']] ?? array());
		$torrentCard = lt_torrent_prepare_browse_card($row, $category, $user, $privilegesByClass);
		include __DIR__.'/templates/default/tpl.torrent.card.php';
	}

	return ob_get_clean();
}

$currentPage = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
$perPage = 5;
$pagesCount = ($countTorrent > 0 ? (int) ceil($countTorrent / $perPage) : 0);
$nextPage = ($currentPage + 1 < $pagesCount ? $currentPage + 1 : null);
$nextPageUrl = ($nextPage !== null ? home_build_url(array('page' => $nextPage, 'view' => $view, 'sort' => $sort, 'ajax' => 1)) : '');

if ($isAjaxLoad) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'html' => home_render_torrent_cards($rows, $categoriesById, $torrentAuthorsById, $torrentAuthorPrivilegesByClass),
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
	<section class="home-market-strip" data-home-market data-market-url="index.php?home_widget_rates=1">
		<div class="home-market-date" data-home-market-date><?=htmlspecialchars(home_widget_date_label(), ENT_QUOTES, 'UTF-8');?></div>
		<div class="home-market-items" data-home-market-items>
			<span class="home-market-item" data-home-market-item="usd"><span class="home-market-label">USD</span> <span class="home-market-value">—</span> <span class="home-market-trend" data-trend="neutral">•</span></span>
			<span class="home-market-item" data-home-market-item="eur"><span class="home-market-label">EUR</span> <span class="home-market-value">—</span> <span class="home-market-trend" data-trend="neutral">•</span></span>
			<span class="home-market-item" data-home-market-item="btc"><span class="home-market-label">BTC/USD</span> <span class="home-market-value">—</span> <span class="home-market-trend" data-trend="neutral">•</span></span>
			<span class="home-market-item" data-home-market-item="ton"><span class="home-market-label">TON/USD</span> <span class="home-market-value">—</span> <span class="home-market-trend" data-trend="neutral">•</span></span>
		</div>
	</section>
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
			<?=home_render_torrent_cards($rows, $categoriesById, $torrentAuthorsById, $torrentAuthorPrivilegesByClass);?>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var list = document.querySelector('[data-browse-list]');
	var viewButtons = document.querySelectorAll('[data-browse-view-toggle]');
	var loadMoreButton = document.querySelector('[data-home-load-more]');
	var paginationHtml = document.querySelector('[data-home-pagination-html]');
	var marketRoot = document.querySelector('[data-home-market]');
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

	if (marketRoot) {
		var marketDate = marketRoot.querySelector('[data-home-market-date]');
		var marketUrl = marketRoot.getAttribute('data-market-url') || 'index.php?home_widget_rates=1';
		var marketTimers = {
			refresh: null
		};

		function setMarketTrend(element, trend) {
			var marker = element.querySelector('.home-market-trend');
			if (!marker) {
				return;
			}

			if (trend === 'up') {
				marker.textContent = '▲';
				marker.setAttribute('data-trend', 'up');
				return;
			}
			if (trend === 'down') {
				marker.textContent = '▼';
				marker.setAttribute('data-trend', 'down');
				return;
			}

			marker.textContent = '•';
			marker.setAttribute('data-trend', 'neutral');
		}

		function renderMarket(payload) {
			if (!payload || typeof payload !== 'object') {
				return;
			}

			if (marketDate && payload.dateLabel) {
				marketDate.textContent = String(payload.dateLabel);
			}

			var rates = payload.rates || {};
			var keys = ['usd', 'eur', 'btc', 'ton'];
			for (var i = 0; i < keys.length; i++) {
				var key = keys[i];
				var entry = marketRoot.querySelector('[data-home-market-item="' + key + '"]');
				if (!entry) {
					continue;
				}

				var data = rates[key] || {};
				var label = entry.querySelector('.home-market-label');
				var value = entry.querySelector('.home-market-value');
				if (label && data.label) {
					label.textContent = String(data.label);
				}
				if (value) {
					value.textContent = (data.value ? String(data.value) : '—');
				}
				setMarketTrend(entry, data.trend || 'neutral');
			}
		}

		function loadMarketRates() {
			var separator = (marketUrl.indexOf('?') === -1 ? '?' : '&');
			var requestUrl = marketUrl + separator + '_t=' + Date.now();
			fetch(requestUrl, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
				.then(function (response) {
					if (!response.ok) {
						throw new Error('market load failed');
					}
					return response.json();
				})
				.then(function (payload) {
					renderMarket(payload);
				})
				.catch(function () {});
		}

		loadMarketRates();
		marketTimers.refresh = window.setInterval(loadMarketRates, 300000);
	}

	setView(initialView, false);
});
</script>
<?php
stdfoot();
?>

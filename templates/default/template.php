<?php

if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

function begin_frame($caption = "", $width = "100", $center = false, $padding = null)
{
	$bodyClass = 'frame-body';
	if ($padding !== null && (int) $padding === 0) {
		$bodyClass .= ' frame-body-no-padding';
	}
	?>
<section class="frame">
	<?php if ($caption !== '') { ?>
	<header class="frame-header">
		<h2 class="frame-title"><?=$caption;?></h2>
	</header>
	<?php } ?>
	<div class="<?=$bodyClass;?>">
	<?php
}

function end_frame()
{
	?>
	</div>
</section>
	<?php
}

function template_truncate_text($text, $length = 180)
{
	$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
	if ($text === '') {
		return '';
	}

	if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
		return rtrim(mb_substr($text, 0, $length, 'UTF-8')).'...';
	}

	return $text;
}

function template_format_number($value)
{
	$value = (float) $value;

	if ((float) (int) $value === $value) {
		return number_format((int) $value, 0, '.', ' ');
	}

	return rtrim(rtrim(number_format($value, 2, '.', ' '), '0'), '.');
}

function template_current_month_label()
{
	static $months = array(
		1 => 'Январь',
		2 => 'Февраль',
		3 => 'Март',
		4 => 'Апрель',
		5 => 'Май',
		6 => 'Июнь',
		7 => 'Июль',
		8 => 'Август',
		9 => 'Сентябрь',
		10 => 'Октябрь',
		11 => 'Ноябрь',
		12 => 'Декабрь',
	);

	$month = (int) date('n');

	return (!empty($months[$month]) ? $months[$month] : 'Сбор');
}

function template_get_sidebar_news()
{
	global $db, $memcached;

	if (false === ($news = $memcached->get('sidebar_news_all'))) {
		$news = array();
		$query = $db->query("SELECT id, name, text, date FROM news ORDER BY date DESC LIMIT 8");

		while ($row = $db->get_row($query)) {
			$news[] = $row;
		}

		$memcached->set('sidebar_news_all', $news, 0, 15 * 60);
	}

	return (is_array($news) ? $news : array());
}

function render_standard_sidebar()
{
	global $config, $USER;

	$buttonHref = trim((string) ($config['project_help_button_href'] ?? ''));
	if ($buttonHref === '') {
		$buttonHref = 'donate.php';
	}

	$buttonLabel = trim((string) ($config['project_help_button_label'] ?? ''));
	if ($buttonLabel === '') {
		$buttonLabel = 'Помочь проекту';
	}

	$helpText = trim((string) ($config['project_help_text'] ?? ''));
	if ($helpText === '') {
		$helpText = 'Оплата аренды сервера, принимаем любую помощь.';
	}

	$periodLabel = trim((string) ($config['project_help_period'] ?? ''));
	if ($periodLabel === '') {
		$periodLabel = template_current_month_label();
	}

	$currentAmount = (float) ($config['project_help_current'] ?? 0);
	$goalAmount = (float) ($config['project_help_goal'] ?? 0);
	$progress = ($goalAmount > 0 ? min(100, max(0, ($currentAmount / $goalAmount) * 100)) : 0);
	$progressLabel = $periodLabel.': '.template_format_number($currentAmount).' из '.template_format_number($goalAmount);
	$showProgress = ($goalAmount > 0);
	$newsItems = template_get_sidebar_news();
	?>
	<aside class="site-sidebar site-sidebar-right">
		<?php if (!lt_user_has_plus($USER ?? null)) { ?>
		<section class="sidebar-panel plus-promo-panel">
			<button class="plus-promo-close" type="button" aria-label="Скрыть" data-plus-promo-close>&times;</button>
			<div class="plus-promo-art" aria-hidden="true">
				<span class="plus-promo-gem"></span>
			</div>
			<h2 class="sidebar-panel-title">Подписка Plus</h2>
			<p class="plus-promo-copy">Без рекламы, реакции, красивый никнейм, видеоаватарка и другие функции Plus.</p>
			<button class="plus-promo-button" type="button" data-plus-benefits-open="1">От <?=template_format_number(lt_plus_month_bonus_price());?> бонусов в месяц</button>
		</section>
		<?php } ?>

		<section class="sidebar-panel project-help-panel">
			<h2 class="sidebar-panel-title"><a class="sidebar-panel-title-link" href="<?=htmlspecialchars($buttonHref, ENT_QUOTES, 'UTF-8');?>">Помощь проекту</a></h2>
			<p class="project-help-copy"><?=htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8');?></p>
			<?php if ($showProgress) { ?>
			<div class="project-help-progress" aria-label="<?=htmlspecialchars($progressLabel, ENT_QUOTES, 'UTF-8');?>">
				<div class="project-help-progress-fill" style="width: <?=$progress;?>%;"></div>
				<div class="project-help-progress-label"><?=htmlspecialchars($progressLabel, ENT_QUOTES, 'UTF-8');?></div>
			</div>
			<?php } ?>
			<a class="project-help-button" href="<?=htmlspecialchars($buttonHref, ENT_QUOTES, 'UTF-8');?>"><?=htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');?></a>
		</section>

		<?=lt_ads_render('sidebar');?>

		<section class="sidebar-panel sidebar-news-panel">
			<h2 class="sidebar-panel-title"><a class="sidebar-panel-title-link" href="news.php">Новости</a></h2>
			<?php if ($newsItems) { ?>
			<div class="sidebar-news-list">
				<?php foreach ($newsItems as $item) { ?>
				<?php
				$title = template_truncate_text(lt_fix_utf8_mojibake((string) $item['name']), 96);
				$excerpt = template_truncate_text(cleanhtml(lt_fix_utf8_mojibake((string) $item['text'])), 220);
				?>
				<article class="sidebar-news-item">
					<a class="sidebar-news-title" href="news.php?id=<?=$item['id'];?>"><?=htmlspecialchars($title, ENT_QUOTES, 'UTF-8');?></a>
					<div class="sidebar-news-date"><?=convent_date($item['date']);?></div>
					<?php if ($excerpt !== '') { ?>
					<div class="sidebar-news-excerpt"><?=htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');?></div>
					<?php } ?>
				</article>
				<?php } ?>
			</div>
			<?php } else { ?>
			<div class="sidebar-empty">Новостей пока нет.</div>
			<?php } ?>
		</section>

		<div class="sidebar-dynamic sidebar-dynamic-right">
			<?php show_blocks('r'); ?>
		</div>
	</aside>
	<?php
}

?>

<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$avatar = 'public/images/default_avatar.gif';
if ($USER && !empty($USER['avatar'])) {
	$avatar = 'public/avatars/small/'.$USER['avatar'];
}

$mainNav = array(
	array('href' => 'browse.php?act=all', 'label' => 'Торренты'),
	array('href' => 'news.php', 'label' => 'Новости'),
	array('href' => 'faq.php', 'label' => 'FAQ'),
);

if ($USER) {
	$mainNav[] = array(
		'href' => 'my.mail.php',
		'label' => 'Сообщения'.(!empty($USER['num_messages']) ? ' ('.$USER['num_messages'].')' : ''),
	);
	$mainNav[] = array('href' => 'my.setting.php', 'label' => 'Настройки');
	$mainNav[] = array('href' => 'exit.php', 'label' => 'Выход');
} else {
	$mainNav[] = array('href' => 'signup.php', 'label' => 'Регистрация', 'tone' => 'primary');
	$mainNav[] = array('href' => 'login.php', 'label' => 'Вход', 'tone' => 'muted');
}

$sidebarNav = array(
	array('href' => 'browse.php?act=all', 'label' => 'Каталог релизов'),
	array('href' => 'users.php', 'label' => 'Пользователи'),
	array('href' => 'news.php', 'label' => 'Обновления'),
	array('href' => 'faq.php', 'label' => 'Помощь'),
	array('href' => 'shop.php', 'label' => 'Магазин'),
	array('href' => 'upload.php', 'label' => 'Добавить релиз'),
);

$managementNav = array();
if ($USER) {
	if (!empty($PRIV['search_query'])) {
		$managementNav[] = array('href' => 'search_query.php', 'label' => $language['template_12']);
	}
	if (!empty($PRIV['multitracker_accounts'])) {
		$managementNav[] = array('href' => 'multitracker_accounts.php', 'label' => $language['template_13']);
	}
	if (!empty($PRIV['ip_util'])) {
		$managementNav[] = array('href' => 'ip.util.php', 'label' => $language['template_14']);
	}
	if (!empty($PRIV['sessions_view'])) {
		$managementNav[] = array('href' => 'sessions.php', 'label' => $language['template_15']);
	}
	if (!empty($PRIV['cats'])) {
		$managementNav[] = array('href' => 'categories.php', 'label' => $language['template_16']);
	}
	if (!empty($PRIV['messages'])) {
		$managementNav[] = array('href' => 'messages.php', 'label' => $language['template_27']);
	}
	if (!empty($PRIV['news_add'])) {
		$managementNav[] = array('href' => 'news.php?act=add', 'label' => $language['template_17']);
	}
	if (!empty($PRIV['user_add'])) {
		$managementNav[] = array('href' => 'user_add.php', 'label' => $language['template_29']);
	}
	if (!empty($PRIV['faq_moderate'])) {
		$managementNav[] = array('href' => 'faq.php?act=topic&type=add', 'label' => 'Добавить FAQ');
	}
	if (!empty($PRIV['EDIT_PRIV'])) {
		$managementNav[] = array('href' => 'edit_priv.php', 'label' => $language['template_28']);
		$managementNav[] = array('href' => 'blocks.php', 'label' => 'Блоки');
	}
	if (!empty($PRIV['polls_moderate'])) {
		$managementNav[] = array('href' => 'polls.php', 'label' => 'Опросы');
	}
}

$pageTitle = trim(strip_tags((string) $title));
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="<?=$language['charset'];?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?=$header;?>
<link href="templates/<?=$tpl;?>/css/buttons.css" rel="stylesheet" type="text/css">
<link href="templates/<?=$tpl;?>/css/my.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="site-header-band">
	<div class="site-shell site-shell-band">
	<header class="site-header">
		<div class="site-topbar">
			<a class="site-brand" href="index.php">
				<span class="site-brand-mark">LT</span>
				<span class="site-brand-copy">
					<strong>LiteTracker <?=htmlspecialchars(LITETRACKER_NAME, ENT_QUOTES, 'UTF-8');?></strong>
					<small>release <?=htmlspecialchars(LITETRACKER_VERSION, ENT_QUOTES, 'UTF-8');?></small>
				</span>
			</a>

			<nav class="site-nav" aria-label="Основная навигация">
				<?php foreach ($mainNav as $item) { ?>
				<a class="site-nav-link<?=(!empty($item['tone']) ? ' site-nav-link-'.$item['tone'] : '');?>" href="<?=$item['href'];?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>
			</nav>
		</div>
	</header>
	</div>
</div>

<div class="site-shell site-shell-content">
	<div class="site-layout">
		<aside class="site-sidebar">
			<section class="sidebar-panel sidebar-profile">
				<h2 class="sidebar-card-title"><?=($USER ? 'Аккаунт' : 'Гостевой режим');?></h2>
				<div class="sidebar-card-copy">
					<div class="sidebar-profile-name"><?=($USER ? get_user_color($USER['class'], $USER['name']) : 'Гость');?></div>
					<p class="sidebar-profile-note"><?=($USER ? 'Онлайн и готов к работе.' : 'Войдите или зарегистрируйтесь, чтобы открыть все возможности трекера.');?></p>
				</div>

				<?php if ($USER) { ?>
				<div class="sidebar-facts">
					<div class="sidebar-fact">
						<span>Раздано</span>
						<strong><?=mksize($USER['uploaded']);?></strong>
					</div>
					<div class="sidebar-fact">
						<span>Скачано</span>
						<strong><?=mksize($USER['downloaded']);?></strong>
					</div>
					<div class="sidebar-fact">
						<span>Баланс</span>
						<strong><?=$USER['voice'];?> y.e</strong>
					</div>
				</div>

				<div class="sidebar-button-stack">
					<a class="sidebar-button sidebar-button-primary" href="my.mail.php">Сообщения<?=(!empty($USER['num_messages']) ? ' ('.$USER['num_messages'].')' : '');?></a>
					<a class="sidebar-button" href="my.friends.php">Друзья<?=(!empty($USER['num_friends']) ? ' ('.$USER['num_friends'].')' : '');?></a>
					<a class="sidebar-button" href="my.setting.php">Профиль</a>
				</div>
				<?php } else { ?>
				<div class="sidebar-button-stack">
					<a class="sidebar-button sidebar-button-primary" href="signup.php">Создать аккаунт</a>
					<a class="sidebar-button" href="login.php">Войти</a>
				</div>
				<?php } ?>
			</section>

			<section class="sidebar-panel">
				<h2 class="sidebar-card-title">Навигация</h2>
				<div class="sidebar-list">
					<?php foreach ($sidebarNav as $item) { ?>
					<a class="sidebar-list-link" href="<?=$item['href'];?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></a>
					<?php } ?>
				</div>
			</section>

			<?php if ($managementNav) { ?>
			<section class="sidebar-panel">
				<h2 class="sidebar-card-title">Управление</h2>
				<div class="sidebar-list">
					<?php foreach ($managementNav as $item) { ?>
					<a class="sidebar-list-link" href="<?=$item['href'];?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></a>
					<?php } ?>
				</div>
			</section>
			<?php } ?>

			<div class="sidebar-dynamic">
				<?php show_blocks('l'); ?>
			</div>
		</aside>

		<main class="site-main">
			<div class="blockContent">
				<?php
				if (!empty($USER['bad_rating']) && !empty($PRIV['bad_rating'])) {
					begin_frame();
					msg($language['template_6']);
					end_frame();
				}
				?>

				<?php show_blocks('c'); ?>

<?php
if (!defined('LITETRACKER')) {
	die('Direct access denied.');
}

$avatar = 'public/images/default_avatar.gif';
if ($USER && !empty($USER['avatar']) && is_file('public/avatars/small/'.$USER['avatar'])) {
	$avatar = 'public/avatars/small/'.$USER['avatar'];
}

$mainNav = array(
	array('href' => 'browse.php?act=all', 'label' => 'Торренты'),
);

if ($USER) {
	$userMenu = array(
		array('href' => 'my.setting.php', 'label' => 'Настройки'),
		array('href' => 'my.mail.php', 'label' => 'Сообщения'),
		array('href' => 'my.book.php', 'label' => 'Закладки'),
		array('href' => profile_href((int) $USER['id']), 'label' => 'Профиль'),
		array('href' => 'exit.php', 'label' => 'Выход'),
	);
}

$messagesCount = (!empty($USER['num_messages']) ? (int) $USER['num_messages'] : 0);
$messagesBadge = ($messagesCount > 99 ? '99+' : (string) $messagesCount);
$requestUri = ltrim((string) ($_SERVER['REQUEST_URI'] ?? ''), '/');
$loginHref = 'login.php';
if ($requestUri !== '' && strpos($requestUri, 'login.php') !== 0) {
	$loginHref .= '?referer='.rawurlencode($requestUri);
}
$bodyClasses = array();
if (!empty($USER['theme_dark'])) {
	$bodyClasses[] = 'theme-dark';
}
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
<body<?=($bodyClasses ? ' class="'.htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES, 'UTF-8').'"' : '');?>>
<div class="site-wrapper">
	<div class="site-content">
<div class="site-header-band">
	<div class="site-shell site-shell-band">
	<header class="site-header">
		<div class="site-topbar">
			<a class="site-brand" href="index.php">
				<span class="site-brand-mark">
					<img src="templates/<?=$tpl;?>/images/ubllogo1.png" alt="LiteTracker" width="27" height="20">
				</span>
				<span class="site-brand-copy">
					<strong><?=htmlspecialchars($config['sitename'], ENT_QUOTES, 'UTF-8');?></strong>
					<small>Торрент-трекер</small>
				</span>
			</a>

			<nav class="site-nav" aria-label="Основная навигация">
				<?php foreach ($mainNav as $item) { ?>
				<a class="site-nav-link" href="<?=$item['href'];?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></a>
				<?php } ?>
			</nav>

			<div class="site-header-tools">
				<?php if ($USER) { ?>
				<a class="site-alert-button<?=($messagesCount > 0 ? ' site-alert-button-active' : '');?>" href="my.mail.php?act=conversation&amp;system=1" aria-label="Оповещения<?=($messagesCount > 0 ? ': '.$messagesBadge : '');?>">
					<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M12 3a5 5 0 0 0-5 5v2.42c0 .8-.32 1.56-.88 2.12L4.3 14.36a1 1 0 0 0 .7 1.71h14a1 1 0 0 0 .7-1.71l-1.82-1.82A3 3 0 0 1 17 10.42V8a5 5 0 0 0-5-5Zm0 18a3 3 0 0 0 2.82-2H9.18A3 3 0 0 0 12 21Z" fill="currentColor"/>
					</svg>
					<?php if ($messagesCount > 0) { ?>
					<span class="site-alert-badge"><?=$messagesBadge;?></span>
					<?php } ?>
				</a>

				<details class="site-user-dropdown">
					<summary class="site-user-summary">
						<span class="site-user-avatar"><img src="<?=$avatar;?>" alt="<?=htmlspecialchars($USER['name'], ENT_QUOTES, 'UTF-8');?>" width="38" height="38"></span>
						<span class="site-user-name"><?=get_user_color((int) ($USER['class'] ?? 0), htmlspecialchars((string) $USER['name'], ENT_QUOTES, 'UTF-8'));?></span>
						<span class="site-user-arrow">
							<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
								<path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</summary>
					<div class="site-user-menu">
						<?php foreach ($userMenu as $item) { ?>
						<a class="site-user-menu-link" href="<?=$item['href'];?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></a>
						<?php } ?>
					</div>
				</details>
				<?php } else { ?>
				<div class="site-auth-actions">
					<?php if (!empty($config['registeronline'])) { ?>
					<a class="site-auth-link site-auth-link-primary" href="signup.php">
						<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm8.5 2c-2 0-6.5 1-6.5 3v2h13v-2c0-2-4.5-3-6.5-3ZM8 13c-2.33 0-7 1.17-7 3.5V18h7v-2c0-.83.35-1.77 1.41-2.56A7.79 7.79 0 0 0 8 13Zm3-8h1V2h2v3h3v2h-3v3h-2V7h-3Z" fill="currentColor"/>
						</svg>
						<span>Регистрация</span>
					</a>
					<?php } ?>
					<a class="site-auth-link site-auth-link-login" href="<?=htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8');?>">
						<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 1.8-6 4v1h12v-1c0-2.2-2.7-4-6-4Z" fill="currentColor"/>
						</svg>
						<span>Вход</span>
					</a>
				</div>
				<?php } ?>
			</div>
		</div>
	</header>
	</div>
</div>




<div class="site-shell site-shell-content">
	<div class="site-layout">
		<main class="site-main">
			<div class="blockContent">
				<?php /*
				if (!empty($USER['bad_rating']) && !empty($PRIV['bad_rating'])) {
					begin_frame();
					msg($language['template_6']);
					end_frame();
				} */
				?>

				<?php show_blocks('c'); ?>

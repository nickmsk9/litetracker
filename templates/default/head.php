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

	if (user_wall_reports_can_moderate()) {
		array_splice($userMenu, 4, 0, array(
			array('href' => user_wall_reports_href(), 'label' => 'Жалобы'),
		));
	}
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
if (!empty($GLOBALS['LITETRACKER_SIGNUP_MODAL_FRAME'])) {
	$bodyClasses[] = 'signup-modal-frame';
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="<?=$language['charset'];?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?=$header;?>
<link href="templates/<?=$tpl;?>/css/my.css" rel="stylesheet" type="text/css">
<?php if (!$USER && !empty($config['registeronline'])) { ?>
<style>
.site-signup-overlay{
	position:fixed;
	inset:0;
	z-index:10000;
	display:flex;
	align-items:center;
	justify-content:center;
	padding:20px;
}

.site-signup-overlay[hidden]{
	display:none;
}

.site-signup-overlay-backdrop{
	position:absolute;
	inset:0;
	background:rgba(0, 0, 0, .56);
}

.site-signup-overlay-dialog{
	position:relative;
	width:min(100%, 760px);
	height:min(100%, 600px);
	background:#f4f5f7;
	border-radius:4px;
	overflow:hidden;
	box-shadow:0 24px 60px rgba(0, 0, 0, .35);
	z-index:1;
}

.site-signup-overlay-close{
	position:fixed;
	top:10px;
	right:16px;
	width:36px;
	height:36px;
	border:0;
	border-radius:2px;
	background:transparent;
	color:#ffffff;
	font-size:36px;
	line-height:1;
	cursor:pointer;
	z-index:10001;
}

.site-signup-overlay-close:hover{
	opacity:.85;
}

.site-signup-frame{
	display:block;
	width:100%;
	height:100%;
	border:0;
	background:#f4f5f7;
}

body.site-signup-modal-open{
	overflow:hidden;
}
</style>
<?php } ?>
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

<?php if (!$USER && !empty($config['registeronline'])) { ?>
<div class="site-signup-overlay" id="site-signup-overlay" hidden>
	<div class="site-signup-overlay-backdrop" data-signup-close="1"></div>
	<div class="site-signup-overlay-dialog" role="dialog" aria-modal="true" aria-label="Регистрация">
		<iframe class="site-signup-frame" id="site-signup-frame" title="Регистрация" src="about:blank"></iframe>
	</div>
	<button class="site-signup-overlay-close" type="button" aria-label="Закрыть" data-signup-close="1">&times;</button>
</div>

<script>
(function(){
	var overlay = document.getElementById('site-signup-overlay');
	var frame = document.getElementById('site-signup-frame');

	if (!overlay || !frame) {
		return;
	}

	var body = document.body;

	function buildModalHref(rawHref) {
		var url;

		try {
			url = new URL(rawHref || 'signup.php', window.location.href);
		} catch (e) {
			url = new URL('signup.php', window.location.href);
		}

		if (!/signup\.php$/i.test(url.pathname)) {
			url = new URL('signup.php', window.location.href);
		}

		url.searchParams.set('modal', '1');

		if (!url.searchParams.get('referer')) {
			var referer = window.location.pathname.replace(/^\//, '') + window.location.search;
			url.searchParams.set('referer', referer);
		}

		return url.pathname + '?' + url.searchParams.toString();
	}

	function openSignupModal(href) {
		frame.src = buildModalHref(href);
		overlay.hidden = false;
		body.classList.add('site-signup-modal-open');
	}

	function closeSignupModal() {
		overlay.hidden = true;
		body.classList.remove('site-signup-modal-open');
		frame.src = 'about:blank';
	}

	document.addEventListener('click', function(event){
		var closeTrigger = event.target.closest('[data-signup-close="1"]');
		if (closeTrigger) {
			event.preventDefault();
			closeSignupModal();
			return;
		}

		var link = event.target.closest('a[href]');
		if (!link) {
			return;
		}

		if (link.hasAttribute('data-signup-direct') || link.target === '_blank' || link.hasAttribute('download')) {
			return;
		}

		var href = String(link.getAttribute('href') || '');
		if (!/(^|\/)signup\.php(?:\?|$)/i.test(href)) {
			return;
		}

		event.preventDefault();
		openSignupModal(href);
	});

	document.addEventListener('keydown', function(event){
		if (event.key === 'Escape' && !overlay.hidden) {
			closeSignupModal();
		}
	});

	window.ltCloseSignupModal = closeSignupModal;
})();
</script>
<?php } ?>




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

				<?php if (empty($GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'])) { ?>
					<?php show_blocks('c'); ?>
				<?php } ?>

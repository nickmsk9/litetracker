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

if ($USER && admin_dashboard_can_access($USER, $PRIV)) {
    $mainNav[] = array('href' => 'admin.php', 'label' => 'Админка');
}

if ($USER) {
    $userMenu = array(
        array('href' => 'my.setting.php', 'label' => 'Настройки', 'icon' => 'settings'),
        array('href' => 'my.mail.php', 'label' => 'Сообщения', 'icon' => 'messages'),
        array('href' => 'my.book.php', 'label' => 'Закладки', 'icon' => 'bookmarks'),
        array('href' => profile_href((int) $USER['id']), 'label' => 'Профиль', 'icon' => 'profile'),
        array('href' => 'exit.php', 'label' => 'Выход', 'icon' => 'logout'),
    );

    if (user_wall_reports_can_moderate()) {
        array_splice($userMenu, 4, 0, array(
            array('href' => user_wall_reports_href(), 'label' => 'Жалобы', 'icon' => 'reports'),
        ));
    }
}

$messagesCount = 0;
if ($USER) {
	$messagesCount = max(0, (int) ($USER['num_messages'] ?? 0));
}
$openWallReportsCount = 0;
if ($USER && user_wall_reports_can_moderate()) {
	user_wall_reports_ensure_table();
	$openWallReportsRow = $db->super_query("SELECT COUNT(*) AS c FROM `".user_wall_reports_table_name()."` WHERE status = 'open'");
	$openWallReportsCount = (int) ($openWallReportsRow['c'] ?? 0);
}
$alertCount = $messagesCount;
$alertBadge = ($alertCount > 99 ? '99+' : (string) $alertCount);
$messagesHref = 'my.mail.php';
$messagesLabel = 'Личные сообщения'.($alertCount > 0 ? ': '.$alertBadge : '');
$requestUri = ltrim((string) ($_SERVER['REQUEST_URI'] ?? ''), '/');
$loginHref = 'login.php';
if ($requestUri !== '' && strpos($requestUri, 'login.php') !== 0) {
    $loginHref .= '?referer='.rawurlencode($requestUri);
}
$bodyClasses = array();
if (!empty($USER['theme_dark'])) {
    $bodyClasses[] = 'theme-dark';
}
if (lt_is_mobile_request()) {
    $bodyClasses[] = 'is-mobile';
}
$welcomeBanner = '';
if ($USER && !empty($_SESSION['lt_welcome_banner'])) {
	$welcomeBanner = trim((string) $_SESSION['lt_welcome_banner']);
	unset($_SESSION['lt_welcome_banner']);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="<?=$language['charset'];?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?=$header;?>
<link href="templates/<?=$tpl;?>/css/my.css" rel="stylesheet" type="text/css">
<link href="templates/<?=$tpl;?>/css/ui.css" rel="stylesheet" type="text/css">
</head>
<body<?=($bodyClasses ? ' class="'.htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES, 'UTF-8').'"' : '');?>>
<div class="site-wrapper">
	<button class="site-scroll-toggle" id="site-scroll-toggle" type="button" aria-label="Прокрутить вниз">↓</button>
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

			<div class="site-header-tools<?=($USER ? ' site-header-tools-auth' : '');?>">
				<?php if ($USER) { ?>
				<a class="site-alert-button<?=($alertCount > 0 ? ' site-alert-button-active' : '');?>" href="<?=$messagesHref;?>" aria-label="<?=htmlspecialchars($messagesLabel, ENT_QUOTES, 'UTF-8');?>">
					<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M12 3a5 5 0 0 0-5 5v2.42c0 .8-.32 1.56-.88 2.12L4.3 14.36a1 1 0 0 0 .7 1.71h14a1 1 0 0 0 .7-1.71l-1.82-1.82A3 3 0 0 1 17 10.42V8a5 5 0 0 0-5-5Zm0 18a3 3 0 0 0 2.82-2H9.18A3 3 0 0 0 12 21Z" fill="currentColor"/>
					</svg>
					<?php if ($alertCount > 0) { ?>
					<span class="site-alert-badge"><?=$alertBadge;?></span>
					<?php } ?>
				</a>

				<details class="site-user-dropdown">
					<summary class="site-user-summary">
						<span class="site-user-avatar"><img src="<?=$avatar;?>" alt="<?=htmlspecialchars($USER['name'], ENT_QUOTES, 'UTF-8');?>" width="38" height="38"></span>
						<span class="site-user-name"><?=get_user_color((int) ($USER['class'] ?? 0), htmlspecialchars((string) $USER['name'], ENT_QUOTES, 'UTF-8'), $USER);?></span>
						<span class="site-user-arrow">
							<svg class="site-icon" viewBox="0 0 24 24" aria-hidden="true">
								<path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</summary>
					<div class="site-user-menu">
						<?php foreach ($userMenu as $item) { ?>
						<a class="site-user-menu-link" href="<?=$item['href'];?>">
							<span class="site-user-menu-icon" aria-hidden="true">
								<?php if (($item['icon'] ?? '') === 'settings') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M12 8.6A3.4 3.4 0 1 0 12 15.4 3.4 3.4 0 0 0 12 8.6Zm8 4.1-1.74-.58a6.73 6.73 0 0 0-.47-1.13l.83-1.63a.9.9 0 0 0-.17-1.05l-1.77-1.77a.9.9 0 0 0-1.05-.17l-1.63.83c-.36-.19-.74-.34-1.13-.47L12.3 4a.9.9 0 0 0-.86-.63h-2.5a.9.9 0 0 0-.86.63l-.58 1.74c-.39.13-.77.28-1.13.47l-1.63-.83a.9.9 0 0 0-1.05.17L1.92 7.32a.9.9 0 0 0-.17 1.05l.83 1.63c-.19.36-.34.74-.47 1.13L.37 12.7a.9.9 0 0 0-.63.86v2.5c0 .39.25.73.63.86l1.74.58c.13.39.28.77.47 1.13l-.83 1.63a.9.9 0 0 0 .17 1.05l1.77 1.77c.28.28.71.35 1.05.17l1.63-.83c.36.19.74.34 1.13.47l.58 1.74c.13.38.47.63.86.63h2.5c.39 0 .73-.25.86-.63l.58-1.74c.39-.13.77-.28 1.13-.47l1.63.83c.34.18.77.11 1.05-.17l1.77-1.77a.9.9 0 0 0 .17-1.05l-.83-1.63c.19-.36.34-.74.47-1.13l1.74-.58c.38-.13.63-.47.63-.86v-2.5a.9.9 0 0 0-.63-.86Z" fill="currentColor"/></svg>
								<?php } elseif (($item['icon'] ?? '') === 'messages') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7A2.5 2.5 0 0 1 17.5 16h-7.7L5 19.6V16.9A2.5 2.5 0 0 1 4 15V6.5Z" fill="currentColor"/></svg>
								<?php } elseif (($item['icon'] ?? '') === 'bookmarks') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M7 4.5A1.5 1.5 0 0 1 8.5 3h7A1.5 1.5 0 0 1 17 4.5V21l-5-3.2L7 21V4.5Z" fill="currentColor"/></svg>
								<?php } elseif (($item['icon'] ?? '') === 'profile') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-7 8a7 7 0 0 1 14 0v1H5v-1Z" fill="currentColor"/></svg>
								<?php } elseif (($item['icon'] ?? '') === 'logout') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<?php } elseif (($item['icon'] ?? '') === 'reports') { ?>
								<svg viewBox="0 0 24 24" fill="none"><path d="M12 3 2.7 19.5A1 1 0 0 0 3.58 21h16.84a1 1 0 0 0 .88-1.5L12 3Zm0 6.5v4.5m0 3h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<?php } ?>
							</span>
							<span><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?></span>
						</a>
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

<?php if (!$USER) { ?>
<div class="site-auth-overlay" id="site-auth-overlay" hidden>
	<div class="site-auth-overlay-backdrop" data-auth-close="1"></div>
	<div class="site-auth-overlay-dialog" id="site-auth-overlay-dialog" role="dialog" aria-modal="true" aria-label="Авторизация">
		<div class="site-auth-overlay-panel">
			<iframe class="site-auth-frame" id="site-auth-frame" title="Авторизация" src="about:blank" scrolling="auto"></iframe>
		</div>
	</div>
</div>

<script>
(function(){
	var overlay = document.getElementById('site-auth-overlay');
	var frame = document.getElementById('site-auth-frame');
	var dialog = document.getElementById('site-auth-overlay-dialog');
	var defaultFrameHeight = 340;
	var defaultDialogWidth = 320;

	function maxFrameHeight() {
		return Math.max(320, window.innerHeight - 40);
	}

	function setFrameHeight(height) {
		var numericHeight = Number(height) || defaultFrameHeight;
		var clampedHeight = Math.max(320, Math.min(maxFrameHeight(), Math.round(numericHeight)));
		frame.style.height = clampedHeight + 'px';
	}

	function maxDialogWidth() {
		return Math.max(292, window.innerWidth - 40);
	}

	function setDialogWidth(width) {
		var numericWidth = Number(width) || defaultDialogWidth;
		var minWidth = dialog.getAttribute('data-auth-kind') === 'login' ? 292 : 320;
		var clampedWidth = Math.max(minWidth, Math.min(maxDialogWidth(), Math.round(numericWidth)));
		dialog.style.width = clampedWidth + 'px';
		dialog.style.maxWidth = '100%';
	}

	if (!overlay || !frame || !dialog) {
		return;
	}

	var body = document.body;
	var authHrefPattern = /(^|\/)(login|signup)\.php(?:\?|$)/i;

	function buildModalMeta(rawHref) {
		var url;
		var title = 'Авторизация';
		var kind = 'login';

		try {
			url = new URL(rawHref || 'login.php', window.location.href);
		} catch (e) {
			url = new URL('login.php', window.location.href);
		}

		if (/signup\.php$/i.test(url.pathname)) {
			title = 'Регистрация';
			kind = 'signup';
		} else if (/login\.php$/i.test(url.pathname)) {
			title = (url.searchParams.get('op') === 'forgot' ? 'Восстановление доступа' : 'Вход');
			kind = (url.searchParams.get('op') === 'forgot' ? 'forgot' : 'login');
		} else {
			return null;
		}

		url.searchParams.set('modal', '1');

		if (!url.searchParams.get('referer')) {
			var referer = window.location.pathname.replace(/^\//, '') + window.location.search;
			url.searchParams.set('referer', referer);
		}

		return {
			href: url.pathname + '?' + url.searchParams.toString(),
			title: title,
			kind: kind
		};
	}

	function openAuthModal(href) {
		var meta = buildModalMeta(href);

		if (!meta) {
			return;
		}

		frame.src = meta.href;
		setFrameHeight(defaultFrameHeight);
		frame.title = meta.title;
		dialog.setAttribute('aria-label', meta.title);
		dialog.setAttribute('data-auth-kind', meta.kind);
		if (meta.kind === 'signup') {
			setDialogWidth(560);
		} else if (meta.kind === 'forgot') {
			setDialogWidth(460);
		} else {
			setDialogWidth(292);
		}
		overlay.hidden = false;
		body.classList.add('site-auth-modal-open');
	}

	function closeAuthModal() {
		overlay.hidden = true;
		body.classList.remove('site-auth-modal-open');
		frame.src = 'about:blank';
		dialog.removeAttribute('data-auth-kind');
		dialog.style.width = '';
		dialog.style.maxWidth = '';
	}

	window.addEventListener('message', function(event){
		if (event.origin !== window.location.origin || !event.data || event.data.type !== 'lt-auth-modal-size') {
			return;
		}

		if (overlay.hidden) {
			return;
		}

		setFrameHeight(event.data.height);
	});

	window.addEventListener('resize', function(){
		if (!overlay.hidden) {
			setFrameHeight(parseInt(frame.style.height, 10) || defaultFrameHeight);
			setDialogWidth(parseInt(dialog.style.width, 10) || defaultDialogWidth);
		}
	});

	document.addEventListener('click', function(event){
		var closeTrigger = event.target.closest('[data-auth-close="1"]');
		if (closeTrigger) {
			event.preventDefault();
			closeAuthModal();
			return;
		}

		var link = event.target.closest('a[href]');
		if (!link) {
			return;
		}

		if (link.hasAttribute('data-auth-direct') || link.target === '_blank' || link.hasAttribute('download')) {
			return;
		}

		var href = String(link.getAttribute('href') || '');
		if (!authHrefPattern.test(href)) {
			return;
		}

		event.preventDefault();
		openAuthModal(href);
	});

	document.addEventListener('keydown', function(event){
		if (event.key === 'Escape' && !overlay.hidden) {
			closeAuthModal();
		}
	});

	window.ltCloseAuthModal = closeAuthModal;
	window.ltCloseSignupModal = closeAuthModal;
})();
</script>
<?php } ?>

<?php if ($USER) { ?>
<script>
(function(){
	var dropdown = document.querySelector('.site-user-dropdown');
	if (!dropdown) {
		return;
	}

	document.addEventListener('click', function(event){
		if (!dropdown.hasAttribute('open')) {
			return;
		}
		if (event.target.closest('.site-user-dropdown')) {
			return;
		}
		dropdown.removeAttribute('open');
	});

	document.addEventListener('keydown', function(event){
		if (event.key === 'Escape' && dropdown.hasAttribute('open')) {
			dropdown.removeAttribute('open');
		}
	});
})();
</script>
<?php } ?>
<script>
(function(){
	var button = document.getElementById('site-scroll-toggle');
	if (!button) {
		return;
	}

	var SCROLL_THRESHOLD = 260;
	var updateState = function () {
		var scrolled = (window.pageYOffset || document.documentElement.scrollTop || 0);
		var toTop = scrolled > SCROLL_THRESHOLD;
		button.textContent = (toTop ? '↑' : '↓');
		button.setAttribute('aria-label', (toTop ? 'Прокрутить вверх' : 'Прокрутить вниз'));
		button.classList.toggle('site-scroll-toggle-up', toTop);
	};

	button.addEventListener('click', function () {
		var scrolled = (window.pageYOffset || document.documentElement.scrollTop || 0);
		var toTop = scrolled > SCROLL_THRESHOLD;
		window.scrollTo({
			top: (toTop ? 0 : Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)),
			behavior: 'smooth'
		});
	});

	window.addEventListener('scroll', updateState, { passive: true });
	window.addEventListener('resize', updateState);
	updateState();
})();
</script>




<div class="site-shell site-shell-content">
	<?php if ($welcomeBanner !== '') { ?>
	<div class="site-welcome-banner"><?=htmlspecialchars($welcomeBanner, ENT_QUOTES, 'UTF-8');?></div>
	<?php } ?>
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

<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Профиль пользователя
===================================================================
*/

require 'system/init.php';

if (!$PRIV['profile_view']) {
	err($language['default_1'], $language['profile_20'], 1);
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id && $USER) {
	$id = (int) $USER['id'];
}

if (!$id) {
	err($language['default_1'], $language['profile_1'], 1);
}

$db->query("SELECT * FROM users WHERE id=".$id);
if (!$db->num_rows()) {
	err($language['default_1'], $language['profile_1'], 1);
}
$arr = $db->get_row();

$isOwnProfile = ($USER && (int) $USER['id'] === (int) $arr['id']);
$canEditProfile = ($isOwnProfile || !empty($PRIV['setting_user']));
$profileName = htmlspecialchars($arr['name'], ENT_QUOTES, 'UTF-8');
$profileTitle = 'Профиль: '.$profileName;
$profileSince = convent_date($arr['added']);
$profileLastAccess = convent_date($arr['last_access']);
$profileAboutRaw = trim((string) $arr['profile_text']);
$profileAbout = ($profileAboutRaw !== '' ? nl2br(htmlspecialchars($profileAboutRaw, ENT_QUOTES, 'UTF-8')) : '');
$avatarLarge = 'public/images/default_avatar.gif';
if (!empty($arr['avatar']) && is_file('public/avatars/'.$arr['avatar'])) {
	$avatarLarge = 'public/avatars/'.$arr['avatar'];
}

$onlineThreshold = get_date_time(gmtime() - 100);
$isOnline = ($arr['last_access'] > $onlineThreshold);
$profileStatusLabel = ($isOnline ? 'Онлайн' : 'Не в сети');
$profileStatusClass = ($isOnline ? 'profile-status-online' : 'profile-status-offline');

$primaryAction = null;
if ($canEditProfile) {
	$primaryAction = array(
		'label' => 'Редактировать',
		'href' => 'my.setting.php?id='.(int) $arr['id'],
		'class' => 'profile-card-button',
	);
} elseif ($USER && (int) $USER['id'] !== (int) $arr['id']) {
	$primaryAction = array(
		'label' => 'Сообщение',
		'href' => 'my.mail.php?act=conversation&id_user='.(int) $arr['id'],
		'class' => 'profile-card-button',
	);
}

$sidebarLinks = array(
	array(
		'label' => ($isOwnProfile ? 'Мой профиль' : 'Профиль пользователя'),
		'href' => 'profile.php?id='.(int) $arr['id'],
		'active' => true,
	),
	array(
		'label' => ($isOwnProfile ? 'Мои торренты' : 'Торренты пользователя'),
		'href' => 'browse.php?search=&id_user='.(int) $arr['id'],
		'active' => false,
	),
);

if ($USER) {
	$sidebarLinks[] = array(
		'label' => ($isOwnProfile ? 'Мои бонусы' : 'Бонусы'),
		'href' => 'shop.php',
		'active' => false,
	);
}

$peerStats = $db->super_query(
	"SELECT
		COUNT(IF(seeder = 1, 1, NULL)) AS seeders,
		COUNT(IF(seeder = 0, 1, NULL)) AS leechers
	FROM peers
	WHERE userid = ".(int) $arr['id']
);

$profileStats = array(
	'voice' => (float) $arr['voice'],
	'seeders' => (int) ($peerStats['seeders'] ?? 0),
	'leechers' => (int) ($peerStats['leechers'] ?? 0),
	'downloaded' => mksize($arr['downloaded']),
	'uploaded' => mksize($arr['uploaded']),
);

$currentUserWallAvatar = 'public/images/default_avatar.gif';
if ($USER && !empty($USER['avatar']) && is_file('public/avatars/small/'.$USER['avatar'])) {
	$currentUserWallAvatar = 'public/avatars/small/'.$USER['avatar'];
}

head($profileTitle);
comment_status();

require 'templates/'.$config['template'].'/tpl.profile.php';

foot();
?>

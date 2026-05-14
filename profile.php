<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Профиль пользователя
===================================================================
*/

require 'system/init.php';

if (!empty($USER) && !$PRIV['profile_view']) {
	err($language['default_1'], $language['profile_20'], 1);
}

$profileBonusColumn = lt_user_bonus_column();

function profile_normalize_view($view)
{
	$view = trim((string) $view);
	$allowedViews = array('profile', 'torrents', 'bonus');

	return (in_array($view, $allowedViews, true) ? $view : 'profile');
}

function profile_normalize_torrent_tab($tab)
{
	$tab = trim((string) $tab);
	$allowedTabs = array('uploaded', 'downloaded', 'leeching', 'seeding');

	return (in_array($tab, $allowedTabs, true) ? $tab : 'uploaded');
}

function profile_get_torrent_tab_labels()
{
	return array(
		'uploaded' => 'Залил',
		'downloaded' => 'Скачал',
		'leeching' => 'Качаю',
		'seeding' => 'Раздаю',
	);
}

function profile_load_torrent_rows($userId, $tab)
{
	global $db, $USER;

	$userId = (int) $userId;
	$tab = profile_normalize_torrent_tab($tab);
	$rows = array();

	if ($userId <= 0) {
		return $rows;
	}

	$statusSql = lt_torrent_status_filter_sql($USER, 't');

	if ($tab === 'uploaded') {
		$sql = $db->query(
			"SELECT t.id, t.name, t.size, t.added AS activity_date, c.name AS category_name
			 FROM torrents AS t
			 LEFT JOIN categories AS c ON c.id = t.id_category
			 WHERE t.id_user = {$userId} AND {$statusSql}
			 ORDER BY t.added DESC, t.id DESC
			 LIMIT 100"
		);
	} elseif ($tab === 'downloaded') {
		$sql = $db->query(
			"SELECT t.id, t.name, t.size,
			        IF(s.completedat > 0, FROM_UNIXTIME(s.completedat), IF(s.startedat > 0, FROM_UNIXTIME(s.startedat), t.added)) AS activity_date,
			        c.name AS category_name
			 FROM snatched AS s
			 LEFT JOIN torrents AS t ON t.id = s.torrent
			 LEFT JOIN categories AS c ON c.id = t.id_category
			 WHERE s.userid = {$userId} AND {$statusSql}
			 ORDER BY s.completedat DESC, s.startedat DESC, s.id DESC
			 LIMIT 100"
		);
	} else {
		$seederFlag = ($tab === 'seeding' ? 1 : 0);
		$sql = $db->query(
			"SELECT t.id, t.name, t.size, p.last_action AS activity_date, c.name AS category_name
			 FROM peers AS p
			 LEFT JOIN torrents AS t ON t.id = p.torrent
			 LEFT JOIN categories AS c ON c.id = t.id_category
			 WHERE p.userid = {$userId} AND p.seeder = {$seederFlag} AND {$statusSql}
			 ORDER BY p.last_action DESC, p.id DESC
			 LIMIT 100"
		);
	}

	while ($row = $db->get_row($sql)) {
		if (empty($row['id'])) {
			continue;
		}

		$rows[] = $row;
	}

	return $rows;
}

function profile_get_bonus_options()
{
	return array(
		array(
			'id' => '1gb',
			'label' => '1Гб к раздаче',
			'description' => 'Обменять бонусные очки на 1Гб трафика, который будет приплюсован к сумме Вашей раздачи.',
			'cost' => 75,
			'bytes' => 1 * 1073741824,
		),
		array(
			'id' => '2_5gb',
			'label' => '2.5Гб к раздаче',
			'description' => 'Обменять бонусные очки на 2.5Гб трафика, который будет приплюсован к сумме Вашей раздачи.',
			'cost' => 150,
			'bytes' => (int) round(2.5 * 1073741824),
		),
		array(
			'id' => '5gb',
			'label' => '5Гб к раздаче',
			'description' => 'Обменять бонусные очки на 5Гб трафика, который будет приплюсован к сумме Вашей раздачи.',
			'cost' => 250,
			'bytes' => 5 * 1073741824,
		),
		array(
			'id' => '10gb',
			'label' => '10Гб к раздаче',
			'description' => 'Обменять бонусные очки на 10Гб трафика, который будет приплюсован к сумме Вашей раздачи.',
			'cost' => 400,
			'bytes' => 10 * 1073741824,
		),
		array(
			'id' => 'all',
			'label' => 'Обменять всё',
			'description' => 'Обменять все бонусные очки на трафик, который будет приплюсован к сумме Вашей раздачи.',
			'cost' => null,
			'bytes' => null,
		),
	);
}

function profile_calculate_bonus_exchange($selectedOption, $currentBonus, $bonusOptions)
{
	$currentBonus = max(0, (float) $currentBonus);

	if (empty($selectedOption) || !is_array($selectedOption)) {
		return array('cost' => 0, 'bytes' => 0);
	}

	if ($selectedOption['cost'] !== null && $selectedOption['bytes'] !== null) {
		return array(
			'cost' => (float) $selectedOption['cost'],
			'bytes' => (int) $selectedOption['bytes'],
		);
	}

	$bestRate = 0;
	foreach ((array) $bonusOptions as $option) {
		$optionCost = (float) ($option['cost'] ?? 0);
		$optionBytes = (int) ($option['bytes'] ?? 0);
		if ($optionCost <= 0 || $optionBytes <= 0) {
			continue;
		}

		$rate = $optionBytes / $optionCost;
		if ($rate > $bestRate) {
			$bestRate = $rate;
		}
	}

	if ($bestRate <= 0 || $currentBonus <= 0) {
		return array('cost' => 0, 'bytes' => 0);
	}

	return array(
		'cost' => $currentBonus,
		'bytes' => (int) floor($currentBonus * $bestRate),
	);
}

$profileView = profile_normalize_view($_GET['view'] ?? 'profile');
$profilePublicId = trim((string) ($_GET['uid'] ?? ''));
$id = 0;

if ($profilePublicId !== '') {
	$id = profile_user_id_from_public($profilePublicId);
	if (!$id) {
		err($language['default_1'], $language['profile_1'], 1);
	}
}

if (!$id) {
	$id = (int) ($_GET['id'] ?? 0);
}

if (!$id && $USER) {
	$id = (int) $USER['id'];
}

if (!$id) {
	err($language['default_1'], $language['profile_1'], 1);
}

$db->query("SELECT * FROM users WHERE id = ".$id);
if (!$db->num_rows()) {
	err($language['default_1'], $language['profile_1'], 1);
}

$arr = $db->get_row();
$id = (int) $arr['id'];
$isOwnProfile = ($USER && (int) $USER['id'] === $id);
$canManageThisProfile = false;
if (!$isOwnProfile && $USER && (!empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV']))) {
	$targetPriv = get_priv_info((int) ($arr['class'] ?? 0));
	$canManageThisProfile = (empty($targetPriv['EDIT_PRIV']) || !empty($PRIV['EDIT_PRIV']));
}
$canEditProfile = ($isOwnProfile || $canManageThisProfile);
$canViewBonus = ($isOwnProfile || $canManageThisProfile);

if (!$isOwnProfile && $profileView !== 'profile') {
	header('Location: '.profile_href($id));
	die();
}

if ($profileView === 'bonus' && !$canViewBonus) {
	header('Location: '.profile_href($id));
	die();
}

$profileNameRaw = (string) $arr['name'];
$profileName = htmlspecialchars($profileNameRaw, ENT_QUOTES, 'UTF-8');
$profileTitle = 'Профиль: '.$profileNameRaw;
$profileSince = convent_date($arr['added']);
$profileLastAccess = convent_date($arr['last_access']);
$profileAboutRaw = trim((string) ($arr['profile_text'] ?? ''));
$profileAbout = ($profileAboutRaw !== '' ? nl2br(htmlspecialchars($profileAboutRaw, ENT_QUOTES, 'UTF-8')) : '');
$avatarLarge = 'public/images/default_avatar.gif';
if (!empty($arr['avatar']) && is_file('public/avatars/'.$arr['avatar'])) {
	$avatarLarge = 'public/avatars/'.$arr['avatar'];
}

$isOnline = user_is_online($id, 15);
$profileStatusLabel = ($isOnline ? 'Онлайн' : 'Был на сайте '.$profileLastAccess);
$profileStatusClass = ($isOnline ? 'profile-status-online' : 'profile-status-offline');
$profileFlashMessage = null;

if ($profileView === 'bonus' && $canViewBonus && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'exchange_bonus') {
	$bonusOptions = profile_get_bonus_options();
	$selectedOptionId = trim((string) ($_POST['bonus_option'] ?? 'all'));
	$selectedOption = null;

	foreach ($bonusOptions as $option) {
		if ($option['id'] === $selectedOptionId) {
			$selectedOption = $option;
			break;
		}
	}

	if (!$selectedOption) {
		$profileFlashMessage = array(
			'type' => 'error',
			'text' => 'Выберите вариант обмена.',
		);
	} else {
		$currentBonus = (float) ($arr['bonus'] ?? $arr['voice'] ?? 0);
		$exchange = profile_calculate_bonus_exchange($selectedOption, $currentBonus, $bonusOptions);
		$cost = (float) $exchange['cost'];
		$bytes = (int) $exchange['bytes'];

		if ($cost <= 0 || $bytes <= 0) {
			$profileFlashMessage = array(
				'type' => 'error',
				'text' => 'Недостаточно бонусов для обмена.',
			);
		} elseif ($currentBonus < $cost) {
			$profileFlashMessage = array(
				'type' => 'error',
				'text' => 'У вас недостаточно бонусов для этого обмена.',
			);
		} else {
			$db->query("UPDATE users SET uploaded = (uploaded + {$bytes}), {$profileBonusColumn} = GREATEST({$profileBonusColumn} - {$cost}, 0) WHERE id = {$id}");
			$memcached->delete('user_'.$id, 0);
			header('Location: '.profile_href($id, 'bonus', array('status' => 'bonus_exchanged')));
			die();
		}
	}
}

if (($_GET['status'] ?? '') === 'bonus_exchanged') {
	$profileFlashMessage = array(
		'type' => 'success',
		'text' => 'Бонусы успешно обменяны.',
	);
}

$profileActions = array();
$profileCanMessage = (!empty($USER['id']) && (int) $USER['id'] !== $id);
$profileBlacklistEnabled = ($profileCanMessage && user_blacklist_available());
$profileBlacklisted = ($profileBlacklistEnabled ? user_is_blacklisted((int) $USER['id'], $id) : false);
$profileMessageHref = ($profileCanMessage ? 'my.mail.php?act=conversation&id_user='.$id : '');

if ($isOwnProfile) {
	$profileActions[] = array(
		'type' => 'link',
		'label' => 'Редактировать',
		'href' => 'my.setting.php?id='.$id,
		'class' => 'profile-card-button',
	);
} elseif ($profileCanMessage) {
	if ($canManageThisProfile) {
		$profileActions[] = array(
			'type' => 'button',
			'label' => 'Редактировать',
			'class' => 'profile-card-button',
			'attributes' => ' data-profile-toggle-editor="1" aria-expanded="false" onclick="var p=document.getElementById(\'profile-editor-panel\');if(p){var open=p.hasAttribute(\'hidden\')||p.hidden;p.hidden=!open;if(open){p.removeAttribute(\'hidden\');this.setAttribute(\'aria-expanded\',\'true\');if(p.scrollIntoView){p.scrollIntoView({block:\'nearest\',behavior:\'smooth\'});}}else{p.setAttribute(\'hidden\',\'hidden\');this.setAttribute(\'aria-expanded\',\'false\');}}return false;"',
		);
	}

	$profileActions[] = array(
		'type' => 'link',
		'label' => 'Написать сообщение',
		'href' => $profileMessageHref,
		'class' => 'profile-card-button',
	);

	if ($profileBlacklistEnabled) {
		$profileActions[] = array(
			'type' => 'button',
			'label' => ($profileBlacklisted ? 'Убрать из ЧС' : 'Добавить в ЧС'),
			'class' => 'profile-card-button profile-card-button-dark'.($profileBlacklisted ? ' profile-card-button-dark-active' : ''),
			'attributes' => ' data-profile-toggle-blacklist="1" data-user-id="'.$id.'" data-blacklisted="'.($profileBlacklisted ? '1' : '0').'"',
		);
	}
}

$sidebarLinks = array();

if ($isOwnProfile) {
	$sidebarLinks = array(
		array(
			'label' => 'Мой профиль',
			'href' => profile_href($id),
			'active' => ($profileView === 'profile'),
		),
		array(
			'label' => 'Мои торренты',
			'href' => profile_href($id, 'torrents'),
			'active' => ($profileView === 'torrents'),
		),
	);

	if ($canViewBonus) {
		$sidebarLinks[] = array(
			'label' => 'Мои бонусы',
			'href' => profile_href($id, 'bonus'),
			'active' => ($profileView === 'bonus'),
		);
	}
}

$peerStats = $db->super_query(
	"SELECT
		COUNT(IF(seeder = 1, 1, NULL)) AS seeders,
		COUNT(IF(seeder = 0, 1, NULL)) AS leechers
	FROM peers
	WHERE userid = ".$id
);

$profileStats = array(
	'bonus' => (float) ($arr['bonus'] ?? $arr['voice'] ?? 0),
	'seeders' => (int) ($peerStats['seeders'] ?? 0),
	'leechers' => (int) ($peerStats['leechers'] ?? 0),
	'downloaded' => mksize((int) ($arr['downloaded'] ?? 0)),
	'uploaded' => mksize((int) ($arr['uploaded'] ?? 0)),
);

$profileEditorClassOptions = array();
$profileEditorHistory = array();
$profileEditorTransferUnit = 1024 * 1024 * 1024;
$profileEditorUploadedGb = round(((int) ($arr['uploaded'] ?? 0)) / $profileEditorTransferUnit, 3);
$profileEditorDownloadedGb = round(((int) ($arr['downloaded'] ?? 0)) / $profileEditorTransferUnit, 3);
$profileEditorBonusColumn = lt_user_bonus_column();
$profileEditorBonusValue = (float) ($arr[$profileEditorBonusColumn] ?? 0);

if ($canManageThisProfile) {
	foreach (get_classes_list() as $classRow) {
		$classPriv = get_priv_info((int) $classRow['id']);
		if (empty($classPriv['EDIT_PRIV']) || !empty($PRIV['EDIT_PRIV'])) {
			$profileEditorClassOptions[] = $classRow;
		}
	}

	if (lt_table_exists('user_admin_notes')) {
		$historySql = $db->query("SELECT id, note, created_at, admin_id FROM user_admin_notes WHERE user_id = ".(int) $id." ORDER BY id DESC LIMIT 10");
		while ($historyRow = $db->get_row($historySql)) {
			$profileEditorHistory[] = $historyRow;
		}
		$db->free($historySql);
	}
}

$currentUserWallAvatar = 'public/images/default_avatar.gif';
if ($USER && !empty($USER['avatar']) && is_file('public/avatars/small/'.$USER['avatar'])) {
	$currentUserWallAvatar = 'public/avatars/small/'.$USER['avatar'];
}

$wallCommentsHtml = user_wall_render_list($id);
$torrentTabLabels = profile_get_torrent_tab_labels();
$torrentTab = profile_normalize_torrent_tab($_GET['torrent_tab'] ?? 'uploaded');
$profileTorrentRows = ($profileView === 'torrents' ? profile_load_torrent_rows($id, $torrentTab) : array());
$bonusOptions = profile_get_bonus_options();
$selectedBonusOption = trim((string) ($_POST['bonus_option'] ?? 'all'));

head($profileTitle);

require 'templates/'.$config['template'].'/tpl.profile.php';

foot();
?>

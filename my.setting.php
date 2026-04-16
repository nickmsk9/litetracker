<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Мои настройки
===================================================================
*/

require 'system/init.php';

is_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id || $id == $USER['id']) {
	$id = (int) $USER['id'];
	$db->query("SELECT * FROM users WHERE id=".$id);
	$arr = $db->get_row();
} else {
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
	$priv = get_priv_info($arr['class']);

	if (!$PRIV['setting_user'] || $priv['EDIT_PRIV']) {
		err($language['default_1'], $language['setting_1'], 1);
	}
}

if (!$arr) {
	err($language['default_1'], $language['profile_1'], 1);
}

$act = trim((string) ($_GET['act'] ?? ''));
if (in_array($act, array('foto', 'email', 'passkey'), true)) {
	header('Location: my.setting.php?id='.$id);
	die();
}

$settingsView = ($act === 'password' ? 'password' : 'general');
$avatarPath = 'public/images/default_avatar.gif';
if (!empty($arr['avatar']) && is_file('public/avatars/'.$arr['avatar'])) {
	$avatarPath = 'public/avatars/'.$arr['avatar'];
}

$birthdayDay = '';
$birthdayMonth = '';
$birthdayYear = '';
if (!empty($arr['birthday_date']) && $arr['birthday_date'] !== '0000-00-00') {
	list($birthdayYear, $birthdayMonth, $birthdayDay) = explode('-', $arr['birthday_date']);
}

$birthdayMonths = array(
	'01' => 'январь',
	'02' => 'февраль',
	'03' => 'март',
	'04' => 'апрель',
	'05' => 'май',
	'06' => 'июнь',
	'07' => 'июль',
	'08' => 'август',
	'09' => 'сентябрь',
	'10' => 'октябрь',
	'11' => 'ноябрь',
	'12' => 'декабрь',
);

$settingsMenu = array(
	array(
		'label' => 'Общие',
		'href' => 'my.setting.php?id='.$id,
		'active' => ($settingsView === 'general'),
	),
	array(
		'label' => 'Сменить пароль',
		'href' => 'my.setting.php?id='.$id.'&act=password',
		'active' => ($settingsView === 'password'),
	),
);

$classOptions = array();
if ($PRIV['EDIT_PRIV']) {
	$classOptions = get_classes_list();
}

$showModerationPanel = ($PRIV['setting_user'] && (int) $arr['id'] !== (int) $USER['id']);
$isIpBanned = false;
if ($showModerationPanel) {
	$db->query("SELECT * FROM bans WHERE '".ip2long_db($arr['ip'])."' >= first AND '".ip2long_db($arr['ip'])."' <= last");
	$isIpBanned = (bool) $db->num_rows();
}

head($language['setting_17']);

$status = (string) ($_GET['status'] ?? '');
if($status == '1') {
	msg($language['default_9'] , $language['setting_18']);
} elseif($status == '2') {
	msg($language['default_9'] , $language['setting_19']);
} elseif($status == '3') {
	msg($language['default_1'] , $language['setting_20'] , 'error');
} elseif($status == '4') {
	msg($language['default_9'] , $language['setting_21']);
} elseif($status == '5') {
	msg($language['default_9'] , $language['setting_22']);
} elseif($status == '6') {
	msg($language['default_1'] , $language['setting_23'] , 'error');
} elseif($status == '7') {
	msg($language['default_9'] , $language['setting_24']);
} elseif($status == '8') {
	msg($language['default_9'] , $language['setting_25']);
} elseif($status == '9') {
	msg($language['default_9'] , $language['setting_26']);
} elseif($status == '10') {
	msg($language['default_9'] ,$language['setting_27']);
} elseif($status == '11') {
	msg($language['default_9'] , $language['setting_28']);
} elseif($status == '12') {
	msg($language['default_9'] , $language['setting_29']);
} elseif($status == '0') {
	msg($language['default_1'] , $language['setting_30'] , 'error');
}

require 'templates/'.$config['template'].'/tpl.setting.php';

foot();
?>

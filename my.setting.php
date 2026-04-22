<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
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


$settingsView = 'general';
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

head($language['setting_17']);

$status = (string) ($_GET['status'] ?? '');
if($status == '2') {
	msg($language['default_9'] , $language['setting_19']);
} elseif($status == '6') {
	msg($language['default_1'] , $language['setting_23'] , 'error');
} elseif($status == '7') {
	msg($language['default_9'] , $language['setting_24']);
} elseif($status == '0') {
	msg($language['default_1'] , $language['setting_30'] , 'error');
}

require 'templates/'.$config['template'].'/tpl.setting.php';

foot();
?>

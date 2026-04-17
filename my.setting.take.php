<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Обработка настроек
===================================================================
*/

require 'system/init.php';

is_login();

function prepare_user_avatar_upload($fieldName, $userId, $userRow)
{
	global $config, $language;

	if (empty($_FILES[$fieldName]['name'])) {
		return false;
	}

	$allowedTypes = array(
		'image/gif' => 'gif',
		'image/pjpeg' => 'jpg',
		'image/jpeg' => 'jpg',
		'image/jpg' => 'jpg',
		'image/png' => 'png',
		'image/bmp' => 'bmp',
	);

	if (!array_key_exists($_FILES[$fieldName]['type'], $allowedTypes)) {
		err($language['default_1'], $language['setting_60'], 1);
	}

	if (!preg_match('/^(.+)\.(jpg|jpeg|png|gif)$/si', $_FILES[$fieldName]['name'])) {
		err($language['default_1'], $language['setting_61'], 1);
	}

	require_once 'system/classes/class.upload.php';

	$dirDest = 'public/avatars/';
	$dirDestSmall = 'public/avatars/small/';

	if (!empty($userRow['avatar'])) {
		@unlink($dirDest.$userRow['avatar']);
		@unlink($dirDestSmall.$userRow['avatar']);
	}

	$photo = new Upload($_FILES[$fieldName]);
	if (!$photo->uploaded) {
		err('Ошибка', $photo->error, 1);
	}

	$photo->file_max_size = $config['max_size_image'];
	$photo->file_new_name_body = (string) $userId;
	$photo->image_resize = true;
	$photo->image_convert = 'jpg';
	$photo->image_x = 600;
	$photo->image_y = 600;
	$photo->image_ratio = true;
	$photo->image_text = 'LITETRACKER ENGINE';
	$photo->image_text_position = 'RB';
	$photo->image_text_padding = 5;
	$photo->Process($dirDest);

	if (!$photo->processed) {
		err('Ошибка', $photo->error, 1);
	}

	$fileName = $photo->file_dst_name;
	$photo->Clean();

	$photoSmall = new Upload($_FILES[$fieldName]);
	if (!$photoSmall->uploaded) {
		err('Ошибка', $photoSmall->error, 1);
	}

	$photoSmall->file_max_size = $config['max_size_image'];
	$photoSmall->file_new_name_body = (string) $userId;
	$photoSmall->image_resize = true;
	$photoSmall->image_convert = 'jpg';
	$photoSmall->image_x = 100;
	$photoSmall->image_y = 100;
	$photoSmall->image_ratio = true;
	$photoSmall->Process($dirDestSmall);

	if (!$photoSmall->processed) {
		err('Ошибка', $photoSmall->error, 1);
	}

	$photoSmall->Clean();

	return $fileName;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id || $id == $USER['id']) {
	$id = (int) $USER['id'];
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
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

if($act == 'ban_ip') {
	$ip = ip2long_db($arr['ip']);
	$db->query("SELECT * FROM bans WHERE '".$ip."' >= first AND '".$ip."' <= last");
	if(!$db->num_rows()) {
		$db->query("INSERT INTO bans (first , last , id_user , date) VALUES ('".$ip."' , '".$ip."' , ".$USER['id']." , NOW())");
		$memcache->delete('ip_bans_'.$ip, 0);
		header('Location:my.setting.php?id='.$id.'&status=11');
		die();
	}

	$db->query("DELETE FROM bans WHERE '".$ip."' >= first AND '".$ip."' <= last");
	$memcache->delete('ip_bans_'.$ip, 0);
	header('Location:my.setting.php?id='.$id.'&status=12');
	die();
}

if($act == 'ban_account') {
	if($arr['banned'] == 0) {
		$db->query("UPDATE users SET banned='1' WHERE id=".$id);
		$memcache->delete('user_'.$id, 0);
		header('Location:my.setting.php?id='.$id.'&status=9');
		die();
	}

	$db->query("UPDATE users SET banned='0' WHERE id=".$id);
	$memcache->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=10');
	die();
}

if($act == 'foto_delete') {
	@unlink('public/avatars/'.$arr['avatar']);
	@unlink('public/avatars/small/'.$arr['avatar']);
	$db->query("UPDATE users SET avatar='' WHERE id='".$id."'");
	$memcache->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=8');
	die();
}

if($act == 'foto') {
	$fileName = prepare_user_avatar_upload('foto', $id, $arr);
	if ($fileName === false) {
		header('Location:my.setting.php?id='.$id.'&status=6');
		die();
	}

	$db->query("UPDATE users SET avatar='".$db->safesql($fileName)."' WHERE id='".$id."'");
	$memcache->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=7');
	die();
}

if($act == 'passkey') {
	$db->query("UPDATE users SET passkey='' WHERE id='".$id."'");
	$memcache->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=5');
	die();
}

if($act == 'password') {
	if(!$PRIV['setting_user']) {
		$oldPassword = trim((string) ($_POST['old_password'] ?? ''));
		if($arr['password'] != md5($arr['password_code'].$oldPassword.$arr['password_code'])) {
			err($language['default_1'], $language['setting_66'], 1);
		}
	}

	$newPassword = trim((string) ($_POST['new_password'] ?? ''));
	$newPasswordRepeat = trim((string) ($_POST['new_password_1'] ?? ''));

	if (strlen($newPassword) < 6) {
		err($language['default_1'], $language['setting_67'], 1);
	}

	if (strlen($newPassword) > 40) {
		err($language['default_1'], $language['setting_68'], 1);
	}

	if($newPassword != $newPasswordRepeat) {
		err($language['default_1'], $language['setting_69'], 1);
	}

	$passwordCode = mksecret(32);
	$passwordHash = md5($passwordCode.$newPassword.$passwordCode);

	$db->query("UPDATE users SET password='".$passwordHash."' , password_code='".$passwordCode."' WHERE id='".$id."'");
	$memcache->delete('user_'.$id, 0);

	if ((int) $USER['id'] === (int) $id) {
		logout_cookie();
		login_cookie($id, $passwordHash);
	}

	header('Location:my.setting.php?id='.$id.'&status=2');
	die();
}

if($act == 'email') {
	$email = trim((string) ($_POST['email'] ?? ''));
	if($email == $arr['email']) {
		header('Location:my.setting.php?id='.$id.'&status=3');
		die();
	}

	if ($email !== '' && !validemail($email)) {
		err($language['default_1'], $language['setting_64'], 1);
	}

	$emailCheck = ($email !== '' ? $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."'") : false);
	if($email !== '' && $db->num_rows() >= 1) {
		err($language['default_1'], $language['setting_65'], 1);
	}

	$db->query("UPDATE users SET email='".$db->safesql($email)."' WHERE id='".$id."'");
	$memcache->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=4');
	die();
}

$update = array();

$name = trim((string) ($_POST['name'] ?? ''));
if($arr['name'] != $name) {
	if(empty($name)) {
		err($language['default_1'], $language['setting_70'], 1);
	}
	if (!validusername($name)) {
		err($language['default_1'], $language['setting_71'], 1);
	}
	if (strlen($name) > 12) {
		err($language['default_1'], $language['setting_72'], 1);
	}

	$emailCheck = $db->query("SELECT * FROM users WHERE name='".$db->safesql($name)."' AND id <> ".$id);
	if($db->num_rows() >= 1) {
		err($language['default_1'], $language['setting_73'], 1);
	}

	$update[] = "name='".$db->safesql($name)."'";
}

$email = trim((string) ($_POST['email'] ?? ''));
if($email != $arr['email']) {
	if ($email !== '' && !validemail($email)) {
		err($language['default_1'], $language['setting_64'], 1);
	}

	$emailCheck = ($email !== '' ? $db->query("SELECT * FROM users WHERE email='".$db->safesql($email)."' AND id <> ".$id) : false);
	if($email !== '' && $db->num_rows() >= 1) {
		err($language['default_1'], $language['setting_65'], 1);
	}

	$update[] = "email='".$db->safesql($email)."'";
}

$sex = ((int) ($_POST['sex'] ?? 1) == 1 ? '1' : '0');
if($arr['sex'] != $sex) {
	$update[] = "sex='".$sex."'";
}

$website = trim((string) ($_POST['website'] ?? ''));
if($arr['website'] != $website) {
	if($website === '') {
		$update[] = "website=''";
	} else {
		$pattern = "#^(http://|https://)?[-a-z0-9_\.]+([-a-z0-9_]+\.(html|php|pl|cgi))?([-a-z0-9_:@&\?=+\.!/~*'%$]+)?$#i";
		if(!preg_match($pattern, $website)) {
			err($language['default_1'], $language['setting_37'], 1);
		}
		$update[] = "website='".$db->safesql($website)."'";
	}
}

$birthdayDay = trim((string) ($_POST['birthday_day'] ?? ''));
$birthdayMonth = trim((string) ($_POST['birthday_month'] ?? ''));
$birthdayYear = trim((string) ($_POST['birthday_year'] ?? ''));
$birthdayDate = null;

if ($birthdayDay === '' && $birthdayMonth === '' && $birthdayYear === '') {
	$birthdayDate = null;
} elseif ($birthdayDay !== '' && $birthdayMonth !== '' && $birthdayYear !== '' && checkdate((int) $birthdayMonth, (int) $birthdayDay, (int) $birthdayYear)) {
	$birthdayDate = sprintf('%04d-%02d-%02d', $birthdayYear, $birthdayMonth, $birthdayDay);
} else {
	err('Ошибка', 'Дата рождения указана неверно.', 1);
}

$currentBirthday = (!empty($arr['birthday_date']) && $arr['birthday_date'] !== '0000-00-00' ? $arr['birthday_date'] : null);
if ($currentBirthday !== $birthdayDate) {
	$update[] = ($birthdayDate === null ? "birthday_date=NULL" : "birthday_date='".$db->safesql($birthdayDate)."'");
}

$profileText = trim((string) ($_POST['profile_text'] ?? ''));
if ((string) $arr['profile_text'] !== $profileText) {
	$update[] = "profile_text='".$db->safesql($profileText)."'";
}

$notifyComments = (!empty($_POST['notify_comments']) ? 1 : 0);
if ((int) $arr['notify_comments'] !== $notifyComments) {
	$update[] = "notify_comments='".$notifyComments."'";
}

$downloadLocalRetracker = (!isset($_POST['download_local_retracker']) ? 0 : 1);
if ((int) $arr['download_local_retracker'] !== $downloadLocalRetracker) {
	$update[] = "download_local_retracker='".$downloadLocalRetracker."'";
}

$themeDark = (!empty($_POST['theme_dark']) ? 1 : 0);
if ((int) $arr['theme_dark'] !== $themeDark) {
	$update[] = "theme_dark='".$themeDark."'";
}

$avatarFileName = prepare_user_avatar_upload('avatar_upload', $id, $arr);
if ($avatarFileName !== false) {
	$update[] = "avatar='".$db->safesql($avatarFileName)."'";
}

if($PRIV['setting_user'] || $PRIV['EDIT_PRIV']) {
	$downloaded = (int) ($_POST['downloaded'] ?? 0);
	$downCommand = ((string) ($_POST['down_command'] ?? '+') == '+' ? '+' : '-');
	$downFormat = (((string) ($_POST['down_format'] ?? 'mb') == 'mb') ? (1024*1024) : (1024*1024*1024));
	if($downloaded > 0) {
		$bytes = $downloaded * $downFormat;
		if($downCommand == '+') {
			$update[] = "downloaded=('".$bytes."' + downloaded)";
		} else {
			if($arr['downloaded'] < $bytes) {
				err($language['default_1'], $language['setting_74'], 1);
			}
			$update[] = "downloaded=(downloaded - '".$bytes."')";
		}
	}

	$uploaded = (int) ($_POST['uploaded'] ?? 0);
	$upCommand = ((string) ($_POST['up_command'] ?? '+') == '+' ? '+' : '-');
	$upFormat = (((string) ($_POST['up_format'] ?? 'mb') == 'mb') ? (1024*1024) : (1024*1024*1024));
	if($uploaded > 0) {
		$bytes = $uploaded * $upFormat;
		if($upCommand == '+') {
			$update[] = "uploaded=('".$bytes."' + uploaded)";
		} else {
			if($arr['uploaded'] < $bytes) {
				err($language['default_1'], $language['setting_75'], 1);
			}
			$update[] = "uploaded=(uploaded - '".$bytes."')";
		}
	}

	if($PRIV['EDIT_PRIV']) {
		$class = (int) ($_POST['class'] ?? $arr['class']);
		$db->query("SELECT * FROM priv WHERE id > 0 AND id=".$class);
		if($arr['class'] != $class && $db->num_rows()) {
			$update[] = "class='".$class."'";
			$infoClass = get_priv_info($class);
			send_msg($language['setting_76'], sprintf($language['setting_77'], $infoClass['NAME']), $id, 0);
		}
	}
}

if(count($update)) {
	$db->query("UPDATE users SET ".implode(',', $update)." WHERE id='".$id."'");
}

$memcache->delete('user_'.$id, 0);
header('Location:my.setting.php?id='.$id.'&status=1');
die();
?>

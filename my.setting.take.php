<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Обработка настроек
===================================================================
*/

require 'system/init.php';

is_login();

function prepare_user_avatar_upload($fieldName, $userId, $userRow)
{
	global $config, $language;

	if (
		empty($_FILES[$fieldName]) ||
		!is_array($_FILES[$fieldName]) ||
		!isset($_FILES[$fieldName]['error']) ||
		(int) $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE ||
		empty($_FILES[$fieldName]['tmp_name'])
	) {
		return false;
	}

	if ((int) $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
		err($language['default_1'], 'Ошибка загрузки файла.', 1);
	}

	if (!is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
		err($language['default_1'], 'Файл загружен некорректно.', 1);
	}

	$imageInfo = @getimagesize($_FILES[$fieldName]['tmp_name']);
	if (!$imageInfo || empty($imageInfo[2])) {
		err($language['default_1'], 'Можно загружать только изображения JPG, PNG или GIF.', 1);
	}

	$type = (int) $imageInfo[2];
	$allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
	if (!in_array($type, $allowedTypes, true)) {
		err($language['default_1'], 'Можно загружать только изображения JPG, PNG или GIF.', 1);
	}

	if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg') || !function_exists('imagecreatefromstring')) {
		err($language['default_1'], 'На сервере не включена библиотека GD для обработки изображений.', 1);
	}

	$maxFileSize = isset($config['max_size_image']) ? (int) $config['max_size_image'] : 0;
	if ($maxFileSize > 0 && isset($_FILES[$fieldName]['size']) && (int) $_FILES[$fieldName]['size'] > $maxFileSize) {
		err($language['default_1'], 'Файл слишком большой.', 1);
	}

	$dirDest = 'public/avatars/';
	$dirDestSmall = 'public/avatars/small/';

	if (!is_dir($dirDest) && !@mkdir($dirDest, 0777, true)) {
		err('Ошибка', 'Не удалось создать папку для аватаров.', 1);
	}

	if (!is_dir($dirDestSmall) && !@mkdir($dirDestSmall, 0777, true)) {
		err('Ошибка', 'Не удалось создать папку для миниатюр аватаров.', 1);
	}

	$imageBinary = @file_get_contents($_FILES[$fieldName]['tmp_name']);
	if ($imageBinary === false || $imageBinary === '') {
		err('Ошибка', 'Не удалось прочитать загруженное изображение.', 1);
	}

	$sourceImage = @imagecreatefromstring($imageBinary);
	if (!$sourceImage) {
		err('Ошибка', 'Не удалось обработать изображение.', 1);
	}

	$sourceWidth = imagesx($sourceImage);
	$sourceHeight = imagesy($sourceImage);
	if ($sourceWidth <= 0 || $sourceHeight <= 0) {
		imagedestroy($sourceImage);
		err('Ошибка', 'Некорректный размер изображения.', 1);
	}

	$fileName = (string) $userId.'_'.time().'_'.substr(md5(mksecret(16)), 0, 8).'.jpg';
	$mainPath = $dirDest . $fileName;
	$smallPath = $dirDestSmall . $fileName;

	if (!empty($userRow['avatar'])) {
		@unlink($dirDest.$userRow['avatar']);
		@unlink($dirDestSmall.$userRow['avatar']);
	}

	$saveResizedJpeg = function ($srcImage, $srcWidth, $srcHeight, $targetPath, $maxWidth, $maxHeight) {
		$ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight, 1);
		$newWidth = max(1, (int) round($srcWidth * $ratio));
		$newHeight = max(1, (int) round($srcHeight * $ratio));

		$targetImage = imagecreatetruecolor($newWidth, $newHeight);
		if (!$targetImage) {
			return false;
		}

		$imageWhite = imagecolorallocate($targetImage, 255, 255, 255);
		imagefill($targetImage, 0, 0, $imageWhite);

		if (!imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight)) {
			imagedestroy($targetImage);
			return false;
		}

		$result = imagejpeg($targetImage, $targetPath, 90);
		imagedestroy($targetImage);

		return $result;
	};

	$mainSaved = $saveResizedJpeg($sourceImage, $sourceWidth, $sourceHeight, $mainPath, 600, 600);
	$smallSaved = $saveResizedJpeg($sourceImage, $sourceWidth, $sourceHeight, $smallPath, 100, 100);
	imagedestroy($sourceImage);

	if (!$mainSaved || !$smallSaved) {
		@unlink($mainPath);
		@unlink($smallPath);
		err('Ошибка', 'Не удалось сохранить аватар.', 1);
	}

	@chmod($mainPath, 0666);
	@chmod($smallPath, 0666);

	return $fileName;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id || $id == $USER['id']) {
	$id = (int) $USER['id'];
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
} else {
	$arr = $db->super_query("SELECT * FROM users WHERE id='".$id."'");
	$priv = get_priv_info($arr['class']);
	$canManageUsers = (!empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV']));
	if (!$canManageUsers || (!empty($priv['EDIT_PRIV']) && empty($PRIV['EDIT_PRIV']))) {
		err($language['default_1'], $language['setting_1'], 1);
	}
}

if (!$arr) {
	err($language['default_1'], $language['profile_1'], 1);
}

$act = trim((string) ($_GET['act'] ?? ''));
$settingsProfileScope = 'settings_profile_'.$id;
$settingsPasswordScope = 'settings_password_'.$id;
$settingsAvatarScope = 'settings_avatar_'.$id;
$settingsModerationScope = 'settings_moderation_'.$id;

if($act == 'ban_ip') {
	if (!$PRIV['setting_user'] || !lt_csrf_validate($settingsModerationScope)) {
		err($language['default_1'], 'Недостаточно прав или защитный токен устарел.', 1);
	}

	$ip = ip2long_db($arr['ip']);
	$db->query("SELECT * FROM bans WHERE '".$ip."' >= first AND '".$ip."' <= last");
	if(!$db->num_rows()) {
		$db->query("INSERT INTO bans (first , last , id_user , date) VALUES ('".$ip."' , '".$ip."' , ".$USER['id']." , NOW())");
		$memcached->delete('ip_bans_'.$ip, 0);
		header('Location:my.setting.php?id='.$id.'&status=11');
		die();
	}

	$db->query("DELETE FROM bans WHERE '".$ip."' >= first AND '".$ip."' <= last");
	$memcached->delete('ip_bans_'.$ip, 0);
	header('Location:my.setting.php?id='.$id.'&status=12');
	die();
}

if($act == 'ban_account') {
	if (!$PRIV['setting_user'] || !lt_csrf_validate($settingsModerationScope)) {
		err($language['default_1'], 'Недостаточно прав или защитный токен устарел.', 1);
	}

	if($arr['banned'] == 0) {
		$db->query("UPDATE users SET banned='1' WHERE id=".$id);
		$memcached->delete('user_'.$id, 0);
		header('Location:my.setting.php?id='.$id.'&status=9');
		die();
	}

	$db->query("UPDATE users SET banned='0' WHERE id=".$id);
	$memcached->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id.'&status=10');
	die();
}

if($act == 'foto_delete') {
	if (!lt_csrf_validate($settingsAvatarScope)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	@unlink('public/avatars/'.$arr['avatar']);
	@unlink('public/avatars/small/'.$arr['avatar']);
	$db->query("UPDATE users SET avatar='' WHERE id='".$id."'");
	$memcached->delete('user_'.$id, 0);
	header('Location:my.setting.php?id='.$id);
	die();
}

if($act == 'password') {
	if (!lt_csrf_validate($settingsPasswordScope)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	if((int) $USER['id'] === (int) $id) {
		$oldPassword = trim((string) ($_POST['old_password'] ?? ''));
		$passwordNeedsRehash = false;
		if(!lt_password_verify_user($oldPassword, $arr, $passwordNeedsRehash)) {
			err($language['default_1'], $language['setting_66'], 1);
		}
	}

	$newPassword = trim((string) ($_POST['new_password'] ?? ''));

	// Removed password repeat validation block as per instructions

	if (strlen($newPassword) < 6) {
		err($language['default_1'], $language['setting_67'], 1);
	}

	if (strlen($newPassword) > 40) {
		err($language['default_1'], $language['setting_68'], 1);
	}

	$passwordHash = lt_password_hash_value($newPassword);

	$db->query("UPDATE users SET password='".$db->safesql($passwordHash)."' , password_code='' WHERE id='".$id."'");
	$memcached->delete('user_'.$id, 0);

	if ((int) $USER['id'] === (int) $id) {
		logout_cookie();
		login_cookie($id, $passwordHash);
	}

	header('Location:my.setting.php?id='.$id.'&tab=password&status=2');
	die();
}

if (!lt_csrf_validate($settingsProfileScope)) {
	err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
}

$update = array();

$sex = ((int) ($_POST['sex'] ?? 1) == 1 ? '1' : '0');
if($arr['sex'] != $sex) {
	$update[] = "sex='".$sex."'";
}

// Removed website validation/update block as per instructions

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

if(!empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV'])) {
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

	if((!empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV'])) && isset($_POST['class']) && (int) $id !== (int) $USER['id']) {
		$class = (int) ($_POST['class'] ?? $arr['class']);
		$targetClass = $db->super_query("SELECT * FROM priv WHERE id > 0 AND id=".$class." LIMIT 1");
		if(!empty($targetClass['id']) && !empty($targetClass['EDIT_PRIV']) && empty($PRIV['EDIT_PRIV'])) {
			err($language['default_1'], 'У вас нет прав назначать этот класс.', 1);
		}
		if($arr['class'] != $class && !empty($targetClass['id'])) {
			$update[] = "class='".$class."'";
			send_msg($language['setting_76'], sprintf($language['setting_77'], $targetClass['NAME']), $id, 0);
		}
	}
}

if(count($update)) {
	$db->query("UPDATE users SET ".implode(',', $update)." WHERE id='".$id."'");
}

$memcached->delete('user_'.$id, 0);
header('Location:my.setting.php?id='.$id);
die();
?>

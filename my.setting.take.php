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

	$imageInfo = getimagesize($_FILES[$fieldName]['tmp_name']);
	if (!$imageInfo || empty($imageInfo[2])) {
		err($language['default_1'], 'Можно загружать только изображения JPG, PNG или GIF.', 1);
	}

	$type = (int) $imageInfo[2];
	$allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
	if (!in_array($type, $allowedTypes, true)) {
		err($language['default_1'], 'Можно загружать только изображения JPG, PNG или GIF.', 1);
	}

	$isAnimatedPlusGif = ($type === IMAGETYPE_GIF && lt_user_has_plus($userRow));

	if (!$isAnimatedPlusGif && (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg') || !function_exists('imagecreatefromstring'))) {
		err($language['default_1'], 'На сервере не включена библиотека GD для обработки изображений.', 1);
	}

	$maxFileSize = isset($config['max_size_image']) ? (int) $config['max_size_image'] : 0;
	if ($maxFileSize > 0 && isset($_FILES[$fieldName]['size']) && (int) $_FILES[$fieldName]['size'] > $maxFileSize) {
		err($language['default_1'], 'Файл слишком большой.', 1);
	}

	$dirDest = 'public/avatars/';
	$dirDestSmall = 'public/avatars/small/';

	if (!is_dir($dirDest) && !mkdir($dirDest, 0777, true)) {
		err('Ошибка', 'Не удалось создать папку для аватаров.', 1);
	}

	if (!is_dir($dirDestSmall) && !mkdir($dirDestSmall, 0777, true)) {
		err('Ошибка', 'Не удалось создать папку для миниатюр аватаров.', 1);
	}

	$imageBinary = file_get_contents($_FILES[$fieldName]['tmp_name']);
	if ($imageBinary === false || $imageBinary === '') {
		err('Ошибка', 'Не удалось прочитать загруженное изображение.', 1);
	}

	if ($isAnimatedPlusGif) {
		$fileName = (string) $userId.'_'.time().'_'.substr(md5(mksecret(16)), 0, 8).'.gif';
		$mainPath = $dirDest . $fileName;
		$smallPath = $dirDestSmall . $fileName;

		if (!empty($userRow['avatar'])) {
			$_p = $dirDest.$userRow['avatar']; if (is_file($_p)) { unlink($_p); }
			$_p = $dirDestSmall.$userRow['avatar']; if (is_file($_p)) { unlink($_p); }
		}

		if (!copy($_FILES[$fieldName]['tmp_name'], $mainPath) || !copy($_FILES[$fieldName]['tmp_name'], $smallPath)) {
			if (is_file($mainPath)) { unlink($mainPath); }
			if (is_file($smallPath)) { unlink($smallPath); }
			err('Ошибка', 'Не удалось сохранить GIF-аватар.', 1);
		}

		chmod($mainPath, 0666);
		chmod($smallPath, 0666);

		return $fileName;
	}

	$sourceImage = imagecreatefromstring($imageBinary);
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
		$_p = $dirDest.$userRow['avatar']; if (is_file($_p)) { unlink($_p); }
		$_p = $dirDestSmall.$userRow['avatar']; if (is_file($_p)) { unlink($_p); }
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
		if (is_file($mainPath)) { unlink($mainPath); }
		if (is_file($smallPath)) { unlink($smallPath); }
		err('Ошибка', 'Не удалось сохранить аватар.', 1);
	}

	chmod($mainPath, 0666);
	chmod($smallPath, 0666);

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

	$_p = 'public/avatars/'.$arr['avatar']; if (is_file($_p)) { unlink($_p); }
	$_p = 'public/avatars/small/'.$arr['avatar']; if (is_file($_p)) { unlink($_p); }
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

	$db->pquery("UPDATE users SET password=?, password_code='' WHERE id=?", 'si', [$passwordHash, (int) $id]);
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
$updateParams = array();
$updateTypes = '';

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
	if ($birthdayDate === null) {
		$update[] = "birthday_date=NULL";
	} else {
		$update[] = "birthday_date=?";
		$updateParams[] = $birthdayDate;
		$updateTypes .= 's';
	}
}

$profileText = trim((string) ($_POST['profile_text'] ?? ''));
if ((string) $arr['profile_text'] !== $profileText) {
	$update[] = "profile_text=?";
	$updateParams[] = $profileText;
	$updateTypes .= 's';
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

$targetHasPlus = lt_user_has_plus($arr);
if ($targetHasPlus) {
	$badge = trim((string) ($_POST['plus_badge'] ?? ($arr['plus_badge'] ?? 'star')));
	$badgeOptions = lt_plus_badge_options();
	if (empty($badgeOptions[$badge])) {
		$badge = 'star';
	}
	if ((string) ($arr['plus_badge'] ?? 'star') !== $badge) {
		$update[] = "plus_badge=?";
		$updateParams[] = $badge;
		$updateTypes .= 's';
	}

	$profileSlug = lt_profile_slug_normalize($_POST['profile_slug'] ?? '');
	if ($profileSlug !== '' && (strlen($profileSlug) < 3 || strlen($profileSlug) > 64)) {
		err($language['default_1'], 'Красивый никнейм должен быть от 3 до 64 символов.', 1);
	}
	if ($profileSlug !== '' && lt_profile_slug_is_reserved($profileSlug)) {
		err($language['default_1'], 'Этот красивый никнейм зарезервирован системой.', 1);
	}
	if ($profileSlug !== '') {
		$slugOwnerId = lt_profile_slug_user_id($profileSlug);
		if ($slugOwnerId > 0 && $slugOwnerId !== (int) $id) {
			err($language['default_1'], 'Этот красивый никнейм уже занят.', 1);
		}
	}
	if ((string) ($arr['profile_slug'] ?? '') !== $profileSlug) {
		$update[] = "profile_slug=?";
		$updateParams[] = $profileSlug;
		$updateTypes .= 's';
	}
} elseif (!empty($arr['profile_slug'])) {
	$update[] = "profile_slug=''";
}

$avatarFileName = prepare_user_avatar_upload('avatar_upload', $id, $arr);
if ($avatarFileName !== false) {
	$update[] = "avatar=?";
	$updateParams[] = $avatarFileName;
	$updateTypes .= 's';
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

	if((!empty($PRIV['setting_user']) || !empty($PRIV['EDIT_PRIV'])) && (int) $id !== (int) $USER['id'] && isset($_POST['plus_grant_mode'])) {
		$plusGrantMode = trim((string) ($_POST['plus_grant_mode'] ?? 'keep'));
		if ($plusGrantMode === 'disable') {
			$update[] = "plus_permanent='0'";
			$update[] = "plus_until=NULL";
			$update[] = "plus_source='manual_disabled'";
		} elseif ($plusGrantMode === 'forever') {
			$update[] = "plus_permanent='1'";
			$update[] = "plus_until=NULL";
			$update[] = "plus_source='manual'";
		} elseif ($plusGrantMode === 'until') {
			$manualUntil = trim((string) ($_POST['plus_manual_until'] ?? ''));
			if ($manualUntil === '' || !preg_match('~^\d{4}-\d{2}-\d{2}$~', $manualUntil) || !strtotime($manualUntil.' 23:59:59')) {
				err($language['default_1'], 'Укажите корректную дату окончания Plus.', 1);
			}
			$update[] = "plus_permanent='0'";
			$update[] = "plus_until=?";
			$updateParams[] = $manualUntil.' 23:59:59';
			$updateTypes .= 's';
			$update[] = "plus_source='manual'";
		}
	}
}

if(count($update)) {
	$updateParams[] = (int) $id;
	$updateTypes .= 'i';
	$db->pquery("UPDATE users SET ".implode(',', $update)." WHERE id=?", $updateTypes, $updateParams);
}

$memcached->delete('user_'.$id, 0);
header('Location:my.setting.php?id='.$id);
die();
?>

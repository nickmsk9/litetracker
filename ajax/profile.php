<?php

require '../system/init.php';

function profile_ajax_response($ok, $message = '', $extra = array())
{
	header('Content-Type: application/json; charset=UTF-8');

	$payload = array(
		'ok' => ($ok ? 1 : 0),
		'message' => (string) $message,
	);

	if (!empty($extra) && is_array($extra)) {
		foreach ($extra as $key => $value) {
			$payload[$key] = $value;
		}
	}

	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	die();
}

function profile_ajax_require_login()
{
	global $USER;

	if (empty($USER['id'])) {
		profile_ajax_response(false, 'Требуется авторизация.');
	}
}

function profile_ajax_admin_ensure_schema()
{
	global $db;
	static $ready = false;

	if ($ready) {
		return;
	}

	$db->query(
		"CREATE TABLE IF NOT EXISTS `user_admin_notes` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`user_id` int unsigned NOT NULL,
			`admin_id` int unsigned NOT NULL,
			`note` text NOT NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `user_created` (`user_id`, `created_at`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin"
	);

	$ready = true;
}

$action = trim((string) ($_REQUEST['action'] ?? ''));

if ($action === 'send_message') {
	profile_ajax_require_login();

	$targetUserId = (int) ($_POST['user_id'] ?? 0);
	$text = trim((string) ($_POST['text'] ?? ''));
	$subject = trim((string) ($_POST['name'] ?? 'Сообщение'));

	if ($targetUserId <= 0) {
		profile_ajax_response(false, 'Получатель не найден.');
	}

	if ($targetUserId === (int) $USER['id']) {
		profile_ajax_response(false, 'Нельзя отправить сообщение самому себе.');
	}

	$targetUser = get_user_info($targetUserId);
	if (empty($targetUser['id'])) {
		profile_ajax_response(false, 'Пользователь не найден.');
	}

	if (user_is_blacklisted($targetUserId, (int) $USER['id'])) {
		profile_ajax_response(false, 'Пользователь добавил вас в ЧС.');
	}

	if (user_is_blacklisted((int) $USER['id'], $targetUserId)) {
		profile_ajax_response(false, 'Сначала уберите пользователя из ЧС.');
	}

	if ($text === '') {
		profile_ajax_response(false, 'Введите текст сообщения.');
	}

	if ($subject === '') {
		$subject = 'Сообщение';
	}

	send_msg($subject, $text, $targetUserId, (int) $USER['id']);
	profile_ajax_response(true, 'Сообщение отправлено.');
}

if ($action === 'toggle_blacklist') {
	profile_ajax_require_login();

	if (!user_blacklist_available()) {
		profile_ajax_response(false, 'Чёрный список пока недоступен.');
	}

	$targetUserId = (int) ($_POST['user_id'] ?? 0);
	if ($targetUserId <= 0) {
		profile_ajax_response(false, 'Пользователь не найден.');
	}

	if ($targetUserId === (int) $USER['id']) {
		profile_ajax_response(false, 'Нельзя добавить в ЧС самого себя.');
	}

	$targetUser = get_user_info($targetUserId);
	if (empty($targetUser['id'])) {
		profile_ajax_response(false, 'Пользователь не найден.');
	}

	$isBlacklisted = user_is_blacklisted((int) $USER['id'], $targetUserId);
	if ($isBlacklisted) {
		$db->query("DELETE FROM users_blacklist WHERE user_id = ".(int) $USER['id']." AND blocked_user_id = {$targetUserId}");
		profile_ajax_response(true, 'Пользователь убран из ЧС.', array(
			'blacklisted' => 0,
			'label' => 'Добавить в ЧС',
		));
	}

	$db->query(
		"INSERT INTO users_blacklist (user_id, blocked_user_id, date_added)
		 VALUES (".(int) $USER['id'].", {$targetUserId}, NOW())"
	);

	profile_ajax_response(true, 'Пользователь добавлен в ЧС.', array(
		'blacklisted' => 1,
		'label' => 'Убрать из ЧС',
	));
}

if ($action === 'moderate_profile') {
	profile_ajax_require_login();
	profile_ajax_admin_ensure_schema();

	if (empty($PRIV['setting_user']) && empty($PRIV['EDIT_PRIV'])) {
		profile_ajax_response(false, 'Недостаточно прав.');
	}

	$userId = (int) ($_POST['user_id'] ?? 0);
	if ($userId <= 0 || $userId === (int) ($USER['id'] ?? 0)) {
		profile_ajax_response(false, 'Некорректный пользователь.');
	}

	$target = get_user_info($userId);
	if (empty($target['id'])) {
		profile_ajax_response(false, 'Пользователь не найден.');
	}

	$targetPriv = get_priv_info((int) ($target['class'] ?? 0));
	if (!empty($targetPriv['EDIT_PRIV']) && empty($PRIV['EDIT_PRIV'])) {
		profile_ajax_response(false, 'Недостаточно прав для редактирования этого пользователя.');
	}

	$bonusColumn = (lt_column_exists('users', 'bonus') ? 'bonus' : 'voice');
	$updates = array();
	$historyNotes = array();
	$name = trim((string) ($_POST['name'] ?? $target['name']));
	if ($name !== '' && $name !== (string) $target['name']) {
		if (!validusername($name)) {
			profile_ajax_response(false, 'Некорректный ник.');
		}
		if (strlen($name) > 12) {
			profile_ajax_response(false, 'Ник слишком длинный.');
		}
		$nameExists = $db->psuper_query("SELECT id FROM users WHERE name = ? AND id <> ? LIMIT 1", 'si', [$name, $userId]);
		if (!empty($nameExists['id'])) {
			profile_ajax_response(false, 'Такой ник уже занят.');
		}
		$updates[] = "name='".$db->safesql($name)."'";
		$historyNotes[] = 'Ник изменен: '.$target['name'].' -> '.$name;
	}

	$email = trim((string) ($_POST['email'] ?? $target['email']));
	if ($email !== (string) ($target['email'] ?? '')) {
		if ($email !== '' && !validemail($email)) {
			profile_ajax_response(false, 'Некорректный E-mail.');
		}
		if ($email !== '') {
			$emailExists = $db->psuper_query("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1", 'si', [$email, $userId]);
			if (!empty($emailExists['id'])) {
				profile_ajax_response(false, 'Такой E-mail уже занят.');
			}
		}
		$updates[] = "email='".$db->safesql($email)."'";
	}

	$classId = (int) ($_POST['class'] ?? $target['class']);
	$classChanged = false;
	$classInfo = array();
	if ($classId > 0 && $classId !== (int) $target['class']) {
		$classInfo = $db->super_query("SELECT id, NAME, EDIT_PRIV FROM priv WHERE id = ".$classId." LIMIT 1");
		if (empty($classInfo['id'])) {
			profile_ajax_response(false, 'Класс не найден.');
		}
		if (!empty($classInfo['EDIT_PRIV']) && empty($PRIV['EDIT_PRIV'])) {
			profile_ajax_response(false, 'Нельзя назначить этот класс.');
		}
		$updates[] = "class=".$classId;
		$classChanged = true;
		$oldClassName = get_user_class_name((int) $target['class']);
		$newClassName = (string) ($classInfo['NAME'] ?? ('#'.$classId));
		$historyNotes[] = 'Класс изменен: '.$oldClassName.' -> '.$newClassName;
	}

	$enabled = ((int) ($_POST['enabled'] ?? 1) === 1 ? 1 : 0);
	$updates[] = "banned=".($enabled ? 0 : 1);

	$sex = ((int) ($_POST['sex'] ?? ($target['sex'] ?? 1)) === 1 ? 1 : 0);
	$updates[] = "sex=".$sex;

	$updates[] = "notify_comments=".(!empty($_POST['notify_comments']) ? 1 : 0);
	$updates[] = "download_local_retracker=".(!empty($_POST['download_local_retracker']) ? 1 : 0);
	$updates[] = "theme_dark=".(!empty($_POST['theme_dark']) ? 1 : 0);
	$updates[] = "bad_rating=".(!empty($_POST['bad_rating']) ? 1 : 0);
	$updates[] = "confirm=".(!empty($_POST['confirm']) ? 1 : 0);

	if (!empty($_POST['reset_birthday'])) {
		$updates[] = "birthday_date=NULL";
	}

	if (!empty($_POST['reset_rating'])) {
		$updates[] = "bad_rating=0";
	}

	$uploadedGb = str_replace(',', '.', trim((string) ($_POST['uploaded_gb'] ?? '')));
	if ($uploadedGb !== '' && is_numeric($uploadedGb)) {
		$updates[] = "uploaded=".max(0, (int) round(((float) $uploadedGb) * 1024 * 1024 * 1024));
	}

	$downloadedGb = str_replace(',', '.', trim((string) ($_POST['downloaded_gb'] ?? '')));
	if ($downloadedGb !== '' && is_numeric($downloadedGb)) {
		$updates[] = "downloaded=".max(0, (int) round(((float) $downloadedGb) * 1024 * 1024 * 1024));
	}

	$bonusValue = str_replace(',', '.', trim((string) ($_POST['bonus_value'] ?? '')));
	if ($bonusValue !== '' && is_numeric($bonusValue)) {
		$updates[] = $bonusColumn."=".max(0, (float) $bonusValue);
	}

	$money = trim((string) ($_POST['money'] ?? ''));
	if ($money !== '' && preg_match('~^-?\d+$~', $money)) {
		$updates[] = "money=".max(0, (int) $money);
	}

	$uploadedMb = (int) ($_POST['uploaded_mb'] ?? 0);
	$downloadedMb = (int) ($_POST['downloaded_mb'] ?? 0);
	if ($uploadedMb !== 0) {
		$delta = (int) ($uploadedMb * 1024 * 1024);
		$updates[] = "uploaded=IF((uploaded + (".$delta.")) < 0, 0, (uploaded + (".$delta.")))";
	}
	if ($downloadedMb !== 0) {
		$delta = (int) ($downloadedMb * 1024 * 1024);
		$updates[] = "downloaded=IF((downloaded + (".$delta.")) < 0, 0, (downloaded + (".$delta.")))";
	}

	if (!empty($_POST['reset_passkey'])) {
		$updates[] = "passkey='".$db->safesql(md5(uniqid('passkey', true)))."'";
	}

	$note = trim((string) ($_POST['note'] ?? ''));
	$deleteUser = !empty($_POST['delete_user']);
	if ($deleteUser) {
		$deletedName = substr('del'.$userId, 0, 12);
		$db->query("UPDATE users SET name = '".$db->safesql($deletedName)."', banned = 1, confirm = 0, profile_text = '' WHERE id = ".$userId." LIMIT 1");
		$memcached->delete('user_'.$userId, 0);
		profile_ajax_response(true, 'Пользователь деактивирован.', array('reload' => 1));
	}

	if ($updates) {
		$db->query("UPDATE users SET ".implode(', ', array_unique($updates))." WHERE id = ".$userId);
	}

	if ($classChanged && !empty($classInfo['NAME'])) {
		send_msg($language['setting_76'] ?? 'Изменение класса', sprintf($language['setting_77'] ?? 'Ваш класс изменен на [b]%s[/b].', $classInfo['NAME']), $userId, 0);
	}

	if ($note !== '') {
		$historyNotes[] = $note;
		send_msg('Комментарий модератора', $note, $userId, (int) ($USER['id'] ?? 0));
		lt_notifications_handle_moderator_note($userId, (int) ($USER['id'] ?? 0), $note);
	}

	foreach ($historyNotes as $historyNote) {
		$historyNote = trim((string) $historyNote);
		if ($historyNote === '' || !lt_table_exists('user_admin_notes')) {
			continue;
		}
		$db->query(
			"INSERT INTO user_admin_notes (user_id, admin_id, note, created_at)
			 VALUES (".$userId.", ".(int) ($USER['id'] ?? 0).", '".$db->safesql($historyNote)."', NOW())"
		);
	}

	$memcached->delete('user_'.$userId, 0);
	$updated = $db->super_query("SELECT * FROM users WHERE id = ".$userId." LIMIT 1");
	$updatedName = htmlspecialchars((string) ($updated['name'] ?? $target['name']), ENT_QUOTES, 'UTF-8');
	$updatedClass = (int) ($updated['class'] ?? $target['class']);
	profile_ajax_response(true, 'Изменения сохранены.', array(
		'reload' => 0,
		'display_name_html' => get_user_color($updatedClass, $updatedName, $updated),
		'class_name' => get_user_class_name($updatedClass),
		'uploaded' => mksize((int) ($updated['uploaded'] ?? 0)),
		'downloaded' => mksize((int) ($updated['downloaded'] ?? 0)),
		'bonus' => number_format((float) ($updated[$bonusColumn] ?? 0), 2, '.', ' '),
		'history_notes' => $historyNotes,
		'history_date' => convent_date(get_date_time()),
		'history_admin' => (int) ($USER['id'] ?? 0),
	));
}

profile_ajax_response(false, 'Неизвестное действие.');

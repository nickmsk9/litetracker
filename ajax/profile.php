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

	$userColumns = array(
		'support_enabled' => "ALTER TABLE `users` ADD COLUMN `support_enabled` tinyint NOT NULL DEFAULT '0' AFTER `plus_badge`",
		'support_until' => "ALTER TABLE `users` ADD COLUMN `support_until` datetime DEFAULT NULL AFTER `support_enabled`",
		'warning_until' => "ALTER TABLE `users` ADD COLUMN `warning_until` datetime DEFAULT NULL AFTER `support_until`",
		'chat_ban' => "ALTER TABLE `users` ADD COLUMN `chat_ban` tinyint NOT NULL DEFAULT '0' AFTER `warning_until`",
		'in_group' => "ALTER TABLE `users` ADD COLUMN `in_group` tinyint NOT NULL DEFAULT '0' AFTER `chat_ban`",
	);

	foreach ($userColumns as $column => $sql) {
		if (!lt_column_exists('users', $column)) {
			$db->query($sql);
		}
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

function profile_ajax_wall_payload($objectId, $message)
{
	profile_ajax_response(true, $message, array(
		'html' => user_wall_render_list((int) $objectId),
	));
}

$action = trim((string) ($_REQUEST['action'] ?? ''));

if ($action === 'wall_add') {
	profile_ajax_require_login();

	$objectId = (int) ($_POST['object_id'] ?? 0);
	$wallOwner = get_user_info($objectId);
	if (empty($wallOwner['id'])) {
		profile_ajax_response(false, 'Пользователь не найден.');
	}

	$text = trim((string) ($_POST['text'] ?? ''));
	if ($text === '') {
		profile_ajax_response(false, 'Введите текст комментария.');
	}

	$parentId = (int) ($_POST['parent_id'] ?? 0);
	if (!user_wall_supports_threads()) {
		$parentId = 0;
	}

	if ($parentId > 0) {
		$parentCheck = $db->super_query("SELECT id FROM comments_users WHERE id = {$parentId} AND id_users = {$objectId} LIMIT 1");
		if (empty($parentCheck['id'])) {
			$parentId = 0;
		}
	}

	$insertFields = array('id_user', 'id_users', 'date', 'text');
	$insertValues = array((int) $USER['id'], $objectId, 'NOW()', "'".$db->safesql($text)."'");

	if (user_wall_supports_threads()) {
		$insertFields[] = 'parent_id';
		$insertValues[] = $parentId;
	}

	$db->query(
		"INSERT INTO comments_users (`".implode('`,`', $insertFields)."`)
		 VALUES (".implode(', ', $insertValues).")"
	);

	if ((int) $USER['id'] !== (int) $wallOwner['id'] && !empty($wallOwner['notify_comments'])) {
		send_msg(
			'Новый комментарий на стене',
			'Пользователь [b]' . $USER['name'] . '[/b] оставил новый комментарий на вашей стене.' . "\n" . 'Ссылка: ' . profile_href((int) $wallOwner['id']),
			(int) $wallOwner['id'],
			0
		);
	}

	profile_ajax_wall_payload($objectId, 'Комментарий добавлен.');
}

if ($action === 'wall_edit') {
	profile_ajax_require_login();

	$objectId = (int) ($_POST['object_id'] ?? 0);
	$commentId = (int) ($_POST['comment_id'] ?? 0);
	$text = trim((string) ($_POST['text'] ?? ''));

	if ($objectId <= 0 || $commentId <= 0) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	if ($text === '') {
		profile_ajax_response(false, 'Введите текст комментария.');
	}

	$comment = $db->super_query("SELECT * FROM comments_users WHERE id = {$commentId} AND id_users = {$objectId} LIMIT 1");
	if (empty($comment['id'])) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	if (!comments_user_can_edit((int) ($comment['id_user'] ?? 0), (string) ($comment['date'] ?? ''), 'users')) {
		profile_ajax_response(false, 'У вас недостаточно прав для редактирования.');
	}

	$db->query(
		"UPDATE comments_users
		 SET text = '".$db->safesql($text)."',
		     id_user_edit = ".(int) $USER['id'].",
		     date_edit = NOW()
		 WHERE id = {$commentId} AND id_users = {$objectId}"
	);

	profile_ajax_wall_payload($objectId, 'Комментарий обновлен.');
}

if ($action === 'wall_delete') {
	profile_ajax_require_login();

	$objectId = (int) ($_POST['object_id'] ?? 0);
	$commentId = (int) ($_POST['comment_id'] ?? 0);

	if ($objectId <= 0 || $commentId <= 0) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	$parentSelect = (user_wall_supports_threads() ? 'parent_id' : '0 AS parent_id');
	$comment = $db->super_query("SELECT id, id_user, {$parentSelect} FROM comments_users WHERE id = {$commentId} AND id_users = {$objectId} LIMIT 1");
	if (empty($comment['id'])) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	if (empty($PRIV['comments_delete'])) {
		profile_ajax_response(false, 'У вас недостаточно прав для удаления.');
	}

	$deletedByAdmin = (!empty($PRIV['comments_delete']) && (int) $USER['id'] !== (int) ($comment['id_user'] ?? 0));
	$deletedText = $db->safesql(lt_comment_deleted_placeholder($deletedByAdmin));
	$db->query("UPDATE comments_users SET text = '{$deletedText}', id_user_edit = ".(int) $USER['id'].", date_edit = NOW() WHERE id = {$commentId} AND id_users = {$objectId}");
	profile_ajax_wall_payload($objectId, 'Комментарий удалён.');
}

if ($action === 'wall_report') {
	profile_ajax_require_login();

	$objectId = (int) ($_POST['object_id'] ?? 0);
	$commentId = (int) ($_POST['comment_id'] ?? 0);

	if ($objectId <= 0 || $commentId <= 0) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	$comment = $db->super_query("SELECT id, id_users, id_user, text FROM comments_users WHERE id = {$commentId} AND id_users = {$objectId} LIMIT 1");
	if (empty($comment['id'])) {
		profile_ajax_response(false, 'Комментарий не найден.');
	}

	if ((int) $comment['id_user'] === (int) $USER['id']) {
		profile_ajax_response(false, 'Нельзя пожаловаться на свой комментарий.');
	}

	user_wall_reports_ensure_table();
	$tableName = user_wall_reports_table_name();
	$existingReport = $db->super_query(
		"SELECT id
		 FROM `".$tableName."`
		 WHERE comment_id = {$commentId}
		   AND reporter_user_id = ".(int) $USER['id']."
		   AND status = 'open'
		 LIMIT 1"
	);

	if (!empty($existingReport['id'])) {
		profile_ajax_response(false, 'Вы уже пожаловались на этот комментарий.');
	}

	$db->query(
		"INSERT INTO `".$tableName."` (`comment_id`, `object_id`, `comment_user_id`, `reporter_user_id`, `comment_text_snapshot`, `status`, `created_at`)
		 VALUES ({$commentId}, {$objectId}, ".(int) $comment['id_user'].", ".(int) $USER['id'].", '".$db->safesql((string) ($comment['text'] ?? ''))."', 'open', NOW())"
	);
	user_wall_reports_notify_moderators((int) $db->insert_id(), $objectId, $commentId, (string) ($USER['name'] ?? ''));

	profile_ajax_response(true, 'Жалоба отправлена администрации.');
}

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

	$updates = array();
	$name = trim((string) ($_POST['name'] ?? $target['name']));
	if ($name !== '' && $name !== (string) $target['name']) {
		if (!validusername($name)) {
			profile_ajax_response(false, 'Некорректный ник.');
		}
		$updates[] = "name='".$db->safesql($name)."'";
	}

	$classId = (int) ($_POST['class'] ?? $target['class']);
	if ($classId > 0 && $classId !== (int) $target['class']) {
		$classInfo = $db->super_query("SELECT id, NAME, EDIT_PRIV FROM priv WHERE id = ".$classId." LIMIT 1");
		if (empty($classInfo['id'])) {
			profile_ajax_response(false, 'Класс не найден.');
		}
		if (!empty($classInfo['EDIT_PRIV']) && empty($PRIV['EDIT_PRIV'])) {
			profile_ajax_response(false, 'Нельзя назначить этот класс.');
		}
		$updates[] = "class=".$classId;
	}

	$enabled = ((int) ($_POST['enabled'] ?? 1) === 1 ? 1 : 0);
	$updates[] = "banned=".($enabled ? 0 : 1);

	if (!empty($_POST['reset_birthday'])) {
		$updates[] = "birthday_date=NULL";
	}

	if (!empty($_POST['reset_rating'])) {
		$updates[] = "bad_rating=0";
	}

	$supportEnabled = ((int) ($_POST['support_enabled'] ?? 0) === 1 ? 1 : 0);
	$updates[] = "support_enabled=".$supportEnabled;
	$supportUntil = trim((string) ($_POST['support_until'] ?? ''));
	if ($supportUntil !== '' && preg_match('~^\d{4}-\d{2}-\d{2}$~', $supportUntil)) {
		$updates[] = "support_until='".$db->safesql($supportUntil.' 23:59:59')."'";
	} elseif ($supportEnabled === 0) {
		$updates[] = "support_until=NULL";
	}

	$warningUntil = trim((string) ($_POST['warning_until'] ?? ''));
	if ($warningUntil !== '' && preg_match('~^\d{4}-\d{2}-\d{2}$~', $warningUntil)) {
		$updates[] = "warning_until='".$db->safesql($warningUntil.' 23:59:59')."'";
	} else {
		$updates[] = "warning_until=NULL";
	}

	$chatBan = ((int) ($_POST['chat_ban'] ?? 0) === 1 ? 1 : 0);
	$inGroup = ((int) ($_POST['in_group'] ?? 0) === 1 ? 1 : 0);
	$updates[] = "chat_ban=".$chatBan;
	$updates[] = "in_group=".$inGroup;

	$uploadedMb = (int) ($_POST['uploaded_mb'] ?? 0);
	$downloadedMb = (int) ($_POST['downloaded_mb'] ?? 0);
	if ($uploadedMb !== 0) {
		$delta = (int) ($uploadedMb * 1024 * 1024);
		$updates[] = "uploaded=GREATEST(uploaded + (".$delta."), 0)";
	}
	if ($downloadedMb !== 0) {
		$delta = (int) ($downloadedMb * 1024 * 1024);
		$updates[] = "downloaded=GREATEST(downloaded + (".$delta."), 0)";
	}

	if (!empty($_POST['reset_passkey'])) {
		$updates[] = "passkey='".$db->safesql(md5(uniqid('passkey', true)))."'";
	}

	$note = trim((string) ($_POST['note'] ?? ''));
	$deleteUser = !empty($_POST['delete_user']);
	if ($deleteUser) {
		$db->query("DELETE FROM users WHERE id = ".$userId." LIMIT 1");
		$memcached->delete('user_'.$userId, 0);
		profile_ajax_response(true, 'Пользователь удалён.', array('reload' => 1));
	}

	if ($updates) {
		$db->query("UPDATE users SET ".implode(', ', array_unique($updates))." WHERE id = ".$userId);
	}

	if ($note !== '' && lt_table_exists('user_admin_notes')) {
		$db->query(
			"INSERT INTO user_admin_notes (user_id, admin_id, note, created_at)
			 VALUES (".$userId.", ".(int) ($USER['id'] ?? 0).", '".$db->safesql($note)."', NOW())"
		);
		send_msg('Комментарий модератора', $note, $userId, (int) ($USER['id'] ?? 0));
	}

	$memcached->delete('user_'.$userId, 0);
	profile_ajax_response(true, 'Изменения сохранены.', array('reload' => 1));
}

profile_ajax_response(false, 'Неизвестное действие.');

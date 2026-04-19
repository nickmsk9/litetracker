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

	if (empty($PRIV['comments_edit'])) {
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

profile_ajax_response(false, 'Неизвестное действие.');

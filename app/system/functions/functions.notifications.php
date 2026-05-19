<?php
/*
===================================================================
LiteTracker Source
===================================================================
Notification helpers
===================================================================
*/

function lt_notifications_table_ready()
{
	global $db;

	if (lt_table_exists('notifications')) {
		return true;
	}

	$created = $db->query(
		"CREATE TABLE IF NOT EXISTS `notifications` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`user_id` INT UNSIGNED NOT NULL,
			`actor_id` INT UNSIGNED NULL,
			`type` VARCHAR(64) NOT NULL,
			`entity_type` VARCHAR(64) NOT NULL,
			`entity_id` INT UNSIGNED NULL,
			`related_type` VARCHAR(64) NULL,
			`related_id` INT UNSIGNED NULL,
			`title` VARCHAR(255) NOT NULL,
			`message` TEXT NULL,
			`url` VARCHAR(500) NULL,
			`payload_json` TEXT NULL,
			`dedupe_key` VARCHAR(191) NULL,
			`is_read` TINYINT(1) NOT NULL DEFAULT 0,
			`read_at` DATETIME NULL,
			`is_archived` TINYINT(1) NOT NULL DEFAULT 0,
			`created_at` DATETIME NOT NULL,
			`updated_at` DATETIME NULL,
			PRIMARY KEY (`id`),
			KEY `user_read_created` (`user_id`, `is_read`, `created_at`),
			KEY `user_created` (`user_id`, `created_at`),
			KEY `type_entity` (`type`, `entity_type`, `entity_id`),
			KEY `user_dedupe` (`user_id`, `dedupe_key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		0
	);

	if ($created !== false) {
		lt_schema_cache_delete(lt_schema_table_cache_key('notifications'));
	}

	return lt_table_exists('notifications', true);
}

function lt_notification_cache_key($userId)
{
	return 'notifications:unread:'.(int) $userId;
}

function lt_notifications_invalidate_user($userId)
{
	$userId = (int) $userId;
	if ($userId > 0 && function_exists('lt_cache_delete')) {
		lt_cache_delete(lt_notification_cache_key($userId));
	}
}

function lt_notification_should_skip(int $recipientId, ?int $actorId): bool
{
	$recipientId = (int) $recipientId;
	$actorId = (int) $actorId;

	return ($recipientId <= 0 || ($actorId > 0 && $recipientId === $actorId));
}

function lt_notification_clean_key($value, $fallback = 'general')
{
	$value = strtolower(trim((string) $value));
	$value = preg_replace('~[^a-z0-9:_-]+~i', '_', $value);
	$value = trim($value, '_-:');

	return ($value !== '' ? substr($value, 0, 64) : $fallback);
}

function lt_notification_text($value, $limit)
{
	$value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
	if ($value === '') {
		return '';
	}

	if (function_exists('mb_substr')) {
		return mb_substr($value, 0, $limit, 'UTF-8');
	}

	return substr($value, 0, $limit);
}

function lt_notification_internal_url($url)
{
	$url = trim((string) $url);
	if ($url === '' || strpos($url, "\0") !== false) {
		return '';
	}

	$url = ltrim($url);
	if (preg_match('~^(?:https?:)?//~i', $url)) {
		return '';
	}

	if ($url[0] === '/') {
		$url = ltrim($url, '/');
	}

	if ($url === '' || preg_match('~^(?:javascript|data):~i', $url)) {
		return '';
	}

	return substr($url, 0, 500);
}

function lt_notification_default_dedupe_key(array $data)
{
	$parts = array(
		lt_notification_clean_key($data['type'] ?? ''),
		lt_notification_clean_key($data['entity_type'] ?? ''),
		(int) ($data['entity_id'] ?? 0),
		lt_notification_clean_key($data['related_type'] ?? ''),
		(int) ($data['related_id'] ?? 0),
	);

	return substr(implode(':', $parts), 0, 191);
}

function lt_notification_create(array $data): int
{
	global $db;

	if (!lt_notifications_table_ready()) {
		return 0;
	}

	$userId = (int) ($data['user_id'] ?? 0);
	$actorId = isset($data['actor_id']) ? (int) $data['actor_id'] : 0;
	if (lt_notification_should_skip($userId, $actorId)) {
		return 0;
	}

	$type = lt_notification_clean_key($data['type'] ?? '', 'general');
	$entityType = lt_notification_clean_key($data['entity_type'] ?? '', 'general');
	$entityId = isset($data['entity_id']) && (int) $data['entity_id'] > 0 ? (int) $data['entity_id'] : null;
	$relatedType = trim((string) ($data['related_type'] ?? ''));
	$relatedType = ($relatedType !== '' ? lt_notification_clean_key($relatedType) : null);
	$relatedId = isset($data['related_id']) && (int) $data['related_id'] > 0 ? (int) $data['related_id'] : null;
	$title = lt_notification_text($data['title'] ?? '', 255);
	$message = lt_notification_text($data['message'] ?? '', 2000);
	$url = lt_notification_internal_url($data['url'] ?? '');
	$dedupeKey = trim((string) ($data['dedupe_key'] ?? ''));
	$dedupeKey = ($dedupeKey !== '' ? substr($dedupeKey, 0, 191) : null);
	$payloadJson = null;

	if ($title === '') {
		$title = 'Новое уведомление';
	}

	if (isset($data['payload']) && is_array($data['payload'])) {
		$payloadJson = json_encode($data['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($payloadJson)) {
			$payloadJson = null;
		}
	}

	$db->query(
		"INSERT INTO notifications
			(user_id, actor_id, type, entity_type, entity_id, related_type, related_id, title, message, url, payload_json, dedupe_key, is_read, is_archived, created_at, updated_at)
		 VALUES (
			".$userId.",
			".($actorId > 0 ? $actorId : 'NULL').",
			'".$db->safesql($type)."',
			'".$db->safesql($entityType)."',
			".($entityId !== null ? $entityId : 'NULL').",
			".($relatedType !== null ? "'".$db->safesql($relatedType)."'" : 'NULL').",
			".($relatedId !== null ? $relatedId : 'NULL').",
			'".$db->safesql($title)."',
			".($message !== '' ? "'".$db->safesql($message)."'" : 'NULL').",
			".($url !== '' ? "'".$db->safesql($url)."'" : 'NULL').",
			".($payloadJson !== null ? "'".$db->safesql($payloadJson)."'" : 'NULL').",
			".($dedupeKey !== null ? "'".$db->safesql($dedupeKey)."'" : 'NULL').",
			0,
			0,
			NOW(),
			NULL
		 )"
	);

	$id = (int) $db->insert_id();
	if ($id > 0) {
		lt_notifications_invalidate_user($userId);
	}

	return $id;
}

function lt_notification_create_once(array $data, int $dedupeSeconds = 300): ?int
{
	global $db;

	if (!lt_notifications_table_ready()) {
		return null;
	}

	$userId = (int) ($data['user_id'] ?? 0);
	$actorId = isset($data['actor_id']) ? (int) $data['actor_id'] : 0;
	if (lt_notification_should_skip($userId, $actorId)) {
		return null;
	}

	$dedupeKey = trim((string) ($data['dedupe_key'] ?? ''));
	if ($dedupeKey === '') {
		$dedupeKey = lt_notification_default_dedupe_key($data);
	}
	$dedupeKey = substr($dedupeKey, 0, 191);
	$data['dedupe_key'] = $dedupeKey;
	$dedupeSeconds = max(1, (int) $dedupeSeconds);

	$existing = $db->super_query(
		"SELECT id
		 FROM notifications
		 WHERE user_id = ".$userId."
		   AND dedupe_key = '".$db->safesql($dedupeKey)."'
		   AND created_at >= DATE_SUB(NOW(), INTERVAL ".$dedupeSeconds." SECOND)
		 LIMIT 1"
	);

	if (!empty($existing['id'])) {
		return null;
	}

	$id = lt_notification_create($data);

	return ($id > 0 ? $id : null);
}

function lt_notifications_unread_count(int $userId): int
{
	global $db;

	$userId = (int) $userId;
	if ($userId <= 0 || !lt_notifications_table_ready()) {
		return 0;
	}

	if (function_exists('lt_cache_get')) {
		$cached = lt_cache_get(lt_notification_cache_key($userId));
		if ($cached !== false && $cached !== null) {
			return max(0, (int) $cached);
		}
	}

	$row = $db->super_query("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ".$userId." AND is_read = 0 AND is_archived = 0");
	$count = (int) ($row['c'] ?? 0);
	if (function_exists('lt_cache_set')) {
		lt_cache_set(lt_notification_cache_key($userId), $count, 45);
	}

	return $count;
}

function lt_notifications_fetch(int $userId, array $options = []): array
{
	global $db;

	$userId = (int) $userId;
	if ($userId <= 0 || !lt_notifications_table_ready()) {
		return array('items' => array(), 'next_cursor' => null);
	}

	$limit = max(1, min(50, (int) ($options['limit'] ?? 20)));
	$cursor = max(0, (int) ($options['cursor'] ?? 0));
	$unreadOnly = !empty($options['unread_only']);
	$includeArchived = !empty($options['include_archived']);
	$where = array('n.user_id = '.$userId);
	if (!$includeArchived) {
		$where[] = 'n.is_archived = 0';
	}
	if ($unreadOnly) {
		$where[] = 'n.is_read = 0';
	}
	if ($cursor > 0) {
		$where[] = 'n.id < '.$cursor;
	}

	$sql = $db->query(
		"SELECT n.*, u.name AS actor_name, u.class AS actor_class, u.avatar AS actor_avatar
		 FROM notifications AS n
		 LEFT JOIN users AS u ON u.id = n.actor_id
		 WHERE ".implode(' AND ', $where)."
		 ORDER BY n.id DESC
		 LIMIT ".($limit + 1)
	);

	$items = array();
	while ($row = $db->get_row($sql)) {
		$items[] = lt_notification_public_row($row);
	}
	$db->free($sql);

	$nextCursor = null;
	if (count($items) > $limit) {
		array_pop($items);
		$lastVisible = end($items);
		$nextCursor = (int) ($lastVisible['id'] ?? 0);
	}

	return array(
		'items' => $items,
		'next_cursor' => ($nextCursor > 0 ? $nextCursor : null),
	);
}

function lt_notification_public_row(array $row)
{
	$actorId = (int) ($row['actor_id'] ?? 0);
	$actorName = trim((string) ($row['actor_name'] ?? ''));
	$avatar = 'public/images/default_avatar.gif';
	if (!empty($row['actor_avatar']) && is_file('public/avatars/small/'.$row['actor_avatar'])) {
		$avatar = 'public/avatars/small/'.$row['actor_avatar'];
	}

	return array(
		'id' => (int) ($row['id'] ?? 0),
		'type' => (string) ($row['type'] ?? ''),
		'entity_type' => (string) ($row['entity_type'] ?? ''),
		'entity_id' => (int) ($row['entity_id'] ?? 0),
		'title' => (string) ($row['title'] ?? ''),
		'message' => (string) ($row['message'] ?? ''),
		'url' => lt_notification_internal_url($row['url'] ?? ''),
		'is_read' => (int) ($row['is_read'] ?? 0),
		'is_archived' => (int) ($row['is_archived'] ?? 0),
		'created_at' => (string) ($row['created_at'] ?? ''),
		'created_label' => (!empty($row['created_at']) ? convent_date((string) $row['created_at']) : ''),
		'actor' => array(
			'id' => $actorId,
			'name' => $actorName,
			'class' => (int) ($row['actor_class'] ?? 0),
			'profile_url' => ($actorId > 0 ? profile_href($actorId) : ''),
			'avatar' => $avatar,
		),
	);
}

function lt_notifications_ids(array $ids)
{
	$result = array();
	foreach ($ids as $id) {
		$id = (int) $id;
		if ($id > 0) {
			$result[$id] = $id;
		}
	}

	return array_values($result);
}

function lt_notifications_mark_read(int $userId, array $ids): int
{
	global $db;

	$userId = (int) $userId;
	$ids = lt_notifications_ids($ids);
	if ($userId <= 0 || !$ids || !lt_notifications_table_ready()) {
		return 0;
	}

	$db->query("UPDATE notifications SET is_read = 1, read_at = IF(read_at IS NULL, NOW(), read_at), updated_at = NOW() WHERE user_id = ".$userId." AND id IN (".implode(',', $ids).") AND is_read = 0");
	$count = max(0, (int) $db->affected_rows());
	if ($count > 0) {
		lt_notifications_invalidate_user($userId);
	}

	return $count;
}

function lt_notifications_mark_all_read(int $userId): int
{
	global $db;

	$userId = (int) $userId;
	if ($userId <= 0 || !lt_notifications_table_ready()) {
		return 0;
	}

	$db->query("UPDATE notifications SET is_read = 1, read_at = IF(read_at IS NULL, NOW(), read_at), updated_at = NOW() WHERE user_id = ".$userId." AND is_read = 0 AND is_archived = 0");
	$count = max(0, (int) $db->affected_rows());
	if ($count > 0) {
		lt_notifications_invalidate_user($userId);
	}

	return $count;
}

function lt_notifications_archive(int $userId, array $ids): int
{
	global $db;

	$userId = (int) $userId;
	$ids = lt_notifications_ids($ids);
	if ($userId <= 0 || !$ids || !lt_notifications_table_ready()) {
		return 0;
	}

	$db->query("UPDATE notifications SET is_archived = 1, is_read = 1, read_at = IF(read_at IS NULL, NOW(), read_at), updated_at = NOW() WHERE user_id = ".$userId." AND id IN (".implode(',', $ids).") AND is_archived = 0");
	$count = max(0, (int) $db->affected_rows());
	if ($count > 0) {
		lt_notifications_invalidate_user($userId);
	}

	return $count;
}

function lt_notification_url_for_event(array $event): string
{
	$entityType = (string) ($event['entity_type'] ?? '');
	$entityId = (int) ($event['entity_id'] ?? 0);
	$relatedId = (int) ($event['related_id'] ?? 0);

	if ($entityType === 'comments_torrents' || ($entityType === 'comment' && (string) ($event['comment_type'] ?? '') === 'torrents')) {
		return 'details.php?id='.$entityId.($relatedId > 0 ? '#wall-comment-'.$relatedId : '');
	}

	if ($entityType === 'comments_users' || ($entityType === 'comment' && (string) ($event['comment_type'] ?? '') === 'users')) {
		return profile_href($entityId).($relatedId > 0 ? '#wall-comment-'.$relatedId : '');
	}

	if ($entityType === 'mail') {
		$actorId = (int) ($event['actor_id'] ?? 0);
		return 'my.mail.php?act=conversation'.($actorId > 0 ? '&id_user='.$actorId : '');
	}

	if ($entityType === 'torrent') {
		return ($entityId > 0 ? 'details.php?id='.$entityId : 'my.releases.php');
	}

	if ($entityType === 'user') {
		return profile_href($entityId);
	}

	return 'notifications.php';
}

function lt_notifications_handle_comment_added($type, $objectId, $commentId, $parentId, $actorId)
{
	global $db;

	$type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
	$objectId = (int) $objectId;
	$commentId = (int) $commentId;
	$parentId = (int) $parentId;
	$actorId = (int) $actorId;
	if ($type === '' || $objectId <= 0 || $commentId <= 0 || $actorId <= 0) {
		return;
	}

	$actor = get_user_info($actorId);
	$actorName = trim((string) ($actor['name'] ?? 'Пользователь'));
	$notified = array();
	$commentUrl = lt_notification_url_for_event(array(
		'entity_type' => 'comment',
		'comment_type' => $type,
		'entity_id' => $objectId,
		'related_id' => $commentId,
	));

	if ($parentId > 0) {
		$table = comments_table_name($type);
		$objectColumn = comments_object_column($type);
		$parent = $db->super_query("SELECT id, id_user FROM `".$table."` WHERE id = ".$parentId." AND `".$objectColumn."` = ".$objectId." LIMIT 1");
		$recipientId = (int) ($parent['id_user'] ?? 0);
		if (!lt_notification_should_skip($recipientId, $actorId)) {
			lt_notification_create_once(array(
				'user_id' => $recipientId,
				'actor_id' => $actorId,
				'type' => 'comment_reply',
				'entity_type' => 'comment',
				'entity_id' => $parentId,
				'related_type' => 'comment',
				'related_id' => $commentId,
				'title' => 'Вам ответили на комментарий',
				'message' => $actorName.' ответил на ваш комментарий.',
				'url' => $commentUrl,
				'dedupe_key' => 'comment_reply:'.$parentId.':'.$commentId,
			), 86400);
			$notified[$recipientId] = true;
		}
	}

	if ($type === 'torrents') {
		$torrent = $db->super_query("SELECT id, id_user, name FROM torrents WHERE id = ".$objectId." LIMIT 1");
		$recipientId = (int) ($torrent['id_user'] ?? 0);
		if (!isset($notified[$recipientId]) && !lt_notification_should_skip($recipientId, $actorId)) {
			lt_notification_create_once(array(
				'user_id' => $recipientId,
				'actor_id' => $actorId,
				'type' => 'torrent_comment',
				'entity_type' => 'torrent',
				'entity_id' => $objectId,
				'related_type' => 'comment',
				'related_id' => $commentId,
				'title' => 'Новый комментарий к релизу',
				'message' => $actorName.' прокомментировал релиз "'.lt_notification_text($torrent['name'] ?? '', 140).'".',
				'url' => $commentUrl,
				'dedupe_key' => 'torrent_comment:'.$objectId.':'.$commentId,
			), 86400);
		}
	}
}

function lt_notifications_handle_comment_deleted($type, $objectId, $commentId, $commentUserId, $actorId, $deletedByAdmin)
{
	$type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
	$objectId = (int) $objectId;
	$commentId = (int) $commentId;
	$commentUserId = (int) $commentUserId;
	$actorId = (int) $actorId;
	if (!$deletedByAdmin || $type === '' || $objectId <= 0 || $commentId <= 0 || lt_notification_should_skip($commentUserId, $actorId)) {
		return;
	}

	lt_notification_create_once(array(
		'user_id' => $commentUserId,
		'actor_id' => $actorId,
		'type' => 'comment_deleted',
		'entity_type' => 'comment',
		'entity_id' => $commentId,
		'related_type' => $type,
		'related_id' => $objectId,
		'title' => 'Комментарий удалён модератором',
		'message' => 'Ваш комментарий был удалён модератором.',
		'url' => lt_notification_url_for_event(array(
			'entity_type' => 'comment',
			'comment_type' => $type,
			'entity_id' => $objectId,
			'related_id' => $commentId,
		)),
		'dedupe_key' => 'comment_deleted:'.$type.':'.$commentId,
	), 86400);
}

function lt_notifications_handle_comment_pinned($type, $objectId, $commentId, $commentUserId, $actorId)
{
	$type = preg_replace('~[^a-z0-9_]~i', '', (string) $type);
	$objectId = (int) $objectId;
	$commentId = (int) $commentId;
	$commentUserId = (int) $commentUserId;
	$actorId = (int) $actorId;
	if ($type === '' || $objectId <= 0 || $commentId <= 0 || lt_notification_should_skip($commentUserId, $actorId)) {
		return;
	}

	lt_notification_create_once(array(
		'user_id' => $commentUserId,
		'actor_id' => $actorId,
		'type' => 'comment_pinned',
		'entity_type' => 'comment',
		'entity_id' => $commentId,
		'related_type' => $type,
		'related_id' => $objectId,
		'title' => 'Комментарий закреплён',
		'message' => 'Ваш комментарий закрепили.',
		'url' => lt_notification_url_for_event(array(
			'entity_type' => 'comment',
			'comment_type' => $type,
			'entity_id' => $objectId,
			'related_id' => $commentId,
		)),
		'dedupe_key' => 'comment_pinned:'.$type.':'.$commentId,
	), 86400);
}

function lt_notifications_handle_private_message($recipientId, $actorId, $messageId, $subject)
{
	$recipientId = (int) $recipientId;
	$actorId = (int) $actorId;
	$messageId = (int) $messageId;
	if ($messageId <= 0 || lt_notification_should_skip($recipientId, $actorId)) {
		return;
	}

	$actor = get_user_info($actorId);
	$actorName = trim((string) ($actor['name'] ?? 'Пользователь'));
	lt_notification_create_once(array(
		'user_id' => $recipientId,
		'actor_id' => $actorId,
		'type' => 'private_message',
		'entity_type' => 'mail',
		'entity_id' => $messageId,
		'title' => 'Новое личное сообщение',
		'message' => $actorName.' написал: '.lt_notification_text($subject, 140),
		'url' => lt_notification_url_for_event(array('entity_type' => 'mail', 'actor_id' => $actorId)),
		'dedupe_key' => 'private_message:'.$messageId,
	), 86400);
}

function lt_notifications_handle_torrent_status($ownerId, $actorId, $torrentId, $torrentName, $status, $reason = '')
{
	$ownerId = (int) $ownerId;
	$actorId = (int) $actorId;
	$torrentId = (int) $torrentId;
	$status = lt_notification_clean_key($status);
	$reason = lt_notification_text($reason, 500);
	if ($torrentId <= 0 || lt_notification_should_skip($ownerId, $actorId)) {
		return;
	}

	$titles = array(
		'approved' => 'Релиз одобрен',
		'need_fix' => 'Релиз отправлен на доработку',
		'rejected' => 'Релиз отклонён',
		'hidden' => 'Релиз скрыт',
		'deleted' => 'Релиз удалён',
		'pending' => 'Релиз ожидает модерации',
	);
	$messages = array(
		'approved' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" доступен в каталоге.',
		'need_fix' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" нужно доработать.',
		'rejected' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" отклонён модератором.',
		'hidden' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" скрыт модератором.',
		'deleted' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" удалён модератором.',
		'pending' => 'Ваш релиз "'.lt_notification_text($torrentName, 140).'" отправлен на проверку.',
	);
	$message = $messages[$status] ?? 'Статус вашего релиза изменён.';
	if ($reason !== '') {
		$message .= ' Причина: '.$reason;
	}

	lt_notification_create_once(array(
		'user_id' => $ownerId,
		'actor_id' => $actorId,
		'type' => 'torrent_'.$status,
		'entity_type' => 'torrent',
		'entity_id' => $torrentId,
		'title' => $titles[$status] ?? 'Статус релиза изменён',
		'message' => $message,
		'url' => ($status === 'deleted' ? 'my.releases.php' : lt_notification_url_for_event(array('entity_type' => 'torrent', 'entity_id' => $torrentId))),
		'dedupe_key' => 'torrent_status:'.$status.':'.$torrentId.':'.date('YmdHi'),
	), 300);
}

function lt_notifications_handle_moderator_note($recipientId, $actorId, $note)
{
	$recipientId = (int) $recipientId;
	$actorId = (int) $actorId;
	if (lt_notification_should_skip($recipientId, $actorId)) {
		return;
	}

	lt_notification_create_once(array(
		'user_id' => $recipientId,
		'actor_id' => $actorId,
		'type' => 'moderator_note',
		'entity_type' => 'user',
		'entity_id' => $recipientId,
		'title' => 'Замечание модератора',
		'message' => lt_notification_text($note, 240),
		'url' => profile_href($recipientId),
		'dedupe_key' => 'moderator_note:'.$recipientId.':'.md5(lt_notification_text($note, 240)),
	), 300);
}
?>

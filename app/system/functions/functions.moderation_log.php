<?php
/*
===================================================================
LiteTracker Source
===================================================================
Moderation action log helpers
===================================================================
*/

function lt_moderation_log_ensure_schema()
{
	global $db;
	static $ready = null;

	if ($ready !== null) {
		return $ready;
	}

	$schemaCacheKey = 'schema:moderation_log:ready_v1';
	if (lt_schema_cache_get($schemaCacheKey) === true) {
		$ready = true;
		return true;
	}

	if (lt_table_exists('moderation_log')) {
		$ready = true;
		lt_schema_cache_set($schemaCacheKey, true);
		return true;
	}

	if (!lt_schema_mutations_enabled()) {
		// TODO: create moderation_log via migrations; runtime CREATE is disabled for web requests.
		$ready = false;
		return false;
	}

	$db->query(
		"CREATE TABLE IF NOT EXISTS `moderation_log` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`moderator_id` INT UNSIGNED NOT NULL,
			`action` VARCHAR(64) NOT NULL,
			`target_type` VARCHAR(64) NOT NULL,
			`target_id` INT UNSIGNED NULL,
			`old_value` TEXT NULL,
			`new_value` TEXT NULL,
			`reason` TEXT NULL,
			`ip` VARCHAR(64) NULL,
			`user_agent` VARCHAR(255) NULL,
			`created_at` DATETIME NOT NULL,
			PRIMARY KEY (`id`),
			KEY `moderator_created` (`moderator_id`, `created_at`),
			KEY `action_created` (`action`, `created_at`),
			KEY `target` (`target_type`, `target_id`),
			KEY `created_at` (`created_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		0
	);

	lt_schema_cache_delete(lt_schema_table_cache_key('moderation_log'));
	$ready = lt_table_exists('moderation_log', true);
	if ($ready) {
		lt_schema_cache_set($schemaCacheKey, true);
	}

	return $ready;
}

function lt_moderation_log_action_label($action)
{
	$action = trim((string) $action);
	$labels = array(
		'torrent_approve' => 'Одобрение раздачи',
		'torrent_need_fix' => 'Отправка на доработку',
		'torrent_reject' => 'Отклонение раздачи',
		'torrent_hide' => 'Скрытие раздачи',
		'torrent_restore' => 'Восстановление раздачи',
		'torrent_soft_delete' => 'Мягкое удаление раздачи',
	);

	return ($labels[$action] ?? $action);
}

function lt_moderation_log_target_url($targetType, $targetId)
{
	$targetType = preg_replace('~[^a-z0-9_-]+~i', '', (string) $targetType);
	$targetId = (int) $targetId;

	if ($targetType === 'torrent' && $targetId > 0) {
		return 'details.php?id='.$targetId;
	}

	if ($targetType === 'user' && $targetId > 0) {
		return profile_href($targetId);
	}

	return '';
}

function lt_moderation_log_mask_sensitive($value)
{
	if (is_array($value)) {
		$result = array();
		foreach ($value as $key => $item) {
			$keyString = strtolower((string) $key);
			if (preg_match('~(?:passkey|password|token|csrf|cookie|session|secret)~i', $keyString)) {
				$result[$key] = '[masked]';
				continue;
			}
			$result[$key] = lt_moderation_log_mask_sensitive($item);
		}
		return $result;
	}

	$value = (string) $value;
	if ($value === '') {
		return '';
	}

	$value = preg_replace('/((?:passkey|password|token|csrf|cookie|session|secret)[^=:\s]*\s*[=:]\s*)([^&\s,;]+)/i', '$1[masked]', $value);
	return (is_string($value) ? $value : '');
}

function lt_moderation_log_value($value)
{
	$value = lt_moderation_log_mask_sensitive($value);
	if (is_array($value)) {
		$json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		return (is_string($json) ? $json : '');
	}

	return (string) $value;
}

function lt_moderation_log($action, $targetType, $targetId, array $data = array())
{
	global $db, $USER;

	if (!lt_moderation_log_ensure_schema()) {
		return 0;
	}

	$moderatorId = (int) ($data['moderator_id'] ?? ($USER['id'] ?? 0));
	$action = preg_replace('~[^a-z0-9:_-]+~i', '_', trim((string) $action));
	$targetType = preg_replace('~[^a-z0-9:_-]+~i', '_', trim((string) $targetType));
	$targetId = (int) $targetId;
	if ($moderatorId <= 0 || $action === '' || $targetType === '') {
		return 0;
	}

	$oldValue = lt_moderation_log_value($data['old_value'] ?? null);
	$newValue = lt_moderation_log_value($data['new_value'] ?? null);
	$reason = lt_moderation_log_value($data['reason'] ?? null);
	$ip = trim((string) ($data['ip'] ?? (function_exists('getip') ? getip() : ($_SERVER['REMOTE_ADDR'] ?? ''))));
	$userAgent = trim((string) ($data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')));

	$db->query(
		"INSERT INTO moderation_log
			(moderator_id, action, target_type, target_id, old_value, new_value, reason, ip, user_agent, created_at)
		 VALUES (
			".$moderatorId.",
			'".$db->safesql(substr($action, 0, 64))."',
			'".$db->safesql(substr($targetType, 0, 64))."',
			".($targetId > 0 ? $targetId : 'NULL').",
			".($oldValue !== '' ? "'".$db->safesql($oldValue)."'" : 'NULL').",
			".($newValue !== '' ? "'".$db->safesql($newValue)."'" : 'NULL').",
			".($reason !== '' ? "'".$db->safesql($reason)."'" : 'NULL').",
			".($ip !== '' ? "'".$db->safesql(substr($ip, 0, 64))."'" : 'NULL').",
			".($userAgent !== '' ? "'".$db->safesql(substr(lt_moderation_log_mask_sensitive($userAgent), 0, 255))."'" : 'NULL').",
			NOW()
		 )",
		0
	);

	return (int) $db->insert_id();
}

function lt_moderation_log_fetch(array $filters = array())
{
	global $db;

	if (!lt_moderation_log_ensure_schema()) {
		return array();
	}

	$where = array();
	$action = trim((string) ($filters['action'] ?? ''));
	$moderatorId = (int) ($filters['moderator_id'] ?? 0);
	$targetType = trim((string) ($filters['target_type'] ?? ''));
	$targetId = (int) ($filters['target_id'] ?? 0);
	$dateFrom = trim((string) ($filters['date_from'] ?? ''));
	$dateTo = trim((string) ($filters['date_to'] ?? ''));
	$limit = max(1, min(200, (int) ($filters['limit'] ?? 100)));

	if ($action !== '') {
		$where[] = "ml.action = '".$db->safesql($action)."'";
	}
	if ($moderatorId > 0) {
		$where[] = 'ml.moderator_id = '.$moderatorId;
	}
	if ($targetType !== '') {
		$where[] = "ml.target_type = '".$db->safesql($targetType)."'";
	}
	if ($targetId > 0) {
		$where[] = 'ml.target_id = '.$targetId;
	}
	if ($dateFrom !== '' && preg_match('~^\d{4}-\d{2}-\d{2}$~', $dateFrom)) {
		$where[] = "ml.created_at >= '".$db->safesql($dateFrom)." 00:00:00'";
	}
	if ($dateTo !== '' && preg_match('~^\d{4}-\d{2}-\d{2}$~', $dateTo)) {
		$where[] = "ml.created_at <= '".$db->safesql($dateTo)." 23:59:59'";
	}

	$sql = $db->query(
		"SELECT ml.*, u.name AS moderator_name, u.class AS moderator_class
		 FROM moderation_log AS ml
		 LEFT JOIN users AS u ON u.id = ml.moderator_id
		 ".($where ? 'WHERE '.implode(' AND ', $where) : '')."
		 ORDER BY ml.created_at DESC, ml.id DESC
		 LIMIT ".$limit,
		0
	);

	$rows = array();
	if ($sql) {
		while ($row = $db->get_row($sql)) {
			$rows[] = $row;
		}
		$db->free($sql);
	}

	return $rows;
}
?>

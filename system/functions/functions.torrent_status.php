<?php
/*
===================================================================
LiteTracker Source
===================================================================
Release moderation helpers
===================================================================
*/

function lt_torrent_statuses()
{
	return array('pending', 'approved', 'need_fix', 'hidden', 'rejected', 'deleted');
}

function lt_torrent_status_normalize($status)
{
	$status = strtolower(trim((string) $status));
	return (in_array($status, lt_torrent_statuses(), true) ? $status : 'approved');
}

function lt_torrent_status_label($status)
{
	$labels = array(
		'pending' => 'Ожидает модерации',
		'approved' => 'Опубликовано',
		'need_fix' => 'Нужна доработка',
		'hidden' => 'Скрыто модератором',
		'rejected' => 'Отклонено',
		'deleted' => 'Удалено',
	);

	$status = lt_torrent_status_normalize($status);
	return $labels[$status];
}

function lt_torrent_status_badge($status)
{
	$status = lt_torrent_status_normalize($status);
	$classes = array(
		'pending' => 'details-badge-status-pending',
		'approved' => 'details-badge-status-approved',
		'need_fix' => 'details-badge-status-need-fix',
		'hidden' => 'details-badge-status-hidden',
		'rejected' => 'details-badge-status-rejected',
		'deleted' => 'details-badge-status-deleted',
	);

	return '<span class="details-badge '.htmlspecialchars($classes[$status], ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars(lt_torrent_status_label($status), ENT_QUOTES, 'UTF-8').'</span>';
}

function lt_torrent_status_ensure_schema()
{
	global $db;
	static $ready = null;

	if ($ready !== null) {
		return $ready;
	}

	if (!lt_table_exists('torrents')) {
		$ready = false;
		return false;
	}

	$columns = array(
		'status' => "ALTER TABLE `torrents` ADD COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'approved'",
		'status_reason' => "ALTER TABLE `torrents` ADD COLUMN `status_reason` TEXT NULL",
		'reviewed_by' => "ALTER TABLE `torrents` ADD COLUMN `reviewed_by` INT UNSIGNED NULL",
		'reviewed_at' => "ALTER TABLE `torrents` ADD COLUMN `reviewed_at` DATETIME NULL",
		'submitted_at' => "ALTER TABLE `torrents` ADD COLUMN `submitted_at` DATETIME NULL",
		'hidden_at' => "ALTER TABLE `torrents` ADD COLUMN `hidden_at` DATETIME NULL",
		'deleted_at' => "ALTER TABLE `torrents` ADD COLUMN `deleted_at` DATETIME NULL",
	);

	foreach ($columns as $column => $sql) {
		if (!lt_column_exists('torrents', $column)) {
			$db->query($sql, 0);
			lt_schema_cache_delete(lt_schema_column_cache_key('torrents', $column));
		}
	}

	if (!lt_torrent_index_exists('torrents', 'status_added')) {
		$db->query("ALTER TABLE `torrents` ADD KEY `status_added` (`status`, `added`)", 0);
	}
	if (!lt_torrent_index_exists('torrents', 'owner_status')) {
		$db->query("ALTER TABLE `torrents` ADD KEY `owner_status` (`id_user`, `status`)", 0);
	}

	$db->query("UPDATE torrents SET status = 'approved' WHERE status IS NULL OR status = ''", 0);
	$db->query("UPDATE torrents SET submitted_at = added WHERE submitted_at IS NULL", 0);

	$ready = lt_column_exists('torrents', 'status', true);
	return $ready;
}

function lt_torrent_index_exists($tableName, $indexName)
{
	global $db;

	$tableName = lt_schema_identifier($tableName);
	$indexName = trim((string) $indexName);
	if ($tableName === '' || $indexName === '') {
		return false;
	}

	$sql = $db->query("SHOW INDEX FROM `".$tableName."` WHERE Key_name = '".$db->safesql($indexName)."'", 0);
	if ($sql === false) {
		return false;
	}

	$row = $db->get_row($sql);
	$db->free($sql);

	return !empty($row);
}

function lt_torrent_owner_id($torrent)
{
	return (int) ((is_array($torrent) ? ($torrent['id_user'] ?? 0) : 0));
}

function lt_torrent_can_moderate($user = null)
{
	$priv = ($GLOBALS['PRIV'] ?? array());
	$user = ($user === null ? ($GLOBALS['USER'] ?? null) : $user);
	$user = (is_array($user) ? $user : array());
	if ($user !== ($GLOBALS['USER'] ?? null) && (int) ($GLOBALS['USER']['id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
		if (empty($user['class'])) {
			return false;
		}
		$priv = get_priv_info((int) $user['class']);
	}

	return (!empty($user['id']) && (!empty($priv['edit_release']) || !empty($priv['EDIT_PRIV'])));
}

function lt_torrent_can_auto_approve($user = null)
{
	return lt_torrent_can_moderate($user);
}

function lt_torrent_can_view($torrent, $user = null)
{
	lt_torrent_status_ensure_schema();

	$user = ($user === null ? ($GLOBALS['USER'] ?? null) : $user);
	$user = (is_array($user) ? $user : array());
	$status = lt_torrent_status_normalize($torrent['status'] ?? 'approved');

	if ($status === 'approved') {
		return true;
	}

	if (lt_torrent_can_moderate($user)) {
		return true;
	}

	$userId = (int) ($user['id'] ?? 0);
	$isOwner = ($userId > 0 && lt_torrent_owner_id($torrent) === $userId);

	return ($isOwner && in_array($status, array('pending', 'need_fix', 'rejected'), true));
}

function lt_torrent_status_filter_sql($user, $alias = 't')
{
	lt_torrent_status_ensure_schema();

	$alias = preg_replace('~[^a-z0-9_]+~i', '', (string) $alias);
	$prefix = ($alias !== '' ? $alias.'.' : '');
	$user = (is_array($user) ? $user : array());

	if (lt_torrent_can_moderate($user)) {
		return '1=1';
	}

	$userId = (int) ($user['id'] ?? 0);
	if ($userId > 0) {
		return "(".$prefix."status = 'approved' OR (".$prefix."id_user = ".$userId." AND ".$prefix."status IN ('pending','need_fix','rejected')))";
	}

	return $prefix."status = 'approved'";
}

function lt_torrent_reviewed_by_user($torrent)
{
	$userId = (int) ($torrent['reviewed_by'] ?? 0);
	return ($userId > 0 ? get_user_info($userId) : array());
}

function lt_torrent_sync_legacy_checked($torrentId, $status)
{
	global $db;

	$torrentId = (int) $torrentId;
	$status = lt_torrent_status_normalize($status);
	if ($torrentId <= 0) {
		return;
	}

	$approved = ($status === 'approved' ? 1 : 0);
	foreach (array('checked', 'moderated', 'verified') as $column) {
		if (lt_column_exists('torrents', $column)) {
			$db->query("UPDATE torrents SET `".$column."` = ".$approved." WHERE id = ".$torrentId, 0);
		}
	}

	if (lt_column_exists('torrents', 'banned')) {
		$banned = (in_array($status, array('hidden', 'deleted'), true) ? 1 : 0);
		$db->query("UPDATE torrents SET banned = '".$banned."' WHERE id = ".$torrentId, 0);
	}
}

function lt_torrent_status_notify_owner($torrent, $moderatorId, $status, $reason = '')
{
	if (!function_exists('lt_notifications_handle_torrent_status')) {
		return;
	}

	lt_notifications_handle_torrent_status(
		lt_torrent_owner_id($torrent),
		(int) $moderatorId,
		(int) ($torrent['id'] ?? 0),
		(string) ($torrent['name'] ?? ''),
		lt_torrent_status_normalize($status),
		(string) $reason
	);
}

function lt_torrent_set_status($torrentId, $status, $moderatorId, $reason = '')
{
	global $db;

	lt_torrent_status_ensure_schema();

	$torrentId = (int) $torrentId;
	$moderatorId = (int) $moderatorId;
	$status = lt_torrent_status_normalize($status);
	$reason = trim((string) $reason);

	if ($torrentId <= 0) {
		return false;
	}

	$torrent = $db->super_query("SELECT * FROM torrents WHERE id = ".$torrentId." LIMIT 1");
	if (empty($torrent['id'])) {
		return false;
	}

	$fields = array(
		"status = '".$db->safesql($status)."'",
		"status_reason = ".($reason !== '' ? "'".$db->safesql($reason)."'" : 'NULL'),
		"reviewed_by = ".($moderatorId > 0 ? $moderatorId : 'NULL'),
		"reviewed_at = ".($moderatorId > 0 ? 'NOW()' : 'NULL'),
	);

	if (empty($torrent['submitted_at']) || $torrent['submitted_at'] === '0000-00-00 00:00:00') {
		$fields[] = "submitted_at = COALESCE(NULLIF(added, '0000-00-00 00:00:00'), NOW())";
	}

	if ($status === 'hidden') {
		$fields[] = 'hidden_at = NOW()';
	} elseif ($status === 'approved') {
		$fields[] = 'hidden_at = NULL';
		$fields[] = 'deleted_at = NULL';
	}

	if ($status === 'deleted') {
		$fields[] = 'deleted_at = NOW()';
	} elseif ($status === 'approved') {
		$fields[] = 'deleted_at = NULL';
	}

	$db->query("UPDATE torrents SET ".implode(', ', $fields)." WHERE id = ".$torrentId, 0);
	lt_torrent_sync_legacy_checked($torrentId, $status);
	// TODO: write moderation_log here if/when the project adds that table.

	if ((string) ($torrent['status'] ?? 'approved') !== $status && $moderatorId > 0) {
		$torrent['status'] = $status;
		lt_torrent_status_notify_owner($torrent, $moderatorId, $status, $reason);
	}

	return true;
}

function lt_torrent_submit_for_review($torrentId, $clearReason = true)
{
	global $db;

	lt_torrent_status_ensure_schema();
	$torrentId = (int) $torrentId;
	if ($torrentId <= 0) {
		return false;
	}

	$db->query(
		"UPDATE torrents
		 SET status = 'pending',
		     status_reason = ".($clearReason ? 'NULL' : 'status_reason').",
		     reviewed_by = NULL,
		     reviewed_at = NULL,
		     submitted_at = NOW(),
		     hidden_at = NULL,
		     deleted_at = NULL
		 WHERE id = ".$torrentId,
		0
	);
	lt_torrent_sync_legacy_checked($torrentId, 'pending');

	return true;
}
?>

<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API архивации уведомлений
===================================================================
*/

api_require_post();
api_require_login();
api_require_csrf('notifications_action');
api_rate_limit('notifications_archive', 60, 60);

$ids = $_POST['ids'] ?? array();
if (!is_array($ids)) {
	$ids = explode(',', (string) $ids);
}

$archived = lt_notifications_archive((int) $USER['id'], $ids);
api_json_success(array(
	'archived' => $archived,
	'unread_count' => lt_notifications_unread_count((int) $USER['id']),
));
?>

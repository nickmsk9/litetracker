<?php
require_once LT_SYSTEM_PATH . '/functions/functions.notifications.php';
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API отметки уведомлений прочитанными
===================================================================
*/

api_require_post();
api_require_login();
api_require_csrf('notifications_action');
api_rate_limit('notifications_mark_read', 60, 60);

$ids = $_POST['ids'] ?? array();
if (!is_array($ids)) {
	$ids = explode(',', (string) $ids);
}

$marked = lt_notifications_mark_read((int) $USER['id'], $ids);
api_json_success(array(
	'marked' => $marked,
	'unread_count' => lt_notifications_unread_count((int) $USER['id']),
));
?>

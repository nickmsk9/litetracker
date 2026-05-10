<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API отметки всех уведомлений прочитанными
===================================================================
*/

require __DIR__.'/../../system/init.php';

api_require_post();
api_require_login();
api_require_csrf('notifications_action');
api_rate_limit('notifications_mark_all_read', 30, 60);

$marked = lt_notifications_mark_all_read((int) $USER['id']);
api_json_success(array(
	'marked' => $marked,
	'unread_count' => lt_notifications_unread_count((int) $USER['id']),
));
?>

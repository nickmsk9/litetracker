<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API количества непрочитанных уведомлений
===================================================================
*/

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
	header('Allow: GET');
	api_json_error('Метод запроса не поддерживается.', 405);
}

api_require_login();
api_rate_limit('notifications_count', 120, 60);

api_json_success(array(
	'unread_count' => lt_notifications_unread_count((int) $USER['id']),
));
?>

<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API списка уведомлений
===================================================================
*/

require __DIR__.'/../../system/init.php';

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
	header('Allow: GET');
	api_json_error('Метод запроса не поддерживается.', 405);
}

api_require_login();
api_rate_limit('notifications_list', 60, 60);

$filter = trim((string) ($_GET['filter'] ?? 'all'));
$limit = max(1, min(20, (int) ($_GET['limit'] ?? 10)));
$cursor = max(0, (int) ($_GET['cursor'] ?? 0));
$result = lt_notifications_fetch((int) $USER['id'], array(
	'limit' => $limit,
	'cursor' => $cursor,
	'unread_only' => ($filter === 'unread'),
));

api_json_success(array(
	'items' => $result['items'],
	'next_cursor' => $result['next_cursor'],
	'unread_count' => lt_notifications_unread_count((int) $USER['id']),
	'csrf_token' => lt_csrf_token('notifications_action'),
));
?>

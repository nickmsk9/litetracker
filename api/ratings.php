<?php
/*
===================================================================
LiteTracker Source
-------------------------------------------------------------------
Назначение: JSON API для оценки торрентов
===================================================================
*/

require __DIR__.'/../system/init.php';
require_once __DIR__.'/../system/functions/functions.details.php';

api_require_post();
api_require_login();
api_rate_limit('rating_torrent', 20, 60);

$torrentId = lt_post_int('torrent_id');
$ratingValue = lt_post_int('rating');

if ($torrentId <= 0) {
	api_json_error('Некорректный торрент.', 400);
}

api_require_csrf('rating_torrent_'.$torrentId);

if ($ratingValue < 1 || $ratingValue > 5) {
	api_json_error('Некорректная оценка.', 400);
}

$torrent = lt_details_load_torrent($torrentId);
if (empty($torrent['id'])) {
	api_json_error('Торрент не найден.', 404);
}

$isOwner = (!empty($USER['id']) && (int) ($torrent['id_user'] ?? 0) === (int) $USER['id']);
if (empty($PRIV['details_view']) && !$isOwner) {
	api_json_error('Вам запрещено просматривать детали.', 403);
}

if (!empty($torrent['banned']) && empty($PRIV['details_banned_view'])) {
	api_json_error('Торрент заблокирован.', 403);
}

$stats = lt_details_save_rating($torrentId, (int) $USER['id'], $ratingValue);
if ($stats === false) {
	api_json_error('Голосование временно недоступно.', 503);
}

api_json_success(array(
	'message' => 'Рейтинг сохранён.',
	'rating_avg' => (float) $stats['rating_avg'],
	'rating_count' => (int) $stats['rating_count'],
	'user_rating' => (int) $stats['user_rating'],
));

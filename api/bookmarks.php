<?php
require_once __DIR__.'/../system/init.php';

header('Content-Type: application/json; charset=UTF-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
	http_response_code(405);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Метод не разрешён.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

if (empty($USER['id'])) {
	http_response_code(401);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Требуется авторизация.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

if (!lt_csrf_validate('bookmarks_action')) {
	http_response_code(403);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Ошибка безопасности. Обновите страницу и попробуйте снова.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$torrentId = (int) ($_POST['torrent_id'] ?? 0);
$userId = (int) ($USER['id'] ?? 0);

if ($torrentId <= 0) {
	http_response_code(400);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Некорректный ID торрента.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

if (!in_array($action, array('add', 'delete', 'toggle'), true)) {
	http_response_code(400);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Некорректное действие.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

$torrentRow = $db->psuper_query("SELECT id FROM torrents WHERE id = ? LIMIT 1", 'i', array($torrentId));
if (!$torrentRow) {
	http_response_code(404);
	echo json_encode(array(
		'ok' => false,
		'message' => 'Торрент не найден.',
	), JSON_UNESCAPED_UNICODE);
	die();
}

$bookmarkRow = $db->psuper_query("SELECT id FROM books WHERE id_torrent = ? AND id_user = ? LIMIT 1", 'ii', array($torrentId, $userId));
$isBookmarked = !empty($bookmarkRow['id']);

if ($action === 'toggle') {
	$action = ($isBookmarked ? 'delete' : 'add');
}

if ($action === 'add') {
	if (!$isBookmarked) {
		$db->pquery("INSERT INTO books (id_torrent, id_user, date) VALUES (?, ?, NOW())", 'ii', array($torrentId, $userId));
		$isBookmarked = true;
	}

	echo json_encode(array(
		'ok' => true,
		'bookmarked' => true,
		'message' => 'Торрент добавлен в закладки.',
		'label' => ($language['details_26'] ?? 'Удалить из закладок'),
	), JSON_UNESCAPED_UNICODE);
	die();
}

if ($isBookmarked) {
	$db->pquery("DELETE FROM books WHERE id_torrent = ? AND id_user = ? LIMIT 1", 'ii', array($torrentId, $userId));
}

echo json_encode(array(
	'ok' => true,
	'bookmarked' => false,
	'message' => 'Торрент удалён из закладок.',
	'label' => ($language['details_25'] ?? 'В закладки'),
), JSON_UNESCAPED_UNICODE);
die();

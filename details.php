<?php
/*
===================================================================
Назначение: Просмотр релиза
===================================================================
*/

//Подключаем главный системный файл
require __DIR__ . '/app/system/init.php';
require_once __DIR__.'/app/system/functions/functions.upload.php';
require_once __DIR__.'/app/system/functions/functions.comments.php';
require_once __DIR__.'/app/system/functions/functions.details.php';

$GLOBALS['LITETRACKER_HIDE_TOP_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_BOTTOM_BLOCKS'] = true;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

//Номер торрента
$id = lt_get_int('id');
$detailsModel = lt_details_view_model($id, (int) ($USER['id'] ?? 0));
$arr = $detailsModel['torrent'];
lt_details_check_access($arr);

$detailsRating = $detailsModel['rating'];
lt_details_handle_rating_request((int) $arr['id'], $detailsRating);

if (isset($_GET['files'])) {
	lt_details_render_files_page((int) $arr['id']);
}

if (isset($_GET['peers'])) {
	lt_details_render_peers_page((int) $arr['id']);
}

if (isset($_GET['trackers']) && !empty($arr['multi'])) {
	lt_details_render_trackers_page((int) $arr['id']);
}

$detailsViewModel = lt_details_prepare_view_model($arr, $detailsRating);
extract($detailsViewModel, EXTR_SKIP);
$GLOBALS['LITETRACKER_ENABLE_PHOTOSWIPE'] = !empty($screens);

//Заголовок
head($torrent_name_plain);

//Выводим статусы
comment_status();

//Редактирование
if(!empty($_GET['edit']) && $_GET['edit'] == '1') {
	msg($language['details_24']);
}

if (!empty($_GET['moderation']) && $_GET['moderation'] === 'pending') {
	msg('Модерация', 'Раздача отправлена на модерацию. Она станет доступна после проверки.');
}

//Подключаем шаблон
require lt_templates_path($config['template'].'/tpl.details.php');

//Подвал
stdfoot();
?>

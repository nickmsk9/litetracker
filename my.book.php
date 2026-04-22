<?php
/*
Назначение: Мои закладки
*/

require 'system/init.php';
is_login();

$ltBookmarkAjax = (!empty($_GET['ajax']) || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');

function lt_bookmark_json($payload, $statusCode = 200)
{
	header('Content-Type: application/json; charset=UTF-8', true, (int) $statusCode);
	echo json_encode($payload);
	die();
}

// безопасный act (фикс warning)
$act = isset($_GET['act']) ? trim((string) $_GET['act']) : '';

//////////////////////////////////////////////////////////////////////////////
// Массовое удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'check_delete') {

	$array = $_POST['check'] ?? [];

	if(!count($array) || !is_array($array)) {
		err($language['default_1'] , $language['books_5'] , 1);
	}

	$ids = array();
	foreach($array AS $id) {
		$ids[] = (int)$id;
	}

	$i = 0;
	foreach($ids AS $id) {

		$db->query("SELECT * FROM torrents WHERE id=".$id);
		if(!$db->num_rows()) continue;

		$db->query("SELECT * FROM books WHERE id_torrent=".$id);
		if(!$db->num_rows()) continue;

		$db->query("DELETE FROM books WHERE id_torrent=".$id);
		$i++;
	}

	head('Удаление закладок');
	begin_frame('Удаление закладок');
	msg(sprintf($language['books_6'] , $i , count($ids)) , '<a href="javascript:history.go(-1)">'.$language['books_7'].'</a>');
	end_frame();
	foot();
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Добавление
//////////////////////////////////////////////////////////////////////////////
if($act === 'add') {

	$id = (int)$_GET['id'];

	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id);
	if(!$count_t['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['download_1']), 404);
		}
		err($language['default_1'] , $language['download_1'] , 1);
	}

	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);
	if($count_b['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['books_1']), 409);
		}
		err($language['default_1'] , $language['books_1'] , 1);
	}

	$db->query("INSERT INTO books(id_torrent , id_user , date ) VALUES (".$id." , ".$USER['id']." , NOW() )");

	if ($ltBookmarkAjax) {
		lt_bookmark_json(array(
			'success' => true,
			'bookmarked' => true,
			'label' => $language['details_26'],
			'href' => 'my.book.php?id='.$id.'&act=delete',
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Удаление
//////////////////////////////////////////////////////////////////////////////
if($act === 'delete') {

	$id = (int)$_GET['id'];

	$count_t = $db->super_query("SELECT COUNT(*) AS count FROM torrents WHERE id=".$id);
	if(!$count_t['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['download_1']), 404);
		}
		err($language['default_1'] , $language['download_1'] , 1);
	}

	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);
	if(!$count_b['count']) {
		if ($ltBookmarkAjax) {
			lt_bookmark_json(array('success' => false, 'message' => $language['books_2']), 409);
		}
		err($language['default_1'] , $language['books_2'] , 1);
	}

	$db->query("DELETE FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']);

	if ($ltBookmarkAjax) {
		lt_bookmark_json(array(
			'success' => true,
			'bookmarked' => false,
			'label' => $language['details_25'],
			'href' => 'my.book.php?id='.$id.'&act=add',
		));
	}

	header("Location:details.php?id=".$id);
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Вывод
//////////////////////////////////////////////////////////////////////////////

head($language['books_3']);

echo '
<style>
.bookmarks-page{
	max-width:1360px;
	margin:16px auto 0 auto;
	padding:0 20px;
	box-sizing:border-box;
}
.bookmarks-block{
	background:#fff;
	border-radius:4px;
	padding:22px 20px;
	margin-bottom:20px;
	box-sizing:border-box;
}
.bookmarks-title{
	font-size:30px;
	line-height:1.2;
	font-weight:700;
	color:#111;
}
.bookmarks-empty{
	min-height:26px;
	display:flex;
	align-items:center;
	justify-content:center;
	font-size:24px;
	line-height:1.35;
	font-weight:400;
	color:#111;
	text-align:center;
}
.bookmarks-table-wrap{
	background:#fff;
	border-radius:4px;
	padding:18px;
	margin-bottom:20px;
	box-sizing:border-box;
}
</style>

<div class="bookmarks-page">

<div class="bookmarks-block">
	<div class="bookmarks-title">Закладки</div>
</div>
';

// пагинация
$db->query("
	SELECT t.*
	FROM books b
	LEFT JOIN torrents t ON b.id_torrent = t.id
	WHERE b.id_user=".$USER['id']."
	GROUP BY t.id
");

$count = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('10', $count, 'my.book.php?');

// основной запрос
$sql = $db->query("
	SELECT t.* , COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.seeders ELSE 0 END), 0) AS seeders , COALESCE(SUM(CASE WHEN tr.tracker = 'localhost' THEN tr.leechers ELSE 0 END), 0) AS leechers
	FROM books b
	LEFT JOIN torrents t ON b.id_torrent = t.id
	LEFT JOIN trackers tr ON tr.torrent = t.id
	WHERE b.id_user=".$USER['id']."
	GROUP BY t.id
	ORDER BY t.added DESC
	".$limit."
");

if($db->num_rows($sql) > 0) {

	echo '<div class="bookmarks-table-wrap">';
	echo $pagertop;

	?>
	<link href="public/css/torrenttable.css" rel="stylesheet">

	<form action="my.book.php?act=check_delete" method="post">
	<table width="100%" class="tt">
	<tr>
		<td class="tt">Тип</td>
		<td class="tt">Имя</td>
		<td class="tt">Размер</td>
		<td class="tt">Сидеры</td>
		<td class="tt">Личеры</td>
		<td class="tt">Файлов</td>
		<td class="tt">Скачан</td>
		<td class="tt"><input type="submit" value="Удалить"></td>
	</tr>

	<?php
	while($arr = $db->get_row($sql)) {
		require 'modules/releases.arr.php';
	}
	?>

	</table>
	</form>

	<?php

	echo $pagerbottom;
	echo '</div>';

} else {

	echo '
	<div class="bookmarks-block">
		<div class="bookmarks-empty">Торрентов не найдено</div>
	</div>
	';
}

echo '</div>';

foot();
?>

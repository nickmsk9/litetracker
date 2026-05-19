<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Новости
===================================================================
*/

//Подключаем главный системный файл
require __DIR__ . '/app/system/init.php';
require_once __DIR__ . '/app/system/functions/functions.comments.php';

$act = (string) ($_GET['act'] ?? '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$status = (string) ($_GET['status'] ?? '');
$newsAddScope = 'news_add';
$newsEditScope = 'news_edit_'.$id;
$newsDeleteScope = 'news_delete_'.$id;
$GLOBALS['LITETRACKER_HIDE_STANDARD_SIDEBAR'] = true;

function lt_news_format_publication_date($date)
{
	return lt_format_date_label_with_year($date);
}

function lt_news_require_manage_permission()
{
	global $language;

	if (!lt_user_can_manage_news()) {
		err($language['default_1'], $language['default_10'], 1);
	}
}

function lt_news_invalidate_cache()
{
	lt_cache_invalidate_news();
}




////////////////////////////////////////////////////////////////////
//Редактирование новости
////////////////////////////////////////////////////////////////////
if($act == 'edit' && $id) {

	//Только Администраторам , Модераторам
	lt_news_require_manage_permission();

	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}
	$arr = $db->get_row();

	//Обработка новости
	if(count($_POST) ) {
		if (!lt_csrf_validate($newsEditScope)) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		$update = array();
		$updateParams = array();
		$updateTypes = '';

		//Название
		$name = lt_fix_utf8_mojibake(trim((string) ($_POST['name'] ?? '')));
		if($arr['name'] != $name) {
			if(empty($name) ) {
				err($language['default_1'] , $language['news_2'] , 1);
			}
			$update[] = 'name=?'; $updateParams[] = $name; $updateTypes .= 's';
		}


		//Описание
		$text = lt_fix_utf8_mojibake((string) ($_POST['text'] ?? ''));
		if($arr['text'] != $text) {
			if(empty($text) ) {
				err($language['default_1'] , $language['news_3'] , 1);
			}
			$update[] = 'text=?'; $updateParams[] = $text; $updateTypes .= 's';
		}

		//Поднятие новости
		$up = (int) ($_POST['up'] ?? 0);
		if($up) {
			$update[] = "date=NOW()";
		}
		//Обновляем новость
		if(count($update)) {
			$updateParams[] = (int) $id; $updateTypes .= 'i';
			$db->pquery("UPDATE news SET ".implode(',' , $update)." WHERE id=?", $updateTypes, $updateParams);
		}

		//Удаляем старый кеш
		lt_news_invalidate_cache();
		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_4']);
	begin_frame($language['news_4']);
	?>
	<form enctype="multipart/form-data" action="news.php?act=edit&id=<?=$id;?>" method="post" name="news" class="news-editor-form">
		<?=lt_csrf_input($newsEditScope);?>
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['text']), ENT_QUOTES, 'UTF-8');?></textarea>
			</label>

			<label class="news-editor-checkbox">
				<input type="checkbox" name="up" value="1">
				<span><?=$language['news_7'];?></span>
			</label>

			<div class="news-editor-actions">
				<input class="news-editor-submit" type="submit" value="<?=$language['news_8'];?>">
			</div>
		</div>
	</form>
	<?php
	end_frame();
	foot();
	die();
}



////////////////////////////////////////////////////////////////////
//Добавление новости
////////////////////////////////////////////////////////////////////
if($act == 'add') {

	//Только Администраторам , Модераторам
	lt_news_require_manage_permission();


	//Обработка новости
	if(count($_POST) ) {
		if (!lt_csrf_validate($newsAddScope)) {
			err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
		}

		//Название
		$name = lt_fix_utf8_mojibake(trim((string) ($_POST['name'] ?? '')));
		if(empty($name) ) {
			err($language['default_1'] , $language['news_2'] , 1);
		}

		//Описание
		$text = lt_fix_utf8_mojibake((string) ($_POST['text'] ?? ''));
		if(empty($text) ) {
			err($language['default_1'] , $language['news_3'] , 1);
		}

		//Добавляем новость
		$db->pquery("INSERT INTO news (name , text , id_user , date) VALUES (?, ?, ?, NOW())", 'ssi', [$name, $text, (int) $USER['id']]);
		$id = $db->insert_id();


		//Удаляем старый кеш
		lt_news_invalidate_cache();
		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_9']);
	begin_frame($language['news_9']);
	?>
	<form enctype="multipart/form-data" action="news.php?act=add" method="post" name="news" class="news-editor-form">
		<?=lt_csrf_input($newsAddScope);?>
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars(lt_fix_utf8_mojibake((string) ($_POST['name'] ?? '')), ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars(lt_fix_utf8_mojibake((string) ($_POST['text'] ?? '')), ENT_QUOTES, 'UTF-8');?></textarea>
			</label>

			<div class="news-editor-actions">
				<input class="news-editor-submit" type="submit" value="<?=$language['news_10'];?>">
			</div>
		</div>
	</form>
	<?php
	end_frame();
	foot();
	die();
}


////////////////////////////////////////////////////////////////////
//Удаление новости
////////////////////////////////////////////////////////////////////
if($act == 'delete' && $id) {


	//Только Администраторам , Модераторам
	lt_news_require_manage_permission();

	if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
		err($language['default_1'], 'Удаление новости доступно только POST-запросом.', 1);
	}

	if (!lt_csrf_validate($newsDeleteScope)) {
		err($language['default_1'], 'Защитный токен устарел. Обновите страницу и попробуйте снова.', 1);
	}

	//Проверяем , существует ли новость
	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}

	//Удаляем новость
	$db->query("DELETE FROM news WHERE id=".$id."");


	//Удаляем старый кеш
	lt_news_invalidate_cache();
	header("Location:news.php?status=1");
	die();
}




////////////////////////////////////////////////////////////////////
//Просмотр новости
////////////////////////////////////////////////////////////////////
if($id && $act == '') {


	$db->query("SELECT *
				FROM news
				WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}
	$arr = $db->get_row();


	//Номер новости
	$id = $arr['id'];


	//Название новости
	$name = htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');

	//Текст новости
	$text = cleanhtml(lt_fix_utf8_mojibake((string) $arr['text']));

	//Дата добавления
	$date = convent_date($arr['date']);
	$published_at = lt_news_format_publication_date($arr['date']);

	/////////////////////////////////////////////////////////
	//Пользователь
	/////////////////////////////////////////////////////////
	$user = get_user_info($arr['id_user']);
	//ID пользователя
	$user_id = $user['id'];
	//Имя пользователя
	$user_name = $user['name'];
	//Класс пользователя
	$user_class = $user['class'];

	//Определяем  , что это детали новости
	define('NEWS_DETAILS' , true);

	head($name);

	//Выводим статусы
	comment_status();

	//Подключаем шаблон
	require lt_templates_path($config['template'].'/tpl.news.php');

	stdfoot();
	die();
}


////////////////////////////////////////////////////////////////////
//Все новости
////////////////////////////////////////////////////////////////////

//Заголовок
head($language['news_11']);

//Статусы
if($status == '1') {
	msg($language['news_12']);
}

$db->query("SELECT id, name, text, date FROM news ORDER BY date DESC");
?>
<div class="news-archive-page">
	<div class="news-archive-head">
		<h1 class="news-archive-page-title">Новости</h1>
		<?php if (!empty($PRIV['news_add'])) { ?>
		<a class="news-archive-manage-link" href="news.php?act=add"><?=$language['news_9'];?></a>
		<?php } ?>
	</div>

	<?php if(!$db->num_rows() ) { ?>
	<div class="news-empty-state"><div class="profile-empty-state"><?=$language['news_13'];?></div></div>
	<?php } else { ?>
	<div class="news-archive-list">
		<?php while($arr = $db->get_row() ) { ?>
		<?php
		$title = htmlspecialchars(lt_fix_utf8_mojibake((string) $arr['name']), ENT_QUOTES, 'UTF-8');
		$excerpt = trim(preg_replace('~\s+~u', ' ', strip_tags(cleanhtml(lt_fix_utf8_mojibake((string) $arr['text'])))));
		?>
		<article class="news-archive-item">
			<a class="news-archive-link" href="news.php?id=<?=(int) $arr['id'];?>">
				<h2 class="news-archive-title"><?=$title;?></h2>
				<div class="news-archive-date"><?=convent_date($arr['date']);?></div>
				<?php if ($excerpt !== '') { ?>
				<p class="news-archive-excerpt"><?=htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8');?></p>
				<?php } ?>
			</a>
		</article>
		<?php } ?>
	</div>
	<?php } ?>
</div>
<?php

//Подвал
stdfoot();


?>

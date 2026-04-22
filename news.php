<?
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
require 'system/init.php';

$act = (string) ($_GET['act'] ?? '');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$status = (string) ($_GET['status'] ?? '');

function lt_news_format_publication_date($date)
{
	global $language;

	$date = trim((string) $date);
	if ($date === '' || strpos($date, ' ') === false) {
		return convent_date($date);
	}

	$months = array(
		'01' => $language['month_1'],
		'02' => $language['month_2'],
		'03' => $language['month_3'],
		'04' => $language['month_4'],
		'05' => $language['month_5'],
		'06' => $language['month_6'],
		'07' => $language['month_7'],
		'08' => $language['month_8'],
		'09' => $language['month_9'],
		'10' => $language['month_10'],
		'11' => $language['month_11'],
		'12' => $language['month_12'],
	);

	list($datePart, $timePart) = explode(' ', $date, 2);
	$explodeDate = explode('-', $datePart);
	$explodeTime = explode(':', $timePart);

	if (count($explodeDate) !== 3 || count($explodeTime) < 2) {
		return convent_date($date);
	}

	$day = (int) $explodeDate[2];
	$month = ($months[$explodeDate[1]] ?? $explodeDate[1]);
	$year = (int) $explodeDate[0];
	$hour = (int) $explodeTime[0];
	$minute = str_pad((string) ((int) $explodeTime[1]), 2, '0', STR_PAD_LEFT);

	return $day.' '.$month.' '.$year.' в '.$hour.':'.$minute;
}




////////////////////////////////////////////////////////////////////
//Редактирование новости
////////////////////////////////////////////////////////////////////
if($act == 'edit' && $id) {

	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}

	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}
	$arr = $db->get_row();

	//Обработка новости
	if(count($_POST) ) {
		$update = array();

		//Название
		$name = trim($_POST['name']);
		if($arr['name'] != $name) {
			if(empty($name) ) {
				err($language['default_1'] , $language['news_2'] , 1);
			}
			$update[] = "name='".$db->safesql($name)."'";
		}


		//Описание
		$text = $_POST['text'];
		if($arr['text'] != $text) {
			if(empty($text) ) {
				err($language['default_1'] , $language['news_3'] , 1);
			}
			$update[] = "text='".$db->safesql($text)."'";
		}

		//Поднятие новости
		$up = (int)$_POST['up'];
		if($up) {
			$update[] = "date=NOW()";
		}
		//Обновляем новость
		if(count($update)) {
			$db->query("UPDATE news SET ".implode(',' , $update)." WHERE id=".$id."");
		}

		//Удаляем старый кеш
		$memcached->delete('news');
		$memcached->delete('sidebar_news_all');
		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_4']);
	begin_frame($language['news_4']);
	?>
	<form enctype="multipart/form-data" action="news.php?act=edit&id=<?=$id;?>" method="post" name="news" class="news-editor-form">
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars((string) $arr['name'], ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars((string) $arr['text'], ENT_QUOTES, 'UTF-8');?></textarea>
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
	<?
	end_frame();
	foot();
	die();
}



////////////////////////////////////////////////////////////////////
//Добавление новости
////////////////////////////////////////////////////////////////////
if($act == 'add') {

	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}


	//Обработка новости
	if(count($_POST) ) {
		//Название
		$name = trim($_POST['name']);
		if(empty($name) ) {
			err($language['default_1'] , $language['news_2'] , 1);
		}

		//Описание
		$text = $_POST['text'];
		if(empty($text) ) {
			err($language['default_1'] , $language['news_3'] , 1);
		}

		//Добавляем новость
		$db->query("INSERT INTO news (name , text , id_user , date) VALUES ('".$db->safesql($name)."' , '".$db->safesql($text)."' , '".$USER['id']."' , NOW())");
		$id = $db->insert_id();


		//Удаляем старый кеш
		$memcached->delete('news');
		$memcached->delete('sidebar_news_all');
		header("Location:news.php?id=".$id."");
		die();
	}

	head($language['news_9']);
	begin_frame($language['news_9']);
	?>
	<form enctype="multipart/form-data" action="news.php?act=add" method="post" name="news" class="news-editor-form">
		<div class="news-editor-grid">
			<label class="news-editor-field">
				<span class="news-editor-label"><?=$language['news_5'];?>:</span>
				<input class="news-editor-input" type="text" name="name" value="<?=htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');?>">
			</label>

			<label class="news-editor-field news-editor-field-full">
				<span class="news-editor-label">Текст новости:</span>
				<textarea class="news-editor-textarea" name="text"><?=htmlspecialchars((string) ($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');?></textarea>
			</label>

			<div class="news-editor-actions">
				<input class="news-editor-submit" type="submit" value="<?=$language['news_10'];?>">
			</div>
		</div>
	</form>
	<?
	end_frame();
	foot();
	die();
}


////////////////////////////////////////////////////////////////////
//Удаление новости
////////////////////////////////////////////////////////////////////
if($act == 'delete' && $id) {


	//Только Администраторам , Модераторам
	if(!$PRIV['news_add']) {
		err($language['default_1'] , $language['default_10'] , 1);
	}

	//Проверяем , существует ли новость
	$db->query("SELECT * FROM news WHERE id=".$id."");
	if(!$db->num_rows() ) {
		err($language['default_1'] , $language['news_1']);
	}

	//Удаляем новость
	$db->query("DELETE FROM news WHERE id=".$id."");


	//Удаляем старый кеш
	$memcached->delete('news');
	$memcached->delete('sidebar_news_all');
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
	$name = htmlspecialchars($arr['name']);

	//Текст новости
	$text = cleanhtml($arr['text']);

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
	require 'templates/'.$config['template'].'/tpl.news.php';

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
if(!$db->num_rows() ) {
	msg($language['default_8'] , $language['news_13']);

}else {
	?>
	<div class="news-archive-page">
		<div class="news-archive-head">
			<h1 class="news-archive-page-title">Новости</h1>
			<?php if (!empty($PRIV['news_add'])) { ?>
			<a class="news-archive-manage-link" href="news.php?act=add"><?=$language['news_9'];?></a>
			<?php } ?>
		</div>

		<div class="news-archive-list">
			<?php while($arr = $db->get_row() ) { ?>
			<?php
			$title = htmlspecialchars((string) $arr['name'], ENT_QUOTES, 'UTF-8');
			$excerpt = trim(preg_replace('~\s+~u', ' ', strip_tags(cleanhtml((string) $arr['text']))));
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
	</div>
	<?php
}

//Подвал
stdfoot();


?>

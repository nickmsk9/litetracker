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
	<form enctype="multipart/form-data" action="news.php?act=edit&id=<?=$id;?>" method="post" name="news">

	<!--Файлы-->
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r" width="1%">
		 <span class="grey"><?=$language['news_5'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="name" style="margin: 0px;" size="50%" class="inputText" value="<?=htmlspecialchars($arr['name']);?>">
		</td><td>
	   </td></tr>
		<tr>

		<td style="padding: 0px;" colspan="2">
		 <?=textbb('text' , $arr['text'],  '100%' , '300');?>
		</td><td>
	   </td></tr>
	   <tr>
		<td class="ta_r" valign="top">
		 <span class="grey" ></span>
		</td>
		<td style="padding: 0px;">
			<input type="checkbox" name="up" value="1"> <?=$language['news_7'];?>
		</td><td>
	   </td></tr>

	   	<tr>
		<td class="ta_r">
		 <span class="grey"></span>
		</td>
		<td style="padding: 0px;">
		 <input type="submit" value="<?=$language['news_8'];?>">
		</td><td>
	   </td></tr>




	</tbody>
	</table>


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
	<form enctype="multipart/form-data" action="news.php?act=add" method="post" name="news">

	<!--Файлы-->
	<table width="80%"  cellspacing="7" cellpadding="0" border="0"  align="center">
	<tbody>


	<tr>
		<td class="ta_r"  width="1%">
		 <span class="grey"><?=$language['news_5'];?>:</span>
		</td>
		<td style="padding: 0px;">
		 <input type="text" name="name" style="margin: 0px;" size="50%" class="inputText">
		</td><td>
	   </td></tr>
		<tr>

		<td style="padding: 0px;" colspan="2">
		 <?=textbb('text' , $_POST['text'] ?? '',  '100%' , '300');?>
		</td><td>
	   </td></tr>

	   	<tr>

		<td style="padding: 0px;" colspan="2">
		 <input type="submit" value="<?=$language['news_10'];?>">
		</td><td>
	   </td></tr>




	</tbody>
	</table>


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

	//Аватар пользователя
	$avatar = ($user['avatar'] ? '<img src="public/avatars/'.$user['avatar'].'" width="50">' : '<img src="public/images/default_avatar.gif" width="50">');

	//Дополнительные поля
	$field = '<input type="button" value="К новостям" onClick="window.location.href=\'news.php\'">';

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



//Постраничная навигация
$db->query("SELECT * FROM news" , 1);

$count_news = $db->num_rows();
list($pagertop, $pagerbottom, $limit) = pager('10', $count_news, 'news.php?');

$db->query("SELECT n.* , u.name AS user_name , u.class AS user_class  , u.avatar
				FROM news AS n
				LEFT JOIN users AS u ON u.id = n.id_user
				ORDER BY n.date DESC
				".$limit."");
if(!$db->num_rows() ) {
	msg($language['default_8'] , $language['news_13']);

}else {

	echo $pagertop;
	while($arr = $db->get_row() ) {

		//Номер новости
		$id = $arr['id'];


		//Название новости
		$name = htmlspecialchars($arr['name']);

		//Текст новости
		$text = cleanhtml($arr['text']);

		//Дата добавления
		$date = convent_date($arr['date']);

		//Имя пользователя
		$user_name = $arr['user_name'];

		//Класс пользователя
		$user_class = $arr['user_class'];

		//Номер пользователя
		$user_id = $arr['id_user'];

		//Аватар пользователя
		$avatar = ($arr['avatar'] ? '<img src="public/avatars/'.$arr['avatar'].'" width="50">' : '<img src="public/images/default_avatar.gif" width="50">');


		//Дополнительные поля
		$field = '<input type="button" value="'.$language['news_18'].'" onClick="window.location.href=\'news.php?id='.$id.'\'">';


		//Подключаем шаблон
		require 'templates/'.$config['template'].'/tpl.news.php';
	}
	echo $pagertop;

}

//Подвал
stdfoot();


?>

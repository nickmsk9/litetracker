<?php
/*
===================================================================
LiteTracker Source
===================================================================
by jenaDI
-------------------------------------------------------------------
Назначение: Просмотр релиза
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';


if(!$PRIV['details_view']) {
	err($language['default_1'] , $language['details_29'] , 1);
}

//Номер торрента
$id = (int)$_GET['id'];



//Запрос к таблице torrents
$db->query("SELECT t.* , SUM(tr.seeders) AS seeders , SUM(tr.leechers) AS leechers , t.multi, 
			IF((SELECT SUM(seeders) FROM trackers WHERE torrent = t.id AND tracker='localhost' GROUP BY tracker) > 0 , true , false) AS local_seeders
			FROM torrents AS t			
			LEFT JOIN trackers AS tr ON  tr.torrent = t.id				
			WHERE t.id = '".$id."'
			GROUP BY t.id 
			");	
			
			
if(!$db->num_rows() ) {	
	err($language['default_1'] , $language['details_19'] , 1);
}		
	
$arr = $db->get_row();

if($arr['banned'] && !$PRIV['details_banned_view']) {
	err($language['default_1'] , $language['details_20'] , 1);
}


/////////////////////////////////////////////////////////
//Информация о файлах
/////////////////////////////////////////////////////////
if(isset($_GET['files'])) {
	
	$sql  = $db->query("SELECT * FROM files WHERE id_torrent  = ".$id." ORDER BY id");
	if(!$db->num_rows($sql)) { 
		err("Ошибка" , "Извините , но наша система не нашла файлов" , 1);
	}
	
	head('Информация о файлах' , true);
	msg('Здесь показаны все файлы  , которые были найдены в торренте' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	begin_frame('Информация о файлах');
	
	//Перебираем в цикле
	echo '<table width="50%" align="center">';
	echo '<tr>
	<td width="10%"><b>Файл</b></td>
	<td ><b>Размер</b></td>
	</tr>';
	while($row = $db->get_row($sql) ) {
		echo '<tr>
			<td>'.htmlspecialchars($row['filename'], ENT_QUOTES, 'UTF-8').'</td><td><b>'.mksize($row['size']).'</b></td>
		</tr>';
	}
	echo '</table>';
	
	end_frame();
	foot();
	die();
}

/////////////////////////////////////////////////////////
//Информация о пирах
/////////////////////////////////////////////////////////
if(isset($_GET['peers']) ) {
	$sql = $db->query("SELECT * FROM peers  WHERE torrent=".$id." ORDER BY seeder DESC");
	if(!$db->num_rows($sql) ) {
		err($language['default_1']  , 'Никаких соединений не обнаружено' , 1);
	}
	
	head('Информация о соединениях');
	begin_frame('Информация о соединениях');
	msg('Здесь показаны все соединение , которые контролирует наш трекер' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	echo '<table>';
	echo '<tr>
	'.($PRIV['ip_util'] ? '<td><b>IP</b></td>' : '').'
	<td><b>Пользователь</b></td>
	<td><b>Раздал</b></td>
	<td><b>Скачал</b></td>
	<td><b>Начало</b></td>
	<td><b>Конец</b></td>
	<td><b>Посл. активность</b></td>
	<td><b>Клиент</b></td>
	<td><b>Статус</b></td>
	</tr>';
	
	while($row = $db->get_row($sql) ) {
		$user = get_user_info($row['userid']);
	
		echo '<tr>';
		if($PRIV['ip_util']) {
			echo '<td><a href="ip.util.php?ip='.$row['ip'].'">'.$row['ip'].'</a></td>';
		}
		echo '<td>'.($user ? '<a href="profile.php?id='.$user['id'].'">'.get_user_color($user['class'] , $user['name']).'</a>' : 'Гость').'</td>';
		echo '<td><font color="green">'.mksize($row['uploaded']).'</font></td>';
		echo '<td><font color="red">'.mksize($row['downloaded']).'</font></td>';
		echo '<td>'.convent_date($row['started']).'</td>';
		echo '<td>'.($row['prev_action'] != '0000-00-00 00:00:00' ? convent_date($row['prev_action']) : 'Не известно').'</td>';
		echo '<td>'.convent_date($row['last_action']).'</td>';
		echo '<td>'.htmlspecialchars($row['agent']).'</td>';
		echo '<td>'.($row['seeder'] ? '<img src="public/images/up.png">Раздающий' : '<img src="public/images/down.png">Качающий').'</td>';
		echo '</tr>';
	}
	
	echo '</table>';
	end_frame();
	foot();
	die();
}


/////////////////////////////////////////////////////////
//Информация о трекерах
/////////////////////////////////////////////////////////
if(isset($_GET['trackers']) && $arr['multi']) {
	$sql = $db->query("SELECT * FROM trackers WHERE tracker <> 'localhost' AND  torrent=".$id);
	if(!$db->num_rows($sql) ) {
		err($language['error_1']  , 'Трекеров не найдено' , 1);
	}
	
	head('Информация о трекерах');
	begin_frame('Информация о трекерах');
	msg('Данные могут не соответствовать настоящим' , '<a href="javascript:history.go(-1);">Вернуться к деталям</a>');
	echo '<table>';
	echo '<tr><td><b>Трекер</b></td><td><b>Раздают</b></td><td><b>Качают</b></td><td><b>Дата обновление</b></td></tr>';
	
	while($row = $db->get_row($sql) ) {
		echo '<tr>';
		echo '<td>'.$row['tracker'].'</td>';
		echo '<td>'.$row['seeders'].'</td>';
		echo '<td>'.$row['leechers'].'</td>';
		echo '<td>'.convent_date(get_date_time($row['lastchecked'])).'</td>';
		echo '</tr>';
	}
	
	echo '</table>';
	end_frame();
	foot();
	die();
}



/////////////////////////////////////////////////////////
//Общее
/////////////////////////////////////////////////////////
//ID релиза
$id = $arr['id'];
//Обложка
$image = ($arr['image'] ? 'public/downloads/images/'.$arr['image'] : 'public/images/default_avatar.gif');
//Имя релиза
$name = htmlspecialchars($arr['name']);

//Хеш релиза
$infohash = $arr['infohash'];

//Время добавления
$date = convent_date($arr['added']);

//Забанен
if($arr['banned']) {
	$banned = '<font color="red">'.$language['default_2'].'</font>';
}else {
	$banned = '<font color="green">'.$language['default_3'].'</font>';
}



//Информация о Категории
$category = categories_array($arr['id_category']);

//Имя категории
$cat_name = htmlspecialchars($category['name']);

//ID Категории
$cat_id = $category['id'];

//Картинка Категории
$cat_image = $category['image'];


//Теги
$tags = tags_echo($arr['tags']);
//Взяли
$downloaded = number_format($arr['downloaded']);
//Скачали
$completed = number_format($arr['completed']);
//Размер
$size = mksize($arr['size']);

//Определяем тип релиза
$type_seeders_array = array();
if($arr['multi']) {
	$type_seeders_array[] = '<font color="red"><b>'.$language['details_21'].'</b></a></font>';
}
if($arr['local_seeders']) {
	$type_seeders_array[] = '<font color="green"><b>'.$language['details_22'].'</b></font>';
}
$type_seeders = implode('+' , $type_seeders_array);


$descr = format_comment($arr['descr']);


/////////////////////////////////////////////////////////
//Пользователь
/////////////////////////////////////////////////////////
$user = get_user_info($arr['id_user']);
//ID пользователя
$id_user = $user['id'];
//Имя пользователя
$user_name = $user['name'];
//Класс пользователя
$user_class = $user['class'];



/////////////////////////////////////////////////////////
//Пиры
/////////////////////////////////////////////////////////
//Раздают
$seeders = number_format($arr['seeders']);
//Качают 
$leechers = number_format($arr['leechers']);
//Пиры 
$peers = number_format($seeders + $leechers);


//Мульти
$multi  = $arr['multi'];


//Мульти
if(!empty($arr['video_vkontakte']) ) {
	
	$video_vkontakte  = '<iframe src="'.htmlspecialchars($arr['video_vkontakte']).'" width="100%" height="360" frameborder="0"></iframe>';
}


//Добавить/Удалить закладку
if($USER) {
	$count_b = $db->super_query("SELECT COUNT(*) AS count FROM books WHERE id_torrent=".$id." AND id_user=".$USER['id']."");
	if(!$count_b['count']) {
		$book = '<a class="proleft" href="my.book.php?id='.$id.'&act=add">'.$language['details_25'].'</a>';
	} else {
		$book = '<a class="proleft" href="my.book.php?id='.$id.'&act=delete">'.$language['details_26'].'</a>';
	}
}

//Заголовок
head($name);

//Выводим статусы
comment_status();

//Редактирование
if($_GET['edit'] == '1') {
	msg($language['details_24']);
}

//Подключаем шаблон
require 'templates/'.$config['template'].'/tpl.details.php';	

//Подвал
stdfoot();
?>

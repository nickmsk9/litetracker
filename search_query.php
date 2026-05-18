<?php
/*
===================================================================
LiteTracker Source
===================================================================
by Nick
-------------------------------------------------------------------
Назначение: Мониторинг поиска
===================================================================
*/

//Подключаем главный системный файл
require 'system/init.php';

//Проверка авторизации
is_login();

//Только Администраторам , Модераторам
if(!$PRIV['search_query']) {
	err('Ошибка' , 'Доступ закрыт' , 1);
}

//Оповещение пользователя
if($_GET['send'] && $_GET['id']) {
	$id = (int)$_GET['id'];
	$db->query("SELECT * FROM search_query WHERE id=".$id);
	if(!$db->num_rows() ) {
		err('Ошибка' , 'Данная заявка не найдена' , 1);
	}
	$arr = $db->get_row();

	$name = 'Вы искали '.$arr['text'].'';
	$text = convent_date($arr['last_date']).' вы искали : [b]'.$arr['text'].'[/b]

			Ваша ссылка: [url=browse.php?search='.urlencode($arr['text']).']'.$_SERVER['HTTP_HOST'].'/browse.php?search='.urlencode($arr['text']).'[/url]';
	send_msg($name , $text , $arr['id_user'] , 0);

	//Оповещенная заявка
	$db->query("UPDATE search_query SET sended='1' WHERE id=".$id);

	header("Location:search_query.php?status=1");
	die();
}


//Принудительная очистка
if($_GET['clean'] && $PRIV['EDIT_PRIV']) {
	//Удаляем все записи
	$db->query("DELETE FROM search_query");
}

//Задаем выборку и параметры для GET запроса
$to_where = array();
$to_param = array();
if($_GET['dead']) {
	$to_where[] = 's.num_torrents = 0';
	$to_param[] = 'dead=1';
}

if(count($to_where) ) {
	$where = 'WHERE '.implode(' AND ' , $to_where);
}
if(count($to_param)) {
	$param = implode('&' , $to_param).'&';
}

//Вывод результата
head('Мониторинг поиска' , true);

if(empty($_GET['status']) ) {
	msg('Внимание' , 'После загрузки того или иного релиза, нажмите кнопку "Оповестить"');
}elseif($_GET['status'] == '1') {
	msg('Успешно' , 'Вы успешно оповестили пользователя');
}
begin_frame('Мониторинг поиска');


echo '<input type="button" value="Без торрентов" onClick="window.location.href=\'search_query.php?dead=1\'">&nbsp';
echo ($PRIV['EDIT_PRIV'] ? '<input type="button" value="Очистить" onClick="window.location.href=\'search_query.php?clean=1\'">' : '');



//Постраничная навигация
$count = $db->super_query("SELECT COUNT(*) AS c FROM search_query AS s ".$where);
$count = $count['c'];
list($pagertop, $pagerbottom, $limit) = pager('10' , $count, "search_query.php?".$param);

//Выводим все записи
$db->query("SELECT s.* , u.name , u.class
				FROM search_query AS s
				LEFT JOIN users AS u ON u.id = s.id_user
				".$where."
				ORDER BY last_date DESC
				".$limit."
				");
if($db->num_rows()) {
	echo $pagertop;
	?>
	<link href="public/css/torrenttable.css" rel="StyleSheet" type="text/css">
	<table widtd="100%" cellpadding="0" style="padding:0" class="tt">
	<tr class="header">
	<td><b>Пользователь</b></td><td widtd="20%"><b>Фраза</b></td><td><b>Время поиска</b></td><td><b>Кол-во раз</b></td><td><b>Кол-во торрентов</b></td> <td><b>Оповещен</b></td>  <td><b>Действия</b></td>
	</tr>
	<?php
	while($arr = $db->get_row() ) {
		echo '<tr>';

		echo '<td><a href="'.profile_href($arr['id_user']).'">'.get_user_color($arr['class'] , $arr['name']).'</a></td>';
		echo '<td><a href="browse.php?search='.htmlspecialchars((string) ($arr['text'] ?? ''), ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars((string) ($arr['text'] ?? ''), ENT_QUOTES, 'UTF-8').'</a></td>';
		echo '<td>'.convent_date($arr['last_date']).'</td>';
		echo '<td>'.$arr['num_views'].'</td>';
		echo '<td>'.($arr['num_torrents'] > 0 ? '<font color="green"><b>'.$arr['num_torrents'].'</b></font>' : '<font color="red"><b>'.$arr['num_torrents'].'</b></font>').'</td>';
		echo '<td>'.($arr['sended'] == 1 ? 'Да': 'Нет').'</td>';
		echo '<td><input type="button" value="Оповестить" onCLick="window.location.href=\'search_query.php?send=1&id='.$arr['id'].'\'"></td>';

		echo '</tr>';
	}
	?>
	</table>
	<?php

	echo $pagertop;
} else
	msg('Ошибка' , 'Мониторинг поиска пуст');
end_frame();
foot();
